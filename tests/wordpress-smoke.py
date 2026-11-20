"""Exercise the installed plugin against a disposable WordPress site."""

import os
import re
import subprocess
from html import unescape
from http.cookiejar import CookieJar
from pathlib import Path
from urllib.error import HTTPError
from urllib.parse import urlencode, urljoin
from urllib.request import HTTPCookieProcessor, build_opener

ROOT = Path(os.environ["WP_ROOT"])
URL = os.environ["WP_URL"]
SLUG = "schedsense-fast-diagnostics-for-action-scheduler"
PAGE = "/wp-admin/tools.php?page=schedsense_diagnostics"
PASSWORD = "audit-only-password"


def wp(*args):
    return subprocess.check_output(["wp", *args], cwd=ROOT, text=True).strip()


def request(session, path, data=None):
    payload = urlencode(data).encode() if data is not None else None
    with session.open(urljoin(URL, path), data=payload, timeout=30) as response:
        return response.read().decode()


def login(user):
    cookies = CookieJar()
    session = build_opener(HTTPCookieProcessor(cookies))
    request(session, "/wp-login.php")
    request(session, "/wp-login.php", {"log": user, "pwd": PASSWORD, "testcookie": "1"})
    assert any(cookie.name.startswith("wordpress_logged_in_")
               for cookie in cookies), f"Login failed for {user}"
    return session


def link(markup, target):
    for href in re.findall(r'href="([^"]+)"', markup):
        href = unescape(href)
        if "page=schedsense_diagnostics" in href and target in href:
            return href
    raise AssertionError(f"Missing SchedSense link for {target}")


def post(session, markup, action, **fields):
    form = next(form for form in re.findall(r"<form\b.*?</form>", markup, re.S)
                if f'value="{action}"' in form)
    nonce = re.search(r'name="_wpnonce" value="([^"]+)"', form)[1]
    return request(session, "/wp-admin/admin-post.php",
                   {"action": action, "_wpnonce": nonce, **fields})


def expect_http_error(session, path, code, data=None):
    try:
        request(session, path, data)
    except HTTPError as error:
        assert error.code == code, (path, error.code)
    else:
        raise AssertionError(f"Expected HTTP {code} at {path}")


def main():
    admin = login("auditadmin")
    overview = request(admin, PAGE)
    assert 'class="wrap schedsense-wrap"' in overview
    assert "The local wp-cron.php endpoint responded." in overview
    assert "The local REST API post-type endpoint responded with valid data." in overview
    actions_url = link(overview, "tab=failures")
    assert "Action Scheduler is not available" in request(admin, actions_url)
    assert "Diagnostics refreshed." in post(admin, overview, "schedsense_refresh")
    cron = post(admin, overview, "schedsense_spawn_cron")
    assert "WordPress accepted a standard cron spawn request." in cron or (
        "WordPress did not start another cron spawn." in cron
    )
    expect_http_error(admin, "/wp-admin/admin-post.php", 403,
                      {"action": "schedsense_refresh", "_wpnonce": "invalid"})

    wp("eval", """
        $requests = 0;
        add_filter('pre_http_request', static function ($pre, $args, $url) use (&$requests) {
            if ($args['redirection'] !== 0 || strpos($url, 'http://127.0.0.1:8080/') !== 0) {
                throw new RuntimeException('Unexpected diagnostic HTTP request settings.');
            }
            ++$requests;
            return array('response' => array('code' => 302, 'message' => 'Found'));
        }, 10, 3);
        $checks = array(new SchedSense\\Health\\Checks\\LoopbackCheck(),
                        new SchedSense\\Health\\Checks\\RestApiCheck());
        foreach ($checks as $check) {
            $result = $check->run(array())->to_array();
            if ($result['status'] !== 'warning' || strpos($result['summary'], '302') === false) {
                exit(1);
            }
        }
        if ($requests !== 2) {
            exit(1);
        }
    """)

    wp("eval", """
        $diagnosis = new SchedSense\\Diagnosis\\DiagnosisEngine();
        $snapshot = array(
            'queue' => array(
                'available' => true,
                'data_available' => true,
                'counts' => array('failed' => 0, 'stuck_in_progress' => 0, 'overdue_1h' => 20, 'overdue_24h' => 0),
            ),
            'checks' => array(),
            'environment' => array(),
        );
        if ($diagnosis->diagnose($snapshot)['status'] !== 'warning') {
            exit(1);
        }
        $snapshot['queue']['counts']['overdue_1h'] = 0;
        $snapshot['queue']['counts']['failed'] = 10;
        $snapshot['queue']['failure_sample'] = array('total' => 10, 'top' => 0);
        if ($diagnosis->diagnose($snapshot)['confidence'] !== 'Possible') {
            exit(1);
        }
        $snapshot['queue']['failure_sample']['top'] = 8;
        if ($diagnosis->diagnose($snapshot)['confidence'] !== 'Likely') {
            exit(1);
        }
    """)

    wp("user", "create", "auditmember", "member@example.test", "--role=subscriber",
       f"--user_pass={PASSWORD}")
    expect_http_error(login("auditmember"), PAGE, 403)
    assert "schedsense-admin-js" not in request(admin, "/wp-admin/index.php")

    fixture = ROOT / "wp-content/mu-plugins/schedsense-ci-queue.php"
    fixture.parent.mkdir(exist_ok=True)
    fixture.write_text("<?php\nadd_filter('action_scheduler_allow_async_request_runner', '__return_false');\n")
    scheduler = os.environ["ACTION_SCHEDULER_VERSION"]
    version = () if scheduler == "latest" else (f"--version={scheduler}",)
    wp("plugin", "install", "action-scheduler", *version, "--activate")
    wp("eval", """
        $store = ActionScheduler::store();
        $failed = as_schedule_single_action(time() + 3600, 'ci_failed', array('ci_private_argument'), 'schedsense-ci');
        $store->mark_failure($failed);
        $future = as_schedule_single_action(time() + 3600, 'ci_future', array(), 'schedsense-ci');
        for ($i = 0; $i < 26; ++$i) {
            as_schedule_single_action(time() - 1800 - $i, 'ci_recent', array($i), 'schedsense-ci');
        }
        as_schedule_single_action(time() - 7200, 'ci_older', array(), 'schedsense-ci');
        update_option('schedsense_ci_action_ids', array('failed' => $failed, 'future' => $future), false);
    """)
    print(wp("eval", """
        delete_transient('schedsense_source_map');
        $writes = 0;
        add_filter('pre_set_transient_schedsense_source_map', static function ($value) use (&$writes) {
            ++$writes;
            return $value;
        });
        $resolver = new SchedSense\\Scheduler\\SourceResolver();
        $hooks = array_map(static function ($i) { return 'ci_source_' . $i; }, range(1, 300));
        $cold = $resolver->resolve_many($hooks);
        $warm = $resolver->resolve_many($hooks);
        if (count($cold) !== 300 || $cold !== $warm || $writes !== 1) {
            exit(1);
        }
        delete_transient('schedsense_source_map');
        echo 'Source cache used one write for 300 cold hooks and zero writes when warm.';
    """))
    failed, future = (wp("option", "pluck", "schedsense_ci_action_ids", key)
                      for key in ("failed", "future"))
    overview = post(admin, overview, "schedsense_refresh")
    assert 'id="schedsense-queue-title"' in overview
    assert "Action Scheduler WP-Cron runner" in overview
    assert "Scheduled time:" in overview
    wp("eval", """
        $queue = get_transient('schedsense_diagnostic_snapshot')['queue'];
        if ($queue['failure_sample'] !== array('total' => 1, 'top' => 0)) {
            exit(1);
        }
    """)
    actions_url = link(overview, "tab=failures")
    failures = request(admin, actions_url)
    assert "<code>ci_failed</code>" in failures
    assert "ci_private_argument" not in failures

    recent_url = actions_url + "&status=pending&age=under_1h&group=schedsense-ci"
    recent = request(admin, recent_url)
    assert "Showing 25 of 26 matching actions." in recent
    assert recent.count("<code>ci_recent</code>") == 25
    assert "<code>ci_older</code>" not in recent and "<code>ci_future</code>" not in recent
    page_two = request(admin, link(recent, "paged=2"))
    assert "Showing 1 of 26 matching actions." in page_two
    assert page_two.count("<code>ci_recent</code>") == 1
    assert request(admin, link(page_two, "paged=1")).count("<code>ci_recent</code>") == 25

    sources = request(admin, link(overview, "tab=sources"))
    assert "ci_failed" in sources
    report = request(admin, link(overview, "tab=report"))
    assert 'id="schedsense-report"' in report
    assert "ci_private_argument" not in report and str(ROOT) not in report
    assert "SchedSense caches cleared." in post(admin, report, "schedsense_clear_cache")

    assert "This action is no longer failed" in post(
        admin, failures, "schedsense_delete_failed_action", action_id=future)
    assert "Failed action record deleted." in post(
        admin, failures, "schedsense_delete_failed_action", action_id=failed)
    assert wp("eval", f"echo ActionScheduler::store()->get_status({future});") == "pending"
    assert "<code>ci_failed</code>" not in request(admin, actions_url)

    wp("transient", "set", "schedsense_ci_unrelated", "keep", "180")
    for phase in ("deactivate", "uninstall"):
        wp("transient", "set", "schedsense_diagnostic_snapshot", "fixture", "180")
        wp("transient", "set", "schedsense_source_map", "fixture", "180")
        wp("plugin", phase, SLUG)
        assert wp("eval", """
            if (false !== get_transient('schedsense_diagnostic_snapshot') ||
                false !== get_transient('schedsense_source_map') ||
                'keep' !== get_transient('schedsense_ci_unrelated')) {
                exit(1);
            }
            echo 'ok';
        """) == "ok"
    assert wp("eval", f"echo ActionScheduler::store()->get_status({future});") == "pending"
    debug = ROOT / "wp-content/debug.log"
    if debug.exists():
        assert not debug.read_text().strip(), "WordPress wrote to debug.log"
    print("WordPress admin, Action Scheduler and lifecycle smoke tests passed.")


if __name__ == "__main__":
    main()

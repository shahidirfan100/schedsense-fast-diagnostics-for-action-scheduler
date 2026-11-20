#!/usr/bin/env bash
set -euo pipefail

slug=schedsense-fast-diagnostics-for-action-scheduler
baseline=e64870609fa930de1263975981f6065fad198d26
fixture="$GITHUB_WORKSPACE/tests/performance.php"
csv="$RUNNER_TEMP/schedsense-performance.csv"

git archive --format=zip --prefix="$slug/" -o "$RUNNER_TEMP/$slug.zip" "$baseline" \
  "$slug.php" readme.txt uninstall.php admin includes languages

cd "$WP_ROOT"
wp config set WP_ENVIRONMENT_TYPE local
export SCHEDSENSE_BENCHMARK=1

printf 'version,actions,view,cache,queries,http,milliseconds\n' > "$csv"
for size in 10000 100000; do
  wp eval-file "$fixture" seed "$size"
  for version in baseline candidate; do
    if [[ $version == baseline ]]; then
      archive="$RUNNER_TEMP/$slug.zip"
    else
      archive="$GITHUB_WORKSPACE/$slug.zip"
    fi
    wp plugin install "$archive" --force --activate --quiet
    for view in overview actions deep; do
      for repeat in {1..5}; do
        for cache in cold warm; do
          wp eval-file "$fixture" measure "$size" "$version" "$view" "$cache" >> "$csv"
        done
      done
    done
  done
done

python3 - "$csv" <<'PY'
import csv
import statistics
import sys
from collections import defaultdict

groups = defaultdict(list)
with open(sys.argv[1], newline="") as source:
    for row in csv.DictReader(source):
        key = tuple(row[field] for field in ("actions", "view", "cache", "version"))
        groups[key].append(tuple(float(row[field]) for field in ("queries", "http", "milliseconds")))

print("Actions | View | Cache | SQL queries before → after | HTTP calls before → after | Median ms before → after")
for size, view, cache in sorted({key[:3] for key in groups}):
    before, after = (groups[size, view, cache, variant] for variant in ("baseline", "candidate"))
    assert len(before) == len(after) == 5
    old_queries, old_http, old_ms = map(statistics.median, zip(*before))
    new_queries, new_http, new_ms = map(statistics.median, zip(*after))
    print(f"{size} | {view} | {cache} | {old_queries:g} → {new_queries:g} | {old_http:g} → {new_http:g} | {old_ms:.1f} → {new_ms:.1f}")
    if view != "overview":
        assert new_http == 0
        if cache == "cold":
            assert new_queries < old_queries
PY

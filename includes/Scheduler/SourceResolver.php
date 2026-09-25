<?php
/**
 * Resolve a scheduled hook to its likely owning plugin.
 *
 * @package QueueHealthMonitor
 */

namespace QueueHealthMonitor\Scheduler;

use ReflectionException;
use ReflectionFunction;
use ReflectionMethod;
use Throwable;

defined( 'ABSPATH' ) || exit;

final class SourceResolver {
	/** @var array|null */
	private $plugins;

	/**
	 * Resolve a hook using registered callback metadata only.
	 *
	 * @param string $hook Hook name.
	 * @return array
	 */
	public function resolve( $hook ) {
		$hook = substr( sanitize_text_field( $hook ), 0, 191 );
		if ( '' === $hook ) {
			return $this->unknown();
		}

		$cache = get_transient( 'qhm_source_map' );
		$cache = is_array( $cache ) ? $cache : array();
		if ( isset( $cache[ $hook ] ) && is_array( $cache[ $hook ] ) ) {
			return $cache[ $hook ];
		}

		$result = $this->inspect_registered_callbacks( $hook );
		if ( 'unknown' === $result['type'] ) {
			$result = $this->infer_from_hook( $hook );
		}

		$cache[ $hook ] = $result;
		if ( count( $cache ) > 300 ) {
			$cache = array_slice( $cache, -300, null, true );
		}
		set_transient( 'qhm_source_map', $cache, DAY_IN_SECONDS );
		return $result;
	}

	/**
	 * Inspect callback files without executing callbacks.
	 *
	 * @param string $hook Hook name.
	 * @return array
	 */
	private function inspect_registered_callbacks( $hook ) {
		global $wp_filter;
		if ( empty( $wp_filter[ $hook ] ) || ! is_object( $wp_filter[ $hook ] ) || ! isset( $wp_filter[ $hook ]->callbacks ) ) {
			return $this->unknown();
		}

		foreach ( $wp_filter[ $hook ]->callbacks as $callbacks ) {
			foreach ( $callbacks as $callback_data ) {
				if ( empty( $callback_data['function'] ) ) {
					continue;
				}
				$reflection = $this->reflect( $callback_data['function'] );
				if ( ! $reflection ) {
					continue;
				}
				$file = $reflection->getFileName();
				if ( is_string( $file ) ) {
					$result = $this->from_file( $file );
					if ( $result ) {
						return $result;
					}
				}
			}
		}
		return $this->unknown();
	}

	/**
	 * Create reflection for supported callback structures.
	 *
	 * @param mixed $callback Registered callback.
	 * @return ReflectionFunction|ReflectionMethod|null
	 */
	private function reflect( $callback ) {
		try {
			if ( is_array( $callback ) && 2 === count( $callback ) ) {
				return new ReflectionMethod( $callback[0], $callback[1] );
			}
			if ( is_string( $callback ) && false !== strpos( $callback, '::' ) ) {
				return new ReflectionMethod( $callback );
			}
			if ( is_object( $callback ) && ! ( $callback instanceof \Closure ) ) {
				return new ReflectionMethod( $callback, '__invoke' );
			}
			if ( is_string( $callback ) || $callback instanceof \Closure ) {
				return new ReflectionFunction( $callback );
			}
		} catch ( ReflectionException $exception ) {
			return null;
		} catch ( Throwable $throwable ) {
			return null;
		}
		return null;
	}

	/**
	 * Map a callback file to a plugin or must-use plugin.
	 *
	 * @param string $file Absolute callback filename.
	 * @return array|null
	 */
	private function from_file( $file ) {
		$roots = array(
			wp_normalize_path( WP_PLUGIN_DIR )   => 'plugin',
			wp_normalize_path( WPMU_PLUGIN_DIR ) => 'mu-plugin',
		);

		foreach ( $roots as $root => $type ) {
			$relative = self::normalize_plugin_path( $file, $root );
			if ( '' === $relative ) {
				continue;
			}
			$slug = strtok( $relative, '/' );
			$name = $this->plugin_name_for_slug( $slug );
			return array(
				'name'       => '' !== $name ? $name : ucwords( str_replace( array( '-', '_' ), ' ', $slug ) ),
				'confidence' => 'high',
				'evidence'   => sprintf(
					/* translators: %s: Redacted plugin-relative callback path. */
					__( 'Registered callback file: %s', 'queue-health-monitor' ),
					$relative
				),
				'path'       => $relative,
				'type'       => $type,
			);
		}
		return null;
	}

	/**
	 * Normalize a file to a root-relative path without assuming wp-content.
	 *
	 * @param string $file Absolute callback path.
	 * @param string $root Plugin directory root.
	 * @return string
	 */
	public static function normalize_plugin_path( $file, $root ) {
		$file = wp_normalize_path( (string) $file );
		$root = untrailingslashit( wp_normalize_path( (string) $root ) );
		if ( '' === $root || 0 !== strpos( $file, trailingslashit( $root ) ) ) {
			return '';
		}
		return ltrim( substr( $file, strlen( $root ) ), '/' );
	}

	/**
	 * Resolve a plugin display name without repeated plugin scans.
	 *
	 * @param string $slug Plugin directory slug.
	 * @return string
	 */
	private function plugin_name_for_slug( $slug ) {
		if ( null === $this->plugins ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
			$this->plugins = get_plugins();
		}
		foreach ( $this->plugins as $basename => $data ) {
			if ( strtok( $basename, '/' ) === $slug ) {
				return isset( $data['Name'] ) ? wp_strip_all_tags( $data['Name'] ) : '';
			}
		}
		return '';
	}

	/**
	 * Conservative fallback inference from common vendor prefixes.
	 *
	 * @param string $hook Hook name.
	 * @return array
	 */
	private function infer_from_hook( $hook ) {
		$patterns = array(
			'woocommerce_' => 'WooCommerce',
			'wc_'          => 'WooCommerce',
			'wcs_'         => 'WooCommerce Subscriptions',
		);
		foreach ( $patterns as $prefix => $name ) {
			if ( 0 === strpos( $hook, $prefix ) ) {
				return array(
					'name'       => $name,
					'confidence' => 'possible',
					'evidence'   => __( 'Inferred from the hook prefix; no callback file was available.', 'queue-health-monitor' ),
					'path'       => '',
					'type'       => 'inference',
				);
			}
		}
		return $this->unknown();
	}

	/** @return array */
	private function unknown() {
		return array(
			'name'       => __( 'Unknown', 'queue-health-monitor' ),
			'confidence' => 'unavailable',
			'evidence'   => __( 'No registered callback or reliable plugin path was available.', 'queue-health-monitor' ),
			'path'       => '',
			'type'       => 'unknown',
		);
	}
}

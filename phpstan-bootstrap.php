<?php
/**
 * PHPStan analysis bootstrap.
 *
 * Defines plugin constants that are normally created at runtime by the
 * main plugin file (offload-plus.php). PHPStan analyzes the
 * codebase statically without executing anything, so it never sees the
 * `define()` calls there. Without these stubs, every reference to
 * `OFFLOAD_PLUS_DIR` and friends produces "Constant not found".
 *
 * This file is referenced from phpstan.neon's `bootstrapFiles:` list.
 * It is excluded from the wp.org deploy via .distignore. It is NOT
 * loaded at plugin runtime — only by PHPStan during analysis.
 *
 * @package OffloadPlus
 */

if ( ! defined( 'OFFLOAD_PLUS_VERSION' ) ) {
	define( 'OFFLOAD_PLUS_VERSION', '0.0.0-phpstan-stub' );
}
if ( ! defined( 'OFFLOAD_PLUS_DIR' ) ) {
	define( 'OFFLOAD_PLUS_DIR', __DIR__ . '/' );
}
if ( ! defined( 'OFFLOAD_PLUS_URL' ) ) {
	define( 'OFFLOAD_PLUS_URL', 'https://example.test/wp-content/plugins/offload-plus/' );
}
if ( ! defined( 'OFFLOAD_PLUS_FILE' ) ) {
	define( 'OFFLOAD_PLUS_FILE', __DIR__ . '/offload-plus.php' );
}

// Optional development-mode constants referenced by some code paths.
if ( ! defined( 'OFFLOAD_PLUS_DEV_MODE' ) ) {
	define( 'OFFLOAD_PLUS_DEV_MODE', false );
}
if ( ! defined( 'OFFLOAD_PLUS_VERBOSE_LOGGING' ) ) {
	define( 'OFFLOAD_PLUS_VERBOSE_LOGGING', false );
}
if ( ! defined( 'DILUX_API_URL' ) ) {
	define( 'DILUX_API_URL', 'https://api.diluxone.com/cloud-storage-wp/v1' );
}

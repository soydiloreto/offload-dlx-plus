<?php
/**
 * Plugin Name: Offload Plus
 * Description: Offload WordPress media files to Azure Blob Storage or Dilux One Cloud — complete replacement for /uploads/ directory using stream wrappers.
 * Version: 1.1.0-dev
 * Author: Pablo Ariel Di Loreto
 * Author URI: https://pablodiloreto.com/
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: offload-plus
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.9
 * Requires PHP: 7.4
 *
 * @package OffloadPlus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin constants
define( 'OFFLOAD_PLUS_VERSION', '1.1.0-dev' );
define( 'OFFLOAD_PLUS_DIR', plugin_dir_path( __FILE__ ) );
define( 'OFFLOAD_PLUS_URL', plugin_dir_url( __FILE__ ) );
define( 'OFFLOAD_PLUS_FILE', __FILE__ );

// Load enhanced autoloader
require_once OFFLOAD_PLUS_DIR . 'includes/enhanced-autoloader.php';
require_once OFFLOAD_PLUS_DIR . 'includes/upgrade.php';

// Carry over data stored under the plugin's previous name, before anything reads it.
add_action( 'plugins_loaded', 'offload_plus_migrate', 1 );

use OffloadPlus\Plugin;

global $offload_plus_plugin;

/**
 * Initialize the enhanced plugin.
 *
 * Translations for plugins hosted on wordpress.org are loaded automatically
 * by WordPress since 4.6, so we no longer call load_plugin_textdomain().
 *
 * @return void
 */
function offload_plus_init() {
	global $offload_plus_plugin;

	$offload_plus_plugin = Plugin::get_instance();
	$offload_plus_plugin->init();
}
add_action( 'plugins_loaded', 'offload_plus_init', 10 );

/**
 * Plugin activation hook.
 */
register_activation_hook(
	__FILE__,
	function () {
		\OffloadPlus\Logger::log( '[Offload Plus] Activation hook fired.', 'info', true );
		offload_plus_migrate();
		Plugin::activate();
	}
);

/**
 * Plugin deactivation hook.
 */
register_deactivation_hook(
	__FILE__,
	function () {
		\OffloadPlus\Logger::log( '[Offload Plus] Deactivation hook fired.', 'info', true );
		Plugin::deactivate();
	}
);

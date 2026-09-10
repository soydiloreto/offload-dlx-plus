<?php
/**
 * Migration from the plugin's previous name.
 *
 * The plugin used to be called "Dilux Cloud Storage" and prefixed everything it
 * stored with `dilux_cs_`. Renaming it to Offload Plus renamed the options and
 * the file-tracking table, so an existing install would come back up with an
 * empty table and default settings — every offloaded file would look local
 * again. This runs once and moves the old data over.
 *
 * Credentials do not need touching: the encrypted payload format never changed,
 * and Crypto still reads the old `DILUXENC1:` prefix.
 *
 * phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange
 * phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter
 *
 * @package OffloadPlus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const OFFLOAD_PLUS_MIGRATED = 'offload_plus_migrated';

/**
 * Options this plugin owns, without their prefix.
 *
 * @return string[]
 */
function offload_plus_migrated_options(): array {
	return array(
		'activation_timestamp',
		'config',
		'connection_health',
		'db_version',
		'debug_enabled',
		'failed_files',
		'plugin_state',
		'sync_meta',
		'sync_progress',
	);
}

/**
 * Move the file-tracking table over, keeping whatever rows it already had.
 *
 * Activation may have created an empty table under the new name before this
 * runs; in that case the empty one is dropped so the old one can take its
 * place. A new table with rows in it means the migration already happened.
 *
 * @return void
 */
function offload_plus_migrate_table(): void {
	global $wpdb;

	$vieja = $wpdb->prefix . 'dilux_cs_files';
	$nueva = $wpdb->prefix . 'offload_plus_files';

	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $vieja ) ) !== $vieja ) {
		return;
	}

	if ( $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $nueva ) ) === $nueva ) {
		if ( (int) $wpdb->get_var( "SELECT COUNT(*) FROM `{$nueva}`" ) > 0 ) {
			return;
		}
		$wpdb->query( "DROP TABLE `{$nueva}`" );
	}

	$wpdb->query( "RENAME TABLE `{$vieja}` TO `{$nueva}`" );
}

/**
 * Move the options over, then forget what the cache remembers about them.
 *
 * Direct SQL leaves the options cache stale, and an option written under the
 * new name by activation would collide with the UNIQUE key on `option_name`
 * and abort the whole statement, so each colliding row is deleted first.
 *
 * @return void
 */
function offload_plus_migrate_options(): void {
	global $wpdb;

	foreach ( offload_plus_migrated_options() as $nombre ) {
		$vieja = 'dilux_cs_' . $nombre;
		$nueva = 'offload_plus_' . $nombre;

		$existe = $wpdb->get_var( $wpdb->prepare( "SELECT option_id FROM {$wpdb->options} WHERE option_name = %s", $vieja ) );
		if ( null === $existe ) {
			continue;
		}

		$wpdb->delete( $wpdb->options, array( 'option_name' => $nueva ), array( '%s' ) );
		$wpdb->update( $wpdb->options, array( 'option_name' => $nueva ), array( 'option_name' => $vieja ), array( '%s' ), array( '%s' ) );

		wp_cache_delete( $vieja, 'options' );
		wp_cache_delete( $nueva, 'options' );
	}

	wp_cache_delete( 'alloptions', 'options' );
	wp_cache_delete( 'notoptions', 'options' );
}

/**
 * Drop the old transients. They are caches — whatever they held is rebuilt on
 * demand under the new names, and leaving them behind just wastes rows.
 *
 * @return void
 */
function offload_plus_migrate_forget_transients(): void {
	global $wpdb;

	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_dilux_cs_' ) . '%',
			$wpdb->esc_like( '_transient_timeout_dilux_cs_' ) . '%'
		)
	);
}

/**
 * Run the migration once.
 *
 * @return void
 */
function offload_plus_migrate(): void {
	if ( get_option( OFFLOAD_PLUS_MIGRATED ) ) {
		return;
	}

	offload_plus_migrate_table();
	offload_plus_migrate_options();
	offload_plus_migrate_forget_transients();

	update_option( OFFLOAD_PLUS_MIGRATED, 1, false );
}

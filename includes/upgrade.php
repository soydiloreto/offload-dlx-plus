<?php
/**
 * Migration from the plugin's previous names.
 *
 * The plugin was called "Dilux Cloud Storage" (`dilux_cs_`) and then "Offload
 * Plus" (`offload_plus_`) before settling on Offload+ (`offload_dlx_plus_`).
 * Each rename renamed the options and the file-tracking table, so an existing
 * install would come back up with an empty table and default settings — every
 * offloaded file would look local again. This runs once and moves the old data
 * over, from whichever prefix that install happens to carry.
 *
 * Credentials do not need touching: the encrypted payload format never changed,
 * and Crypto still reads the older `DILUXENC1:` and `OFFLOADPLUSENC1:`
 * prefixes.
 *
 * phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.NoCaching
 * phpcs:disable WordPress.DB.DirectDatabaseQuery.SchemaChange
 * phpcs:disable PluginCheck.Security.DirectDB.UnescapedDBParameter
 *
 * @package OffloadDlxPlus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const OFFLOAD_DLX_PLUS_MIGRATED = 'offload_dlx_plus_migrated';

/** The current prefix, written once. */
const OFFLOAD_DLX_PLUS_PREFIX = 'offload_dlx_plus_';

/**
 * Every prefix this plugin has stored data under, oldest first.
 *
 * @return string[]
 */
function offload_dlx_plus_old_prefixes(): array {
	return array( 'dilux_cs_', 'offload_plus_' );
}

/**
 * Options this plugin owns, without their prefix.
 *
 * @return string[]
 */
function offload_dlx_plus_migrated_options(): array {
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
 * @param string $viejo The old prefix to move from.
 * @return void
 */
function offload_dlx_plus_migrate_table( string $viejo ): void {
	global $wpdb;

	$vieja = $wpdb->prefix . $viejo . 'files';
	$nueva = $wpdb->prefix . OFFLOAD_DLX_PLUS_PREFIX . 'files';

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
 * @param string $viejo The old prefix to move from.
 * @return void
 */
function offload_dlx_plus_migrate_options( string $viejo ): void {
	global $wpdb;

	foreach ( offload_dlx_plus_migrated_options() as $nombre ) {
		$vieja = $viejo . $nombre;
		$nueva = OFFLOAD_DLX_PLUS_PREFIX . $nombre;

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
 * @param string $viejo The old prefix to move from.
 * @return void
 */
function offload_dlx_plus_migrate_forget_transients( string $viejo ): void {
	global $wpdb;

	$wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
			$wpdb->esc_like( '_transient_' . $viejo ) . '%',
			$wpdb->esc_like( '_transient_timeout_' . $viejo ) . '%'
		)
	);
}

/**
 * Run the migration once.
 *
 * @return void
 */
function offload_dlx_plus_migrate(): void {
	if ( get_option( OFFLOAD_DLX_PLUS_MIGRATED ) ) {
		return;
	}

	foreach ( offload_dlx_plus_old_prefixes() as $viejo ) {
		offload_dlx_plus_migrate_table( $viejo );
		offload_dlx_plus_migrate_options( $viejo );
		offload_dlx_plus_migrate_forget_transients( $viejo );
	}

	update_option( OFFLOAD_DLX_PLUS_MIGRATED, 1, false );
}

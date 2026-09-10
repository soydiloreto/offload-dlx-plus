<?php
/**
 * Admin: Status & Tools tab template.
 *
 * Local variables ($current_state, $is_configured, $plugin_config, etc.) are
 * populated by Admin::render_tab_content() in the calling scope. Suppress the
 * prefix sniff for this template:
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 *
 * @package OffloadPlus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OffloadPlus\Admin;
use OffloadPlus\ConfigManager;
use OffloadPlus\Enums\PluginState;

// Get all offload_plus_ options from database. The pattern is hardcoded to our
// own option-name prefix; cache layers don't apply since this is a one-shot
// admin diagnostic page.
global $wpdb;
// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- Diagnostic-only read, hardcoded LIKE pattern, $wpdb->options is the WP-managed table name.
$offload_plus_options = $wpdb->get_results(
	"SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE 'offload_plus_%'",
	ARRAY_A
);

// Build config array for display - unserialize values for proper JSON export
$config_data = array();
foreach ( $offload_plus_options as $option ) {
	$value = $option['option_value'];

	// Try to unserialize - WordPress auto-serializes arrays/objects in options.
	// Source bytes can only have been written by code we control via
	// update_option(); Object-Injection risk does not apply.
	// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged,WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize -- See comment above.
	$unserialized = @unserialize( $value );

	// Use unserialized value if it worked, otherwise use original string
	$config_data[ $option['option_name'] ] = ( $unserialized !== false || $value === 'b:0;' )
		? $unserialized
		: $value;
}

// Get current state
$current_state = ConfigManager::get_state();
$is_configured = ConfigManager::is_configured();
$is_offloading = ConfigManager::is_offloading_enabled();

// Get plugin config for basic info
$plugin_config = ConfigManager::get_config();

// Health context — when the cloud connection is unhealthy (decrypt failure,
// permission denied, etc.) the cards below switch to "paused" copy so the
// user does not see contradictory states (e.g. "Offloading: Active" while
// the underlying credentials are unreadable). The state machine itself is
// left untouched; we only change how it is *displayed*. The full diagnosis
// and CTA live in the red banner rendered above this template.
$health      = ConfigManager::get_connection_health();
$is_paused   = $health['status'] === 'unhealthy';
$pause_cause = (string) ( $health['error_code'] ?? '' );
$pause_label = $is_paused ? Admin::pause_reason_short( $pause_cause ) : '';

// Section to render: 'status' (default) or 'tools'.
// Set in class-offload-plus-admin.php based on the current tab.
$section = $section ?? 'status';
?>

<div class="offload-plus-status-tools">
	<?php if ( $section === 'status' ) : ?>
	<!-- =================================================================
		SECTION 1: SYSTEM STATUS
		================================================================= -->
	<div class="status-section">
		<!-- Header -->
		<div class="section-header-main">
			<h2><?php esc_html_e( 'System Status', 'offload-plus' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'View plugin status and system information.', 'offload-plus' ); ?>
			</p>
		</div>

		<!-- Plugin State Cards -->
		<div class="state-cards">
			<!-- Plugin State -->
			<div class="state-card">
				<div class="state-icon">
					<span class="dashicons dashicons-admin-plugins"></span>
				</div>
				<div class="state-content">
					<h3><?php esc_html_e( 'Plugin State', 'offload-plus' ); ?></h3>
					<p class="state-value">
						<?php
						$badge_class = 'state-gray';
						switch ( $current_state ) {
							case PluginState::CONFIGURED:
								$badge_class = 'state-blue';
								break;
							case PluginState::SYNCING:
								$badge_class = 'state-yellow';
								break;
							case PluginState::SYNCED:
								$badge_class = 'state-green';
								break;
							case PluginState::OFFLOADING_ACTIVE:
								$badge_class = 'state-purple';
								break;
						}
						if ( $is_paused ) {
							$badge_class .= ' is-paused';
						}
						echo '<span class="state-badge ' . esc_attr( $badge_class ) . '">' . esc_html( PluginState::get_state_name( $current_state ) ) . '</span>';
						?>
					</p>
					<?php if ( $is_paused ) : ?>
					<p class="state-pause-reason" style="margin-top:6px; color:#856404; font-size:12px;">
						<?php
						printf(
							/* translators: %s: short reason, e.g. "credentials unreadable" */

							esc_html__( 'Paused (%s) — see banner above.', 'offload-plus' ),
							esc_html( $pause_label )
						);
						?>
					</p>
					<?php endif; ?>
				</div>
			</div>

			<!-- Configuration Status -->
			<div class="state-card">
				<div class="state-icon">
					<span class="dashicons dashicons-admin-settings"></span>
				</div>
				<div class="state-content">
					<h3><?php esc_html_e( 'Configuration', 'offload-plus' ); ?></h3>
					<?php
					// Vocabulary intentionally identical to admin-overview.php.
					// Keep these strings in sync with the Overview tab.
					$is_decrypt_failure = $is_paused && $pause_cause === 'decrypt_failed';
					?>
					<p class="state-value">
						<?php if ( $is_configured && ! $is_paused ) : ?>
							<span class="status-indicator status-success"></span>
							<?php esc_html_e( 'Configured', 'offload-plus' ); ?>
						<?php elseif ( $is_decrypt_failure ) : ?>
							<span class="status-indicator" style="background:#dba617;"></span>
							<?php esc_html_e( 'Awaiting Re-entry', 'offload-plus' ); ?>
						<?php elseif ( $is_configured && $is_paused ) : ?>
							<span class="status-indicator" style="background:#dba617;"></span>
							<?php
							printf(
								/* translators: %s: short reason for the pause */
								esc_html__( 'Paused (%s)', 'offload-plus' ),
								esc_html( $pause_label )
							);
							?>
						<?php else : ?>
							<span class="status-indicator status-inactive"></span>
							<?php esc_html_e( 'Not Configured', 'offload-plus' ); ?>
						<?php endif; ?>
					</p>
					<?php if ( $is_configured && ! $is_paused && ! empty( $plugin_config['cloud_provider'] ) ) : ?>
						<p class="state-details">
							<?php
							echo wp_kses(
								/* translators: %s: storage provider name (Azure) wrapped in <strong> */
								sprintf( __( 'Provider: %s', 'offload-plus' ), '<strong>Azure</strong>' ),
								array( 'strong' => array() )
							);
							?>
						</p>
					<?php elseif ( $is_decrypt_failure ) : ?>
						<p class="state-details" style="color:#856404;">
							<?php esc_html_e( 'Stored credentials cannot be decrypted. See banner above.', 'offload-plus' ); ?>
						</p>
						<p class="state-details">
							<a href="?page=offload-plus&tab=cloud-provider" class="button button-primary button-small">
								<?php esc_html_e( 'Re-enter Credentials', 'offload-plus' ); ?>
							</a>
						</p>
					<?php elseif ( $is_configured && $is_paused ) : ?>
						<p class="state-details" style="color:#856404;">
							<?php esc_html_e( 'See banner above for details.', 'offload-plus' ); ?>
						</p>
					<?php endif; ?>
				</div>
			</div>

			<!-- Offloading Status -->
			<div class="state-card">
				<div class="state-icon">
					<span class="dashicons dashicons-cloud"></span>
				</div>
				<div class="state-content">
					<h3><?php esc_html_e( 'Offloading', 'offload-plus' ); ?></h3>
					<p class="state-value">
						<?php if ( $is_offloading && ! $is_paused ) : ?>
							<span class="status-indicator status-success"></span>
							<?php esc_html_e( 'Active', 'offload-plus' ); ?>
						<?php elseif ( $is_offloading && $is_paused ) : ?>
							<span class="status-indicator" style="background:#dba617;"></span>
							<?php
							printf(
								/* translators: %s: short reason for the pause */
								esc_html__( 'Paused (%s)', 'offload-plus' ),
								esc_html( $pause_label )
							);
							?>
						<?php else : ?>
							<span class="status-indicator status-inactive"></span>
							<?php esc_html_e( 'Inactive', 'offload-plus' ); ?>
						<?php endif; ?>
					</p>
					<?php if ( $is_offloading && $is_paused ) : ?>
					<p class="state-details" style="margin-top:6px; color:#856404; font-size:12px;">
						<?php esc_html_e( 'Falling back to local storage for new uploads.', 'offload-plus' ); ?>
					</p>
					<?php endif; ?>
				</div>
			</div>

			<!-- Database Status -->
			<div class="state-card">
				<div class="state-icon">
					<span class="dashicons dashicons-database"></span>
				</div>
				<div class="state-content">
					<h3><?php esc_html_e( 'Database', 'offload-plus' ); ?></h3>
					<p class="state-value">
						<span class="status-indicator status-success"></span>
						<?php
						/* translators: %d: number of stored options */
						echo esc_html( sprintf( __( '%d options stored', 'offload-plus' ), count( $offload_plus_options ) ) );
						?>
					</p>
				</div>
			</div>
		</div>

		<!-- System Information -->
		<div class="system-info-section">
			<h3>
				<span class="dashicons dashicons-info"></span>
				<?php esc_html_e( 'System Information', 'offload-plus' ); ?>
			</h3>

			<div class="info-grid">
				<div class="info-card">
					<h4><?php esc_html_e( 'WordPress', 'offload-plus' ); ?></h4>
					<table class="info-table">
						<tr>
							<td><?php esc_html_e( 'Version', 'offload-plus' ); ?></td>
							<td><strong><?php echo esc_html( get_bloginfo( 'version' ) ); ?></strong></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Multisite', 'offload-plus' ); ?></td>
							<td><strong><?php echo esc_html( is_multisite() ? __( 'Yes', 'offload-plus' ) : __( 'No', 'offload-plus' ) ); ?></strong></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Upload Directory', 'offload-plus' ); ?></td>
							<td><code><?php echo esc_html( wp_upload_dir()['basedir'] ); ?></code></td>
						</tr>
					</table>
				</div>

				<div class="info-card">
					<h4><?php esc_html_e( 'PHP Environment', 'offload-plus' ); ?></h4>
					<table class="info-table">
						<tr>
							<td><?php esc_html_e( 'PHP Version', 'offload-plus' ); ?></td>
							<td><strong><?php echo esc_html( PHP_VERSION ); ?></strong></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Memory Limit', 'offload-plus' ); ?></td>
							<td><strong><?php echo esc_html( ini_get( 'memory_limit' ) ); ?></strong></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Max Upload Size', 'offload-plus' ); ?></td>
							<td><strong><?php echo esc_html( (string) size_format( wp_max_upload_size() ) ); ?></strong></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Max Execution Time', 'offload-plus' ); ?></td>
							<td><strong><?php echo esc_html( ini_get( 'max_execution_time' ) ); ?>s</strong></td>
						</tr>
					</table>
				</div>

				<div class="info-card">
					<h4><?php esc_html_e( 'Plugin', 'offload-plus' ); ?></h4>
					<table class="info-table">
						<tr>
							<td><?php esc_html_e( 'Version', 'offload-plus' ); ?></td>
							<td><strong><?php echo esc_html( Admin::get_plugin_version() ); ?></strong></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'DB Schema Version', 'offload-plus' ); ?></td>
							<td><strong><?php echo esc_html( get_option( 'offload_plus_db_version', 'N/A' ) ); ?></strong></td>
						</tr>
						<tr>
							<td><?php esc_html_e( 'Plugin Directory', 'offload-plus' ); ?></td>
							<td><code><?php echo esc_html( defined( 'OFFLOAD_PLUS_DIR' ) ? OFFLOAD_PLUS_DIR : 'N/A' ); ?></code></td>
						</tr>
					</table>
				</div>

				<?php if ( $is_configured && ! empty( $plugin_config['provider_config'] ) ) : ?>
				<div class="info-card">
					<h4><?php esc_html_e( 'Cloud Provider', 'offload-plus' ); ?></h4>
					<table class="info-table">
						<tr>
							<td><?php esc_html_e( 'Provider', 'offload-plus' ); ?></td>
							<td><strong>Azure Blob Storage</strong></td>
						</tr>
						<?php if ( ! empty( $plugin_config['provider_config']['storage_account'] ) ) : ?>
						<tr>
							<td><?php esc_html_e( 'Storage Account', 'offload-plus' ); ?></td>
							<td><strong><?php echo esc_html( $plugin_config['provider_config']['storage_account'] ); ?></strong></td>
						</tr>
						<?php endif; ?>
						<?php if ( ! empty( $plugin_config['provider_config']['container_name'] ) ) : ?>
						<tr>
							<td><?php esc_html_e( 'Container', 'offload-plus' ); ?></td>
							<td><strong><?php echo esc_html( $plugin_config['provider_config']['container_name'] ); ?></strong></td>
						</tr>
						<?php endif; ?>
						<?php if ( ! empty( $plugin_config['provider_config']['custom_domain'] ) ) : ?>
						<tr>
							<td><?php esc_html_e( 'Custom Domain', 'offload-plus' ); ?></td>
							<td><strong><?php echo esc_html( $plugin_config['provider_config']['custom_domain'] ); ?></strong></td>
						</tr>
						<?php endif; ?>
					</table>
				</div>
				<?php endif; ?>
			</div>
		</div>
	</div>
	<?php endif; // section === 'status' ?>

	<?php if ( $section === 'tools' ) : ?>
	<!-- =================================================================
		SECTION 2: CONFIGURATION TOOLS
		================================================================= -->
	<div class="tools-section">
		<!-- Header -->
		<div class="section-header-main">
			<h2><?php esc_html_e( 'Configuration Tools', 'offload-plus' ); ?></h2>
			<p class="description">
				<?php esc_html_e( 'Export and import plugin configuration for backup, migration, or disaster recovery purposes.', 'offload-plus' ); ?>
			</p>
		</div>

		<!-- Export/Import Grid -->
		<div class="tools-grid">
			<!-- Export Configuration -->
			<div class="tool-card">
				<div class="tool-header">
					<span class="dashicons dashicons-download"></span>
					<h3><?php esc_html_e( 'Export Configuration', 'offload-plus' ); ?></h3>
				</div>
				<p class="tool-description">
					<?php esc_html_e( 'Download all plugin settings as a JSON file. Use this to backup your configuration or migrate to another site.', 'offload-plus' ); ?>
				</p>
				<div class="tool-info">
					<p><strong><?php esc_html_e( 'Current configuration:', 'offload-plus' ); ?></strong></p>
					<ul>
						<li>
						<?php
							/* translators: %d: number of stored option rows */
							echo esc_html( sprintf( __( 'Total options: %d', 'offload-plus' ), count( $offload_plus_options ) ) );
						?>
						</li>
						<li><?php esc_html_e( 'Includes: Credentials, settings, state, and metadata', 'offload-plus' ); ?></li>
						<li><?php esc_html_e( 'Format: JSON (readable and portable)', 'offload-plus' ); ?></li>
					</ul>
				</div>
				<button type="button" id="export-config" class="button button-primary button-large">
					<span class="dashicons dashicons-download"></span>
					<?php esc_html_e( 'Export Configuration', 'offload-plus' ); ?>
				</button>
			</div>

			<!-- Import Configuration -->
			<div class="tool-card">
				<div class="tool-header">
					<span class="dashicons dashicons-upload"></span>
					<h3><?php esc_html_e( 'Import Configuration', 'offload-plus' ); ?></h3>
				</div>
				<p class="tool-description">
					<?php esc_html_e( 'Paste JSON configuration below to restore settings. This will overwrite current configuration.', 'offload-plus' ); ?>
				</p>
				<textarea id="import-config-data" class="import-textarea" placeholder='{"offload_plus_config": {...}, "offload_plus_plugin_state": "configured", ...}'></textarea>
				<div class="tool-actions">
					<button type="button" id="import-config" class="button button-primary button-large">
						<span class="dashicons dashicons-upload"></span>
						<?php esc_html_e( 'Import Configuration', 'offload-plus' ); ?>
					</button>
					<button type="button" id="clear-import" class="button button-secondary">
						<?php esc_html_e( 'Clear', 'offload-plus' ); ?>
					</button>
				</div>
				<div id="import-result" class="import-result" style="display: none;"></div>
			</div>
		</div>

		<!-- Warning Box -->
		<div class="tools-warning">
			<span class="dashicons dashicons-warning"></span>
			<div>
				<strong><?php esc_html_e( 'Important:', 'offload-plus' ); ?></strong>
				<p><?php esc_html_e( 'Importing configuration will completely overwrite all current settings including credentials, state, and metadata. Make sure to export your current configuration first as a backup before importing.', 'offload-plus' ); ?></p>
			</div>
		</div>
	</div>
	<?php endif; // section === 'tools' ?>
</div>

<?php if ( $section === 'tools' ) : ?>
<script>
jQuery(document).ready(function($) {
	// Prepare config data for export
	var configData = <?php echo wp_json_encode( $config_data, JSON_PRETTY_PRINT ); ?>;

	// Export configuration
	$('#export-config').on('click', function() {
		var $button = $(this);

		// Create JSON data
		var exportData = {
			exported_at: new Date().toISOString(),
			wordpress_version: '<?php echo esc_js( get_bloginfo( 'version' ) ); ?>',
			plugin_version: '<?php echo esc_js( Admin::get_plugin_version() ); ?>',
			site_url: '<?php echo esc_js( get_site_url() ); ?>',
			configuration: configData
		};

		// Convert to JSON string
		var jsonString = JSON.stringify(exportData, null, 2);

		// Create download
		var blob = new Blob([jsonString], { type: 'application/json' });
		var url = URL.createObjectURL(blob);
		var link = document.createElement('a');
		link.href = url;
		link.download = 'offload-plus-config-' + new Date().toISOString().slice(0, 10) + '.json';
		document.body.appendChild(link);
		link.click();
		document.body.removeChild(link);
		URL.revokeObjectURL(url);

		// Visual feedback
		var originalHTML = $button.html();
		$button.html('<span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Exported!', 'offload-plus' ); ?>')
			.addClass('button-success')
			.prop('disabled', true);

		setTimeout(function() {
			$button.html(originalHTML)
				.removeClass('button-success')
				.prop('disabled', false);
		}, 2000);
	});

	// Clear import textarea
	$('#clear-import').on('click', function() {
		$('#import-config-data').val('');
		$('#import-result').hide();
	});

	// Import configuration
	$('#import-config').on('click', function() {
		var $button = $(this);
		var $textarea = $('#import-config-data');
		var $result = $('#import-result');
		var jsonData = $textarea.val().trim();

		if (!jsonData) {
			$result.html('<span class="dashicons dashicons-warning"></span> <?php esc_html_e( 'Please paste configuration JSON first.', 'offload-plus' ); ?>')
				.removeClass('result-success').addClass('result-error').show();
			return;
		}

		// Validate JSON
		var importData;
		try {
			importData = JSON.parse(jsonData);
		} catch (e) {
			$result.html('<span class="dashicons dashicons-warning"></span> <?php esc_html_e( 'Invalid JSON format:', 'offload-plus' ); ?> ' + e.message)
				.removeClass('result-success').addClass('result-error').show();
			return;
		}

		// Extract configuration
		var config = importData.configuration || importData;

		// Confirm before importing
		if (!confirm('<?php esc_html_e( '⚠️ WARNING: This will overwrite your current configuration!\n\nAre you sure you want to continue?', 'offload-plus' ); ?>')) {
			return;
		}

		// Disable button
		var originalHTML = $button.html();
		$button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> <?php esc_html_e( 'Importing...', 'offload-plus' ); ?>');

		// Send to server
		$.ajax({
			url: offloadPlusAdmin.ajaxUrl,
			type: 'POST',
			data: {
				action: 'offload_plus_import_config',
				nonce: offloadPlusAdmin.nonce,
				config: JSON.stringify(config)
			},
			success: function(response) {
				if (response.success) {
					$result.html('<span class="dashicons dashicons-yes"></span> <?php esc_html_e( 'Configuration imported successfully! Reloading page...', 'offload-plus' ); ?>')
						.removeClass('result-error').addClass('result-success').show();

					// Reload page after 2 seconds
					setTimeout(function() {
						location.reload();
					}, 2000);
				} else {
					$result.html('<span class="dashicons dashicons-warning"></span> ' + (response.data || '<?php esc_html_e( 'Import failed.', 'offload-plus' ); ?>'))
						.removeClass('result-success').addClass('result-error').show();
					$button.prop('disabled', false).html(originalHTML);
				}
			},
			error: function() {
				$result.html('<span class="dashicons dashicons-warning"></span> <?php esc_html_e( 'Request failed. Please try again.', 'offload-plus' ); ?>')
					.removeClass('result-success').addClass('result-error').show();
				$button.prop('disabled', false).html(originalHTML);
			}
		});
	});
});
</script>
<?php endif; // section === 'tools' (script block) ?>

<style>
.offload-plus-status-tools {
	max-width: 1200px;
}

/* Section Headers */
.section-header-main {
	background: #fff;
	border: 1px solid #ddd;
	border-radius: 8px;
	padding: 24px;
	margin-bottom: 24px;
}

.section-header-main h2 {
	margin: 0 0 8px 0;
	font-size: 24px;
	color: #1d2327;
}

.section-header-main .description {
	margin: 0;
	color: #646970;
	font-size: 14px;
}

/* Section Spacing */
.status-section {
	margin-bottom: 50px;
}

.tools-section {
	margin-bottom: 30px;
}

/* State Cards Grid */
.state-cards {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
	gap: 20px;
	margin-bottom: 30px;
}

.state-card {
	background: #fff;
	border: 1px solid #ddd;
	border-radius: 8px;
	padding: 20px;
	display: flex;
	align-items: flex-start;
	gap: 16px;
}

.state-icon {
	flex-shrink: 0;
}

.state-icon .dashicons {
	font-size: 40px;
	width: 40px;
	height: 40px;
	color: #0073aa;
}

.state-content {
	flex: 1;
}

.state-content h3 {
	margin: 0 0 8px 0;
	font-size: 14px;
	font-weight: 600;
	color: #646970;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.state-value {
	margin: 0 0 4px 0;
	font-size: 16px;
	font-weight: 600;
	color: #1d2327;
}

.state-details {
	margin: 4px 0 0 0;
	font-size: 13px;
	color: #646970;
}

/* Status Indicator */
.status-indicator {
	display: inline-block;
	width: 10px;
	height: 10px;
	border-radius: 50%;
	margin-right: 6px;
}

.status-indicator.status-success {
	background: #28a745;
}

.status-indicator.status-inactive {
	background: #6c757d;
}

/* State Badges */
.state-badge {
	display: inline-block;
	padding: 4px 12px;
	border-radius: 4px;
	font-size: 12px;
	font-weight: 600;
	text-transform: uppercase;
	letter-spacing: 0.5px;
}

.state-badge.state-gray {
	background: #f0f0f0;
	color: #6c757d;
}

.state-badge.state-blue {
	background: #e7f3ff;
	color: #0073aa;
}

.state-badge.state-yellow {
	background: #fff8e1;
	color: #f57c00;
}

.state-badge.state-green {
	background: #e8f5e9;
	color: #2e7d32;
}

.state-badge.state-purple {
	background: #f3e5f5;
	color: #7b1fa2;
}

/* Greyed-out badge when the connection-health system reports a pause —
 * the underlying state is preserved but visually de-emphasised because
 * the feature is not actually working at the moment. */
.state-badge.is-paused {
	opacity: 0.5;
}

/* System Info Section */
.system-info-section {
	background: #fff;
	border: 1px solid #ddd;
	border-radius: 8px;
	padding: 24px;
}

.system-info-section > h3 {
	margin: 0 0 20px 0;
	font-size: 18px;
	font-weight: 600;
	color: #1d2327;
	display: flex;
	align-items: center;
	gap: 8px;
}

.system-info-section > h3 .dashicons {
	color: #0073aa;
}

.info-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
	gap: 20px;
}

.info-card {
	background: #f9f9f9;
	border: 1px solid #e0e0e0;
	border-radius: 6px;
	padding: 16px;
}

.info-card h4 {
	margin: 0 0 12px 0;
	font-size: 14px;
	font-weight: 600;
	color: #1d2327;
	text-transform: uppercase;
	letter-spacing: 0.5px;
	border-bottom: 2px solid #0073aa;
	padding-bottom: 8px;
}

.info-table {
	width: 100%;
	border-collapse: collapse;
}

.info-table tr {
	border-bottom: 1px solid #e0e0e0;
}

.info-table tr:last-child {
	border-bottom: none;
}

.info-table td {
	padding: 8px 0;
	font-size: 13px;
	line-height: 1.5;
}

.info-table td:first-child {
	color: #646970;
	width: 45%;
}

.info-table td:last-child {
	color: #1d2327;
	text-align: right;
	word-break: break-word;
}

.info-table code {
	background: #fff;
	padding: 2px 6px;
	border-radius: 3px;
	font-size: 12px;
	border: 1px solid #ddd;
}

/* Tools Grid */
.tools-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(450px, 1fr));
	gap: 30px;
	margin-bottom: 30px;
}

.tool-card {
	background: #fff;
	border: 1px solid #ddd;
	border-radius: 8px;
	padding: 30px;
}

.tool-header {
	display: flex;
	align-items: center;
	gap: 12px;
	margin-bottom: 16px;
	padding-bottom: 16px;
	border-bottom: 2px solid #0073aa;
}

.tool-header .dashicons {
	font-size: 32px;
	width: 32px;
	height: 32px;
	color: #0073aa;
}

.tool-header h3 {
	margin: 0;
	font-size: 20px;
	font-weight: 600;
	color: #1d2327;
}

.tool-description {
	margin: 0 0 20px 0;
	color: #646970;
	font-size: 14px;
	line-height: 1.6;
}

.tool-info {
	background: #f6f7f7;
	border-left: 4px solid #0073aa;
	padding: 16px;
	margin-bottom: 20px;
	border-radius: 4px;
}

.tool-info p {
	margin: 0 0 8px 0;
	color: #1d2327;
	font-weight: 600;
	font-size: 14px;
}

.tool-info ul {
	margin: 8px 0 0 0;
	padding-left: 20px;
}

.tool-info li {
	color: #646970;
	font-size: 13px;
	line-height: 1.8;
}

.import-textarea {
	width: 100%;
	min-height: 250px;
	font-family: 'Courier New', Courier, monospace;
	font-size: 13px;
	line-height: 1.5;
	background: #f6f7f7;
	border: 2px dashed #ddd;
	border-radius: 6px;
	padding: 16px;
	color: #1d2327;
	resize: vertical;
	margin-bottom: 16px;
	transition: all 0.2s ease;
}

.import-textarea:focus {
	outline: none;
	border-color: #0073aa;
	background: #fff;
	box-shadow: 0 0 0 1px #0073aa;
}

.import-textarea::placeholder {
	color: #9ca3af;
}

.button-large {
	padding: 10px 20px;
	font-size: 14px;
	height: auto;
	line-height: 1.5;
}

.button-large .dashicons {
	font-size: 18px;
	width: 18px;
	height: 18px;
	margin-right: 6px;
}

.button-success {
	background: #28a745 !important;
	border-color: #28a745 !important;
	color: #fff !important;
}

.tool-actions {
	display: flex;
	gap: 12px;
	align-items: center;
	flex-wrap: wrap;
}

.import-result {
	margin-top: 16px;
	padding: 12px 16px;
	border-radius: 6px;
	font-size: 14px;
	display: flex;
	align-items: center;
	gap: 10px;
	animation: slideDown 0.3s ease;
}

@keyframes slideDown {
	from {
		opacity: 0;
		transform: translateY(-10px);
	}
	to {
		opacity: 1;
		transform: translateY(0);
	}
}

.import-result.result-success {
	background: #d4edda;
	color: #155724;
	border: 1px solid #c3e6cb;
}

.import-result.result-error {
	background: #f8d7da;
	color: #721c24;
	border: 1px solid #f5c6cb;
}

.import-result .dashicons {
	flex-shrink: 0;
	font-size: 20px;
	width: 20px;
	height: 20px;
}

/* Warning Box */
.tools-warning {
	background: #fff3cd;
	border: 1px solid #ffc107;
	border-left: 5px solid #ffc107;
	border-radius: 6px;
	padding: 20px;
	margin-bottom: 30px;
	display: flex;
	align-items: flex-start;
	gap: 16px;
}

.tools-warning .dashicons {
	flex-shrink: 0;
	color: #f57c00;
	font-size: 24px;
	width: 24px;
	height: 24px;
}

.tools-warning strong {
	color: #856404;
	font-size: 15px;
	display: block;
	margin-bottom: 6px;
}

.tools-warning p {
	margin: 0;
	color: #856404;
	font-size: 14px;
	line-height: 1.6;
}

.dashicons.spin {
	animation: spin 1s linear infinite;
}

@keyframes spin {
	from { transform: rotate(0deg); }
	to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 782px) {
	.state-cards {
		grid-template-columns: 1fr;
	}

	.info-grid {
		grid-template-columns: 1fr;
	}

	.info-table td:last-child {
		text-align: left;
	}

	.tools-grid {
		grid-template-columns: 1fr;
	}

	.tool-actions {
		flex-direction: column;
		align-items: stretch;
	}

	.button-large {
		width: 100%;
		text-align: center;
	}
}
</style>

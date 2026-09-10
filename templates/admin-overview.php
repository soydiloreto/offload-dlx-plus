<?php
/**
 * Admin: Overview tab template.
 *
 * Local variables in this template (e.g. $config, $is_configured, $cloud_stats)
 * are populated by Admin::render_tab_content() in the calling scope and are
 * intentionally unprefixed because the include() puts them in the same local
 * scope as this template — they are not globals. Suppress the prefix sniff
 * for the whole template:
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 *
 * @package OffloadDlxPlus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OffloadDlxPlus\Admin;
use OffloadDlxPlus\ConfigManager;

use OffloadDlxPlus\Enums\PluginState;

// Get plugin state and config
$plugin_state = ConfigManager::get_state();
// Single source of truth — same definition used by the Status tab.
// `is_configured()` is FALSE when stored credentials cannot be decrypted
// (the field is cleared post-decrypt). The "looser" `!empty(cloud_provider)`
// check would diverge here and create the kind of cross-tab inconsistency
// we are trying to remove.
$is_configured = ConfigManager::is_configured();
$is_synced     = in_array( $plugin_state, array( PluginState::SYNCED, PluginState::OFFLOADING_ACTIVE ), true );
$is_offloading = $plugin_state === PluginState::OFFLOADING_ACTIVE;
$cloud_stats   = $cloud_stats ?? null;

// Health context (mirrors admin-status-tools.php) — when the cloud connection
// is unhealthy, every card below shows a "paused" sub-state so the user
// doesn't see contradictory greens like "Configured / Active" while the
// banner above reports unreadable credentials. We do NOT mutate the
// underlying state machine here — only the *display* changes.
$health      = ConfigManager::get_connection_health();
$is_paused   = $health['status'] === 'unhealthy';
$pause_cause = (string) ( $health['error_code'] ?? '' );
$pause_label = $is_paused ? Admin::pause_reason_short( $pause_cause ) : '';
?>

<div class="offload-dlx-plus-overview">
	<!-- Welcome Header -->
	<div class="welcome-header">
		<h2><?php esc_html_e( 'Welcome to Offload+', 'offload-dlx-plus' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Offload your WordPress media files to cloud storage and free up server space.', 'offload-dlx-plus' ); ?>
			<a href="<?php echo esc_url( 'https://diluxone.com/' ); ?>" target="_blank" rel="noopener noreferrer">
				<?php esc_html_e( 'More Info', 'offload-dlx-plus' ); ?>
			</a>
		</p>
	</div>

	<!-- Status Cards Grid -->
	<div class="status-grid">
		<!-- Configuration Status -->
		<?php
		// Visual mode: success (green) only when configured AND not paused.
		// When paused, downgrade to "warning" so the green check doesn't
		// contradict the red banner above the page.
		//
		// NOTE: vocabulary is intentionally identical to the Status tab —
		// "Awaiting Re-entry" for decrypt failures, "Paused (X)" for other
		// paused states. Keep these strings in sync with admin-status-tools.php.
		$config_card_mode   = ( ! $is_configured || $is_paused ) ? 'status-warning' : 'status-success';
		$config_card_icon   = ( ! $is_configured || $is_paused ) ? 'dashicons-warning' : 'dashicons-yes-alt';
		$is_decrypt_failure = $is_paused && $pause_cause === 'decrypt_failed';
		?>
		<div class="status-card <?php echo esc_attr( $config_card_mode ); ?>">
			<div class="status-icon">
				<span class="dashicons <?php echo esc_attr( $config_card_icon ); ?>"></span>
			</div>
			<div class="status-content">
				<h3><?php esc_html_e( 'Configuration', 'offload-dlx-plus' ); ?></h3>
				<?php if ( $is_configured && ! $is_paused ) : ?>
					<p class="status-label status-active"><?php esc_html_e( 'Configured', 'offload-dlx-plus' ); ?></p>
					<p class="status-details">
						<?php
						$provider_names   = array(
							'diluxone' => 'Dilux One Cloud',
							'azure'    => 'Azure Blob Storage',
						);
						$provider_display = $provider_names[ $config['cloud_provider'] ?? '' ] ?? ucfirst( $config['cloud_provider'] ?? '' );
						echo wp_kses(
							sprintf(
								/* translators: %s: cloud provider name */
								__( 'Provider: <strong>%s</strong>', 'offload-dlx-plus' ),
								esc_html( $provider_display )
							),
							array( 'strong' => array() )
						);
						?>
					</p>
					<?php if ( ! empty( $config['account_name'] ) ) : ?>
						<p class="status-details">
							<?php
							echo wp_kses(
								/* translators: %s: storage account name */
								sprintf( __( 'Account: <strong>%s</strong>', 'offload-dlx-plus' ), esc_html( $config['account_name'] ) ),
								array( 'strong' => array() )
							);
							?>
						</p>
					<?php endif; ?>
				<?php elseif ( $is_decrypt_failure ) : ?>
					<p class="status-label" style="color:#dba617;"><?php esc_html_e( 'Awaiting Re-entry', 'offload-dlx-plus' ); ?></p>
					<p class="status-details" style="color:#856404;">
						<?php esc_html_e( 'Stored credentials cannot be decrypted. See banner above.', 'offload-dlx-plus' ); ?>
					</p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=offload-dlx-plus&tab=cloud-provider' ) ); ?>" class="button button-primary button-small">
						<?php esc_html_e( 'Re-enter Credentials', 'offload-dlx-plus' ); ?>
					</a>
				<?php elseif ( $is_configured && $is_paused ) : ?>
					<p class="status-label" style="color:#dba617;">
						<?php
						printf(
							/* translators: %s: short reason for the pause */
							esc_html__( 'Paused (%s)', 'offload-dlx-plus' ),
							esc_html( $pause_label )
						);
						?>
					</p>
					<p class="status-details" style="color:#856404;">
						<?php esc_html_e( 'See banner above for details.', 'offload-dlx-plus' ); ?>
					</p>
				<?php else : ?>
					<p class="status-label status-inactive"><?php esc_html_e( 'Not Configured', 'offload-dlx-plus' ); ?></p>
					<p class="status-details">
						<?php esc_html_e( 'Connect a cloud provider to get started', 'offload-dlx-plus' ); ?>
					</p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=offload-dlx-plus&tab=cloud-provider' ) ); ?>" class="button button-primary button-small">
						<?php esc_html_e( 'Configure Now', 'offload-dlx-plus' ); ?>
					</a>
				<?php endif; ?>
			</div>
		</div>

		<!-- Sync Status -->
		<?php
		$sync_card_mode = ( $is_synced && ! $is_paused ) ? 'status-success' : ( $is_synced && $is_paused ? 'status-warning' : 'status-neutral' );
		?>
		<div class="status-card <?php echo esc_attr( $sync_card_mode ); ?>">
			<div class="status-icon">
				<span class="dashicons <?php echo $is_synced ? 'dashicons-cloud-saved' : 'dashicons-cloud-upload'; ?>"></span>
			</div>
			<div class="status-content">
				<h3><?php esc_html_e( 'Synchronization', 'offload-dlx-plus' ); ?></h3>
				<?php if ( $is_synced && ! $is_paused ) : ?>
					<p class="status-label status-active"><?php esc_html_e( 'Synced', 'offload-dlx-plus' ); ?></p>
					<p class="status-details">
						<?php esc_html_e( 'Your files are in the cloud', 'offload-dlx-plus' ); ?>
					</p>
				<?php elseif ( $is_synced && $is_paused ) : ?>
					<p class="status-label" style="color:#dba617;">
						<?php
						printf(
							/* translators: %s: short reason for the pause */
							esc_html__( 'Paused (%s)', 'offload-dlx-plus' ),
							esc_html( $pause_label )
						);
						?>
					</p>
					<p class="status-details">
						<?php esc_html_e( 'Files were synced previously, but the plugin cannot reach the cloud right now.', 'offload-dlx-plus' ); ?>
					</p>
				<?php else : ?>
					<p class="status-label status-inactive"><?php esc_html_e( 'Not Synced', 'offload-dlx-plus' ); ?></p>
					<p class="status-details">
						<?php
						if ( $is_configured ) {
							esc_html_e( 'Ready to sync your files', 'offload-dlx-plus' );
						} else {
							esc_html_e( 'Configure cloud storage first', 'offload-dlx-plus' );
						}
						?>
					</p>
					<?php if ( $is_configured && ! $is_paused ) : ?>
						<a href="<?php echo esc_url( admin_url( 'admin.php?page=offload-dlx-plus&tab=sync' ) ); ?>" class="button button-primary button-small">
							<?php esc_html_e( 'Start Sync', 'offload-dlx-plus' ); ?>
						</a>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</div>

		<!-- Offloading Status -->
		<?php
		$off_card_mode = ( $is_offloading && ! $is_paused ) ? 'status-success' : ( $is_offloading && $is_paused ? 'status-warning' : 'status-neutral' );
		?>
		<div class="status-card <?php echo esc_attr( $off_card_mode ); ?>">
			<div class="status-icon">
				<span class="dashicons <?php echo $is_offloading ? 'dashicons-superhero' : 'dashicons-database'; ?>"></span>
			</div>
			<div class="status-content">
				<h3><?php esc_html_e( 'Offloading', 'offload-dlx-plus' ); ?></h3>
				<?php if ( $is_offloading && ! $is_paused ) : ?>
					<p class="status-label status-active"><?php esc_html_e( 'Active', 'offload-dlx-plus' ); ?></p>
					<p class="status-details">
						<?php esc_html_e( 'Files served from cloud storage', 'offload-dlx-plus' ); ?>
					</p>
				<?php elseif ( $is_offloading && $is_paused ) : ?>
					<p class="status-label" style="color:#dba617;">
						<?php
						printf(
							/* translators: %s: short reason for the pause */
							esc_html__( 'Paused (%s)', 'offload-dlx-plus' ),
							esc_html( $pause_label )
						);
						?>
					</p>
					<p class="status-details">
						<?php esc_html_e( 'Falling back to local storage for new uploads.', 'offload-dlx-plus' ); ?>
					</p>
				<?php else : ?>
					<p class="status-label status-inactive"><?php esc_html_e( 'Inactive', 'offload-dlx-plus' ); ?></p>
					<p class="status-details">
						<?php
						if ( $is_synced ) {
							esc_html_e( 'Files still served locally', 'offload-dlx-plus' );
						} else {
							esc_html_e( 'Sync files first to enable', 'offload-dlx-plus' );
						}
						?>
					</p>
				<?php endif; ?>
			</div>
		</div>

		<!-- Plugin State -->
		<div class="status-card status-info">
			<div class="status-icon">
				<span class="dashicons dashicons-info"></span>
			</div>
			<div class="status-content">
				<h3><?php esc_html_e( 'Plugin State', 'offload-dlx-plus' ); ?></h3>
				<p class="status-label">
					<?php
					$badge_class = 'state-gray';
					$badge_label = $plugin_state;
					switch ( $plugin_state ) {
						case PluginState::NOT_CONFIGURED:
							$badge_class = 'state-gray';
							$badge_label = __( 'Not Configured', 'offload-dlx-plus' );
							break;
						case PluginState::CONFIGURED:
							$badge_class = 'state-blue';
							$badge_label = __( 'Configured', 'offload-dlx-plus' );
							break;
						case PluginState::SYNCING:
							$badge_class = 'state-yellow';
							$badge_label = __( 'Syncing', 'offload-dlx-plus' );
							break;
						case PluginState::SYNCED:
							$badge_class = 'state-green';
							$badge_label = __( 'Synced', 'offload-dlx-plus' );
							break;
						case PluginState::OFFLOADING_ACTIVE:
							$badge_class = 'state-purple';
							$badge_label = __( 'Offloading Active', 'offload-dlx-plus' );
							break;
					}
					if ( $is_paused ) {
						$badge_class .= ' is-paused';
					}
					echo '<span class="state-badge ' . esc_attr( $badge_class ) . '">' . esc_html( $badge_label ) . '</span>';
					?>
				</p>
				<?php if ( $is_paused ) : ?>
					<p class="status-details" style="color:#856404;">
						<?php
						printf(
							/* translators: %s: short reason for the pause */
							esc_html__( 'Paused (%s) — see banner above.', 'offload-dlx-plus' ),
							esc_html( $pause_label )
						);
						?>
					</p>
				<?php else : ?>
					<p class="status-details">
						<?php esc_html_e( 'Current operational mode', 'offload-dlx-plus' ); ?>
					</p>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<!-- Storage Overview (only if configured) -->
	<?php if ( $is_configured && $cloud_stats ) : ?>
		<div class="storage-overview-section">
			<h3 style="display: flex; align-items: center; justify-content: space-between;">
				<?php esc_html_e( 'Storage Overview', 'offload-dlx-plus' ); ?>
				<button type="button" id="refresh-stats-btn" class="button button-small">
					<span class="dashicons dashicons-update" style="font-size: 14px; width: 14px; height: 14px; vertical-align: middle;"></span>
					<?php esc_html_e( 'Refresh', 'offload-dlx-plus' ); ?>
				</button>
			</h3>

			<div id="stats-loading" style="display: none; text-align: center; padding: 20px;">
				<span class="spinner is-active" style="float: none;"></span>
				<p class="description"><?php esc_html_e( 'Loading storage statistics...', 'offload-dlx-plus' ); ?></p>
			</div>

			<div id="stats-content">
				<?php if ( ! $cloud_stats['success'] ) : ?>
					<!-- Storage bar with ERROR -->
					<div class="offload-dlx-plus-overview-bars">
						<div class="offload-dlx-plus-bar-section">
							<div class="offload-dlx-plus-bar-header">
								<span class="offload-dlx-plus-bar-title"><?php esc_html_e( 'Storage', 'offload-dlx-plus' ); ?></span>
								<span class="offload-dlx-plus-bar-value" id="stat-storage-detail" style="color: #d63638; font-weight: 600;">ERROR</span>
							</div>
						</div>
					</div>
					<!-- Files section with ERROR -->
					<div class="offload-dlx-plus-files-section">
						<div class="offload-dlx-plus-files-grid">
							<div class="offload-dlx-plus-files-count">
								<span class="offload-dlx-plus-stat-label"><?php esc_html_e( 'Total Files', 'offload-dlx-plus' ); ?></span>
								<div id="stat-file-count" class="offload-dlx-plus-stat-value" style="color: #d63638; font-size: 16px;">
									<?php esc_html_e( 'ERROR: please update your credentials', 'offload-dlx-plus' ); ?>
								</div>
							</div>
						</div>
					</div>
					<p class="description" style="color: #d63638; margin-top: 10px;">
						<?php echo esc_html( $cloud_stats['message'] ?? 'Unknown error' ); ?>
					</p>
					<?php
				else :
					$cs_data           = $cloud_stats['data'];
					$used_bytes        = $cs_data['storageUsedBytes'] ?? 0;
					$has_storage_limit = ! empty( $cs_data['storageLimitBytes'] );
					$storage_limit     = $has_storage_limit ? $cs_data['storageLimitBytes'] : 0;
					$storage_pct       = $has_storage_limit && $storage_limit > 0 ? round( ( $used_bytes / $storage_limit ) * 100, 1 ) : 0;
					$quota_exceeded    = $cs_data['quotaExceeded'] ?? false;

					$bw_used      = $cs_data['bandwidthUsedBytes'] ?? null;
					$bw_limit     = $cs_data['bandwidthLimitBytes'] ?? null;
					$has_bw_limit = ! empty( $bw_limit );
					$bw_pct       = $has_bw_limit && $bw_limit > 0 ? round( ( $bw_used / $bw_limit ) * 100, 1 ) : 0;

					$files_by_type = $cs_data['filesByType'] ?? null;

					// Color helper for bar charts
					$offload_dlx_plus_bar_color = function ( float $pct, bool $exceeded = false ): array {
						if ( $exceeded ) {
							return array(
								'color' => '#d63638',
								'bg'    => '#f8d7da',
							);
						}
						if ( $pct >= 80 ) {
							return array(
								'color' => '#dba617',
								'bg'    => '#fff3cd',
							);
						}
						return array(
							'color' => '#00a32a',
							'bg'    => '#d1e7dd',
						);
					};
					$storage_colors             = $offload_dlx_plus_bar_color( $storage_pct, $quota_exceeded );
					$bw_colors                  = $offload_dlx_plus_bar_color( $bw_pct );
					?>

					<?php if ( $quota_exceeded ) : ?>
					<div id="quota-exceeded-warning" style="background: #f8d7da; border-left: 4px solid #d63638; padding: 12px; margin-bottom: 15px; border-radius: 4px;">
						<strong style="color: #721c24;"><?php esc_html_e( 'Storage quota exceeded. Uploads are disabled until you free up space or upgrade your plan.', 'offload-dlx-plus' ); ?></strong>
					</div>
					<?php endif; ?>

					<!-- Plan name -->
					<?php if ( ( $cs_data['plan'] ?? null ) !== null ) : ?>
					<div id="stat-plan-section" style="text-align: center; margin-bottom: 20px;">
						<span class="offload-dlx-plus-stat-label"><?php esc_html_e( 'Current Plan', 'offload-dlx-plus' ); ?></span>
						<div id="stat-plan" style="font-size: 28px; font-weight: 700; color: #2271b1; margin-top: 4px;"><?php echo esc_html( $cs_data['plan'] ); ?></div>
					</div>
					<?php endif; ?>

					<!-- Progress bars -->
					<div class="offload-dlx-plus-overview-bars">
						<!-- Storage bar -->
						<div class="offload-dlx-plus-bar-section">
							<div class="offload-dlx-plus-bar-header">
								<span class="offload-dlx-plus-bar-title"><?php esc_html_e( 'Storage', 'offload-dlx-plus' ); ?></span>
								<span class="offload-dlx-plus-bar-value" id="stat-storage-detail">
									<?php if ( $has_storage_limit ) : ?>
										<?php echo esc_html( sprintf( '%s / %s (%s%%)', (string) size_format( $used_bytes ), (string) size_format( $storage_limit ), $storage_pct ) ); ?>
									<?php else : ?>
										<?php echo esc_html( (string) size_format( $used_bytes ) ); ?>
									<?php endif; ?>
								</span>
							</div>
							<?php if ( $has_storage_limit ) : ?>
							<div class="offload-dlx-plus-stat-bar-container" style="background: <?php echo esc_attr( $storage_colors['bg'] ); ?>;">
								<div id="stat-storage-bar" class="offload-dlx-plus-stat-bar" style="width: <?php echo esc_attr( (string) min( $storage_pct, 100 ) ); ?>%; background: <?php echo esc_attr( $storage_colors['color'] ); ?>;"></div>
							</div>
							<?php endif; ?>
						</div>

						<!-- Bandwidth bar (DiluxOne only) -->
						<?php if ( $bw_used !== null ) : ?>
						<div class="offload-dlx-plus-bar-section" id="stat-bandwidth-section">
							<div class="offload-dlx-plus-bar-header">
								<span class="offload-dlx-plus-bar-title"><?php esc_html_e( 'Bandwidth (30 days)', 'offload-dlx-plus' ); ?></span>
								<span class="offload-dlx-plus-bar-value" id="stat-bandwidth-detail">
									<?php if ( $has_bw_limit ) : ?>
										<?php echo esc_html( sprintf( '%s / %s (%s%%)', (string) size_format( $bw_used ), (string) size_format( $bw_limit ), $bw_pct ) ); ?>
									<?php else : ?>
										<?php echo $bw_used > 0 ? esc_html( (string) size_format( $bw_used ) ) : esc_html__( 'Not available', 'offload-dlx-plus' ); ?>
									<?php endif; ?>
								</span>
							</div>
							<?php if ( $has_bw_limit ) : ?>
							<div class="offload-dlx-plus-stat-bar-container" style="background: <?php echo esc_attr( $bw_colors['bg'] ); ?>;">
								<div id="stat-bandwidth-bar" class="offload-dlx-plus-stat-bar" style="width: <?php echo esc_attr( (string) min( $bw_pct, 100 ) ); ?>%; background: <?php echo esc_attr( $bw_colors['color'] ); ?>;"></div>
							</div>
							<?php endif; ?>
						</div>
						<?php endif; ?>
					</div>

					<!-- Files section with pie chart -->
					<div class="offload-dlx-plus-files-section">
						<div class="offload-dlx-plus-files-grid">
							<!-- File count -->
							<div class="offload-dlx-plus-files-count">
								<span class="offload-dlx-plus-stat-label"><?php esc_html_e( 'Total Files', 'offload-dlx-plus' ); ?></span>
								<div id="stat-file-count" class="offload-dlx-plus-stat-value"><?php echo esc_html( number_format_i18n( $cs_data['fileCount'] ?? 0 ) ); ?></div>
							</div>

							<!-- Pie chart (only if filesByType exists from API) -->
							<?php
							if ( $files_by_type !== null ) :
								$total_typed = (int) ( $files_by_type['images'] ?? 0 ) + (int) ( $files_by_type['videos'] ?? 0 ) + (int) ( $files_by_type['audio'] ?? 0 ) + (int) ( $files_by_type['other'] ?? 0 );
								if ( $total_typed > 0 ) :
									$pct_images = round( ( $files_by_type['images'] ?? 0 ) / $total_typed * 100, 1 );
									$pct_videos = round( ( $files_by_type['videos'] ?? 0 ) / $total_typed * 100, 1 );
									$pct_audio  = round( ( $files_by_type['audio'] ?? 0 ) / $total_typed * 100, 1 );
									$pct_other  = round( 100 - $pct_images - $pct_videos - $pct_audio, 1 );
									$s1         = $pct_images;
									$s2         = $s1 + $pct_videos;
									$s3         = $s2 + $pct_audio;
									?>
							<div class="offload-dlx-plus-pie-container" id="stat-pie-section">
								<div class="offload-dlx-plus-pie" style="background: conic-gradient(#2271b1 0% <?php echo esc_attr( (string) $s1 ); ?>%, #d63638 <?php echo esc_attr( (string) $s1 ); ?>% <?php echo esc_attr( (string) $s2 ); ?>%, #dba617 <?php echo esc_attr( (string) $s2 ); ?>% <?php echo esc_attr( (string) $s3 ); ?>%, #8c8f94 <?php echo esc_attr( (string) $s3 ); ?>% 100%);"></div>
								<div class="offload-dlx-plus-pie-legend">
									<div class="offload-dlx-plus-legend-item"><span class="offload-dlx-plus-legend-dot" style="background: #2271b1;"></span>
									<?php
										/* translators: 1: number of files, 2: percentage */
										echo esc_html( sprintf( __( 'Images %1$s (%2$s%%)', 'offload-dlx-plus' ), number_format_i18n( $files_by_type['images'] ?? 0 ), $pct_images ) );
									?>
									</div>
									<div class="offload-dlx-plus-legend-item"><span class="offload-dlx-plus-legend-dot" style="background: #d63638;"></span>
									<?php
										/* translators: 1: number of files, 2: percentage */
										echo esc_html( sprintf( __( 'Videos %1$s (%2$s%%)', 'offload-dlx-plus' ), number_format_i18n( $files_by_type['videos'] ?? 0 ), $pct_videos ) );
									?>
									</div>
									<div class="offload-dlx-plus-legend-item"><span class="offload-dlx-plus-legend-dot" style="background: #dba617;"></span>
									<?php
										/* translators: 1: number of files, 2: percentage */
										echo esc_html( sprintf( __( 'Audio %1$s (%2$s%%)', 'offload-dlx-plus' ), number_format_i18n( $files_by_type['audio'] ?? 0 ), $pct_audio ) );
									?>
									</div>
									<div class="offload-dlx-plus-legend-item"><span class="offload-dlx-plus-legend-dot" style="background: #8c8f94;"></span>
									<?php
										/* translators: 1: number of files, 2: percentage */
										echo esc_html( sprintf( __( 'Other %1$s (%2$s%%)', 'offload-dlx-plus' ), number_format_i18n( $files_by_type['other'] ?? 0 ), $pct_other ) );
									?>
									</div>
								</div>
							</div>
									<?php
							endif;
endif;
							?>
						</div>
					</div>

					<!-- Last updated -->
					<?php
					$checked_at = $cs_data['storageCheckedAt'] ?? null;
					if ( $checked_at ) :
						$timestamp = strtotime( $checked_at );
						$diff      = time() - $timestamp;
						if ( $diff < 60 ) {
							$ago = __( 'just now', 'offload-dlx-plus' );
						} elseif ( $diff < 3600 ) {
							$minutes = (int) ( $diff / 60 );
							/* translators: %d: number of minutes */
							$ago = sprintf( _n( '%d minute ago', '%d minutes ago', $minutes, 'offload-dlx-plus' ), $minutes );
						} elseif ( $diff < 86400 ) {
							$hours = (int) ( $diff / 3600 );
							/* translators: %d: number of hours */
							$ago = sprintf( _n( '%d hour ago', '%d hours ago', $hours, 'offload-dlx-plus' ), $hours );
						} else {
							$ago = date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp );
						}
						?>
					<p id="stat-last-updated" class="description" style="margin-top: 10px; text-align: right; font-size: 12px;">
						<?php
						/* translators: %s: relative time, e.g. "3 minutes ago" */
						echo esc_html( sprintf( __( 'Last updated: %s', 'offload-dlx-plus' ), $ago ) );
						?>
					</p>
					<?php endif; ?>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>

	<!-- Quick Actions -->
	<?php if ( $is_configured ) : ?>
		<div class="quick-links-section">
			<h3><?php esc_html_e( 'Quick Actions', 'offload-dlx-plus' ); ?></h3>
			<div class="quick-links">
				<a href="<?php echo esc_url( 'https://diluxone.com/support' ); ?>" target="_blank" rel="noopener noreferrer" class="quick-link">
					<span class="dashicons dashicons-sos"></span>
					<?php esc_html_e( 'Get Help', 'offload-dlx-plus' ); ?>
				</a>
				<a href="<?php echo esc_url( 'https://diluxone.com/' ); ?>" target="_blank" rel="noopener noreferrer" class="quick-link">
					<span class="dashicons dashicons-info"></span>
					<?php esc_html_e( 'More Info', 'offload-dlx-plus' ); ?>
				</a>
			</div>
		</div>
	<?php endif; ?>

	<!-- Getting Started (if not configured) -->
	<?php if ( ! $is_configured ) : ?>
		<div class="getting-started-section">
			<h3><?php esc_html_e( 'Getting Started', 'offload-dlx-plus' ); ?></h3>
			<ol class="setup-steps">
				<li>
					<strong><?php esc_html_e( 'Configure Cloud Provider', 'offload-dlx-plus' ); ?></strong>
					<p><?php esc_html_e( 'Choose your cloud provider and enter your credentials', 'offload-dlx-plus' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=offload-dlx-plus&tab=cloud-provider' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Go to Cloud Provider', 'offload-dlx-plus' ); ?>
					</a>
				</li>
				<li>
					<strong><?php esc_html_e( 'Sync Your Files', 'offload-dlx-plus' ); ?></strong>
					<p><?php esc_html_e( 'Upload your existing media files to the cloud', 'offload-dlx-plus' ); ?></p>
				</li>
				<li>
					<strong><?php esc_html_e( 'Enable Offloading', 'offload-dlx-plus' ); ?></strong>
					<p><?php esc_html_e( 'Serve files directly from the cloud', 'offload-dlx-plus' ); ?></p>
				</li>
			</ol>
		</div>
	<?php endif; ?>
</div>

<style>
.offload-dlx-plus-overview {
	max-width: 1200px;
}

/* Welcome Header */
.welcome-header {
	background: #fff;
	border: 1px solid #ddd;
	border-radius: 8px;
	padding: 24px;
	margin-bottom: 24px;
}

.welcome-header h2 {
	margin: 0 0 8px 0;
	font-size: 24px;
	color: #1d2327;
}

.welcome-header .description {
	margin: 0;
	color: #646970;
	font-size: 14px;
}

/* Status Grid */
.status-grid {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
	gap: 20px;
	margin-bottom: 30px;
}

.status-card {
	background: #fff;
	border: 1px solid #ddd;
	border-radius: 8px;
	padding: 20px;
	display: flex;
	align-items: flex-start;
	gap: 16px;
}

.status-card.status-success {
	border-left: 4px solid #28a745;
}

.status-card.status-warning {
	border-left: 4px solid #ffc107;
}

.status-card.status-neutral {
	border-left: 4px solid #6c757d;
}

.status-card.status-info {
	border-left: 4px solid #17a2b8;
}

.status-icon {
	flex-shrink: 0;
}

.status-icon .dashicons {
	font-size: 40px;
	width: 40px;
	height: 40px;
}

.status-success .status-icon .dashicons {
	color: #28a745;
}

.status-warning .status-icon .dashicons {
	color: #ffc107;
}

.status-neutral .status-icon .dashicons {
	color: #6c757d;
}

.status-info .status-icon .dashicons {
	color: #17a2b8;
}

.status-content {
	flex: 1;
}

.status-content h3 {
	margin: 0 0 8px 0;
	font-size: 16px;
	font-weight: 600;
	color: #1d2327;
}

.status-label {
	font-size: 14px;
	font-weight: 600;
	margin: 0 0 6px 0;
}

.status-label.status-active {
	color: #28a745;
}

.status-label.status-inactive {
	color: #6c757d;
}

.status-details {
	font-size: 13px;
	color: #646970;
	margin: 4px 0;
	line-height: 1.5;
}

.status-details strong {
	color: #1d2327;
}

.button-small {
	padding: 4px 12px;
	font-size: 12px;
	height: auto;
	line-height: 1.5;
	margin-top: 8px;
}

/* State Badge */
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

/* Quick Links Section */
.quick-links-section {
	background: #fff;
	border: 1px solid #ddd;
	border-radius: 8px;
	padding: 20px;
	margin-bottom: 24px;
}

.quick-links-section h3 {
	margin: 0 0 16px 0;
	font-size: 16px;
	font-weight: 600;
}

.quick-links {
	display: flex;
	gap: 12px;
	flex-wrap: wrap;
}

.quick-link {
	display: inline-flex;
	align-items: center;
	gap: 8px;
	padding: 10px 16px;
	background: #f6f7f7;
	border: 1px solid #ddd;
	border-radius: 6px;
	text-decoration: none;
	color: #1d2327;
	font-size: 14px;
	font-weight: 500;
	transition: all 0.2s ease;
}

.quick-link:hover {
	background: #fff;
	border-color: #0073aa;
	color: #0073aa;
	text-decoration: none;
	transform: translateY(-1px);
}

.quick-link .dashicons {
	font-size: 18px;
	width: 18px;
	height: 18px;
}

/* Getting Started Section */
.getting-started-section {
	background: #fff;
	border: 1px solid #ddd;
	border-radius: 8px;
	padding: 24px;
}

.getting-started-section h3 {
	margin: 0 0 20px 0;
	font-size: 18px;
	font-weight: 600;
}

.setup-steps {
	margin: 0;
	padding: 0;
	list-style: none;
	counter-reset: step-counter;
}

.setup-steps li {
	position: relative;
	padding: 20px 0 20px 60px;
	border-left: 2px solid #e0e0e0;
	margin-left: 20px;
}

.setup-steps li:last-child {
	border-left-color: transparent;
}

.setup-steps li::before {
	content: counter(step-counter);
	counter-increment: step-counter;
	position: absolute;
	left: -21px;
	top: 20px;
	width: 40px;
	height: 40px;
	background: #0073aa;
	color: #fff;
	border-radius: 50%;
	display: flex;
	align-items: center;
	justify-content: center;
	font-weight: bold;
	font-size: 18px;
}

.setup-steps li strong {
	display: block;
	margin-bottom: 8px;
	font-size: 15px;
	color: #1d2327;
}

.setup-steps li p {
	margin: 0 0 12px 0;
	color: #646970;
	font-size: 14px;
	line-height: 1.6;
}

/* Responsive */
@media (max-width: 782px) {
	.status-grid {
		grid-template-columns: 1fr;
	}

	.quick-links {
		flex-direction: column;
	}

	.quick-link {
		justify-content: center;
	}
}

/* Storage Overview Section */
.storage-overview-section {
	background: #fff;
	border: 1px solid #ddd;
	border-radius: 8px;
	padding: 20px;
	margin-bottom: 24px;
}

.storage-overview-section h3 {
	margin: 0 0 16px 0;
	font-size: 16px;
	font-weight: 600;
	padding-bottom: 10px;
	border-bottom: 1px solid #eee;
}

/* Progress bars layout */
.offload-dlx-plus-overview-bars {
	display: flex;
	flex-direction: column;
	gap: 16px;
	margin-bottom: 20px;
}

.offload-dlx-plus-bar-section {
	background: #f9f9f9;
	border: 1px solid #e2e4e7;
	border-radius: 6px;
	padding: 14px;
}

.offload-dlx-plus-bar-header {
	display: flex;
	justify-content: space-between;
	align-items: center;
	margin-bottom: 8px;
}

.offload-dlx-plus-bar-title {
	font-size: 13px;
	font-weight: 600;
	color: #1d2327;
}

.offload-dlx-plus-bar-value {
	font-size: 13px;
	color: #50575e;
}

/* Files section with pie */
.offload-dlx-plus-files-section {
	margin-top: 20px;
	padding-top: 16px;
	border-top: 1px solid #eee;
}

.offload-dlx-plus-files-grid {
	display: flex;
	align-items: center;
	gap: 30px;
}

.offload-dlx-plus-files-count {
	text-align: center;
	min-width: 120px;
}

/* Pie chart CSS */
.offload-dlx-plus-pie-container {
	display: flex;
	align-items: center;
	gap: 24px;
	flex: 1;
}

.offload-dlx-plus-pie {
	width: 140px;
	height: 140px;
	border-radius: 50%;
	flex-shrink: 0;
}

.offload-dlx-plus-pie-legend {
	display: flex;
	flex-direction: column;
	gap: 6px;
}

.offload-dlx-plus-legend-item {
	display: flex;
	align-items: center;
	gap: 6px;
	font-size: 13px;
	color: #50575e;
}

.offload-dlx-plus-legend-dot {
	width: 12px;
	height: 12px;
	border-radius: 50%;
	flex-shrink: 0;
}

/* Spin animation */
.dashicons.spin {
	animation: offload-dlx-plus-spin 1s linear infinite;
}

@keyframes offload-dlx-plus-spin {
	100% { transform: rotate(360deg); }
}

/* Responsive for storage overview */
@media (max-width: 782px) {
	.offload-dlx-plus-files-grid {
		flex-direction: column;
		text-align: center;
	}

	.offload-dlx-plus-pie-container {
		flex-direction: column;
	}
}
</style>

<script>
jQuery(document).ready(function($) {
	function formatBytes(bytes) {
		if (!bytes || bytes === 0) return '0 B';
		var k = 1024;
		var sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
		var i = Math.floor(Math.log(bytes) / Math.log(k));
		return parseFloat((bytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
	}

	$('#refresh-stats-btn').on('click', function() {
		var $button = $(this);
		var $loading = $('#stats-loading');
		var $content = $('#stats-content');

		$button.prop('disabled', true);
		$button.find('.dashicons').addClass('spin');
		$loading.show();
		$content.hide();

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'offload_dlx_plus_refresh_stats',
				nonce: '<?php echo esc_js( wp_create_nonce( 'offload_dlx_plus_admin' ) ); ?>'
			},
			timeout: 30000,
			success: function(response) {
				if (response.success) {
					var d = response.data;
					$('#stat-file-count').text(parseInt(d.fileCount || 0).toLocaleString());

					// Hide pie chart if no files
					if (parseInt(d.fileCount || 0) === 0) {
						$('#stat-pie-section').hide();
					}

					if (d.storageLimitBytes) {
						var pct = Math.min((d.storageUsedBytes / d.storageLimitBytes) * 100, 100).toFixed(1);
						$('#stat-storage-bar').css('width', pct + '%');
						$('#stat-storage-detail').text(formatBytes(d.storageUsedBytes) + ' / ' + formatBytes(d.storageLimitBytes) + ' (' + pct + '%)');
					} else {
						$('#stat-storage-detail').text(formatBytes(d.storageUsedBytes));
					}

					if (d.bandwidthLimitBytes) {
						var bwPct = Math.min((d.bandwidthUsedBytes / d.bandwidthLimitBytes) * 100, 100).toFixed(1);
						$('#stat-bandwidth-bar').css('width', bwPct + '%');
						$('#stat-bandwidth-detail').text(formatBytes(d.bandwidthUsedBytes) + ' / ' + formatBytes(d.bandwidthLimitBytes) + ' (' + bwPct + '%)');
					} else if (d.bandwidthUsedBytes !== null) {
						$('#stat-bandwidth-detail').text(d.bandwidthUsedBytes > 0 ? formatBytes(d.bandwidthUsedBytes) : 'Not available');
					}

					if (d.plan !== null && d.plan !== undefined) {
						$('#stat-plan').text(d.plan);
					}

					if (d.quotaExceeded) {
						$('#quota-exceeded-warning').show();
					} else {
						$('#quota-exceeded-warning').hide();
					}

					// Update pie chart
					if (d.filesByType) {
						var ft = d.filesByType;
						var total = (ft.images || 0) + (ft.videos || 0) + (ft.audio || 0) + (ft.other || 0);
						if (total > 0) {
							var pImages = ((ft.images || 0) / total * 100).toFixed(1);
							var pVideos = ((ft.videos || 0) / total * 100).toFixed(1);
							var pAudio = ((ft.audio || 0) / total * 100).toFixed(1);
							var pOther = (100 - pImages - pVideos - pAudio).toFixed(1);
							var s1 = parseFloat(pImages);
							var s2 = s1 + parseFloat(pVideos);
							var s3 = s2 + parseFloat(pAudio);
							var $pie = $('#stat-pie-section');
							if ($pie.length) {
								$pie.find('.offload-dlx-plus-pie').css('background', 'conic-gradient(#2271b1 0% ' + s1 + '%, #d63638 ' + s1 + '% ' + s2 + '%, #dba617 ' + s2 + '% ' + s3 + '%, #8c8f94 ' + s3 + '% 100%)');
								var $legends = $pie.find('.offload-dlx-plus-legend-item');
								var labels = [
									'<?php echo esc_js( __( 'Images', 'offload-dlx-plus' ) ); ?>',
									'<?php echo esc_js( __( 'Videos', 'offload-dlx-plus' ) ); ?>',
									'<?php echo esc_js( __( 'Audio', 'offload-dlx-plus' ) ); ?>',
									'<?php echo esc_js( __( 'Other', 'offload-dlx-plus' ) ); ?>'
								];
								var counts = [ft.images || 0, ft.videos || 0, ft.audio || 0, ft.other || 0];
								var pcts = [pImages, pVideos, pAudio, pOther];
								$legends.each(function(i) {
									var $dot = $(this).find('.offload-dlx-plus-legend-dot').clone();
									$(this).empty().append($dot).append(document.createTextNode(' ' + labels[i] + ' ' + parseInt(counts[i]).toLocaleString() + ' (' + pcts[i] + '%)'));
								});
							} else {
								// Pie chart section doesn't exist yet — build it
								var pieHtml = '<div class="offload-dlx-plus-pie-container" id="stat-pie-section">';
								pieHtml += '<div class="offload-dlx-plus-pie" style="background: conic-gradient(#2271b1 0% ' + s1 + '%, #d63638 ' + s1 + '% ' + s2 + '%, #dba617 ' + s2 + '% ' + s3 + '%, #8c8f94 ' + s3 + '% 100%);"></div>';
								pieHtml += '<div class="offload-dlx-plus-pie-legend">';
								var colors = ['#2271b1', '#d63638', '#dba617', '#8c8f94'];
								var labels2 = [
									'<?php echo esc_js( __( 'Images', 'offload-dlx-plus' ) ); ?>',
									'<?php echo esc_js( __( 'Videos', 'offload-dlx-plus' ) ); ?>',
									'<?php echo esc_js( __( 'Audio', 'offload-dlx-plus' ) ); ?>',
									'<?php echo esc_js( __( 'Other', 'offload-dlx-plus' ) ); ?>'
								];
								var counts2 = [ft.images || 0, ft.videos || 0, ft.audio || 0, ft.other || 0];
								var pcts2 = [pImages, pVideos, pAudio, pOther];
								for (var i = 0; i < 4; i++) {
									pieHtml += '<div class="offload-dlx-plus-legend-item"><span class="offload-dlx-plus-legend-dot" style="background: ' + colors[i] + ';"></span> ' + labels2[i] + ' ' + parseInt(counts2[i]).toLocaleString() + ' (' + pcts2[i] + '%)</div>';
								}
								pieHtml += '</div></div>';
								$('.offload-dlx-plus-files-count').after(pieHtml);
							}
						}
					}

					$('#stat-last-updated').text('<?php echo esc_js( __( 'Last updated:', 'offload-dlx-plus' ) ); ?> <?php echo esc_js( __( 'just now', 'offload-dlx-plus' ) ); ?>');
				} else {
					// Remove stale pie chart and show ERROR state
					$('#stat-pie-section').remove();
					var errorHtml = '<div class="offload-dlx-plus-overview-bars"><div class="offload-dlx-plus-bar-section"><div class="offload-dlx-plus-bar-header"><span class="offload-dlx-plus-bar-title"><?php echo esc_js( __( 'Storage', 'offload-dlx-plus' ) ); ?></span><span class="offload-dlx-plus-bar-value" id="stat-storage-detail" style="color: #d63638; font-weight: 600;">ERROR</span></div></div></div>';
					errorHtml += '<div class="offload-dlx-plus-files-section"><div class="offload-dlx-plus-files-grid"><div class="offload-dlx-plus-files-count"><span class="offload-dlx-plus-stat-label"><?php echo esc_js( __( 'Total Files', 'offload-dlx-plus' ) ); ?></span><div id="stat-file-count" class="offload-dlx-plus-stat-value" style="color: #d63638; font-size: 16px;"><?php echo esc_js( __( 'ERROR: please update your credentials', 'offload-dlx-plus' ) ); ?></div></div></div></div>';
					errorHtml += '<p class="description" style="color: #d63638; margin-top: 10px;">' + (response.data.message || 'Unknown error') + '</p>';
					$('#stats-content').html(errorHtml);
				}
			},
			error: function(xhr, status, error) {
				var msg = status === 'timeout' ? '<?php echo esc_js( __( 'Request timed out. Try again later.', 'offload-dlx-plus' ) ); ?>' : 'Network error: ' + error;
				var errorHtml = '<div class="offload-dlx-plus-overview-bars"><div class="offload-dlx-plus-bar-section"><div class="offload-dlx-plus-bar-header"><span class="offload-dlx-plus-bar-title"><?php echo esc_js( __( 'Storage', 'offload-dlx-plus' ) ); ?></span><span class="offload-dlx-plus-bar-value" style="color: #d63638; font-weight: 600;">ERROR</span></div></div></div>';
				errorHtml += '<div class="offload-dlx-plus-files-section"><div class="offload-dlx-plus-files-grid"><div class="offload-dlx-plus-files-count"><span class="offload-dlx-plus-stat-label"><?php echo esc_js( __( 'Total Files', 'offload-dlx-plus' ) ); ?></span><div class="offload-dlx-plus-stat-value" style="color: #d63638; font-size: 16px;"><?php echo esc_js( __( 'ERROR: please update your credentials', 'offload-dlx-plus' ) ); ?></div></div></div></div>';
				errorHtml += '<p class="description" style="color: #d63638; margin-top: 10px;">' + msg + '</p>';
				$('#stats-content').html(errorHtml);
			},
			complete: function() {
				$button.prop('disabled', false);
				$button.find('.dashicons').removeClass('spin');
				$loading.hide();
				$content.show();
			}
		});
	});
});
</script>

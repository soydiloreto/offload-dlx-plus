<?php
/**
 * Admin: Sync tab template.
 *
 * Handles the synchronization process from local to cloud.
 *
 * Local variables ($current_state, $sync_progress, $stats, $failed_count, etc.)
 * are populated by Admin::render_tab_content() in the calling scope. Suppress
 * the prefix sniff for this template:
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 *
 * @package OffloadPlus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// All data is prepared by Admin::render_tab_content() — no business logic in templates
$current_state   = $template_data['current_state'] ?? 'not_configured';
$sync_progress   = $template_data['sync_progress'] ?? array();
$stats           = $template_data['stats'] ?? array();
$failed_files    = $template_data['failed_files'] ?? array();
$failed_count    = $template_data['failed_count'] ?? 0;
$has_files_in_db = $template_data['has_files_in_db'] ?? false;
$synced_count    = $template_data['synced_count'] ?? 0;
$pending_count   = $template_data['pending_count'] ?? 0;
?>

<div class="offload-plus-sync-container">
	<!-- ⭐ Global notification container -->
	<div id="offload-plus-notification" style="display: none; margin: 15px 0; padding: 12px 15px; border-radius: 4px; border-left: 4px solid;"></div>

	<!-- ⭐ CRITICAL WARNING: Do not close page during sync -->
	<div id="sync-warning-banner" style="display: none; position: sticky; top: 32px; z-index: 999; margin: 15px 0; padding: 15px 20px; background: #dc3545; color: white; border-radius: 4px; box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3); font-size: 15px; font-weight: 600;">
		<span class="dashicons dashicons-warning" style="font-size: 20px; vertical-align: middle; margin-right: 8px;"></span>
		<?php esc_html_e( '⚠️ SYNCHRONIZATION IN PROGRESS - DO NOT CLOSE THIS PAGE OR NAVIGATE AWAY', 'offload-plus' ); ?>
	</div>

	<!-- =========================================== -->
	<!-- CARD 1: CURRENT STATUS -->
	<!-- =========================================== -->
	<div class="card offload-plus-status-card" style="max-width: 900px; margin-bottom: 20px;">
		<h3 style="margin-top: 0; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #e0e0e0;">
			<?php esc_html_e( 'Current Status', 'offload-plus' ); ?>
		</h3>

		<?php if ( $current_state === 'configured' ) : ?>
			<!-- STATUS: CONFIGURED -->
			<?php if ( $has_files_in_db ) : ?>
				<!-- Sync started but not completed -->
				<div style="background: #fff3cd; border-left: 4px solid #f0b849; padding: 12px 15px; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 12px;">
					<span class="dashicons dashicons-update" style="color: #f0b849; font-size: 24px; flex-shrink: 0; margin-top: 2px;"></span>
					<div>
						<strong style="color: #856404; font-size: 14px;">
							<?php esc_html_e( 'Sync Not Completed', 'offload-plus' ); ?>
						</strong>
						<br>
						<span style="color: #856404; font-size: 13px;">
							<?php esc_html_e( 'A previous synchronization was interrupted. You can continue from where it left off or start fresh.', 'offload-plus' ); ?>
						</span>
					</div>
				</div>
			<?php else : ?>
				<!-- Fresh configuration, no sync started -->
				<div style="background: #d1ecf1; border-left: 4px solid #0073aa; padding: 12px 15px; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 12px;">
					<span class="dashicons dashicons-admin-settings" style="color: #0073aa; font-size: 24px; flex-shrink: 0; margin-top: 2px;"></span>
					<div>
						<strong style="color: #0c5460; font-size: 14px;">
							<?php esc_html_e( 'Cloud Provider Configured', 'offload-plus' ); ?>
						</strong>
						<br>
						<span style="color: #0c5460; font-size: 13px;">
							<?php esc_html_e( 'Your cloud provider is configured and ready. Click "Start Sync" below to upload your media files to the cloud.', 'offload-plus' ); ?>
						</span>
					</div>
				</div>
			<?php endif; ?>

		<?php elseif ( $current_state === 'synced' ) : ?>
			<?php if ( $failed_count > 0 ) : ?>
				<!-- STATUS: SYNCED WITH ERRORS (any files not synced) -->
				<div style="background: #fff3cd; border-left: 4px solid #f0b849; padding: 12px 15px; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 12px;">
					<span class="dashicons dashicons-warning" style="color: #f0b849; font-size: 24px; flex-shrink: 0; margin-top: 2px;"></span>
					<div>
						<strong style="color: #856404; font-size: 14px;">
							<?php esc_html_e( 'Synced with Errors', 'offload-plus' ); ?>
						</strong>
						<br>
						<span style="color: #856404; font-size: 13px;">
							<?php
							printf(
								/* translators: %d: number of files that failed to upload */
								esc_html__( 'Synchronization completed but %d files could not be uploaded. You can retry the failed files or proceed with offloading.', 'offload-plus' ),
								(int) $failed_count
							);
							?>
						</span>
					</div>
				</div>
			<?php else : ?>
				<!-- STATUS: SYNCED COMPLETED -->
				<div style="background: #d4edda; border-left: 4px solid #46b450; padding: 12px 15px; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 12px;">
					<span class="dashicons dashicons-yes-alt" style="color: #46b450; font-size: 24px; flex-shrink: 0; margin-top: 2px;"></span>
					<div>
						<strong style="color: #155724; font-size: 14px;">
							<?php esc_html_e( 'Synced Successfully', 'offload-plus' ); ?>
						</strong>
						<br>
						<span style="color: #155724; font-size: 13px;">
							<?php esc_html_e( 'All your media files have been uploaded to the cloud. You can now enable offloading to serve files directly from cloud storage.', 'offload-plus' ); ?>
						</span>
					</div>
				</div>
			<?php endif; ?>

		<?php elseif ( $current_state === 'offloading_active' ) : ?>
			<!-- STATUS: OFFLOADING ACTIVE -->
			<div style="background: #cce5ff; border-left: 4px solid #2196f3; padding: 12px 15px; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 12px;">
				<span class="dashicons dashicons-cloud" style="color: #2196f3; font-size: 24px; flex-shrink: 0; margin-top: 2px;"></span>
				<div>
					<strong style="color: #004085; font-size: 14px;">
						<?php esc_html_e( 'Offloading Active', 'offload-plus' ); ?>
					</strong>
					<br>
					<span style="color: #004085; font-size: 13px;">
						<?php esc_html_e( 'Your media files are being served directly from cloud storage. Local uploads are automatically synced to the cloud.', 'offload-plus' ); ?>
					</span>
				</div>
			</div>

		<?php elseif ( $current_state === 'syncing' ) : ?>
			<!-- STATUS: SYNCING -->
			<div style="background: #e7f3ff; border-left: 4px solid #0073aa; padding: 12px 15px; margin-bottom: 20px; display: flex; align-items: flex-start; gap: 12px;">
				<span class="dashicons dashicons-update" style="color: #0073aa; font-size: 24px; flex-shrink: 0; margin-top: 2px;"></span>
				<div>
					<strong style="color: #004085; font-size: 14px;">
						<?php esc_html_e( 'Sync in Progress', 'offload-plus' ); ?>
					</strong>
					<br>
					<span style="color: #004085; font-size: 13px;">
						<?php esc_html_e( 'File upload is in progress. Do not close this page until synchronization is complete.', 'offload-plus' ); ?>
					</span>
				</div>
			</div>

		<?php endif; ?>
	</div>

	<!-- =========================================== -->
	<!-- CARD 2: ACTIONS -->
	<!-- =========================================== -->
	<div class="card offload-plus-actions-card" style="max-width: 900px;">
		<h3 style="margin-top: 0; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #e0e0e0;">
			<?php esc_html_e( 'Actions', 'offload-plus' ); ?>
		</h3>

		<?php if ( $current_state === 'configured' ) : ?>
			<!-- ACTIONS: START SYNC / CONTINUE SYNC -->
			<?php
			// Continuation detection uses data prepared by the controller
			$is_continuation = ( $synced_count > 0 || $pending_count > 0 );
			?>

			<?php if ( $is_continuation ) : ?>
				<!-- ACTIONS: SYNC NOT COMPLETED (has files in DB) -->
				<div style="padding: 20px 0;">
					<?php if ( $pending_count > 0 ) : ?>
						<!-- Case 1: Incomplete sync (has pending files) -->
						<!-- Main action buttons side by side -->
						<div style="margin-bottom: 10px; display: flex; gap: 15px; align-items: flex-start;">
							<!-- Continue Sync button (primary action) -->
							<div style="flex: 1; background: #f0f6fc; border: 2px solid #0073aa; border-radius: 6px; padding: 15px;">
								<button id="start-sync-btn" class="button button-primary" style="width: 100%; height: 50px; font-size: 15px; background: #0073aa; border-color: #0073aa;">
									<span class="dashicons dashicons-cloud-upload"></span>
									<?php esc_html_e( 'Continue Sync', 'offload-plus' ); ?>
								</button>
								<p class="description" style="margin: 12px 0 0 0; font-size: 13px; line-height: 1.5; color: #555;">
									<?php
									printf(
										/* translators: 1: number of files already synced, 2: number of files pending */
										esc_html__( 'Resume synchronization. You have %1$d files already synced and %2$d files pending. The system will scan and detect any new or modified files.', 'offload-plus' ),
										(int) $synced_count,
										(int) $pending_count
									);
									?>
								</p>
							</div>

							<!-- Reset button (alternative action) -->
							<div style="flex: 1; background: #f9f9f9; border: 2px solid #ddd; border-radius: 6px; padding: 15px;">
								<button id="cancel-all-sync-btn" class="button" style="width: 100%; height: 50px; font-size: 15px; background: #dc3545; border-color: #dc3545; color: #fff;">
									<span class="dashicons dashicons-no-alt"></span>
									<?php esc_html_e( 'Reset Sync', 'offload-plus' ); ?>
								</button>
								<p class="description" style="margin: 12px 0 0 0; font-size: 13px; line-height: 1.5; color: #555;">
									<?php esc_html_e( 'Cancel the entire sync process and return to configured state. This will discard ALL progress including successfully uploaded files.', 'offload-plus' ); ?>
								</p>
							</div>
						</div>
					<?php else : ?>
						<!-- Case 2: Sync complete (pending=0, all synced) -->
						<div style="margin-bottom: 10px; display: flex; gap: 15px; align-items: flex-start;">
							<!-- Complete Sync button (primary action) -->
							<div style="flex: 1; background: #e7f5e7; border: 2px solid #46b450; border-radius: 6px; padding: 15px;">
								<button id="start-sync-btn" class="button button-primary" style="width: 100%; height: 50px; font-size: 15px; background: #46b450; border-color: #46b450;">
									<span class="dashicons dashicons-yes-alt"></span>
									<?php esc_html_e( 'Complete Sync', 'offload-plus' ); ?>
								</button>
								<p class="description" style="margin: 12px 0 0 0; font-size: 13px; line-height: 1.5; color: #555;">
									<?php
									printf(
										/* translators: %d: number of files already synced */
										esc_html__( 'All %d files are synced! Click to scan for any new/modified files and complete the sync process to enable offloading.', 'offload-plus' ),
										(int) $synced_count
									);
									?>
								</p>
							</div>

							<!-- Reset button (alternative action) -->
							<div style="flex: 1; background: #f9f9f9; border: 2px solid #ddd; border-radius: 6px; padding: 15px;">
								<button id="cancel-all-sync-btn" class="button" style="width: 100%; height: 50px; font-size: 15px; background: #dc3545; border-color: #dc3545; color: #fff;">
									<span class="dashicons dashicons-no-alt"></span>
									<?php esc_html_e( 'Reset Sync', 'offload-plus' ); ?>
								</button>
								<p class="description" style="margin: 12px 0 0 0; font-size: 13px; line-height: 1.5; color: #555;">
									<?php esc_html_e( 'Discard all sync progress and start from scratch.', 'offload-plus' ); ?>
								</p>
							</div>
						</div>
					<?php endif; ?>

					<!-- Cancel Sync button (hidden, shown during active sync) -->
					<button id="cancel-sync-btn" class="button button-secondary" style="display: none; margin-top: 15px;">
						<span class="dashicons dashicons-no-alt"></span>
						<?php esc_html_e( 'Cancel Sync', 'offload-plus' ); ?>
					</button>
				</div>

			<?php else : ?>
				<!-- ACTIONS: START SYNC (fresh configuration) -->
				<div style="padding: 20px 0;">
					<button id="start-sync-btn" class="button button-primary button-hero" style="margin-bottom: 15px;">
						<span class="dashicons dashicons-cloud-upload" style="margin-top: 5px;"></span>
						<?php esc_html_e( 'Start Sync', 'offload-plus' ); ?>
					</button>

					<button id="cancel-sync-btn" class="button button-secondary" style="display: none; margin-left: 10px;">
						<span class="dashicons dashicons-no-alt"></span>
						<?php esc_html_e( 'Cancel Sync', 'offload-plus' ); ?>
					</button>

					<p class="description" style="margin: 15px 0 0 0; font-size: 14px; line-height: 1.6;">
						<?php esc_html_e( 'This will scan your local media library and upload all files to cloud storage. Files already present in the cloud will be automatically skipped to save time and bandwidth.', 'offload-plus' ); ?>
					</p>
				</div>
			<?php endif; ?>

			<?php if ( defined( 'OFFLOAD_PLUS_DEV_MODE' ) && OFFLOAD_PLUS_DEV_MODE ) : ?>
			<!-- DEV MODE: Enable Without Sync -->
			<div style="margin-top: 20px; padding-top: 20px; border-top: 2px dashed #ff9800;">
				<div style="background: #fff3e0; border: 2px solid #ff9800; border-radius: 6px; padding: 15px;">
					<div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
						<span class="dashicons dashicons-warning" style="color: #ff9800; font-size: 20px; width: 20px; height: 20px;"></span>
						<strong style="color: #e65100; font-size: 13px;"><?php esc_html_e( 'DEV MODE', 'offload-plus' ); ?></strong>
					</div>
					<button id="dev-enable-without-sync-btn" class="button" style="width: 100%; height: 45px; font-size: 14px; background: #ff9800; border-color: #e65100; color: #fff;">
						<span class="dashicons dashicons-controls-skipforward" style="margin-top: 3px;"></span>
						<?php esc_html_e( 'Enable Without Sync', 'offload-plus' ); ?>
					</button>
					<p class="description" style="margin: 10px 0 0 0; font-size: 12px; line-height: 1.5; color: #795548;">
						<?php esc_html_e( 'Skip file upload and jump directly to offloading mode. Assumes cloud already has all files. For development/testing only.', 'offload-plus' ); ?>
					</p>
				</div>
			</div>
			<?php endif; ?>

			<!-- Progress container (shown during sync) -->
			<div id="sync-progress-container" style="display: none; margin-top: 30px; padding-top: 30px; border-top: 1px solid #e0e0e0;">
				<h4 style="margin: 0 0 15px 0;"><?php esc_html_e( 'Sync Progress', 'offload-plus' ); ?></h4>

				<!-- ⭐ Status Message (for errors/warnings) -->
				<div id="sync-status-message" style="display: none; padding: 12px 15px; background: #fff3cd; border-left: 4px solid #ffc107; margin-bottom: 15px; border-radius: 4px;">
					<!-- Error/warning messages appear here -->
				</div>

				<div class="progress-bar">
					<div id="sync-progress-bar" class="progress-fill" style="width: 0%"></div>
				</div>

				<div id="sync-progress-text" class="progress-text" style="margin-top: 10px;">
					0 / 0 files (0%)
				</div>

				<!-- ⭐ Batch Info -->
				<div id="batch-info" style="display: none; margin-top: 10px; font-size: 14px; color: #0073aa;">
					<!-- Batch statistics appear here -->
				</div>

				<div id="current-file-text" style="margin-top: 10px; font-size: 14px; color: #666;">
					<!-- Current file being processed -->
				</div>
			</div>

		<?php elseif ( $current_state === 'synced' && $failed_count > 0 ) : ?>
			<!-- ACTIONS: SYNCED WITH ERRORS -->
			<div style="padding: 20px 0;">
				<!-- Main action buttons side by side -->
				<div style="margin-bottom: 10px; display: flex; gap: 15px; align-items: flex-start;">
					<!-- Retry button (primary action) -->
					<div style="flex: 1; background: #f0f6fc; border: 2px solid #0073aa; border-radius: 6px; padding: 15px;">
						<button class="retry-failed-btn button button-primary" style="width: 100%; height: 50px; font-size: 15px; background: #0073aa; border-color: #0073aa;">
							<span class="dashicons dashicons-update"></span>
							<?php esc_html_e( 'Retry Failed Files', 'offload-plus' ); ?>
						</button>
						<p class="description" style="margin: 12px 0 0 0; font-size: 13px; line-height: 1.5; color: #555;">
							<?php
							printf(
								/* translators: %d: number of files that failed to upload */
								esc_html__( 'Attempt to upload the %d failed files again. Successfully uploaded files remain in cloud storage.', 'offload-plus' ),
								(int) $failed_count
							);
							?>
						</p>
					</div>

					<!-- Enable offloading button (alternative action) -->
					<div style="flex: 1; background: #f9f9f9; border: 2px solid #ddd; border-radius: 6px; padding: 15px;">
						<button id="discard-and-enable-static-btn" class="button" style="width: 100%; height: 50px; font-size: 15px; background: #46b450; border-color: #46b450; color: #fff;">
							<span class="dashicons dashicons-yes"></span>
							<?php esc_html_e( 'Clear Failed & Enable', 'offload-plus' ); ?>
						</button>
						<p class="description" style="margin: 12px 0 0 0; font-size: 13px; line-height: 1.5; color: #555;">
							<?php esc_html_e( 'Discard failed files list and enable offloading. Failed files will remain in local storage only.', 'offload-plus' ); ?>
						</p>
					</div>
				</div>

				<!-- View failed files link (small, below buttons) -->
				<div style="margin-bottom: 30px; margin-top: 15px;">
					<button class="view-failed-btn button button-link" style="text-decoration: none; padding: 0; height: auto; font-size: 13px; color: #0073aa;">
						<span class="dashicons dashicons-visibility" style="font-size: 13px; margin-top: 2px;"></span>
						<?php esc_html_e( 'View Failed Files', 'offload-plus' ); ?>
					</button>
				</div>

				<!-- Separator and Cancel Sync button (red, separated) -->
				<div style="padding-top: 25px; border-top: 2px solid #e0e0e0; margin-top: 10px;">
					<button id="cancel-all-sync-btn" class="button" style="background: #dc3545; border-color: #dc3545; color: #fff; padding: 8px 20px;">
						<span class="dashicons dashicons-no-alt"></span>
						<?php esc_html_e( 'Cancel Sync & Reset', 'offload-plus' ); ?>
					</button>
					<p class="description" style="margin: 10px 0 0 0; font-size: 13px; color: #666;">
						<?php esc_html_e( 'Cancel the entire sync process and return to configured state. This will discard ALL progress including successfully uploaded files.', 'offload-plus' ); ?>
					</p>
				</div>
			</div>

		<?php elseif ( $current_state === 'synced' && (int) $failed_count === 0 ) : ?>
			<!-- ACTIONS: SYNCED COMPLETED (NO ERRORS, NO PENDINGS) -->
			<div style="padding: 20px 0;">
				<!-- Main action: Enable Offloading (prominent) -->
				<div style="margin-bottom: 20px;">
					<div style="background: #e7f5e7; border: 3px solid #46b450; border-radius: 8px; padding: 20px;">
						<button id="enable-offloading-btn" class="button button-primary" data-confirm="true" style="width: 100%; height: 60px; font-size: 16px; background: #46b450; border-color: #46b450;">
							<span class="dashicons dashicons-cloud" style="font-size: 20px;"></span>
							<?php esc_html_e( 'Enable Cloud Storage (Offloading)', 'offload-plus' ); ?>
						</button>
						<p class="description" style="margin: 12px 0 0 0; font-size: 13px; line-height: 1.5; color: #155724;">
							<?php esc_html_e( 'Activate offloading to serve all media files directly from cloud storage. New uploads will go straight to the cloud, saving local disk space.', 'offload-plus' ); ?>
						</p>
					</div>
				</div>

				<!-- Secondary actions side by side -->
				<div style="display: flex; gap: 15px; margin-bottom: 20px;">
					<!-- Resync button -->
					<div style="flex: 1; background: #f0f6fc; border: 2px solid #ddd; border-radius: 6px; padding: 15px;">
						<button class="resync-all-btn button button-secondary" style="width: 100%; height: 45px; font-size: 14px;">
							<span class="dashicons dashicons-backup"></span>
							<?php esc_html_e( 'Resync All Files', 'offload-plus' ); ?>
						</button>
						<p class="description" style="margin: 12px 0 0 0; font-size: 12px; line-height: 1.5; color: #555;">
							<?php esc_html_e( 'Compare local files with cloud storage and resynchronize everything from scratch.', 'offload-plus' ); ?>
						</p>
					</div>

					<!-- Reset button -->
					<div style="flex: 1; background: #f9f9f9; border: 2px solid #ddd; border-radius: 6px; padding: 15px;">
						<button id="cancel-all-sync-btn" class="button" style="width: 100%; height: 45px; font-size: 14px; background: #dc3545; border-color: #dc3545; color: #fff;">
							<span class="dashicons dashicons-no-alt"></span>
							<?php esc_html_e( 'Reset Sync', 'offload-plus' ); ?>
						</button>
						<p class="description" style="margin: 12px 0 0 0; font-size: 12px; line-height: 1.5; color: #555;">
							<?php esc_html_e( 'Cancel sync and return to configured state. This will discard all progress.', 'offload-plus' ); ?>
						</p>
					</div>
				</div>
			</div>

		<?php elseif ( $current_state === 'syncing' ) : ?>
			<!-- ACTIONS: SYNCING -->
			<div style="background: #e7f3ff; border-left: 4px solid #0073aa; padding: 12px 15px; display: flex; align-items: center; gap: 12px;">
				<span class="dashicons dashicons-info" style="color: #0073aa; font-size: 24px; flex-shrink: 0;"></span>
				<div>
					<span style="color: #004085; font-size: 13px;">
						<?php esc_html_e( 'Sync controls are available in the modal dialog above.', 'offload-plus' ); ?>
					</span>
				</div>
			</div>

		<?php elseif ( $current_state === 'offloading_active' ) : ?>
			<!-- ACTIONS: OFFLOADING ACTIVE -->
			<div style="padding: 20px 0;">
				<!-- Primary Actions: Disconnect and Delete Local Files -->
				<div style="margin-bottom: 20px; display: flex; gap: 15px; align-items: flex-start;">
					<!-- Disconnect from Cloud (primary action) -->
					<div style="flex: 1; background: #fff3f3; border: 2px solid #dc3545; border-radius: 6px; padding: 15px;">
						<button id="disconnect-from-cloud-btn" class="button" style="width: 100%; height: 50px; font-size: 15px; background: #dc3545; border-color: #dc3545; color: #fff;">
							<span class="dashicons dashicons-download"></span>
							<?php esc_html_e( 'Disconnect from Cloud', 'offload-plus' ); ?>
						</button>
						<p class="description" style="margin: 12px 0 0 0; font-size: 13px; line-height: 1.5; color: #555;">
							<?php esc_html_e( 'Download all files from cloud storage back to local storage and disable offloading. This is a full reverse sync operation.', 'offload-plus' ); ?>
						</p>
					</div>

					<?php if ( ! empty( $stats['deletable_files'] ) ) : ?>
					<!-- Delete Local Files (alternative action) -->
					<div style="flex: 1; background: #f0f6fc; border: 2px solid #0073aa; border-radius: 6px; padding: 15px;">
						<button id="delete-local-files-btn" class="button" style="width: 100%; height: 50px; font-size: 15px; background: #0073aa; border-color: #0073aa; color: #fff;">
							<span class="dashicons dashicons-trash"></span>
							<?php esc_html_e( 'Delete Local Files', 'offload-plus' ); ?>
						</button>
						<p class="description" style="margin: 12px 0 0 0; font-size: 13px; line-height: 1.5; color: #555;">
							<?php
							echo wp_kses(
								sprintf(
									/* translators: 1: human-readable disk size (e.g. "200 MB"), 2: number of files */
									__( 'Free up %1$s of disk space by deleting %2$s local files. Files will continue to be served from cloud storage.', 'offload-plus' ),
									'<strong>' . esc_html( (string) size_format( $stats['deletable_size'] ) ) . '</strong>',
									'<strong>' . esc_html( number_format_i18n( $stats['deletable_files'] ) ) . '</strong>'
								),
								array( 'strong' => array() )
							);
							?>
						</p>
					</div>
					<?php endif; ?>
				</div>

				<?php if ( defined( 'OFFLOAD_PLUS_DEV_MODE' ) && OFFLOAD_PLUS_DEV_MODE ) : ?>
				<!-- DEV MODE: Disconnect Without Sync -->
				<div style="margin-top: 15px;">
					<div style="background: #fff3e0; border: 2px solid #ff9800; border-radius: 6px; padding: 15px;">
						<div style="display: flex; align-items: center; gap: 8px; margin-bottom: 10px;">
							<span class="dashicons dashicons-warning" style="color: #ff9800; font-size: 20px; width: 20px; height: 20px;"></span>
							<strong style="color: #e65100; font-size: 13px;"><?php esc_html_e( 'DEV MODE', 'offload-plus' ); ?></strong>
						</div>
						<button id="dev-disconnect-without-sync-btn" class="button" style="width: 100%; height: 45px; font-size: 14px; background: #ff9800; border-color: #e65100; color: #fff;">
							<span class="dashicons dashicons-controls-skipforward" style="margin-top: 3px;"></span>
							<?php esc_html_e( 'Disconnect Without Sync', 'offload-plus' ); ?>
						</button>
						<p class="description" style="margin: 10px 0 0 0; font-size: 12px; line-height: 1.5; color: #795548;">
							<?php esc_html_e( 'Skip file download and jump directly to configured state. Assumes local already has all files. For development/testing only.', 'offload-plus' ); ?>
						</p>
					</div>
				</div>
				<?php endif; ?>

				<?php if ( $failed_count > 0 ) : ?>
				<!-- Failed Files Section (in offloading_active state) -->
				<div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #e0e0e0;">
					<div style="background: #fff3cd; border-left: 4px solid #f0b849; padding: 20px; border-radius: 6px;">
						<p style="margin: 0 0 15px 0; font-weight: 600; color: #856404; font-size: 15px;">
							<span class="dashicons dashicons-warning" style="font-size: 20px; vertical-align: middle; margin-right: 5px;"></span>
							<?php
							/* translators: %d: number of files that failed to sync */
							printf( esc_html__( '%d files failed to sync', 'offload-plus' ), (int) $failed_count );
							?>
						</p>
						<div style="display: flex; gap: 10px; flex-wrap: wrap;">
							<button class="retry-failed-btn button button-primary">
								<span class="dashicons dashicons-update"></span>
								<?php esc_html_e( 'Retry Failed Files', 'offload-plus' ); ?>
							</button>
							<button class="view-failed-btn button button-secondary">
								<span class="dashicons dashicons-visibility"></span>
								<?php esc_html_e( 'View Failed Files', 'offload-plus' ); ?>
							</button>
							<button class="clear-failed-btn button button-secondary">
								<span class="dashicons dashicons-dismiss"></span>
								<?php esc_html_e( 'Clear List', 'offload-plus' ); ?>
							</button>
						</div>
					</div>
				</div>
				<?php endif; ?>
			</div>

		<?php endif; ?>
	</div>

	<!-- =========================================== -->
	<!-- MODALS -->
	<!-- =========================================== -->

	<!-- Failed Files Modal -->
	<div id="failed-files-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 100000;">
		<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 30px; border-radius: 8px; max-width: 800px; max-height: 80vh; overflow-y: auto; width: 90%;">
			<h2 style="margin-top: 0;"><?php esc_html_e( 'Failed Files', 'offload-plus' ); ?> (<?php echo (int) $failed_count; ?>)</h2>
			<div style="max-height: 400px; overflow-y: auto; margin: 20px 0;">
				<table class="wp-list-table widefat fixed striped">
					<thead>
						<tr>
							<th style="width: 50%;"><?php esc_html_e( 'File', 'offload-plus' ); ?></th>
							<th style="width: 10%;"><?php esc_html_e( 'Attempts', 'offload-plus' ); ?></th>
							<th style="width: 40%;"><?php esc_html_e( 'Error', 'offload-plus' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $failed_files as $failed ) : ?>
							<tr>
								<td><code style="font-size: 11px;"><?php echo esc_html( basename( $failed['file'] ?? '' ) ); ?></code></td>
								<td><?php echo esc_html( $failed['errors'] ?? '0' ); ?></td>
								<td style="font-size: 11px;"><?php echo esc_html( $failed['error_message'] ?? 'Unknown error' ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>
			<button id="close-failed-modal" class="button button-primary"><?php esc_html_e( 'Close', 'offload-plus' ); ?></button>
		</div>
	</div>

	<!-- Clear Failed & Enable Offloading Confirmation Modal -->
	<div id="clear-and-enable-modal" class="offload-plus-modal" style="display: none;">
		<div class="offload-plus-modal-overlay"></div>
		<div class="offload-plus-modal-content" style="max-width: 550px;">
			<!-- Initial confirmation view -->
			<div id="clear-enable-confirm-view">
				<h3 style="margin-top: 0; color: #46b450; border-bottom: 2px solid #46b450; padding-bottom: 10px;">
					<span class="dashicons dashicons-yes" style="font-size: 24px;"></span>
					<?php esc_html_e( 'Clear Failed Files & Enable Offloading', 'offload-plus' ); ?>
				</h3>

				<div style="background: #fff3cd; border-left: 4px solid #f0b849; padding: 12px; margin: 15px 0; border-radius: 4px;">
					<p style="margin: 0; color: #856404; font-size: 14px;">
						<strong>⚠️ <?php esc_html_e( 'Important:', 'offload-plus' ); ?></strong>
						<?php esc_html_e( 'This action will discard the list of failed files and enable cloud storage offloading.', 'offload-plus' ); ?>
					</p>
				</div>

				<div style="margin: 20px 0;">
					<p style="margin: 0 0 10px 0; font-size: 14px; line-height: 1.6;">
						<strong><?php esc_html_e( 'What will happen:', 'offload-plus' ); ?></strong>
					</p>
					<ul style="margin: 0 0 15px 20px; font-size: 14px; line-height: 1.8;">
						<li>
						<?php
							/* translators: %d: number of failed files to remove */
							printf( esc_html__( 'The %d failed files will be removed from the sync queue', 'offload-plus' ), (int) $failed_count );
						?>
						</li>
						<li><?php esc_html_e( 'Failed files will remain in local storage only (not in cloud)', 'offload-plus' ); ?></li>
						<li><?php esc_html_e( 'Successfully uploaded files will continue to be served from cloud', 'offload-plus' ); ?></li>
						<li><?php esc_html_e( 'Offloading will be enabled for future uploads', 'offload-plus' ); ?></li>
					</ul>
				</div>

				<div style="margin-top: 25px; padding-top: 15px; border-top: 1px solid #ddd; text-align: right;">
					<button type="button" class="button button-secondary close-clear-enable-modal" style="margin-right: 10px;">
						<?php esc_html_e( 'Cancel', 'offload-plus' ); ?>
					</button>
					<button type="button" id="confirm-clear-and-enable" class="button button-primary" style="background: #46b450; border-color: #46b450;">
						<?php esc_html_e( 'Yes, Clear & Enable', 'offload-plus' ); ?>
					</button>
				</div>
			</div>

			<!-- Processing view -->
			<div id="clear-enable-processing-view" style="display: none; text-align: center; padding: 60px 20px;">
				<div class="spinner is-active" style="float: none; width: 40px; height: 40px; margin: 0 auto 20px;"></div>
				<h3 style="margin: 0 0 10px 0; font-size: 20px; font-weight: 600; color: #2271b1;">
					<?php esc_html_e( 'Processing...', 'offload-plus' ); ?>
				</h3>
				<p style="margin: 0; font-size: 15px; color: #666;">
					<?php esc_html_e( 'Clearing failed files and enabling offloading', 'offload-plus' ); ?>
				</p>
			</div>

			<!-- Success view -->
			<div id="clear-enable-success-view" style="display: none; text-align: center; padding: 60px 20px;">
				<div style="width: 80px; height: 80px; margin: 0 auto 20px; background: #46b450; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
					<span class="dashicons dashicons-yes" style="font-size: 50px; color: #fff; width: 50px; height: 50px;"></span>
				</div>
				<h3 style="margin: 0 0 10px 0; font-size: 20px; font-weight: 600; color: #46b450;">
					<?php esc_html_e( 'Successfully Enabled!', 'offload-plus' ); ?>
				</h3>
				<p style="margin: 0; font-size: 15px; color: #666;">
					<?php esc_html_e( 'Failed files cleared and offloading activated', 'offload-plus' ); ?>
				</p>
			</div>

			<!-- Error view -->
			<div id="clear-enable-error-view" style="display: none; text-align: center; padding: 60px 20px;">
				<div style="width: 80px; height: 80px; margin: 0 auto 20px; background: #dc3545; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
					<span class="dashicons dashicons-no" style="font-size: 50px; color: #fff; width: 50px; height: 50px;"></span>
				</div>
				<h3 style="margin: 0 0 10px 0; font-size: 20px; font-weight: 600; color: #dc3545;">
					<?php esc_html_e( 'Error', 'offload-plus' ); ?>
				</h3>
				<p id="clear-enable-error-message" style="margin: 0 0 20px 0; font-size: 15px; color: #666;"></p>
				<button type="button" class="button button-primary close-clear-enable-modal">
					<?php esc_html_e( 'Close', 'offload-plus' ); ?>
				</button>
			</div>
		</div>
	</div>

	<!-- Cancel Sync & Reset Confirmation Modal -->
	<div id="cancel-sync-modal" class="offload-plus-modal" style="display: none;">
		<div class="offload-plus-modal-overlay"></div>
		<div class="offload-plus-modal-content" style="max-width: 550px;">
			<h3 style="margin-top: 0; color: #dc3545; border-bottom: 2px solid #dc3545; padding-bottom: 10px;">
				<span class="dashicons dashicons-warning" style="font-size: 24px;"></span>
				<?php esc_html_e( 'Cancel Sync & Reset', 'offload-plus' ); ?>
			</h3>

			<div style="background: #f8d7da; border-left: 4px solid #dc3545; padding: 12px; margin: 15px 0; border-radius: 4px;">
				<p style="margin: 0; color: #721c24; font-size: 14px;">
					<strong>⚠️ <?php esc_html_e( 'Warning:', 'offload-plus' ); ?></strong>
					<?php esc_html_e( 'This action will completely reset the synchronization and cannot be undone.', 'offload-plus' ); ?>
				</p>
			</div>

			<div style="margin: 20px 0;">
				<p style="margin: 0 0 10px 0; font-size: 14px; line-height: 1.6;">
					<strong><?php esc_html_e( 'What will happen:', 'offload-plus' ); ?></strong>
				</p>
				<ul style="margin: 0 0 15px 20px; font-size: 14px; line-height: 1.8; color: #721c24;">
					<li><?php esc_html_e( 'ALL sync progress will be discarded (including successfully uploaded files)', 'offload-plus' ); ?></li>
					<li><?php esc_html_e( 'The plugin will return to CONFIGURED state', 'offload-plus' ); ?></li>
					<li><?php esc_html_e( 'Files uploaded to cloud will remain there but won\'t be tracked', 'offload-plus' ); ?></li>
					<li><?php esc_html_e( 'You will need to sync again from scratch if you want to use offloading', 'offload-plus' ); ?></li>
				</ul>
			</div>

			<div style="margin-top: 25px; padding-top: 15px; border-top: 1px solid #ddd; text-align: right;">
				<button type="button" class="button button-secondary close-cancel-sync-modal" style="margin-right: 10px;">
					<?php esc_html_e( 'No, Keep Progress', 'offload-plus' ); ?>
				</button>
				<button type="button" id="confirm-cancel-sync" class="button" style="background: #dc3545; border-color: #dc3545; color: #fff;">
					<?php esc_html_e( 'Yes, Reset Everything', 'offload-plus' ); ?>
				</button>
			</div>
		</div>
	</div>

	<!-- Disconnect from Cloud Confirmation Modal -->
	<div id="disconnect-modal" class="offload-plus-modal" style="display: none;">
		<div class="offload-plus-modal-overlay"></div>
		<div class="offload-plus-modal-content" style="max-width: 700px;">
			<!-- Initial confirmation view -->
			<div id="disconnect-confirm-view">
				<h3 style="margin-top: 0; color: #dc3545; border-bottom: 2px solid #dc3545; padding-bottom: 10px;">
					<span class="dashicons dashicons-download" style="font-size: 24px;"></span>
					<?php esc_html_e( 'Disconnect from Cloud Provider', 'offload-plus' ); ?>
				</h3>

				<div style="background: #f8d7da; border-left: 4px solid #dc3545; padding: 12px; margin: 15px 0; border-radius: 4px;">
					<p style="margin: 0; color: #721c24; font-size: 14px;">
						<strong>⚠️ <?php esc_html_e( 'Warning:', 'offload-plus' ); ?></strong>
						<?php esc_html_e( 'This action will download all files from cloud storage back to local storage and disable offloading.', 'offload-plus' ); ?>
					</p>
				</div>

				<div style="margin: 20px 0;">
					<p style="margin: 0 0 10px 0; font-size: 14px; line-height: 1.6;">
						<strong><?php esc_html_e( 'What will happen:', 'offload-plus' ); ?></strong>
					</p>
					<ul style="margin: 0 0 15px 20px; font-size: 14px; line-height: 1.8; color: #721c24;">
						<li><?php esc_html_e( 'All files will be downloaded from cloud to local storage (reverse sync)', 'offload-plus' ); ?></li>
						<li><?php esc_html_e( 'Offloading will be disabled automatically', 'offload-plus' ); ?></li>
						<li><?php esc_html_e( 'Files in cloud will remain untouched (no deletion)', 'offload-plus' ); ?></li>
						<li><?php esc_html_e( 'This process may take time depending on the number of files', 'offload-plus' ); ?></li>
					</ul>
				</div>

				<div style="margin-top: 25px; padding-top: 15px; border-top: 1px solid #ddd; text-align: right;">
					<button type="button" class="button button-secondary close-disconnect-modal" style="margin-right: 10px;">
						<?php esc_html_e( 'Cancel', 'offload-plus' ); ?>
					</button>
					<button type="button" id="confirm-disconnect" class="button" style="background: #dc3545; border-color: #dc3545; color: #fff;">
						<?php esc_html_e( 'Yes, Disconnect & Download', 'offload-plus' ); ?>
					</button>
				</div>
			</div>

			<!-- Scanning view -->
			<div id="disconnect-scanning-view" style="display: none; text-align: center; padding: 60px 20px;">
				<div class="spinner is-active" style="float: none; width: 40px; height: 40px; margin: 0 auto 20px;"></div>
				<h3 style="margin: 0 0 10px 0; font-size: 20px; font-weight: 600; color: #2271b1;">
					<?php esc_html_e( 'Scanning Cloud Storage...', 'offload-plus' ); ?>
				</h3>
				<p style="margin: 0; font-size: 15px; color: #666;">
					<?php esc_html_e( 'Finding all files in Azure. This may take a moment.', 'offload-plus' ); ?>
				</p>
			</div>

			<!-- Download options view -->
			<div id="disconnect-options-view" style="display: none;">
				<h3 style="margin-top: 0; color: #dc3545; border-bottom: 2px solid #dc3545; padding-bottom: 10px;">
					<span class="dashicons dashicons-download" style="font-size: 24px;"></span>
					<?php esc_html_e( 'Download Files from Cloud', 'offload-plus' ); ?>
				</h3>

				<div id="disconnect-stats" style="background: #f0f0f1; padding: 15px; border-radius: 4px; margin: 15px 0;">
					<!-- Stats will be populated via JS -->
				</div>

				<div style="margin-top: 25px; padding-top: 15px; border-top: 1px solid #ddd; text-align: right;">
					<button type="button" class="button button-secondary close-disconnect-modal" style="margin-right: 10px;">
						<?php esc_html_e( 'Cancel', 'offload-plus' ); ?>
					</button>
					<button type="button" id="start-disconnect" class="button button-primary" style="background: #dc3545; border-color: #dc3545;">
						<?php esc_html_e( 'Start Download', 'offload-plus' ); ?>
					</button>
				</div>
			</div>

			<!-- Progress view -->
			<div id="disconnect-progress-view" style="display: none; padding: 20px;">
				<h3 style="margin-top: 0; color: #2271b1; border-bottom: 2px solid #2271b1; padding-bottom: 10px;">
					<span class="dashicons dashicons-download" style="font-size: 24px;"></span>
					<?php esc_html_e( 'Downloading Files...', 'offload-plus' ); ?>
				</h3>

				<!-- ⭐ WARNING (coherente con sync modal) -->
				<div style="background: linear-gradient(135deg, #d63638 0%, #c62d30 100%); color: #fff; padding: 20px; border-radius: 6px; margin-bottom: 20px; text-align: center; box-shadow: 0 2px 8px rgba(214, 54, 56, 0.3); border: 2px solid #d63638;">
					<div style="font-size: 28px; font-weight: 700; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px;">
						⚠️ <?php esc_html_e( 'DO NOT CLOSE THIS WINDOW', 'offload-plus' ); ?> ⚠️
					</div>
					<div style="font-size: 14px; font-weight: 500; opacity: 0.95;">
						<?php esc_html_e( 'Closing this window will cancel the download', 'offload-plus' ); ?>
					</div>
				</div>

				<p id="disconnect-progress-label" style="text-align: center; font-weight: 600; margin: 15px 0;"><?php esc_html_e( 'Downloading files from cloud...', 'offload-plus' ); ?></p>

				<div id="disconnect-progress-details" style="margin: 20px 0;">
					<!-- ⭐ Progress bar (coherente con sync modal) -->
					<div style="background: #f0f0f1; border-radius: 8px; overflow: hidden; margin: 15px 0;">
						<div id="disconnect-progress-bar" class="progress-fill" style="height: 30px; background: linear-gradient(90deg, #0073aa 0%, #005177 100%); width: 0%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">
							<span id="disconnect-progress-percent">0%</span>
						</div>
					</div>

					<div id="disconnect-progress-text" style="margin-top: 10px; text-align: center; font-size: 14px; color: #666;">
						0 / 0 files (0%)
					</div>
				</div>

				<!-- ⭐ Statistics (coherente con sync modal) -->
				<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-top: 20px;">
					<div style="background: #f0f0f1; padding: 12px; border-radius: 4px; text-align: center;">
						<div style="font-size: 12px; color: #666; margin-bottom: 4px;"><?php esc_html_e( 'Downloaded', 'offload-plus' ); ?></div>
						<div id="disconnect-stats-downloaded" style="font-size: 18px; font-weight: 600;">0</div>
					</div>
					<div style="background: #d4edda; padding: 12px; border-radius: 4px; text-align: center;">
						<div style="font-size: 12px; color: #155724; margin-bottom: 4px;"><?php esc_html_e( 'Successful', 'offload-plus' ); ?></div>
						<div id="disconnect-stats-successful" style="font-size: 18px; font-weight: 600; color: #155724;">0</div>
					</div>
					<div style="background: #fff3cd; padding: 12px; border-radius: 4px; text-align: center;">
						<div style="font-size: 12px; color: #856404; margin-bottom: 4px;"><?php esc_html_e( 'Remaining', 'offload-plus' ); ?></div>
						<div id="disconnect-stats-remaining" style="font-size: 18px; font-weight: 600; color: #856404;">0</div>
					</div>
				</div>

				<!-- ⭐ Cancel button (coherente con sync modal) -->
				<div style="margin-top: 25px; padding-top: 15px; border-top: 1px solid #ddd; text-align: right;">
					<button type="button" id="cancel-disconnect" class="button button-secondary">
						<span class="dashicons dashicons-no-alt"></span>
						<?php esc_html_e( 'Cancel Download', 'offload-plus' ); ?>
					</button>
				</div>
			</div>

			<!-- Success view -->
			<div id="disconnect-success-view" style="display: none; text-align: center; padding: 60px 20px;">
				<div style="width: 80px; height: 80px; margin: 0 auto 20px; background: #46b450; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
					<span class="dashicons dashicons-yes" style="font-size: 50px; color: #fff; width: 50px; height: 50px;"></span>
				</div>
				<h3 style="margin: 0 0 10px 0; font-size: 20px; font-weight: 600; color: #46b450;">
					<?php esc_html_e( 'Disconnected Successfully!', 'offload-plus' ); ?>
				</h3>
				<p style="margin: 0; font-size: 15px; color: #666;">
					<?php esc_html_e( 'All files downloaded and offloading disabled', 'offload-plus' ); ?>
				</p>
			</div>

			<!-- Error view with Force Disconnect option -->
			<div id="disconnect-error-view" style="display: none; text-align: center; padding: 40px 20px;">
				<div style="width: 80px; height: 80px; margin: 0 auto 20px; background: #dc3545; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
					<span class="dashicons dashicons-no" style="font-size: 50px; color: #fff; width: 50px; height: 50px;"></span>
				</div>
				<h3 style="margin: 0 0 10px 0; font-size: 20px; font-weight: 600; color: #dc3545;">
					<?php esc_html_e( 'Error', 'offload-plus' ); ?>
				</h3>
				<p id="disconnect-error-message" style="margin: 0 0 15px 0; font-size: 15px; color: #666;"></p>

				<!-- Force disconnect warning + button -->
				<div style="background: #fff3cd; border: 1px solid #ffecb5; border-radius: 6px; padding: 15px; margin: 15px 0; text-align: left;">
					<p style="margin: 0 0 8px 0; font-weight: 600; color: #856404;">
						<?php esc_html_e( 'You can force disconnect without downloading files:', 'offload-plus' ); ?>
					</p>
					<p style="margin: 0; font-size: 13px; color: #856404;">
						<?php esc_html_e( 'Files stored in the cloud will NOT be downloaded back to your server. Only files already available locally will remain accessible. This action cannot be undone.', 'offload-plus' ); ?>
					</p>
				</div>

				<div style="display: flex; gap: 10px; justify-content: center; margin-top: 20px;">
					<button type="button" class="button button-secondary close-disconnect-modal">
						<?php esc_html_e( 'Close', 'offload-plus' ); ?>
					</button>
					<button type="button" id="force-disconnect-btn" class="button" style="background: #d63638; border-color: #d63638; color: #fff;">
						<?php esc_html_e( 'Force Disconnect Without Sync', 'offload-plus' ); ?>
					</button>
				</div>
			</div>
		</div>
	</div>

	<!-- ⭐ Unified Sync Modal (for both Sync and Disconnect) - OUTSIDE CONDITIONAL -->
	<div id="sync-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 100000;">
		<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 30px; border-radius: 8px; max-width: 700px; width: 90%;">

			<!-- ⭐ Dynamic Container (for new double-validation flow) -->
			<div id="sync-container" style="display: none;"></div>

			<!-- ⭐ Dynamic Summary (for Retry/Resync flows) -->
			<div id="sync-modal-summary"></div>

			<!-- Main Sync Modal Content (title, config, progress, buttons) -->
			<div id="sync-modal-content">
				<h2 id="sync-modal-title" style="margin-top: 0; border-bottom: 1px solid #ddd; padding-bottom: 15px;">
					<span id="sync-modal-icon" class="dashicons dashicons-cloud-upload"></span>
					<span id="sync-modal-title-text"><?php esc_html_e( 'Sync Files to Cloud', 'offload-plus' ); ?></span>
				</h2>

			<!-- Configuration Step -->
			<div id="sync-modal-config" style="margin: 20px 0;">
				<p id="sync-modal-description" style="margin: 0 0 15px 0; font-size: 14px;">
					<?php esc_html_e( 'This will scan local files and upload them to cloud storage.', 'offload-plus' ); ?>
				</p>

				<div style="background: #f0f0f1; padding: 15px; border-radius: 4px; margin: 15px 0;">
					<div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
						<span style="font-weight: 600;">📊 <span id="sync-modal-files-label"><?php esc_html_e( 'Files to process:', 'offload-plus' ); ?></span></span>
						<span id="sync-modal-total-files">-</span>
					</div>
					<div style="display: flex; justify-content: space-between;">
						<span style="font-weight: 600;">💾 <?php esc_html_e( 'Estimated size:', 'offload-plus' ); ?></span>
						<span id="sync-modal-total-size">-</span>
					</div>
				</div>

				<div style="margin: 20px 0;">
					<label for="sync-modal-concurrency" style="display: block; margin-bottom: 10px; font-weight: 600;">
						<?php esc_html_e( 'Performance Level:', 'offload-plus' ); ?>
					</label>
					<select id="sync-modal-concurrency" class="regular-text" style="width: 100%;">
						<option value="5" selected><?php esc_html_e( 'Balanced (5 parallel - Recommended)', 'offload-plus' ); ?></option>
						<option value="20"><?php esc_html_e( 'Fast (20 parallel - More resources)', 'offload-plus' ); ?></option>
						<option value="40"><?php esc_html_e( 'Intensive (40 parallel - Maximum speed)', 'offload-plus' ); ?></option>
					</select>
					<p class="description" style="margin-top: 8px;">
						<?php esc_html_e( 'Balanced is recommended for most cases. Fast and Intensive require more server resources.', 'offload-plus' ); ?>
					</p>
				</div>
			</div>

			<!-- Progress Step -->
			<div id="sync-modal-progress" style="display: none; margin: 20px 0;">
				<!-- WARNING -->
				<div style="background: linear-gradient(135deg, #d63638 0%, #c62d30 100%); color: #fff; padding: 20px; border-radius: 6px; margin-bottom: 20px; text-align: center; box-shadow: 0 2px 8px rgba(214, 54, 56, 0.3); border: 2px solid #d63638;">
					<div style="font-size: 28px; font-weight: 700; margin-bottom: 8px; text-transform: uppercase; letter-spacing: 1px;">
						⚠️ <?php esc_html_e( 'DO NOT CLOSE THIS WINDOW', 'offload-plus' ); ?> ⚠️
					</div>
					<div style="font-size: 14px; font-weight: 500; opacity: 0.95;">
						<?php esc_html_e( 'Closing this window will cancel the synchronization', 'offload-plus' ); ?>
					</div>
				</div>

				<p style="text-align: center; font-weight: 600; margin: 15px 0;" id="sync-modal-progress-label"><?php esc_html_e( 'Syncing files...', 'offload-plus' ); ?></p>

				<div style="background: #f0f0f1; border-radius: 8px; overflow: hidden; margin: 15px 0;">
					<div id="sync-modal-progress-bar" style="height: 30px; background: linear-gradient(90deg, #0073aa 0%, #005177 100%); width: 0%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">
						<span id="sync-modal-progress-percent">0%</span>
					</div>
				</div>

				<p id="sync-modal-progress-text" style="text-align: center; margin: 10px 0; font-size: 14px; color: #666;">0 / 0 (0%)</p>

				<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-top: 20px;">
					<div style="background: #f0f0f1; padding: 12px; border-radius: 4px; text-align: center;">
						<div style="font-size: 12px; color: #666; margin-bottom: 4px;"><?php esc_html_e( 'Processed', 'offload-plus' ); ?></div>
						<div id="sync-modal-stats-processed" style="font-size: 18px; font-weight: 600;">0</div>
					</div>
					<div style="background: #d4edda; padding: 12px; border-radius: 4px; text-align: center;">
						<div style="font-size: 12px; color: #155724; margin-bottom: 4px;"><?php esc_html_e( 'Successful', 'offload-plus' ); ?></div>
						<div id="sync-modal-stats-successful" style="font-size: 18px; font-weight: 600; color: #155724;">0</div>
					</div>
					<div style="background: #f8d7da; padding: 12px; border-radius: 4px; text-align: center;">
						<div style="font-size: 12px; color: #721c24; margin-bottom: 4px;"><?php esc_html_e( 'Failed', 'offload-plus' ); ?></div>
						<div id="sync-modal-stats-failed" style="font-size: 18px; font-weight: 600; color: #721c24;">0</div>
					</div>
				</div>
			</div>

				<!-- Footer Buttons -->
				<div style="border-top: 1px solid #ddd; padding-top: 15px; margin-top: 20px; text-align: right;">
					<button id="sync-modal-cancel" class="button button-secondary" style="margin-right: 10px;">
						<?php esc_html_e( 'Cancel', 'offload-plus' ); ?>
					</button>
					<button id="sync-modal-start" class="button button-primary">
						<span class="dashicons dashicons-cloud-upload" style="margin-top: 3px;"></span>
						<span id="sync-modal-start-text"><?php esc_html_e( 'Start Sync', 'offload-plus' ); ?></span>
					</button>
				</div>
			</div>
			<!-- End #sync-modal-content -->

		</div>
	</div>

	<!-- ⭐ NEW: Dedicated Delete Local Files Modal -->
	<div id="delete-modal" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.7); z-index: 100000;">
		<div style="position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); background: #fff; padding: 30px; border-radius: 8px; max-width: 700px; width: 90%;">
			<h2 style="margin-top: 0; border-bottom: 1px solid #ddd; padding-bottom: 15px;">
				<span class="dashicons dashicons-trash" style="color: #d63638;"></span>
				<span><?php esc_html_e( 'Delete Local Files', 'offload-plus' ); ?></span>
			</h2>

			<!-- Loading State -->
			<div id="delete-modal-loading" style="margin: 20px 0; text-align: center; padding: 60px 30px;">
				<div class="spinner is-active" style="float: none; margin: 0 auto 15px;"></div>
				<p style="font-size: 15px;"><strong><?php esc_html_e( 'Calculating files to delete...', 'offload-plus' ); ?></strong></p>
				<p style="color: #666;"><?php esc_html_e( 'Scanning local storage. This may take a moment.', 'offload-plus' ); ?></p>
			</div>

			<!-- Initial Info -->
			<div id="delete-modal-info" style="display: none; margin: 20px 0;">
				<p style="margin: 0 0 15px 0; font-size: 14px;">
					<?php esc_html_e( 'This will permanently delete ALL files from local storage. Files will remain in your cloud provider and continue to be served from there.', 'offload-plus' ); ?>
				</p>

				<div style="background: #f0f0f1; padding: 15px; border-radius: 4px; margin: 15px 0;">
					<div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
						<span style="font-weight: 600;">🗑️ <?php esc_html_e( 'Files to delete:', 'offload-plus' ); ?></span>
						<span id="delete-modal-total-files">-</span>
					</div>
					<div style="display: flex; justify-content: space-between;">
						<span style="font-weight: 600;">💾 <?php esc_html_e( 'Space to free:', 'offload-plus' ); ?></span>
						<span id="delete-modal-total-size">-</span>
					</div>
				</div>
			</div>

			<!-- Progress -->
			<div id="delete-modal-progress" style="display: none; margin: 20px 0;">
				<div style="background: linear-gradient(135deg, #d63638 0%, #c62d30 100%); color: #fff; padding: 20px; border-radius: 6px; margin-bottom: 20px; text-align: center;">
					<div style="font-size: 28px; font-weight: 700; margin-bottom: 8px;">
						⚠️ <?php esc_html_e( 'DO NOT CLOSE THIS WINDOW', 'offload-plus' ); ?> ⚠️
					</div>
					<div style="font-size: 14px; font-weight: 500;">
						<?php esc_html_e( 'Closing will interrupt the deletion process', 'offload-plus' ); ?>
					</div>
				</div>

				<p style="text-align: center; font-weight: 600; margin: 15px 0;"><?php esc_html_e( 'Deleting local files...', 'offload-plus' ); ?></p>

				<div style="background: #f0f0f1; border-radius: 8px; overflow: hidden; margin: 15px 0;">
					<div id="delete-modal-progress-bar" style="height: 30px; background: linear-gradient(90deg, #d63638 0%, #f56e6e 100%); width: 0%; transition: width 0.3s; display: flex; align-items: center; justify-content: center; color: white; font-weight: 600;">
						<span id="delete-modal-progress-percent">0%</span>
					</div>
				</div>

				<p id="delete-modal-progress-text" style="text-align: center; margin: 10px 0; font-size: 14px; color: #666;">0 / 0 (0%)</p>

				<div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; margin-top: 20px;">
					<div style="background: #f0f0f1; padding: 12px; border-radius: 4px; text-align: center;">
						<div style="font-size: 12px; color: #666; margin-bottom: 4px;"><?php esc_html_e( 'Processed', 'offload-plus' ); ?></div>
						<div id="delete-modal-stats-processed" style="font-size: 18px; font-weight: 600;">0</div>
					</div>
					<div style="background: #d4edda; padding: 12px; border-radius: 4px; text-align: center;">
						<div style="font-size: 12px; color: #155724; margin-bottom: 4px;"><?php esc_html_e( 'Successful', 'offload-plus' ); ?></div>
						<div id="delete-modal-stats-successful" style="font-size: 18px; font-weight: 600; color: #155724;">0</div>
					</div>
					<div style="background: #f8d7da; padding: 12px; border-radius: 4px; text-align: center;">
						<div style="font-size: 12px; color: #721c24; margin-bottom: 4px;"><?php esc_html_e( 'Failed', 'offload-plus' ); ?></div>
						<div id="delete-modal-stats-failed" style="font-size: 18px; font-weight: 600; color: #721c24;">0</div>
					</div>
				</div>
			</div>

			<!-- Summary (completion) -->
			<div id="delete-modal-summary" style="display: none; margin: 20px 0;">
				<!-- Summary will be populated by JavaScript -->
			</div>

			<!-- Footer -->
			<div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd;">
				<button id="delete-modal-cancel" class="button"><?php esc_html_e( 'Cancel', 'offload-plus' ); ?></button>
				<button id="delete-modal-start" class="button button-primary" style="background: #d63638; border-color: #d63638;">
					<span class="dashicons dashicons-trash"></span>
					<span id="delete-modal-start-text"><?php esc_html_e( 'Start Delete', 'offload-plus' ); ?></span>
				</button>
			</div>
		</div>
	</div>

</div>

<style>
/* =========================================== */
/* OFFLOAD PLUS - SYNC & OFFLOADING UI */
/* =========================================== */

.offload-plus-sync-container {
	max-width: 100%;
}

/* Card Styles */
.offload-plus-sync-container .card {
	background: #fff;
	padding: 25px;
	border: 1px solid #ddd;
	border-radius: 6px;
	box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.offload-plus-sync-container .card h3 {
	font-size: 18px;
	font-weight: 600;
	color: #23282d;
}

/* Progress Bar Styles */
.progress-bar {
	width: 100%;
	height: 24px;
	background-color: #f0f0f1;
	border-radius: 4px;
	overflow: hidden;
	margin: 15px 0 10px 0;
}

.progress-fill {
	height: 100%;
	background: linear-gradient(90deg, #0073aa 0%, #005177 100%);
	transition: width 0.3s ease;
}

.progress-text {
	text-align: center;
	font-size: 14px;
	color: #666;
	margin-top: 8px;
}

/* Button Hero Styles */
.button-hero {
	height: auto !important;
	padding: 12px 24px !important;
	font-size: 16px !important;
	line-height: 1.5 !important;
}

/* Description Text Spacing */
.offload-plus-sync-container .description {
	line-height: 1.6 !important;
	margin-top: 8px !important;
}

/* Button Spacing */
.offload-plus-sync-container .button {
	margin-bottom: 0 !important;
}

.offload-plus-sync-container .button .dashicons {
	vertical-align: middle;
}

/* Ensure proper spacing between action sections */
.offload-plus-actions-card > div > div {
	margin-bottom: 0;
}

.offload-plus-actions-card > div > div + div {
	margin-top: 0;
}

/* Spinner Animation */
@keyframes spin {
	from { transform: rotate(0deg); }
	to { transform: rotate(360deg); }
}

.spin {
	animation: spin 1s linear infinite;
}

/* Status Icons */
.offload-plus-status-card .dashicons {
	display: inline-block;
}

/* Modal Improvements */
#failed-files-modal .wp-list-table code,
#failed-files-modal .wp-list-table td {
	word-break: break-word;
}

/* Responsive */
@media (max-width: 782px) {
	.offload-plus-sync-container .card {
		padding: 15px;
	}

	.button-hero {
		padding: 10px 20px !important;
		font-size: 14px !important;
	}
}

/* Legacy compatibility */
.sync-status-card, .sync-stats-card {
	background: #fff;
	border: 1px solid #ccd0d4;
	border-radius: 4px;
	padding: 20px;
	margin-bottom: 20px;
}

.sync-status-ready, .sync-status-progress, .sync-status-complete {
	display: flex;
	align-items: center;
	margin-bottom: 20px;
}

.sync-status-ready .dashicons,
.sync-status-progress .dashicons,
.sync-status-complete .dashicons {
	font-size: 24px;
	margin-right: 10px;
	color: #0073aa;
}

.sync-status-complete .dashicons {
	color: #46b450;
}

.sync-actions {
	margin-top: 15px;
}

.sync-actions button {
	margin-right: 10px;
}

.progress-bar {
	width: 100%;
	height: 20px;
	background-color: #e5e5e5;
	border-radius: 10px;
	overflow: hidden;
	margin: 10px 0;
}

.progress-fill {
	height: 100%;
	background-color: #0073aa;
	transition: width 0.3s ease;
}

.progress-text {
	text-align: center;
	font-size: 14px;
	color: #666;
}

.spin {
	animation: spin 1s linear infinite;
}

@keyframes spin {
	0% { transform: rotate(0deg); }
	100% { transform: rotate(360deg); }
}

/* Modal styles */
.offload-plus-modal {
	position: fixed;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	z-index: 100000;
}

.offload-plus-modal-overlay {
	position: absolute;
	top: 0;
	left: 0;
	right: 0;
	bottom: 0;
	background: rgba(0, 0, 0, 0.7);
}

.offload-plus-modal-content {
	position: absolute;
	top: 50%;
	left: 50%;
	transform: translate(-50%, -50%);
	background: #fff;
	border-radius: 8px;
	padding: 30px;
	max-width: 500px;
	width: 90%;
	box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
}

.offload-plus-modal-content h3 {
	margin-top: 0;
}
</style>

<script>
jQuery(document).ready(function($) {
	console.log('[Offload Plus] jQuery ready - admin-sync.php loaded');
	console.log('[Offload Plus] Enable offloading button exists:', $('#enable-offloading-btn').length);

	// ⭐ FIX: Use event delegation for Cancel button to work with dynamically created content
	$(document).on('click', '#sync-modal-cancel', function() {
		console.log('[Offload Plus Sync] Cancel button clicked');

		if ($('#sync-modal-progress').is(':visible')) {
			// Cancel ongoing sync
			isSyncCancelled = true;
			console.log('[Offload Plus Sync] Canceled by user');

			// Show cancelling message
			$('#sync-modal-progress-label').text('<?php esc_html_e( 'Cancelling sync...', 'offload-plus' ); ?>');
			$('#sync-modal-cancel').prop('disabled', true).css('opacity', '0.5');

			// ⭐ IMPORTANT: Only reset state to configured if NOT in download mode
			// In download mode (disconnect), we should stay in "synced" state
			if (currentSyncMode === 'download') {
				console.log('[Offload Plus Sync] Download cancelled - staying in synced state');
				// Just reload without changing state
				setTimeout(function() {
					location.reload();
				}, 500);
			} else {
				// Upload mode - reset state to configured
				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'offload_plus_reset_state_to_configured',
						nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin' ) ); ?>'
					},
					success: function(response) {
						console.log('[Offload Plus Sync] State reset to configured');
						// Reload page to show correct UI
						location.reload();
					},
					error: function() {
						console.error('[Offload Plus Sync] Failed to reset state');
						location.reload();
					}
				});
			}
		} else {
			$('#sync-modal').hide();
		}
	});

	// ⭐ Professional notification system (no alert popups)
	function showNotification(message, type = 'info') {
		const $notification = $('#offload-plus-notification');
		const colors = {
			'success': { bg: '#d4edda', border: '#46b450', color: '#155724' },
			'error': { bg: '#f8d7da', border: '#dc3545', color: '#721c24' },
			'warning': { bg: '#fff3cd', border: '#ffc107', color: '#856404' },
			'info': { bg: '#e7f3ff', border: '#0073aa', color: '#004085' }
		};

		const style = colors[type] || colors.info;
		$notification.css({
			'background-color': style.bg,
			'border-left-color': style.border,
			'color': style.color,
			'display': 'block'
		}).html(message);

		// Auto-hide success messages after 5 seconds
		if (type === 'success' || type === 'info') {
			setTimeout(() => $notification.fadeOut(), 5000);
		}
	}

	function hideNotification() {
		$('#offload-plus-notification').fadeOut();
	}

	// ══════════════════════════════════════════════════════════
	// ⭐ NEW: Recursion-based sync (Infinite Uploads style)
	// ══════════════════════════════════════════════════════════
	let isSyncCancelled = false;
	let retryCount = 0;
	const maxRetries = 6;

	// ══════════════════════════════════════════════════════════
	// ⭐ NEW: Unified Modal for Sync and Disconnect
	// ══════════════════════════════════════════════════════════
	let currentSyncMode = 'upload'; // 'upload' or 'download'

	// ⭐ NEW: Multi-tab coordination
	// Generate unique session ID for this tab (persists across page refresh)
	let tabSessionId = sessionStorage.getItem('offload_plus_tab_session_id');
	if (!tabSessionId) {
		tabSessionId = 'sync_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
		sessionStorage.setItem('offload_plus_tab_session_id', tabSessionId);
	}
	let currentSyncState = 'no_sync'; // 'active', 'inactive', 'terminated', 'no_sync'
	let stateCheckInterval = null;
	let activePollingInterval = null;

	console.log('[Offload Plus Multi-Tab] Tab session ID:', tabSessionId);

	// ⭐ Pass PHP state to JavaScript
	const pluginState = '<?php echo esc_js( $current_state ); ?>';

	// ⭐ NEW: Check on page load if there's an active sync in another tab
	function checkInitialSyncState() {
		console.log('[Offload Plus Multi-Tab] Checking for existing sync on page load...');

		// Track if we opened the modal (so we can close it later)
		let initialCheckOpenedModal = false;

		// Show loading spinner in modal if state is syncing
		if (pluginState === 'syncing') {
			showLoadingState();
			initialCheckOpenedModal = true;
		}

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'offload_plus_get_sync_state',
				nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin' ) ); ?>',
				session_id: tabSessionId
			},
			success: function(response) {
				if (response.success) {
					const data = response.data;
					const state = data.state;

					console.log('[Offload Plus Multi-Tab] Initial state check:', state);

					if (state === 'active') {
						// ⭐ FIX: This tab is active (could be after refresh with sessionStorage)
						console.log('[Offload Plus Multi-Tab] This tab is ACTIVE - resuming sync...');
						currentSyncState = 'active';

						// ⭐ FIX: Request current progress FIRST, keep loading spinner until received
						updateProgressFromServer(function() {
							// Callback: Progress received, now show progress modal
							$('#sync-container').hide();
							$('#sync-modal-content').show(); // ⭐ FIX PROBLEMA 3: Show main content container
							$('#sync-modal-progress').show();
							$('#sync-modal').show(); // Keep modal visible

							// Resume processing batches
							processSyncBatch();
						});
					} else if (state === 'inactive') {
						// Another tab is running the sync
						console.log('[Offload Plus Multi-Tab] Detected sync in another tab');
						currentSyncState = 'inactive';
						showInactiveTabUI(data.sync_meta);
						startStateMonitoring();
					} else if (state === 'terminated') {
						// Sync just finished
						console.log('[Offload Plus Multi-Tab] Detected terminated sync');
						currentSyncState = 'terminated';
						// Only hide modal if WE opened it
						if (initialCheckOpenedModal) {
							hideLoadingState();
						}
						// Could show completion screen
					} else {
						// No sync active
						console.log('[Offload Plus Multi-Tab] No active sync detected');
						currentSyncState = 'no_sync';
						// Only hide modal if WE opened it
						if (initialCheckOpenedModal) {
							hideLoadingState();
						}
					}
				}
			},
			error: function(xhr, status, error) {
				// Only hide modal if WE opened it
				if (initialCheckOpenedModal) {
					hideLoadingState();
				}
				console.error('[Offload Plus Multi-Tab] Error checking initial state:', error);
			}
		});
	}

	// Show loading state in modal (same as sync calculation)
	function showLoadingState(title = 'Analyzing Sync Status', message = 'Checking for active synchronization...') {
		$('#sync-modal').show();
		$('#sync-modal-content').hide();
		$('#sync-modal-summary').hide();
		$('#sync-modal-progress').hide();
		$('#sync-modal-start').hide();
		$('#sync-modal-config').hide();

		const loadingHtml = '<div id="offload-plus-loading-spinner" style="text-align: center; padding: 60px 20px;">' +
			'<div class="spinner is-active" style="float: none; margin: 0 auto 20px; width: 40px; height: 40px;"></div>' +
			'<h3 style="margin: 0 0 12px 0; color: #2271b1; font-size: 20px; font-weight: 600;">' + title + '</h3>' +
			'<p style="color: #666; font-size: 15px; margin: 0;">' + message + '</p>' +
			'</div>';

		// ⭐ #sync-container now exists in HTML (line 537), no need to create it
		$('#sync-container').html(loadingHtml).show();
	}

	// Hide loading state modal
	function hideLoadingState() {
		$('#sync-modal').hide();
		$('#sync-container').empty();
	}

	// Show notification message
	function showNotice(message, type = 'info') {
		const colors = {
			'success': { bg: '#d4edda', border: '#46b450', text: '#155724' },
			'error': { bg: '#f8d7da', border: '#dc3232', text: '#721c24' },
			'warning': { bg: '#fff3cd', border: '#f0b849', text: '#856404' },
			'info': { bg: '#d1ecf1', border: '#0073aa', text: '#0c5460' }
		};

		const color = colors[type] || colors['info'];

		const $notice = $('<div class="offload-plus-notice" style="margin: 15px 0; padding: 12px 15px; border-radius: 4px; border-left: 4px solid ' + color.border + '; background: ' + color.bg + '; color: ' + color.text + ';">' +
			message +
			'</div>');

		$('#offload-plus-notification').html($notice).show();

		// Auto-hide after 5 seconds
		setTimeout(function() {
			$('#offload-plus-notification').fadeOut();
		}, 5000);
	}

	// Run initial check on page load (always, for multi-tab coordination)
	// The spinner inside checkInitialSyncState() will only show if pluginState === 'syncing'
	checkInitialSyncState();

	// ⭐ REFACTORED: Start sync process with DOUBLE VALIDATION
	// Called when user clicks Start/Continue/Retry sync buttons
	function startSyncProcess(fromScratch, retryFailed) {
		retryFailed = retryFailed || false;

		console.log('[Offload Plus Sync] START PROCESS - fromScratch:', fromScratch, 'retryFailed:', retryFailed);

		// Show loading state with unified look & feel
		showLoadingState('Validating Action', 'Calculating files to sync...');

		// ⭐ PRIMERA LLAMADA: Pre-check + calculate (confirmed=0)
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'offload_plus_start_sync',
				nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin' ) ); ?>',
				session_id: tabSessionId,
				confirmed: 0, // ⭐ Pre-check
				retry_failed: retryFailed ? 1 : 0
			},
			success: function(response) {
				console.log('[Offload Plus Sync] Pre-check AJAX response:', response);

				if (!response.success) {
					$('#sync-modal').hide();
					showNotice('Error: ' + (response.data || 'Unknown error'), 'error');
					return;
				}

				// ⭐ Check 1: Validación falló?
				if (response.data.validation_failed) {
					console.warn('[Offload Plus Sync] Validation FAILED on pre-check:', response.data.reason);
					handleValidationError(response.data.reason, response.data.details);
					return;
				}

				// ⭐ Check 2: Requiere confirmación?
				if (response.data.requires_confirmation) {
					console.log('[Offload Plus Sync] Pre-check PASSED, showing options modal');
					console.log('[Offload Plus Sync] Data received:', response.data.data);
					// Mostrar modal con opciones (Continue/From Scratch)
					showSyncOptionsModal(response.data.data, fromScratch, retryFailed);
					return;
				}

				// No debería llegar aquí
				console.error('[Offload Plus Sync] Unexpected response:', response);
				$('#sync-modal').hide();
				showNotice('Unexpected response from server', 'error');
			},
			error: function(xhr, status, error) {
				console.error('[Offload Plus Sync] AJAX error on pre-check:', error);
				$('#sync-modal').hide();
				showNotice('Connection error. Please try again.', 'error');
			}
		});
	}

	// ⭐ NEW: Show sync options modal (Continue/From Scratch)
	function showSyncOptionsModal(data, fromScratch, retryFailed) {
		console.log('[Offload Plus Sync] showSyncOptionsModal called with data:', data);

		// ⭐ FIX: Si pending=0 y synced>0, skip modal y mostrar directamente pantalla de Enable Offloading
		// El usuario ya sabe que todo está sincronizado, no tiene sentido mostrar opciones de upload
		if (data.pending_files === 0 && data.synced_files > 0) {
			console.log('[Offload Plus Sync] All files already synced (pending=0). Skipping to completion screen with Enable Offloading.');

			// Preparar modal para mostrar resultado
			$('#sync-modal').show();
			$('#sync-container').hide();
			$('#sync-modal-content').show();
			$('#sync-modal-summary').hide();
			$('#sync-modal-config').hide();
			$('#sync-modal-progress').hide();
			$('#sync-modal-start').hide();

			// Llamar directamente a onSyncComplete con datos del pre-check
			onSyncComplete({
				status: 'completed',
				total_files: data.synced_files,
				successful_uploads: data.synced_files,
				failed_uploads: 0,
				processed_files: data.synced_files
			});
			return;
		}

		var summaryHtml = '<div class="sync-summary" style="position: relative;">';

		// Close button (X) at top-right
		summaryHtml += '<button id="close-sync-options-btn" style="position: absolute; top: -10px; right: -10px; background: #d63638; color: white; border: none; border-radius: 50%; width: 30px; height: 30px; cursor: pointer; font-size: 18px; line-height: 1; padding: 0; display: flex; align-items: center; justify-content: center; box-shadow: 0 2px 4px rgba(0,0,0,0.2);" title="<?php esc_attr_e( 'Close', 'offload-plus' ); ?>">&times;</button>';

		summaryHtml += '<h3 style="margin: 0 0 15px 0;">☁️ <?php esc_html_e( 'Upload Summary', 'offload-plus' ); ?></h3>';
		summaryHtml += '<div class="summary-stats" style="background: #f5f5f5; padding: 15px; border-radius: 5px; margin-bottom: 20px;">';
		summaryHtml += '<div style="margin-bottom: 8px;">';
		summaryHtml += '<strong><?php esc_html_e( 'Total files:', 'offload-plus' ); ?></strong> ' + data.total_files.toLocaleString() + ' (' + data.total_size_formatted + ')';
		summaryHtml += '</div>';
		summaryHtml += '<div style="margin-bottom: 8px; color: #0a0;">';
		summaryHtml += '<strong><?php esc_html_e( 'Already uploaded:', 'offload-plus' ); ?></strong> ' + data.synced_files.toLocaleString() + ' (' + data.synced_size_formatted + ') ✅';
		summaryHtml += '</div>';

		if (data.new_files > 0) {
			summaryHtml += '<div style="margin-bottom: 8px; color: #f90;">';
			summaryHtml += '<strong><?php esc_html_e( 'New files:', 'offload-plus' ); ?></strong> ' + data.new_files.toLocaleString() + ' (' + data.new_files_size_formatted + ') 💛';
			summaryHtml += '</div>';
		}

		if (data.pending_files > 0) {
			summaryHtml += '<div style="color: #c60;">';
			summaryHtml += '<strong><?php esc_html_e( 'Pending:', 'offload-plus' ); ?></strong> ' + data.pending_files.toLocaleString() + ' (' + data.pending_size_formatted + ') 🎈';
			summaryHtml += '</div>';
		}

		summaryHtml += '</div>';

		// Performance selector
		summaryHtml += '<div style="margin: 20px 0; padding: 15px; background: #e7f3ff; border-left: 4px solid #2196f3; border-radius: 4px;">';
		summaryHtml += '<label for="upload-concurrency-select" style="display: block; margin-bottom: 10px; font-weight: 600;">';
		summaryHtml += '⚡ <?php esc_html_e( 'Upload Performance:', 'offload-plus' ); ?>';
		summaryHtml += '</label>';
		summaryHtml += '<select id="upload-concurrency-select" class="regular-text" style="width: 100%; padding: 8px;">';
		summaryHtml += '<option value="5" selected><?php esc_html_e( 'Balanced (5 parallel)', 'offload-plus' ); ?></option>';
		summaryHtml += '<option value="20"><?php esc_html_e( 'Fast (20 parallel)', 'offload-plus' ); ?></option>';
		summaryHtml += '<option value="40"><?php esc_html_e( 'Intensive (40 parallel)', 'offload-plus' ); ?></option>';
		summaryHtml += '</select>';
		summaryHtml += '</div>';

		// Buttons
		var continueDisabled = (data.synced_files === 0 || data.pending_files === 0);
		summaryHtml += '<div class="button-group" style="display: flex; gap: 15px; margin-top: 20px;">';

		if (!continueDisabled) {
			// Case 1: Has pending files - show Continue Upload
			summaryHtml += '<button id="continue-upload-btn" class="button button-primary button-large" style="flex: 1; padding: 15px;">';
			summaryHtml += '<span class="dashicons dashicons-controls-play"></span> <?php esc_html_e( 'Continue Upload', 'offload-plus' ); ?>';
			summaryHtml += '</button>';

			summaryHtml += '<button id="scratch-upload-btn" class="button button-secondary button-large" style="flex: 1; padding: 15px;">';
			summaryHtml += '<span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Upload from Scratch', 'offload-plus' ); ?>';
			summaryHtml += '</button>';
		} else if (data.pending_files === 0 && data.synced_files > 0) {
			// Case 2: All synced (pending=0) - show Complete Sync button
			summaryHtml += '<button id="continue-upload-btn" class="button button-primary button-large" style="flex: 1; padding: 15px; background: #46b450; border-color: #46b450;">';
			summaryHtml += '<span class="dashicons dashicons-yes-alt"></span> <?php esc_html_e( 'Scan and Complete Sync', 'offload-plus' ); ?>';
			summaryHtml += '</button>';

			summaryHtml += '<button id="scratch-upload-btn" class="button button-secondary button-large" style="flex: 1; padding: 15px;">';
			summaryHtml += '<span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Upload from Scratch', 'offload-plus' ); ?>';
			summaryHtml += '</button>';
		} else {
			// Case 3: Starting fresh (synced=0) - show Upload from Scratch only
			summaryHtml += '<button id="scratch-upload-btn" class="button button-primary button-large" style="flex: 1; padding: 15px;">';
			summaryHtml += '<span class="dashicons dashicons-update"></span> <?php esc_html_e( 'Upload from Scratch', 'offload-plus' ); ?>';
			summaryHtml += '</button>';
		}

		summaryHtml += '</div>';
		summaryHtml += '</div>';

		// ⭐ CRITICAL: Ensure modal and container are visible
		$('#sync-modal').show();
		$('#sync-container').html(summaryHtml).show();
		$('#sync-modal-content').hide();
		$('#sync-modal-summary').hide();
		$('#sync-modal-config').hide();
		$('#sync-modal-progress').hide();

		console.log('[Offload Plus Sync] Modal content set, modal should be visible now');

		// Attach handlers
		$('#continue-upload-btn').off('click').on('click', function() {
			executeSyncConfirmed(false, retryFailed); // Continue mode
		});

		$('#scratch-upload-btn').off('click').on('click', function() {
			executeSyncConfirmed(true, retryFailed); // From scratch
		});

		$('#close-sync-options-btn').off('click').on('click', function() {
			$('#sync-modal').hide();
			$('#sync-container').empty();
		});
	}

	// ⭐ NEW: Execute sync after user confirmation (SEGUNDA LLAMADA)
	function executeSyncConfirmed(fromScratch, retryFailed) {
		const concurrency = parseInt($('#upload-concurrency-select').val()) || 5;

		console.log('[Offload Plus Sync] User confirmed - fromScratch:', fromScratch, 'concurrency:', concurrency);

		// Show progress modal
		$('#sync-modal-content').show();
		$('#sync-container').hide();
		$('#sync-modal-summary').hide();
		$('#sync-modal-config').hide();
		$('#sync-modal-progress').show();
		$('#sync-modal-start').hide();

		// Reset state
		isSyncCancelled = false;
		retryCount = 0;
		currentSyncState = 'active';

		// Reset progress UI
		$('#sync-modal-progress-bar').css('width', '0%');
		$('#sync-modal-progress-text').text('0 / 0 (0%)');
		$('#sync-modal-progress-percent').text('0%');
		$('#sync-modal-stats-processed').text('0');
		$('#sync-modal-stats-successful').text('0');
		$('#sync-modal-stats-failed').text('0');

		// ⭐ SEGUNDA LLAMADA: Execution-check + execute (confirmed=1)
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'offload_plus_start_sync',
				nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin' ) ); ?>',
				session_id: tabSessionId,
				confirmed: 1, // ⭐ Execution-check
				concurrency: concurrency,
				from_scratch: fromScratch ? 1 : 0,
				retry_failed: retryFailed ? 1 : 0
			},
			success: function(response) {
				if (!response.success) {
					$('#sync-modal').hide();
					showNotice('Error: ' + (response.data || 'Unknown error'), 'error');
					return;
				}

				// ⭐ IMPORTANTE: Validar OTRA VEZ (execution-check puede fallar si otro tab inició sync)
				if (response.data.validation_failed) {
					console.warn('[Offload Plus Sync] Validation FAILED on execution-check:', response.data.reason);
					handleValidationError(response.data.reason, response.data.details);
					return;
				}

				// ⭐ Acción ejecutada exitosamente
				if (response.data.action_executed) {
					console.log('[Offload Plus Sync] Sync started successfully, processing batches...');
					processSyncBatch();
				} else {
					console.error('[Offload Plus Sync] Unexpected response:', response);
					$('#sync-modal').hide();
					showNotice('Unexpected response from server', 'error');
				}
			},
			error: function(xhr, status, error) {
				console.error('[Offload Plus Sync] AJAX error on execution:', error);
				$('#sync-modal').hide();
				showNotice('Connection error. Please try again.', 'error');
			}
		});
	}

	// ⭐ NEW: Unified validation error handler
	function handleValidationError(reason, details) {
		console.log('[Offload Plus Validation] Handling error:', reason);

		switch(reason) {
			case 'sync_active_in_another_tab':
				console.log('[Offload Plus Validation] Another tab is active, showing Continue Here modal');
				showInactiveTabUI(details.sync_meta);
				startStateMonitoring();
				break;

			case 'sync_already_active':
				console.warn('[Offload Plus Validation] Sync already active');
				$('#sync-modal').hide();
				showNotice('Sync is already active. Please wait or refresh the page.', 'warning');
				setTimeout(() => location.reload(), 2000);
				break;

			case 'state_conflict':
				console.warn('[Offload Plus Validation] Plugin state conflict');
				$('#sync-modal').hide();
				showNotice('Plugin state conflict. Refreshing page...', 'warning');
				setTimeout(() => location.reload(), 1000);
				break;

			case 'files_not_synced':
				const stats = details;
				const failedCount = stats.failed_count || 0;
				const pendingCount = stats.pending_count || 0;
				let errorMsg = 'Cannot proceed: ';
				if (failedCount > 0) errorMsg += failedCount + ' failed files';
				if (failedCount > 0 && pendingCount > 0) errorMsg += ' and ';
				if (pendingCount > 0) errorMsg += pendingCount + ' pending files';
				errorMsg += '. Please resolve errors first.';

				$('#sync-modal').hide();
				showNotice(errorMsg, 'error');
				break;

			default:
				console.error('[Offload Plus Validation] Unknown error:', reason);
				$('#sync-modal').hide();
				showNotice('Operation not allowed: ' + reason, 'error');
		}
	}

	// ⭐ REFACTORED: Start/Continue Sync button - usa nuevo flujo con validación doble
	$('#start-sync-btn').on('click', function() {
		currentSyncMode = 'upload';

		// Configure modal for upload
		$('#sync-modal-icon').removeClass('dashicons-download').addClass('dashicons-cloud-upload');
		$('#sync-modal-title-text').text('<?php esc_html_e( 'Sync Files to Cloud', 'offload-plus' ); ?>');

		// ⭐ NEW: Call unified startSyncProcess (with double validation)
		startSyncProcess(false, false); // fromScratch=false, retryFailed=false
	});

	// ⭐ OLD CODE REMOVED - The following 100+ lines were replaced by startSyncProcess()
	// This old code was calling offload_plus_calculate_sync directly and building the modal manually
	// Now everything goes through the new double-validation flow

	// ⭐ RECURSION: Process batch and immediately call next
	function processSyncBatch() {
		if (isSyncCancelled) {
			console.log('[Offload Plus Sync] Cancelled by user');
			return;
		}

		// ⭐ Check if this tab still owns the sync before processing
		if (currentSyncState !== 'active') {
			console.log('[Offload Plus Multi-Tab] Not active, stopping process_batch recursion');
			return;
		}

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'offload_plus_process_batch',
				nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin' ) ); ?>',
				session_id: tabSessionId // ⭐ NEW: Send tab session ID
			},
			success: function(response) {
				retryCount = 0; // Reset error counter on success

				if (response.success) {
					const data = response.data;

					// ⭐ NEW: Check if session was lost
					if (data.status === 'session_lost') {
						console.log('[Offload Plus Multi-Tab] Lost control of sync to another tab');
						currentSyncState = 'inactive';

						// Start monitoring state instead of processing
						startStateMonitoring();
						return;
					}

					// Update UI
					updateSyncProgress(data);

					// ⭐ Check if completed
					if (data.status === 'completed') {
						onSyncComplete(data);
					} else {
						// ⭐ IMMEDIATE RECURSION (no delay)
						processSyncBatch();
					}
				} else {
					// ⭐ FIXED: Handle different error response formats
					const errorMsg = response.data?.message || response.data || 'Unknown error';
					console.error('[Offload Plus Sync] Batch error:', errorMsg);
					showNotification('<?php esc_html_e( 'Error processing batch:', 'offload-plus' ); ?> ' + errorMsg, 'error');
				}
			},
			error: function(xhr, status, error) {
				// ⭐ EXPONENTIAL BACKOFF on errors
				retryCount++;

				if (retryCount > maxRetries) {
					showNotification('<?php esc_html_e( '⚠️ Max retries exceeded. Sync stopped. Please check logs and try again.', 'offload-plus' ); ?>', 'error');
					console.error('[Offload Plus Sync] Max retries exceeded');
					return;
				}

				// Calculate exponential backoff delay
				const backoff = Math.floor(Math.pow(retryCount, 2.5) * 1000);
				// Retry 1: 1s, 2: 5.6s, 3: 15.5s, 4: 37s, 5: 78s, 6: 156s

				console.warn('[Offload Plus Sync] Error (attempt ' + retryCount + '/' + maxRetries + '). Retrying in ' + (backoff/1000) + 's...');
				console.error('[Offload Plus Sync] Error details:', status, error);

				// Show warning in UI
				$('#sync-status-message').text('⚠️ Connection error. Retrying in ' + (backoff/1000) + 's... (attempt ' + retryCount + '/' + maxRetries + ')').show();

				setTimeout(function() {
					$('#sync-status-message').hide();
					processSyncBatch(); // Retry
				}, backoff);
			}
		});
	}

	// ⭐ NEW: Multi-tab state monitoring
	function startStateMonitoring() {
		console.log('[Offload Plus Multi-Tab] Starting state monitoring (polling every 5 seconds)');

		// Clear any existing interval
		if (stateCheckInterval) {
			clearInterval(stateCheckInterval);
		}

		// Poll state every 5 seconds
		stateCheckInterval = setInterval(checkSyncState, 5000);

		// Check immediately
		checkSyncState();
	}

	function stopStateMonitoring() {
		if (stateCheckInterval) {
			clearInterval(stateCheckInterval);
			stateCheckInterval = null;
		}
	}

	function checkSyncState() {
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'offload_plus_get_sync_state',
				nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin' ) ); ?>',
				session_id: tabSessionId
			},
			success: function(response) {
				if (response.success) {
					const data = response.data;
					const state = data.state;

					console.log('[Offload Plus Multi-Tab] State check:', state);

					if (state === 'no_sync') {
						// No sync active anymore (cancelled or error)
						currentSyncState = 'no_sync';
						stopStateMonitoring();
						$('#sync-modal').hide();

						// ⭐ FIX: Reload page to show updated state (CONFIGURED)
						console.log('[Offload Plus Multi-Tab] Sync cancelled/stopped, reloading page...');
						setTimeout(function() {
							location.reload();
						}, 1000);
					} else if (state === 'expired') {
						// ⭐ NEW: Session expired due to inactivity (timeout)
						currentSyncState = 'no_sync';
						stopStateMonitoring();
						$('#sync-modal').hide();

						showNotice('Sync session expired due to inactivity. The page will reload...', 'warning');
						console.log('[Offload Plus Multi-Tab] Session expired, reloading page...');

						// Reload page after 2 seconds
						setTimeout(function() {
							location.reload();
						}, 2000);
					} else if (state === 'terminated') {
						// Sync completed
						currentSyncState = 'terminated';
						stopStateMonitoring();
						onSyncComplete(data.sync_meta);
					} else if (state === 'active') {
						// This tab regained control somehow (shouldn't happen normally)
						currentSyncState = 'active';
						stopStateMonitoring();
						processSyncBatch();
					} else if (state === 'inactive') {
						// Another tab is still active - show "Continue Here" UI
						showInactiveTabUI(data.sync_meta);
					}
				}
			},
			error: function(xhr, status, error) {
				console.error('[Offload Plus Multi-Tab] Error checking state:', error);
			}
		});
	}

	function showInactiveTabUI(syncMeta) {
		// Show modal with "Continue Here" button
		$('#sync-modal').show();
		$('#sync-modal-content').hide(); // ⭐ FIX PROBLEMA 1: Hide main content to avoid duplication
		$('#sync-modal-progress').hide();
		$('#sync-modal-start').hide();
		$('#sync-modal-config').hide();

		// Create inactive tab UI
		const percentage = syncMeta.percentage || 0;
		const processed = syncMeta.processed_files || 0;
		const total = syncMeta.total_files || 0;

		let inactiveHtml = '<div style="padding: 30px; text-align: center;">';
		inactiveHtml += '<div style="font-size: 48px; margin-bottom: 15px;">⏸️</div>';
		inactiveHtml += '<h3 style="margin: 0 0 10px 0; color: #856404;">Sync Active in Another Tab</h3>';
		inactiveHtml += '<p style="color: #666; margin-bottom: 20px;">Another browser tab is currently processing the sync.</p>';

		// Progress info
		inactiveHtml += '<div style="background: #f5f5f5; padding: 15px; border-radius: 5px; margin-bottom: 20px;">';
		inactiveHtml += '<div style="margin-bottom: 8px;"><strong>Progress:</strong> ' + processed + ' / ' + total + ' files (' + percentage.toFixed(1) + '%)</div>';
		inactiveHtml += '</div>';

		inactiveHtml += '<button id="continue-here-btn" class="button button-primary button-large" style="padding: 15px 30px; font-size: 14px;">';
		inactiveHtml += '<span class="dashicons dashicons-controls-play" style="margin-right: 5px;"></span>';
		inactiveHtml += 'Continue Here';
		inactiveHtml += '</button>';

		inactiveHtml += '<p class="description" style="margin-top: 15px; color: #666;">This will move the sync to this tab.</p>';
		inactiveHtml += '</div>';

		// ⭐ #sync-container now exists in HTML (line 537), no need to create it
		$('#sync-container').html(inactiveHtml).show();

		// Attach event handler
		$('#continue-here-btn').on('click', function() {
			takeControl();
		});
	}

	function takeControl() {
		console.log('[Offload Plus Multi-Tab] Taking control of sync...');

		// ⭐ FIX: Show "Transferring control..." loading state
		const transferringHtml = '<div style="padding: 40px; text-align: center;">' +
			'<div class="spinner is-active" style="float: none; margin: 0 auto 20px; width: 40px; height: 40px;"></div>' +
			'<h3 style="margin: 0 0 12px 0; color: #2271b1; font-size: 20px; font-weight: 600;">Transferring Control</h3>' +
			'<p style="color: #666; font-size: 15px; margin: 0;">Taking over synchronization from other tab...</p>' +
			'</div>';

		$('#sync-container').html(transferringHtml).show();
		$('#sync-modal-content').hide();

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'offload_plus_take_control',
				nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin' ) ); ?>',
				session_id: tabSessionId
			},
			success: function(response) {
				if (response.success) {
					console.log('[Offload Plus Multi-Tab] Control taken successfully');

					// Set this tab as active
					currentSyncState = 'active';

					// Stop state monitoring
					stopStateMonitoring();

					// ⭐ FIX: Request current progress FIRST, then hide transferring UI
					updateProgressFromServer(function() {
						// Callback: Progress received, now show modal
						$('#sync-container').hide();
						$('#sync-modal-content').show(); // ⭐ FIX PROBLEMA 2: Show main content container
						$('#sync-modal-progress').show();

						// Start processing batches
						processSyncBatch();
					});
				} else {
					alert('Failed to take control: ' + (response.data || 'Unknown error'));
					// Restore inactive UI
					$('#sync-container').empty();
					showInactiveTabUI(response.data.sync_meta || {});
				}
			},
			error: function(xhr, status, error) {
				alert('Connection error while taking control');
				console.error('[Offload Plus Multi-Tab] Take control error:', error);
				// Restore inactive UI
				$('#sync-container').empty();
			}
		});
	}

	// ⭐ FIX PROBLEMA 5: Request current progress from server (lightweight, no processing)
	function updateProgressFromServer(callback) {
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'offload_plus_get_sync_state',
				nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin' ) ); ?>',
				session_id: tabSessionId
			},
			success: function(response) {
				if (response.success && response.data.sync_meta) {
					// Update UI with current progress
					const syncMeta = response.data.sync_meta;
					updateSyncProgress({
						percentage: syncMeta.percentage || 0,
						processed_files: syncMeta.processed_files || 0,
						total_files: syncMeta.total_files || 0,
						successful_uploads: syncMeta.successful_uploads || 0,
						failed_uploads: syncMeta.failed_uploads || 0
					});
					console.log('[Offload Plus Multi-Tab] Progress updated from server');
				}
				// Call callback when done (success or no data)
				if (callback) callback();
			},
			error: function(xhr, status, error) {
				console.error('[Offload Plus Multi-Tab] Error fetching progress:', error);
				// Call callback even on error
				if (callback) callback();
			}
		});
	}

	function showSyncProgress() {
		// Hide start button, show progress
		$('#start-sync-btn').hide();
		$('#cancel-sync-btn').show();

		// Show progress container
		$('#sync-progress-container').show();

		// ⭐ SHOW CRITICAL WARNING
		$('#sync-warning-banner').slideDown(300);
	}
	
	function updateSyncProgress(data) {
		const percentage = data.percentage || 0;
		const processed = data.processed_files || 0;
		const total = data.total_files || 0;
		const uploaded = data.uploaded_this_batch || 0;
		const successful = data.successful_uploads || processed;
		const failed = data.failed_uploads || 0;

		// Update old progress bar (if visible)
		$('#sync-progress-bar').css('width', percentage + '%');
		$('#sync-progress-text').text(processed + ' / ' + total + ' files (' + percentage.toFixed(1) + '%)');

		// Update modal progress
		$('#sync-modal-progress-bar').css('width', percentage + '%');
		$('#sync-modal-progress-text').text(processed.toLocaleString() + ' / ' + total.toLocaleString() + ' files');
		$('#sync-modal-progress-percent').text(Math.round(percentage) + '%');
		$('#sync-modal-stats-processed').text(processed.toLocaleString());
		$('#sync-modal-stats-successful').text(successful.toLocaleString());
		$('#sync-modal-stats-failed').text(failed.toLocaleString());

		// Update batch info
		if (uploaded > 0) {
			$('#batch-info').text('Last batch: ' + uploaded + ' files uploaded').show();
		}

		// Log progress for debugging
		console.log('[Offload Plus Sync] Progress: ' + processed + '/' + total + ' (' + percentage.toFixed(1) + '%)');
	}
	
	function onSyncComplete(data) {
		// ⭐ HIDE WARNING BANNER
		$('#sync-warning-banner').slideUp(300);

		// Hide progress and title, show completion summary
		$('#sync-modal-progress').hide();
		$('#sync-modal-content').hide(); // ⭐ Hide entire content container (including title)
		$('#sync-container').hide(); // ⭐ FIX: Hide "Sync Active in Another Tab" content from inactive tab

		const processed = data.processed_files || 0;
		const total = data.total_files || 0;
		const successful = data.successful_uploads || 0;
		const failed = data.failed_uploads || 0;

		// ⭐ DEBUG: Log data to understand what's happening
		console.log('[Offload Plus Sync Complete] Data received:', data);
		console.log('[Offload Plus Sync Complete] Status:', data.status, 'Total:', total, 'Successful:', successful, 'Failed:', failed);

		// Build completion summary
		let summaryHtml = '<div class="sync-summary" style="text-align: center; padding: 20px;">';

		// ⭐ FIXED LOGIC: Consider successful if we have successful uploads OR if total equals successful
		const isSuccess = (data.status === 'completed' && failed === 0) || (successful > 0 && failed === 0) || (total > 0 && successful === total);

		if (isSuccess) {
			// ✅ All successful
			summaryHtml += '<div style="font-size: 64px; margin-bottom: 20px;">✅</div>';
			summaryHtml += '<h3 style="color: #46b450; margin: 0 0 10px 0;"><?php esc_html_e( 'Sync Completed Successfully!', 'offload-plus' ); ?></h3>';
			summaryHtml += '<p style="font-size: 16px; color: #666; margin: 10px 0;">';
			summaryHtml += '<?php esc_html_e( 'All files have been synced to cloud storage.', 'offload-plus' ); ?>';
			summaryHtml += '</p>';
		} else if (failed > 0) {
			// ⚠️ Some failures
			summaryHtml += '<div style="font-size: 64px; margin-bottom: 20px;">⚠️</div>';
			summaryHtml += '<h3 style="color: #f0b849; margin: 0 0 10px 0;"><?php esc_html_e( 'Sync Completed with Errors', 'offload-plus' ); ?></h3>';
			summaryHtml += '<p style="font-size: 16px; color: #666; margin: 10px 0;">';
			summaryHtml += '<?php esc_html_e( 'Some files could not be synced.', 'offload-plus' ); ?>';
			summaryHtml += '</p>';
		} else {
			// ❌ Failed completely (no successful uploads and no clear completion status)
			summaryHtml += '<div style="font-size: 64px; margin-bottom: 20px;">❌</div>';
			summaryHtml += '<h3 style="color: #d63638; margin: 0 0 10px 0;"><?php esc_html_e( 'Sync Failed', 'offload-plus' ); ?></h3>';
			summaryHtml += '<p style="font-size: 16px; color: #666; margin: 10px 0;">';
			summaryHtml += (data.message || '<?php esc_html_e( 'Unknown error', 'offload-plus' ); ?>');
			summaryHtml += '</p>';
		}

		// Stats
		summaryHtml += '<div style="background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: left;">';
		summaryHtml += '<div style="margin-bottom: 10px;"><strong><?php esc_html_e( 'Total files:', 'offload-plus' ); ?></strong> ' + total.toLocaleString() + '</div>';
		summaryHtml += '<div style="margin-bottom: 10px; color: #46b450;"><strong><?php esc_html_e( 'Successful:', 'offload-plus' ); ?></strong> ' + successful.toLocaleString() + '</div>';
		if (failed > 0) {
			summaryHtml += '<div style="color: #d63638;"><strong><?php esc_html_e( 'Failed:', 'offload-plus' ); ?></strong> ' + failed.toLocaleString() + '</div>';
		}
		summaryHtml += '</div>';

		// ⭐ Action buttons - different based on result
		summaryHtml += '<div style="margin-top: 20px; display: flex; gap: 10px; justify-content: center;">';

		if (isSuccess) {
			// ✅ SUCCESS: Offer to enable offloading or continue later
			summaryHtml += '<button id="enable-offloading-btn" class="button button-primary" style="padding: 10px 30px; font-size: 16px;">';
			summaryHtml += '<?php esc_html_e( 'Enable Offloading', 'offload-plus' ); ?>';
			summaryHtml += '</button>';
			summaryHtml += '<button id="later-btn" class="button button-secondary" style="padding: 10px 30px; font-size: 16px;">';
			summaryHtml += '<?php esc_html_e( 'Later', 'offload-plus' ); ?>';
			summaryHtml += '</button>';
		} else if (failed > 0) {
			// ⚠️ WITH ERRORS: Just accept and reload
			summaryHtml += '<button id="accept-errors-btn" class="button button-primary" style="padding: 10px 30px; font-size: 16px;">';
			summaryHtml += '<?php esc_html_e( 'Accept', 'offload-plus' ); ?>';
			summaryHtml += '</button>';
		} else {
			// ❌ FAILED: Just close
			summaryHtml += '<button id="sync-complete-close-btn" class="button button-primary" style="padding: 10px 30px; font-size: 16px;">';
			summaryHtml += '<?php esc_html_e( 'Close', 'offload-plus' ); ?>';
			summaryHtml += '</button>';
		}

		summaryHtml += '</div>';
		summaryHtml += '</div>';

		$('#sync-modal-summary').html(summaryHtml).show();

		// ⭐ Mark as completed on server (sets state to SYNCED) in background
		if (data.status === 'completed') {
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'offload_plus_mark_sync_complete',
					nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin' ) ); ?>'
				}
			});
		}
	}

	// ═══════════════════════════════════════════════════════
	// ⭐ GLOBAL Button Handlers - Must be at document.ready level
	// ═══════════════════════════════════════════════════════

	// "Enable Offloading" button - Works for BOTH modal and static page buttons
	$(document).on('click', '#enable-offloading-btn', function(e) {
		e.preventDefault();
		e.stopPropagation();
		const $btn = $(this);

		// ⭐ VALIDATION: Check for failed files before enabling offloading
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'offload_plus_get_failed_files_count',
				nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin' ) ); ?>'
			},
			success: function(validationResponse) {
				if (validationResponse.success) {
					const failedCount = validationResponse.data.failed_count || 0;
					const pendingCount = validationResponse.data.pending_count || 0;

					// Block if there are failed or pending files
					if (failedCount > 0 || pendingCount > 0) {
						let errorMsg = '<?php esc_html_e( 'Cannot enable offloading: ', 'offload-plus' ); ?>';
						if (failedCount > 0) {
							errorMsg += failedCount + ' <?php esc_html_e( 'failed files', 'offload-plus' ); ?>';
							if (pendingCount > 0) errorMsg += ' <?php esc_html_e( 'and', 'offload-plus' ); ?> ';
						}
						if (pendingCount > 0) {
							errorMsg += pendingCount + ' <?php esc_html_e( 'pending files', 'offload-plus' ); ?>';
						}
						errorMsg += '. <?php esc_html_e( 'Please resolve errors first using "Clear Failed & Enable" or retry failed files.', 'offload-plus' ); ?>';

						showNotification(errorMsg, 'error');
						return;
					}

					// Validation passed, proceed with offloading
					proceedWithOffloading($btn);
				} else {
					// Validation endpoint failed, proceed anyway (fail-open)
					console.warn('[Offload Plus] Failed files validation failed, proceeding anyway');
					proceedWithOffloading($btn);
				}
			},
			error: function() {
				// Network error, proceed anyway (fail-open)
				console.warn('[Offload Plus] Failed files validation error, proceeding anyway');
				proceedWithOffloading($btn);
			}
		});
	});

	// ⭐ Helper function to activate offloading (extracted for reuse)
	function proceedWithOffloading($btn) {
		// Check if confirmation is needed (static page button has data-confirm="true")
		if ($btn.data('confirm') === true) {
			// Show custom notification instead of ugly browser confirm
			showNotification('<?php esc_html_e( 'Enabling cloud storage offloading...', 'offload-plus' ); ?>', 'info');
		}

		// Disable button and show loading state
		$btn.prop('disabled', true);
		const originalHtml = $btn.html();
		$btn.html('<?php esc_html_e( 'Enabling...', 'offload-plus' ); ?>');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'offload_plus_activate_offloading',
				nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin_nonce' ) ); ?>'
			},
			success: function(response) {
				if (response.success) {
					showNotification('✅ <?php esc_html_e( 'Offloading enabled successfully!', 'offload-plus' ); ?>', 'success');
					setTimeout(() => window.location.reload(), 1000);
				} else {
					showNotification('Error: ' + (response.data || 'Unknown error'), 'error');
					$btn.prop('disabled', false).html(originalHtml);
				}
			},
			error: function(xhr, status, error) {
				showNotification('<?php esc_html_e( 'Connection error', 'offload-plus' ); ?>', 'error');
				$btn.prop('disabled', false).html(originalHtml);
			}
		});
	}

	// "Later" button (modal)
	$(document).on('click', '#later-btn', function() {
		window.location.reload();
	});

	// "Accept" button (modal with errors)
	$(document).on('click', '#accept-errors-btn', function() {
		window.location.reload();
	});

	// Close button (modal failure)
	$(document).on('click', '#sync-complete-close-btn', function() {
		window.location.reload();
	});

	$('#cancel-sync-btn').on('click', function() {
		// ⭐ Stop recursion immediately
		isSyncCancelled = true;

		const button = $(this);
		button.prop('disabled', true).text('<?php esc_html_e( 'Cancelling...', 'offload-plus' ); ?>');

		// ⭐ HIDE WARNING BANNER
		$('#sync-warning-banner').slideUp(300);

		console.log('[Offload Plus Sync] Cancelling sync...');
		showNotification('<?php esc_html_e( 'Cancelling sync... Please wait.', 'offload-plus' ); ?>', 'warning');

		// ⭐ FIXED: Call server to properly cancel and reset state
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'offload_plus_cancel_sync',
				nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin' ) ); ?>'
			},
			success: function(response) {
				console.log('[Offload Plus Sync] Cancel response:', response);
				showNotification('<?php esc_html_e( 'Sync cancelled. Refreshing...', 'offload-plus' ); ?>', 'info');
				setTimeout(() => window.location.reload(), 1000);
			},
			error: function() {
				// Even on error, refresh to show correct state
				showNotification('<?php esc_html_e( 'Sync cancelled. Refreshing...', 'offload-plus' ); ?>', 'info');
				setTimeout(() => window.location.reload(), 1000);
			}
		});
	});
	
	// ⭐ NEW: Retry failed files button - Opens modal like regular sync
	$(document).on('click', '.retry-failed-btn', function() {
		const button = $(this);
		button.prop('disabled', true);

		// Show loading modal
		$('#sync-modal-content').hide();
		$('#sync-modal-summary').html('<div style="text-align: center; padding: 40px;"><span class="spinner is-active" style="float: none; margin: 0 auto;"></span><p style="margin-top: 20px;">Calculating failed files...</p></div>');
		$('#sync-modal').show();

		// Get stats for failed files only
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'offload_plus_calculate_sync',
				retry_failed: 1, // ⭐ NEW: Only count failed files
				nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin' ) ); ?>'
			},
			success: function(response) {
				button.prop('disabled', false);

				if (response.success && response.data) {
					const data = response.data;

					// Build retry summary
					var summaryHtml = '<div class="sync-summary">';
					summaryHtml += '<h3 style="margin: 0 0 15px 0;">🔄 <?php esc_html_e( 'Retry Failed Files', 'offload-plus' ); ?></h3>';
					summaryHtml += '<div class="summary-stats" style="background: #fff3cd; padding: 15px; border-radius: 5px; margin-bottom: 20px; border-left: 4px solid #ffc107;">';

					// ⭐ Calculate total files to retry (old failed + new files found)
					var total_to_retry = data.pending_files + data.new_files;
					var total_size_to_retry = data.pending_size + data.new_files_size;

					summaryHtml += '<div style="margin-bottom: 8px; color: #856404;">';
					// ⭐ Create a helper function to format size in JavaScript
					function formatSize(bytes) {
						if (bytes === 0) return '0 B';
						var k = 1024;
						var sizes = ['B', 'KB', 'MB', 'GB', 'TB'];
						var i = Math.floor(Math.log(bytes) / Math.log(k));
						return Math.round((bytes / Math.pow(k, i)) * 100) / 100 + ' ' + sizes[i];
					}
					summaryHtml += '<strong><?php esc_html_e( 'Failed files to retry:', 'offload-plus' ); ?></strong> ' + total_to_retry.toLocaleString() + ' (' + formatSize(total_size_to_retry) + ')';
					summaryHtml += '</div>';

					// ⭐ Show breakdown if there are new files
					if (data.new_files > 0) {
						summaryHtml += '<div style="font-size: 13px; color: #666; margin-top: 8px; padding-top: 8px; border-top: 1px solid #e0e0e0;">';
						summaryHtml += '├ <?php esc_html_e( 'Previously failed:', 'offload-plus' ); ?> ' + data.pending_files.toLocaleString() + ' (' + data.pending_size_formatted + ')';
						summaryHtml += '<br>';
						summaryHtml += '└ <?php esc_html_e( 'New files found:', 'offload-plus' ); ?> ' + data.new_files.toLocaleString() + ' (' + data.new_files_size_formatted + ')';
						summaryHtml += '</div>';
					}

					summaryHtml += '</div>';

					// Performance Level Selector
					summaryHtml += '<div style="margin: 20px 0; padding: 15px; background: #e7f3ff; border-left: 4px solid #2196f3; border-radius: 4px;">';
					summaryHtml += '<label for="retry-concurrency-select" style="display: block; margin-bottom: 10px; font-weight: 600; color: #333;">';
					summaryHtml += '⚡ <?php esc_html_e( 'Performance Level:', 'offload-plus' ); ?>';
					summaryHtml += '</label>';
					summaryHtml += '<select id="retry-concurrency-select" class="regular-text" style="width: 100%; padding: 8px;">';
					summaryHtml += '<option value="5" selected><?php esc_html_e( 'Balanced (5 parallel - Recommended)', 'offload-plus' ); ?></option>';
					summaryHtml += '<option value="20"><?php esc_html_e( 'Fast (20 parallel - More resources)', 'offload-plus' ); ?></option>';
					summaryHtml += '<option value="40"><?php esc_html_e( 'Intensive (40 parallel - Maximum speed)', 'offload-plus' ); ?></option>';
					summaryHtml += '</select>';
					summaryHtml += '<p class="description" style="margin-top: 8px; font-size: 12px; color: #666;">';
					summaryHtml += '<?php esc_html_e( 'Higher values = faster upload but more server resources. Start with Balanced if unsure.', 'offload-plus' ); ?>';
					summaryHtml += '</p>';
					summaryHtml += '</div>';

					// Action buttons
					summaryHtml += '<div style="margin-top: 20px; display: flex; gap: 10px;">';
					summaryHtml += '<button id="retry-upload-btn" class="button button-primary" style="flex: 1;">';
					summaryHtml += '🔄 <?php esc_html_e( 'Retry Upload', 'offload-plus' ); ?>';
					summaryHtml += '</button>';
					summaryHtml += '<button id="sync-modal-cancel" class="button" style="flex: 0;"><?php esc_html_e( 'Cancel', 'offload-plus' ); ?></button>';
					summaryHtml += '</div>';
					summaryHtml += '</div>';

					$('#sync-modal-summary').html(summaryHtml);

					// Retry upload button handler
					$('#retry-upload-btn').on('click', function() {
						startSyncProcess(false, true); // fromScratch=false, retryFailed=true
					});
				} else {
					$('#sync-modal').hide();
					showNotification('<?php esc_html_e( 'Error calculating failed files', 'offload-plus' ); ?>', 'error');
				}
			},
			error: function() {
				button.prop('disabled', false);
				$('#sync-modal').hide();
				showNotification('<?php esc_html_e( 'Connection error', 'offload-plus' ); ?>', 'error');
			}
		});
	});


	// ⭐ NEW: Resync all files - Shows confirmation modal first
	$(document).on('click', '.resync-all-btn', function() {
		// Hide content modal, show summary
		$('#sync-modal-content').hide();

		// Show confirmation modal with improved UX/UI
		var confirmHtml = '<div class="sync-summary" style="padding: 20px;">';

		// Icon and title
		confirmHtml += '<div style="text-align: center; margin-bottom: 20px;">';
		confirmHtml += '<div style="font-size: 64px; margin-bottom: 15px;">⚠️</div>';
		confirmHtml += '<h2 style="margin: 0 0 10px 0; font-size: 24px; color: #d63638;"><?php esc_html_e( 'Confirm Complete Resync', 'offload-plus' ); ?></h2>';
		confirmHtml += '</div>';

		// Warning message
		confirmHtml += '<div style="background: #fff3cd; padding: 20px; border-radius: 6px; margin-bottom: 25px; border-left: 4px solid #f0b849;">';
		confirmHtml += '<p style="margin: 0 0 15px 0; color: #856404; line-height: 1.6; font-size: 15px;">';
		confirmHtml += '<strong><?php esc_html_e( 'Are you sure you want to resynchronize all files?', 'offload-plus' ); ?></strong>';
		confirmHtml += '</p>';
		confirmHtml += '<ul style="margin: 15px 0; padding-left: 20px; color: #856404; line-height: 1.8;">';
		confirmHtml += '<li><?php esc_html_e( 'All sync history will be cleared', 'offload-plus' ); ?></li>';
		confirmHtml += '<li><?php esc_html_e( 'Files will be scanned from scratch', 'offload-plus' ); ?></li>';
		confirmHtml += '<li><?php esc_html_e( 'Already synced files will be detected and skipped', 'offload-plus' ); ?></li>';
		confirmHtml += '</ul>';
		confirmHtml += '<p style="margin: 15px 0 0 0; color: #721c24; font-weight: 600; background: #f8d7da; padding: 12px; border-radius: 4px; border-left: 4px solid #d63638;">';
		confirmHtml += '⚠️ <?php esc_html_e( 'This action cannot be undone.', 'offload-plus' ); ?>';
		confirmHtml += '</p>';
		confirmHtml += '</div>';

		// Action buttons
		confirmHtml += '<div style="margin-top: 25px; display: flex; gap: 10px; justify-content: center;">';
		confirmHtml += '<button id="resync-cancel-btn" class="button button-secondary" style="padding: 10px 30px; font-size: 15px;"><?php esc_html_e( 'Cancel', 'offload-plus' ); ?></button>';
		confirmHtml += '<button id="resync-confirm-btn" class="button button-primary" style="padding: 10px 30px; font-size: 15px; background: #d63638; border-color: #d63638;"><?php esc_html_e( 'Yes, Resync All Files', 'offload-plus' ); ?></button>';
		confirmHtml += '</div>';
		confirmHtml += '</div>';

		$('#sync-modal-summary').html(confirmHtml);
		$('#sync-modal').show();

		// Cancel button - close modal
		$('#resync-cancel-btn').on('click', function() {
			$('#sync-modal').hide();
		});

		// Confirm button - proceed with resync
		$('#resync-confirm-btn').on('click', function() {
			const $btn = $(this);
			$btn.prop('disabled', true).text('<?php esc_html_e( 'Processing...', 'offload-plus' ); ?>');

			// Show loading state
			$('#sync-modal-summary').html('<div style="text-align: center; padding: 40px;"><span class="spinner is-active" style="float: none; margin: 0 auto;"></span><p style="margin-top: 20px;"><?php esc_html_e( 'Clearing sync data...', 'offload-plus' ); ?></p></div>');

			// Call backend to clear table and set state to CONFIGURED
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'offload_plus_prepare_resync',
					nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin' ) ); ?>'
				},
				success: function(response) {
					if (response.success) {
						// Show success message
						$('#sync-modal-summary').html('<div style="text-align: center; padding: 40px;"><div style="font-size: 64px; margin-bottom: 20px;">✅</div><h3 style="color: #46b450; margin: 0 0 15px 0;"><?php esc_html_e( 'Sync Data Cleared', 'offload-plus' ); ?></h3><p style="color: #666;"><?php esc_html_e( 'Reloading page...', 'offload-plus' ); ?></p></div>');

						// Reload page after 1 second to show CONFIGURED state with "Start Sync" button
						setTimeout(function() {
							location.reload();
						}, 1000);
					} else {
						$('#sync-modal').hide();
						showNotification('<?php esc_html_e( 'Error preparing resync', 'offload-plus' ); ?>', 'error');
					}
				},
				error: function() {
					$('#sync-modal').hide();
					showNotification('<?php esc_html_e( 'Connection error', 'offload-plus' ); ?>', 'error');
				}
			});
		});
	});

	// View failed files button
	$(document).on('click', '.view-failed-btn', function() {
		$('#failed-files-modal').fadeIn(200);
	});

	// Close failed files modal - ONLY via close button (no backdrop click)
	$('#close-failed-modal').on('click', function(e) {
		$('#failed-files-modal').fadeOut(200);
	});

	// Clear failed files list
	$(document).on('click', '.clear-failed-btn', function() {
		if (confirm('<?php esc_html_e( 'Are you sure you want to clear the failed files list?', 'offload-plus' ); ?>')) {
			const button = $(this);
			button.prop('disabled', true).text('<?php esc_html_e( 'Clearing...', 'offload-plus' ); ?>');

			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'offload_plus_clear_failed',
					nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin' ) ); ?>'
				},
				success: function(response) {
					if (response.success) {
						alert(response.data);
						window.location.reload();
					} else {
						alert('Error: ' + response.data);
						button.prop('disabled', false).text('<?php esc_html_e( 'Clear List', 'offload-plus' ); ?>');
					}
				},
				error: function() {
					alert('<?php esc_html_e( 'Connection error', 'offload-plus' ); ?>');
					button.prop('disabled', false).text('<?php esc_html_e( 'Clear List', 'offload-plus' ); ?>');
				}
			});
		}
	});

	// ⭐ "Clear Failed & Enable" button - Open modal
	console.log('[Offload Plus] Registering click handler for #discard-and-enable-static-btn');
	$(document).on('click', '#discard-and-enable-static-btn', function(e) {
		e.preventDefault();
		e.stopPropagation();
		console.log('[Offload Plus] Clear and Enable button clicked - opening modal');
		$('#clear-and-enable-modal').show();
	});

	// Close Clear & Enable modal - ONLY via close button, NOT backdrop
	$('.close-clear-enable-modal').on('click', function() {
		$('#clear-and-enable-modal').hide();
		// Reset modal to initial view
		$('#clear-enable-confirm-view').show();
		$('#clear-enable-processing-view').hide();
		$('#clear-enable-success-view').hide();
		$('#clear-enable-error-view').hide();
	});

	// Confirm Clear & Enable action - ALL IN MODAL
	$('#confirm-clear-and-enable').on('click', function() {
		console.log('[Offload Plus] User confirmed clear and enable');

		// Switch to processing view (stay in modal)
		$('#clear-enable-confirm-view').hide();
		$('#clear-enable-processing-view').show();

		// First discard failed files
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'offload_plus_discard_failed_files',
				nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin' ) ); ?>'
			},
			success: function(response) {
				if (response.success) {
					console.log('[Offload Plus] Failed files discarded: ' + response.data.deleted_count);

					// Then enable offloading
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'offload_plus_activate_offloading',
							nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin_nonce' ) ); ?>'
						},
						success: function(offloadingResponse) {
							if (offloadingResponse.success) {
								// Show success view in modal
								$('#clear-enable-processing-view').hide();
								$('#clear-enable-success-view').show();

								// Reload page after 2.5 seconds
								setTimeout(function() {
									window.location.reload();
								}, 2500);
							} else {
								// Show error view in modal
								$('#clear-enable-processing-view').hide();
								$('#clear-enable-error-message').text('<?php esc_html_e( 'Files discarded but failed to enable offloading', 'offload-plus' ); ?>');
								$('#clear-enable-error-view').show();
							}
						},
						error: function() {
							// Show error view in modal
							$('#clear-enable-processing-view').hide();
							$('#clear-enable-error-message').text('<?php esc_html_e( 'Connection error while enabling offloading', 'offload-plus' ); ?>');
							$('#clear-enable-error-view').show();
						}
					});
				} else {
					// Show error view in modal
					$('#clear-enable-processing-view').hide();
					$('#clear-enable-error-message').text('<?php esc_html_e( 'Failed to discard files', 'offload-plus' ); ?>');
					$('#clear-enable-error-view').show();
				}
			},
			error: function() {
				// Show error view in modal
				$('#clear-enable-processing-view').hide();
				$('#clear-enable-error-message').text('<?php esc_html_e( 'Connection error', 'offload-plus' ); ?>');
				$('#clear-enable-error-view').show();
			}
		});
	});

	// ⭐ "Cancel Sync & Reset" button - Open modal
	console.log('[Offload Plus] Registering click handler for #cancel-all-sync-btn');
	$(document).on('click', '#cancel-all-sync-btn', function(e) {
		e.preventDefault();
		e.stopPropagation();
		console.log('[Offload Plus] Cancel Sync & Reset button clicked - checking if this tab has control...');

		// ⭐ Show loading state immediately for better UX
		showLoadingState('Validating Action', 'Checking sync status...');

		// ⭐ NEW: Verify this tab has control BEFORE allowing cancel
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'offload_plus_get_sync_state',
				nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin' ) ); ?>',
				session_id: tabSessionId
			},
			success: function(response) {
				// Hide loading state first
				hideLoadingState();
				if (response.success) {
					const state = response.data.state;

					if (state === 'inactive') {
						// Another tab has control - show inactive tab UI instead
						console.log('[Offload Plus] Cannot cancel from inactive tab - showing "Continue Here" modal');
						showInactiveTabUI(response.data.sync_meta);
						startStateMonitoring();
						return;
					}

					// This tab has control or no sync active - safe to show cancel modal
					console.log('[Offload Plus] Tab has control, showing cancel modal...');
					$('#cancel-sync-modal').show();
				} else {
					console.error('[Offload Plus] Error checking sync state:', response);
					showNotice('Error checking sync state. Please refresh the page.', 'error');
				}
			},
			error: function(xhr, status, error) {
				// Hide loading state on error
				hideLoadingState();
				console.error('[Offload Plus] AJAX error checking sync state:', error);
				showNotice('Connection error. Please refresh the page.', 'error');
			}
		});
	});

	// Close Cancel Sync modal - ONLY via close button, NOT backdrop
	$('.close-cancel-sync-modal').on('click', function() {
		$('#cancel-sync-modal').hide();
	});

	// Confirm Cancel Sync action
	$('#confirm-cancel-sync').on('click', function() {
		console.log('[Offload Plus] User confirmed cancel sync');

		// ⭐ Show "Resetting..." state inside the modal (better UX)
		showLoadingState('Resetting Sync', 'Clearing sync data and resetting state...');

		// Call cancel_sync AJAX to clear DB and reset state
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'offload_plus_cancel_sync',
				nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin' ) ); ?>',
				session_id: tabSessionId // ⭐ Include session_id for validation
			},
			success: function(response) {
				// Hide the confirmation modal
				$('#cancel-sync-modal').hide();

				// ⭐ Check if validation failed
				if (!response.success && response.data && response.data.validation_failed) {
					console.error('[Offload Plus] Reset blocked by validation:', response.data.reason);

					// Show error in main modal
					const errorHtml = '<div style="text-align: center; padding: 60px 20px;">' +
						'<div style="font-size: 60px; color: #dc3232; margin-bottom: 20px;">⚠️</div>' +
						'<h3 style="margin: 0 0 12px 0; color: #dc3232; font-size: 20px; font-weight: 600;">Cannot Reset</h3>' +
						'<p style="color: #666; font-size: 15px; margin: 0 0 20px 0;">' + (response.data.message || 'Another tab is currently syncing') + '</p>' +
						'<button class="button button-primary" onclick="jQuery(\'#sync-modal\').hide(); location.reload();">Refresh Page</button>' +
						'</div>';

					$('#sync-container').html(errorHtml).show();
					return;
				}

				if (response.success) {
					console.log('[Offload Plus] Sync cancelled successfully');

					// Show success message in the main modal
					const successHtml = '<div style="text-align: center; padding: 60px 20px;">' +
						'<div style="font-size: 60px; color: #46b450; margin-bottom: 20px;">✓</div>' +
						'<h3 style="margin: 0 0 12px 0; color: #46b450; font-size: 20px; font-weight: 600;">Success!</h3>' +
						'<p style="color: #666; font-size: 15px; margin: 0;">' + (response.data.message || '<?php esc_html_e( 'Sync cancelled and reset to configured state', 'offload-plus' ); ?>') + '</p>' +
						'<p style="color: #999; font-size: 13px; margin-top: 15px;">Refreshing page...</p>' +
						'</div>';

					$('#sync-container').html(successHtml).show();

					// Reload page after 1.5 seconds
					setTimeout(function() {
						window.location.reload();
					}, 1500);
				} else {
					console.error('[Offload Plus] Failed to cancel sync:', response);

					// Show error in main modal
					const errorHtml = '<div style="text-align: center; padding: 60px 20px;">' +
						'<div style="font-size: 60px; color: #dc3232; margin-bottom: 20px;">✗</div>' +
						'<h3 style="margin: 0 0 12px 0; color: #dc3232; font-size: 20px; font-weight: 600;">Error</h3>' +
						'<p style="color: #666; font-size: 15px; margin: 0 0 20px 0;"><?php esc_html_e( 'Failed to cancel sync', 'offload-plus' ); ?>: ' + (response.data.message || response.data || 'Unknown error') + '</p>' +
						'<button class="button button-primary" onclick="jQuery(\'#sync-modal\').hide();">Close</button>' +
						'</div>';

					$('#sync-container').html(errorHtml).show();
				}
			},
			error: function(xhr, status, error) {
				// Hide the confirmation modal
				$('#cancel-sync-modal').hide();

				console.error('[Offload Plus] AJAX error cancelling sync:', error);

				// Show error in main modal
				const errorHtml = '<div style="text-align: center; padding: 60px 20px;">' +
					'<div style="font-size: 60px; color: #dc3232; margin-bottom: 20px;">✗</div>' +
					'<h3 style="margin: 0 0 12px 0; color: #dc3232; font-size: 20px; font-weight: 600;">Connection Error</h3>' +
					'<p style="color: #666; font-size: 15px; margin: 0 0 20px 0;"><?php esc_html_e( 'Connection error while cancelling sync', 'offload-plus' ); ?></p>' +
					'<button class="button button-primary" onclick="jQuery(\'#sync-modal\').hide();">Close</button>' +
					'</div>';

				$('#sync-container').html(errorHtml).show();
			}
		});
	});

	// ══════════════════════════════════════════════════════════
	// ⭐ NEW: Delete Local Files (Dedicated Modal)
	// ══════════════════════════════════════════════════════════
	var isDeleteCancelled = false;
	var totalFilesToDelete = 0;
	var deletedFilesCount = 0;
	var failedFilesCount = 0;

	$('#delete-local-files-btn').on('click', function() {
		// Show modal with loading state
		$('#delete-modal-loading').show();
		$('#delete-modal-info').hide();
		$('#delete-modal-progress').hide();
		$('#delete-modal-start').hide();
		$('#delete-modal-summary').hide();
		$('#delete-modal-cancel').show(); // Reset Cancel button visibility
		$('#delete-modal').show();

		// Fetch deletable files stats
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'offload_plus_get_deletable_stats',
				nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin' ) ); ?>'
			},
			success: function(response) {
				// Hide loading, show info
				$('#delete-modal-loading').hide();
				$('#delete-modal-info').show();
				$('#delete-modal-start').show();

				if (response.success && response.data) {
					$('#delete-modal-total-files').text((response.data.files || 0).toLocaleString());
					$('#delete-modal-total-size').text(response.data.size_formatted || '0 B');
					$('#delete-modal-start').prop('disabled', false);
					totalFilesToDelete = response.data.files || 0;
				} else {
					$('#delete-modal-total-files').text('0');
					$('#delete-modal-total-size').text('0 B');
					$('#delete-modal-start').prop('disabled', false);
				}
			},
			error: function() {
				$('#delete-modal-loading').hide();
				$('#delete-modal-info').show();
				$('#delete-modal-start').show();
				$('#delete-modal-total-files').text('Error');
				$('#delete-modal-total-size').text('Error');
				$('#delete-modal-start').prop('disabled', false);
			}
		});
	});

	// Handle delete modal cancel
	$('#delete-modal-cancel').on('click', function() {
		if ($('#delete-modal-progress').is(':visible')) {
			isDeleteCancelled = true;
		}
		$('#delete-modal').hide();
	});

	// Handle delete modal start
	$('#delete-modal-start').on('click', function() {
		// Hide info, show progress
		$('#delete-modal-info').hide();
		$('#delete-modal-progress').show();
		$('#delete-modal-start').hide();

		// Reset state
		isDeleteCancelled = false;
		deletedFilesCount = 0;
		failedFilesCount = 0;

		// Reset progress
		$('#delete-modal-progress-bar').css('width', '0%');
		$('#delete-modal-progress-text').text('0 / ' + totalFilesToDelete + ' (0%)');
		$('#delete-modal-progress-percent').text('0%');
		$('#delete-modal-stats-processed').text('0');
		$('#delete-modal-stats-successful').text('0');
		$('#delete-modal-stats-failed').text('0');

		console.log('[Offload Plus Delete] Starting deletion of ' + totalFilesToDelete + ' files');
		processDeleteBatch();
	});

	// Process delete batch (recursion)
	function processDeleteBatch() {
		if (isDeleteCancelled) {
			console.log('[Offload Plus Delete] Cancelled by user');
			return;
		}

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'offload_plus_process_delete_batch',
				nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin' ) ); ?>'
			},
			success: function(response) {
				if (!response.success) {
					alert('Error: ' + (response.data || 'Unknown error'));
					$('#delete-modal').hide();
					return;
				}

				const data = response.data;

				// Update stats
				deletedFilesCount += data.deleted_this_batch || 0;
				failedFilesCount += data.failed_this_batch || 0;

				const processedTotal = deletedFilesCount + failedFilesCount;
				const percentage = totalFilesToDelete > 0 ? Math.round((processedTotal / totalFilesToDelete) * 100) : 0;

				// Update UI
				$('#delete-modal-progress-bar').css('width', percentage + '%');
				$('#delete-modal-progress-percent').text(percentage + '%');
				$('#delete-modal-progress-text').text(processedTotal + ' / ' + totalFilesToDelete + ' (' + percentage + '%)');
				$('#delete-modal-stats-processed').text(processedTotal.toLocaleString());
				$('#delete-modal-stats-successful').text(deletedFilesCount.toLocaleString());
				$('#delete-modal-stats-failed').text(failedFilesCount.toLocaleString());

				console.log('[Offload Plus Delete] Batch processed: +' + data.deleted_this_batch + ' deleted, ' + data.pending_files + ' remaining');

				if (data.status === 'completed') {
					// Done! Show summary
					console.log('[Offload Plus Delete] Completed! Total deleted: ' + deletedFilesCount);
					onDeleteComplete(deletedFilesCount, failedFilesCount);
				} else {
					// ⚡ Continue immediately (backend handles timing)
					processDeleteBatch();
				}
			},
			error: function(xhr, status, error) {
				console.error('[Offload Plus Delete] Error:', error);
				alert('<?php esc_html_e( 'Connection error. Deletion interrupted.', 'offload-plus' ); ?>');
				$('#delete-modal').hide();
			}
		});
	}

	// ⭐ Delete completion handler
	function onDeleteComplete(successful, failed) {
		// Hide progress
		$('#delete-modal-progress').hide();

		const total = successful + failed;

		// Build completion summary
		let summaryHtml = '<div style="text-align: center; padding: 20px;">';

		if (failed === 0) {
			// ✅ All successful
			summaryHtml += '<div style="font-size: 64px; margin-bottom: 20px;">✅</div>';
			summaryHtml += '<h3 style="color: #46b450; margin: 0 0 10px 0;"><?php esc_html_e( 'Deletion Completed Successfully!', 'offload-plus' ); ?></h3>';
			summaryHtml += '<p style="font-size: 16px; color: #666; margin: 10px 0;">';
			summaryHtml += '<?php esc_html_e( 'All local files have been deleted.', 'offload-plus' ); ?>';
			summaryHtml += '</p>';
		} else {
			// ⚠️ Some failures
			summaryHtml += '<div style="font-size: 64px; margin-bottom: 20px;">⚠️</div>';
			summaryHtml += '<h3 style="color: #f0b849; margin: 0 0 10px 0;"><?php esc_html_e( 'Deletion Completed with Errors', 'offload-plus' ); ?></h3>';
			summaryHtml += '<p style="font-size: 16px; color: #666; margin: 10px 0;">';
			summaryHtml += '<?php esc_html_e( 'Some files could not be deleted.', 'offload-plus' ); ?>';
			summaryHtml += '</p>';
		}

		// Stats
		summaryHtml += '<div style="background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: left;">';
		summaryHtml += '<div style="margin-bottom: 10px;"><strong><?php esc_html_e( 'Total files:', 'offload-plus' ); ?></strong> ' + total.toLocaleString() + '</div>';
		summaryHtml += '<div style="margin-bottom: 10px; color: #46b450;"><strong><?php esc_html_e( 'Deleted:', 'offload-plus' ); ?></strong> ' + successful.toLocaleString() + '</div>';
		if (failed > 0) {
			summaryHtml += '<div style="color: #d63638;"><strong><?php esc_html_e( 'Failed:', 'offload-plus' ); ?></strong> ' + failed.toLocaleString() + '</div>';
		}
		summaryHtml += '</div>';

		// Accept button
		summaryHtml += '<div style="margin-top: 20px;">';
		summaryHtml += '<button id="delete-accept-btn" class="button button-primary" style="padding: 10px 30px; font-size: 16px;">';
		summaryHtml += '<?php esc_html_e( 'Accept', 'offload-plus' ); ?>';
		summaryHtml += '</button>';
		summaryHtml += '</div>';
		summaryHtml += '</div>';

		$('#delete-modal-summary').html(summaryHtml).show();

		// Hide Cancel button when showing completion summary
		$('#delete-modal-cancel').hide();

		// Accept button handler
		$(document).on('click', '#delete-accept-btn', function() {
			window.location.reload();
		});
	}

	// ══════════════════════════════════════════════════════════
	// ⭐ Enable Offloading Button (Static page version with confirm dialog)
	// Note: Modal version (without confirm) is handled above with delegated event
	// This one is only for the button on the static SYNCED state page
	// ══════════════════════════════════════════════════════════
	// REMOVED: Duplicate handler - now using single delegated handler above (line 1154)

	// ══════════════════════════════════════════════════════════
	// ⭐ NEW: Disconnect from Cloud Provider (Modern Modal)
	// ══════════════════════════════════════════════════════════
	$('#disconnect-from-cloud-btn').on('click', function() {
		// Show modern disconnect modal
		$('#disconnect-modal').show();
	});

	// Close Disconnect modal
	$('.close-disconnect-modal').on('click', function() {
		$('#disconnect-modal').hide();
		// Reset modal to initial view
		$('#disconnect-confirm-view').show();
		$('#disconnect-scanning-view').hide();
		$('#disconnect-options-view').hide();
		$('#disconnect-progress-view').hide();
		$('#disconnect-success-view').hide();
		$('#disconnect-error-view').hide();
	});

	// Confirm Disconnect - Start scanning
	$('#confirm-disconnect').on('click', function() {
		currentSyncMode = 'download';

		// Switch to scanning view
		$('#disconnect-confirm-view').hide();
		$('#disconnect-scanning-view').show();

		// ⭐ STEP 1: Scan remote files first

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'offload_plus_scan_remote',
				nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin' ) ); ?>'
			},
			success: function(scanResponse) {
				if (scanResponse.success) {
					console.log('[Offload Plus] Remote scan complete:', scanResponse.data);

					// ⭐ STEP 2: Calculate download requirements from DB
					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'offload_plus_calculate_download',
							nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin' ) ); ?>'
						},
						success: function(response) {
							if (response.success && response.data) {
								const data = response.data;

								// ⭐ Skip directly to deactivate if nothing to download
								if (data.pending === 0) {
									console.log('[Offload Plus Disconnect] All files already local (pending=0). Deactivating offloading...');

									// Update scanning view to show deactivation message
									$('#disconnect-scanning-view h3').text('<?php esc_html_e( 'Deactivating Offloading...', 'offload-plus' ); ?>');
									$('#disconnect-scanning-view p').text(data.total_cloud.toLocaleString() + ' <?php esc_html_e( 'files already exist locally.', 'offload-plus' ); ?>');
									// Keep scanning view visible with spinner

									// ⭐ FIX: Call deactivate offloading endpoint BEFORE reload
									$.ajax({
										url: ajaxurl,
										type: 'POST',
										data: {
											action: 'offload_plus_deactivate_offloading',
											nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin_nonce' ) ); ?>'
										},
										success: function(response) {
											console.log('[Offload Plus Disconnect] Offloading disabled (pending=0 case)');

											// Hide scanning view and show success view
											$('#disconnect-scanning-view').hide();
											$('#disconnect-success-view').show();
											$('#disconnect-success-view p').text(data.total_cloud.toLocaleString() + ' files already exist locally. Offloading has been disabled.');

											// Auto-reload after 2.5 seconds
											setTimeout(function() {
												window.location.reload();
											}, 2500);
										},
										error: function() {
											// Hide scanning view and show error
											$('#disconnect-scanning-view').hide();
											$('#disconnect-error-message').text('<?php esc_html_e( 'Files are already local but failed to disable offloading. Please disable manually.', 'offload-plus' ); ?>');
											$('#disconnect-error-view').show();
										}
									});
									return;
								}

								// Build stats HTML for options view
								var summaryHtml = '';
								summaryHtml += '<div style="display: flex; justify-content: space-between; margin-bottom: 10px;">';
								summaryHtml += '<span style="font-weight: 600;">📁 <?php esc_html_e( 'Total files in cloud:', 'offload-plus' ); ?></span>';
								summaryHtml += '<span>' + data.total_cloud.toLocaleString() + ' (' + data.total_size_formatted + ')</span>';
								summaryHtml += '</div>';

								if (data.already_local > 0) {
									summaryHtml += '<div style="display: flex; justify-content: space-between; margin-bottom: 10px; color: #46b450;">';
									summaryHtml += '<span style="font-weight: 600;">✅ <?php esc_html_e( 'Already local:', 'offload-plus' ); ?></span>';
									summaryHtml += '<span>' + data.already_local.toLocaleString() + ' (' + data.local_size_formatted + ')</span>';
									summaryHtml += '</div>';
								}

								summaryHtml += '<div style="display: flex; justify-content: space-between; color: #d63638;">';
								summaryHtml += '<span style="font-weight: 600;">⬇️ <?php esc_html_e( 'Pending download:', 'offload-plus' ); ?></span>';
								summaryHtml += '<span>' + data.pending.toLocaleString() + ' (' + data.pending_size_formatted + ')</span>';
								summaryHtml += '</div>';

								// ⭐ Performance Level Selector
								summaryHtml += '<div style="margin-top: 15px; padding-top: 15px; border-top: 1px solid #ddd;">';
								summaryHtml += '<label for="download-concurrency-select" style="display: block; margin-bottom: 8px; font-weight: 600;">';
								summaryHtml += '⚡ <?php esc_html_e( 'Performance Level:', 'offload-plus' ); ?>';
								summaryHtml += '</label>';
								summaryHtml += '<select id="download-concurrency-select" class="regular-text" style="width: 100%; padding: 8px;">';
								summaryHtml += '<option value="5" selected><?php esc_html_e( 'Balanced (5 parallel)', 'offload-plus' ); ?></option>';
								summaryHtml += '<option value="20"><?php esc_html_e( 'Fast (20 parallel)', 'offload-plus' ); ?></option>';
								summaryHtml += '<option value="40"><?php esc_html_e( 'Intensive (40 parallel)', 'offload-plus' ); ?></option>';
								summaryHtml += '</select>';
								summaryHtml += '</div>';

								// Show options view with stats
								$('#disconnect-stats').html(summaryHtml);
								$('#disconnect-scanning-view').hide();
								$('#disconnect-options-view').show();

								// Store data for start button
								$('#start-disconnect').data('downloadData', data);
							} else {
								// Show error view
								$('#disconnect-scanning-view').hide();
								$('#disconnect-error-message').text('Failed to calculate download requirements');
								$('#disconnect-error-view').show();
							}
						},
						error: function() {
							// Show error view
							$('#disconnect-scanning-view').hide();
							$('#disconnect-error-message').text('Connection error while calculating downloads');
							$('#disconnect-error-view').show();
						}
					});
				} else {
					// Scan failed - show error
					$('#disconnect-scanning-view').hide();
					$('#disconnect-error-message').text(scanResponse.data || 'Failed to scan cloud storage');
					$('#disconnect-error-view').show();
				}
			},
			error: function() {
				// Scan error - show error view
				$('#disconnect-scanning-view').hide();
				$('#disconnect-error-message').text('Connection error while scanning cloud storage');
				$('#disconnect-error-view').show();
			}
		});
	});

	// Force Disconnect - skip scan, deactivate offloading only (keep provider config)
	$('#force-disconnect-btn').on('click', function() {
		var $btn = $(this);
		$btn.prop('disabled', true).text('<?php echo esc_js( __( 'Disconnecting...', 'offload-plus' ) ); ?>');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'offload_plus_deactivate_offloading',
				nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin_nonce' ) ); ?>'
			},
			success: function(response) {
				if (response.success) {
					$btn.text('<?php echo esc_js( __( 'Disconnected. Reloading...', 'offload-plus' ) ); ?>');
					setTimeout(function() { window.location.reload(); }, 1500);
				} else {
					$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Force Disconnect Without Sync', 'offload-plus' ) ); ?>');
					alert(response.data || '<?php echo esc_js( __( 'Failed to disconnect', 'offload-plus' ) ); ?>');
				}
			},
			error: function() {
				$btn.prop('disabled', false).text('<?php echo esc_js( __( 'Force Disconnect Without Sync', 'offload-plus' ) ); ?>');
				alert('<?php echo esc_js( __( 'Connection error', 'offload-plus' ) ); ?>');
			}
		});
	});

	// Start Download button handler (REVERSE SYNC)
	$('#start-disconnect').on('click', function() {
		const concurrency = parseInt($('#download-concurrency-select').val()) || 5;
		const data = $(this).data('downloadData');

		console.log('[Offload Plus Download] Starting reverse sync with concurrency:', concurrency);
		console.log('[Offload Plus Download] Files to download:', data.pending);

		// Switch to progress view
		$('#disconnect-options-view').hide();
		$('#disconnect-progress-view').show();

		// Start reverse sync AJAX (correct endpoint)
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'offload_plus_start_reverse_sync',
				nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin' ) ); ?>',
				concurrency: concurrency,
				mode: 'continue'
			},
			success: function(response) {
				if (response.success) {
					console.log('[Offload Plus Download] Reverse sync started:', response.data);
					// Start processing batches
					processReverseBatch();
				} else {
					$('#disconnect-progress-view').hide();
					$('#disconnect-error-message').text('Failed to start download: ' + response.data);
					$('#disconnect-error-view').show();
				}
			},
			error: function() {
				$('#disconnect-progress-view').hide();
				$('#disconnect-error-message').text('Connection error while starting download');
				$('#disconnect-error-view').show();
			}
		});
	});

	// Process reverse sync batches
	function processReverseBatch() {
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'offload_plus_process_reverse_batch',
				nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin' ) ); ?>'
			},
			success: function(response) {
				if (response.success && response.data) {
					const data = response.data;

					// Update progress bar
					const totalFiles = data.total_files || 1;
					// ⭐ FIX: Backend returns 'processed_files' not 'downloaded'
					const downloaded = data.processed_files || data.successful_downloads || data.downloaded || 0;
					// ⭐ FIX: Read both possible names (pending_files for SYNC, remaining_files for DISCONNECT)
					const remaining = data.pending_files || data.remaining_files || 0;
					const percent = Math.round((downloaded / totalFiles) * 100);

					// ⭐ Update progress bar and percentage
					$('#disconnect-progress-bar').css('width', percent + '%');
					$('#disconnect-progress-percent').text(percent + '%');
					$('#disconnect-progress-text').text(downloaded + ' / ' + totalFiles + ' files (' + percent + '%)');

					// ⭐ Update statistics (coherente con sync modal)
					$('#disconnect-stats-downloaded').text(downloaded.toLocaleString());
					$('#disconnect-stats-successful').text(downloaded.toLocaleString());
					$('#disconnect-stats-remaining').text(remaining.toLocaleString());

					console.log('[Offload Plus Download] Progress:', downloaded, '/', totalFiles, '(', percent, '%)');

					// ⭐ FIX: Improved validation - ONLY complete if status === 'completed'
					// Don't rely solely on remaining === 0 to prevent premature completion
					if (data.status === 'completed') {
						console.log('[Offload Plus Download] Download completed');
						onDisconnectComplete(downloaded, data.failed || 0, data.skipped || 0);
					} else if (data.status !== 'processing' && remaining === 0) {
						// Fallback: If status is not 'processing' and no files remaining, also complete
						console.log('[Offload Plus Download] Download completed (fallback)');
						onDisconnectComplete(downloaded, data.failed || 0, data.skipped || 0);
					} else {
						// Continue with next batch
						console.log('[Offload Plus Download] Remaining:', remaining, 'files - continuing...');
						setTimeout(processReverseBatch, 100);
					}
				} else {
					$('#disconnect-progress-view').hide();
					$('#disconnect-error-message').text('Download failed: ' + (response.data || 'Unknown error'));
					$('#disconnect-error-view').show();
				}
			},
			error: function() {
				$('#disconnect-progress-view').hide();
				$('#disconnect-error-message').text('Connection error during download');
				$('#disconnect-error-view').show();
			}
		});
	}

	// ⭐ Cancel Download button (coherente con sync modal - NO alert)
	$('#cancel-disconnect').on('click', function() {
		console.log('[Offload Plus Download] Cancel button clicked');

		// Change button state to "Cancelling..."
		$('#disconnect-progress-label').text('<?php esc_html_e( 'Cancelling download...', 'offload-plus' ); ?>');
		$('#cancel-disconnect').prop('disabled', true).css('opacity', '0.5');

		// Reload page (esto cancela el polling automáticamente)
		setTimeout(function() {
			window.location.reload();
		}, 500);
	});

	// ⭐ Disconnect completion handler
	function onDisconnectComplete(successful, failed, skipped) {
		// Hide progress
		$('#disconnect-progress-view').hide();

		const total = successful + failed + skipped;

		// Check if there were failures
		if (failed > 0 || skipped > 0) {
			// ⚠️ Show summary with stats (don't disconnect, don't reload)
			let summaryHtml = '<div style="text-align: center; padding: 20px;">';
			summaryHtml += '<div style="font-size: 64px; margin-bottom: 20px;">⚠️</div>';
			summaryHtml += '<h3 style="color: #f0b849; margin: 0 0 10px 0;"><?php esc_html_e( 'Download Completed with Errors', 'offload-plus' ); ?></h3>';
			summaryHtml += '<p style="font-size: 16px; color: #666; margin: 10px 0;">';
			summaryHtml += '<?php esc_html_e( 'Some files could not be downloaded.', 'offload-plus' ); ?>';
			summaryHtml += '</p>';

			// Stats
			summaryHtml += '<div style="background: #f5f5f5; padding: 20px; border-radius: 8px; margin: 20px 0; text-align: left;">';
			summaryHtml += '<div style="margin-bottom: 10px;"><strong><?php esc_html_e( 'Total files:', 'offload-plus' ); ?></strong> ' + total.toLocaleString() + '</div>';
			summaryHtml += '<div style="margin-bottom: 10px; color: #46b450;"><strong><?php esc_html_e( 'Downloaded:', 'offload-plus' ); ?></strong> ' + successful.toLocaleString() + '</div>';
			if (failed > 0) {
				summaryHtml += '<div style="margin-bottom: 10px; color: #d63638;"><strong><?php esc_html_e( 'Failed:', 'offload-plus' ); ?></strong> ' + failed.toLocaleString() + '</div>';
			}
			if (skipped > 0) {
				summaryHtml += '<div style="color: #f0b849;"><strong><?php esc_html_e( 'Skipped:', 'offload-plus' ); ?></strong> ' + skipped.toLocaleString() + '</div>';
			}
			summaryHtml += '</div>';

			// Close button
			summaryHtml += '<div style="margin-top: 20px;">';
			summaryHtml += '<button class="button button-primary close-disconnect-modal" style="padding: 10px 30px; font-size: 16px;">';
			summaryHtml += '<?php esc_html_e( 'Close', 'offload-plus' ); ?>';
			summaryHtml += '</button>';
			summaryHtml += '</div>';
			summaryHtml += '</div>';

			// Replace modal content with summary
			$('.offload-plus-modal-content', '#disconnect-modal').html(summaryHtml);
		} else {
			// ✅ All successful - disconnect offloading and show success
			console.log('[Offload Plus Disconnect] All files downloaded successfully. Disconnecting offloading...');

			// Call disable offloading endpoint
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'offload_plus_deactivate_offloading',
					nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin_nonce' ) ); ?>'
				},
				success: function(response) {
					console.log('[Offload Plus Disconnect] Offloading disabled');

					// Show success view
					$('#disconnect-success-view').show();

					// Auto-reload after 2.5 seconds
					setTimeout(function() {
						window.location.reload();
					}, 2500);
				},
				error: function() {
					// If disable fails, show error
					$('#disconnect-error-message').text('<?php esc_html_e( 'Files downloaded but failed to disable offloading. Please disable manually.', 'offload-plus' ); ?>');
					$('#disconnect-error-view').show();
				}
			});
		}
	}

	// Legacy code below needs to be refactored to use new modal...
	// Keeping it temporarily for reference

	/*
	// OLD CODE - TO BE REMOVED AFTER FULL MIGRATION
	// Only show "Already downloaded" if > 0
					if (data.already_local > 0) {
						summaryHtml += '<div style="margin-bottom: 8px; color: #0a0;">';
						summaryHtml += '<strong>Already downloaded:</strong> ' + data.already_local.toLocaleString() + ' (' + data.local_size_formatted + ') ✅';
						summaryHtml += '</div>';
					}

					summaryHtml += '<div style="color: #c60;">';
					summaryHtml += '<strong>Pending download:</strong> ' + data.pending.toLocaleString() + ' (' + data.pending_size_formatted + ')';
					summaryHtml += '</div>';
					summaryHtml += '</div>';

					// Change info message based on already_local count
					if (data.already_local > 0) {
						summaryHtml += '<p style="margin: 15px 0; color: #666;">';
						summaryHtml += 'ℹ️  You already have ' + data.already_local.toLocaleString() + ' files locally. Choose how to proceed:';
						summaryHtml += '</p>';
					} else {
						summaryHtml += '<p style="margin: 15px 0; color: #666;">';
						summaryHtml += 'ℹ️  All files need to be downloaded from the cloud.';
						summaryHtml += '</p>';
					}

					// ⭐ Performance Level Selector for Downloads
					summaryHtml += '<div style="margin: 20px 0; padding: 15px; background: #e7f3ff; border-left: 4px solid #2196f3; border-radius: 4px;">';
					summaryHtml += '<label for="download-concurrency-select" style="display: block; margin-bottom: 10px; font-weight: 600; color: #333;">';
					summaryHtml += '⚡ <?php esc_html_e( 'Download Performance Level:', 'offload-plus' ); ?>';
					summaryHtml += '</label>';
					summaryHtml += '<select id="download-concurrency-select" class="regular-text" style="width: 100%; padding: 8px;">';
					summaryHtml += '<option value="5" selected><?php esc_html_e( 'Balanced (5 parallel - Recommended)', 'offload-plus' ); ?></option>';
					summaryHtml += '<option value="20"><?php esc_html_e( 'Fast (20 parallel - More resources)', 'offload-plus' ); ?></option>';
					summaryHtml += '<option value="40"><?php esc_html_e( 'Intensive (40 parallel - Maximum speed)', 'offload-plus' ); ?></option>';
					summaryHtml += '</select>';
					summaryHtml += '<p class="description" style="margin-top: 8px; font-size: 12px; color: #666;">';
					summaryHtml += '<?php esc_html_e( 'Balanced is recommended for most cases. Fast and Intensive require more server resources.', 'offload-plus' ); ?>';
					summaryHtml += '</p>';
					summaryHtml += '</div>';

					// Check if Continue button should be disabled (nothing downloaded yet)
					var continueDisabled = (data.already_local === 0 || data.pending === data.total_cloud);
					var continueStyle = continueDisabled ? 'opacity: 0.5; cursor: not-allowed;' : '';
					var continueClass = continueDisabled ? 'button-disabled' : '';

					summaryHtml += '<div class="button-group" style="display: flex; gap: 15px; justify-content: center; margin-top: 20px;">';
					summaryHtml += '<div style="flex: 1; text-align: center;">';
					summaryHtml += '<button id="continue-download-btn" class="button button-primary button-large ' + continueClass + '" style="width: 100%; height: auto; padding: 15px; font-size: 14px; ' + continueStyle + '" ' + (continueDisabled ? 'disabled' : '') + '>';
					summaryHtml += '<span class="dashicons dashicons-controls-play" style="font-size: 20px; width: 20px; height: 20px; margin-right: 5px;"></span>';
					summaryHtml += '<div style="font-size: 15px; font-weight: 600;">Continue Download</div>';
					summaryHtml += '<div style="font-size: 12px; opacity: 0.9; margin-top: 5px;">';
					if (continueDisabled) {
						summaryHtml += 'Nothing downloaded yet';
					} else {
						summaryHtml += 'Download only ' + data.pending.toLocaleString() + ' missing files';
					}
					summaryHtml += '</div>';
					summaryHtml += '</button>';
					summaryHtml += '</div>';
					summaryHtml += '<div style="flex: 1; text-align: center;">';
					summaryHtml += '<button id="scratch-download-btn" class="button button-secondary button-large" style="width: 100%; height: auto; padding: 15px; font-size: 14px;">';
					summaryHtml += '<span class="dashicons dashicons-update" style="font-size: 20px; width: 20px; height: 20px; margin-right: 5px;"></span>';
					summaryHtml += '<div style="font-size: 15px; font-weight: 600;">Download from Scratch</div>';
					summaryHtml += '<div style="font-size: 12px; opacity: 0.9; margin-top: 5px;">Re-download all ' + data.total_cloud.toLocaleString() + ' files</div>';
					summaryHtml += '</button>';
					summaryHtml += '</div>';
					summaryHtml += '</div>';
					summaryHtml += '<p style="margin-top: 20px; padding: 10px; background: #fff3cd; border-left: 4px solid #ffc107; font-size: 13px;">';
					summaryHtml += '⚠️  <strong>Warning:</strong> Download process may take hours for large libraries. Keep this tab open.';
					summaryHtml += '</p>';
					summaryHtml += '</div>';

					$('#disconnect-container').html(summaryHtml);

					// Store data for later use
					window.downloadCalculation = data;

							} else {
								$('#disconnect-container').html('<div style="color: #d63638; padding: 20px; text-align: center;">Error calculating download: ' + (response.data || 'Unknown error') + '</div>');
							}
						},
						error: function(xhr, status, error) {
							$('#disconnect-container').html('<div style="color: #d63638; padding: 20px; text-align: center;">Connection error (calculate): ' + error + '</div>');
						}
					});

				} else {
					$('#disconnect-container').html('<div style="color: #d63638; padding: 20px; text-align: center;">Error scanning remote: ' + (scanResponse.data || 'Unknown error') + '</div>');
				}
			},
			error: function(xhr, status, error) {
				$('#disconnect-container').html('<div style="color: #d63638; padding: 20px; text-align: center;">Connection error (scan): ' + error + '</div>');
			}
		});
	});

	// ⭐ NEW: Handle "Continue Download" button (event delegation for dynamically created button)
	$(document).on('click', '#continue-download-btn', function() {
		startDownload('continue');
	});

	// ⭐ NEW: Handle "Download from Scratch" button
	$(document).on('click', '#scratch-download-btn', function() {
		startDownload('scratch');
	});

	// ⭐ NEW: Start download function (with mode)
	function startDownload(mode) {
		// Get concurrency from selector (not hardcoded)
		const concurrency = parseInt($('#download-concurrency-select').val()) || 5;

		console.log('[Offload Plus Download] Using concurrency level: ' + concurrency);

		// Hide disconnect container and show progress
		$('#disconnect-container').hide();
		$('#sync-modal-progress').show();

		// Reset state
		isSyncCancelled = false;
		retryCount = 0;

		// Reset progress
		$('#sync-modal-progress-bar').css('width', '0%');
		$('#sync-modal-progress-text').text('0 / 0 (0%)');
		$('#sync-modal-progress-percent').text('0%');
		$('#sync-modal-stats-processed').text('0');
		$('#sync-modal-stats-successful').text('0');
		$('#sync-modal-stats-failed').text('0');

		console.log('[Offload Plus Download] Starting download in mode: ' + mode);

		// Start reverse sync with mode
		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'offload_plus_start_reverse_sync',
				nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin' ) ); ?>',
				concurrency: concurrency,
				mode: mode
			},
			success: function(response) {
				if (response.success) {
					console.log('[Offload Plus Download] Started with ' + response.data.total_files + ' files (mode: ' + mode + ')');
					processReverseSyncBatch();
				} else {
					alert('Error: ' + (response.data?.message || 'Unknown error'));
					$('#sync-modal').hide();
				}
			},
			error: function() {
				alert('<?php esc_html_e( 'Connection error. Please try again.', 'offload-plus' ); ?>');
				$('#sync-modal').hide();
			}
		});
	}

	// ⭐ OLD DELEGATED HANDLER - NOT USED ANYMORE (moved to direct handler in jQuery ready)
	// The delegated handler was not working due to event propagation issues
	// Now using direct handler attached in jQuery ready block (line ~1121)

	// Handle modal start
	$('#sync-modal-start').on('click', function() {
		const concurrency = parseInt($('#sync-modal-concurrency').val()) || 5;

		// Hide config, show progress
		$('#sync-modal-config').hide();
		$('#sync-modal-progress').show();
		$('#sync-modal-start').hide();

		// Reset state
		isSyncCancelled = false;
		retryCount = 0;

		// Reset progress
		$('#sync-modal-progress-bar').css('width', '0%');
		$('#sync-modal-progress-text').text('0 / 0 (0%)');
		$('#sync-modal-progress-percent').text('0%');
		$('#sync-modal-stats-processed').text('0');
		$('#sync-modal-stats-successful').text('0');
		$('#sync-modal-stats-failed').text('0');

		if (currentSyncMode === 'upload') {
			// Start upload sync
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'offload_plus_start_sync',
					nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin' ) ); ?>',
					concurrency: concurrency
				},
				success: function(response) {
					if (response.success) {
						console.log('[Offload Plus Sync] Initialized with ' + response.data.total_files + ' files');
						$('#sync-modal-total-files').text(response.data.total_files.toLocaleString());
						processSyncBatch();
					} else {
						alert('Error: ' + (response.data?.message || 'Unknown error'));
						$('#sync-modal').hide();
					}
				},
				error: function() {
					alert('<?php esc_html_e( 'Connection error. Please try again.', 'offload-plus' ); ?>');
					$('#sync-modal').hide();
				}
			});
		} else {
			// Start reverse sync (download)
			$.ajax({
				url: ajaxurl,
				type: 'POST',
				data: {
					action: 'offload_plus_start_reverse_sync',
					nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin' ) ); ?>',
					concurrency: concurrency
				},
				success: function(response) {
					if (response.success) {
						console.log('[Offload Plus Reverse Sync] Started with ' + response.data.total_files + ' files');
						$('#sync-modal-total-files').text(response.data.total_files.toLocaleString());
						processReverseSyncBatch();
					} else {
						alert('Error: ' + (response.data?.message || 'Unknown error'));
						$('#sync-modal').hide();
					}
				},
				error: function() {
					alert('<?php esc_html_e( 'Connection error. Please try again.', 'offload-plus' ); ?>');
					$('#sync-modal').hide();
				}
			});
		}
	});

	// ⭐ Process reverse sync batch (recursion)
	function processReverseSyncBatch() {
		console.log('[Offload Plus Reverse Sync] processReverseSyncBatch() called, isSyncCancelled:', isSyncCancelled);

		if (isSyncCancelled) {
			console.log('[Offload Plus Reverse Sync] Cancelled by user');
			return;
		}

		console.log('[Offload Plus Reverse Sync] Starting AJAX call to process_reverse_batch...');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'offload_plus_process_reverse_batch',
				nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin' ) ); ?>'
			},
			success: function(response) {
				console.log('[Offload Plus Reverse Sync] Batch response:', response);
				retryCount = 0; // Reset retry count on success

				if (response.success) {
					const data = response.data;
					const percentage = data.percentage || 0;
					const processed = data.processed_files || 0;
					const total = data.total_files || 0;
					const successful = data.successful_downloads || processed;
					const failed = data.failed_downloads || 0;

					// Update old progress bar (if visible)
					$('#sync-progress-bar').css('width', percentage + '%');
					$('#sync-progress-text').text(processed + ' / ' + total + ' files (' + percentage + '%)');

					// Update modal progress
					$('#sync-modal-progress-bar').css('width', percentage + '%');
					$('#sync-modal-progress-text').text(processed.toLocaleString() + ' / ' + total.toLocaleString() + ' files');
					$('#sync-modal-progress-percent').text(Math.round(percentage) + '%');
					$('#sync-modal-stats-processed').text(processed.toLocaleString());
					$('#sync-modal-stats-successful').text(successful.toLocaleString());
					$('#sync-modal-stats-failed').text(failed.toLocaleString());

					console.log('[Offload Plus Reverse Sync] Progress: ' + processed + '/' + total + ' (' + percentage + '%)');

					// Check if completed
					if (data.status === 'completed') {
						console.log('[Offload Plus Reverse Sync] Completed!');

						// ⭐ Update ALL progress to final values BEFORE hiding (important for small batches)
						const finalProcessed = data.processed_files || data.total_files || 0;
						const finalTotal = data.total_files || 0;

						$('#sync-modal-progress-bar').css('width', '100%');
						$('#sync-modal-progress-percent').text('100%');
						$('#sync-modal-progress-text').text(finalProcessed.toLocaleString() + ' / ' + finalTotal.toLocaleString() + ' files');
						$('#sync-modal-stats-processed').text(finalProcessed.toLocaleString());
						$('#sync-modal-stats-successful').text(finalProcessed.toLocaleString());

						// Wait a moment so user sees 100%, then show completion
						setTimeout(function() {
							// Hide progress, show completion message
							$('#sync-modal-progress').hide();

						// Show completion screen with deactivate button
						var completionHtml = '<div style="text-align: center; padding: 40px 20px;">';
						completionHtml += '<div style="font-size: 48px; color: #46b450; margin-bottom: 20px;">✓</div>';
						completionHtml += '<h3 style="margin: 0 0 15px 0; color: #2c3338;">Download Complete</h3>';
						completionHtml += '<p style="margin: 0 0 10px 0; font-size: 15px; color: #50575e;">';
						completionHtml += '<strong>' + finalProcessed.toLocaleString() + ' files</strong> downloaded successfully';
						completionHtml += '</p>';
						completionHtml += '<p style="margin: 0 0 30px 0; font-size: 14px; color: #787c82;">';
						completionHtml += 'All files have been restored to local storage.';
						completionHtml += '</p>';
						completionHtml += '<button id="deactivate-offloading-btn" class="button button-primary button-large" style="padding: 12px 40px; font-size: 15px; height: auto;">';
						completionHtml += '<span class="dashicons dashicons-cloud" style="margin-top: 4px;"></span> ';
						completionHtml += 'Deactivate Offloading';
						completionHtml += '</button>';
						completionHtml += '<p style="margin: 20px 0 0 0; font-size: 13px; color: #787c82;">';
						completionHtml += 'Click the button above to complete the disconnection.';
						completionHtml += '</p>';
						completionHtml += '</div>';

						$('#sync-modal-config').html(completionHtml).show();
						$('#sync-modal-start').hide();
						$('#sync-modal-cancel').text('Close').show();
						}, 800); // 800ms delay so user sees 100% completion
					} else {
						// ⭐ Continue recursion
						processReverseSyncBatch();
					}
				}
			},
			error: function(xhr, status, error) {
				retryCount++;
				console.error('[Offload Plus Reverse Sync] Error (attempt ' + retryCount + '/' + maxRetries + '):', error);

				if (retryCount > maxRetries) {
					alert('<?php esc_html_e( 'Max retries exceeded. Please try again later.', 'offload-plus' ); ?>');
					location.reload();
					return;
				}

				// Exponential backoff
				const backoff = Math.pow(retryCount, 2.5) * 1000;
				console.log('[Offload Plus Reverse Sync] Retrying in ' + (backoff/1000).toFixed(1) + 's...');

				setTimeout(function() {
					processReverseSyncBatch();
				}, backoff);
			}
		});
	}

	// Handle "Deactivate Offloading" button (after download completes)
	$(document).on('click', '#deactivate-offloading-btn', function() {
		const button = $(this);
		button.prop('disabled', true).html('<span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span> Deactivating...');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'offload_plus_deactivate_offloading',
				nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin_nonce' ) ); ?>'
			},
			success: function(response) {
				if (response.success) {
					button.html('✓ Deactivated').css('background', '#46b450');
					setTimeout(function() {
						location.reload();
					}, 1000);
				} else {
					alert('Error: ' + (response.data?.message || 'Failed to deactivate offloading'));
					button.prop('disabled', false).html('<span class="dashicons dashicons-cloud"></span> Deactivate Offloading');
				}
			},
			error: function() {
				alert('Connection error. Please try again.');
				button.prop('disabled', false).html('<span class="dashicons dashicons-cloud"></span> Deactivate Offloading');
			}
		});
	});

	// ⭐ DISABLED: No backdrop click to close modals
	// Users must use action buttons to close modals
	// This prevents accidental closes during important operations

	/* REMOVED - backdrop click handlers
	$('#sync-modal').on('click', function(e) {
		if (e.target === this) {
			if ($('#sync-modal-progress').is(':visible')) {
				isSyncCancelled = true;
			}
			$(this).hide();
		}
	});

	$('#failed-files-modal').on('click', function(e) {
		if (e.target === this) {
			$(this).hide();
		}
	});
	*/

	// ══════════════════════════════════════════════════════════
	// DEV MODE: Skip Sync Buttons
	// ══════════════════════════════════════════════════════════

	$('#dev-enable-without-sync-btn').on('click', function() {
		var $btn = $(this);

		if (!confirm('<?php echo esc_js( __( 'DEV MODE: Enable offloading without syncing files? This assumes cloud already has all files.', 'offload-plus' ) ); ?>')) {
			return;
		}

		$btn.prop('disabled', true);
		var originalHtml = $btn.html();
		$btn.html('<?php echo esc_js( __( 'Enabling...', 'offload-plus' ) ); ?>');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'offload_plus_dev_enable_without_sync',
				nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin' ) ); ?>'
			},
			success: function(response) {
				if (response.success) {
					showNotification('<?php echo esc_js( __( 'DEV MODE: Offloading enabled without sync!', 'offload-plus' ) ); ?>', 'success');
					setTimeout(function() { window.location.reload(); }, 1000);
				} else {
					showNotification('Error: ' + (response.data || 'Unknown error'), 'error');
					$btn.prop('disabled', false).html(originalHtml);
				}
			},
			error: function() {
				showNotification('<?php echo esc_js( __( 'Connection error', 'offload-plus' ) ); ?>', 'error');
				$btn.prop('disabled', false).html(originalHtml);
			}
		});
	});

	$('#dev-disconnect-without-sync-btn').on('click', function() {
		var $btn = $(this);

		if (!confirm('<?php echo esc_js( __( 'DEV MODE: Disconnect without downloading files? This assumes local already has all files.', 'offload-plus' ) ); ?>')) {
			return;
		}

		$btn.prop('disabled', true);
		var originalHtml = $btn.html();
		$btn.html('<?php echo esc_js( __( 'Disconnecting...', 'offload-plus' ) ); ?>');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'offload_plus_dev_disconnect_without_sync',
				nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin' ) ); ?>'
			},
			success: function(response) {
				if (response.success) {
					showNotification('<?php echo esc_js( __( 'DEV MODE: Offloading disabled without sync!', 'offload-plus' ) ); ?>', 'success');
					setTimeout(function() { window.location.reload(); }, 1000);
				} else {
					showNotification('Error: ' + (response.data || 'Unknown error'), 'error');
					$btn.prop('disabled', false).html(originalHtml);
				}
			},
			error: function() {
				showNotification('<?php echo esc_js( __( 'Connection error', 'offload-plus' ) ); ?>', 'error');
				$btn.prop('disabled', false).html(originalHtml);
			}
		});
	});

	// Auto-start sync if coming from cloud-provider tab CTA
	var urlParams = new URLSearchParams(window.location.search);
	if (urlParams.get('auto-start') === '1' && $('#start-sync-btn').length && !$('#start-sync-btn').prop('disabled')) {
		// Clean URL to prevent re-trigger on refresh
		var cleanUrl = window.location.pathname + '?page=offload-plus&tab=sync-offloading';
		window.history.replaceState({}, '', cleanUrl);
		// Trigger sync start after UI is ready
		setTimeout(function() {
			$('#start-sync-btn').trigger('click');
		}, 500);
	}
});
</script>
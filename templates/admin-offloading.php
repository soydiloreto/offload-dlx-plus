<?php
/**
 * Admin: Offloading tab template.
 *
 * Local variables ($config, $provider_config) are populated by
 * Admin::render_tab_content() in the calling scope. Suppress the prefix sniff:
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 *
 * @package OffloadDlxPlus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OffloadDlxPlus\ConfigManager;

$config          = ConfigManager::get_config();
$provider_config = ConfigManager::get_current_provider_config();
?>

<div class="offload-dlx-plus-offloading">
	<div class="offload-dlx-plus-header">
		<h2><?php esc_html_e( 'Media Offloading Configuration', 'offload-dlx-plus' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Configure how your media files are handled and stored in the cloud.', 'offload-dlx-plus' ); ?>
		</p>
	</div>

	<?php if ( ! ConfigManager::is_configured() ) : ?>
		<!-- Configuration Required Notice -->
		<div class="notice notice-warning">
			<p>
				<strong><?php esc_html_e( 'Cloud Storage Configuration Required', 'offload-dlx-plus' ); ?></strong><br>
				<?php
				echo wp_kses(
					sprintf(
						/* translators: %s: URL of the Settings tab */
						__( 'Please configure your cloud storage settings in the <a href="%s">Settings tab</a> before enabling offloading.', 'offload-dlx-plus' ),
						esc_url( admin_url( 'admin.php?page=offload-dlx-plus&tab=settings' ) )
					),
					array( 'a' => array( 'href' => true ) )
				);
				?>
			</p>
		</div>
	<?php else : ?>
		
		<!-- Offloading Status -->
		<div class="offload-dlx-plus-offloading-status">
			<h3><?php esc_html_e( 'Current Status', 'offload-dlx-plus' ); ?></h3>
			
			<div class="status-cards">
				<div class="status-card <?php echo ConfigManager::is_offloading_enabled() ? 'active' : 'inactive'; ?>">
					<div class="status-icon">
						<span class="dashicons <?php echo ConfigManager::is_offloading_enabled() ? 'dashicons-cloud' : 'dashicons-cloud-outline'; ?>"></span>
					</div>
					<div class="status-content">
						<h4><?php esc_html_e( 'Media Offloading', 'offload-dlx-plus' ); ?></h4>
						<p class="status-text">
							<?php if ( ConfigManager::is_offloading_enabled() ) : ?>
								<?php esc_html_e( 'Active', 'offload-dlx-plus' ); ?>
								<span class="strategy">(<?php echo esc_html( ConfigManager::get_offloading_strategy() ); ?>)</span>
							<?php else : ?>
								<?php esc_html_e( 'Disabled', 'offload-dlx-plus' ); ?>
							<?php endif; ?>
						</p>
					</div>
				</div>
				
				<div class="status-card provider">
					<div class="status-icon">
						<span class="dashicons dashicons-admin-settings"></span>
					</div>
					<div class="status-content">
						<h4><?php esc_html_e( 'Cloud Provider', 'offload-dlx-plus' ); ?></h4>
						<p class="status-text">
							<?php echo esc_html( ucfirst( $config['cloud_provider'] ) ); ?>
							<small>(<?php echo esc_html( $provider_config['account_name'] ); ?>)</small>
						</p>
					</div>
				</div>
			</div>
		</div>

		<!-- Offloading Configuration Form -->
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="offload-dlx-plus-offloading-form">
			<input type="hidden" name="action" value="offload_dlx_plus_save_offloading">
			<?php wp_nonce_field( 'offload_dlx_plus_save_offloading', '_wpnonce' ); ?>
			
			<div class="settings-section">
				<h3><?php esc_html_e( 'Offloading Strategy', 'offload-dlx-plus' ); ?></h3>
				
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Enable Media Offloading', 'offload-dlx-plus' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e( 'Enable Media Offloading', 'offload-dlx-plus' ); ?></span>
								</legend>
								<label for="offloading_enabled">
									<input type="checkbox" 
											id="offloading_enabled" 
											name="offloading_enabled" 
											value="1" 
											<?php checked( $config['offloading_enabled'] ); ?>>
									<?php esc_html_e( 'Enable automatic media offloading to cloud storage', 'offload-dlx-plus' ); ?>
								</label>
							</fieldset>
							<p class="description">
								<?php esc_html_e( 'When enabled, media files will be automatically uploaded to cloud storage based on the strategy selected below.', 'offload-dlx-plus' ); ?>
							</p>
						</td>
					</tr>
				</table>

				<div class="strategy-section" style="<?php echo ! $config['offloading_enabled'] ? 'display: none;' : ''; ?>">
					<h4><?php esc_html_e( 'Offloading Strategy', 'offload-dlx-plus' ); ?></h4>
					
					<div class="strategy-options">
						<label class="strategy-option">
							<input type="radio" 
									name="offloading_strategy" 
									value="complete" 
									<?php checked( $config['offloading_strategy'], 'complete' ); ?>>
							<div class="strategy-content">
								<h5><?php esc_html_e( 'Complete Migration', 'offload-dlx-plus' ); ?></h5>
								<p><?php esc_html_e( 'Upload all existing media files to cloud storage and serve all media from the cloud.', 'offload-dlx-plus' ); ?></p>
								<div class="strategy-features">
									<span class="feature"><?php esc_html_e( '✓ All media in cloud', 'offload-dlx-plus' ); ?></span>
									<span class="feature"><?php esc_html_e( '✓ Consistent experience', 'offload-dlx-plus' ); ?></span>
									<span class="feature"><?php esc_html_e( '⚠ Requires migration time', 'offload-dlx-plus' ); ?></span>
								</div>
							</div>
						</label>

						<label class="strategy-option">
							<input type="radio" 
									name="offloading_strategy" 
									value="new_uploads_only" 
									<?php checked( $config['offloading_strategy'], 'new_uploads_only' ); ?>>
							<div class="strategy-content">
								<h5><?php esc_html_e( 'New Files Only', 'offload-dlx-plus' ); ?></h5>
								<p><?php esc_html_e( 'Only upload new media files to cloud storage. Existing files remain local.', 'offload-dlx-plus' ); ?></p>
								<div class="strategy-features">
									<span class="feature"><?php esc_html_e( '✓ Immediate activation', 'offload-dlx-plus' ); ?></span>
									<span class="feature"><?php esc_html_e( '✓ No migration needed', 'offload-dlx-plus' ); ?></span>
									<span class="feature"><?php esc_html_e( '⚠ Mixed storage locations', 'offload-dlx-plus' ); ?></span>
								</div>
							</div>
						</label>
					</div>
				</div>
			</div>

			<!-- Additional Offloading Options -->
			<div class="additional-options" style="<?php echo ! $config['offloading_enabled'] ? 'display: none;' : ''; ?>">
				<h3><?php esc_html_e( 'Offloading Options', 'offload-dlx-plus' ); ?></h3>
				
				<table class="form-table">
					<tr>
						<th scope="row"><?php esc_html_e( 'Local File Management', 'offload-dlx-plus' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e( 'Local File Management', 'offload-dlx-plus' ); ?></span>
								</legend>
								<label for="delete_local_files">
									<input type="checkbox" 
											id="delete_local_files" 
											name="delete_local_files" 
											value="1" 
											<?php checked( $config['offloading']['remove_local_copies'] ?? false ); ?>>
									<?php esc_html_e( 'Delete local files after uploading to cloud', 'offload-dlx-plus' ); ?>
								</label>
								<p class="description">
									<?php esc_html_e( 'Save local disk space by removing files after successful cloud upload. Local files will be deleted permanently.', 'offload-dlx-plus' ); ?>
								</p>
							</fieldset>
						</td>
					</tr>
					
					<tr>
						<th scope="row"><?php esc_html_e( 'Backup & Safety', 'offload-dlx-plus' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text">
									<span><?php esc_html_e( 'Backup & Safety', 'offload-dlx-plus' ); ?></span>
								</legend>
								<label for="backup_originals">
									<input type="checkbox" 
											id="backup_originals" 
											name="backup_originals" 
											value="1" 
											<?php checked( $config['offloading']['backup_originals'] ?? true ); ?>>
									<?php esc_html_e( 'Keep backup copies of original files during migration', 'offload-dlx-plus' ); ?>
								</label>
								<p class="description">
									<?php esc_html_e( 'Recommended for safety. Original files will be preserved during the migration process.', 'offload-dlx-plus' ); ?>
								</p>
							</fieldset>
						</td>
					</tr>
				</table>
			</div>

			<!-- Migration Status (for complete strategy) -->
			<?php if ( $config['offloading_strategy'] === 'complete' && $config['migration_status'] !== 'completed' ) : ?>
				<div class="settings-section migration-section">
					<h3><?php esc_html_e( 'Migration Status', 'offload-dlx-plus' ); ?></h3>
					
					<?php if ( $config['migration_status'] === 'pending' ) : ?>
						<div class="migration-pending">
							<p><?php esc_html_e( 'Migration is required to complete the setup. This process will:', 'offload-dlx-plus' ); ?></p>
							<ul>
								<li><?php esc_html_e( 'Analyze all existing media files', 'offload-dlx-plus' ); ?></li>
								<li><?php esc_html_e( 'Upload files to cloud storage (without deleting local copies)', 'offload-dlx-plus' ); ?></li>
								<li><?php esc_html_e( 'Enable URL rewriting to serve files from cloud', 'offload-dlx-plus' ); ?></li>
							</ul>
							<button type="button" class="button button-primary" id="start-migration">
								<?php esc_html_e( 'Start Migration', 'offload-dlx-plus' ); ?>
							</button>
						</div>
					<?php elseif ( $config['migration_status'] === 'running' ) : ?>
						<div class="migration-running">
							<p><?php esc_html_e( 'Migration in progress...', 'offload-dlx-plus' ); ?></p>
							<div class="progress-bar">
								<div class="progress-fill" style="width: <?php echo intval( $config['migration_progress'] ); ?>%"></div>
							</div>
							<p class="progress-text"><?php echo intval( $config['migration_progress'] ); ?>% <?php esc_html_e( 'completed', 'offload-dlx-plus' ); ?></p>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<div class="submit-section">
				<?php submit_button( __( 'Save Offloading Configuration', 'offload-dlx-plus' ), 'primary', 'save_offloading' ); ?>
			</div>
		</form>

	<?php endif; ?>
</div>

<style>
.offload-dlx-plus-offloading {
	max-width: 800px;
}

.status-cards {
	display: flex;
	gap: 20px;
	margin: 20px 0;
}

.status-card {
	flex: 1;
	padding: 20px;
	border: 1px solid #ddd;
	border-radius: 6px;
	background: #fff;
}

.status-card.active {
	border-color: #00a32a;
	background: #f0f6fc;
}

.status-card .status-icon {
	font-size: 24px;
	margin-bottom: 10px;
}

.strategy-options {
	display: flex;
	flex-direction: column;
	gap: 15px;
	margin-top: 15px;
}

.strategy-option {
	display: block;
	padding: 20px;
	border: 2px solid #ddd;
	border-radius: 6px;
	cursor: pointer;
	transition: border-color 0.2s;
}

.strategy-option:hover {
	border-color: #2271b1;
}

.strategy-option input[type="radio"]:checked + .strategy-content {
	border-left: 4px solid #2271b1;
	padding-left: 16px;
}

.strategy-features {
	display: flex;
	flex-wrap: wrap;
	gap: 10px;
	margin-top: 10px;
}

.strategy-features .feature {
	padding: 4px 8px;
	background: #f1f1f1;
	border-radius: 3px;
	font-size: 12px;
}

.progress-bar {
	width: 100%;
	height: 20px;
	background: #f1f1f1;
	border-radius: 10px;
	overflow: hidden;
	margin: 10px 0;
}

.progress-fill {
	height: 100%;
	background: #00a32a;
	transition: width 0.3s;
}

.migration-section {
	background: #f9f9f9;
	padding: 20px;
	border-radius: 6px;
	margin-top: 20px;
}
</style>

<script>
jQuery(document).ready(function($) {
	// Toggle strategy section and additional options
	$('#offloading_enabled').on('change', function() {
		if ($(this).is(':checked')) {
			$('.strategy-section').slideDown();
			$('.additional-options').slideDown();
		} else {
			$('.strategy-section').slideUp();
			$('.additional-options').slideUp();
		}
	});

	// Migration start
	$('#start-migration').on('click', function() {
		if (confirm('<?php esc_html_e( 'Are you sure you want to start the migration? This process may take some time.', 'offload-dlx-plus' ); ?>')) {
			// TODO: Implement migration start
			alert('<?php esc_html_e( 'Migration functionality will be implemented in the next phase.', 'offload-dlx-plus' ); ?>');
		}
	});
});
</script>
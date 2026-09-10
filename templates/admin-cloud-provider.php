<?php
/**
 * Admin: Cloud Provider tab template.
 *
 * Local variables ($config, $is_configured, etc.) are populated by
 * Admin::render_tab_content() in the calling scope. Suppress the prefix sniff:
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
$is_configured   = $template_data['is_configured'] ?? false;
$cloud_stats     = $template_data['cloud_stats'] ?? null;
$has_files_in_db = $template_data['has_files_in_db'] ?? false;
?>

<div class="offload-plus-settings">
	<?php
	// Delete Provider button visible only before offloading is active (disconnect first via Sync tab)
	$can_delete_provider = in_array( $current_state, array( 'configured', 'syncing', 'synced' ), true );

	// Mask credentials for read-only display
	$provider_name         = $config['cloud_provider'] ?? '';
	$provider_display_name = '';
	$masked_key            = '';
	$account_name          = '';
	$container_name_val    = '';
	if ( $provider_name === 'diluxone' ) {
		$provider_display_name = 'Dilux One Cloud';
		$api_key               = $config['provider_config']['api_key'] ?? '';
		$masked_key            = strlen( $api_key ) > 12
			? substr( $api_key, 0, 8 ) . '...' . substr( $api_key, -4 )
			: '****';
	} elseif ( $provider_name === 'azure' ) {
		$provider_display_name = 'Microsoft Azure Blob Storage';
		$account_name          = $config['provider_config']['storage_account'] ?? $config['account_name'] ?? '';
		$container_name_val    = $config['provider_config']['container_name'] ?? $config['container_name'] ?? '';
	}

	// Show success/error messages produced by the admin_post handler that
	// already verified its own nonce and redirected back here. The reads below
	// are display-only and never trigger side effects.
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display of message redirected back from a nonce-verified admin_post handler.
	if ( isset( $_GET['success'] ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- See above.
		$offload_plus_msg = sanitize_text_field( wp_unslash( $_GET['success'] ) );
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $offload_plus_msg ) . '</p></div>';
	}
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only display of message redirected back from a nonce-verified admin_post handler.
	if ( isset( $_GET['error'] ) ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- See above.
		$offload_plus_msg = sanitize_text_field( wp_unslash( $_GET['error'] ) );
		echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $offload_plus_msg ) . '</p></div>';
	}
	?>

	<?php if ( ! $is_configured ) : ?>
		<!-- ====================================================================
			STATE: NOT CONFIGURED — Full configuration form
			==================================================================== -->
		<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
			<?php wp_nonce_field( 'offload_plus_save_config' ); ?>
			<input type="hidden" name="action" value="offload_plus_save_config">
			<input type="hidden" name="redirect_tab" value="cloud-provider">

			<!-- Cloud Provider Selection -->
			<div class="settings-section">
				<h3><?php esc_html_e( 'Cloud Provider Configuration', 'offload-plus' ); ?></h3>
				<p class="description">
					<?php esc_html_e( 'Select your cloud storage provider and configure the connection settings.', 'offload-plus' ); ?>
				</p>

				<table class="form-table">
					<tr>
						<th scope="row">
							<label for="cloud_provider"><?php esc_html_e( 'Cloud Storage Provider', 'offload-plus' ); ?></label>
						</th>
						<td>
							<select id="cloud_provider" name="cloud_provider" class="regular-text" onchange="showProviderConfig(this.value)">
								<option value=""><?php esc_html_e( 'Select a provider...', 'offload-plus' ); ?></option>
								<option value="diluxone" <?php selected( $config['cloud_provider'] ?? '', 'diluxone' ); ?>>
									<?php esc_html_e( 'Dilux One Cloud (Recommended)', 'offload-plus' ); ?>
								</option>
								<option value="azure" <?php selected( $config['cloud_provider'] ?? '', 'azure' ); ?>>
									<?php esc_html_e( 'Microsoft Azure Blob Storage', 'offload-plus' ); ?>
								</option>
							</select>
							<p class="description">
								<?php esc_html_e( 'Choose your preferred cloud storage provider. Configuration options will appear below.', 'offload-plus' ); ?>
							</p>
						</td>
					</tr>
				</table>
			</div>

			<!-- Dilux One Config -->
			<div class="settings-section provider-config" id="diluxone-config" style="<?php echo ( $config['cloud_provider'] ?? '' ) === 'diluxone' ? '' : 'display: none;'; ?>">
				<h3><?php esc_html_e( 'Dilux One Cloud', 'offload-plus' ); ?></h3>
				<p class="description">
					<?php
					echo wp_kses(
						sprintf(
							/* translators: 1: opening anchor tag, 2: closing anchor tag */
							__( 'Enter your API Key from your Dilux One account. Don\'t have one? %1$sGet started%2$s', 'offload-plus' ),
							'<a href="https://diluxone.com/" target="_blank" rel="noopener noreferrer">',
							'</a>'
						),
						array(
							'a' => array(
								'href'   => true,
								'target' => true,
								'rel'    => true,
							),
						)
					);
					?>
				</p>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="api_key"><?php esc_html_e( 'API Key', 'offload-plus' ); ?></label></th>
						<td>
							<input type="password" id="api_key" name="api_key"
									value="<?php echo esc_attr( $config['provider_config']['api_key'] ?? '' ); ?>"
									class="large-text" required>
							<p class="description"><?php esc_html_e( 'Your Dilux One Cloud API Key (starts with dok_).', 'offload-plus' ); ?></p>
						</td>
					</tr>
				</table>
				<div class="test-connection-section">
					<button type="button" class="button button-secondary test-connection-btn">
						<span class="dashicons dashicons-admin-links"></span>
						<?php esc_html_e( 'Test Connection', 'offload-plus' ); ?>
					</button>
					<div class="connection-result"></div>
					<p class="test-status-message description" style="margin-top: 8px; color: #d63638; font-weight: 600;">
						<?php esc_html_e( 'You must test the connection successfully before saving credentials.', 'offload-plus' ); ?>
					</p>
				</div>
			</div>

			<!-- Azure Config -->
			<div class="settings-section provider-config" id="azure-config" style="<?php echo ( $config['cloud_provider'] ?? '' ) === 'azure' ? '' : 'display: none;'; ?>">
				<h3><?php esc_html_e( 'Azure Blob Storage', 'offload-plus' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Enter your Azure Storage credentials.', 'offload-plus' ); ?></p>
				<table class="form-table">
					<tr>
						<th scope="row"><label for="account_name"><?php esc_html_e( 'Storage Account Name', 'offload-plus' ); ?></label></th>
						<td>
							<input type="text" id="account_name" name="account_name"
									value="<?php echo esc_attr( $config['provider_config']['storage_account'] ?? $config['account_name'] ?? '' ); ?>"
									class="regular-text" required>
							<p class="description"><?php esc_html_e( 'Your storage account name (3-24 lowercase characters and numbers only).', 'offload-plus' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="account_key"><?php esc_html_e( 'Account Key', 'offload-plus' ); ?></label></th>
						<td>
							<input type="password" id="account_key" name="account_key"
									value="<?php echo esc_attr( $config['provider_config']['access_key'] ?? $config['account_key'] ?? '' ); ?>"
									class="large-text" required>
							<p class="description"><?php esc_html_e( 'Primary or secondary access key from your storage account.', 'offload-plus' ); ?></p>
						</td>
					</tr>
					<tr>
						<th scope="row"><label for="container_name"><?php esc_html_e( 'Container Name', 'offload-plus' ); ?></label></th>
						<td>
							<input type="text" id="container_name" name="container_name"
									value="<?php echo esc_attr( $config['provider_config']['container_name'] ?? $config['container_name'] ?? '' ); ?>"
									class="regular-text" required>
							<p class="description"><?php esc_html_e( 'Container name for storing your media files.', 'offload-plus' ); ?></p>
						</td>
					</tr>
				</table>
				<div class="test-connection-section">
					<button type="button" class="button button-secondary test-connection-btn">
						<span class="dashicons dashicons-admin-links"></span>
						<?php esc_html_e( 'Test Connection', 'offload-plus' ); ?>
					</button>
					<div class="connection-result"></div>
					<p class="test-status-message description" style="margin-top: 8px; color: #d63638; font-weight: 600;">
						<?php esc_html_e( 'You must test the connection successfully before saving credentials.', 'offload-plus' ); ?>
					</p>
				</div>
			</div>

			<!-- Save button -->
			<div class="submit-section">
				<button type="submit" name="submit" id="submit" class="button button-primary" disabled>
					<?php esc_html_e( 'Save Cloud Provider', 'offload-plus' ); ?>
				</button>
			</div>
		</form>

	<?php else : ?>
		<!-- ====================================================================
			STATE: CONFIGURED+ — Read-only info + Stats + Actions
			==================================================================== -->

		<!-- Section 1: Provider Info (read-only) -->
		<div class="settings-section" id="provider-info">
			<h3><?php esc_html_e( 'Cloud Storage Provider', 'offload-plus' ); ?></h3>
			<table class="form-table offload-plus-provider-info">
				<tr>
					<th scope="row"><?php esc_html_e( 'Provider', 'offload-plus' ); ?></th>
					<td><strong><?php echo esc_html( $provider_display_name ); ?></strong></td>
				</tr>
				<?php if ( $provider_name === 'diluxone' ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'API Key', 'offload-plus' ); ?></th>
					<td><code><?php echo esc_html( $masked_key ); ?></code></td>
				</tr>
				<?php elseif ( $provider_name === 'azure' ) : ?>
				<tr>
					<th scope="row"><?php esc_html_e( 'Storage Account', 'offload-plus' ); ?></th>
					<td><code><?php echo esc_html( $account_name ); ?></code></td>
				</tr>
				<tr>
					<th scope="row"><?php esc_html_e( 'Container', 'offload-plus' ); ?></th>
					<td><code><?php echo esc_html( $container_name_val ); ?></code></td>
				</tr>
				<?php endif; ?>
			</table>
			<?php if ( $current_state === 'configured' ) : ?>
			<div style="margin-top: 15px; padding: 15px; background: #f0f6fc; border-left: 4px solid #2271b1; border-radius: 4px;">
				<p style="margin: 0 0 10px 0;">
					<?php esc_html_e( 'Your cloud provider is configured. Start syncing your media files to the cloud.', 'offload-plus' ); ?>
				</p>
				<a href="?page=offload-plus&tab=sync-offloading&auto-start=1" class="button button-primary">
					<span class="dashicons dashicons-cloud-upload" style="vertical-align: middle;"></span>
					<?php esc_html_e( 'Sync Files to Cloud', 'offload-plus' ); ?>
				</a>
			</div>
			<?php endif; ?>
		</div>

		<!-- Section 2: Actions -->
		<div class="settings-section">
			<h3><?php esc_html_e( 'Configuration', 'offload-plus' ); ?></h3>
			<p class="description">
				<?php esc_html_e( 'Update your credentials or remove the cloud provider configuration.', 'offload-plus' ); ?>
			</p>
			<div style="display: flex; gap: 10px; margin-top: 15px;">
				<button type="button" class="button button-secondary update-credentials-trigger">
					<span class="dashicons dashicons-update" style="vertical-align: middle;"></span>
					<?php esc_html_e( 'Update Key', 'offload-plus' ); ?>
				</button>
				<?php if ( $can_delete_provider ) : ?>
				<button type="button" id="remove-provider" class="button button-secondary" style="color: #d63638; border-color: #d63638;">
					<?php esc_html_e( 'Delete Cloud Provider', 'offload-plus' ); ?>
				</button>
				<?php endif; ?>
			</div>
		</div>
	<?php endif; ?>

	<!-- Remove Provider Modal -->
	<div id="remove-provider-modal" class="offload-plus-modal" style="display: none;">
		<div class="offload-plus-modal-overlay"></div>
		<div class="offload-plus-modal-content">
			<h3><?php esc_html_e( 'Delete Cloud Provider Configuration', 'offload-plus' ); ?></h3>
			<p><?php esc_html_e( 'Are you sure you want to delete your cloud storage configuration?', 'offload-plus' ); ?></p>
			<p><?php esc_html_e( 'This will remove all saved credentials and reset the plugin.', 'offload-plus' ); ?></p>
			<p><strong><?php esc_html_e( 'This action cannot be undone.', 'offload-plus' ); ?></strong></p>
			<div class="modal-buttons">
				<button type="button" id="confirm-delete-provider" class="button" style="background: #d63638; border-color: #d63638; color: #fff;">
					<span class="button-text"><?php esc_html_e( 'Yes, Delete Configuration', 'offload-plus' ); ?></span>
					<span class="spinner" style="display: none; float: none; margin: 0 0 0 8px;"></span>
				</button>
				<button type="button" class="button button-secondary cancel-remove" style="margin-left: 10px;">
					<?php esc_html_e( 'Cancel', 'offload-plus' ); ?>
				</button>
			</div>
		</div>
	</div>

	<!-- Update Credentials Modal -->
	<div id="update-credentials-modal" class="offload-plus-modal" style="display: none;">
		<div class="offload-plus-modal-overlay"></div>
		<div class="offload-plus-modal-content update-credentials-modal">
			<h3>
				<?php esc_html_e( 'Update Cloud Provider Credentials', 'offload-plus' ); ?>
				<button type="button" class="modal-close" style="float: right; background: none; border: none; font-size: 24px; cursor: pointer; line-height: 1;">&times;</button>
			</h3>

			<div style="background: #fff3cd; border-left: 4px solid #f0b849; padding: 12px; margin: 15px 0; border-radius: 4px;">
				<p style="margin: 0; color: #856404;">
					<strong><?php esc_html_e( 'WARNING:', 'offload-plus' ); ?></strong>
					<?php esc_html_e( 'Updating the access key will temporarily interrupt file operations while testing the new connection. Current uploads/downloads may fail.', 'offload-plus' ); ?>
				</p>
			</div>

			<!-- Azure fields -->
			<div id="modal-azure-fields" style="<?php echo ( $config['cloud_provider'] ?? '' ) === 'diluxone' ? 'display: none;' : ''; ?>">
				<div style="background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; padding: 12px; margin: 15px 0;">
					<p style="margin: 0 0 8px 0; font-size: 13px; color: #666;">
						<strong><?php esc_html_e( 'Storage Account:', 'offload-plus' ); ?></strong>
						<span id="modal_account_name" style="color: #333; font-family: monospace;">
							<?php echo esc_html( $config['provider_config']['storage_account'] ?? $config['account_name'] ?? '' ); ?>
						</span>
					</p>
					<p style="margin: 0; font-size: 13px; color: #666;">
						<strong><?php esc_html_e( 'Container:', 'offload-plus' ); ?></strong>
						<span id="modal_container_name" style="color: #333; font-family: monospace;">
							<?php echo esc_html( $config['provider_config']['container_name'] ?? $config['container_name'] ?? '' ); ?>
						</span>
					</p>
				</div>

				<table class="form-table" style="margin-top: 15px;">
					<tr>
						<th scope="row">
							<label for="modal_account_key"><?php esc_html_e( 'New Account Key', 'offload-plus' ); ?></label>
						</th>
						<td>
							<input type="password"
									id="modal_account_key"
									value=""
									class="large-text"
									required
									placeholder="<?php esc_attr_e( 'Enter new access key', 'offload-plus' ); ?>">
						</td>
					</tr>
				</table>
			</div>

			<!-- DiluxOne fields -->
			<div id="modal-diluxone-fields" style="<?php echo ( $config['cloud_provider'] ?? '' ) === 'diluxone' ? '' : 'display: none;'; ?>">
				<div style="background: #f9f9f9; border: 1px solid #ddd; border-radius: 4px; padding: 12px; margin: 15px 0;">
					<p style="margin: 0; font-size: 13px; color: #666;">
						<strong><?php esc_html_e( 'Provider:', 'offload-plus' ); ?></strong>
						<span style="color: #333;">Dilux One Cloud</span>
					</p>
				</div>

				<table class="form-table" style="margin-top: 15px;">
					<tr>
						<th scope="row">
							<label for="modal_api_key"><?php esc_html_e( 'New API Key', 'offload-plus' ); ?></label>
						</th>
						<td>
							<input type="password"
									id="modal_api_key"
									value=""
									class="large-text"
									required
									placeholder="<?php esc_attr_e( 'Enter new API key (dok_...)', 'offload-plus' ); ?>">
						</td>
					</tr>
				</table>
			</div>

			<table class="form-table">
				<tr>
					<th scope="row"></th>
					<td>
						<label style="display: inline-block;">
							<input type="checkbox" id="modal_show_key">
							<?php esc_html_e( 'Show key', 'offload-plus' ); ?>
						</label>
					</td>
				</tr>

				<tr>
					<th scope="row"></th>
					<td>
						<button type="button" id="modal-test-connection" class="button button-secondary">
							<span class="dashicons dashicons-admin-links"></span>
							<?php esc_html_e( 'Test Connection', 'offload-plus' ); ?>
						</button>
					</td>
				</tr>
				<tr>
					<th scope="row"></th>
					<td>
						<div id="modal-connection-result" style="margin-top: 10px; display: block !important; visibility: visible !important;"></div>
					</td>
				</tr>
			</table>

			<div class="modal-buttons" style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd;">
				<p class="description" style="float: left; margin: 8px 0;">
					<?php esc_html_e( 'You must test the connection before saving.', 'offload-plus' ); ?>
				</p>
				<button type="button" class="button button-secondary modal-close">
					<?php esc_html_e( 'Cancel', 'offload-plus' ); ?>
				</button>
				<button type="button" id="modal-save-credentials" class="button button-primary" disabled style="margin-left: 10px;">
					<?php esc_html_e( 'Save', 'offload-plus' ); ?>
				</button>
			</div>
		</div>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	// ========================================================================
	// Helper: Get current provider
	// ========================================================================
	function getCurrentProvider() {
		var $select = $('#cloud_provider');
		return $select.length ? $select.val() : '<?php echo esc_js( $config['cloud_provider'] ?? '' ); ?>';
	}

	// ========================================================================
	// Test Connection (for NOT_CONFIGURED state only)
	// ========================================================================
	$(document).on('click', '.test-connection-btn', function() {
		var $button = $(this);
		var $section = $button.closest('.provider-config');
		var $result = $section.find('.connection-result');
		var provider = getCurrentProvider();

		var data = {
			action: 'offload_plus_test_connection',
			nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin' ) ); ?>',
			provider: provider
		};

		if (provider === 'diluxone') {
			data.api_key = $section.find('#api_key').val() || $('#api_key').val();
			if (!data.api_key) {
				$result.html('<div style="padding: 10px; background: #f8d7da; border-left: 3px solid #dc3545; color: #721c24; border-radius: 3px;"><strong><?php echo esc_js( __( 'Connection Failed', 'offload-plus' ) ); ?></strong><br><?php echo esc_js( __( 'Please enter the API Key.', 'offload-plus' ) ); ?></div>').show();
				return;
			}
		} else {
			data.account_name = $('#account_name').val();
			data.account_key = $('#account_key').val();
			data.container_name = $('#container_name').val();
			if (!data.account_name || !data.account_key || !data.container_name) {
				$result.html('<div style="padding: 10px; background: #f8d7da; border-left: 3px solid #dc3545; color: #721c24; border-radius: 3px;"><strong><?php echo esc_js( __( 'Connection Failed', 'offload-plus' ) ); ?></strong><br><?php echo esc_js( __( 'Please fill in all required fields.', 'offload-plus' ) ); ?></div>').show();
				return;
			}
		}

		$button.prop('disabled', true);
		$button.html('<span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span><?php echo esc_js( __( 'Testing...', 'offload-plus' ) ); ?>');
		$result.empty();

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: data,
			success: function(response) {
				$button.prop('disabled', false);
				$button.html('<span class="dashicons dashicons-admin-links"></span><?php echo esc_js( __( 'Test Connection', 'offload-plus' ) ); ?>');
				if (response.success) {
					$result.html('<div style="padding: 10px; background: #d4edda; border-left: 3px solid #28a745; color: #155724; border-radius: 3px;"><strong><?php echo esc_js( __( 'Connection Successful', 'offload-plus' ) ); ?></strong><br>' + (response.data.message || '') + '</div>').show();
					$('#submit').prop('disabled', false);
				} else {
					$result.html('<div style="padding: 10px; background: #f8d7da; border-left: 3px solid #dc3545; color: #721c24; border-radius: 3px;"><strong><?php echo esc_js( __( 'Connection Failed', 'offload-plus' ) ); ?></strong><br>' + (response.data.message || '') + '</div>').show();
				}
			},
			error: function(xhr, status, error) {
				$button.prop('disabled', false);
				$button.html('<span class="dashicons dashicons-admin-links"></span><?php echo esc_js( __( 'Test Connection', 'offload-plus' ) ); ?>');
				$result.html('<div style="padding: 10px; background: #f8d7da; border-left: 3px solid #dc3545; color: #721c24; border-radius: 3px;"><strong><?php echo esc_js( __( 'Connection Failed', 'offload-plus' ) ); ?></strong><br>Error: ' + error + '</div>').show();
			}
		});
	});

	// ========================================================================
	// Form validation (NOT_CONFIGURED state only)
	// ========================================================================
	$('form').on('submit', function(e) {
		var provider = getCurrentProvider();
		if (provider === 'azure') {
			var accountName = $('#account_name').val();
			var containerName = $('#container_name').val();
			if (accountName && !/^[a-z0-9]{3,24}$/.test(accountName)) {
				alert('<?php echo esc_js( __( 'Storage Account Name must be 3-24 characters long and contain only lowercase letters and numbers.', 'offload-plus' ) ); ?>');
				e.preventDefault();
				return false;
			}
			if (containerName && !/^[a-z0-9]([a-z0-9\-]*[a-z0-9])?$/.test(containerName)) {
				alert('<?php echo esc_js( __( 'Container Name must contain only lowercase letters, numbers, and hyphens.', 'offload-plus' ) ); ?>');
				e.preventDefault();
				return false;
			}
		}
	});

	// ========================================================================
	// Remove Provider Modal
	// ========================================================================
	$('#remove-provider').on('click', function() {
		$('#remove-provider-modal').show();
	});

	$('.cancel-remove, #remove-provider-modal .offload-plus-modal-overlay').on('click', function() {
		$('#remove-provider-modal').hide();
	});

	$('#confirm-delete-provider').on('click', function() {
		var $button = $(this);
		var $buttonText = $button.find('.button-text');
		var $spinner = $button.find('.spinner');

		$button.prop('disabled', true).css('opacity', '0.6');
		$buttonText.text('<?php echo esc_js( __( 'Deleting configuration...', 'offload-plus' ) ); ?>');
		$spinner.css('visibility', 'visible').show();

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'offload_plus_ajax_remove_provider',
				nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin' ) ); ?>'
			},
			success: function(response) {
				if (response.success) {
					window.location.reload();
				} else {
					alert('Error: ' + (response.data.message || 'Unknown error'));
					$button.prop('disabled', false).css('opacity', '1');
					$buttonText.text('<?php echo esc_js( __( 'Yes, Delete Configuration', 'offload-plus' ) ); ?>');
					$spinner.hide();
				}
			},
			error: function(xhr, status, error) {
				alert('Error deleting configuration: ' + error);
				$button.prop('disabled', false).css('opacity', '1');
				$buttonText.text('<?php echo esc_js( __( 'Yes, Delete Configuration', 'offload-plus' ) ); ?>');
				$spinner.hide();
			}
		});
	});

	// ========================================================================
	// Update Credentials Modal (provider-aware)
	// ========================================================================
	$(document).on('click', '.update-credentials-trigger', function() {
		var provider = getCurrentProvider();
		if (provider === 'diluxone') {
			$('#modal-azure-fields').hide();
			$('#modal-diluxone-fields').show();
		} else {
			$('#modal-azure-fields').show();
			$('#modal-diluxone-fields').hide();
		}
		$('#update-credentials-modal').show();
		$('#modal-connection-result').empty();
		$('#modal-save-credentials').prop('disabled', true);
	});

	$('.modal-close, #update-credentials-modal .offload-plus-modal-overlay').on('click', function() {
		$('#update-credentials-modal').hide();
		$('#modal_account_key').val('');
		$('#modal_api_key').val('');
		$('#modal-connection-result').empty();
		$('#modal-save-credentials').prop('disabled', true);
	});

	$('#modal_show_key').on('change', function() {
		var $keyInput = getCurrentProvider() === 'diluxone' ? $('#modal_api_key') : $('#modal_account_key');
		$keyInput.attr('type', $(this).is(':checked') ? 'text' : 'password');
	});

	$('#modal-test-connection').on('click', function() {
		var $button = $(this);
		var $result = $('#modal-connection-result');
		var provider = getCurrentProvider();

		var data = {
			action: 'offload_plus_test_connection',
			nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin' ) ); ?>',
			provider: provider
		};

		if (provider === 'diluxone') {
			data.api_key = $('#modal_api_key').val();
			if (!data.api_key) {
				$result.html('<div style="padding: 10px; background: #f8d7da; border-left: 3px solid #dc3545; color: #721c24; border-radius: 3px;"><?php echo esc_js( __( 'Please enter the new API Key.', 'offload-plus' ) ); ?></div>');
				return;
			}
		} else {
			data.account_name = $('#modal_account_name').text().trim();
			data.account_key = $('#modal_account_key').val();
			data.container_name = $('#modal_container_name').text().trim();
			if (!data.account_key) {
				$result.html('<div style="padding: 10px; background: #f8d7da; border-left: 3px solid #dc3545; color: #721c24; border-radius: 3px;"><?php echo esc_js( __( 'Please enter the new access key.', 'offload-plus' ) ); ?></div>');
				return;
			}
		}

		$button.prop('disabled', true);
		$button.html('<span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span><?php echo esc_js( __( 'Testing...', 'offload-plus' ) ); ?>');
		$result.empty();

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: data,
			success: function(response) {
				$button.prop('disabled', false);
				$button.html('<span class="dashicons dashicons-admin-links"></span><?php echo esc_js( __( 'Test Connection', 'offload-plus' ) ); ?>');
				if (response.success) {
					$result.html('<div style="padding: 10px; background: #d4edda; border-left: 3px solid #28a745; color: #155724; border-radius: 3px;"><strong><?php echo esc_js( __( 'Connection Successful', 'offload-plus' ) ); ?></strong><br>' + (response.data.message || '') + '</div>');
					$('#modal-save-credentials').prop('disabled', false);
				} else {
					$result.html('<div style="padding: 10px; background: #f8d7da; border-left: 3px solid #dc3545; color: #721c24; border-radius: 3px;"><strong><?php echo esc_js( __( 'Connection Failed', 'offload-plus' ) ); ?></strong><br>' + (response.data.message || '') + '</div>');
					$('#modal-save-credentials').prop('disabled', true);
				}
			},
			error: function(xhr, status, error) {
				$button.prop('disabled', false);
				$button.html('<span class="dashicons dashicons-admin-links"></span><?php echo esc_js( __( 'Test Connection', 'offload-plus' ) ); ?>');
				$result.html('<div style="padding: 10px; background: #f8d7da; border-left: 3px solid #dc3545; color: #721c24; border-radius: 3px;">Error: ' + error + '</div>');
				$('#modal-save-credentials').prop('disabled', true);
			}
		});
	});

	$('#modal-save-credentials').on('click', function() {
		var $button = $(this);
		var provider = getCurrentProvider();

		var data = {
			action: 'offload_plus_save_updated_credentials',
			nonce: '<?php echo esc_js( wp_create_nonce( 'offload_plus_admin' ) ); ?>',
			provider: provider
		};

		if (provider === 'diluxone') {
			data.api_key = $('#modal_api_key').val();
		} else {
			data.account_name = $('#modal_account_name').text().trim();
			data.account_key = $('#modal_account_key').val();
			data.container_name = $('#modal_container_name').text().trim();
		}

		$button.prop('disabled', true);
		$button.html('<span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span><?php echo esc_js( __( 'Saving...', 'offload-plus' ) ); ?>');

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: data,
			success: function(response) {
				if (response.success) {
					var successMsg = encodeURIComponent(response.data.message || 'Credentials updated successfully');
					window.location.href = window.location.pathname + '?page=offload-plus&tab=cloud-provider&success=' + successMsg;
				} else {
					alert('Error: ' + (response.data.message || 'Unknown error'));
					$button.prop('disabled', false);
					$button.html('<?php echo esc_js( __( 'Save', 'offload-plus' ) ); ?>');
				}
			},
			error: function(xhr, status, error) {
				alert('Error saving credentials: ' + error);
				$button.prop('disabled', false);
				$button.html('<?php echo esc_js( __( 'Save', 'offload-plus' ) ); ?>');
			}
		});
	});

});

// Provider config toggle (NOT_CONFIGURED state only)
function showProviderConfig(provider) {
	var configs = document.querySelectorAll('.provider-config');
	configs.forEach(function(el) {
		el.style.display = 'none';
		// Remove required from hidden fields to prevent browser validation errors
		el.querySelectorAll('[required]').forEach(function(input) {
			input.removeAttribute('required');
		});
	});
	if (provider) {
		var selected = document.getElementById(provider + '-config');
		if (selected) {
			selected.style.display = 'block';
			// Restore required on visible fields
			selected.querySelectorAll('input[name]').forEach(function(input) {
				input.setAttribute('required', '');
			});
		}
	}
}

document.addEventListener('DOMContentLoaded', function() {
	var select = document.getElementById('cloud_provider');
	if (select) showProviderConfig(select.value);
});
</script>

<style>
.offload-plus-settings {
	max-width: 800px;
}

.settings-section {
	background: #fff;
	border: 1px solid #ddd;
	border-radius: 8px;
	padding: 20px;
	margin-bottom: 30px;
}

.settings-section h3 {
	margin-top: 0;
	padding-bottom: 10px;
	border-bottom: 1px solid #eee;
	color: #333;
}

.test-connection-section {
	margin-top: 20px;
	padding: 15px;
	background: #f9f9f9;
	border-radius: 5px;
}

.connection-result {
	margin-top: 15px;
}

.connection-result .notice {
	margin: 0;
	padding: 10px 15px;
}

.submit-section {
	background: #fff;
	border: 1px solid #ddd;
	border-radius: 8px;
	padding: 20px;
	text-align: right;
}

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
	color: #dc3545;
}

.update-credentials-modal {
	max-width: 600px;
}

.update-credentials-modal h3 {
	color: #f0b849;
	border-bottom: 2px solid #f0b849;
	padding-bottom: 10px;
}

.update-credentials-modal .form-table th {
	padding-left: 0;
}

.update-credentials-modal .connection-result,
#modal-connection-result {
	margin-top: 10px;
	display: block !important;
	visibility: visible !important;
	min-height: 0;
}

.modal-buttons {
	margin-top: 20px;
	text-align: right;
}

.modal-buttons .button {
	margin-left: 10px;
}

@media (max-width: 768px) {
	.offload-plus-settings {
		max-width: 100%;
	}

	.offload-plus-modal-content {
		margin: 20px;
		width: calc(100% - 40px);
		max-width: none;
	}

	.submit-section {
		text-align: center;
	}

	.submit-section .button {
		margin: 5px;
		display: block;
		width: 100%;
	}

	.modal-buttons {
		text-align: center;
	}

	.modal-buttons .button {
		display: block;
		width: 100%;
		margin: 5px 0;
	}

	/* Provider config sections */
	.provider-config {
		border: 1px solid #ddd;
		border-radius: 5px;
		padding: 15px;
		background: #f9f9f9;
	}
}
</style>

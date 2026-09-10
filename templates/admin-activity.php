<?php
/**
 * Admin: Activity tab template.
 *
 * Local variables ($activity_stats, $current_page, $activity_type, etc.) are
 * populated by Admin::render_tab_content() in the calling scope. Suppress the
 * prefix sniff for this template:
 *
 * phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
 *
 * @package OffloadDlxPlus
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use OffloadDlxPlus\ConfigManager;

// Variables populated by Admin::render_tab_content() via extract( $template_data ).
// Initialise defensively so static analysis sees a definite type and a stray
// direct include cannot crash on undefined indexes.
$activity_stats = $activity_stats ?? array();
$chart_data     = $chart_data ?? array();

// Read-only filter inputs for the activity-log table. The page is reachable
// only by users with manage_options; these $_GET reads do not change state.
// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only filters; no state change.
// phpcs:disable WordPress.WP.GlobalVariablesOverride.Prohibited -- $per_page is a local template variable, not a WordPress global; the rule false-positives because WP also has a $per_page global of the same name in the admin-list-table context.
$per_page     = 50;
$current_page = isset( $_GET['paged'] ) ? max( 1, intval( wp_unslash( $_GET['paged'] ) ) ) : 1;
$offset       = ( $current_page - 1 ) * $per_page;

// Activity type filter
$activity_type = isset( $_GET['activity_type'] ) ? sanitize_text_field( wp_unslash( $_GET['activity_type'] ) ) : '';

// Date range filter
$date_from = isset( $_GET['date_from'] ) ? sanitize_text_field( wp_unslash( $_GET['date_from'] ) ) : '';
$date_to   = isset( $_GET['date_to'] ) ? sanitize_text_field( wp_unslash( $_GET['date_to'] ) ) : '';
// phpcs:enable WordPress.Security.NonceVerification.Recommended
?>

<div class="offload-dlx-plus-activity">
	<div class="offload-dlx-plus-header">
		<h2><?php esc_html_e( 'Activity Monitoring', 'offload-dlx-plus' ); ?></h2>
		<p class="description">
			<?php esc_html_e( 'Monitor cloud storage operations and system activity logs.', 'offload-dlx-plus' ); ?>
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
						__( 'Please configure your cloud storage settings in the <a href="%s">Settings tab</a> before viewing activity logs.', 'offload-dlx-plus' ),
						esc_url( admin_url( 'admin.php?page=offload-dlx-plus&tab=settings' ) )
					),
					array( 'a' => array( 'href' => true ) )
				);
				?>
			</p>
		</div>
	<?php else : ?>

	<div class="activity-content">
	<div class="activity-filters">
		<h3><?php esc_html_e( 'Activity Filters', 'offload-dlx-plus' ); ?></h3>
		
		<form method="get" class="filters-form">
			<input type="hidden" name="page" value="offload-dlx-plus" />
			<input type="hidden" name="tab" value="activity" />
			
			<div class="filter-row">
				<div class="filter-group">
					<label for="activity_type"><?php esc_html_e( 'Activity Type:', 'offload-dlx-plus' ); ?></label>
					<select name="activity_type" id="activity_type">
						<option value=""><?php esc_html_e( 'All Types', 'offload-dlx-plus' ); ?></option>
						<option value="upload" <?php selected( $activity_type, 'upload' ); ?>>
							<?php esc_html_e( 'File Upload', 'offload-dlx-plus' ); ?>
						</option>
						<option value="delete" <?php selected( $activity_type, 'delete' ); ?>>
							<?php esc_html_e( 'File Delete', 'offload-dlx-plus' ); ?>
						</option>
						<option value="migration" <?php selected( $activity_type, 'migration' ); ?>>
							<?php esc_html_e( 'Migration', 'offload-dlx-plus' ); ?>
						</option>
						<option value="config" <?php selected( $activity_type, 'config' ); ?>>
							<?php esc_html_e( 'Configuration', 'offload-dlx-plus' ); ?>
						</option>
						<option value="error" <?php selected( $activity_type, 'error' ); ?>>
							<?php esc_html_e( 'Error', 'offload-dlx-plus' ); ?>
						</option>
					</select>
				</div>
				
				<div class="filter-group">
					<label for="date_from"><?php esc_html_e( 'From Date:', 'offload-dlx-plus' ); ?></label>
					<input type="date" name="date_from" id="date_from" value="<?php echo esc_attr( $date_from ); ?>" />
				</div>
				
				<div class="filter-group">
					<label for="date_to"><?php esc_html_e( 'To Date:', 'offload-dlx-plus' ); ?></label>
					<input type="date" name="date_to" id="date_to" value="<?php echo esc_attr( $date_to ); ?>" />
				</div>
				
				<div class="filter-actions">
					<button type="submit" class="button button-primary"><?php esc_html_e( 'Filter', 'offload-dlx-plus' ); ?></button>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=offload-dlx-plus&tab=activity' ) ); ?>"
						class="button"><?php esc_html_e( 'Reset', 'offload-dlx-plus' ); ?></a>
				</div>
			</div>
		</form>
	</div>

	<div class="activity-statistics">
		<h3><?php esc_html_e( 'Activity Statistics', 'offload-dlx-plus' ); ?></h3>
		
		<div class="stats-cards">
			<div class="stat-card">
				<div class="stat-number"><?php echo esc_html( number_format( $activity_stats['total_today'] ) ); ?></div>
				<div class="stat-label"><?php esc_html_e( 'Activities Today', 'offload-dlx-plus' ); ?></div>
				<div class="stat-trend">
					<?php if ( $activity_stats['trend_today'] > 0 ) : ?>
						<span class="trend-up">
							<span class="dashicons dashicons-arrow-up-alt"></span>
							<?php echo esc_html( $activity_stats['trend_today'] ); ?>%
						</span>
					<?php elseif ( $activity_stats['trend_today'] < 0 ) : ?>
						<span class="trend-down">
							<span class="dashicons dashicons-arrow-down-alt"></span>
							<?php echo esc_html( (string) abs( $activity_stats['trend_today'] ) ); ?>%
						</span>
					<?php else : ?>
						<span class="trend-neutral">
							<span class="dashicons dashicons-minus"></span>
							0%
						</span>
					<?php endif; ?>
				</div>
			</div>

			<div class="stat-card">
				<div class="stat-number"><?php echo esc_html( number_format( $activity_stats['total_week'] ) ); ?></div>
				<div class="stat-label"><?php esc_html_e( 'This Week', 'offload-dlx-plus' ); ?></div>
				<div class="stat-breakdown">
					<?php
					echo esc_html(
						sprintf(
						/* translators: 1: number of uploads, 2: number of deletions */
							__( '%1$d uploads, %2$d deletions', 'offload-dlx-plus' ),
							$activity_stats['week_uploads'],
							$activity_stats['week_deletions']
						)
					);
					?>
				</div>
			</div>

			<div class="stat-card">
				<div class="stat-number"><?php echo esc_html( number_format( $activity_stats['total_month'] ) ); ?></div>
				<div class="stat-label"><?php esc_html_e( 'This Month', 'offload-dlx-plus' ); ?></div>
				<div class="stat-size"><?php echo esc_html( (string) size_format( $activity_stats['month_size'] ) ); ?></div>
			</div>

			<div class="stat-card">
				<div class="stat-number"><?php echo esc_html( number_format( $activity_stats['errors_count'] ) ); ?></div>
				<div class="stat-label"><?php esc_html_e( 'Errors (24h)', 'offload-dlx-plus' ); ?></div>
				<div class="stat-status <?php echo esc_attr( $activity_stats['errors_count'] > 0 ? 'has-errors' : 'no-errors' ); ?>">
					<?php
					echo esc_html(
						$activity_stats['errors_count'] > 0
						? __( 'Needs attention', 'offload-dlx-plus' )
						: __( 'All good', 'offload-dlx-plus' )
					);
					?>
				</div>
			</div>
		</div>
	</div>

	<div class="activity-chart">
		<h3><?php esc_html_e( 'Activity Chart (Last 30 Days)', 'offload-dlx-plus' ); ?></h3>
		<canvas id="activity-chart" width="800" height="200"></canvas>
	</div>

	<div class="activity-actions">
		<div class="bulk-actions">
			<h3><?php esc_html_e( 'Activity Actions', 'offload-dlx-plus' ); ?></h3>
			
			<div class="action-buttons">
				<button type="button" id="export-activity" class="button button-secondary">
					<span class="dashicons dashicons-download"></span>
					<?php esc_html_e( 'Export Activity Log', 'offload-dlx-plus' ); ?>
				</button>
				
				<button type="button" id="clear-old-logs" class="button button-secondary">
					<span class="dashicons dashicons-trash"></span>
					<?php esc_html_e( 'Clear Old Logs', 'offload-dlx-plus' ); ?>
				</button>
				
				<button type="button" id="refresh-activity" class="button button-secondary">
					<span class="dashicons dashicons-update"></span>
					<?php esc_html_e( 'Refresh', 'offload-dlx-plus' ); ?>
				</button>
			</div>
		</div>
	</div>

	<div class="activity-log">
		<h3><?php esc_html_e( 'Recent Activity', 'offload-dlx-plus' ); ?></h3>
		
		<?php if ( ! empty( $activity_log ) ) : ?>
			<div class="activity-table-container">
				<table class="wp-list-table widefat fixed striped activity-table">
					<thead>
						<tr>
							<th scope="col" class="column-type"><?php esc_html_e( 'Type', 'offload-dlx-plus' ); ?></th>
							<th scope="col" class="column-message"><?php esc_html_e( 'Message', 'offload-dlx-plus' ); ?></th>
							<th scope="col" class="column-file"><?php esc_html_e( 'File', 'offload-dlx-plus' ); ?></th>
							<th scope="col" class="column-size"><?php esc_html_e( 'Size', 'offload-dlx-plus' ); ?></th>
							<th scope="col" class="column-user"><?php esc_html_e( 'User', 'offload-dlx-plus' ); ?></th>
							<th scope="col" class="column-date"><?php esc_html_e( 'Date', 'offload-dlx-plus' ); ?></th>
							<th scope="col" class="column-details"><?php esc_html_e( 'Details', 'offload-dlx-plus' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $activity_log as $log ) : ?>
							<tr class="activity-row activity-<?php echo esc_attr( $log['type'] ); ?>">
								<td class="column-type">
									<span class="activity-icon activity-icon-<?php echo esc_attr( $log['type'] ); ?>">
										<?php
										switch ( $log['type'] ) {
											case 'upload':
												echo '<span class="dashicons dashicons-upload"></span>';
												break;
											case 'delete':
												echo '<span class="dashicons dashicons-trash"></span>';
												break;
											case 'migration':
												echo '<span class="dashicons dashicons-cloud"></span>';
												break;
											case 'config':
												echo '<span class="dashicons dashicons-admin-settings"></span>';
												break;
											case 'error':
												echo '<span class="dashicons dashicons-warning"></span>';
												break;
											default:
												echo '<span class="dashicons dashicons-info"></span>';
										}
										?>
									</span>
									<span class="activity-type-text">
										<?php
										switch ( $log['type'] ) {
											case 'upload':
												esc_html_e( 'Upload', 'offload-dlx-plus' );
												break;
											case 'delete':
												esc_html_e( 'Delete', 'offload-dlx-plus' );
												break;
											case 'migration':
												esc_html_e( 'Migration', 'offload-dlx-plus' );
												break;
											case 'config':
												esc_html_e( 'Config', 'offload-dlx-plus' );
												break;
											case 'error':
												esc_html_e( 'Error', 'offload-dlx-plus' );
												break;
											default:
												esc_html_e( 'Info', 'offload-dlx-plus' );
										}
										?>
									</span>
								</td>
								
								<td class="column-message">
									<?php echo esc_html( $log['message'] ); ?>
									<?php if ( ! empty( $log['error_details'] ) ) : ?>
										<button type="button" class="show-error-details button-link">
											<?php esc_html_e( 'Show details', 'offload-dlx-plus' ); ?>
										</button>
										<div class="error-details" style="display: none;">
											<pre><?php echo esc_html( $log['error_details'] ); ?></pre>
										</div>
									<?php endif; ?>
								</td>
								
								<td class="column-file">
									<?php if ( ! empty( $log['file_path'] ) ) : ?>
										<span class="file-path" title="<?php echo esc_attr( $log['file_path'] ); ?>">
											<?php echo esc_html( basename( $log['file_path'] ) ); ?>
										</span>
										<?php if ( strlen( $log['file_path'] ) > 30 ) : ?>
											<button type="button" class="show-full-path button-link">
												<?php esc_html_e( 'Full path', 'offload-dlx-plus' ); ?>
											</button>
										<?php endif; ?>
									<?php else : ?>
										<span class="no-file">—</span>
									<?php endif; ?>
								</td>
								
								<td class="column-size">
									<?php if ( ! empty( $log['file_size'] ) && $log['file_size'] > 0 ) : ?>
										<?php echo esc_html( (string) size_format( $log['file_size'] ) ); ?>
									<?php else : ?>
										<span class="no-size">—</span>
									<?php endif; ?>
								</td>

								<td class="column-user">
									<?php if ( ! empty( $log['user_id'] ) ) : ?>
										<?php
										$user = get_userdata( $log['user_id'] );
										if ( $user ) {
											echo esc_html( $user->display_name );
										} else {
											/* translators: %d: user ID */
											echo esc_html( sprintf( __( 'User #%d', 'offload-dlx-plus' ), $log['user_id'] ) );
										}
										?>
									<?php else : ?>
										<span class="system-user"><?php esc_html_e( 'System', 'offload-dlx-plus' ); ?></span>
									<?php endif; ?>
								</td>

								<td class="column-date">
									<abbr title="<?php echo esc_attr( (string) mysql2date( 'c', $log['created_at'] ) ); ?>">
										<?php echo esc_html( human_time_diff( (int) strtotime( $log['created_at'] ), time() ) . ' ' . __( 'ago', 'offload-dlx-plus' ) ); ?>
									</abbr>
								</td>
								
								<td class="column-details">
									<?php if ( ! empty( $log['metadata'] ) ) : ?>
										<button type="button" class="show-metadata button-link">
											<?php esc_html_e( 'View', 'offload-dlx-plus' ); ?>
										</button>
										<div class="activity-metadata" style="display: none;">
											<?php
											$metadata = json_decode( $log['metadata'], true );
											if ( $metadata ) {
												echo '<pre>' . esc_html( (string) wp_json_encode( $metadata, JSON_PRETTY_PRINT ) ) . '</pre>';
											}
											?>
										</div>
									<?php else : ?>
										<span class="no-metadata">—</span>
									<?php endif; ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
			</div>

			<?php
			// Pagination
			$total_pages = (int) ceil( (int) $activity_stats['total_entries'] / $per_page );
			if ( $total_pages > 1 ) :
				?>
				<div class="tablenav">
					<div class="tablenav-pages">
						<?php
						$page_links = paginate_links(
							array(
								'base'      => (string) add_query_arg( 'paged', '%#%' ),
								'format'    => '',
								'prev_text' => __( '&laquo; Previous', 'offload-dlx-plus' ),
								'next_text' => __( 'Next &raquo;', 'offload-dlx-plus' ),
								'total'     => $total_pages,
								'current'   => $current_page,
								'type'      => 'array',
							)
						);

						if ( $page_links ) {
							echo '<span class="pagination-links">';
							// paginate_links() returns a pre-escaped HTML array; safe to echo.
							echo implode( "\n", $page_links ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							echo '</span>';
						}
						?>
					</div>
				</div>
			<?php endif; ?>

		<?php else : ?>
			<div class="no-activity">
				<div class="no-activity-icon">
					<span class="dashicons dashicons-info"></span>
				</div>
				<h4><?php esc_html_e( 'No Activity Found', 'offload-dlx-plus' ); ?></h4>
				<p><?php esc_html_e( 'No activity matches your current filters. Try adjusting your search criteria.', 'offload-dlx-plus' ); ?></p>
			</div>
		<?php endif; ?>
	</div>
</div>

<script>
jQuery(document).ready(function($) {
	// Chart data (passed from PHP)
	var chartData = <?php echo wp_json_encode( $chart_data ); ?>;
	
	// Initialize chart
	if (typeof Chart !== 'undefined' && document.getElementById('activity-chart')) {
		var ctx = document.getElementById('activity-chart').getContext('2d');
		new Chart(ctx, {
			type: 'line',
			data: {
				labels: chartData.labels,
				datasets: [{
					label: '<?php esc_html_e( 'Uploads', 'offload-dlx-plus' ); ?>',
					data: chartData.uploads,
					borderColor: '#0073aa',
					backgroundColor: 'rgba(0, 115, 170, 0.1)',
					fill: true
				}, {
					label: '<?php esc_html_e( 'Deletions', 'offload-dlx-plus' ); ?>',
					data: chartData.deletions,
					borderColor: '#dc3545',
					backgroundColor: 'rgba(220, 53, 69, 0.1)',
					fill: true
				}]
			},
			options: {
				responsive: true,
				maintainAspectRatio: false,
				scales: {
					y: {
						beginAtZero: true
					}
				},
				plugins: {
					legend: {
						position: 'top',
					},
					title: {
						display: false
					}
				}
			}
		});
	}
	
	// Show/hide error details
	$('.show-error-details').on('click', function() {
		var $details = $(this).next('.error-details');
		$details.toggle();
		$(this).text($details.is(':visible') ? 
			'<?php esc_html_e( 'Hide details', 'offload-dlx-plus' ); ?>' : 
			'<?php esc_html_e( 'Show details', 'offload-dlx-plus' ); ?>'
		);
	});
	
	// Show/hide metadata
	$('.show-metadata').on('click', function() {
		var $metadata = $(this).next('.activity-metadata');
		$metadata.toggle();
		$(this).text($metadata.is(':visible') ? 
			'<?php esc_html_e( 'Hide', 'offload-dlx-plus' ); ?>' : 
			'<?php esc_html_e( 'View', 'offload-dlx-plus' ); ?>'
		);
	});
	
	// Show full file path
	$('.show-full-path').on('click', function() {
		var $filePath = $(this).prev('.file-path');
		var fullPath = $filePath.attr('title');
		var isExpanded = $filePath.text() === fullPath;
		
		if (isExpanded) {
			$filePath.text(fullPath.split('/').pop());
			$(this).text('<?php esc_html_e( 'Full path', 'offload-dlx-plus' ); ?>');
		} else {
			$filePath.text(fullPath);
			$(this).text('<?php esc_html_e( 'Short name', 'offload-dlx-plus' ); ?>');
		}
	});
	
	// Export activity log
	$('#export-activity').on('click', function() {
		var $button = $(this);
		$button.prop('disabled', true).find('.dashicons').removeClass('dashicons-download').addClass('dashicons-update');
		
		$.post(offloadDlxPlusAdmin.ajaxUrl, {
			action: 'offload_dlx_plus_export_activity',
			nonce: offloadDlxPlusAdmin.nonce,
			activity_type: '<?php echo esc_js( $activity_type ); ?>',
			date_from: '<?php echo esc_js( $date_from ); ?>',
			date_to: '<?php echo esc_js( $date_to ); ?>'
		}, function(response) {
			if (response.success) {
				// Create download link
				var link = document.createElement('a');
				link.href = 'data:text/csv;charset=utf-8,' + encodeURIComponent(response.csv);
				link.download = 'offload-dlx-plus-activity-log-' + new Date().toISOString().substr(0, 10) + '.csv';
				link.click();
			} else {
				alert('<?php esc_html_e( 'Export failed:', 'offload-dlx-plus' ); ?> ' + response.error);
			}
		}).always(function() {
			$button.prop('disabled', false).find('.dashicons').removeClass('dashicons-update').addClass('dashicons-download');
		});
	});
	
	// Clear old logs
	$('#clear-old-logs').on('click', function() {
		if (!confirm('<?php esc_html_e( 'Are you sure you want to delete activity logs older than 30 days? This action cannot be undone.', 'offload-dlx-plus' ); ?>')) {
			return;
		}
		
		var $button = $(this);
		$button.prop('disabled', true).find('.dashicons').removeClass('dashicons-trash').addClass('dashicons-update');
		
		$.post(offloadDlxPlusAdmin.ajaxUrl, {
			action: 'offload_dlx_plus_clear_old_logs',
			nonce: offloadDlxPlusAdmin.nonce
		}, function(response) {
			if (response.success) {
				location.reload();
			} else {
				alert('<?php esc_html_e( 'Clear failed:', 'offload-dlx-plus' ); ?> ' + response.error);
			}
		}).always(function() {
			$button.prop('disabled', false).find('.dashicons').removeClass('dashicons-update').addClass('dashicons-trash');
		});
	});
	
	// Refresh activity
	$('#refresh-activity').on('click', function() {
		location.reload();
	});
	
	// Auto-refresh every 30 seconds
	setInterval(function() {
		if (document.visibilityState === 'visible') {
			// Only refresh statistics, not the full page
			$.post(offloadDlxPlusAdmin.ajaxUrl, {
				action: 'offload_dlx_plus_refresh_stats',
				nonce: offloadDlxPlusAdmin.nonce
			}, function(response) {
				if (response.success) {
					// Update statistics cards
					$('.stats-cards .stat-number').each(function(index) {
						var keys = ['total_today', 'total_week', 'total_month', 'errors_count'];
						if (response.stats[keys[index]] !== undefined) {
							$(this).text(response.stats[keys[index]].toLocaleString());
						}
					});
				}
			});
		}
	}, 30000);
});
</script>

<style>
.offload-dlx-plus-activity {
	max-width: 1200px;
}

.activity-filters {
	background: #fff;
	border: 1px solid #ddd;
	border-radius: 8px;
	padding: 20px;
	margin-bottom: 20px;
}

.filters-form {
	margin-top: 15px;
}

.filter-row {
	display: flex;
	flex-wrap: wrap;
	gap: 15px;
	align-items: end;
}

.filter-group {
	display: flex;
	flex-direction: column;
	min-width: 150px;
}

.filter-group label {
	font-weight: 600;
	margin-bottom: 5px;
}

.filter-group select,
.filter-group input[type="date"] {
	padding: 8px;
	border: 1px solid #ddd;
	border-radius: 4px;
}

.filter-actions {
	display: flex;
	gap: 10px;
}

.activity-statistics {
	background: #fff;
	border: 1px solid #ddd;
	border-radius: 8px;
	padding: 20px;
	margin-bottom: 20px;
}

.stats-cards {
	display: grid;
	grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
	gap: 20px;
	margin-top: 15px;
}

.stat-card {
	text-align: center;
	padding: 20px;
	background: #f9f9f9;
	border-radius: 8px;
	border-top: 4px solid #0073aa;
}

.stat-number {
	font-size: 28px;
	font-weight: bold;
	color: #333;
	margin-bottom: 5px;
}

.stat-label {
	font-size: 14px;
	color: #666;
	margin-bottom: 10px;
}

.stat-trend {
	font-size: 12px;
}

.trend-up {
	color: #28a745;
}

.trend-down {
	color: #dc3545;
}

.trend-neutral {
	color: #6c757d;
}

.stat-breakdown,
.stat-size {
	font-size: 12px;
	color: #999;
}

.stat-status.has-errors {
	color: #dc3545;
	font-weight: 600;
}

.stat-status.no-errors {
	color: #28a745;
	font-weight: 600;
}

.activity-chart {
	background: #fff;
	border: 1px solid #ddd;
	border-radius: 8px;
	padding: 20px;
	margin-bottom: 20px;
}

#activity-chart {
	max-height: 200px;
}

.activity-actions {
	background: #fff;
	border: 1px solid #ddd;
	border-radius: 8px;
	padding: 20px;
	margin-bottom: 20px;
}

.action-buttons {
	display: flex;
	gap: 10px;
	margin-top: 15px;
	flex-wrap: wrap;
}

.action-buttons .button .dashicons {
	margin-right: 5px;
}

.activity-log {
	background: #fff;
	border: 1px solid #ddd;
	border-radius: 8px;
	padding: 20px;
}

.activity-table-container {
	overflow-x: auto;
}

.activity-table {
	margin-top: 15px;
	width: 100%;
	min-width: 800px;
}

.activity-table th,
.activity-table td {
	padding: 12px 8px;
	vertical-align: top;
}

.column-type {
	width: 100px;
}

.column-message {
	width: 250px;
}

.column-file {
	width: 150px;
}

.column-size {
	width: 80px;
	text-align: right;
}

.column-user {
	width: 120px;
}

.column-date {
	width: 120px;
}

.column-details {
	width: 80px;
	text-align: center;
}

.activity-icon {
	display: inline-flex;
	align-items: center;
	margin-right: 8px;
}

.activity-icon .dashicons {
	font-size: 16px;
}

.activity-upload .activity-icon .dashicons {
	color: #28a745;
}

.activity-delete .activity-icon .dashicons {
	color: #dc3545;
}

.activity-migration .activity-icon .dashicons {
	color: #0073aa;
}

.activity-config .activity-icon .dashicons {
	color: #6c757d;
}

.activity-error .activity-icon .dashicons {
	color: #ffc107;
}

.activity-type-text {
	font-size: 12px;
	color: #666;
	display: block;
}

.show-error-details,
.show-metadata,
.show-full-path {
	font-size: 12px;
	color: #0073aa;
	text-decoration: none;
	background: none;
	border: none;
	padding: 0;
	cursor: pointer;
	margin-top: 5px;
	display: block;
}

.show-error-details:hover,
.show-metadata:hover,
.show-full-path:hover {
	text-decoration: underline;
}

.error-details,
.activity-metadata {
	margin-top: 10px;
	background: #f8f9fa;
	border: 1px solid #dee2e6;
	border-radius: 4px;
	padding: 10px;
}

.error-details pre,
.activity-metadata pre {
	margin: 0;
	font-size: 11px;
	white-space: pre-wrap;
	word-break: break-all;
}

.file-path {
	display: block;
	word-break: break-all;
	font-family: monospace;
	font-size: 12px;
}

.no-file,
.no-size,
.no-metadata {
	color: #999;
	font-style: italic;
}

.system-user {
	color: #666;
	font-style: italic;
}

.no-activity {
	text-align: center;
	padding: 40px 20px;
	color: #666;
}

.no-activity-icon .dashicons {
	font-size: 48px;
	opacity: 0.3;
	margin-bottom: 20px;
}

.no-activity h4 {
	margin-bottom: 10px;
	color: #333;
}

@media (max-width: 768px) {
	.filter-row {
		flex-direction: column;
		align-items: stretch;
	}
	
	.filter-group {
		min-width: auto;
	}
	
	.stats-cards {
		grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
	}
	
	.action-buttons {
		flex-direction: column;
	}
	
	.activity-table {
		font-size: 12px;
	}
	
	.column-message {
		width: 200px;
	}
	
	.column-file {
		width: 120px;
	}
}

@media (max-width: 480px) {
	.stats-cards {
		grid-template-columns: 1fr;
	}
	
	.stat-number {
		font-size: 24px;
	}
}
</style>

	</div> <!-- end activity-content -->
	<?php endif; ?>
</div> <!-- end offload-dlx-plus-activity -->
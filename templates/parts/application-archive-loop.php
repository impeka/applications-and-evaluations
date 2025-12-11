<?php
/**
 * Partial to render application types, sessions, and user applications.
 *
 * Variables available (provided by ApplicationTemplateHelpers::render_application_archive_sections()):
 * - $types            array<WP_Term>
 * - $current_user_id  int
 * - $now              int (timestamp)
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Impeka\Applications\Application;

$session_filter = isset( $session_filter ) && $session_filter instanceof WP_Term ? $session_filter : null;
$type_filter    = isset( $type_term ) && $type_term instanceof WP_Term ? $type_term : ( isset( $type_filter ) ? $type_filter : null );

?>

<?php if ( empty( $types ) ) : ?>
	<p><?php esc_html_e( 'No application types available.', 'applications-and-evaluations' ); ?></p>
<?php else : ?>
	<?php foreach ( $types as $type ) : ?>
		<section class="application-type-block">
			<h2><?php echo esc_html( $type->name ); ?></h2>
			<?php
			if ( $session_filter && $type_filter && (int) $type_filter->term_id === (int) $type->term_id ) {
				// Force a specific session (even if closed) for this type.
				$sessions = [ $session_filter ];
			} else {
				$sessions = ae_get_sessions_for_type( $type, $now );
			}

			if ( empty( $sessions ) ) :
				?>
				<p><?php esc_html_e( 'No active sessions for this application type.', 'applications-and-evaluations' ); ?></p>
			<?php else : ?>
				<?php foreach ( $sessions as $session ) : ?>
					<?php
					$session_range   = ae_format_session_range( $session );
					$limit_raw       = ae_get_session_meta( $session, 'application_session_submission_limit' );
					$limit           = $limit_raw !== '' ? intval( $limit_raw ) : 0;
					$applications    = ae_get_user_applications_for_session( $current_user_id, $type->term_id, $session->term_id );
					$app_count       = count( $applications );
					$limit_reached   = $limit > 0 && $app_count >= $limit;
					$session_start_ts = ae_session_start_ts( $session );
					$session_end_ts   = ae_session_end_ts( $session );
					$session_active   = ae_is_session_active( $session, $now );
					$session_closed   = $session_end_ts && $session_end_ts < $now;
					$button_disabled  = $limit_reached || $session_closed || ! is_user_logged_in();
					?>
					<article class="application-session">
						<header class="application-session__header">
							<h3><?php echo esc_html( $session->name ); ?></h3>
							<?php if ( $session_range ) : ?>
								<p class="application-session__dates"><?php echo esc_html( $session_range ); ?></p>
							<?php endif; ?>
						</header>

						<div class="application-session__actions">
							<form method="post" action="<?php echo esc_url( admin_url('admin-post.php') ); ?>">
								<?php wp_nonce_field( 'ae_new_application', 'ae_new_application_nonce' ); ?>
								<input type="hidden" name="action" value="create_application" />
								<input type="hidden" name="application_type" value="<?php echo esc_attr( $type->slug ); ?>" />
								<input type="hidden" name="application_session" value="<?php echo esc_attr( $session->slug ); ?>" />
								<button class="new-application-button<?php echo $button_disabled ? ' is-disabled' : ''; ?>" type="submit"<?php echo $button_disabled ? ' disabled' : ''; ?>>
									<?php esc_html_e( 'New Application', 'applications-and-evaluations' ); ?>
								</button>
							</form>
							<?php if ( $limit_reached ) : ?>
								<p class="application-session__notice"><?php esc_html_e( 'Application limit reached for this session.', 'applications-and-evaluations' ); ?></p>
							<?php elseif ( $session_closed ) : ?>
								<p class="application-session__notice"><?php esc_html_e( 'This session is closed.', 'applications-and-evaluations' ); ?></p>
							<?php elseif ( ! is_user_logged_in() ) : ?>
								<p class="application-session__notice"><?php esc_html_e( 'Log in to start an application.', 'applications-and-evaluations' ); ?></p>
							<?php elseif ( $limit > 0 ) : ?>
								<p class="application-session__notice">
									<?php
									printf(
										esc_html__( 'You can submit %1$d application(s) for this session. %2$d remaining.', 'applications-and-evaluations' ),
										$limit,
										max( 0, $limit - $app_count )
									);
									?>
								</p>
							<?php endif; ?>
						</div>

						<div class="application-session__list">
							<h4><?php esc_html_e( 'Your Applications', 'applications-and-evaluations' ); ?></h4>
							<?php if ( ! is_user_logged_in() ) : ?>
								<p><?php esc_html_e( 'Log in to view your applications.', 'applications-and-evaluations' ); ?></p>
							<?php elseif ( empty( $applications ) ) : ?>
								<p><?php esc_html_e( 'You have not started any applications for this session.', 'applications-and-evaluations' ); ?></p>
							<?php else : ?>
								<table class="application-table">
									<thead>
										<tr>
											<th><?php esc_html_e( 'Title', 'applications-and-evaluations' ); ?></th>
											<th><?php esc_html_e( 'Created', 'applications-and-evaluations' ); ?></th>
											<th><?php esc_html_e( 'Status', 'applications-and-evaluations' ); ?></th>
											<th><?php esc_html_e( 'Actions', 'applications-and-evaluations' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $applications as $application_post ) : ?>
											<?php
											$application = new Application( $application_post->ID );
											$title       = get_the_title( $application_post ) ?: sprintf( __( 'Application #%d', 'applications-and-evaluations' ), $application_post->ID );
											$edit_link     = get_permalink( $application_post );
											$view_link     = get_permalink( $application_post ).'/view/';
											$delete_link   = get_delete_post_link( $application_post->ID, '', false );
											$created       = ae_application_created_display( $application_post );
											$edit_disabled   = $session_closed && ! $application->is_unlocked();
											$delete_disabled = ! $session_active;
											?>
											<tr>
												<td><?php echo esc_html( $title ); ?></td>
												<td><?php echo esc_html( $created ); ?></td>
												<td>
													<?php switch( $application->get_status() ): case 'progress':  ?>
														<progress class="application__progress" max="100" value="<?php echo $application->get_progress_percentage(); ?>"><?php echo $application->get_progress_percentage(); ?>%</progress>
													<?php break; ?>
													<?php case 'submit': ?>
														<i class="fa-solid fa-check success-green-color"></i> <?php _e( 'Submitted', 'applications-and-evaluations' ); ?>
													<?php break; ?>
													<?php default: ?>
														<?php echo $application->get_status(); ?>
													<?php break; ?>
												<?php endswitch; ?>
												</td>
												<td class="application-table__actions">
													<?php if( 
														$edit_link 
														&& ! $edit_disabled
													) : ?>
														<a class="application-table__action application-table__action--edit" href="<?php echo esc_url( $edit_link ); ?>">
															<i class="fa-light fa-pen-to-square" aria-hidden="true"></i>
															<span class="screen-reader-text"><?php esc_html_e( 'Edit application', 'applications-and-evaluations' ); ?></span>
														</a>
													<?php elseif ( $edit_disabled ) : ?>
														<span class="application-table__action application-table__action--edit is-disabled" aria-disabled="true">
															<i class="fa-light fa-pen-to-square" aria-hidden="true"></i>
															<span class="screen-reader-text"><?php esc_html_e( 'Editing disabled; session closed.', 'applications-and-evaluations' ); ?></span>
														</span>
														<a class="application-table__action application-table__action--view" href="<?php echo esc_url( $view_link ); ?>">
															<i class="fa-thin fa-eye"aria-hidden="true"></i>
															<span class="screen-reader-text"><?php esc_html_e( 'View application', 'applications-and-evaluations' ); ?></span>
														</a>
													<?php endif; ?>
													<?php if ( $delete_link && ! $delete_disabled ) : ?>
														<a class="application-table__action application-table__action--delete" href="<?php echo esc_url( $delete_link ); ?>">
															<i class="fa-light fa-trash" aria-hidden="true"></i>
															<span class="screen-reader-text"><?php esc_html_e( 'Delete application', 'applications-and-evaluations' ); ?></span>
														</a>
													<?php elseif ( $delete_disabled ) : ?>
														<span class="application-table__action application-table__action--delete is-disabled" aria-disabled="true">
															<i class="fa-light fa-trash" aria-hidden="true"></i>
															<span class="screen-reader-text"><?php esc_html_e( 'Delete disabled; session closed.', 'applications-and-evaluations' ); ?></span>
														</span>
													<?php endif; ?>
												</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							<?php endif; ?>
						</div>
					</article>
				<?php endforeach; ?>
			<?php endif; ?>
		</section>
	<?php endforeach; ?>
<?php endif; ?>

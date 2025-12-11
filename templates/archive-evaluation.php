<?php
/**
 * Archive template for Evaluations (frontend).
 *
 * Shows application types and sessions the current user can evaluate,
 * and lets them start/continue evaluations on submitted applications.
 */

use Impeka\Applications\EvaluationTemplateHelpers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$current_user_id = get_current_user_id();
$now             = current_time( 'timestamp' );
$types           = get_terms(
	[
		'taxonomy'   => 'application_type',
		'hide_empty' => false,
		'orderby'    => 'name',
		'order'      => 'ASC',
	]
);

if ( is_wp_error( $types ) ) {
	$types = [];
}
?>

<main id="primary" class="site-main">
	<header class="page-header">
		<h1 class="page-title"><?php echo esc_html( __( 'Evaluations', 'applications-and-evaluations' ) ); ?></h1>
	</header>

	<?php if ( empty( $types ) ) : ?>
		<p><?php esc_html_e( 'No application types available.', 'applications-and-evaluations' ); ?></p>
	<?php else : ?>
		<?php foreach ( $types as $type ) : ?>
			<section class="application-type-block">
				<h2><?php echo esc_html( $type->name ); ?></h2>
				<?php
				$sessions = ae_get_sessions_for_type( $type, $now );

				if ( empty( $sessions ) ) :
					?>
					<p><?php esc_html_e( 'No active sessions for this application type.', 'applications-and-evaluations' ); ?></p>
				<?php else : ?>
					<?php foreach ( $sessions as $session ) : ?>
						<?php
						$evaluation_sessions = array_filter(
							EvaluationTemplateHelpers::get_evaluation_sessions_for_application_session( $session ),
							static function ( $eval_session ) use ( $current_user_id ) {
								return EvaluationTemplateHelpers::user_can_evaluate_session( $eval_session, $current_user_id );
							}
						);

						if ( empty( $evaluation_sessions ) ) {
							continue;
						}

						$submitted_apps = EvaluationTemplateHelpers::get_submitted_applications( $type->term_id, $session->term_id );

						if ( empty( $submitted_apps ) ) {
							continue;
						}

						?>
						<article class="application-session">
							<header class="application-session__header">
								<h3><?php echo esc_html( $session->name ); ?></h3>
								<?php $session_range = ae_format_session_range( $session ); ?>
								<?php if ( $session_range ) : ?>
									<p class="application-session__dates"><?php echo esc_html( $session_range ); ?></p>
								<?php endif; ?>
							</header>

							<div class="application-session__list">
								<h4><?php esc_html_e( 'Applications to Evaluate', 'applications-and-evaluations' ); ?></h4>
								<table class="application-table">
									<thead>
										<tr>
											<th><?php esc_html_e( 'Application', 'applications-and-evaluations' ); ?></th>
											<th><?php esc_html_e( 'Applicant', 'applications-and-evaluations' ); ?></th>
											<th><?php esc_html_e( 'Submitted', 'applications-and-evaluations' ); ?></th>
											<th><?php esc_html_e( 'Actions', 'applications-and-evaluations' ); ?></th>
										</tr>
									</thead>
									<tbody>
										<?php foreach ( $submitted_apps as $application_post ) : ?>
											<?php
											$application_title = get_the_title( $application_post ) ?: sprintf( __( 'Application #%d', 'applications-and-evaluations' ), $application_post->ID );
											$applicant_name    = get_the_author_meta( 'display_name', $application_post->post_author );
											$submitted_date    = get_post_time( get_option( 'date_format' ), false, $application_post, true );
											$view_link         = trailingslashit( get_permalink( $application_post ) ) . 'view/';

											// Use the first evaluation session the user can access for this application session.
											$evaluation_session = reset( $evaluation_sessions );
											$existing_eval      = EvaluationTemplateHelpers::get_user_evaluation_for_application( $current_user_id, $application_post->ID );
											?>
											<tr>
												<td><?php echo esc_html( $application_title ); ?></td>
												<td><?php echo esc_html( $applicant_name ); ?></td>
												<td><?php echo esc_html( $submitted_date ); ?></td>
												<td class="application-table__actions">
													<a class="application-table__action application-table__action--view" href="<?php echo esc_url( $view_link ); ?>" target="_blank" rel="noopener noreferrer">
														<i class="fa-thin fa-eye" aria-hidden="true"></i>
														<span class="screen-reader-text"><?php esc_html_e( 'View application', 'applications-and-evaluations' ); ?></span>
													</a>

													<?php if ( $existing_eval instanceof WP_Post ) : ?>
														<a class="application-table__action application-table__action--edit" href="<?php echo esc_url( get_permalink( $existing_eval ) ); ?>">
															<i class="fa-light fa-pen-to-square" aria-hidden="true"></i>
															<span class="screen-reader-text"><?php esc_html_e( 'Continue evaluation', 'applications-and-evaluations' ); ?></span>
														</a>
													<?php elseif ( $evaluation_session ) : ?>
														<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline;">
															<?php wp_nonce_field( 'ae_new_evaluation', 'ae_new_evaluation_nonce' ); ?>
															<input type="hidden" name="action" value="create_evaluation" />
															<input type="hidden" name="application_id" value="<?php echo esc_attr( $application_post->ID ); ?>" />
															<input type="hidden" name="evaluation_category" value="<?php echo esc_attr( $evaluation_session->term_id ); ?>" />
															<button type="submit" class="application-table__action application-table__action--edit">
																<i class="fa-light fa-flag-checkered" aria-hidden="true"></i>
																<span class="screen-reader-text"><?php esc_html_e( 'Start evaluation', 'applications-and-evaluations' ); ?></span>
															</button>
														</form>
													<?php endif; ?>
												</td>
											</tr>
										<?php endforeach; ?>
									</tbody>
								</table>
							</div>
						</article>
					<?php endforeach; ?>
				<?php endif; ?>
			</section>
		<?php endforeach; ?>
	<?php endif; ?>
</main>

<?php
get_footer();

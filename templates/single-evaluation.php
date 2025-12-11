<?php
/**
 * Single template for Evaluations (fallback from the plugin).
 *
 * Override by placing a file with the same name in
 * wp-content/themes/your-theme/applications-and-evaluations/.
 */

use Impeka\Applications\Evaluation;
use Impeka\Tools\Forms\PostForm;

get_header();
?>

<main id="primary" class="site-main">
	<?php
	while ( have_posts() ) :
		the_post();

		$evaluation   = null;
		$form         = null;
		$application_id = 0;

		try {
			$evaluation     = new Evaluation( get_the_ID() );
			$form           = $evaluation->get_form();
			$application_id = $evaluation->get_application_id();
		} catch ( \Throwable $e ) {
			$form = null;
		}
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<header class="entry-header" style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">
				<h1 class="entry-title"><?php the_title(); ?></h1>
				<?php if ( $application_id ) : ?>
					<a class="button-default" href="<?php echo esc_url( trailingslashit( get_permalink( $application_id ) ) . 'view/' ); ?>" target="_blank" rel="noopener noreferrer">
						<i class="fa-thin fa-eye" aria-hidden="true"></i>
						<?php esc_html_e( 'View Application', 'applications-and-evaluations' ); ?>
					</a>
				<?php endif; ?>
			</header>

			<div class="entry-content">
				<?php
				if ( $form instanceof PostForm ) {
					$form->show_nav( get_the_ID() );
					$form->show_form( get_the_ID() );
				}
				?>
			</div>
		</article>

	<?php endwhile; ?>
</main>

<?php
get_footer();

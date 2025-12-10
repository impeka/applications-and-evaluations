<?php
/**
 * Single template for Applications (fallback from the plugin).
 *
 * Override by placing a file with the same name in
 * wp-content/themes/your-theme/applications-and-evaluations/.
 */

use Impeka\Applications\Application;
use Impeka\Tools\Forms\PostForm;

get_header();
?>

<main id="primary" class="site-main">
	<?php
	while ( have_posts() ) :
		the_post();

		$application = null;
		$form        = null;

		try {
			$application = new Application( get_the_ID() );
			$form        = $application->get_form();
		} catch ( \Throwable $e ) {
			$form = null;
		}
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
			<header class="entry-header">
				<h1 class="entry-title"><?php the_title(); ?></h1>
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

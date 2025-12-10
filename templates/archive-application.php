<?php
/**
 * Archive template for Applications (fallback from the plugin).
 *
 * Override by placing a file with the same name in
 * wp-content/themes/your-theme/applications-and-evaluations/.
 */

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
		<h1 class="page-title"><?php echo esc_html( post_type_archive_title( '', false ) ); ?></h1>
	</header>

	<?php ae_render_application_archive_sections(); ?>
</main>

<?php
get_footer();

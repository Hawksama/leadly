<?php
/**
 * The template for displaying list of users.
 *
 * @package WordPress
 */

get_header();
?>

<main id="site-content" role="main">

	<?php

	leadly_include('templates/content/users.php');

	?>

</main><!-- #site-content -->

<?php get_template_part( 'template-parts/footer-menus-widgets' ); ?>

<?php get_footer(); ?>

<?php

$id = get_the_ID();

$blog_hide_comments = "";
if (isset($qode_options_proya['blog_hide_comments']))
	$blog_hide_comments = $qode_options_proya['blog_hide_comments'];
?>
<?php get_header(); ?>
<?php if (have_posts()) : ?>
<?php while (have_posts()) : the_post(); ?>
<div style="text-align: center">
    <?php the_title( '<h3>', '</h3>' ); ?>
	<h2> Sitio en construcción ... </h2>
</div>
<?php endwhile; ?>
<?php endif; ?>


<?php get_footer(); ?>

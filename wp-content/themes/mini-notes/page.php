<?php get_header(); ?>
<div class="page-wrap reading-page">
<?php while (have_posts()) : the_post(); ?>
<article><span class="eyebrow">MINI NOTES</span><h1><?php the_title(); ?></h1><div class="prose"><?php the_content(); ?></div></article>
<?php endwhile; ?>
</div>
<?php get_footer(); ?>

<?php get_header(); ?>
<div class="page-wrap reading-page">
<?php while (have_posts()) : the_post(); ?>
<article><a class="back-link" href="<?php echo esc_url(home_url('/')); ?>">← Back home</a><p class="eyebrow"><?php echo esc_html(get_the_date()); ?></p><h1><?php the_title(); ?></h1><div class="prose"><?php the_content(); ?></div></article>
<?php endwhile; ?>
</div>
<?php get_footer(); ?>

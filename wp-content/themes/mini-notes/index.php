<?php
// STEP 3/4: fallback template. More specific templates take precedence.
get_header();
?>
<div class="page-wrap reading-page"><h1><?php echo esc_html(is_archive() ? get_the_archive_title() : 'Notebook'); ?></h1>
<?php if (have_posts()) : while (have_posts()) : the_post(); get_template_part('template-parts/post-card'); endwhile; the_posts_pagination(); else : ?><p>No entries found.</p><?php endif; ?>
</div>
<?php get_footer(); ?>

<?php
// Checkout is a page, so the reading layout would wrap the payment form in article styles and a 38px title.
$mini_notes_checkout = function_exists('is_checkout') && is_checkout();
get_header();
?>
<div class="page-wrap <?php echo $mini_notes_checkout ? 'shop-wrap checkout-wrap' : 'reading-page'; ?>">
<?php while (have_posts()) : the_post(); ?>
<?php if ($mini_notes_checkout) : ?>
<?php the_content(); ?>
<?php else : ?>
<article><span class="eyebrow">MINI NOTES</span><h1><?php the_title(); ?></h1><div class="prose"><?php the_content(); ?></div></article>
<?php endif; ?>
<?php endwhile; ?>
</div>
<?php get_footer(); ?>

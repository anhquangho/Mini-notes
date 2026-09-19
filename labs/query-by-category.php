<?php
// STEP 5: CLI only. MN_ROOT must point to a local study WordPress installation.
if (PHP_SAPI !== 'cli' || !getenv('MN_ROOT')) { exit("Set MN_ROOT and run with PHP CLI.\n"); }
require getenv('MN_ROOT') . '/wp-load.php';
$query = new WP_Query([
    'post_type' => 'post',
    'post_status' => 'publish',
    'posts_per_page' => 5,
    'category_name' => 'notebook',
]);
while ($query->have_posts()) {
    $query->the_post();
    echo get_the_ID() . ': ' . get_the_title() . PHP_EOL;
}
wp_reset_postdata();
// This uses the public Post taxonomy, not the mn_category text field of Notes.

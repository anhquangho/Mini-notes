<?php
defined('ABSPATH') || exit;

// STEP 3/6: functions.php registers behavior; templates render each request.
function mini_notes_setup() {
    add_theme_support('title-tag');
    add_theme_support('html5', ['search-form', 'comment-form', 'gallery', 'caption', 'style', 'script']);
}
add_action('after_setup_theme', 'mini_notes_setup');

function mini_notes_assets() {
    $uri = get_template_directory_uri();
    wp_enqueue_style('mini-notes-app', $uri . '/assets/css/app.css', [], '1.0.0');
    wp_enqueue_script('mini-notes-app', $uri . '/assets/js/app.js', [], '1.0.0', true);
}
add_action('wp_enqueue_scripts', 'mini_notes_assets');

add_filter('excerpt_length', function ($length) { return 24; }, 999);
add_filter('show_admin_bar', function ($show) { return is_admin() ? $show : false; });

function mini_notes_icon($name) {
    $paths = [
        'home' => '<path d="m3 10 9-7 9 7v10a1 1 0 0 1-1 1h-5v-7H9v7H4a1 1 0 0 1-1-1z"/>',
        'note' => '<path d="M14 3H5a1 1 0 0 0-1 1v16a1 1 0 0 0 1 1h14a1 1 0 0 0 1-1V9z"/><path d="M14 3v6h6M8 13h8M8 17h5"/>',
        'search' => '<circle cx="10.5" cy="10.5" r="6.5"/><path d="m16 16 5 5"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'arrow' => '<path d="M5 12h14m-6-6 6 6-6 6"/>',
    ];
    // Only hard-coded SVG paths are output, never request data.
    echo '<svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' .
        ($paths[$name] ?? $paths['note']) . '</svg>';
}

function mini_notes_core_required() {
    if (!function_exists('mn_query_notes')) {
        echo '<section class="empty-state"><h1>Activate Mini Notes Core</h1><p>The workspace needs its companion plugin. Activate it under WordPress Admin → Plugins.</p></section>';
        return false;
    }
    return true;
}

// STEP 6: a filter changes a value and returns it. Only note titles lose the private prefix.
add_filter('private_title_format', function ($format, $post) {
    return $post->post_type === 'note' ? '%s' : $format;
}, 10, 2);

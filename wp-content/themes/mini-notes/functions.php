<?php
defined('ABSPATH') || exit;

// STEP 3/6: functions.php registers behavior; templates render each request.
function mini_notes_setup() {
    add_theme_support('title-tag');
    add_theme_support('html5', ['search-form', 'comment-form', 'gallery', 'caption', 'style', 'script']);
    add_theme_support('woocommerce');
    add_theme_support('wc-product-gallery-zoom');
    add_theme_support('wc-product-gallery-lightbox');
    add_theme_support('wc-product-gallery-slider');
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
        'shop' => '<path d="M6 8h12l-1 12H7L6 8z"/><path d="M9 8V7a3 3 0 0 1 6 0v1"/>',
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

function mini_notes_shop_assets() {
    if (!function_exists('is_woocommerce') || (!is_woocommerce() && !is_cart() && !is_checkout() && !is_account_page())) {
        return;
    }
    $deps = ['mini-notes-app'];
    foreach (['woocommerce-layout', 'woocommerce-smallscreen'] as $handle) {
        if (wp_style_is($handle, 'registered')) {
            $deps[] = $handle;
        }
    }
    wp_enqueue_style('mini-notes-shop', get_template_directory_uri() . '/assets/css/shop.css', $deps, '1.0.2');
}
add_action('wp_enqueue_scripts', 'mini_notes_shop_assets', 20);

function mini_notes_woocommerce() {
    if (!class_exists('WooCommerce')) {
        return;
    }
    remove_action('woocommerce_before_main_content', 'woocommerce_output_content_wrapper', 10);
    remove_action('woocommerce_after_main_content', 'woocommerce_output_content_wrapper_end', 10);
    remove_action('woocommerce_sidebar', 'woocommerce_get_sidebar', 10);
    remove_action('woocommerce_before_shop_loop_item', 'woocommerce_template_loop_product_link_open', 10);
    remove_action('woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5);
    remove_action('woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_title', 10);
    remove_action('woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10);
    add_action('woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 8);
    add_action('woocommerce_before_shop_loop_item_title', 'mini_notes_product_thumb_open', 9);
    add_action('woocommerce_before_shop_loop_item_title', 'mini_notes_product_thumb_close', 12);
    add_action('woocommerce_shop_loop_item_title', 'mini_notes_product_title');
    add_filter('loop_shop_columns', function () {
        return 4;
    });
    add_action('woocommerce_before_shop_loop', function () {
        echo '<div class="mn-shop-toolbar">';
    }, 15);
    add_action('woocommerce_before_shop_loop', function () {
        echo '</div>';
    }, 35);
}
add_action('wp', 'mini_notes_woocommerce');

function mini_notes_product_thumb_open() {
    echo '<a class="mn-product-thumb" href="' . esc_url(get_permalink()) . '" aria-label="' . esc_attr(get_the_title()) . '">';
}

function mini_notes_product_thumb_close() {
    echo '</a>';
}

function mini_notes_product_title() {
    echo '<h2 class="woocommerce-loop-product__title"><a href="' . esc_url(get_permalink()) . '">' . esc_html(get_the_title()) . '</a></h2>';
}

function mini_notes_crumb_label() {
    if (function_exists('is_woocommerce') && is_woocommerce()) {
        if (is_product()) {
            return wp_strip_all_tags(get_the_title());
        }
        return wp_strip_all_tags(woocommerce_page_title(false));
    }
    return is_front_page() ? 'Home' : wp_strip_all_tags(get_the_title());
}

function mini_notes_cart_assets() {
    if (!function_exists('is_cart') || !is_cart()) {
        return;
    }
    wp_enqueue_style('mini-notes-cart', get_template_directory_uri() . '/assets/css/cart.css', ['mini-notes-shop'], '1.0.0');
    wp_enqueue_script('mini-notes-cart', get_template_directory_uri() . '/assets/js/cart.js', [], '1.0.0', true);
    wp_localize_script('mini-notes-cart', 'mnCart', [
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('mn_update_cart'),
    ]);
}
add_action('wp_enqueue_scripts', 'mini_notes_cart_assets', 21);

function mini_notes_update_cart() {
    check_ajax_referer('mn_update_cart', 'nonce');
    if (!function_exists('WC') || !WC()->cart) {
        wp_send_json_error(['message' => 'Cart is unavailable.'], 400);
    }

    $quantities = isset($_POST['quantities']) && is_array($_POST['quantities']) ? wp_unslash($_POST['quantities']) : [];
    $cart = WC()->cart->get_cart();
    foreach ($quantities as $key => $qty) {
        $key = sanitize_text_field((string) $key);
        if (!preg_match('/^[A-Fa-f0-9]{32}$/', $key) || !isset($cart[$key]) || !is_scalar($qty)) {
            continue;
        }
        $quantity = wc_stock_amount($qty);
        if ($quantity < 0 || $quantity > 9999) {
            continue;
        }
        WC()->cart->set_quantity($key, $quantity, false);
    }
    WC()->cart->calculate_totals();

    $cart = WC()->cart->get_cart();
    $items = [];
    foreach ($quantities as $key => $qty) {
        $key = sanitize_text_field((string) $key);
        if (!preg_match('/^[A-Fa-f0-9]{32}$/', $key)) {
            continue;
        }
        if (!isset($cart[$key])) {
            $items[$key] = ['removed' => true];
            continue;
        }
        $item = $cart[$key];
        $items[$key] = [
            'removed' => false,
            'quantity' => (int) $item['quantity'],
            'subtotal' => WC()->cart->get_product_subtotal($item['data'], $item['quantity']),
        ];
    }

    ob_start();
    woocommerce_cart_totals();
    $totals = ob_get_clean();
    $empty_html = '';
    if (WC()->cart->is_empty()) {
        ob_start();
        wc_get_template('cart/cart-empty.php');
        $empty_html = ob_get_clean();
    }

    wp_send_json_success([
        'items' => $items,
        'totals' => $totals,
        'notices' => wc_print_notices(true),
        'count' => WC()->cart->get_cart_contents_count(),
        'empty' => WC()->cart->is_empty(),
        'emptyHtml' => $empty_html,
    ]);
}
add_action('wp_ajax_mn_update_cart', 'mini_notes_update_cart');
add_action('wp_ajax_nopriv_mn_update_cart', 'mini_notes_update_cart');

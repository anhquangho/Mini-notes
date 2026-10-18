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
    wp_enqueue_style('mini-notes-shop', get_template_directory_uri() . '/assets/css/shop.css', $deps, '1.0.5');
}
add_action('wp_enqueue_scripts', 'mini_notes_shop_assets', 20);

function mini_notes_is_commerce_page() {
    return function_exists('is_woocommerce') && (is_woocommerce() || is_cart() || is_checkout() || is_account_page());
}

function mini_notes_trim_woocommerce_assets() {
    if (!class_exists('WooCommerce')) {
        return;
    }
    // No mini-cart in the header, so this script only polls admin-ajax.php.
    wp_dequeue_script('wc-cart-fragments');
    if (mini_notes_is_commerce_page()) {
        return;
    }
    foreach (['woocommerce-general', 'woocommerce-layout', 'woocommerce-smallscreen', 'woocommerce-inline'] as $style) {
        wp_dequeue_style($style);
    }
    foreach (['woocommerce', 'wc-add-to-cart', 'wc-jquery-blockui', 'wc-js-cookie', 'sourcebuster-js', 'wc-order-attribution'] as $script) {
        wp_dequeue_script($script);
    }
    global $wp_scripts;
    if (!$wp_scripts instanceof WP_Scripts) {
        return;
    }
    foreach ($wp_scripts->queue as $handle) {
        if ($handle === 'jquery') {
            continue;
        }
        $deps = $wp_scripts->registered[$handle]->deps ?? [];
        if (in_array('jquery', $deps, true) || in_array('jquery-core', $deps, true)) {
            return;
        }
    }
    wp_dequeue_script('jquery');
    wp_dequeue_script('jquery-core');
    wp_dequeue_script('jquery-migrate');
}
add_filter('woocommerce_enqueue_styles', function ($styles) {
    return mini_notes_is_commerce_page() ? $styles : [];
});
add_filter('wp_resource_hints', function ($urls, $relation_type) {
    // A non-empty cart makes WooCommerce prefetch the checkout bundle on every page.
    if (mini_notes_is_commerce_page() || !in_array($relation_type, ['prefetch', 'prerender'], true)) {
        return $urls;
    }
    $kept = [];
    foreach ($urls as $url) {
        $href = is_array($url) ? (string) ($url['href'] ?? '') : (string) $url;
        if (!str_contains($href, '/plugins/woocommerce/')) {
            $kept[] = $url;
        }
    }
    return $kept;
}, 20, 2);
add_action('wp_enqueue_scripts', 'mini_notes_trim_woocommerce_assets', 100);
add_action('wp_footer', 'mini_notes_trim_woocommerce_assets', 1);

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
        'empty' => WC()->cart->is_empty(),
        'emptyHtml' => $empty_html,
    ]);
}
add_action('wp_ajax_mn_update_cart', 'mini_notes_update_cart');
add_action('wp_ajax_nopriv_mn_update_cart', 'mini_notes_update_cart');

function mini_notes_checkout_fields($fields) {
    if (isset($fields['billing']['billing_phone'])) {
        $fields['billing']['billing_phone']['required'] = true;
        $fields['billing']['billing_phone']['custom_attributes']['maxlength'] = '20';
    }
    if (isset($fields['order']['order_comments'])) {
        $fields['order']['order_comments']['maxlength'] = 180;
        $fields['order']['order_comments']['custom_attributes']['maxlength'] = '180';
    }
    return $fields;
}
add_filter('woocommerce_checkout_fields', 'mini_notes_checkout_fields');

// Block checkout ignores woocommerce_checkout_fields and reads the country locale, so Phone stays optional without this.
function mini_notes_require_checkout_phone($locale) {
    foreach ($locale as $country => $fields) {
        $locale[$country]['phone']['required'] = true;
    }
    return $locale;
}
add_filter('woocommerce_get_country_locale', 'mini_notes_require_checkout_phone');

function mini_notes_validate_checkout_values($phone, $first, $last, $note, $errors) {
    $digits = preg_replace('/\D+/', '', (string) $phone);
    if ($digits === '') {
        $errors->add('billing_phone', 'Enter a phone number so we can contact you about this order.');
    } elseif (strlen($digits) < 8 || strlen($digits) > 15) {
        $errors->add('billing_phone', 'Enter a phone number with 8 to 15 digits.');
    }

    $names = [
        'billing_first_name' => [$first, 'first name'],
        'billing_last_name' => [$last, 'last name'],
    ];
    foreach ($names as $code => [$name, $label]) {
        $name = trim((string) $name);
        if (mb_strlen($name) < 2 || !preg_match('/\p{L}/u', $name)) {
            $errors->add($code, 'Enter a ' . $label . ' using at least 2 letters.');
        }
    }

    if (mb_strlen(trim((string) $note)) > 180) {
        $errors->add('order_comments', 'Order notes must be 180 characters or fewer.');
    }
}

// Classic checkout. The block checkout hook below runs before the payment gateway, including Cash on delivery.
function mini_notes_validate_classic_checkout($data, $errors) {
    mini_notes_validate_checkout_values(
        $data['billing_phone'] ?? '',
        $data['billing_first_name'] ?? '',
        $data['billing_last_name'] ?? '',
        $data['order_comments'] ?? '',
        $errors
    );
}
add_action('woocommerce_after_checkout_validation', 'mini_notes_validate_classic_checkout', 10, 2);

// A WP_Error here aborts payment and does not create the order.
function mini_notes_validate_block_checkout($order, $errors) {
    mini_notes_validate_checkout_values(
        $order->get_billing_phone(),
        $order->get_billing_first_name(),
        $order->get_billing_last_name(),
        $order->get_customer_note(),
        $errors
    );
}
add_action('woocommerce_checkout_validate_order_before_payment', 'mini_notes_validate_block_checkout', 10, 2);

require_once get_template_directory() . '/inc/order-workflow.php';

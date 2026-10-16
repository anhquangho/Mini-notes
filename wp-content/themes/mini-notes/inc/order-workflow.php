<?php
defined('ABSPATH') || exit;

if (!class_exists('WooCommerce')) {
    return;
}

function mini_notes_register_order_statuses() {
    $statuses = [
        'wc-mn-packed' => 'Packed',
        'wc-mn-shipped' => 'Shipped',
    ];

    foreach ($statuses as $status => $label) {
        register_post_status($status, [
            'label' => $label,
            'public' => true,
            'exclude_from_search' => false,
            'show_in_admin_all_list' => true,
            'show_in_admin_status_list' => true,
            'label_count' => _n_noop(
                $label . ' <span class="count">(%s)</span>',
                $label . ' <span class="count">(%s)</span>',
                'mini-notes'
            ),
        ]);
    }
}
add_action('init', 'mini_notes_register_order_statuses');

function mini_notes_order_statuses($statuses) {
    $updated = [];
    foreach ($statuses as $key => $label) {
        $updated[$key] = $label;
        if ($key === 'wc-processing') {
            $updated['wc-mn-packed'] = 'Packed';
            $updated['wc-mn-shipped'] = 'Shipped';
        }
    }
    if (!isset($updated['wc-mn-packed'])) {
        $updated['wc-mn-packed'] = 'Packed';
        $updated['wc-mn-shipped'] = 'Shipped';
    }
    return $updated;
}
add_filter('wc_order_statuses', 'mini_notes_order_statuses');

function mini_notes_paid_order_statuses($statuses) {
    $statuses[] = 'mn-packed';
    $statuses[] = 'mn-shipped';
    return $statuses;
}
add_filter('woocommerce_order_is_paid_statuses', 'mini_notes_paid_order_statuses');

function mini_notes_report_order_statuses($statuses) {
    if (!is_array($statuses)) {
        return $statuses;
    }
    $statuses[] = 'mn-packed';
    $statuses[] = 'mn-shipped';
    return $statuses;
}
add_filter('woocommerce_reports_order_statuses', 'mini_notes_report_order_statuses');

function mini_notes_order_bulk_actions($actions) {
    $actions['mark_mn-packed'] = 'Change status to packed';
    $actions['mark_mn-shipped'] = 'Change status to shipped';
    return $actions;
}
add_filter('bulk_actions-edit-shop_order', 'mini_notes_order_bulk_actions');
add_filter('bulk_actions-woocommerce_page_wc-orders', 'mini_notes_order_bulk_actions');

function mini_notes_order_status_admin_css() {
    $screen = function_exists('get_current_screen') ? get_current_screen() : null;
    if (!$screen || !in_array($screen->id, ['shop_order', 'edit-shop_order', 'woocommerce_page_wc-orders'], true)) {
        return;
    }
    echo '<style>.order-status.status-mn-packed{background:#d8e5ba;color:#2e584b}.order-status.status-mn-shipped{background:#2e584b;color:#fff}</style>';
}
add_action('admin_head', 'mini_notes_order_status_admin_css');

function mini_notes_email_actions($actions) {
    $actions[] = 'woocommerce_order_status_processing_to_mn-packed';
    $actions[] = 'woocommerce_order_status_on-hold_to_mn-packed';
    $actions[] = 'woocommerce_order_status_pending_to_mn-packed';
    $actions[] = 'woocommerce_order_status_mn-packed_to_mn-shipped';
    $actions[] = 'woocommerce_order_status_processing_to_mn-shipped';
    $actions[] = 'woocommerce_order_status_on-hold_to_mn-shipped';
    return $actions;
}
add_filter('woocommerce_email_actions', 'mini_notes_email_actions');

function mini_notes_email_color($value, $option) {
    $colors = [
        'woocommerce_email_base_color' => '#2e584b',
        'woocommerce_email_background_color' => '#fafbf7',
        'woocommerce_email_body_background_color' => '#ffffff',
        'woocommerce_email_text_color' => '#263b36',
        'woocommerce_email_footer_text_color' => '#7b8580',
    ];
    return $colors[$option] ?? $value;
}

foreach (array_keys([
    'woocommerce_email_base_color' => true,
    'woocommerce_email_background_color' => true,
    'woocommerce_email_body_background_color' => true,
    'woocommerce_email_text_color' => true,
    'woocommerce_email_footer_text_color' => true,
]) as $option) {
    add_filter('option_' . $option, function ($value) use ($option) {
        return mini_notes_email_color($value, $option);
    });
}

function mini_notes_email_classes($emails) {
    if (!class_exists('MN_Email_Customer_Order_Status', false)) {
        require_once get_template_directory() . '/inc/class-mn-email-customer-order-status.php';
    }

    $emails['MN_Email_Customer_Order_Packed'] = new MN_Email_Customer_Order_Status([
        'id' => 'mn_customer_order_packed',
        'title' => 'Order packed',
        'description' => 'Sent to the customer when an order is marked packed.',
        'subject' => 'Your {site_title} order {order_number} is packed',
        'heading' => 'Your order is packed',
        'intro' => 'We packed order {order_number} and it is ready to leave the studio.',
        'hooks' => [
            'woocommerce_order_status_processing_to_mn-packed_notification',
            'woocommerce_order_status_on-hold_to_mn-packed_notification',
            'woocommerce_order_status_pending_to_mn-packed_notification',
        ],
    ]);

    $emails['MN_Email_Customer_Order_Shipped'] = new MN_Email_Customer_Order_Status([
        'id' => 'mn_customer_order_shipped',
        'title' => 'Order shipped',
        'description' => 'Sent to the customer when an order is marked shipped.',
        'subject' => 'Your {site_title} order {order_number} is on the way',
        'heading' => 'Your order has shipped',
        'intro' => 'Order {order_number} has shipped. We will mark it complete once it arrives.',
        'hooks' => [
            'woocommerce_order_status_mn-packed_to_mn-shipped_notification',
            'woocommerce_order_status_processing_to_mn-shipped_notification',
            'woocommerce_order_status_on-hold_to_mn-shipped_notification',
        ],
    ]);

    return $emails;
}
add_filter('woocommerce_email_classes', 'mini_notes_email_classes');

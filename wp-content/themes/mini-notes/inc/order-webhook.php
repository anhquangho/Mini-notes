<?php
defined('ABSPATH') || exit;

if (!class_exists('WooCommerce')) {
    return;
}

function mini_notes_order_webhook_secret() {
    $secret = get_option('mini_notes_order_webhook_secret');
    if (!is_string($secret) || strlen($secret) < 32) {
        $secret = wp_generate_password(64, false, false);
        update_option('mini_notes_order_webhook_secret', $secret, false);
    }
    return $secret;
}

function mini_notes_order_webhook_url() {
    return rest_url('mini-notes/v1/order-events');
}

function mini_notes_ensure_order_webhooks() {
    static $done = false;
    if ($done || !class_exists('WC_Webhook')) {
        return;
    }
    $done = true;
    $admins = get_users([
        'role' => 'administrator',
        'number' => 1,
        'fields' => 'ID',
    ]);
    $owner = $admins ? (int) $admins[0] : 0;
    if (!$owner) {
        return;
    }
    global $wpdb;
    $wpdb->get_var("SELECT GET_LOCK('mn_order_webhooks', 10)");
    $topics = [
        'Mini Notes order created' => 'order.created',
        'Mini Notes order updated' => 'order.updated',
    ];
    $secret = mini_notes_order_webhook_secret();
    $url = mini_notes_order_webhook_url();
    foreach ($topics as $name => $topic) {
        mini_notes_ensure_order_webhook($name, $topic, $url, $secret, $owner);
    }
    $wpdb->get_var("SELECT RELEASE_LOCK('mn_order_webhooks')");
}
add_action('init', 'mini_notes_ensure_order_webhooks', 30);

function mini_notes_ensure_order_webhook($name, $topic, $url, $secret, $owner) {
    global $wpdb;
    $ids = array_map('intval', $wpdb->get_col($wpdb->prepare(
        "SELECT webhook_id FROM {$wpdb->prefix}wc_webhooks WHERE name = %s ORDER BY webhook_id ASC",
        $name
    )));
    $keep = $ids ? (int) array_shift($ids) : 0;
    foreach ($ids as $extra) {
        (new WC_Webhook($extra))->delete(true);
    }
    if ($keep) {
        $webhook = new WC_Webhook($keep);
        $changed = false;
        if ($webhook->get_status() !== 'active') {
            $webhook->set_status('active');
            $changed = true;
        }
        if ($webhook->get_delivery_url() !== $url) {
            $webhook->set_delivery_url($url);
            $changed = true;
        }
        if ($webhook->get_secret() !== $secret) {
            $webhook->set_secret($secret);
            $changed = true;
        }
        if ($webhook->get_topic() !== $topic) {
            $webhook->set_topic($topic);
            $changed = true;
        }
        if ($changed) {
            $webhook->save();
        }
        return;
    }
    $webhook = new WC_Webhook();
    $webhook->set_name($name);
    $webhook->set_status('active');
    $webhook->set_topic($topic);
    $webhook->set_delivery_url($url);
    $webhook->set_secret($secret);
    $webhook->set_user_id($owner);
    $webhook->set_api_version('wp_api_v3');
    $webhook->save();
    // This request already loaded webhooks before the row existed, so hook the new one now.
    $webhook->enqueue();
    $webhook->deliver_ping();
}

function mini_notes_register_order_webhook_route() {
    register_rest_route('mini-notes/v1', '/order-events', [
        'methods' => 'POST',
        'callback' => 'mini_notes_receive_order_webhook',
        'permission_callback' => '__return_true',
    ]);
}
add_action('rest_api_init', 'mini_notes_register_order_webhook_route');

function mini_notes_receive_order_webhook(WP_REST_Request $request) {
    $body = $request->get_body();
    // WooCommerce pings the URL when the webhook is saved. Anything other than 200 leaves it unreachable.
    if (preg_match('/^webhook_id=\d+$/', $body)) {
        return new WP_REST_Response(['ok' => true], 200);
    }
    $sent = (string) $request->get_header('x-wc-webhook-signature');
    $known = base64_encode(hash_hmac('sha256', $body, mini_notes_order_webhook_secret(), true));
    if ($sent === '' || !hash_equals($known, $sent)) {
        return new WP_REST_Response(['ok' => false], 401);
    }
    $payload = json_decode($body, true);
    if (!is_array($payload) || empty($payload['id'])) {
        return new WP_REST_Response(['ok' => true], 200);
    }
    $log = get_option('mini_notes_order_webhooks', []);
    if (!is_array($log)) {
        $log = [];
    }
    array_unshift($log, [
        'topic' => (string) $request->get_header('x-wc-webhook-topic'),
        'order_id' => isset($payload['id']) ? (int) $payload['id'] : 0,
        'status' => isset($payload['status']) ? (string) $payload['status'] : '',
        'total' => isset($payload['total']) ? (string) $payload['total'] : '',
        'received_at' => gmdate('c'),
    ]);
    update_option('mini_notes_order_webhooks', array_slice($log, 0, 5), false);
    return new WP_REST_Response(['ok' => true], 200);
}

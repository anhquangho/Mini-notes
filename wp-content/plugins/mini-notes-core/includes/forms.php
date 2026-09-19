<?php
defined('ABSPATH') || exit;

// PHP helper: accept strings only, because clients can also send field[]=value.
function mn_input($source, $key, $default = '') {
    return isset($source[$key]) && is_string($source[$key]) ? wp_unslash($source[$key]) : $default;
}

function mn_fail($message, $status = 400) {
    wp_die(esc_html($message), 'Mini Notes', ['response' => $status, 'back_link' => true]);
}

function mn_require_post() {
    if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
        mn_fail('Use the form to submit this request.', 405);
    }
}

function mn_require_member() {
    if (!is_user_logged_in()) {
        wp_safe_redirect(home_url('/login/'));
        exit;
    }
    if (!current_user_can('edit_notes')) {
        mn_fail('This account does not have Notes access.', 403);
    }
}

function mn_protect_pages() {
    if (is_page(['dashboard', 'login'])) {
        nocache_headers();
    }
    if (is_page('dashboard')) {
        mn_require_member();
        // Reject a forged selection before any private data is rendered.
        $raw_id = mn_input($_GET, 'note');
        if (isset($_GET['note']) && (!ctype_digit($raw_id) || (int) $raw_id < 1)) {
            mn_fail('Invalid note ID.', 400);
        }
        if ($raw_id !== '') {
            $note = get_post((int) $raw_id);
            if (!$note || $note->post_type !== 'note' || $note->post_status === 'trash') {
                mn_fail('Note not found.', 404);
            }
            if (!mn_owns_note($note->ID) || !current_user_can('read_post', $note->ID)) {
                mn_fail('You cannot open this note.', 403);
            }
        }
    }
    if (is_page('login') && is_user_logged_in()) {
        wp_safe_redirect(mn_workspace_url());
        exit;
    }
}
add_action('template_redirect', 'mn_protect_pages');

function mn_login() {
    mn_require_post();
    if (!wp_verify_nonce(mn_input($_POST, 'mn_nonce'), 'mn_login')) {
        mn_fail('This login form expired. Reload the login page.', 403);
    }
    // STEP 7: wp_signon verifies credentials and sets WordPress authentication cookies.
    $user = wp_signon([
        'user_login' => sanitize_text_field(mn_input($_POST, 'username')),
        'user_password' => mn_input($_POST, 'password'),
        'remember' => mn_input($_POST, 'remember') === '1',
    ], is_ssl());
    if (is_wp_error($user)) {
        wp_safe_redirect(add_query_arg('login', 'failed', home_url('/login/')));
        exit;
    }
    wp_safe_redirect(mn_workspace_url());
    exit;
}
add_action('admin_post_nopriv_mn_login', 'mn_login');
add_action('admin_post_mn_login', 'mn_login');

// STEP 9/10: POST -> nonce -> permission -> validate -> sanitize -> DB -> redirect.
function mn_save_note() {
    mn_require_post();
    mn_require_member();
    $raw_id = mn_input($_POST, 'note_id', '0');
    if (!ctype_digit($raw_id)) {
        mn_fail('Invalid note ID.');
    }
    $id = (int) $raw_id;
    if (!wp_verify_nonce(mn_input($_POST, 'mn_nonce'), 'mn_save_note_' . $id)) {
        mn_fail('This form expired. Reload the workspace and try again.', 403);
    }
    if ($id && (!mn_owns_note($id) || !current_user_can('edit_post', $id))) {
        mn_fail('You cannot edit this note.', 403);
    }
    if ($id && get_post_status($id) === 'trash') {
        mn_fail('This note is in Trash.', 404);
    }
    $title = sanitize_text_field(mn_input($_POST, 'title'));
    $content = sanitize_textarea_field(mn_input($_POST, 'content'));
    $priority = sanitize_text_field(mn_input($_POST, 'priority', 'normal'));
    $status = sanitize_text_field(mn_input($_POST, 'status', 'todo'));
    $category = sanitize_text_field(mn_input($_POST, 'category'));
    if ($title === '' || mb_strlen($title) > 180) {
        mn_fail('Title must contain 1–180 characters.');
    }
    if (mb_strlen($content) > 100000 || mb_strlen($category) > 60) {
        mn_fail('Content is too long (100,000 max) or category is too long (60 max).');
    }
    if (!isset(mn_choices('priority')[$priority]) || !isset(mn_choices('status')[$status])) {
        mn_fail('Choose a valid priority and status.');
    }
    $data = [
        'post_type' => 'note', 'post_status' => 'private',
        'post_title' => $title, 'post_content' => $content,
        'post_author' => get_current_user_id(),
    ];
    if ($id) {
        $data['ID'] = $id;
        $saved = wp_update_post(wp_slash($data), true);
    } else {
        $saved = wp_insert_post(wp_slash($data), true);
    }
    if (is_wp_error($saved)) {
        error_log('Mini Notes save failed: ' . $saved->get_error_message());
        mn_fail('Could not save this note. Please try again.', 500);
    }
    update_post_meta($saved, 'mn_priority', $priority);
    update_post_meta($saved, 'mn_status', $status);
    update_post_meta($saved, 'mn_category', $category);
    wp_safe_redirect(mn_workspace_url(['note' => $saved, 'message' => $id ? 'updated' : 'created']));
    exit;
}
add_action('admin_post_mn_save_note', 'mn_save_note');
add_action('admin_post_nopriv_mn_save_note', 'mn_save_note');

function mn_delete_note() {
    mn_require_post();
    mn_require_member();
    $raw_id = mn_input($_POST, 'note_id');
    if (!ctype_digit($raw_id) || (int) $raw_id < 1) {
        mn_fail('Invalid note ID.');
    }
    $id = (int) $raw_id;
    if (!wp_verify_nonce(mn_input($_POST, 'mn_nonce'), 'mn_delete_note_' . $id)) {
        mn_fail('This delete form expired. Reload the workspace.', 403);
    }
    if (!mn_owns_note($id) || !current_user_can('delete_post', $id)) {
        mn_fail('You cannot delete this note.', 403);
    }
    // Custom post types need wp_trash_post(); wp_delete_post() can delete them permanently.
    if (!wp_trash_post($id)) {
        mn_fail('Could not move the note to Trash.', 500);
    }
    wp_safe_redirect(mn_workspace_url(['message' => 'deleted']));
    exit;
}
add_action('admin_post_mn_delete_note', 'mn_delete_note');
add_action('admin_post_nopriv_mn_delete_note', 'mn_delete_note');

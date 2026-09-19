<?php
defined('ABSPATH') || exit;

// STEP 8: WordPress stores notes in wp_posts, identified by post_type = note.
function mn_register_note_type() {
    register_post_type('note', [
        'labels' => ['name' => 'Notes', 'singular_name' => 'Note', 'add_new_item' => 'Add new note'],
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true,
        'rest_base' => 'notes',
        'publicly_queryable' => false,
        'exclude_from_search' => true,
        'rewrite' => false,
        'query_var' => false,
        'menu_icon' => 'dashicons-welcome-write-blog',
        'supports' => ['title', 'editor', 'author', 'custom-fields'],
        'capability_type' => ['note', 'notes'],
        'map_meta_cap' => true,
    ]);
    // STEP 13: metadata lives in wp_postmeta; REST uses these same registered fields.
    foreach (['priority', 'status', 'category'] as $field) {
        register_post_meta('note', 'mn_' . $field, [
            'type' => 'string', 'single' => true, 'show_in_rest' => true,
            'sanitize_callback' => 'sanitize_text_field',
            'auth_callback' => function ($allowed, $key, $post_id) {
                return mn_owns_note($post_id) && current_user_can('edit_post', $post_id);
            },
        ]);
    }
}
add_action('init', 'mn_register_note_type');

function mn_choices($field) {
    $choices = [
        'priority' => ['low' => 'Low', 'normal' => 'Normal', 'high' => 'High'],
        'status' => ['todo' => 'To do', 'in-progress' => 'In progress', 'done' => 'Done'],
    ];
    return $choices[$field] ?? [];
}

function mn_owns_note($id, $user_id = 0) {
    $note = get_post($id);
    $user_id = $user_id ?: get_current_user_id();
    return $user_id > 0 && $note && $note->post_type === 'note'
        && (int) $note->post_author === (int) $user_id;
}

// STEP 9/10/20: deny access to someone else's note, including via wp-admin or REST.
// Even administrators use their own notes in this learning app.
function mn_owner_capabilities($caps, $cap, $user_id, $args) {
    if (in_array($cap, ['read_post', 'edit_post', 'delete_post', 'read_note', 'edit_note', 'delete_note'], true)
        && !empty($args[0])) {
        $note = get_post($args[0]);
        if ($note && $note->post_type === 'note' && !mn_owns_note($note->ID, $user_id)) {
            return ['do_not_allow'];
        }
    }
    return $caps;
}
add_filter('map_meta_cap', 'mn_owner_capabilities', 10, 4);

// A note always stays private, regardless of whether it was saved by a form, Admin or REST.
function mn_private_note_data($data, $postarr) {
    if ($data['post_type'] === 'note') {
        if (!in_array($data['post_status'], ['trash', 'auto-draft'], true)) {
            $data['post_status'] = 'private';
        }
        $original = !empty($postarr['ID']) ? get_post((int) $postarr['ID']) : null;
        if ($original && $original->post_type === 'note') {
            $data['post_author'] = $original->post_author;
        } elseif (get_current_user_id()) {
            $data['post_author'] = get_current_user_id();
        }
        $data['post_title'] = sanitize_text_field($data['post_title']);
        $data['post_content'] = sanitize_textarea_field($data['post_content']);
    }
    return $data;
}
add_filter('wp_insert_post_data', 'mn_private_note_data', 10, 2);

function mn_workspace_url($args = []) {
    return add_query_arg($args, home_url('/dashboard/'));
}

// STEP 5/12: array -> WP_Query -> wp_posts -> WP_Post objects.
function mn_query_notes($search = '', $page = 1) {
    return new WP_Query([
        'post_type' => 'note',
        'post_status' => 'private',
        'author' => get_current_user_id(),
        'post__in' => is_user_logged_in() ? [] : [0],
        's' => $search,
        'posts_per_page' => 20,
        'paged' => max(1, $page),
        'orderby' => 'modified',
        'order' => 'DESC',
    ]);
}

// STEP 18: the core REST controller handles serialization and CRUD.
// This boundary blocks anonymous access before it reaches the controller.
function mn_rest_auth($result, $server, $request) {
    if (preg_match('#^/wp/v2/notes(?:/|$)#', $request->get_route())) {
        if (!is_user_logged_in()) {
            return new WP_Error('mn_login_required', 'Please sign in.', ['status' => 401]);
        }
        if (!current_user_can('edit_notes')) {
            return new WP_Error('mn_forbidden', 'Notes access is not available.', ['status' => 403]);
        }
    }
    return $result;
}
add_filter('rest_pre_dispatch', 'mn_rest_auth', 10, 3);

function mn_rest_query($args, $request) {
    $args['author'] = get_current_user_id() ?: -1;
    unset($args['author__in'], $args['author__not_in']);
    $args['post_status'] = 'private';
    return $args;
}
add_filter('rest_note_query', 'mn_rest_query', 10, 2);

// Make REST validation match the HTML form rather than silently accepting arbitrary enum values.
function mn_rest_validate($prepared, $request) {
    if (is_wp_error($prepared)) {
        return $prepared;
    }
    if ((!$request->get_param('id') && empty($prepared->post_title)) || (isset($prepared->post_title) && (trim($prepared->post_title) === '' || mb_strlen($prepared->post_title) > 180))) {
        return new WP_Error('mn_title', 'Title must contain 1–180 characters.', ['status' => 400]);
    }
    if (isset($prepared->post_content) && mb_strlen($prepared->post_content) > 100000) {
        return new WP_Error('mn_content', 'Content must be at most 100,000 characters.', ['status' => 400]);
    }
    $meta = $request->get_param('meta') ?: [];
    foreach (['priority', 'status'] as $field) {
        $key = 'mn_' . $field;
        if (isset($meta[$key]) && !isset(mn_choices($field)[$meta[$key]])) {
            return new WP_Error('mn_meta', 'Invalid ' . $field . '.', ['status' => 400]);
        }
    }
    if (isset($meta['mn_category']) && mb_strlen($meta['mn_category']) > 60) {
        return new WP_Error('mn_category', 'Category must be at most 60 characters.', ['status' => 400]);
    }
    return $prepared;
}
add_filter('rest_pre_insert_note', 'mn_rest_validate', 10, 2);

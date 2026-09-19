<?php
defined('ABSPATH') || exit;

// STEP 14: keep the Admin list scoped to the signed-in owner too.
function mn_admin_query($query) {
    if (is_admin() && $query->is_main_query() && $query->get('post_type') === 'note') {
        $query->set('author', get_current_user_id() ?: -1);
    }
}
add_action('pre_get_posts', 'mn_admin_query');

// Global status counts can reveal that other users have notes; hide these shared views.
add_filter('views_edit-note', '__return_empty_array');

function mn_admin_columns($columns) {
    $columns['mn_priority'] = 'Priority';
    $columns['mn_status'] = 'Status';
    $columns['mn_category'] = 'Category';
    $columns['author'] = 'Author';
    return $columns;
}
add_filter('manage_note_posts_columns', 'mn_admin_columns');

function mn_admin_column($column, $id) {
    if (in_array($column, ['mn_priority', 'mn_status', 'mn_category'], true)) {
        $value = get_post_meta($id, $column, true);
        $field = substr($column, 3);
        $label = mn_choices($field)[$value] ?? $value;
        echo esc_html($label ?: '—');
    }
}
add_action('manage_note_posts_custom_column', 'mn_admin_column', 10, 2);

function mn_meta_box() {
    add_meta_box('mn-details', 'Note details', 'mn_render_meta_box', 'note', 'side');
}
add_action('add_meta_boxes', 'mn_meta_box');

function mn_render_meta_box($post) {
    wp_nonce_field('mn_admin_meta_' . $post->ID, 'mn_meta_nonce');
    foreach (['priority' => 'normal', 'status' => 'todo'] as $field => $default) {
        $current = get_post_meta($post->ID, 'mn_' . $field, true) ?: $default;
        echo '<p><label for="mn-' . esc_attr($field) . '">' . esc_html(ucfirst($field)) . '</label><br>';
        echo '<select id="mn-' . esc_attr($field) . '" name="mn_' . esc_attr($field) . '">';
        foreach (mn_choices($field) as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . selected($current, $value, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select></p>';
    }
    echo '<p><label for="mn-category">Category</label><br><input id="mn-category" name="mn_category" maxlength="60" value="' .
        esc_attr(get_post_meta($post->ID, 'mn_category', true)) . '"></p>';
    echo '<p>Notes remain private. Their author is fixed at creation.</p>';
}

function mn_save_admin_meta($id) {
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE || wp_is_post_revision($id)) {
        return;
    }
    if (!wp_verify_nonce(mn_input($_POST, 'mn_meta_nonce'), 'mn_admin_meta_' . $id)
        || !mn_owns_note($id) || !current_user_can('edit_post', $id)) {
        return;
    }
    foreach (['priority', 'status'] as $field) {
        $value = sanitize_text_field(mn_input($_POST, 'mn_' . $field));
        if (isset(mn_choices($field)[$value])) {
            update_post_meta($id, 'mn_' . $field, $value);
        }
    }
    update_post_meta($id, 'mn_category', mb_substr(sanitize_text_field(mn_input($_POST, 'mn_category')), 0, 60));
}
add_action('save_post_note', 'mn_save_admin_meta');
add_filter('use_block_editor_for_post_type', function ($use, $type) {
    return $type === 'note' ? false : $use;
}, 10, 2);

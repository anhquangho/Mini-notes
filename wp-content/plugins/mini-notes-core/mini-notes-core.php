<?php
/**
 * Plugin Name: Mini Notes Core
 * Description: Private notes, ownership, forms, metadata and REST support for WP Mini Notes.
 * Version: 1.0.0
 * Requires PHP: 8.1
 * Text Domain: mini-notes
 */
defined('ABSPATH') || exit;

// STEP 15: require loads business logic independently of the active theme.
require_once __DIR__ . '/includes/notes.php';
require_once __DIR__ . '/includes/forms.php';
require_once __DIR__ . '/includes/admin.php';

function mn_activate() {
    mn_register_note_type();
    $caps = [
        'read' => true, 'edit_notes' => true, 'publish_notes' => true,
        'edit_private_notes' => true, 'delete_notes' => true,
        'delete_private_notes' => true, 'edit_published_notes' => true,
        'delete_published_notes' => true,
    ];
    add_role('notes_member', 'Notes Member', $caps);
    foreach (['administrator', 'notes_member'] as $role_name) {
        $role = get_role($role_name);
        if ($role) {
            foreach ($caps as $cap => $grant) {
                $role->add_cap($cap, $grant);
            }
        }
    }
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'mn_activate');
register_deactivation_hook(__FILE__, 'flush_rewrite_rules');

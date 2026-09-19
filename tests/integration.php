<?php
// Run ONLY against a disposable local study database. Creates and removes its own fixtures.
if (PHP_SAPI !== 'cli' || getenv('MN_RUN_TESTS') !== '1') { exit("Set MN_RUN_TESTS=1 and MN_ROOT to a test installation.\n"); }
$_SERVER['HTTP_HOST'] = '127.0.0.1:8091';
$_SERVER['REQUEST_METHOD'] = 'GET';
require getenv('MN_ROOT') . '/wp-load.php';
$results = [];
function verify($name, $condition) {
    global $results;
    $results[] = [$name, (bool) $condition];
    echo ($condition ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
}
function request_rest($method, $route, $params = []) {
    $request = new WP_REST_Request($method, $route);
    $request->set_body_params($params);
    return rest_do_request($request);
}
$alice = get_user_by('login', 'alice');
$bob = get_user_by('login', 'bob');
if (!$alice || !$bob) { exit("Run the study setup first.\n"); }
$created = [];
try {
    wp_set_current_user($alice->ID);
    $a = wp_insert_post(['post_type' => 'note', 'post_status' => 'publish', 'post_author' => $bob->ID, 'post_title' => 'TEST-OWNERSHIP-ALPHA', 'post_content' => 'Original content']);
    $created[] = $a;
    verify('New note author is current user, not forged author', (int) get_post($a)->post_author === $alice->ID);
    verify('Notes are always private', get_post_status($a) === 'private');
    verify('Alice can edit her own note', current_user_can('edit_post', $a));
    $own = request_rest('GET', '/wp/v2/notes/' . $a);
    verify('Owner REST read succeeds', $own->get_status() === 200);
    $empty = request_rest('POST', '/wp/v2/notes', ['content' => 'No title']);
    verify('REST empty title rejected', $empty->get_status() === 400);
    $badmeta = request_rest('POST', '/wp/v2/notes/' . $a, ['meta' => ['mn_priority' => 'invalid']]);
    verify('REST invalid priority rejected', $badmeta->get_status() === 400);
    $goodmeta = request_rest('POST', '/wp/v2/notes/' . $a, ['title' => 'TEST-OWNERSHIP-ALPHA updated', 'meta' => ['mn_priority' => 'high', 'mn_status' => 'done', 'mn_category' => 'Tests']]);
    verify('Owner REST update succeeds', $goodmeta->get_status() === 200);
    verify('REST metadata persists', get_post_meta($a, 'mn_priority', true) === 'high');
    wp_set_current_user($bob->ID);
    $b = wp_insert_post(['post_type' => 'note', 'post_status' => 'private', 'post_title' => 'TEST-OWNERSHIP-BETA', 'post_content' => 'Bob private content']);
    $created[] = $b;
    verify('Bob cannot read Alice note', !current_user_can('read_post', $a));
    verify('Bob cannot edit Alice note', !current_user_can('edit_post', $a));
    verify('Bob cannot delete Alice note', !current_user_can('delete_post', $a));
    verify('REST other-owner read denied', request_rest('GET', '/wp/v2/notes/' . $a)->get_status() === 403);
    verify('REST other-owner edit denied', request_rest('POST', '/wp/v2/notes/' . $a, ['title' => 'Hacked'])->get_status() === 403);
    verify('REST other-owner delete denied', request_rest('DELETE', '/wp/v2/notes/' . $a)->get_status() === 403);
    verify('Other-owner title is unchanged', get_post($a)->post_title === 'TEST-OWNERSHIP-ALPHA updated');
    $list = request_rest('GET', '/wp/v2/notes', ['author' => $alice->ID, 'per_page' => 100]);
    $list_data = $list->get_data();
    verify('REST list request succeeds', $list->get_status() === 200);
    verify('REST forged author never reveals other notes', $list->get_status() === 200 && count(array_filter($list_data, fn($p) => (int) $p['author'] !== $bob->ID)) === 0);
    wp_set_current_user($alice->ID);
    verify('Search cannot return another owner note', mn_query_notes('TEST-OWNERSHIP-BETA')->found_posts === 0);
    verify('Search returns owner note', mn_query_notes('TEST-OWNERSHIP-ALPHA')->found_posts === 1);
    $nonce = wp_create_nonce('mn_save_note_' . $a);
    verify('Valid nonce verifies for own action', (bool) wp_verify_nonce($nonce, 'mn_save_note_' . $a));
    verify('Nonce is bound to note ID', !wp_verify_nonce($nonce, 'mn_save_note_' . $b));
    wp_set_current_user($bob->ID);
    verify('Nonce is bound to user', !wp_verify_nonce($nonce, 'mn_save_note_' . $a));
    wp_set_current_user(0);
    verify('Anonymous query returns no notes', mn_query_notes()->found_posts === 0);
    verify('Anonymous REST list denied', request_rest('GET', '/wp/v2/notes')->get_status() === 401);
    verify('Anonymous REST detail denied', request_rest('GET', '/wp/v2/notes/' . $a)->get_status() === 401);
    $admin = get_user_by('login', 'study_admin');
    wp_set_current_user($admin->ID);
    verify('Admin workspace cannot read another owner note', !current_user_can('read_post', $a));
    require_once ABSPATH . 'wp-admin/includes/theme.php';
    $original_theme = get_stylesheet();
    $fallbacks = array_filter(wp_get_themes(), fn($t) => $t->get_stylesheet() !== 'mini-notes');
    if ($fallbacks) {
        switch_theme(array_key_first($fallbacks));
        verify('Note CPT stays registered when theme changes', post_type_exists('note') && get_post($a)->post_type === 'note');
        switch_theme($original_theme);
    }
    wp_set_current_user($alice->ID);
    wp_trash_post($a);
    verify('Delete moves to recoverable Trash', get_post_status($a) === 'trash');
    wp_untrash_post($a);
    verify('Restore preserves privacy', get_post_status($a) === 'private');
} finally {
    foreach ($created as $id) { wp_delete_post($id, true); }
}
$failed = array_filter($results, fn($r) => !$r[1]);
echo count($results) . ' checks; ' . count($failed) . " failures.\n";
exit(count($failed) ? 1 : 0);

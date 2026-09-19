<?php
/**
 * CLI installer. Never put this file inside the WordPress web root.
 * Run via Install-Local.ps1 or set MN_ROOT / MN_URL / MN_DB / MN_ACCESS_FILE.
 */
if (PHP_SAPI !== 'cli') { http_response_code(403); exit('CLI only.'); }
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);
$root = getenv('MN_ROOT') ?: 'C:/xampp/htdocs/wp-mini-notes';
$url = getenv('MN_URL') ?: 'http://localhost:8080/wp-mini-notes';
$db_name = getenv('MN_DB') ?: 'wp_mini_notes';
$db_user = getenv('MN_DB_USER') ?: 'root';
$db_pass = getenv('MN_DB_PASS') ?: '';
$access_file = getenv('MN_ACCESS_FILE');
if (!$access_file) { throw new RuntimeException('Set MN_ACCESS_FILE to a private file outside the web root.'); }
if (!preg_match('/^[a-zA-Z0-9_]+$/', $db_name)) { throw new RuntimeException('Invalid database name.'); }
if (file_exists($root . '/wp-config.php')) { throw new RuntimeException('An existing wp-config.php was found. Installer stopped to preserve it.'); }
$db = new mysqli('127.0.0.1', $db_user, $db_pass, '', 3306);
$exists = $db->query("SHOW DATABASES LIKE '" . $db->real_escape_string(str_replace(['_', '%'], ['\\_', '\\%'], $db_name)) . "'")->num_rows > 0;
if ($exists) {
    $db->select_db($db_name);
    if ($db->query('SHOW TABLES')->num_rows > 0) {
        throw new RuntimeException('Target database is not empty. Choose a new database.');
    }
} else {
    $db->query("CREATE DATABASE \x60$db_name\x60 CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
}
$config = "<?php\n";
foreach (['DB_NAME' => $db_name, 'DB_USER' => $db_user, 'DB_PASSWORD' => $db_pass, 'DB_HOST' => '127.0.0.1:3306', 'DB_CHARSET' => 'utf8mb4', 'DB_COLLATE' => '', 'WP_ENVIRONMENT_TYPE' => 'local'] as $key => $value) {
    $config .= "define('$key', " . var_export($value, true) . ");\n";
}
foreach (['AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT'] as $key) {
    $config .= "define('$key', '" . bin2hex(random_bytes(32)) . "');\n";
}
$config .= "define('WP_DEBUG', true);\ndefine('WP_DEBUG_LOG', true);\ndefine('WP_DEBUG_DISPLAY', false);\n";
$config .= "define('DISALLOW_FILE_EDIT', true);\ndefine('WP_AUTO_UPDATE_CORE', false);\n";
$config .= "\$table_prefix = 'wp_';\nif (!defined('ABSPATH')) { define('ABSPATH', __DIR__ . '/'); }\nrequire_once ABSPATH . 'wp-settings.php';\n";
file_put_contents($root . '/wp-config.php', $config);
define('WP_INSTALLING', true);
$_SERVER['HTTP_HOST'] = parse_url($url, PHP_URL_HOST) . ':' . (parse_url($url, PHP_URL_PORT) ?: 80);
$_SERVER['SERVER_NAME'] = parse_url($url, PHP_URL_HOST);
$_SERVER['SERVER_PORT'] = parse_url($url, PHP_URL_PORT) ?: 80;
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
require_once $root . '/wp-load.php';
require_once ABSPATH . 'wp-admin/includes/upgrade.php';
require_once ABSPATH . 'wp-admin/includes/plugin.php';
$password = function () { return wp_generate_password(22, false); };
$admin_password = $password();
wp_install('WP Mini Notes', 'study_admin', 'study-admin@example.test', false, '', $admin_password);
update_option('home', $url);
update_option('siteurl', $url);
update_option('timezone_string', 'Asia/Bangkok');
update_option('blogdescription', 'Small notes. Clearer days.');
update_option('default_comment_status', 'closed');
update_option('default_ping_status', 'closed');
update_option('users_can_register', 0);
$admin = get_user_by('login', 'study_admin');
wp_set_current_user($admin->ID);
$result = activate_plugin('mini-notes-core/mini-notes-core.php');
if (is_wp_error($result)) { throw new RuntimeException($result->get_error_message()); }
switch_theme('mini-notes');
$pages = [
    'home' => ['Home', ''],
    'dashboard' => ['My notes', ''],
    'login' => ['Log in', ''],
    'about' => ['About Mini Notes', '<h2>A small space for everyday thoughts.</h2><p>Mini Notes is a personal notebook. Write in plain text, add a priority and keep track of what is next.</p><h2>Make it yours</h2><p>Create a note, give it a category, and find it again with search. Changes are saved when you press Save changes.</p><h2>Your notes stay with you</h2><p>Each account has its own notes. Deleted notes go to WordPress Trash, where their owner can restore them.</p>'],
];
foreach ($pages as $slug => [$title, $content]) {
    $id = wp_insert_post(['post_type' => 'page', 'post_status' => 'publish', 'post_title' => $title, 'post_name' => $slug, 'post_content' => $content], true);
    if (is_wp_error($id)) { throw new RuntimeException($id->get_error_message()); }
    if ($slug === 'home') {
        update_option('show_on_front', 'page');
        update_option('page_on_front', $id);
    }
}
$category = wp_create_category('Notebook');
$sample_post = wp_insert_post([
    'post_type' => 'post', 'post_status' => 'publish', 'post_title' => 'A small habit, a clearer day',
    'post_content' => '<p>Start with one thought. It does not need to be finished, polished or important yet.</p><p>Write down what is on your mind, give it a title, and leave a little room to come back. A notebook becomes useful one small entry at a time.</p>',
    'post_category' => [$category],
]);
$accounts = [['username' => 'study_admin', 'password' => $admin_password, 'role' => 'administrator', 'id' => $admin->ID]];
foreach (['alice' => 'Alice', 'bob' => 'Bob'] as $login => $display) {
    $pass = $password();
    $id = wp_insert_user(['user_login' => $login, 'user_pass' => $pass, 'user_email' => $login . '@example.test', 'display_name' => $display, 'role' => 'notes_member']);
    if (is_wp_error($id)) { throw new RuntimeException($id->get_error_message()); }
    $accounts[] = ['username' => $login, 'password' => $pass, 'role' => 'notes_member', 'id' => $id];
}
$samples = [
    ['A little plan for the week', "A few things worth making time for:\n\n• Finish one small task before starting another.\n• Leave room for an unexpected idea.\n• Take a proper break away from the screen.\n\nProgress can be quiet. Keep going.", 'high', 'in-progress', 'Personal'],
    ['Ideas worth keeping', "A tiny reading corner by the window.\nA weekend without too many plans.\nA better way to organise meeting notes.\n\nNo pressure to act on everything. Just collect what feels interesting.", 'normal', 'todo', 'Ideas'],
    ['Meeting notes · Monday', "What we discussed\n\nThe next milestone is a simpler first version. Focus on the essential flow and test it with two different accounts.\n\nNext actions\n\nWrite down the open questions.\nShare a clear summary.\nCheck in again on Friday.", 'high', 'in-progress', 'Work'],
    ['A reminder to slow down', "You do not have to finish every idea today.\n\nPick one thing. Give it your attention.\nThe rest can wait on this page.", 'low', 'done', 'Personal'],
];
foreach ($accounts as $account) {
    wp_set_current_user($account['id']);
    $rows = $account['username'] === 'bob' ? [['Bob private notebook', 'Only Bob should see this note. Use this record to test ownership.', 'normal', 'todo', 'Personal']] : $samples;
    foreach ($rows as [$title, $content, $priority, $status, $cat]) {
        $id = wp_insert_post(['post_type' => 'note', 'post_status' => 'private', 'post_author' => $account['id'], 'post_title' => $title, 'post_content' => $content], true);
        if (is_wp_error($id)) { throw new RuntimeException($id->get_error_message()); }
        update_post_meta($id, 'mn_priority', $priority);
        update_post_meta($id, 'mn_status', $status);
        update_post_meta($id, 'mn_category', $cat);
    }
}
wp_set_current_user($admin->ID);
global $wp_rewrite;
$wp_rewrite->set_permalink_structure('/%postname%/');
flush_rewrite_rules(true);
// PHP CLI may not detect Apache; explicitly write the standard local rewrite rules.
$base_path = rtrim(parse_url($url, PHP_URL_PATH) ?: '', '/') . '/';
$htaccess = "# BEGIN WordPress\n<IfModule mod_rewrite.c>\nRewriteEngine On\nRewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]\nRewriteBase {$base_path}\nRewriteRule ^index\\.php$ - [L]\nRewriteCond %{REQUEST_FILENAME} !-f\nRewriteCond %{REQUEST_FILENAME} !-d\nRewriteRule . {$base_path}index.php [L]\n</IfModule>\n# END WordPress\n";
file_put_contents($root . '/.htaccess', $htaccess);
file_put_contents($access_file, json_encode(['url' => $url, 'database' => $db_name, 'accounts' => $accounts], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
echo "Installed WP Mini Notes. Credentials saved outside web root.\n";

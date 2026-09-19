<?php
// STEP 16: isolated CLI lab. Never deploy as a web endpoint.
if (PHP_SAPI !== 'cli') { exit('CLI only.'); }
ini_set('display_errors', '1');
error_reporting(E_ALL);
$mode = $argv[1] ?? 'inspect';
switch ($mode) {
    case 'inspect':
        $note = ['title' => 'Debug me', 'priority' => 'high'];
        var_dump($note);
        print_r($note);
        error_log('Mini Notes debug lab: inspect completed.');
        break;
    case 'undefined':
        echo $missing_variable; // Deliberate warning: locate this line from the error.
        break;
    case 'syntax':
        echo "Copy this broken line into a scratch file: \x24title = 'Missing semicolon'\n";
        echo "Add another statement, then run php -l on that scratch file.\n";
        break;
    default:
        echo "Modes: inspect | undefined | syntax\n";
}

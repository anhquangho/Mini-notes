<?php
// STEP 1: PHP only, no WordPress dependency. Run: C:\xampp\php\php.exe labs\php-basics.php
if (PHP_SAPI !== 'cli') { exit('Run in CLI.'); }
$project = 'WP Mini Notes'; // string
$notes = require __DIR__ . '/data.php'; // file returns an associative-array list
function describe_note(array $note): string {
    if ($note['done']) {
        return $note['title'] . ' — completed';
    } elseif ($note['priority'] === 'high') {
        return $note['title'] . ' — do next';
    }
    return $note['title'] . ' — later';
}
echo $project . PHP_EOL;
foreach ($notes as $index => $note) {
    echo ($index + 1) . '. ' . describe_note($note) . PHP_EOL;
}
for ($i = 0; $i < count($notes); $i++) {
    echo 'Index ' . $i . ': ' . $notes[$i]['priority'] . PHP_EOL;
}
$status = 'todo';
switch ($status) {
    case 'done': echo "Finished\n"; break;
    case 'todo': echo "Ready to start\n"; break;
    default: echo "In progress\n";
}
// Exercise: change the returned array, not the function, then predict output.
// include also loads a file, but a missing include emits a warning and can continue;
// a missing require throws an Error and stops if not caught.

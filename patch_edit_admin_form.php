<?php
$file = 'admin/edit_admin.php';
$content = file_get_contents($file);

// 1. Remove the wrongly inserted token blocks
$bad_insertion = '
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION[\'csrf_token\'] ?? \'\') ?>">
';
$content = str_replace($bad_insertion, '', $content);

// 2. Properly insert CSRF token after every `<form ...>` 
// We will look for `method="POST"` which is in all these forms, or just manually do it.
// Let's replace `method="POST"` with `method="POST"> <input ...`? No, wait. 
// A form tag ends with `>`. We can just add it right after `<form...>` by matching properly.
// The forms in edit_admin.php are:
// <form action="..." method="POST" class="...">
// <form action="..." method="POST"\n class="...">

$content = preg_replace('/(<form[^>]+method="POST"[^>]*>)/is', '$1' . "\n" . '        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION[\'csrf_token\'] ?? \'\') ?>">' . "\n", $content);

file_put_contents($file, $content);
echo "Fixed form tags in edit_admin.php\n";
?>


<?php
$file = 'includes/db.php';
$content = file_get_contents($file);

$migration = <<<PHP
    // Migration to add full_name
    \$checkFullName = \$pdo->prepare("SHOW COLUMNS FROM `Users` LIKE 'full_name'");
    \$checkFullName->execute();
    if (\$checkFullName->rowCount() == 0) {
        \$pdo->exec("ALTER TABLE `Users` ADD COLUMN `full_name` VARCHAR(255) NULL AFTER `username`");
    }
PHP;

$content = str_replace(
    '// Migration to add is_active to users for account disabling',
    $migration . "\n\n    // Migration to add is_active to users for account disabling",
    $content
);

file_put_contents($file, $content);
echo "Updated db.php\n";
?>


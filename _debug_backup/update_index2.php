<?php
$content = file_get_contents('admin/index.php');

$replacements = [
    [
        '<!-- Manage Products -->',
        '<?php if ($_SESSION["module_stock"]): ?>' . "\n            <!-- Manage Products -->"
    ],
    [
        '<p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Add, edit, and delete products.</p>' . "\r\n            </a>",
        '<p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Add, edit, and delete products.</p>' . "\r\n            </a>\r\n            <?php endif; ?>"
    ],
    [
        '<!-- Manage Categories -->',
        '<?php if ($_SESSION["module_stock"]): ?>' . "\n            <!-- Manage Categories -->"
    ],
    [
        '<p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Add, edit, and delete categories.</p>' . "\r\n            </a>",
        '<p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Add, edit, and delete categories.</p>' . "\r\n            </a>\r\n            <?php endif; ?>"
    ],
    [
        '<!-- Manage Suppliers -->',
        '<?php if ($_SESSION["module_supply"]): ?>' . "\n            <!-- Manage Suppliers -->"
    ],
    [
        '<p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Add, edit, and delete suppliers.</p>' . "\r\n            </a>",
        '<p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Add, edit, and delete suppliers.</p>' . "\r\n            </a>\r\n            <?php endif; ?>"
    ],
    [
        '<!-- Add Product -->',
        '<?php if ($_SESSION["module_stock"]): ?>' . "\n            <!-- Add Product -->"
    ],
    [
        '<p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Quickly add a new item to the store.</p>' . "\r\n            </a>",
        '<p class="text-sm text-gray-500 dark:text-gray-400 mt-1">Quickly add a new item to the store.</p>' . "\r\n            </a>\r\n            <?php endif; ?>"
    ],
    [
        '<!-- Manage Sales & Orders -->',
        '<?php if ($_SESSION["module_pos"]): ?>' . "\n            <!-- Manage Sales & Orders -->"
    ],
    [
        '<p class="text-sm text-gray-500 dark:text-gray-400 mt-1">View shop sales and track orders.</p>' . "\r\n            </a>",
        '<p class="text-sm text-gray-500 dark:text-gray-400 mt-1">View shop sales and track orders.</p>' . "\r\n            </a>\r\n            <?php endif; ?>"
    ]
];

foreach ($replacements as $rep) {
    $content = str_replace($rep[0], $rep[1], $content);
}

// Ensure no BOM
$Utf8NoBomEncoding = New-Object System.Text.UTF8Encoding $False;
file_put_contents('admin/index.php', $content);
echo "Updated admin/index.php successfully.";
?>

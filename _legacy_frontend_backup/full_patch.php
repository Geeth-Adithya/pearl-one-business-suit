<?php
$file = 'admin/product_add.php';
$content = file_get_contents($file);

// 1. Backend generation logic
$backend_validation = <<<PHP
    // Auto-generate base code if missing
    \$prefix = 'ITM';
    if (!empty(\$category_ids)) {
        \$first_cat_id = \$category_ids[0];
        \$stmt = \$pdo->prepare("SELECT name FROM Categories WHERE id = ?");
        \$stmt->execute([\$first_cat_id]);
        \$cat_name = \$stmt->fetchColumn();
        if (\$cat_name) {
            \$clean_name = preg_replace('/[^a-zA-Z0-9]/', '', \$cat_name);
            if (strlen(\$clean_name) > 0) {
                \$prefix = strtoupper(substr(\$clean_name, 0, 3));
                if (strlen(\$prefix) < 3) \$prefix = str_pad(\$prefix, 3, 'X');
            }
        }
    }

    \$base_code = \$_POST['base_item_code'] ?? '';
    if (empty(trim(\$base_code))) {
        do {
            \$code = \$prefix . '-' . mt_rand(1000, 9999);
            \$check = \$pdo->prepare("SELECT COUNT(*) FROM Products WHERE item_code LIKE ?");
            \$check->execute([\$code . '%']);
        } while (\$check->fetchColumn() > 0);
        \$base_code = \$code;
    }

    \$is_multiple = count(\$variant_rows) > 1;
    \$used_codes = [];
    \$variant_counter = 1;

    foreach (\$variant_rows as &\$variant) {
        if (\$is_multiple) {
            do {
                \$test_code = \$base_code . '-' . \$variant_counter;
                \$check = \$pdo->prepare("SELECT COUNT(*) FROM Products WHERE item_code = ?");
                \$check->execute([\$test_code]);
                \$exists = \$check->fetchColumn() > 0 || in_array(\$test_code, \$used_codes);
                if (\$exists) \$variant_counter++;
            } while (\$exists);
            \$variant['item_code'] = \$test_code;
            \$variant_counter++;
        } else {
            \$variant['item_code'] = \$base_code;
        }
        \$used_codes[] = \$variant['item_code'];
    }
    unset(\$variant);

    foreach (\$variant_rows as \$variant) {
        if (\$variant['purchasing_price'] === '' || \$variant['margin_percent'] === '' || \$variant['selling_price'] === '' || (\$is_multiple && \$variant['attribute'] === '')) {
            \$error = 'Please complete every required field for each product variant.';
            break;
        }
    }
PHP;

$content = preg_replace('/foreach \(\$variant_rows as \$variant\) \{.*?break;\s*\}\s*\}/s', $backend_validation, $content, 1);
$content = preg_replace('/is_numeric\(\$purchasing_price\) && is_numeric\(\$margin_percent\)[^:]*: \(\$selling_prices\[\$index\] \?\? \'\'\)/s', 'is_numeric($selling_prices[$index] ?? null) ? number_format((float)$selling_prices[$index], 2, ".", "") : ""', $content, 1);

// 2. HTML Layout Replacement (Basic Info block)
$html_top_search = '/<!-- Basic Info -->.*?<\/div>\s*<\/div>/s';
$html_top_replace = <<<HTML
            <!-- Basic Info & Restructured Layout -->
            <div>
                <div class="relative" x-data="{ open: false, selectedCount: 0 }"
                    x-init="selectedCount = \$root.querySelectorAll('input[name=\'category_ids[]\']:checked').length">
                    <div class="flex justify-between items-center">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Categories</label>
                        <a href="manage_categories.php" class="text-xs text-brand-blue hover:underline flex items-center gap-1" target="_blank">
                            <ion-icon name="add-circle-outline"></ion-icon> Add New Category
                        </a>
                    </div>
                    <button type="button" @click="open = !open"
                        class="mt-1 w-full flex justify-between items-center text-left border border-black dark:border-gray-600 rounded-md shadow-sm px-3 py-2 bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
                        <span x-text="selectedCount > 0 ? `\${selectedCount} selected` : 'Select Categories'"></span>
                        <ion-icon name="chevron-down-outline" class="transition-transform text-gray-500" :class="{ 'rotate-180': open }"></ion-icon>
                    </button>
                    <div x-show="open" @click.away="open = false" x-transition style="display: none;"
                        class="origin-top-right absolute right-0 mt-2 w-full rounded-md shadow-lg bg-white dark:bg-gray-800 ring-1 ring-black ring-opacity-5 focus:outline-none z-10">
                        <div class="p-4 h-48 overflow-y-auto space-y-2">
                            <?php foreach (\$categories as \$cat): ?>
                                <div class="flex items-center">
                                    <input id="cat_<?= \$cat['id'] ?>" name="category_ids[]" type="checkbox"
                                        value="<?= \$cat['id'] ?>" data-name="<?= htmlspecialchars(\$cat['name']) ?>"
                                        @change="selectedCount = \$event.target.checked ? selectedCount + 1 : selectedCount - 1"
                                        class="h-4 w-4 text-brand-blue focus:ring-brand-blue border-black rounded">
                                    <label for="cat_<?= \$cat['id'] ?>"
                                        class="ml-3 block text-sm text-gray-900 dark:text-gray-300">
                                        <?= htmlspecialchars(\$cat['name']) ?>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Item Code (Base)</label>
                <div class="flex gap-2 mt-1">
                    <input type="text" id="base_item_code" name="base_item_code" placeholder="Auto-generated on category select"
                        class="block w-full border-black dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue focus:border-brand-blue bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
                    <button type="button" id="btn_regen_code" class="px-3 py-2 bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-md border border-gray-400 hover:bg-gray-300 dark:hover:bg-gray-600" title="Regenerate Code">
                        <ion-icon name="refresh-outline"></ion-icon>
                    </button>
                </div>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Product Name *</label>
                <input type="text" name="name" required
                    class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue focus:border-brand-blue bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type (Optional)</label>
                <input type="text" name="type" placeholder=""
                    class="mt-1 block w-full border-black dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue focus:border-brand-blue bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white px-3 py-2">
            </div>
HTML;
$content = preg_replace($html_top_search, $html_top_replace, $content, 1);

// 3. Variant UI tweaks
$toggleHtml = <<<HTML
            <!-- Product Variants -->
            <div class="md:col-span-2">
                <div class="mb-4 flex items-center justify-between">
                    <label class="text-sm font-medium text-gray-700 dark:text-gray-300">Enable Product Variants (Size, Color, etc.)</label>
                    <label class="inline-flex relative items-center cursor-pointer">
                        <input type="checkbox" id="enable_variants_toggle" class="sr-only peer">
                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-brand-blue/30 dark:peer-focus:ring-brand-blue/80 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-brand-blue"></div>
                    </label>
                </div>
                <div class="flex items-center justify-between gap-4 mb-3">
                    <div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white" id="variants_title">Pricing & Inventory</h2>
                        <p class="text-sm text-gray-500 dark:text-gray-400" id="variants_subtitle">Enter pricing details.</p>
                    </div>
                    <button type="button" id="add-variant"
                        class="hidden rounded-md bg-brand-blue px-3 py-2 text-sm font-medium text-white hover:bg-brand-blueDark">
                        <ion-icon name="add-outline"></ion-icon> Add Variant
                    </button>
                </div>
HTML;
$content = preg_replace('/<!-- Product Variants -->.*?<\/button>\s*<\/div>/s', $toggleHtml, $content, 1);
$content = preg_replace('/<div>\s*<label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Attribute<\/label>/', '<div class="attr-col hidden"><label class="block text-xs font-medium text-gray-700 dark:text-gray-300">Attribute</label>', $content);
$content = str_replace('<button type="button" class="remove-variant mb-1 p-2 text-red-600 hover:text-red-800"', '<button type="button" class="remove-variant remove-col hidden mb-1 p-2 text-red-600 hover:text-red-800"', $content);
$content = str_replace('required readonly', 'required', $content);
$content = preg_replace('/class="variant-selling([^"]*)bg-gray-100 dark:bg-gray-600([^"]*)"/', 'class="variant-selling$1bg-white dark:bg-gray-800$2"', $content);

// Make the variant item code input hidden, or rather readonly with grey bg
$content = preg_replace('/<input type="text" name="item_code\[\]" value="<\?= htmlspecialchars\(\$variant\[\'item_code\'\]\) \?>"([^>]*)>/', '<input type="text" name="item_code[]" value="<?= htmlspecialchars($variant[\'item_code\']) ?>" readonly class="variant-input mt-1 block w-full rounded-md border-black dark:border-gray-500 bg-gray-200 dark:bg-gray-600 text-gray-900 dark:text-white px-3 py-2">', $content);
// We also need to remove the "required" from item_code if it was there. It was handled in the regex above because we matched everything inside.

// 4. Javascript
$newJs = <<<JS
<script>
    const variantRows = document.getElementById('variant-rows');
    const addVariantButton = document.getElementById('add-variant');
    const baseItemCodeInput = document.getElementById('base_item_code');
    const regenBtn = document.getElementById('btn_regen_code');
    const categoryCheckboxes = document.querySelectorAll('input[name="category_ids[]"]');

    function updateSellingPrice(row) {
        const purchasingPrice = parseFloat(row.querySelector('.variant-purchase').value);
        const marginPercent = parseFloat(row.querySelector('.variant-margin').value);
        const sellingPrice = row.querySelector('.variant-selling');
        if (Number.isFinite(purchasingPrice) && Number.isFinite(marginPercent)) {
            sellingPrice.value = (purchasingPrice * (1 + marginPercent / 100)).toFixed(2);
        }
    }

    function updateMargin(row) {
        const purchasingPrice = parseFloat(row.querySelector('.variant-purchase').value);
        const sellingPrice = parseFloat(row.querySelector('.variant-selling').value);
        const marginPercent = row.querySelector('.variant-margin');
        if (Number.isFinite(purchasingPrice) && Number.isFinite(sellingPrice) && purchasingPrice > 0) {
            marginPercent.value = (((sellingPrice - purchasingPrice) / purchasingPrice) * 100).toFixed(2);
        }
    }

    function bindVariantRow(row) {
        row.querySelector('.variant-purchase').addEventListener('input', () => updateSellingPrice(row));
        row.querySelector('.variant-margin').addEventListener('input', () => updateSellingPrice(row));
        row.querySelector('.variant-selling').addEventListener('input', () => updateMargin(row));
        
        row.querySelector('.remove-variant').addEventListener('click', () => {
            if (variantRows.querySelectorAll('.variant-row').length > 1) {
                row.remove();
                syncVariantItemCodes();
            }
        });
    }

    function syncVariantItemCodes() {
        const base = baseItemCodeInput.value.trim();
        const rows = variantRows.querySelectorAll('.variant-row');
        const isMultiple = document.getElementById('enable_variants_toggle') && document.getElementById('enable_variants_toggle').checked;
        
        rows.forEach((row, index) => {
            const input = row.querySelector('input[name="item_code[]"]');
            if (input) {
                if (base === '') {
                    input.value = '';
                } else if (isMultiple && rows.length > 1) {
                    input.value = base + '-' + (index + 1);
                } else {
                    input.value = base;
                }
            }
        });
    }

    function generateRandomCode(prefix) {
        return prefix + '-' + Math.floor(1000 + Math.random() * 9000);
    }

    function forceRegenBaseCode() {
        let prefix = 'ITM';
        for (let cb of categoryCheckboxes) {
            if (cb.checked) {
                let name = cb.getAttribute('data-name').replace(/[^a-zA-Z0-9]/g, '');
                if (name.length > 0) {
                    prefix = name.substring(0, 3).toUpperCase();
                    if (prefix.length < 3) prefix = prefix.padEnd(3, 'X');
                }
                break;
            }
        }
        baseItemCodeInput.value = generateRandomCode(prefix);
        syncVariantItemCodes();
    }

    function autoGenBaseCode() {
        if (baseItemCodeInput.value.trim() !== '') return; 
        forceRegenBaseCode();
    }

    if (regenBtn) regenBtn.addEventListener('click', forceRegenBaseCode);
    categoryCheckboxes.forEach(cb => cb.addEventListener('change', autoGenBaseCode));
    baseItemCodeInput.addEventListener('input', syncVariantItemCodes);

    function updateAttributeRequirement() {
        const toggle = document.getElementById('enable_variants_toggle');
        const isEnabled = toggle ? toggle.checked : false;
        
        variantRows.querySelectorAll('input[name="attribute[]"]').forEach(input => {
            input.required = isEnabled;
        });
        
        const attrCols = document.querySelectorAll('.attr-col');
        const removeCols = document.querySelectorAll('.remove-col');
        const addVariantBtn = document.getElementById('add-variant');
        
        if (isEnabled) {
            attrCols.forEach(col => col.classList.remove('hidden'));
            removeCols.forEach(col => col.classList.remove('hidden'));
            if (addVariantBtn) addVariantBtn.classList.remove('hidden');
            const t = document.getElementById('variants_title');
            if (t) t.innerText = 'Product Variants';
            const s = document.getElementById('variants_subtitle');
            if (s) s.innerText = 'Add one row for each attribute and price.';
        } else {
            attrCols.forEach(col => col.classList.add('hidden'));
            removeCols.forEach(col => col.classList.add('hidden'));
            if (addVariantBtn) addVariantBtn.classList.add('hidden');
            const t = document.getElementById('variants_title');
            if (t) t.innerText = 'Pricing & Inventory';
            const s = document.getElementById('variants_subtitle');
            if (s) s.innerText = 'Enter pricing details.';
            
            const rows = variantRows.querySelectorAll('.variant-row');
            for(let i = 1; i < rows.length; i++) {
                rows[i].remove();
            }
        }
        syncVariantItemCodes();
    }
    
    const enableToggle = document.getElementById('enable_variants_toggle');
    if (enableToggle) enableToggle.addEventListener('change', updateAttributeRequirement);

    variantRows.querySelectorAll('.variant-row').forEach(bindVariantRow);
    
    if (variantRows.querySelectorAll('.variant-row').length > 1 || (variantRows.querySelector('input[name="attribute[]"]') && variantRows.querySelector('input[name="attribute[]"]').value !== '')) {
        if(enableToggle) enableToggle.checked = true;
    }
    updateAttributeRequirement();
    
    if (addVariantButton) {
        addVariantButton.addEventListener('click', () => {
            if (variantRows.querySelectorAll('.variant-row').length >= 5) {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'warning',
                        title: 'Limit Reached',
                        text: 'You can only add up to 5 variants at a time.',
                        confirmButtonColor: '#3085d6'
                    });
                } else {
                    alert('Maximum 5 variants allowed.');
                }
                return;
            }
            const row = variantRows.querySelector('.variant-row').cloneNode(true);
            row.querySelectorAll('input').forEach(input => { if(input.name !== 'item_code[]') input.value = ''; });
            variantRows.appendChild(row);
            bindVariantRow(row);
            updateAttributeRequirement();
        });
    }
</script>
JS;

$content = preg_replace('/<script>.*?<\/script>/s', $newJs, $content);
file_put_contents($file, $content);
echo "Successfully fully patched product_add.php\n";
?>


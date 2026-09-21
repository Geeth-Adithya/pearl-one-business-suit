<?php
$content = file_get_contents('user/dashboard.php');

// UI stock display
$pattern1 = '/<h3 class="font-bold text-gray-900 dark:text-white text-center text-\[15px\] leading-tight \s*mb-2" x-text="product\.name \+ \(product\.attribute \? \' - \' \+ product\.attribute : \'\'\)"><\/h3>/is';
$replacement1 = '<h3 class="font-bold text-gray-900 dark:text-white text-center text-[15px] leading-tight mb-2" x-text="product.name + (product.attribute ? \' - \' + product.attribute : \'\')"></h3>
                        <?php if (!empty([\'module_stock\'])): ?>
                          <div class="text-xs text-center mb-2 font-medium" :class="product.stock_quantity <= (product.low_stock_threshold || 10) ? \'text-red-500\' : \'text-gray-500 dark:text-gray-400\'" x-text="\'Stock: \' + product.stock_quantity"></div>
                        <?php endif; ?>';

$content = preg_replace($pattern1, $replacement1, $content);

// addToCart logic
$pattern2 = '/addToCart\(product\) \{\s*const existing = this\.cart\.find\(i => i\.id === product\.id\);\s*if \(existing\) \{\s*existing\.qty\+\+;\s*\} else \{\s*this\.cart\.push\(\{.*?\}\);\s*\}/is';
$replacement2 = 'addToCart(product) {
            const existing = this.cart.find(i => i.id === product.id);
            const currentQty = existing ? existing.qty : 0;
            
            <?php if (!empty([\'module_stock\'])): ?>
            if (currentQty >= product.stock_quantity) {
                alert(\'Not enough stock available.\');
                return;
            }
            <?php endif; ?>

            if (existing) {
                existing.qty++;
            } else {
                this.cart.push({...product, qty: 1});
            }';

$content = preg_replace($pattern2, $replacement2, $content);

// increaseQty logic
$pattern3 = '/increaseQty\(item\) \{\s*item\.qty\+\+;\s*\}/is';
$replacement3 = 'increaseQty(item) {
            <?php if (!empty([\'module_stock\'])): ?>
            if (item.qty >= item.stock_quantity) {
                alert(\'Not enough stock available.\');
                return;
            }
            <?php endif; ?>
            item.qty++;
        }';

$content = preg_replace($pattern3, $replacement3, $content);

file_put_contents('user/dashboard.php', $content);
echo "dashboard.php updated!";
?>

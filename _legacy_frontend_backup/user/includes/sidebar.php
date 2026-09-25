<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside x-data="{ sidebarExpanded: false }" 
       :class="sidebarExpanded ? 'w-56 absolute sm:relative shadow-2xl sm:shadow-none' : 'w-14 sm:w-16'"
       class="flex-shrink-0 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700 h-full flex flex-col z-10 transition-all duration-300 relative">
    
    <!-- Sidebar Header / Toggle -->
    <div class="flex items-center justify-center py-4">
        <button @click="sidebarExpanded = !sidebarExpanded" 
                class="text-gray-500 hover:text-brand-blue dark:text-gray-400 dark:hover:text-brand-blue transition flex items-center justify-center w-10 h-10 rounded-xl hover:bg-gray-100 dark:hover:bg-gray-700">
            <ion-icon :name="sidebarExpanded ? 'chevron-back' : 'menu'" class="text-3xl"></ion-icon>
        </button>
    </div>

    <nav class="flex-1 space-y-2 px-2 overflow-y-auto no-scrollbar overflow-x-hidden">
        <!-- POS Terminal -->
        <a href="<?= BASE_URL ?>/user/dashboard.php" 
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all duration-200 <?= $current_page === 'dashboard.php' || $current_page === 'checkout.php' ? 'bg-brand-blue text-white shadow-md' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white' ?>"
           title="POS Terminal">
            <ion-icon name="cart" class="text-2xl flex-shrink-0"></ion-icon>
            <span x-show="sidebarExpanded" class="font-medium whitespace-nowrap transition-opacity duration-300">POS Terminal</span>
        </a>

        <!-- Sales Manage -->
        <a href="<?= BASE_URL ?>/user/sales_manage.php" 
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all duration-200 <?= $current_page === 'sales_manage.php' ? 'bg-brand-blue text-white shadow-md' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white' ?>"
           title="Sales Manage">
            <ion-icon name="stats-chart" class="text-2xl flex-shrink-0"></ion-icon>
            <span x-show="sidebarExpanded" class="font-medium whitespace-nowrap transition-opacity duration-300">Sales Manage</span>
        </a>

        <!-- Invoice History -->
        <a href="<?= BASE_URL ?>/user/invoice_history.php" 
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all duration-200 <?= $current_page === 'invoice_history.php' ? 'bg-brand-blue text-white shadow-md' : 'text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white' ?>"
           title="Invoice History">
            <ion-icon name="receipt" class="text-2xl flex-shrink-0"></ion-icon>
            <span x-show="sidebarExpanded" class="font-medium whitespace-nowrap transition-opacity duration-300">Invoice History</span>
        </a>

        <!-- Item Management (Admin Only) -->
        <?php if ($_SESSION['role'] === 'admin'): ?>
        <a href="<?= BASE_URL ?>/admin/products.php" 
           class="flex items-center gap-3 px-3 py-3 rounded-xl transition-all duration-200 text-gray-600 dark:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white"
           title="Item Management">
            <ion-icon name="cube" class="text-2xl flex-shrink-0"></ion-icon>
            <span x-show="sidebarExpanded" class="font-medium whitespace-nowrap transition-opacity duration-300">Item Management</span>
        </a>
        <?php endif; ?>
    </nav>
</aside>

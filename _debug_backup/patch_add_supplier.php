<?php
$file = 'admin/product_add.php';
$content = file_get_contents($file);

// 1. Add AJAX Handler
$ajaxHandler = '
// Handle AJAX Add Supplier
if (isset($_POST[\'ajax_add_supplier\'])) {
    header(\'Content-Type: application/json\');
    $name = trim($_POST[\'sup_name\'] ?? \'\');
    $phone = trim($_POST[\'sup_phone\'] ?? \'\');
    $email = trim($_POST[\'sup_email\'] ?? \'\');
    $admin_id = $_SESSION[\'role\'] === \'superadmin\' ? null : $_SESSION[\'user_id\'];
    
    if (!$name) {
        echo json_encode([\'success\' => false, \'error\' => \'Name is required\']);
        exit;
    }
    
    $check = $pdo->prepare("SELECT id FROM Suppliers WHERE name = ? AND (admin_id = ? OR admin_id IS NULL)");
    $check->execute([$name, $admin_id]);
    if ($check->fetch()) {
        echo json_encode([\'success\' => false, \'error\' => \'Supplier already exists\']);
        exit;
    }
    
    $stmt = $pdo->prepare(\'INSERT INTO Suppliers (name, admin_id, phone, email, address, product_types) VALUES (?, ?, ?, ?, ?, ?)\');
    if ($stmt->execute([$name, $admin_id, $phone, $email, \'\', \'\'])) {
        echo json_encode([\'success\' => true, \'id\' => $pdo->lastInsertId(), \'name\' => htmlspecialchars($name)]);
    } else {
        echo json_encode([\'success\' => false, \'error\' => \'Failed to add supplier\']);
    }
    exit;
}
';
if (strpos($content, 'ajax_add_supplier') === false) {
    $content = str_replace("\$error = '';\n\$success = '';\n", "\$error = '';\n\$success = '';\n" . $ajaxHandler, $content);
}

// 2. Modify Supplier Label
$oldLabel = '<label for="supplier_ids"
                        class="block text-sm font-medium text-gray-700 dark:text-gray-300">Suppliers</label>';
$newLabel = '<div class="flex justify-between items-center">
                        <label for="supplier_ids" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Suppliers</label>
                        <button type="button" onclick="document.getElementById(\'addSupplierModal\').classList.remove(\'hidden\'); document.getElementById(\'addSupplierModal\').classList.add(\'flex\');" class="text-xs text-brand-blue hover:underline flex items-center gap-1">
                            <ion-icon name="add-circle-outline"></ion-icon> Add New Supplier
                        </button>
                    </div>';
$content = str_replace($oldLabel, $newLabel, $content);

// 3. Add Modal and JS
$modalHtml = '
<!-- Add Supplier Modal -->
<div id="addSupplierModal" class="fixed inset-0 bg-gray-900 bg-opacity-50 hidden items-center justify-center z-[100]">
    <div class="bg-white dark:bg-gray-800 rounded-xl card-shadow w-full max-w-md transform transition-all">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <ion-icon name="person-add" class="text-brand-blue"></ion-icon> Add Supplier
            </h3>
            <button type="button" onclick="document.getElementById(\'addSupplierModal\').classList.add(\'hidden\'); document.getElementById(\'addSupplierModal\').classList.remove(\'flex\');" class="text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200">
                <ion-icon name="close-outline" class="text-2xl"></ion-icon>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div id="sup_error" class="hidden bg-red-100 text-red-700 p-2 rounded text-sm mb-3"></div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Supplier Name *</label>
                <input type="text" id="sup_name" class="w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Phone (Optional)</label>
                <input type="text" id="sup_phone" class="w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Email (Optional)</label>
                <input type="email" id="sup_email" class="w-full px-3 py-2 border border-black dark:border-gray-600 rounded-md shadow-sm focus:ring-brand-blue bg-gray-50 dark:bg-gray-700 text-gray-900 dark:text-white">
            </div>
            <div class="flex justify-end gap-3 mt-6">
                <button type="button" onclick="document.getElementById(\'addSupplierModal\').classList.add(\'hidden\'); document.getElementById(\'addSupplierModal\').classList.remove(\'flex\');" class="px-4 py-2 bg-gray-200 text-gray-800 dark:bg-gray-700 dark:text-gray-200 rounded-md hover:bg-gray-300 transition">Cancel</button>
                <button type="button" id="btn_save_supplier" class="px-4 py-2 bg-brand-blue text-white rounded-md hover:bg-blue-700 shadow transition flex items-center gap-2">
                    Save Supplier
                </button>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById("btn_save_supplier")?.addEventListener("click", function() {
    const name = document.getElementById("sup_name").value.trim();
    const phone = document.getElementById("sup_phone").value.trim();
    const email = document.getElementById("sup_email").value.trim();
    const errorDiv = document.getElementById("sup_error");
    
    if(!name) {
        errorDiv.textContent = "Supplier Name is required!";
        errorDiv.classList.remove("hidden");
        return;
    }
    
    this.disabled = true;
    this.innerHTML = "Saving...";
    
    const formData = new FormData();
    formData.append("ajax_add_supplier", "1");
    formData.append("sup_name", name);
    formData.append("sup_phone", phone);
    formData.append("sup_email", email);
    
    fetch(window.location.href, {
        method: "POST",
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if(data.success) {
            // Append to checkboxes
            const container = document.querySelector(".max-h-52.overflow-y-auto");
            const newLabel = document.createElement("label");
            newLabel.className = "flex items-center gap-2 rounded px-2 py-2 text-sm text-gray-900 dark:text-gray-100 hover:bg-gray-100 dark:hover:bg-gray-700";
            newLabel.innerHTML = `<input type="checkbox" name="supplier_ids[]" value="${data.id}" checked class="h-4 w-4 rounded border-gray-400 text-brand-blue focus:ring-brand-blue"> <span>${data.name}</span>`;
            
            // Remove "Add suppliers first" msg if present
            const emptyMsg = container.querySelector("span.text-gray-500");
            if (emptyMsg && emptyMsg.innerText.includes("first")) {
                emptyMsg.remove();
            }
            
            container.appendChild(newLabel);
            
            // Reset and close modal
            document.getElementById("sup_name").value = "";
            document.getElementById("sup_phone").value = "";
            document.getElementById("sup_email").value = "";
            errorDiv.classList.add("hidden");
            document.getElementById("addSupplierModal").classList.add("hidden");
            document.getElementById("addSupplierModal").classList.remove("flex");
        } else {
            errorDiv.textContent = data.error || "An error occurred.";
            errorDiv.classList.remove("hidden");
        }
    })
    .catch(err => {
        errorDiv.textContent = "Network error. Try again.";
        errorDiv.classList.remove("hidden");
    })
    .finally(() => {
        this.disabled = false;
        this.innerHTML = "Save Supplier";
    });
});
</script>
';
if (strpos($content, 'addSupplierModal') === false) {
    $content = str_replace("<?php require_once '../includes/footer.php'; ?>", $modalHtml . "\n<?php require_once '../includes/footer.php'; ?>", $content);
}

file_put_contents($file, $content);
echo "Product Add Supplier Modal injected!";
?>

import re

with open('frontend/src/pages/superadmin/ShopEdit.jsx', 'r', encoding='utf-8') as f:
    c = f.read()

c = re.sub(r"shop_contact:\s*''\s*\}\);", "shop_contact: '', module_pos: 1, module_inventory: 1, module_suppliers: 1, module_customers: 1, module_expenses: 1, module_reports: 1, module_staff: 1, module_branches: 1\n  });", c)

c = re.sub(r"shop_contact:\s*res\.data\.shop\.shop_contact \|\| ''", "shop_contact: res.data.shop.shop_contact || '', module_pos: res.data.shop.module_pos ?? 1, module_inventory: res.data.shop.module_inventory ?? 1, module_suppliers: res.data.shop.module_suppliers ?? 1, module_customers: res.data.shop.module_customers ?? 1, module_expenses: res.data.shop.module_expenses ?? 1, module_reports: res.data.shop.module_reports ?? 1, module_staff: res.data.shop.module_staff ?? 1, module_branches: res.data.shop.module_branches ?? 1", c)

with open('frontend/src/pages/superadmin/ShopEdit.jsx', 'w', encoding='utf-8') as f:
    f.write(c)

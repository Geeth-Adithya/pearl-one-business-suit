const fs = require('fs');
let file = fs.readFileSync('api/suppliers.php', 'utf8');

const oldGetBlock = \        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } elseif (\['REQUEST_METHOD'] === 'POST') {\;

const newGetBlock = \        } elseif (\ === 'requests_history') {
            \ = \->prepare("
                SELECT sr.*, s.name as supplier_name, p.name as product_name
                FROM Supply_Requests sr
                JOIN Suppliers s ON sr.supplier_id = s.id
                JOIN Products p ON sr.product_id = p.id
                WHERE sr.admin_id = ?
                ORDER BY sr.created_at DESC
            ");
            \->execute([\]);
            echo json_encode(['success' => true, 'requests' => \->fetchAll()]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
        }
    } elseif (\['REQUEST_METHOD'] === 'POST') {\;

file = file.replace(oldGetBlock, newGetBlock);
fs.writeFileSync('api/suppliers.php', file);
console.log('Updated suppliers.php GET block');

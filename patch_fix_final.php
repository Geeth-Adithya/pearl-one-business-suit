<?php
function fix_add() {
    $file = 'admin/product_add.php';
    $content = file_get_contents($file);

    // Let's restore the video logic and fix the block.
    // The current state is:
    /*
        } else {
            // --- Security Fix: Do not allow unknown files to be uploaded as images ---
            $error = "Invalid image format. Only JPG, PNG, GIF, and WEBP are supported.";
            $img_name = null;
    } elseif (!empty($_FILES['video']['name'])) {
    */
    
    $search = <<<PHP
        } else {
            // --- Security Fix: Do not allow unknown files to be uploaded as images ---
            \$error = "Invalid image format. Only JPG, PNG, GIF, and WEBP are supported.";
            \$img_name = null;
    } elseif (!empty(\$_FILES['video']['name'])) {
PHP;

    $replace = <<<PHP
        } else {
            // --- Security Fix: Do not allow unknown files to be uploaded as images ---
            \$error = "Invalid image format. Only JPG, PNG, GIF, and WEBP are supported.";
            \$img_name = null;
        }
        \$image_url = \$img_name;
    }

    // Prioritize link over upload for video
    if (!empty(\$video_link)) {
        \$video_url = \$video_link;
    } elseif (!empty(\$_FILES['video']['name'])) {
PHP;
    
    $content = str_replace($search, $replace, $content);
    
    // Also let's make sure the if (empty($error)) has its brace if it lost it! Wait, if the POST block was closed early, did we lose anything else?
    // The brace error was because we had an extra `}` early on which closed the POST block. 
    // Now that we fixed the extra `}`, the `}` at the end of the try/catch is now the proper closing brace for the POST block.
    // Let's just fix the `if (empty($error))` formatting.
    $content = str_replace("if (empty(\$error))\n        try {", "if (empty(\$error)) {\n        try {", $content);
    
    // And add a closing brace for `if (empty($error)) {` at the end of catch block!
    $search_catch = <<<PHP
        } catch (\PDOException \$e) {
            \$pdo->rollBack();
            if (\$e->getCode() == 23000) {
                \$error = "Item code already exists.";
            } else {
                \$error = "Error adding product: " . \$e->getMessage();
            }
        }
}
PHP;

    $replace_catch = <<<PHP
        } catch (\PDOException \$e) {
            \$pdo->rollBack();
            if (\$e->getCode() == 23000) {
                \$error = "Item code already exists.";
            } else {
                \$error = "Error adding product: " . \$e->getMessage();
            }
        }
    }
}
PHP;
    $content = str_replace($search_catch, $replace_catch, $content);
    
    file_put_contents($file, $content);
    echo "Fixed product_add.php\n";
}

function fix_edit() {
    $file = 'admin/product_edit.php';
    $content = file_get_contents($file);

    // Apply the GD patch to edit
    $search = '/if \(\$img_info && extension_loaded\(\'gd\'\)\) \{.*?imagedestroy\(\$src\);\s*\} else \{\s*\/\/ --- Security Fix.*?\$uploaded = false;\s*\}/s';

    $replace = <<<PHP
        if (\$img_info) {
            if (extension_loaded('gd')) {
                \$mime = \$img_info['mime'];
                switch (\$mime) {
                    case 'image/jpeg': \$src = imagecreatefromjpeg(\$tmp_path); break;
                    case 'image/png': \$src = imagecreatefrompng(\$tmp_path); break;
                    case 'image/gif': \$src = imagecreatefromgif(\$tmp_path); break;
                    case 'image/webp': \$src = imagecreatefromwebp(\$tmp_path); break;
                    default: \$src = false;
                }
                if (\$src) {
                    \$orig_w = imagesx(\$src); \$orig_h = imagesy(\$src);
                    if (\$orig_w > \$max_width) {
                        \$new_w = \$max_width;
                        \$new_h = intval(\$orig_h * (\$max_width / \$orig_w));
                    } else {
                        \$new_w = \$orig_w; \$new_h = \$orig_h;
                    }
                    \$dst = imagecreatetruecolor(\$new_w, \$new_h);
                    if (\$mime == 'image/png' || \$mime == 'image/webp' || \$mime == 'image/gif') {
                        imagealphablending(\$dst, false);
                        imagesavealpha(\$dst, true);
                        \$transparent = imagecolorallocatealpha(\$dst, 255, 255, 255, 127);
                        imagefilledrectangle(\$dst, 0, 0, \$new_w, \$new_h, \$transparent);
                    }
                    imagecopyresampled(\$dst, \$src, 0, 0, 0, 0, \$new_w, \$new_h, \$orig_w, \$orig_h);
                    if (function_exists('imagewebp')) {
                        imagewebp(\$dst, \$save_path, \$quality);
                    } else {
                        imagejpeg(\$dst, \$save_path, \$quality);
                    }
                    imagedestroy(\$dst);
                    imagedestroy(\$src);
                } else {
                    \$error = "Failed to process image with GD.";
                    \$uploaded = false;
                }
            } else {
                \$allowed_img_extensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                if (in_array(\$original_ext, \$allowed_img_extensions)) {
                    move_uploaded_file(\$tmp_path, \$save_path);
                } else {
                    \$error = "Invalid image format. Only JPG, PNG, GIF, and WEBP are supported.";
                    \$uploaded = false;
                }
            }
        } else {
            // --- Security Fix: Do not allow unknown files to be uploaded as images ---
            \$error = "Invalid image format. Only JPG, PNG, GIF, and WEBP are supported.";
            \$uploaded = false;
        }
PHP;

    $content = preg_replace($search, $replace, $content);
    file_put_contents($file, $content);
    echo "Fixed product_edit.php\n";
}

fix_add();
fix_edit();
?>


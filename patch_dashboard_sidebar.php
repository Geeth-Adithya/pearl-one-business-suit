<?php
$file = 'user/dashboard.php';
$content = file_get_contents($file);

$search = <<<HTML
<!-- Wrapper for full height POS -->
<div class="flex flex-col lg:flex-row h-[calc(100vh-6rem)] gap-6" x-data="posSystem()">
HTML;

$replace = <<<HTML
<!-- Main Layout with Sidebar -->
<div class="flex h-[calc(100vh-4rem)] overflow-hidden">
    <!-- Sidebar -->
    <?php require_once 'includes/sidebar.php'; ?>

    <!-- POS Content Area -->
    <div class="flex-1 overflow-hidden p-4 lg:p-6" x-data="posSystem()">
        <div class="flex flex-col lg:flex-row h-full gap-6">
HTML;

$content = str_replace($search, $replace, $content);

// Now I need to close the two divs at the end of the file.
// The file ends with:
//         }
//     }));
// });
// </script>
// 
// <?php require_once '../includes/footer.php'; ? >

$search_end = <<<HTML
    }));
});
</script>

<?php require_once '../includes/footer.php'; ?>
HTML;

$replace_end = <<<HTML
    }));
});
</script>

    </div> <!-- End POS Content Area -->
</div> <!-- End Main Layout -->

<?php require_once '../includes/footer.php'; ?>
HTML;

$content = str_replace($search_end, $replace_end, $content);

file_put_contents($file, $content);
echo "Patched user/dashboard.php\n";
?>


<?php
$content = file_get_contents('admin/index.php');
$content = str_replace("</div>\n\n<!-- Quick Actions -->", "</div>\n<?php endif; ?>\n\n<!-- Quick Actions -->", $content);
file_put_contents('admin/index.php', $content);
echo "Fixed endif!";

<?php
require_once 'includes/mail_config.php';
if (sendResetCode('geethadithya0304@gmail.com', '123456')) {
    echo "Mail Sent Successfully!";
} else {
    echo "Mail Failed!";
}
?>

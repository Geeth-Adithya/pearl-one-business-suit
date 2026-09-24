<?php
$file = 'admin/index.php';
$content = file_get_contents($file);

if (strpos($content, 'function checkUsername') === false) {
    $js = <<<JS
<script>
let checkTimeout;
function checkUsername(username, feedbackId, inputId) {
    clearTimeout(checkTimeout);
    const feedback = document.getElementById(feedbackId);
    if(username.trim() === '') {
        feedback.innerHTML = '';
        return;
    }
    
    checkTimeout = setTimeout(() => {
        fetch('validate_username.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'username=' + encodeURIComponent(username)
        })
        .then(response => response.json())
        .then(data => {
            if(data.available) {
                feedback.className = 'text-xs mt-1 text-green-600';
                feedback.innerHTML = data.message;
            } else {
                feedback.className = 'text-xs mt-1 text-red-600';
                feedback.innerHTML = data.message + ' Suggestion: <a href="#" class="text-blue-600 underline cursor-pointer" onclick="document.getElementById(\'' + inputId + '\').value=\'' + data.suggestion + '\'; checkUsername(\'' + data.suggestion + '\', \'' + feedbackId + '\', \'' + inputId + '\'); return false;">' + data.suggestion + '</a>';
            }
        });
    }, 500);
}
</script>
JS;

    $content = str_replace('</body>', $js . "\n</body>", $content);
    file_put_contents($file, $content);
    echo "Added JS to index.php\n";
}
?>


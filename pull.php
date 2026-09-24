<?php
$output = shell_exec("git pull origin main 2>&1");
echo "<pre>Result:\n$output</pre>";
?>

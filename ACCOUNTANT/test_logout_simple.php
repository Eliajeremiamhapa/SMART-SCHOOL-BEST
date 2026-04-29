<?php
// test_logout_simple.php
session_start();
session_destroy();
echo "Session destroyed. <a href='login_fixed.php'>Go to login</a>";
?>
<?php
session_start();
session_destroy();
header('Location: ../ACCOUNTANT/login_fixed.php');
exit();
?>
<?php
session_start();
session_destroy();
header("Location: /SISTEM INFORMASI KLINIK/login_page.php");
exit;
?>

<?php
session_start();
session_destroy();
// Ubah arah redirect dari login.php menjadi index.php
header("Location: index.php");
exit;
?>
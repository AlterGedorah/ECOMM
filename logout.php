<?php
session_start();
session_destroy();
header("Location: /ECOMM/index.php");
exit();
?>
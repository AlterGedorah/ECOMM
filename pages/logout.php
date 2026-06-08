<?php
setcookie("user_id", "", time() - 3600, "/");
setcookie("user_name", "", time() - 3600, "/");
setcookie("user_role", "", time() - 3600, "/");
ini_set('session.cookie_lifetime', 0);
session_set_cookie_params(0);
session_start();
session_start();
unset($_SESSION["cart"]);
session_destroy();

header("Location: /Fresh Harvest/index.php");
exit();
?>  
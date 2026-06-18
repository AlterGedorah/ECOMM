<?php
setcookie("user_id", "", time() - 3600, "/");
setcookie("user_name", "", time() - 3600, "/");
setcookie("user_first_name", "", time() - 3600, "/");
setcookie("user_middle_name", "", time() - 3600, "/");
setcookie("user_last_name", "", time() - 3600, "/");
setcookie("user_phone_num", "", time() - 3600, "/");
setcookie("user_role", "", time() - 3600, "/");
setcookie("cookie_consent", "", time() - 3600, "/");
session_start();
$_SESSION = [];
if (ini_get("session.use_cookies")) {
	$params = session_get_cookie_params();
	setcookie(session_name(), '', time() - 42000,
		$params["path"], $params["domain"],
		$params["secure"], $params["httponly"]
	);
}
session_destroy();

header("Location: /Fresh Harvest/index.php");
exit();
?>  
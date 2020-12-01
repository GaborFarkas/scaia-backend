<?php
require_once '../admin/users/init.php';
$db = DB::getInstance();
$settings = $db->query("SELECT * FROM settings")->first();

if (ipCheckBan() || !$user->isLoggedIn()) {
    die();
}
$user->logout();
?>

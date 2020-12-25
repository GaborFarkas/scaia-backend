<?php

require_once '../admin/users/init.php';

if (ipCheckBan() || !$user->isLoggedIn()) {
    die();
}

header('Content-Type: application/json;charset=utf-8');
$response = new stdClass();

if ($user->isBanned() || $user->isPwResetNeeded() || $user->isNotVerified()) {
    $response->error = 'user';
    echo json($response);
    exit();
}

$config = Input::get('config');

if ($config && file_exists('../config/'.$config)) {
    echo file_get_contents('../config/'.$config);
    exit();
} else {
    $response->error = 'config';
    echo json($response);
    exit();
}
?>

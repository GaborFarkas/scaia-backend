<?php

require_once '../admin/users/init.php';

header('Content-Type: application/json;charset=utf-8');
$response = new stdClass();

if (!$user->canServed()) {
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

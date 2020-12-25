<?php

require_once '../admin/users/init.php';

header('Content-Type: application/json;charset=utf-8');

if (!$user->canServed()) {
    http_response_code(401);
    die();
}

$config = Input::get('config');

if ($config && file_exists('../config/'.$config)) {
    echo file_get_contents('../config/'.$config);
    exit();
} else {
    http_response_code(404);
    die();
}
?>

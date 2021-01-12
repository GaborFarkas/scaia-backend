<?php

require_once '../admin/users/init.php';

header('Content-Type: application/json;charset=utf-8');

if (!$user->canServed()) {
    http_response_code(401);
    die();
}
if (!$user->isEligible()) {
    http_response_code(403);
    die();
}

if (Input::get('id')) {
    $id = Input::get('id');

    //TODO: Query the DB for a product matching the ID.
    http_response_code(404);
    die();
} else {
    http_response_code(404);
    die();
}

?>

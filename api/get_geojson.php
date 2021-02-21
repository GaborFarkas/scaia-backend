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
    $db = DB::getInstance();
    $settings = $db->query("SELECT * FROM settings")->first();
    $id = Input::get('id');
    $paths = json_decode(file_get_contents('../config/vector_paths.json'));

    if (file_exists($settings->vector_output.'/'.$paths->$id)) {
        echo file_get_contents($settings->vector_output.'/'.$paths->$id);
    } else {
        http_response_code(404);
        die();
    }
} else {
    echo file_get_contents('../config/basemap.geojson');
}

?>

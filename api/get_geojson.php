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

if (Input::get('lyrid')) {
    $db = DB::getInstance();
    $settings = $db->query("SELECT * FROM settings")->first();
    $lyrId = Input::get('lyrid');
    $paths = json_decode(file_get_contents('../config/map_paths.json'));
    if (isset($paths->$lyrId)) {
        $path = $paths->$lyrId;

        if (strpos($paths->$lyrId, '{timestamp}') !== false) {
            if (Input::get('jobid')) {
                $job = fetchJob(Input::get('jobid'));

                if ($job) {
                    // Do not allow to access other users' results.
                    if (!$user->apiData()->admin && $user->data()->id != $job->user_id) {
                        http_response_code(403);
                        die();
                    }

                    $fileTs = tsToFile($job->timestamp);
                    $path = str_replace('{timestamp}', $fileTs, $path);
                } else {
                    http_response_code(404);
                    die();  
                }
            } else {
                http_response_code(404);
                die();
            }
        }

        if (file_exists(getConfigPath($settings->vector_output, $abs_us_root).'/'.$path)) {
            echo file_get_contents(getConfigPath($settings->vector_output, $abs_us_root).'/'.$path);
        }
    }

    http_response_code(404);
    die();
} else {
    echo file_get_contents('../config/basemap.geojson');
}

?>

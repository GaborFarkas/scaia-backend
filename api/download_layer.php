<?php

require_once '../admin/users/init.php';

if (!$user->canServed()) {
    http_response_code(401);
    die();
}
if (!$user->isEligible()) {
    http_response_code(403);
    die();
}

if (Input::get('id') && Input::get('layer')) {
    $job = fetchJob(Input::get('id'));
    if ($job) {
        // Do not allow to access other users' results.
        if (!$user->apiData()->admin && $user->data()->id != $job->user_id) {
            http_response_code(403);
            die();
        }

        $paths = json_decode(file_get_contents('../config/map_paths.json'));
        $lyrId = Input::get('layer');

        if (isset($paths->$lyrId) && strpos($paths->$lyrId, '{timestamp}') !== false) {
            $db = DB::getInstance();
            $settings = $db->query("SELECT * FROM settings")->first();

            $fileTs = tsToFile($job->timestamp);
            $path = str_replace('{timestamp}', $fileTs, $paths->$lyrId);
            $rasterPath = getConfigPath($settings->raster_output, $abs_us_root).'/'.$path;
            $vectorPath = getConfigPath($settings->vector_output, $abs_us_root).'/'.$path;

            if (file_exists($rasterPath)) {
                // File exists, send it
                download($rasterPath);
            } else if (file_exists($vectorPath)) {
                download($vectorPath);
            }
        }
    }
}

http_response_code(404);
die();

?>

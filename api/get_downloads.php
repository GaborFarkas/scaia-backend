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
    $job = fetchJob(Input::get('id'));
    if ($job) {
        // Do not allow to access other users' results.
        if (!$user->apiData()->admin && $user->data()->id != $job->user_id && $job->public == '0') {
            http_response_code(403);
            die();
        }

        $maps = json_decode(file_get_contents('../config/maps_dynamic.json'));
        $paths = json_decode(file_get_contents('../config/map_paths.json'));
        $prodId = $job->product_id;

        if (isset($maps->$prodId)) {
            $db = DB::getInstance();
            $settings = $db->query("SELECT * FROM settings")->first();

            $map = $maps->$prodId;
            $map->name = $job->name;
            unset($map->mapfile);
            $fileTs = tsToFile($job->timestamp);
            $availability = str_split($job->avail_mask);

            foreach ($map->layers as $key => $layer) {
                $lyrId = $layer->id;
                $layer->available = false;
                $layer->size = 0;

                // If the file can be available, check if it exists.
                if ($availability[$key] == '1' && isset($paths->$lyrId) && strpos($paths->$lyrId, '{timestamp}') !== false) {
                    // Generate file path
                    $path = str_replace('{timestamp}', $fileTs, $paths->$lyrId);
                    $output_folder = $layer->type === 'vector' ? $settings->vector_output : $settings->raster_output;
                    $path = getConfigPath($output_folder, $abs_us_root).'/'.$path;

                    if (file_exists($path)) {
                        // File exists, report size
                        $layer->available = true;
                        $layer->size = filesize($path);
                    }
                }
            }

            echo json($map);
            exit();
        }
    }
}

http_response_code(404);
die();

?>

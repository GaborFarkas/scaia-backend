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
    $job = fetchJob($id);

    // Only handle finished processes with at least partial result.
    if ($job && ($job->status == 'success' || $job->status == 'partial')) {
        $maps = json_decode(file_get_contents('../config/maps_dynamic.json'));
        $prodId = $job->product_id;

        // If we have a template in the config JSON
        if (isset($maps->$prodId)) {
            // Convert timestamp to filename and display formats.
            $displayTs = tsToDisplay($job->timestamp);
            $fileTs = tsToFile($job->timestamp);

            $map = $maps->$prodId;
            if (isset($map->mapfile)) {
                $map->mapfile = str_replace('{timestamp}', $fileTs, $map->mapfile);
            }
            
            $availability = str_split($job->avail_mask);
            foreach ($map->layers as $key => $layer) {
                if ($availability[$key] == '0') {
                    unset($map->layers[$key]);
                }
            }

            // Add timestamps to display names.
            // If we have only a single layer, add it to the layer name, as in this case, the group name won't be processed.
            if (count($map->layers) == 1) {
                $map->layers[0]->name .= ' ('.$displayTs.')';
            } else {
                $map->name .= ' ('.$displayTs.')';
            }

            echo json($map);
            exit();
        } else {
            http_response_code(404);
            die();
        }
    } else {
        http_response_code(404);
        die();
    }
} else {
    http_response_code(404);
    die();
}

?>

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

$response = new stdClass();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['csrf'];
    if (!Token::check($token)) {
        $response->error = 'token';
        echo json($response);
        exit();
    } else {
        // Get the corresponding product node from the config JSON for parameter validation.
        $prodid = Input::get('prodid');
        $config = json_decode(file_get_contents('../config/product.json'));
        $prod = $prodid ? getProductFromGraph($config, $prodid) : null;

        if ($prod) {
            $params = [];

            if ($prod->inputs && count($prod->inputs)) {
                // If the selected product has inputs, validate them as Dates.
                // TODO: Implement other types of inputs, when we will have them.
                $validation = new Validate();
                $validationFields = new stdClass();

                foreach ($prod->inputs as $key => $input) {
                    $paramKey = 'param'.$key;
                    if (Input::get($paramKey)) {
                        $validationFields->$paramKey = [
                            'required' => true,
                            'is_datetime' => 'm/d/Y'
                        ];
                        $params[] = $input->name.': '.Input::get($paramKey);
                    } else {
                        $response->error = 'input';
                        echo json($response);
                        exit();
                    }
                }

                $validation->check($_POST, $validationFields);
                if (!$validation->passed()) {
                    $response->error = 'input';
                    echo json($response);
                    exit();
                }
            }

            // Add initial DB entry
            $name = $prod->name.' ('.date('Y. m. d.').')';
            $fields = [
                'name' => $name,
                'status' => 'running',
                'user_id' => $user->data()->id,
                'product_id' => $prodid,
                'params' => count($params) ? implode('\n', $params) : null
            ];
            $job_id = insertJob($fields);

            // Generate mapfile, if there are raster layers in this product.
            $maps = json_decode(file_get_contents('../config/maps_dynamic.json'));
            $map = $maps->$prodid;
            $template_err = false;

            if ($map) {
                $db = DB::getInstance();
                $settings = $db->query("SELECT * FROM settings")->first();
                $job = fetchJob($job_id);
                $fileTs = tsToFile($job->timestamp);
                $mapfile = $map->mapfile ? getConfigPath($settings->mapfile_prefix, $abs_us_root).'/'.str_replace('{timestamp}', $fileTs, $map->mapfile) : null;
                $paths = json_decode(file_get_contents('../config/map_paths.json'));
                $mapfile_layers_content = '';

                foreach ($map->layers as $id => $layer) {
                    // Only generate template for raster layers.
                    if ($layer->type === 'raster') {
                        $layer_id = $layer->id;
                        if ($paths->$layer_id && file_exists('../map_templates/'.$layer_id)) {
                            // Replace {layername} placeholder to the template file name.
                            $raster_path = getConfigPath($settings->raster_output, $abs_us_root).'/'.str_replace('{timestamp}', $fileTs, $paths->$layer_id);
                            $mapfile_layers_content .= str_replace('{layername}', $raster_path, file_get_contents('../map_templates/'.$layer_id));
                        } else {
                            $template_err = true;
                            break;
                        }
                    }
                }

                // Only generate mapfile, if no errors were encountered and we have raster layers.
                if (!$template_err && $mapfile_layers_content !== '') {
                    $mapfile_content = str_replace('{layers}', $mapfile_layers_content, file_get_contents('../map_templates/mapfile'));

                    // Try to create a new mapfile. If we have an exception due to lack of permissions, fail the process.
                    try {
                        $file = fopen($mapfile, 'w');

                        if ($file) {
                            fwrite($file, $mapfile_content);
                            fclose($file);
                        } else {
                            $template_err = true;
                        }
                    } catch (Exception $e) {
                        $template_err = true;
                    }
                }
            } else {
                $template_err = true;
            }

            if ($template_err) {
                // There was an error during template generation. Fail the job and do not generate a request file.
                $fields = [
                    'status' => 'error',
                    'message' => 'Could not generate the required files for this product.'
                ];
                $db->update('jobs', $job_id, $fields);
            } else {
                // TODO: Generate request to the processing unit.
            }

            exit();
        } else {
            http_response_code(404);
            die();
        }
    }
} else {
    $response->token = Token::generate();
    echo json($response);
    exit();
}

?>

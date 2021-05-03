<?php
ini_set('max_execution_time', 1356);
ini_set('memory_limit','1024M');
require_once '../init.php';
$filename = currentPage();
$db = DB::getInstance();
$ip = ipCheck();
$settings = $db->query("SELECT * FROM settings")->first();
if($settings->cron_ip != ''){
if($ip != $settings->cron_ip && $ip != '127.0.0.1'){
	logger(1,"CronRequest","Cron request DENIED from $ip.");
	die;
	}
}
$errors = $successes = [];

$jobFolder = getConfigPath($settings->job_folder, $abs_us_root);
$jobs = fetchRunningJobs();

foreach ($jobs as $job) {
    $fileTs = tsToFile($job->timestamp);

    if (file_exists($jobFolder.'/'.$fileTs.'.status')) {
        $status = file_get_contents($jobFolder.'/'.$fileTs.'.status');
        $statKey = $status.explode(':')[0];
        $statValue = $status.explode(':')[1];

        // We have an error
        if ($statKey === 'PERMERROR') {
            $fields = [
                'status' => 'error',
                'message' => $statValue
            ];
            $db->update('jobs', $job->id, $fields);
        } else if ($statKey === 'OK') {
            if ($statValue !== $job->message) {
                $fields = [
                    'message' => $statValue
                ];
                $db->update('jobs', $job->id, $fields);
            }
        }
    }
}

$from = Input::get('from');
if($from != NULL && $currentPage == $filename) {
	$query = $db->query("SELECT id,name FROM crons WHERE file = ?",array($filename));
	$results = $query->first();
		$cronfields = array(
		'cron_id' => $results->id,
		'datetime' => date("Y-m-d H:i:s"),
		'user_id' => $user_id);
		$db->insert('crons_logs',$cronfields);
	Redirect::to('/'. $from);
}
?>
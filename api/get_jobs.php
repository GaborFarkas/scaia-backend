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

$response = [];

if (isset($_GET['user'])) {
    // Return list of user jobs
    $jobs = fetchUserJobs($user->data()->id);
} else {
    // Return list of public jobs
    $jobs = fetchPublicJobs();
}

foreach($jobs as $job) {
    $jobObj = new stdClass();
    $jobObj->id = $job->id;
    $jobObj->name = $job->name;
    $jobObj->timestamp = $job->timestamp;
    $jobObj->status = $job->status;
    $jobObj->params = $job->params;
    $jobObj->message = $job->message;
    $jobObj->own = $job->user_id == $user->data()->id;

    $response[] = $jobObj;
}

echo json($response);
exit();

?>

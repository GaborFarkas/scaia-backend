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

if (Input::get('id')) {
    $job = fetchJob(Input::get('id'));

    // If job is not running, treat it as non-existent.
    if ($job && $job->status == 'running') {
        if ($job->user_id == $user->data()->id) {
            $db = DB::getInstance();
            $uname = $user->data()->fname.' '.$user->data()->lname;
            $db->update('jobs', $job->id, ['status' => 'canceled', 'message' => "Job was canceled by $uname."]);
            logger($user->data()->id, 'Jobs', "Canceled job $job->id.");
        } else {
            // Cannot cancel someone else's job.
            http_response_code(403);
            die();
        }

        exit();
    }
}

http_response_code(404);
die();

?>

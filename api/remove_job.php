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

    if ($job) {
        if ($job->user_id == $user->data()->id) {
            $db = DB::getInstance();
            $db->update('jobs', $job->id, ['archived' => 1]);
            logger($user->data()->id, 'Jobs', "Archived job $job->id.");
        } else {
            // Cannot remove someone else's job.
            http_response_code(403);
            die();
        }

        exit();
    }
}

http_response_code(404);
die();

?>

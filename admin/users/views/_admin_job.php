<div class="col-sm-8">
  <div class="page-header float-right">
    <div class="page-title">
      <ol class="breadcrumb text-right">
        <li><a href="<?=$us_url_root; ?>users/admin.php">Dashboard</a></li>
        <li>Manage</li>
        <li><a href="<?=$us_url_root; ?>users/admin.php?view=jobs">Sentinel Processes</a></li>
        <li class="active">Sentinel Process</li>
      </ol>
    </div>
  </div>
</div>
</div>
</header>
<style media="screen">
  form label {font-weight:600}
</style>

<?php
$validation = new Validate();
//PHP Goes Here!
$errors = [];
$successes = [];
$procId = Input::get('id');

//Check if selected user exists
if (!jobExists($procId)) {
    Redirect::to($us_url_root.'users/admin.php?view=jobs&err=That process does not exist.');
    die();
}

$job = fetchJob($procId, true); //Fetch job
$newCancel = false;

//Forms posted
if (!empty($_POST)) {
    $token = $_POST['csrf'];
    if (!Token::check($token)) {
        include $abs_us_root.$us_url_root.'usersc/scripts/token_error.php';
    } else {
        if (!empty($_POST['delete'])) {
            $deletions = $_POST['delete'];
            if ($deletion_count = deleteJobs($deletions)) {
                logger($user->data()->id, 'Sentinel Process Manager', "Deleted process $procId.");
                Redirect::to($us_url_root.'users/admin.php?view=jobs');
            } else {
                $errors[] = lang('SQL_ERROR');
            }
        } else {
            //Cancel job
            $canceled = Input::get('canceled');
            if ($canceled == 1) {
                if ($job->status == 'running') {
                    $uname = $user->data()->fname.' '.$user->data()->lname;
                    $db->update('jobs', $job->id, ['status' => 'canceled', 'message' => "Job was canceled by $uname."]);
                    logger($user->data()->id, 'Jobs', "Canceled job $job->id.");
                    $newCancel = true;
                } else {
                    $errors[] = 'Cannot cancel finished job.';
                }
            }

            //Archive job
            $archived = Input::get('archived');
            if ($archived != $job->archived && ($archived == 1 || $archived == 0)) {
                if ($job->status == 'running' && !$newCancel) {
                    $errors[] = 'Cannot archive running job.';
                } else {
                    $db->update('jobs', $job->id, ['archived' => $archived]);
                    logger($user->data()->id, 'Jobs', "Job $job->id archived status changed.");
                }
            }

            //Publish job
            $public = Input::get('public');
            if ($public != $job->public && ($public == 1 || $public == 0)) {
                if ($job->status !== 'success' && $job->status !== 'partial') {
                    $errors[] = 'Cannot publish unfinished job.';
                } else {
                    $db->update('jobs', $job->id, ['public' => $public]);
                    logger($user->data()->id, 'Jobs', "Job $job->id public status changed.");
                }
            }

            //Update name
            $name = Input::get('name');
            if ($job->name != $name) {
                $fields = ['name' => $name];
                $validation->check($_POST, [
                    'name' => [
                        'display' => 'Name',
                        'required' => true
                    ],
                ]);
                if ($validation->passed()) {
                    $db->update('jobs', $procId, $fields);
                    $successes[] = 'Name Updated';
                    logger($user->data()->id, 'Jobs', "Updated name for $procId from $job->name to $name.");
                }
            }
        }
    }

    if ($errors == [] && Input::get('return') != '') {
        Redirect::to('admin.php?view=jobs&err=Saved');
    } elseif ($errors == []) {
        Redirect::to('admin.php?view=jobs&err=Saved&id='.$procId);
    }
}

?>

<div class="content mt-3">
    <?=resultBlock($errors, $successes); ?>
    <?php if (!$validation->errors() == '') {?><div class="alert alert-danger"><?=display_errors($validation->errors()); ?></div><?php } ?>
    <form class="form" id='job' name='job' action='admin.php?view=job&id=<?=$procId; ?>' method='post'>
        <div class="row">
          <div class="col-8">
            <label>Process ID: </label> <?=$job->id; ?>
          </div>
        </div>
              <div class="form-group" id="name-group">
              <label>Name</label>
              <input type="text" class="form-control" id="name" name="name" value="<?=$job->name; ?>" required autocomplete="off">
              </div>
            <div class="form-group">
                <label>Public</label>
                <select name="public" class="form-control">
                    <option value="1" <?php if ($job->public == 1) {
                        echo "selected='selected'";
                    } else {
                        if ($job->status != 'success' && $job->status != 'partial') {  ?>disabled<?php }
                    } ?>>Yes</option>
                    <option value="0" <?php if ($job->public == 0) {
                        echo "selected='selected'";
                    } else {
                        if ($job->status != 'success' && $job->status != 'partial') {  ?>disabled<?php }
                    } ?>>No</option>
                </select>
            </div>
            <?php if ($job->status != 'running') { ?>
            <div class="form-group">
                <label>Archived</label>
                <select name="archived" class="form-control">
                    <option value="1" <?php if ($job->archived == 1) {
                        echo "selected='selected'";
                    } ?>>Yes</option>
                    <option value="0" <?php if ($job->archived == 0) {
                        echo "selected='selected'";
                    } ?>>No</option>
                </select>
            </div>
            <?php } else { ?>
            <div class="form-group">
                <label>Canceled</label>
                <select name="canceled" class="form-control">
                    <option value="1">Yes</option>
                    <option value="0" selected="selected">No</option>
                </select>
            </div>
            <?php } ?>
            <div class="form-group">
                <label>Delete this Process<a class="nounderline" data-toggle="tooltip" title="Completely delete a process. This cannot be undone."><font color="blue">?</font></a></label>
                <select name='delete[<?php echo "$procId"; ?>]' id='delete[<?php echo "$procId"; ?>]' class="form-control">
                    <option selected='selected' disabled>No</option>
                    <option value="<?=$procId; ?>">Yes - Cannot be undone!</option>
                </select>
            </div>
            <input type="hidden" name="csrf" value="<?=Token::generate(); ?>" />
            <a class='btn btn-warning' href="<?=$us_url_root; ?>users/admin.php?view=jobs">Cancel</a>
            <input class='btn btn-secondary' name="return" type='submit' value='Update & Close' class='submit' />
            <input class='btn btn-primary' type='submit' value='Update' class='submit' />
    </form>
</div>
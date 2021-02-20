<div class="col-sm-8">
  <div class="page-header float-right">
    <div class="page-title">
      <ol class="breadcrumb text-right">
        <li><a href="<?=$us_url_root; ?>users/admin.php">Dashboard</a></li>
        <li>Manage</li>
        <li><a href="<?=$us_url_root; ?>users/admin.php?view=helps">Help Cards</a></li>
        <li class="active">Help Card</li>
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
$helpId = Input::get('id');

//Check if selected user exists
if (!helpCardExists($helpId)) {
    Redirect::to($us_url_root.'users/admin.php?view=helps&err=That help card does not exist.');
    die();
}

$helpcard = fetchHelpCard($helpId); //Fetch help card

//Forms posted
if (!empty($_POST)) {
    $token = $_POST['csrf'];
    if (!Token::check($token)) {
        include $abs_us_root.$us_url_root.'usersc/scripts/token_error.php';
    } else {
        if (!empty($_POST['delete'])) {
            $deletions = $_POST['delete'];
            if ($deletion_count = deleteHelpCards($deletions)) {
                logger($user->data()->id, 'Help card Manager', "Deleted help card $helpId.");
                Redirect::to($us_url_root.'users/admin.php?view=helps');
            } else {
                $errors[] = lang('SQL_ERROR');
            }
        } else {
            //Update category
            $cat = Input::get('category');
            if ($helpcard->category != $cat) {
                $fields = ['category' => $cat];
                $validation->check($_POST, [
                    'category' => [
                        'display' => 'Category',
                        'required' => true
                    ],
                ]);
                if ($validation->passed()) {
                    $db->update('help', $helpId, $fields);
                    $successes[] = 'Category Updated';
                    logger($user->data()->id, 'Help card Manager', "Updated category for $helpId from $helpcard->category to $cat.");
                }
            }

            //Update title
            $title = Input::get('title');
            if ($helpcard->title != $title) {
                $fields = ['title' => $title];
                $validation->check($_POST, [
                    'title' => [
                        'display' => 'Title',
                        'required' => true
                    ],
                ]);
                if ($validation->passed()) {
                    $db->update('help', $helpId, $fields);
                    $successes[] = 'Title Updated';
                    logger($user->data()->id, 'Help card Manager', "Updated title for $helpId.");
                }
            }

            //Update body
            $body = Input::get('body');
            if ($helpcard->body != $body) {
                $fields = ['body' => $body];
                $validation->check($_POST, [
                    'body' => [
                        'display' => 'Text body',
                        'required' => true
                    ],
                ]);
                if ($validation->passed()) {
                    $db->update('help', $helpId, $fields);
                    $successes[] = 'Text body Updated';
                    logger($user->data()->id, 'Help card Manager', "Updated text body for $helpId.");
                }
            }
        }
    }

    if ($errors == [] && Input::get('return') != '') {
        Redirect::to('admin.php?view=helps&err=Saved');
    } elseif ($errors == []) {
        Redirect::to('admin.php?view=help&err=Saved&id='.$helpId);
    }
}

$categories = getHelpCategories();

?>

<div class="content mt-3">
    <?=resultBlock($errors, $successes); ?>
    <?php if (!$validation->errors() == '') {?><div class="alert alert-danger"><?=display_errors($validation->errors()); ?></div><?php } ?>
    <form class="form" id='helpCard' name='helpCard' action='admin.php?view=help&id=<?=$helpId; ?>' method='post'>
        <div class="row">
          <div class="col-8">
            <label>Help card ID: </label> <?=$helpcard->id; ?>
          </div>
        </div>
        <div class="form-group" id="category-group">
              <label>Category</label>
              <select class="form-control" id="category" name="category" required>
                <?php
                foreach ($categories as $key => $cat) { ?>
                    <option value="<?=$key; ?>" <?php if ($helpcard->category == $key) {
              echo 'selected';
          } ?>><?=$cat; ?></option>
                <?php } ?>
              </select>
              </div>
              <div class="form-group" id="title-group">
              <label>Title</label>
              <input type="text" class="form-control" id="title" name="title" value="<?=$helpcard->title; ?>" required autocomplete="off">
              </div>
              <div class="form-group" id="body-group">
              <label>Text body</label>
              <textarea class="form-control" name="body" id="body" rows="10" required autocomplete="off"><?=$helpcard->body; ?></textarea>
              </div>
              <div class="form-group">
                <label>Delete this Help card<a class="nounderline" data-toggle="tooltip" title="Completely delete a help card. This cannot be undone."><font color="blue">?</font></a></label>
                <select name='delete[<?php echo "$helpId"; ?>]' id='delete[<?php echo "$helpId"; ?>]' class="form-control">
                <option selected='selected' disabled>No</option>
                <option value="<?=$helpId; ?>">Yes - Cannot be undone!</option>
                </select>
            </div>
            <input type="hidden" name="csrf" value="<?=Token::generate(); ?>" />
            <a class='btn btn-warning' href="<?=$us_url_root; ?>users/admin.php?view=helps">Cancel</a>
            <input class='btn btn-secondary' name = "return" type='submit' value='Update & Close' class='submit' />
            <input class='btn btn-primary' type='submit' value='Update' class='submit' />
    </form>
</div>
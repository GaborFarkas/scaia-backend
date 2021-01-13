<div class="col-sm-8">
  <div class="page-header float-right">
    <div class="page-title">
      <ol class="breadcrumb text-right">
        <li><a href="<?=$us_url_root; ?>users/admin.php">Dashboard</a></li>
        <li>Manage</li>
        <li class="active">Help cards</li>
      </ol>
    </div>
  </div>
</div>
</div>
</header>

<?php

//PHP Goes Here!
$errors = $successes = [];
$form_valid = true;
$validation = new Validate();
if (!empty($_POST)) {
    includeHook($hooks, 'post');
    //Manually Add User
    if (!empty($_POST['addHelp'])) {
        $cat = Input::get('category');
        $title = Input::get('title');
        $body = Input::get('body');
        $token = $_POST['csrf'];

        if (!Token::check($token)) {
            include $abs_us_root.$us_url_root.'usersc/scripts/token_error.php';
        }

        $form_valid = false; // assume the worst
        $validation->check($_POST, [
          'category' => [
            'display' => 'Category',
            'required' => true
          ],
          'title' => [
            'display' => 'Title',
            'required' => true
          ],
          'body' => [
            'display' => 'Body',
            'required' => true
          ]
        ]);

        if ($validation->passed()) {
            $form_valid = true;
            try {
                // echo "Trying to create user";
                $fields = [
            'category' => $cat,
            'title' => $title,
            'body' => $body
          ];

                $db->insert('help', $fields);
                $theNewId = $db->lastId();

                logger($user->data()->id, 'Help Manager', "Added help card $title.");
                Redirect::to($us_url_root.'users/admin.php?view=helps');
            } catch (Exception $e) {
                die($e->getMessage());
            }
        }
    }
}
  $helpCards = fetchHelpCards();
  $categories = getHelpCategories();

  foreach ($validation->errors() as $error) {
      $errors[] = $error[0];
  }
  ?>

<div class="content mt-3">
  <div class="row">
    <div class="col-12 mb-2">
    <h2>Manage Help cards</h2>
    <?=resultBlock($errors, $successes); ?>
    <?php includeHook($hooks, 'pre'); ?>
    <div class="w-100 text-right">
    <button class="btn btn-outline-dark" data-toggle="modal" data-target="#addhelp"><i class="fa fa-plus"></i> Add Help card</button>
    </div>
    </div>
    <div class="col-12">
    <div class="card">
      <div class="card-body">
      <div class="allutable">
      <table id="paginate" class='table table-hover table-list-search'>
        <thead>
          <tr>
            <th></th><th>Category</th><th>Title</th>
          </tr>
        </thead>
        <tbody>
          <?php
          foreach ($helpCards as $v1) {
              ?>
            <tr>
              <td><a class="nounderline text-dark" href='admin.php?view=help&id=<?=$v1->id; ?>'><?=$v1->id; ?></a></td>
              <td><a class="nounderline text-danger" href='admin.php?view=help&id=<?=$v1->id; ?>'><?php
                $catId = $v1->category;
                echo $categories->$catId;
              ?></a></td>
              <td><a class="nounderline text-dark" href='admin.php?view=help&id=<?=$v1->id; ?>'><?=$v1->title; ?></a></td>
              <?php includeHook($hooks, 'bottom'); ?>
            </tr>
          <?php
          } ?>
        </tbody>
      </table>
    </div>
      </div>
    </div>
    </div>
    </div>

    <div id="addhelp" class="modal fade" role="dialog">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h4 class="modal-title">New Help card</h4>
        <button type="button" class="close float-right" data-dismiss="modal">&times;</button>
      </div>
      <form class="form-signup mb-0" action="admin.php?view=helps" method="POST">
      <div class="modal-body">
              <div class="form-group" id="category-group">
              <label>Category</label>
              <select class="form-control" id="category" name="category" required>
                <?php
                foreach ($categories as $key => $cat) { ?>
                    <option value="<?=$key; ?>" <?php if (!$form_valid && !empty($_POST) && $category == $key) {
              echo 'selected';
          } ?>><?=$cat; ?></option>
                <?php } ?>
              </select>
              </div>
              <div class="form-group" id="title-group">
              <label>Title</label>
              <input type="text" class="form-control" id="title" name="title" value="<?php if (!$form_valid && !empty($_POST)) {
              echo $title;
          } ?>" required autocomplete="off">
              </div>
              <div class="form-group" id="body-group">
              <label>Text body</label>
              <textarea class="form-control" name="body" id="body" rows="10" value="<?php if (!$form_valid && !empty($_POST)) {
              echo $body;
          } ?>" required autocomplete="off"></textarea>
              </div>
            </div>
            <div class="modal-footer">
                <input type="hidden" name="csrf" value="<?=Token::generate(); ?>" />
                <input class='btn btn-primary submit' type='submit' id="addHelp" name="addHelp" value='Add Help card' />
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              </div>
            </form>
          </div>
        </div>
      </div>


    <script type="text/javascript" src="js/pagination/datatables.min.js"></script>
    <script src="js/jwerty.js"></script>

    <script>
    $(document).ready(function() {
      jwerty.key('esc', function(){
        $('.modal').modal('hide');
      });
      $('#paginate').DataTable({"pageLength": 25,"stateSave": true,"aLengthMenu": [[25, 50, 100, -1], [25, 50, 100, "All"]], "aaSorting": []});
    });
    </script>
<div class="col-sm-8">
  <div class="page-header float-right">
    <div class="page-title">
      <ol class="breadcrumb text-right">
        <li><a href="<?=$us_url_root; ?>users/admin.php">Dashboard</a></li>
        <li>Manage</li>
        <li class="active">Sentinel Processes</li>
      </ol>
    </div>
  </div>
</div>
</div>
</header>

<?php

//PHP Goes Here!
  $jobs = fetchAllJobs();
  ?>

<div class="content mt-3">
  <div class="row">
    <div class="col-12 mb-2">
    <h2>Manage Sentinel processes</h2>
    <?=resultBlock($errors, $successes); ?>
    </div>
    <div class="col-12">
    <div class="card">
      <div class="card-body">
      <div class="allutable">
      <table id="paginate" class='table table-hover table-list-search'>
        <thead>
          <tr>
            <th></th><th>User</th><th>Product</th><th>Name</th><th>Created</th><th>State</th><th>Public</th><th>Archived</th>
          </tr>
        </thead>
        <tbody>
          <?php
          foreach ($jobs as $v1) {
              ?>
            <tr>
              <td><a class="nounderline text-dark" href='admin.php?view=job&id=<?=$v1->id; ?>'><?=$v1->id; ?></a></td>
              <td><a class="nounderline text-dark" href='admin.php?view=user&id=<?=$v1->user_id; ?>'><?=$v1->user_id; ?></a></td>
              <td><a class="nounderline text-dark" href='admin.php?view=job&id=<?=$v1->id; ?>'><?=$v1->product_id; ?></a></td>
              <td><a class="nounderline text-dark" href='admin.php?view=job&id=<?=$v1->id; ?>'><?=$v1->name; ?></a></td>
              <td><a class="nounderline text-dark" href='admin.php?view=job&id=<?=$v1->id; ?>'><?=$v1->timestamp; ?></a></td>
              <td><a class="nounderline text-dark" href='admin.php?view=job&id=<?=$v1->id; ?>'><?=$v1->status; ?></a></td>
              <td><a class="nounderline text-dark" href='admin.php?view=job&id=<?=$v1->id; ?>'><?=$v1->public == 0 ? 'No' : 'Yes'; ?></a></td>
              <td><a class="nounderline text-dark" href='admin.php?view=job&id=<?=$v1->id; ?>'><?=$v1->archived == 0 ? 'No' : 'Yes'; ?></a></td>
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
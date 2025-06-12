<!-- Main content -->
<?php include('../includes/student_header.php');?>
<section class='content'>

  <!-- Stats boxes -->
  <div class='row'>
    <div class='col-lg-3 col-6'>
      <div class='small-box bg-info'>
        <div class='inner'>
          <h3><?php echo count( $projects );
?></h3>
          <p>My Projects</p>
        </div>
        <div class='icon'><i class='fas fa-project-diagram'></i></div>
        <a href='project_create.php' class='small-box-footer'>
          New Project <i class='fas fa-plus-circle'></i>
        </a>
      </div>
    </div>
    <div class='col-lg-3 col-6'>
      <div class='small-box bg-warning'>
        <div class='inner'>
          <h3><?php echo $unreadCount;
?></h3>
          <p>Unread Notices</p>
        </div>
        <div class='icon'><i class='far fa-bell'></i></div>
        <a href='notifications.php' class='small-box-footer'>
          View All <i class='fas fa-arrow-circle-right'></i>
        </a>
      </div>
    </div>
  </div>
  <!-- /.row -->

  <div class='row'>
    <!-- Notifications card -->
    <section class='col-lg-4 connectedSortable'>
      <div class='card'>
        <div class='card-header'>
          <h3 class='card-title'><i class='far fa-bell'></i> Recent Notifications</h3>
        </div>
        <div class='card-body p-0'>
          <?php if ( empty( $notifications ) ): ?>
          <p class='p-3'>No new notifications.</p>
          <?php else: ?>
          <ul class='products-list product-list-in-card pl-2 pr-2'>
            <?php foreach ( $notifications as $note ): ?>
            <li class='item'>
              <div class='product-img'><i class='far fa-envelope fa-2x'></i></div>
              <div class='product-info'>
                <a href='#' class='product-title'><?php echo h( $note[ 'title' ] );
?>
                  <span class='badge badge-info float-right'><?php echo time_elapsed_string( $note[ 'created_at' ] );
?></span>
                </a>
                <span class='product-description'><?php echo h( $note[ 'message' ] );
?></span>
              </div>
            </li>
            <?php endforeach;
?>
          </ul>
          <?php endif;
?>
        </div>
        <div class='card-footer text-center'><a href='notifications.php'>See All Notifications</a></div>
      </div>
    </section>

    <!-- Projects table -->
    <section class='col-lg-8 connectedSortable'>
      <div class='card'>
        <div class='card-header border-0'>
          <h3 class='card-title'><i class='fas fa-project-diagram'></i> My Projects</h3>
        </div>
        <div class='card-body table-responsive p-0'>
          <table class='table table-striped table-valign-middle'>
            <thead>
              <tr>
                <th>Title</th>
                <th>Supervisor</th>
                <th>Status</th>
                <th>Progress</th>
                <th>Updated</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ( $projects as $proj ):
$progress = $proj[ 'total_stages' ]
? ( $proj[ 'completed_stages' ]/$proj[ 'total_stages' ] )*100 : 0;
$badge   = match( $proj[ 'status' ] ) {
    'completed'=>'success',
    'in_progress'=>'primary',
    'pending'=>'warning',
    default=>'danger'
}
;
?>
              <tr>
                <td><?php echo h( $proj[ 'title' ] );
?></td>
                <td><?php echo h( $proj[ 'supervisor_name' ] );
?></td>
                <td><span class="badge badge-<?php echo $badge;?>">
                    <?php echo ucfirst( $proj[ 'status' ] );
?></span>
                </td>
                <td style='width:150px'>
                  <div class='progress progress-xs'>
                    <div class="progress-bar bg-<?php echo $badge;?>" role='progressbar'
                      style="width: <?php echo $progress;?>%;"></div>
                  </div>
                  <small><?php echo round( $progress );
?>%</small>
                </td>
                <td><?php echo date( 'Y-m-d H:i', strtotime( $proj[ 'updated_at' ] ) );
?></td>
                <td>
                  <a href="project_view.php?id=<?php echo $proj['project_id'];?>" class='text-primary mr-2'>
                    <i class='fas fa-eye'></i>
                  </a>
                  <a href="project_edit.php?id=<?php echo $proj['project_id'];?>" class='text-warning mr-2'>
                    <i class='fas fa-edit'></i>
                  </a>
                  <a href="stage_upload.php?project_id=<?php echo $proj['project_id'];?>" class='text-success'>
                    <i class='fas fa-plus-circle'></i>
                  </a>
                </td>
              </tr>
              <?php endforeach;
?>
            </tbody>
          </table>
        </div>
      </div>
    </section>
  </div>
  <!-- /.row -->

</section>
<!-- /.content -->
</div>
<!-- /.content-wrapper -->

<!-- Main Footer --><?php include('../includes/student_footer.php');?>

<?php
// Include the database configuration
require_once __DIR__ . '/../config/database.php';

// Ensure the user is logged in
include('../includes/student_header.php');
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$userId = $_SESSION['user_id'];

// Fetch notifications for the logged-in student
$stmt = $pdo->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC');
$stmt->execute([$userId]);
$notifications = $stmt->fetchAll();


?>

<!-- Content Wrapper -->
<div class="content-wrapper p-3">
  <section class="content-header">
    <div class="container-fluid">
      <h1>Notifications</h1>
    </div>
  </section>

  <!-- Main Content -->
  <section class="content">
    <!-- Notifications List -->
    <div class="row">
      <div class="col-12">
        <?php if (empty($notifications)): ?>
        <div class="alert alert-info text-center">No notifications at the moment.</div>
        <?php else: ?>
        <div class="list-group" id="notifications-list">
          <?php foreach ($notifications as $note): ?>
          <div
            class="list-group-item list-group-item-action d-flex justify-content-between align-items-start <?php echo (!$note['is_read']) ? 'list-group-item-warning' : ''; ?>"
            data-id="<?php echo $note['notification_id']; ?>">
            <div class="ms-2 me-auto">
              <div class="fw-bold"><?php echo htmlspecialchars($note['title']); ?></div>
              <?php echo nl2br(htmlspecialchars($note['message'])); ?>
              <div class="text-muted small mt-1"><?php echo $note['created_at']; ?></div>
            </div>

            <!-- Mark as Read/Unread Button -->
            <?php if (!$note['is_read']): ?>
            <button class="badge bg-warning text-dark rounded-pill mark-as-read">
              <i class="fa-solid fa-bell"></i> New
            </button>
            <?php else: ?>
            <span class="badge bg-secondary rounded-pill">
              <i class="fa-solid fa-eye"></i> Read
            </span>
            <?php endif; ?>
          </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </section>
</div>

<?php include('../includes/student_footer.php'); ?>

<!-- Include jQuery (needed for AJAX) -->
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

<script>
// AJAX to mark notification as read
$(document).on('click', '.mark-as-read', function() {
  var notificationId = $(this).closest('.list-group-item').data('id');

  // Send AJAX request to mark as read
  $.ajax({
    url: 'mark_as_read.php', // This file will handle the logic for marking as read
    type: 'POST',
    data: {
      mark_read: notificationId
    },
    success: function(response) {
      // On success, update the UI
      if (response === 'success') {
        // Change the badge to "Read" without reloading the page
        var badge = $(this).closest('.list-group-item').find('.mark-as-read');
        badge.removeClass('bg-warning text-dark').addClass('bg-secondary');
        badge.html('<i class="fa-solid fa-eye"></i> Read');
      }
    }
  });
});
</script>
<?php
require_once 'config/database.php';
require_once 'includes/notifications.php';
session_start();

if (!isset($_SESSION['user']['id'])) {
    header('Location: auth/login.php');
    exit;
}

$user_id = $_SESSION['user']['id'];
$notifications = get_recent_notifications($db, $user_id, 50);

include 'includes/header.php';
?>
<div class="content-wrapper">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1 class="m-0">الإشعارات</h1>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="card">
                <div class="card-body">
                    <?php if (empty($notifications)): ?>
                        <div class="text-center text-muted">لا توجد إشعارات</div>
                    <?php else: ?>
                        <div class="list-group">
                            <?php foreach ($notifications as $n): ?>
                                <div class="list-group-item">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <h6 class="mb-1"><?= h($n['data']) ?></h6>
                                            <small class="text-muted">
                                                <span class="badge badge-<?= get_notification_badge_class($n['type']) ?>">
                                                    <?= ucfirst($n['type']) ?>
                                                </span>
                                                <?= time_elapsed_string($n['created_at']) ?>
                                            </small>
                                        </div>
                                        <?php if (!$n['is_read']): ?>
                                            <span class="badge badge-warning">جديد</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>
</div>
<?php include 'includes/footer.php'; ?> 
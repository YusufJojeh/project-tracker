<?php
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../config/database.php';

$pid = (int)$_GET['project_id'];
// تأكد من ملكية المشروع
$stmt = $pdo->prepare("SELECT * FROM projects WHERE project_id=? AND student_id=?");
$stmt->execute([$pid, $_SESSION['user_id']]);
$pr = $stmt->fetch() ?: die('غير مسموح');

$stages = $pdo->prepare("SELECT * FROM stages WHERE project_id=?");
$stages->execute([$pid]);
?>
<h3>تفاصيل المشروع: <?= htmlspecialchars($pr['title']) ?></h3>
<p><?= nl2br(htmlspecialchars($pr['description'])) ?></p>
<hr>
<div class="d-flex justify-content-between align-items-center mb-2">
  <h5>مراحل المشروع</h5>
  <a href="stage_upload.php?project_id=<?= $pid ?>" class="btn btn-sm btn-success">إضافة مرحلة</a>
</div>
<table class="table table-bordered bg-white">
  <thead>
    <tr>
      <th>المرحلة</th>
      <th>تاريخ الاستحقاق</th>
      <th>الملفات</th>
      <th>تقييم المشرف</th>
    </tr>
  </thead>
  <tbody>
    <?php foreach($stages as $st): ?>
    <tr>
      <td><?= htmlspecialchars($st['name']) ?></td>
      <td><?= $st['due_date'] ?></td>
      <td>
        <?php
          $up = $pdo->prepare("SELECT * FROM uploads WHERE stage_id=?");
          $up->execute([$st['stage_id']]);
          foreach($up as $f){
            echo "<a href='".str_replace('../public','/public',$f['file_path'])."' target='_blank'>📄</a> ";
          }
        ?>
      </td>
      <td>
        <?php
          $rv = $pdo->prepare("SELECT * FROM reviews WHERE stage_id=?");
          $rv->execute([$st['stage_id']]);
          $r = $rv->fetch();
          if ($r) {
            echo "علامة: ".$r['grade']."<br>ملاحظات: ".htmlspecialchars($r['comment']);
          } else {
            echo "<a href=\"/supervisors/review_stage.php?stage_id={$st['stage_id']}\" class=\"btn btn-sm btn-warning\">انتظار التقييم</a>";
          }
        ?>
      </td>
    </tr>
    <?php endforeach; ?>
  </tbody>
</table>
<?php require __DIR__ . '/../includes/footer.php'; ?>
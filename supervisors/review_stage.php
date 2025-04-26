<?php
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../config/database.php';

$stage_id = (int)$_GET['stage_id'];
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $ins = $pdo->prepare("INSERT INTO reviews (stage_id,supervisor_id,comment,grade) VALUES (?,?,?,?)");
  $ins->execute([$stage_id, $_SESSION['user_id'], $_POST['comment'], $_POST['grade']]);
  header('Location: dashboard.php');
  exit;
}
?>
<h3>تقييم المرحلة</h3>
<form method="post" class="bg-white p-4 rounded shadow">
  <div class="mb-3"><label>الملاحظات</label><textarea name="comment" class="form-control" rows="4"></textarea></div>
  <div class="mb-3"><label>العلامة</label><input name="grade" type="number" step="0.1" class="form-control" required>
  </div>
  <button class="btn btn-success">حفظ التقييم</button>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
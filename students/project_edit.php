<?php
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../config/database.php';

$id = (int)$_GET['project_id'];
$stmt = $pdo->prepare("SELECT * FROM projects WHERE project_id=? AND student_id=?");
$stmt->execute([$id, $_SESSION['user_id']]);
$project = $stmt->fetch();
if (!$project) die('غير مسموح');

if ($_SERVER['REQUEST_METHOD']==='POST') {
  $upd = $pdo->prepare("UPDATE projects SET title=?,description=?,tools_used=? WHERE project_id=?");
  $upd->execute([$_POST['title'], $_POST['description'], $_POST['tools_used'], $id]);
  header('Location: dashboard.php');
  exit;
}
?>
<h3>تعديل المشروع</h3>
<form method="post" class="bg-white p-4 rounded shadow">
  <div class="mb-3"><label>العنوان</label><input name="title" class="form-control"
      value="<?= htmlspecialchars($project['title']) ?>" required></div>
  <div class="mb-3"><label>الوصف</label><textarea name="description" class="form-control"
      rows="4"><?= htmlspecialchars($project['description']) ?></textarea></div>
  <div class="mb-3"><label>الأدوات المستخدمة</label><input name="tools_used" class="form-control"
      value="<?= htmlspecialchars($project['tools_used']) ?>"></div>
  <button class="btn btn-primary">حفظ التعديلات</button>
</form>
<?php require __DIR__ . '/../includes/footer.php'; ?>
<?php
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../config/database.php';

$stmt = $pdo->prepare( "
  SELECT s.stage_id, p.title, s.name AS stage_name, s.due_date
  FROM stages s
  JOIN projects p ON p.project_id=s.project_id
  WHERE p.student_id IS NOT NULL
  ORDER BY s.created_at DESC
" );
$stmt->execute();
$stages = $stmt->fetchAll();
?>
<h3>مراحل المشاريع الموكّلة إليك</h3>
<table class = 'table table-bordered bg-white'>
<thead>
<tr>
<th>المشروع</th>
<th>المرحلة</th>
<th>تاريخ الاستحقاق</th>
<th>إجراءات</th>
</tr>
</thead>
<tbody>
<?php foreach ( $stages as $st ): ?>
<tr>
<td>< ?= htmlspecialchars( $st[ 'title' ] ) ?></td>
<td>< ?= htmlspecialchars( $st[ 'stage_name' ] ) ?></td>
<td>< ?= $st[ 'due_date' ] ?></td>
<td>
<a href = "review_stage.php?stage_id=<?= $st['stage_id'] ?>" class = 'btn btn-sm btn-primary'>تقييم</a>
</td>
</tr>
<?php endforeach;
?>
</tbody>
</table>
<?php require __DIR__ . '/../includes/footer.php';
?>
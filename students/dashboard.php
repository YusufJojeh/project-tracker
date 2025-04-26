<?php
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../config/database.php';

// Prepare query to fetch projects for the logged-in student
$stmt = $pdo->prepare( 'SELECT * FROM projects WHERE student_id = ?' );
$stmt->execute( [ $_SESSION[ 'user_id' ] ] );
$projects = $stmt->fetchAll();
?>
<div class = 'd-flex justify-content-between align-items-center mb-3'>
<h3>مشاريعك</h3>
<a href = 'project_create.php' class = 'btn btn-success'>إضافة مشروع جديد</a>
</div>

<table class = 'table table-bordered bg-white'>
<thead>
<tr>
<th>العنوان</th>
<th>الإنشاء</th>
<th>إجراءات</th>
</tr>
</thead>
<tbody>
<?php foreach ( $projects as $p ): ?>
<tr>
<td>
<?php echo $p[ 'title' ];
?>
</td>
<td>
<?php echo date( 'Y-m-d', strtotime( $p[ 'created_at' ] ) );
?>
</td>
<td>
<a href = "project_view.php?project_id=<?php echo $p['project_id']; ?>" class = 'btn btn-primary btn-sm'>عرض</a>
<a href = "project_edit.php?project_id=<?php echo $p['project_id']; ?>" class = 'btn btn-warning btn-sm'>تعديل</a>
</td>
</tr>
<?php endforeach;
?>
</tbody>
</table>

<?php require __DIR__ . '/../includes/footer.php';
?>
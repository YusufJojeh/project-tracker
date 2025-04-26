<?php
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../config/database.php';

// Check if the supervisor is authorized
if ( $_SESSION[ 'role' ] !== 'supervisor' ) {
    header( 'Location: /auth/login.php' );
    exit;
}

$project_id = ( int )$_GET[ 'project_id' ];

// Fetch project details
$stmt = $pdo->prepare( 'SELECT * FROM projects WHERE project_id = ?' );
$stmt->execute( [ $project_id ] );
$project = $stmt->fetch();
if ( !$project ) {
    die( 'مشروع غير موجود.' );
}

// Fetch stages and their reviews
$stages = $pdo->prepare( "
    SELECT s.*, r.grade, r.comment 
    FROM stages s
    LEFT JOIN reviews r ON r.stage_id = s.stage_id
    WHERE s.project_id = ?
" );
$stages->execute( [ $project_id ] );
$project_stages = $stages->fetchAll();

?>
<h3>تقرير مشروع: < ?= htmlspecialchars( $project[ 'title' ] ) ?></h3>
<p><strong>الوصف:</strong> < ?= nl2br( htmlspecialchars( $project[ 'description' ] ) ) ?></p>
<p><strong>الأدوات المستخدمة:</strong> < ?= htmlspecialchars( $project[ 'tools_used' ] ) ?></p>

<h4>مراحل المشروع</h4>
<table class = 'table table-bordered bg-white'>
<thead>
<tr>
<th>المرحلة</th>
<th>تاريخ الاستحقاق</th>
<th>العلامة</th>
<th>الملاحظات</th>
</tr>
</thead>
<tbody>
<?php foreach ( $project_stages as $stage ): ?>
<tr>
<td>< ?= htmlspecialchars( $stage[ 'name' ] ) ?></td>
<td>< ?= $stage[ 'due_date' ] ?></td>
<td>< ?= $stage[ 'grade' ] ? $stage[ 'grade' ] : 'لم يتم التقييم' ?></td>
<td>< ?= htmlspecialchars( $stage[ 'comment' ] ) ?></td>
</tr>
<?php endforeach;
?>
</tbody>
</table>

<a href = "/reports/generate.php?project_id=<?= $project_id ?>" class = 'btn btn-primary'>توليد تقرير PDF</a>

<?php require __DIR__ . '/../includes/footer.php';
?>
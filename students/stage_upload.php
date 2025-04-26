<?php
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../config/database.php';

if ( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' ) {
    $stmt = $pdo->prepare( 'INSERT INTO stages (project_id,name,due_date) VALUES (?,?,?)' );
    $stmt->execute( [ $_POST[ 'project_id' ], $_POST[ 'name' ], $_POST[ 'due_date' ] ] );
    header( 'Location: project_view.php?project_id='.$_POST[ 'project_id' ] );
    exit;
}

$pid = ( int )$_GET[ 'project_id' ];
?>
<h3>إضافة مرحلة</h3>
<form method = 'post' class = 'bg-white p-4 rounded shadow'>
<input type = 'hidden' name = 'project_id' value = "<?= $pid ?>">
<div class = 'mb-3'><label>اسم المرحلة</label><input name = 'name' class = 'form-control' required></div>
<div class = 'mb-3'><label>تاريخ الاستحقاق</label><input name = 'due_date' type = 'date' class = 'form-control'></div>
<button class = 'btn btn-success'>حفظ المرحلة</button>
</form>
<?php require __DIR__ . '/../includes/footer.php';
?>
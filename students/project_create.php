<?php
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../config/database.php';

if ( $_SERVER[ 'REQUEST_METHOD' ] === 'POST' ) {
    $stmt = $pdo->prepare( 'INSERT INTO projects (student_id,title,description,tools_used) VALUES (?,?,?,?)' );
    $stmt->execute( [ $_SESSION[ 'user_id' ], $_POST[ 'title' ], $_POST[ 'description' ], $_POST[ 'tools_used' ] ] );
    header( 'Location: dashboard.php' );
    exit;
}
?>
<h3>إضافة مشروع جديد</h3>
<form method = 'post' class = 'bg-white p-4 rounded shadow'>
<div class = 'mb-3'><label>العنوان</label><input name = 'title' class = 'form-control' required></div>
<div class = 'mb-3'><label>الوصف</label><textarea name = 'description' class = 'form-control' rows = '4'></textarea></div>
<div class = 'mb-3'><label>الأدوات المستخدمة</label><input name = 'tools_used' class = 'form-control'></div>
<button class = 'btn btn-success'>حفظ المشروع</button>
</form>
<?php require __DIR__ . '/../includes/footer.php';
?>
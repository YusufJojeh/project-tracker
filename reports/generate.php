<?php
require '../config/database.php';
require '../vendor/autoload.php';
// TCPDF autoload

use TCPDF;

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

// Create PDF instance
$pdf = new TCPDF( 'P', 'mm', 'A4', true, 'UTF-8', false );
$pdf->SetTitle( 'تقرير مشروع' );
$pdf->SetAutoPageBreak( TRUE, 15 );
$pdf->AddPage();

// Set PDF Title
$pdf->SetFont( 'dejavusans', 'B', 14 );
$pdf->Cell( 0, 10, 'تقرير مشروع: ' . htmlspecialchars( $project[ 'title' ] ), 0, 1, 'C' );

// Project Description
$pdf->SetFont( 'dejavusans', '', 12 );
$pdf->MultiCell( 0, 10, 'الوصف: ' . nl2br( htmlspecialchars( $project[ 'description' ] ) ) );
$pdf->Ln( 5 );
$pdf->MultiCell( 0, 10, 'الأدوات المستخدمة: ' . htmlspecialchars( $project[ 'tools_used' ] ) );

// Project Stages Table Header
$pdf->Ln( 5 );
$pdf->SetFont( 'dejavusans', 'B', 12 );
$pdf->Cell( 50, 10, 'المرحلة', 1, 0, 'C' );
$pdf->Cell( 40, 10, 'تاريخ الاستحقاق', 1, 0, 'C' );
$pdf->Cell( 40, 10, 'العلامة', 1, 0, 'C' );
$pdf->Cell( 60, 10, 'الملاحظات', 1, 1, 'C' );

// Project Stages Data
$pdf->SetFont( 'dejavusans', '', 12 );
foreach ( $project_stages as $stage ) {
    $pdf->Cell( 50, 10, htmlspecialchars( $stage[ 'name' ] ), 1, 0, 'C' );
    $pdf->Cell( 40, 10, $stage[ 'due_date' ], 1, 0, 'C' );
    $pdf->Cell( 40, 10, $stage[ 'grade' ] ? $stage[ 'grade' ] : 'لم يتم التقييم', 1, 0, 'C' );
    $pdf->Cell( 60, 10, htmlspecialchars( $stage[ 'comment' ] ), 1, 1, 'C' );
}

// Output the PDF
$pdf->Output( 'تقرير_المشروع_' . $project_id . '.pdf', 'I' );
exit;
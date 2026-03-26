<?php
require_once 'config/db.php';
require_once 'auth.php';
requireStaff();

// Only allow admin users to access this page
if (($_SESSION['user_role'] ?? '') !== 'admin') {
    header('HTTP/1.1 403 Forbidden');
    echo 'Admin access required.';
    exit;
}

// NOTE: Ensure you have installed the required libraries via Composer
// composer require phpoffice/phpspreadsheet
// composer require dompdf/dompdf
if (file_exists('vendor/autoload.php')) {
    require_once 'vendor/autoload.php';
} else {
    die("Error: Composer dependencies not found. Please run 'composer install'.");
}

$format = $_GET['format'] ?? 'pdf';

$pdo = getConnection();

$payments = [];
try {
    // This query is the same as in payments.php to ensure data consistency.
    $stmt = $pdo->prepare("
        SELECT
            s.name as student_name,
            s.student_id as admission_no,
            r.room_number,
            s.is_active,
            SUM(CASE 
                WHEN mb.status = 'pending' AND mb.due_date IS NOT NULL AND CURDATE() > mb.due_date AND mb.fine = 0 
                THEN mb.total_amount + 10 
                WHEN mb.status IN ('pending', 'submitted') THEN mb.total_amount 
                ELSE 0 
            END) as total_due,
            SUM(CASE WHEN mb.status = 'submitted' THEN 1 ELSE 0 END) as submitted_count,
            s.id as student_db_id
        FROM
            students s
        JOIN
            monthly_bills mb ON s.id = mb.student_id
        LEFT JOIN
            rooms r ON s.room_id = r.id
        GROUP BY
            s.id, s.name, s.student_id, r.room_number, s.is_active
        HAVING total_due > 0 OR submitted_count > 0
        ORDER BY
            submitted_count DESC, s.is_active DESC, total_due DESC, s.name ASC
    ");
    $stmt->execute();
    $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    if ($e->getCode() !== '42S02') throw $e;
    die("Error: Monthly bills table not found or query failed. " . $e->getMessage());
}

function getStatusText($payment) {
    if ($payment['submitted_count'] > 0) {
        return "Request Submitted";
    } elseif ($payment['total_due'] > 0) {
        return "Pending";
    } else {
        return "Paid";
    }
}

if ($format === 'excel') {
    $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    $sheet = $spreadsheet->getActiveSheet();
    
    $sheet->setTitle('Pending Payments');
    
    // Headers
    $sheet->setCellValue('A1', 'Student Name');
    $sheet->setCellValue('B1', 'Admission No');
    $sheet->setCellValue('C1', 'Room No');
    $sheet->setCellValue('D1', 'Student Status');
    $sheet->setCellValue('E1', 'Payment Status');
    $sheet->setCellValue('F1', 'Total Due');

    // Data
    $row = 2;
    foreach ($payments as $p) {
        $sheet->setCellValue('A' . $row, $p['student_name']);
        $sheet->setCellValue('B' . $row, $p['admission_no']);
        $sheet->setCellValue('C' . $row, $p['room_number'] ?? 'N/A');
        $sheet->setCellValue('D' . $row, $p['is_active'] ? 'Active' : 'Vacated');
        $sheet->setCellValue('E' . $row, getStatusText($p));
        $sheet->setCellValue('F' . $row, $p['total_due']);
        $row++;
    }

    // Formatting
    $sheet->getStyle('A1:F1')->getFont()->setBold(true);
    foreach (range('A', 'F') as $col) {
        $sheet->getColumnDimension($col)->setAutoSize(true);
    }
    $sheet->getStyle('F2:F' . ($row - 1))->getNumberFormat()->setFormatCode('"₹"#,##0.00');

    header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    header('Content-Disposition: attachment;filename="pending_payments_report.xlsx"');
    header('Cache-Control: max-age=0');

    $writer = \PhpOffice\PhpSpreadsheet\IOFactory::createWriter($spreadsheet, 'Xlsx');
    $writer->save('php://output');
    exit;

} elseif ($format === 'pdf') {
    $dompdf = new \Dompdf\Dompdf();
    $dompdf->set_option('isHtml5ParserEnabled', true);

    $html = '
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: sans-serif; font-size: 12px; }
            h1 { text-align: center; margin-bottom: 0; }
            p { text-align: center; color: #666; margin-top: 5px; }
            table { width: 100%; border-collapse: collapse; margin-top: 20px; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            .total { text-align: right; }
        </style>
    </head>
    <body>
        <h1>Pending Payments Report</h1>
        <p>Generated on: ' . date('d M, Y H:i:s') . '</p>
        <table>
            <thead>
                <tr><th>Student Name</th><th>Admission No</th><th>Room No</th><th>Student Status</th><th>Payment Status</th><th class="total">Total Due</th></tr>
            </thead>
            <tbody>';
    
    foreach ($payments as $p) {
        $html .= '<tr>
                    <td>' . htmlspecialchars($p['student_name']) . '</td>
                    <td>' . htmlspecialchars($p['admission_no']) . '</td>
                    <td>' . htmlspecialchars($p['room_number'] ?? 'N/A') . '</td>
                    <td>' . ($p['is_active'] ? 'Active' : 'Vacated') . '</td>
                    <td>' . getStatusText($p) . '</td>
                    <td class="total">₹' . number_format($p['total_due'], 2) . '</td>
                  </tr>';
    }

    $html .= '</tbody></table></body></html>';

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $dompdf->stream('pending_payments_report.pdf', ['Attachment' => 0]); // Use 1 to force download
    exit;
}

header('HTTP/1.1 400 Bad Request');
echo 'Invalid format specified.';
exit;

?>
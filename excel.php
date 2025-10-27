<?php
require 'vendor/autoload.php'; // only this, remove other includes
// require_once __DIR__ . '/PhpSpreadsheet/src/Bootstrap.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$host = "localhost";
$user = "root";
$pass = "";
$db = "studentmsdb";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$fee_default = 60; //If DB does not include class fee.
$student_ids = $_POST['student_ids'];
$class_ids = $_POST['class_ids'] ?? [];
// print_r($student_ids); print_r($class_ids);
$start_year = $_POST['year'] ?: 2025;
$start_month = $_POST['start_month'] ?: 1;
$end_month = $_POST['end_month'] ?: 12;
$title = $_POST['title'] ?: "Student Payment Report ({$start_year})";
if ($start_month > $end_month) {
    die("Invalid month range selected.");
}

// Start date (always day 01)
// $start_date = sprintf("%04d-%02d-01", $start_year, $start_month);
// $end_day = cal_days_in_month(CAL_GREGORIAN, $end_month, $start_year);
// $end_date = sprintf("%04d-%02d-%02d", $start_year, $end_month, $end_day);

// // ReceiptDate is stored as "YYYY-MM"
// $start_date = sprintf("%04d-%02d", $start_year, $start_month);
// $end_date   = sprintf("%04d-%02d", $start_year, $end_month);

$all_month = ["JAN", "FEB", "MAR", "APR", "MAY", "JUN", "JUL", "AUG", "SEP", "OCT", "NOV", "DEC"];
$months = range($start_month, $end_month);

$placeholders = implode(',', array_fill(0, count($student_ids), '?'));
$class_placeholders = implode(',', array_fill(0, count($class_ids), '?'));
$sum_parts = [];
foreach ($months as $m) {
    $alias = $all_month[$m - 1]; // convert to JAN, FEB, etc.
    $sum_parts[] =
        "SUM(CASE WHEN FIELD(p.PaymentMonth, 'JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC') = $m AND YEAR(p.ReceiptDate) = $start_year
             THEN p.PaymentAmount ELSE 0 END) AS `$alias`";
}

$sum_sql = implode(",\n    ", $sum_parts);

$sql = "
SELECT 
    p.ID AS PaymentID,
    p.StudentName1 AS StudentName,
    p.StudentID1 AS StudentID,
    p.ClassName1 AS StudentClass,
    $sum_sql,
    IFNULL(c.ClassFees, ?) AS ClassFees
FROM tblpayment p
LEFT JOIN tblclass c 
       ON p.ClassName1 = c.ClassName
WHERE FIELD(p.PaymentMonth, 'JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC')
      BETWEEN ? AND ?

  AND p.StudentID1 IN ($placeholders)";

// Add class filter only if not empty
if (!empty($class_placeholders)) {
    $sql .= " AND c.ID IN ($class_placeholders) ";
}

$sql .= "
GROUP BY p.StudentId1, p.StudentName1, p.ClassName1
ORDER BY p.ClassName1, p.StudentID1";

$stmt = $conn->prepare($sql);

// --- Bind Parameters ---
$params = array_merge([$fee_default, $start_month, $end_month], $student_ids, $class_ids);
// $types = "iss" . str_repeat("s", count($student_ids)) . str_repeat("s", count($class_ids));
$types = "iii" . str_repeat("s", count($student_ids)) . str_repeat("s", count($class_ids));
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// --- Create Spreadsheet ---
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle("Payment Report");

// Header row
$headers = ['BIL', 'Student ID', 'Student Name', 'Class Name'];

foreach ($months as $m) {
    $headers[] = ucfirst(strtolower($all_month[$m - 1]));
}
$headers[] = 'Total';
$headers[] = 'Outstanding Balance';
// print_r($headers);

$colIndex = 1;
foreach ($headers as $header) {
    $sheet->setCellValue([$colIndex++, 1], $header);
}
$sheet->getStyle('A1:' . $sheet->getHighestColumn() . '1')->getFont()->setBold(true); 

// Data rows
$rowNum = 2;
$bil = 1;
while ($row = $result->fetch_assoc()) {
    $col = 1;
    $sheet->setCellValue([$col++, $rowNum], $bil++);
    $sheet->setCellValue([$col++, $rowNum], $row['StudentID']);
    $sheet->setCellValue([$col++, $rowNum], $row['StudentName']);
    $sheet->setCellValue([$col++, $rowNum], $row['StudentClass']);

    $total = 0;
    foreach ($months as $m) {
        $monthKey = $all_month[$m - 1]; // e.g. JAN
        $value = (float)($row[$monthKey] ?? 0);
        $sheet->setCellValue([$col++, $rowNum], $value);
        $total += $value;
    }

    $sheet->setCellValue([$col++, $rowNum], $total);
    $outstanding = $row['ClassFees'] * count($months) - $total;
    $sheet->setCellValue([$col++, $rowNum], $outstanding);
    $rowNum++;
}

// Auto-size columns
foreach (range('A', $sheet->getHighestColumn()) as $col) {
    $sheet->getColumnDimension($col)->setAutoSize(true);
}

// --- Output Excel file ---
ob_clean();
$filename = "Payment_Report_" . date('Ymd_His') . ".xlsx";
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header("Content-Disposition: attachment; filename=\"$filename\"");
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');

exit;
?>

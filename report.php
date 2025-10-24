<?php
require('tcpdf/tcpdf.php');  // Use TCPDF

// --- Database connection ---
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
        "SUM(CASE WHEN FIELD(p.PaymentMonth, 'JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC') = $m
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

// --- Create PDF ---
$pageFormat = array(281.94, 215.9); // 11.1 × 8.5 in landscape
$pdf = new TCPDF('L', 'mm', $pageFormat, true, 'UTF-8', false);
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);

$pdf->SetCreator(PDF_CREATOR);
$pdf->SetFont('helvetica', '', 10);
$pdf->SetAuthor('SchoolSystem');
$pdf->AddPage();
$pdf->SetTitle('Student Payment Report');

// --- Table Header ---
$html = '<h3 style="text-align:center; margin:0;padding:0">' . htmlspecialchars($title) . '</h3>
<table 
    border="1" 
    cellpadding="4" 
    style="font-size:8px; 
        text-align: center;
        display: flex;
        word-wrap:break-word; 
        white-space:normal;
        table-layout:auto;
">
<thead>
<tr style="background-color:#f2f2f2;">
    <th>BIL</th>
    <th>Student Name</th>
    <th>Student ID</th>
    <th>ClassFees</th>
    <th>Student Class</th>';

foreach ($months as $m) {
    $alias = $all_month[$m - 1];
    $html .= '<th>' . $alias . '</th>';
}

$html .= '<th>Total</th>
          <th>Outstanding<br>Balance</th>
</tr>
</thead>
<tbody>';

// --- Table Rows ---
$bil = 1;
while ($row = $result->fetch_assoc()) {
    $total = 0;
    $html .= "<tr>
                <td>{$bil}</td>
                <td>{$row['StudentName']}</td>
                <td>{$row['StudentID']}</td>
                <td>RM{$row['ClassFees']}</td>
                <td>{$row['StudentClass']}</td>";

    foreach ($months as $m) {
        $alias = $all_month[$m - 1];
        $val = (float) $row[$alias];
        if ($val == 0) {
            $html .= '<td></td>';   // leave blank if zero
        } else {
            $html .= '<td>RM' . $val . '</td>';
        }
        $total += $val;
    }

    $outstanding = $row['ClassFees'] * count($months) - $total;

    $html .= "<td>RM{$total}</td>
              <td>RM{$outstanding}</td>
              </tr>";
    $bil++;
}

$html .= '</tbody></table>';

// --- Logo Upload and Insert into PDF ---
if (!empty($_FILES['logo']['tmp_name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = __DIR__ . '/image/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    // Secure filename (avoid path injection)
    $fileName = basename($_FILES['logo']['name']);
    $filePath = $uploadDir . $fileName;

    // Move uploaded file
    if (move_uploaded_file($_FILES['logo']['tmp_name'], $filePath)) {
        // Optional: limit to PNG/JPG for safety
        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        if (in_array($ext, ['png', 'jpg', 'jpeg'])) {
            // --- Display original logo in PDF ---
            $x = 10;
            $y = 6;
            $logoWidth = 15;  // mm
            $logoHeight = 0;  // auto-scale
            $pdf->Image($filePath, $x, $y, $logoWidth, $logoHeight, strtoupper($ext));
        } else {
            // Fallback if file type unsupported
            $pdf->Image('logo.png', 3, 3, 20);
        }
    } else {
        // Move failed
        $pdf->Image('logo.png', 3, 3, 20);
    }
} else {
    // No upload, use default logo
    $pdf->Image('image/logo.png', 3, 3, 20);
}

// --- Output ---
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output('students.pdf', 'I'); // I = inline view in browser

$conn->close();
?>
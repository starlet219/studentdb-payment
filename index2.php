<?php
$conn = new mysqli("localhost", "root", "", "studentmsdb");
if ($conn->connect_error)
    die("DB connection failed");

// Fetch classes
$classes = $conn->query("SELECT ID, ClassName FROM tblclass ORDER BY ID ASC");

// Fetch students grouped by class
$students = $conn->query("
    SELECT s.StuID, s.StudentName, s.StudentClass, c.ClassName
    FROM tblstudent s
    JOIN tblclass c ON s.StudentClass = c.ID
    ORDER BY  c.ID ASC, s.StudentName
");

$studentsByClass = [];
while ($s = $students->fetch_assoc()) {
    $studentsByClass[$s['StudentClass']][] = [
        "ID" => $s['StuID'],
        "StudentName" => $s['StudentName'],
        "ClassName" => $s['StudentClass'],
    ];
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>Student Report Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <div class="container mt-5 p-4 bg-white shadow rounded">
        <h3 class="mb-4 text-center">Generate Payment Report</h3>
        <!-- <form action="report.php" method="POST" enctype="multipart/form-data"  onsubmit="alert('Your Excel file is being generated!');"> -->
        <form method="POST" enctype="multipart/form-data">
            <!-- <form action="report.php" method="POST" enctype="multipart/form-data"> -->

            <div class="form-section" style="margin: 0;">Select Class and student</div>


            <div class="form-setion class-student" id="student-box">
                <!-- Class Selection -->
                <div class="form-section">
                    <label class="form-label">Class</label>
                    <input type="text" id="classSearch" class="form-control mb-2"
                        placeholder="Search class by name or ID...">
                    <select id="classSelect" name="class_ids[]" class="form-select" multiple size="3">
                        <?php while ($c = $classes->fetch_assoc()): ?>
                            <option value="<?= $c['ID'] ?>">
                                <?= htmlspecialchars($c['ID']) . '-' . htmlspecialchars($c['ClassName']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                    <small class="text-muted">Hold Ctrl (Windows) or Cmd (Mac) to select multiple</small>
                </div>

                <!-- Student Selection -->
                <div class="form-section">
                    <label class="form-label">Students</label>

                    <div class="student-box">
                        <?php foreach ($studentsByClass as $classId => $group): ?>
                            <div class="class-group" data-class="<?= $classId ?>">
                                <div class="class-header">
                                    <?= htmlspecialchars($group[0]['ClassName']) ?>
                                    <span class="select-all-class" data-class="<?= $classId ?>">[✔ Select All]</span>
                                </div>
                                <?php foreach ($group as $s): ?>
                                    <div class="form-check">
                                        <input class="form-check-input student-checkbox" type="checkbox" name="student_ids[]"
                                            value="<?= $s['ID'] ?>" data-class="<?= $classId ?>" multiple size="4">
                                        <label class="form-check-label">
                                            <?= htmlspecialchars($s['StudentName']) ?> (ID: <?= $s['ID'] ?>)
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div id="student-box-error" style="display: none; color: red; margin-bottom: 10px;">
                No student is selected!
            </div>
            <!-- Year & Month Range -->
            <div class="form-section">
                <label class="form-label">Select Year & Month Range</label>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="yearSelect" class="form-label">Year</label>
                        <select id="yearSelect" name="year" class="form-select">
                            <option value="2024">2024</option>
                            <option value="2025" selected>2025</option>
                            <option value="2026">2026</option>
                            <option value="2027">2027</option>
                            <option value="2028">2028</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <!-- <input type="date" id="month-year" name="start_date" required> -->
                        <label class="form-label">Start Month</label>
                        <select name="start_month" class="form-select" required>
                            <option value="1" selected>January</option>
                            <option value="2">February</option>
                            <option value="3">March</option>
                            <option value="4">April</option>
                            <option value="5">May</option>
                            <option value="6">June</option>
                            <option value="7">July</option>
                            <option value="8">August</option>
                            <option value="9">September</option>
                            <option value="10">October</option>
                            <option value="11">November</option>
                            <option value="12">December</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <!-- <input type="date" id="month-year" name="end_date" required> -->
                        <label class="form-label">End Month</label>
                        <select name="end_month" class="form-select" required>
                            <option value="1">January</option>
                            <option value="2">February</option>
                            <option value="3">March</option>
                            <option value="4">April</option>
                            <option value="5">May</option>
                            <option value="6">June</option>
                            <option value="7">July</option>
                            <option value="8">August</option>
                            <option value="9">September</option>
                            <option value="10">October</option>
                            <option value="11">November</option>
                            <option value="12" selected>December</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- PDF Logo + Title -->
            <div class="form-section">
                <label class="form-label">PDF Logo</label>
                <input type="file" id="logo" name="logo" class="form-control">
                <small class="text-muted">Upload school/company logo (optional)</small>
            </div>

            <div class="form-section">
                <label class="form-label">Report Title</label>
                <input type="text" name="title" class="form-control" placeholder="Student Payment Report">
            </div>

            <div class="d-grid gap-3 d-md-grid">
                <button type="submit" formaction="report.php" formtarget="_blank" class="btn btn-primary btn-lg">
                    Generate PDF
                </button>
                <button type="submit" class="btn btn-success btn-lg" formtarget="_blank" formaction="excel.php"
                    id="excelBtn">
                    Export to Excel
                </button>
            </div>
        </form>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        document.getElementById('logo').addEventListener('change', function () {
            let file = this.files[0];
            let formData = new FormData();
            formData.append('logo', file);

            fetch('report.php', {
                method: 'POST',
                body: formData
            })
                .then(res => res.text())
                .then(data => console.log(data));
        });

        $(document).ready(function () {
            // Per-class select all toggle
            $(document).on('click', '.select-all-class', function () {
                const classId = $(this).data('class');
                const checkboxes = $('input.student-checkbox[data-class="' + classId + '"]');
                const allChecked = checkboxes.length === checkboxes.filter(':checked').length;

                if (allChecked) {
                    checkboxes.prop('checked', false);
                    $(this).text('[✔ Select All]');
                } else {
                    checkboxes.prop('checked', true);
                    $(this).text('[✖ Deselect All]');
                }
            });

            $(document).ready(function () {
                const allOptions = $('#classSelect option').map(function () {
                    return {
                        value: $(this).val(),
                        text: $(this).text()
                    };
                }).get();

                // Filter class list by search text
                $('#classSearch').on('input', function () {
                    const query = $(this).val().toLowerCase();
                    const select = $('#classSelect');
                    select.empty(); // remove all options

                    const filtered = allOptions.filter(opt => opt.text.toLowerCase().includes(query));

                    if (filtered.length > 0) {
                        filtered.forEach(opt => {
                            select.append(`<option value="${opt.value}">${opt.text}</option>`);
                        });
                    } else {
                        select.append('<option disabled>No matching classes</option>');
                    }
                });
            });

        });


        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('form');
            const studentBox = document.getElementById('student-box');       // main container
            const alertDiv = document.getElementById('student-box-error');  // alert message

            form.addEventListener('submit', function (event) {
                const studentCheckboxes = document.querySelectorAll('.student-checkbox:checked');

                if (studentCheckboxes.length === 0) {
                    event.preventDefault(); // stop submission

                    // Show red border & shadow
                    studentBox.style.border = '3px solid indianred';
                    studentBox.style.boxShadow = '0px 0px 4px 1px indianred';

                    // Show alert
                    alertDiv.style.display = 'block';
                    return;
                } else {
                    // Remove border & hide alert
                    studentBox.style.border = '';
                    studentBox.style.boxShadow = '';
                    alertDiv.style.display = 'none';
                }
            });
        });
        
        // --- Class search filter ---
        $('#classSearch').on('keyup', function () {
            const searchText = $(this).val().toLowerCase().trim();

            $('#classSelect option').each(function () {
                const text = $(this).text().toLowerCase();
                if (text.includes(searchText)) {
                    $(this).show();
                } else {
                    $(this).hide();
                }
            });
        });

    </script>
</body>

</html>
# PHP Payment Report Module


This module/script generates **monthly student payment reports** in **PDF (TCPDF)** and **Excel (PhpSpreadsheet)** formats.
It features a smart filter UI for **Classes**, **Students**, and **Date ranges**.

------------------------------------------------------------------------

## 🚀 Features

* Generate professional **PDF** and **Excel (XLSX)** reports.
* Filter data by:
  * 📅 **Date range** (year, start and end months)
  * 🎓 **Class(es)**
  * 👨‍🎓 **Student(s)**
* Automatically calculates **outstanding balance** per student.
* Upload and embed your **school logo** in reports.
* Intuitive **Bootstrap multiselect UI** with “Select All” options.

------------------------------------------------------------------------

## 📂 Files Included

| File            | Description                                 |
| --------------- | ------------------------------------------- |
| `index.php`     | Main filter UI for generating reports       |
| `report.php`    | PDF report generation (TCPDF)               |
| `excel.php`     | Excel report generation (PhpSpreadsheet)    |
| `tcpdf/`        | TCPDF library folder                        |
| `vendor/`       | Composer dependencies                       |
|`PhpSpreadsheet/`| Library for  manipulating spreadsheet files |
| `composer.json` | Composer config file for required libraries |

------------------------------------------------------------------------

## ⚙️ Requirements

* PHP **7.4+**
* MySQL
* Composer (for dependency management)

Enable in `php.ini`:

```ini
extension=mysqli
extension=gd
extension=zip
extension=xml
```

------------------------------------------------------------------------

## 🗄 Database Schema

Your MySQL database must contain at least the following tables/fields:

### `tblstudent`
| Field         | Type     | Description          |
| ------------- | -------- | -------------------- |
| `ID`          | INT (PK) | Unique student ID    |
| `StudentName` | VARCHAR  | Student full name    |
| `ClassID`     | INT (FK) | Linked to `tblclass` |


### `tblpayment`

 | Field         | Type          | Description               |
| ------------- | ------------- | ------------------------- |
| `PaymentID`   | INT (PK)      | Unique payment entry      |
| `StudentID`   | INT (FK)      | Linked to `tblstudent`    |
| `ReceiptDate` | DATE          | Payment date (yyyy-mm-dd) |
| `Amount`      | DECIMAL(10,2) | Payment amount            |

### `tblclass`
| Field       | Type     | Description                   |
| ----------- | -------- | ----------------------------- |
| `ClassID`   | INT (PK) | Unique class ID               |
| `ClassName` | VARCHAR  | Class name (e.g., "Grade 10") |
| `Section`   | VARCHAR  | Optional, e.g., "A", "B"      |
 
------------------------------------------------------------------------

## 📦 Installation via Composer

To install **TCPDF** and **PhpSpreadsheet**, run these commands in your project root:

```bash
composer require tecnickcom/tcpdf
composer require phpoffice/phpspreadsheet
```

Or install both in one command:

```bash
composer require tecnickcom/tcpdf phpoffice/phpspreadsheet
```

After installation, include the autoloader:

```php
require 'vendor/autoload.php';
```
------------------------------------------------------------------------

## 🖼 Customization

### Change Logo

Replace the logo file in `report.php`:

``` php
$pdf->Image('logo.png', 10, 10, 30); // (file, x, y, width)
```
Or **Upload logo from UI(index.php)**.

### Change Header Text

Edit in `report.php`:

``` php
$pdf->SetTitle('Monthly Payment Report');
$pdf->Cell(0, 10, 'School Payment Report', 0, 1, 'C');
```

Or **Input at UI page**.

------------------------------------------------------------------------


## ▶️ Usage

1. **Place files on your server**
   Copy all files to your server directory (e.g., `htdocs` if using XAMPP).

2. **Import the database**
   Import the provided database schema and add any sample data.

3. **Configure database connection**
   Update the database credentials in `report.php`:

   ```php
   $host = "localhost";
   $user = "root";
   $pass = "";
   $db   = "studentmsdb";
   ```

4. **Open in your browser**
   Navigate to:

   ```
   http://localhost/[script path]/index.php
   ```

5. **Apply filters and generate report**

   * **Select Student:**

     * If no class is selected, all students will be included.
     * If specific classes are selected, only students from those classes will be available.
   * **Select Date Range:**

     * Choose the year, starting month, and ending month.
   * Click **Generate PDF** or **Export to Excel** to create the report.

6. **Pick a Logo (optional)**
   Select a logo image if desired. By default, the image in the `image` folder will be used.

7. **Enter Report Title**
   Type your report title. A default title is provided as a placeholder.



------------------------------------------------------------------------

## 📑 Example Output

-   Student details (Name, Email)
-   Full payment history within date range
-   Outstanding balance clearly shown
-   Branded header + logo

| BIL | Student  | Student ID | Class    | Apr | May | ... | Total | Outstanding |
| --- | -------- | ---------- | -------- | --- | --- | --- | ----- | ----------- |
| 1   | John Doe | 101        | Grade 10 | 50  | 100 | ... | 800   | 200         |


------------------------------------------------------------------------

## 📘 Notes

* Works smoothly with **XAMPP** or **WAMP**.
* All scripts are clean and well-commented for easy customization.
* TCPDF and PhpSpreadsheet support **UTF-8** and **multilingual** text.
* `table-layout:auto` ensures adaptable table widths in exported PDFs.

------------------------------------------------------------------------

© 2025 Payment Report Module
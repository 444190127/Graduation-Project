<?php
header('Content-Type: application/pdf');
require_once('C:\xampp\htdocs\vendor\autoload.php'); 

$servername = "localhost";
$username = "KKK";
$password = "Azoz1234";
$dbname = "ats";

// إنشاء الاتصال
$conn = new mysqli($servername, $username, $password, $dbname);

// التحقق من الاتصال
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

class MYPDF extends TCPDF {
    //Page header
    public function Header() {
        // Logo
        $image_file = 'C:\xampp\htdocs\Logo.jpg'; // تغيير المسار إلى الشعار الخاص بك
        $this->Image($image_file, 10, 10, 25, '', 'JPG', '', 'T', false, 300, '', false, false, 0, false, false, false);
        // Set font
        $this->SetFont('helvetica', 'B', 12);
        // Title
        $this->Cell(0, 15, 'Trackify - Applicants Report', 0, false, 'C', 0, '', 0, false, 'M', 'M');
    }
}

// الاستعلام لاختيار البيانات المطلوبة
$sql = "SELECT SeekerID, LastName, FirstName, PhoneNumber, Email, Gender, Experience, Degree, Speciality, Languages, CV FROM seeker";
$result = $conn->query($sql);

$reportData = [];

if ($result && $result->num_rows > 0) {
    // إخراج البيانات لكل صف
    while($row = $result->fetch_assoc()) {
        $reportData[] = $row;
    }
} else {
    echo "0 results";
    exit; // إنهاء السكربت إذا لم يكن هناك نتائج
}

// إغلاق الاتصال
$conn->close();

// إنشاء PDF
$pdf = new MYPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
$pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE.' 001', PDF_HEADER_STRING);
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// تعيين معلومات الوثيقة
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Your Name');
$pdf->SetTitle('Trackify - Applicants Report');
$pdf->SetSubject('Applicants Report');

// إضافة صفحة
$pdf->AddPage();
// إنشاء HTML للتقرير
$html = <<<EOD
<h1 style="text-align: center;">Applicants Report</h1>
<table border="1" cellpadding="4" style="border-collapse: collapse;">
  <tr>
    <th><strong>Email</strong></th>
    <th><strong>Full Name</strong></th>
    <th><strong>Gender</strong></th>
    <th><strong>Degree</strong></th>
    <th><strong>Speciality</strong></th>
    <th><strong>Experience</strong></th>
  </tr>
EOD;

// Print each data row
foreach ($reportData as $data) {
    $html .= "<tr>";
    $html .= "<td>{$data['Email']}</td>";
    $html .= "<td>{$data['FirstName']} {$data['LastName']}</td>";
    $html .= "<td>{$data['Gender']}</td>";
    $html .= "<td>{$data['Degree']}</td>";
    $html .= "<td>{$data['Speciality']}</td>";
    $html .= "<td>{$data['Experience']}</td>";
    $html .= "</tr>";
}

$html .= "</table>";


// Print text using writeHTML()
$pdf->writeHTML($html, true, false, true, false, '');

// Close and output PDF document
$pdf->Output('report.pdf', 'I');?>

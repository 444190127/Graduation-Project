<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("error_log", "error.log");

// تبدأ الجلسة
session_start();

// إعدادات الاتصال بقاعدة البيانات
$host = 'localhost';
$dbUser = 'KKK'; // اسم المستخدم لقاعدة البيانات
$dbPassword = 'Azoz1234'; // كلمة المرور لقاعدة البيانات
$dbName = 'ats'; // اسم قاعدة البيانات

// إنشاء اتصال جديد
$mysqli = new mysqli($host, $dbUser, $dbPassword, $dbName);

// التحقق من الاتصال
if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}

// بريد المستخدم
$email = $_SESSION['email'] ?? '';

// استعلام بيانات المستخدم
$stmt = $mysqli->prepare("SELECT company_name, ImagePath, Open_Jobs, Candidates, New_Messages FROM recruiter WHERE company_email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$recruiterData = $result->fetch_assoc();
$stmt->close();

// استعلام آخر الوظائف
$jobsStmt = $mysqli->prepare("SELECT * FROM job WHERE company_email = ? ORDER BY DatePosted DESC LIMIT 5");
$jobsStmt->bind_param("s", $email);
$jobsStmt->execute();
$jobsResult = $jobsStmt->get_result();
$jobsData = [];
while ($job = $jobsResult->fetch_assoc()) {
    $jobsData[] = $job;
}
$jobsStmt->close();

// استعلام لجلب عناوين الوظائف
$jStmt = $mysqli->prepare("SELECT JobID, title FROM job WHERE company_email = ?");
$jStmt->bind_param("s", $email);
$jStmt->execute();
$jResult = $jStmt->get_result();
$jData = [];
while ($job = $jResult->fetch_assoc()) {
    $jData[] = $job;
}
$jStmt->close();


$response = [
    'openJobs' => $recruiterData['Open_Jobs'] ?? '0',
    'candidates' => $recruiterData['Candidates'] ?? '0',
    'newMessages' => $recruiterData['New_Messages'] ?? '0',
    'companyName' => $recruiterData['company_name'] ?? 'Default Company Name',
    'companyLogo' => $recruiterData['ImagePath'] ?? 'path/to/default/logo.png',
        'latestJobs' => $jobsData,
        'jobs' => $jData,
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['jobTitle'])) {
    // استلام البيانات من الطلب
    $jobTitle = $mysqli->real_escape_string($_POST['jobTitle']);
    $jobDescription = $mysqli->real_escape_string($_POST['jobDescription']);
    $jobLocation = $mysqli->real_escape_string($_POST['jobLocation']);
    $degreeType = $mysqli->real_escape_string($_POST['degreeType']);
    $salary = $mysqli->real_escape_string($_POST['salary']);
    $expireDate = $mysqli->real_escape_string($_POST['expireDate']);
    $tags = $mysqli->real_escape_string($_POST['tags']);

    $stmt = $mysqli->prepare("INSERT INTO job (Title, JobDescription, Location, DegreeType, Salary, ExpireDate, Tags, company_email) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssss", $jobTitle, $jobDescription, $jobLocation, $degreeType, $salary, $expireDate, $tags, $email);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = "Job posted successfully";
    } else {
        $response['success'] = false;
        $response['message'] = "Error: " . $stmt->error;
    }
    $stmt->close();

    // بعد إضافة الوظيفة، يجب جمع بيانات الوظائف الجديدة مرة أخرى هنا إذا لزم الأمر
}

header('Content-Type: application/json');
echo json_encode($response);
$mysqli->close();
exit; // تأكد من إنهاء السكربت بعد إرسال الاستجابة
?>

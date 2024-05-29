<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("error_log", "error.log");

session_start();

if (!isset($_SESSION['email'])) {
    echo json_encode(['error' => 'User is not logged in']);
    exit;
}

$host = 'localhost';
$dbUser = 'KKK'; // اسم المستخدم لقاعدة البيانات
$dbPassword = 'Azoz1234'; // كلمة المرور لقاعدة البيانات
$dbName = 'ats'; // اسم قاعدة البيانات

$mysqli = new mysqli($host, $dbUser, $dbPassword, $dbName);

if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}
$email = $_SESSION['email'];

$stmt = $mysqli->prepare("SELECT ImagePath, company_name FROM recruiter WHERE company_email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $imagePath = $row['ImagePath'];
    $companyName = $row['company_name'];
    error_log("ImagePath: " . $imagePath);
    error_log("CompanyName: " . $companyName);
} else {
    echo json_encode(['error' => 'data not found']);
    exit;
}
$stmt->close();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['jobTitle'])) {
    $jobTitle = $mysqli->real_escape_string($_POST['jobTitle']);
    $jobDescription = $mysqli->real_escape_string($_POST['jobDescription']);
    $jobLocation = $mysqli->real_escape_string($_POST['jobLocation']);
    $degreeType = $mysqli->real_escape_string($_POST['degreeType']);
    $salary = $mysqli->real_escape_string($_POST['salary']);
    $expireDate = $mysqli->real_escape_string($_POST['expireDate']);
    $tags = $mysqli->real_escape_string($_POST['tags']);
    $experience = $mysqli->real_escape_string($_POST['experience']); //jobType
    $jobType = $mysqli->real_escape_string($_POST['jobType']); //jobType


    $stmt = $mysqli->prepare("INSERT INTO job (Title, JobDescription, Location, DegreeType, Salary, ExpireDate, Tags, Experience, jobType, company_email, ImagePath, company_name) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("ssssssssssss", $jobTitle, $jobDescription, $jobLocation, $degreeType, $salary, $expireDate, $tags, $experience, $jobType, $email, $imagePath, $companyName);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => "Job posted successfully"]);
    } else {
        echo json_encode(['error' => "Error: " . $stmt->error]);
    }
    $stmt->close();
} else {
    echo json_encode(['error' => 'Invalid request']);
}

$mysqli->close();
?>

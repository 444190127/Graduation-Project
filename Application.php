<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("error_log", "php-error.log");
session_start();

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header("Access-Control-Allow-Headers: X-Requested-With");

$mysqli = new mysqli("localhost", "KKK", "Azoz1234", "ats");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$response = ['success' => false, 'message' => ''];

if (!isset($_SESSION['email'])) {
    $response['message'] = 'User is not logged in.';
    echo json_encode($response);
    exit;
}

$email = $_SESSION['email']; // استخدم البريد الإلكتروني من الجلسة

// استعلم عن الـ SeekerID باستخدام البريد الإلكتروني
$stmt = $mysqli->prepare("SELECT SeekerID FROM seeker WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
if ($row = $result->fetch_assoc()) {
    $seeker_id = $row['SeekerID'];
} else {
    $response['message'] = 'No seeker found for the provided email.';
    echo json_encode($response);
    exit;
}
$stmt->close();

if(isset($_POST['job_id'])) {
    $job_id = $_POST['job_id'];

    // إدراج طلب التقديم في قاعدة البيانات
    $stmt = $mysqli->prepare("INSERT INTO application (JobID, SeekerID, ASD) VALUES (?, ?, NOW())");
    $stmt->bind_param("ii", $job_id, $seeker_id);

    if ($stmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Application submitted successfully.';
    } else {
        $response['message'] = 'Failed to submit application: ' . $stmt->error;
    }
    $stmt->close();
} else {
    $response['message'] = 'Job ID not provided.';
}

echo json_encode($response);
$mysqli->close();
?>

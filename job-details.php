<?php
header('Content-Type: application/json');
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("error_log", "error.log");
session_start();
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header("Access-Control-Allow-Headers: X-Requested-With");

$mysqli = new mysqli("localhost", "KKK", "Azoz1234", "ats");

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

$response = ['success' => false, 'message' => ''];

// هنا نضيف البريد الإلكتروني إلى الاستجابة إذا كان موجودًا
if (isset($_SESSION['email'])) {
    $response['email'] = $_SESSION['email'];
} else {
    $response['email'] = null; // أو يمكنك حذف هذا الخط إذا كنت لا تريد إرسال الايميل عند عدم تسجيل الدخول
}

if(isset($_GET['id'])) {
    $jobId = $_GET['id'];

    // استرجاع تفاصيل الوظيفة من جدول job
    $stmt = $mysqli->prepare("SELECT * FROM job WHERE JobID = ?");
    $stmt->bind_param("i", $jobId);
    $stmt->execute();
    $result = $stmt->get_result();
    $jobDetails = $result->fetch_assoc();
    $stmt->close();

    if($jobDetails) {
        // استرجاع بيانات من جدول recruiter
        $stmt = $mysqli->prepare("SELECT phone_number, address FROM recruiter WHERE company_email = ?");
        $stmt->bind_param("s", $jobDetails['company_email']);
        $stmt->execute();
        $result = $stmt->get_result();
        $recruiterDetails = $result->fetch_assoc();
        $stmt->close();

        if($recruiterDetails) {
            // دمج بيانات الوظيفة مع بيانات recruiter وإرسالها كـ JSON
            $response['data'] = array_merge($jobDetails, $recruiterDetails);
            $response['success'] = true;
        } else {
            $response['message'] = 'Recruiter details not found.';
        }
    } else {
        $response['message'] = 'Job details not found.';
    }
} else {
    $response['message'] = 'Job ID not provided.';
}

echo json_encode($response);

$mysqli->close();
?>

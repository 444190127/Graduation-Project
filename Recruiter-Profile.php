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
$dbUser = 'KKK';
$dbPassword = 'Azoz1234';
$dbName = 'ats';

$mysqli = new mysqli($host, $dbUser, $dbPassword, $dbName);

if ($mysqli->connect_error) {
    die('Connection failed: ' . $mysqli->connect_error);
}

$email = $_SESSION['email']; // البريد الإلكتروني للمُجند المُسجل الدخول

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $companyName = $mysqli->real_escape_string($_POST['company_name'] ?? '');
    $contactNumber = $mysqli->real_escape_string($_POST['phone_number'] ?? '');
    $website = $mysqli->real_escape_string($_POST['website'] ?? '');
    $bio = $mysqli->real_escape_string($_POST['bio'] ?? '');
    $experience = $mysqli->real_escape_string($_POST['experience'] ?? '');
    $numEmployees = isset($_POST['num_employees']) ? intval($_POST['num_employees']) : 0;
    $languages = $mysqli->real_escape_string($_POST['languages'] ?? '');
    $categories = $mysqli->real_escape_string($_POST['categories'] ?? '');
    $address = $mysqli->real_escape_string($_POST['address'] ?? '');
    $averageWage = $mysqli->real_escape_string($_POST['average_wage'] ?? '');

    $imagePath = 'assets/imgs/brands/logo.png';
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif']; // أنواع الملفات المسموح بها
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $targetDir = "uploads/";
        $fileName = basename($_FILES['image']['name']);
        $targetFilePath = $targetDir . $fileName;
        $fileType = pathinfo($targetFilePath,PATHINFO_EXTENSION);

        if (in_array($fileType, $allowedTypes) && $_FILES['image']['size'] < 5000000) {
            if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFilePath)) {
                $imagePath = $targetFilePath;
            } else {
                echo json_encode(['error' => 'Failed to upload image']);
                exit;
            }
        } else {
            echo json_encode(['error' => 'Invalid file type or size']);
            exit;
        }
    }

    // إعداد الاستعلام لتحديث بيانات المُجند
    $stmt = $mysqli->prepare("UPDATE recruiter SET company_name=?, phone_number=?, website=?, ImagePath=?, bio=?, experience=?, num_employees=?, languages=?, categories=?, address=?, average_wage=? WHERE company_email=?");
    $stmt->bind_param("sssssiisssss",
    $companyName,    // string
    $contactNumber,  // string
    $website,        // string
    $imagePath,      // string
    $bio,            // string
    $experience,     // integer, assumed to be converted to int already
    $numEmployees,   // integer
    $languages,      // string
    $categories,     // string
    $address,        // string
    $averageWage,    // string (assuming this is a string representation of a number)
    $email           // string
);      
    if ($stmt->execute()) {
        $response = ['success' => true, 'message' => 'Profile updated successfully'];
    } else {
        $response = ['success' => false, 'message' => 'Failed to update profile: ' . $stmt->error];
    }
    $stmt->close();
    echo json_encode($response);
} else {
    // استعلام لجلب بيانات المُجند عند تحميل الصفحة
    $stmt = $mysqli->prepare("SELECT * FROM recruiter WHERE company_email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    $recruiterData = $result->fetch_assoc();
    if ($recruiterData) {
        echo json_encode(['success' => true, 'recruiter' => $recruiterData]);
    } else {
        echo json_encode(['success' => false, 'error' => 'No data found for the given email']);
    }
    $stmt->close();
}

$mysqli->close();
?>

<?php
ob_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', 'C:\xampp\htdocs\error.log');
session_start();

$mysqli = new mysqli("localhost", "KKK", "Azoz1234", "ats");
$response = ['success' => false, 'message' => 'An unknown error occurred', 'user' => null, 'jobs' => [], 'cvSuccess' => false, 'cvFilename' => ''];

if ($mysqli->connect_error) {
    $response['message'] = "Connection failed: " . $mysqli->connect_error;
    echo json_encode($response);
    exit;
}

if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];
    $userStmt = $mysqli->prepare("SELECT * FROM seeker WHERE Email = ?");
    if ($userStmt) {
        $userStmt->bind_param("s", $email);
        $userStmt->execute();
        $result = $userStmt->get_result();
        if ($result) {
            $user = $result->fetch_assoc();
            $response['user'] = $user;

            // تخزين SeekerID في الجلسة
            $_SESSION['seeker_id'] = $user['SeekerID'];
        }
        $userStmt->close();
    } else {
        $response['message'] = "Failed to prepare statement: " . $mysqli->error;
    }
    $mysqli->close();
} else {
    $response['message'] = "User is not logged in.";
}

echo json_encode($response);
exit;
?>

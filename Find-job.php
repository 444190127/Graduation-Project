<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("error_log", "error.log");
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST');
header("Access-Control-Allow-Headers: X-Requested-With");

session_start();

$response = ['success' => false, 'message' => ''];

if (isset($_SESSION['email'])) {
    $email = $_SESSION['email'];
    $response['success'] = true;
    $response['message'] = "Email exists in session: $email";
    
    $mysqli = new mysqli("localhost", "KKK", "Azoz1234", "ats");
    if ($mysqli->connect_error) {
        die("Connection failed: " . $mysqli->connect_error);
    }
    
    $result = $mysqli->query("SELECT * FROM job");
    $jobs = [];
    while($row = $result->fetch_assoc()) {
        $jobs[] = $row;
    }
    $mysqli->close();
    
    $response['jobs'] = $jobs;
} else {
    $response['success'] = false;
    $response['message'] = "No email set in session.";
}

echo json_encode($response);
?>

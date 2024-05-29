<?php
ob_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/error.log');

$response = ['success' => false, 'message' => 'An unknown error occurred']; // Initialize response array

header('Content-Type: application/json'); // Ensure JSON response is properly interpreted

$mysqli = new mysqli("localhost", "KKK", "Azoz1234", "ats");

if ($mysqli->connect_error) {
    die('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $role = $_POST['Role-choice'] ?? ''; // Make sure this matches the name attribute of your select element
    $fullName = $_POST['fullname'] ?? '';
    $email = $_POST['emailaddress'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    $hashedPassword = md5($password);

    // Log the role for debugging
    error_log("Role: " . $role);

    if ($role === 'Recruiter') {
        $query = "INSERT INTO recruiter (company_name, phone_number, company_email, password) VALUES (?, ?, ?, ?)";
        // Adjust the 'phone' field based on your form, assuming you have a phone input for recruiters
        $phone = $_POST['phone'] ?? '';
        $insertStmt = $mysqli->prepare($query);
        $insertStmt->bind_param("ssss", $fullName, $phone, $email, $hashedPassword);        

        // Log inserting into recruiter table
        error_log("Inserting into recruiter table");
    } else {
        $query = "INSERT INTO seeker (FirstName, Email, Password) VALUES (?, ?, ?)";
        $insertStmt = $mysqli->prepare($query);
        $insertStmt->bind_param("sss", $fullName, $email, $hashedPassword);

        // Log inserting into seeker table
        error_log("Inserting into seeker table");
    }

    if ($insertStmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Account created successfully.';
        header("Location:page-signin.html");
    } else {
        $response['message'] = 'Failed to create account: ' . $insertStmt->error;
        // Log error
        error_log('Failed to create account: ' . $insertStmt->error);
    }
    $insertStmt->close();
} else {
    $response['message'] = 'Invalid request method.';
}

$mysqli->close();
echo json_encode($response);
?>

<?php
// Start the session.
session_start();

// Check if the recruiter is logged in and the seekerId is set in the URL.
if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => 'Recruiter is not logged in']);
    exit;
}

// Assuming the session is valid and the seekerId is passed as a query parameter.
$seekerId = isset($_GET['seekerId']) ? $_GET['seekerId'] : null;

// Database connection here...
$mysqli = new mysqli('localhost', 'KKK', 'Azoz1234', 'ats');

if ($mysqli->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Prepare and bind
$stmt = $mysqli->prepare("SELECT * FROM seeker WHERE SeekerID = ?");
$stmt->bind_param("i", $seekerId);

// Set parameters and execute
$stmt->execute();
$result = $stmt->get_result();
$seekerDetails = $result->fetch_assoc();

if ($seekerDetails) {
    echo json_encode(['success' => true, 'details' => $seekerDetails]);
} else {
    echo json_encode(['success' => false, 'message' => 'No details found for the provided SeekerID']);
}

$stmt->close();
$mysqli->close();
?>

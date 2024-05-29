<?php
header('Content-Type: application/json');

// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set("log_errors", 1);
ini_set("error_log", "error.log");

// Start or resume a session
session_start();

// Check if the recruiter is logged in
if (!isset($_SESSION['email'])) {
    // If not logged in, return an error in JSON format
    die(json_encode(['success' => false, 'message' => 'Recruiter is not logged in']));
}

// Establish a connection to the database
$mysqli = new mysqli("localhost", "KKK", "Azoz1234", "ats");
if ($mysqli->connect_error) {
    // If connection fails, return an error in JSON format
    die(json_encode(['success' => false, 'message' => 'Connection failed: ' . $mysqli->connect_error]));
}

// Check for a selected job ID
$selectedJobID = $_GET['jobID'] ?? null;  // Change to $_GET if the ID is being passed as a query parameter
// Fetch applications for the selected job ID
$applicationIDs = [];
if ($selectedJobID) {
    $applicationQuery = $mysqli->prepare("SELECT SeekerID FROM application WHERE JobID = ? AND Selected = 0");
    $applicationQuery->bind_param('i', $selectedJobID);
    $applicationQuery->execute();
    $applicationResult = $applicationQuery->get_result();
    $applicationQuery->close();


    while ($application = $applicationResult->fetch_assoc()) {
        $applicationIDs[] = $application['SeekerID'];
    }
}

// Fetch all seeker data based on SeekerIDs
$seekers = [];
if (!empty($applicationIDs)) {
    $inClause = implode(',', array_fill(0, count($applicationIDs), '?'));
    $seekersQuery = $mysqli->prepare("SELECT * FROM seeker WHERE SeekerID IN ($inClause)");
    $seekersQuery->bind_param(str_repeat('i', count($applicationIDs)), ...$applicationIDs);
    $seekersQuery->execute();
    $seekersResult = $seekersQuery->get_result();
    $seekersQuery->close();

    while ($seeker = $seekersResult->fetch_assoc()) {
        $seekers[] = $seeker;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['selectedCandidates'])) {
    // Since there's a POST request and it's not empty, handle the candidate selection
    $selectedCandidates = $_POST['selectedCandidates'];

    // Begin transaction to ensure atomicity
    $mysqli->begin_transaction();
    try {
        $updateQuery = "UPDATE application SET Selected = 1 WHERE SeekerID = ?";
        $stmt = $mysqli->prepare($updateQuery);
        if (false === $stmt) {
            throw new Exception("Failed to prepare the statement: " . $mysqli->error);
        }
    
        foreach ($selectedCandidates as $seekerID => $value) {
            $stmt->bind_param('i', $seekerID);
            $stmt->execute();
            if ($stmt->error) {
                throw new Exception("MySQL error: " . $stmt->error);
            }
        }
    
        $stmt->close();
        $mysqli->commit();

        echo json_encode(['success' => true, 'message' => 'Candidates updated successfully.']);
    } catch (Exception $e) {
        $mysqli->rollback();
        echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
    } finally {
        $mysqli->close();
    }
}

echo json_encode(['success' => true, 'seekers' => $seekers]);
exit;
?>

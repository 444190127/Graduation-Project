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

// Retrieve Job IDs using the recruiter's email from the session
$recruiterEmail = $_SESSION['email'];
$jobsQuery = $mysqli->prepare("SELECT JobID FROM job WHERE company_email = ?");
$jobsQuery->bind_param("s", $recruiterEmail);
$jobsQuery->execute();
$jobResult = $jobsQuery->get_result();
$jobsQuery->close();

$jobIDs = [];
while ($job = $jobResult->fetch_assoc()) {
    $jobIDs[] = $job['JobID'];
}

if (empty($jobIDs)) {
    die(json_encode(['success' => false, 'message' => 'No jobs found for this recruiter']));
}

// Debugging: Output JobIDs
error_log("JobIDs: " . json_encode($jobIDs));

// Now you have an array of JobIDs, let's fetch the SeekerIDs based on those JobIDs
$applicationIDs = [];
if (!empty($jobIDs)) {
    $inClause = implode(',', array_fill(0, count($jobIDs), '?'));
    $applicationQuery = $mysqli->prepare("SELECT SeekerID FROM application WHERE JobID IN ($inClause) AND Selected = 1");
    $applicationQuery->bind_param(str_repeat('i', count($jobIDs)), ...$jobIDs);
    $applicationQuery->execute();
    $applicationResult = $applicationQuery->get_result();
    $applicationQuery->close();

    while ($application = $applicationResult->fetch_assoc()) {
        $applicationIDs[] = $application['SeekerID'];
    }
}

if (empty($applicationIDs)) {
    die(json_encode(['success' => false, 'message' => 'No selected applications found']));
}

// Debugging: Output ApplicationIDs
error_log("ApplicationIDs: " . json_encode($applicationIDs));

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

if (empty($seekers)) {
    die(json_encode(['success' => false, 'message' => 'No seekers found']));
}

// Debugging: Output Seekers
error_log("Seekers: " . json_encode($seekers));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['selectedCandidates'])) {
    // Check if the recruiter is logged in
    if (!isset($_SESSION['email'])) {
        die(json_encode(['success' => false, 'message' => 'Recruiter is not logged in']));
    }

    // Since there's a POST request and it's not empty, handle the candidate selection
    $selectedCandidates = $_POST['selectedCandidates'];

    // Begin transaction to ensure atomicity
    $mysqli->begin_transaction();
    try {
        $updateQuery = "UPDATE application SET Selected = 1 WHERE SeekerID = ?";
        $stmt = $mysqli->prepare($updateQuery);

        foreach ($selectedCandidates as $seekerID) {
            $stmt->bind_param('i', $seekerID);
            $stmt->execute();
        }

        $stmt->close();
        $mysqli->commit();

        echo json_encode(['success' => true, 'message' => 'Candidates updated successfully.']);
        $mysqli->close();
        exit; // Close the connection and exit after updating the candidates
    } catch (Exception $e) {
        $mysqli->rollback(); // Rollback any changes if there's an error
        echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
        $mysqli->close();
        exit;
    }
}

// If it's not a POST request, or no candidates were selected, just retrieve and display the data
// Retrieve seekers (candidates) data
echo json_encode(['success' => true, 'seekers' => $seekers]);

// Always close the connection
$mysqli->close();
exit;
?>

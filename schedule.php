<?php
session_start();
require 'vendor/autoload.php'; // تأكد من أن مكتبة Mailgun PHP مثبتة باستخدام Composer

use Mailgun\Mailgun;
use GuzzleHttp\Client;

$servername = "localhost";
$username = "KKK";
$password = "Azoz1234";
$dbname = "ats";

if (!isset($_SESSION['email'])) {
    die("Email is not set in the session.");
}

$email = $_SESSION['email'];

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$recruiterID = null;
$recruiterQuery = "SELECT RecruiterID FROM recruiter WHERE company_email = ?";
$recruiterStmt = $conn->prepare($recruiterQuery);
$recruiterStmt->bind_param("s", $email);
$recruiterStmt->execute();
$recruiterStmt->bind_result($recruiterID);
$recruiterFound = $recruiterStmt->fetch();
$recruiterStmt->close();

if (!$recruiterFound) {
    die("Recruiter not found.");
}

// استقبال بيانات JSON من AJAX
$json = file_get_contents('php://input');
$data = json_decode($json, true); // تحويل ال JSON إلى مصفوفة PHP

// تحقق من البيانات المرسلة
if (!isset($data['interviews'])) {
    die("Interview data is missing.");
}

$status = 'pending';

$mg = Mailgun::create('99eedd445545aeca3bae0c6b3874d5bb-32a0fef1-612ed36f'); 
$domain = "sandboxfd8a3e16f0e0401697ae771e4c921e4f.mailgun.org"; 

// Zoom API credentials
$zoomClientId = 'N6IrZhxURkWxVzYi0in9cw';
$zoomClientSecret = '2MjfeC0WCSVQB9PQm4bF1caNpDv78BmS';

function getZoomAccessToken($zoomClientId, $zoomClientSecret) {
    $client = new Client();
    try {
        $response = $client->request('POST', 'https://zoom.us/oauth/token', [
            'auth' => [$zoomClientId, $zoomClientSecret],
            'form_params' => [
                'grant_type' => 'client_credentials'
            ]
        ]);

        $data = json_decode($response->getBody(), true);
        var_dump($data); // طباعة التوكن للتحقق منه
        return $data['access_token'];
    } catch (Exception $e) {
        echo 'Error: ' . $e->getMessage();
    }
}

// Function to create Zoom meeting
function createZoomMeeting($accessToken, $startTime) {
    $client = new Client([
        'base_uri' => 'https://api.zoom.us',
    ]);

    try {
        $response = $client->request('POST', '/v2/users/me/meetings', [
            'headers' => [
                'Authorization' => "Bearer $accessToken",
                'Content-Type' => 'application/json',
            ],
            'json' => [
                'topic' => 'Interview',
                'type' => 2,
                'start_time' => $startTime,
                'duration' => 30, // duration in minutes
                'timezone' => 'UTC',
            ],
        ]);

        $data = json_decode($response->getBody(), true);
        return $data['join_url'];
    } catch (Exception $e) {
        echo 'Error creating meeting: ' . $e->getMessage();
    }
}

$accessToken = getZoomAccessToken($zoomClientId, $zoomClientSecret);

foreach ($data['interviews'] as $interview) {
    // تأكد من أن كل مفتاح موجود
    if (!isset($interview['seekerID']) || !isset($interview['dateTime']) || !isset($interview['email'])) {
        die("Missing data for seeker ID, email, or date/time.");
    }
    
    $seekerID = $interview['seekerID'];
    $interviewDate = $interview['dateTime'];
    $email = $interview['email'];

    // إدخال المقابلة في قاعدة البيانات
    $sql = "INSERT INTO interview (Interview_date, Status, SeekerID, RecruiterID) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    
    if (!$stmt) {
        die("Error in SQL statement: " . $conn->error);
    }
    
    $stmt->bind_param("ssii", $interviewDate, $status, $seekerID, $recruiterID);
    
    if (!$stmt->execute()) {
        echo "Error: " . $stmt->error;
    } else {
        echo "New record created successfully for seeker ID: $seekerID\n";
    }
    
    $stmt->close();

    // إنشاء اجتماع Zoom
    $zoomLink = createZoomMeeting($accessToken, $interviewDate);

    // إرسال البريد الإلكتروني باستخدام Mailgun
    $subject = "Interview Scheduled with Our Company";
    $body = "Dear Candidate,\n\nWe are pleased to inform you that an interview has been scheduled for you on " . date('m/d/Y H:i:s', strtotime($interviewDate)) . " with our company.\n\nJoin the Zoom meeting: $zoomLink\n\nBest regards,\nYour Company Name";

    $mg->messages()->send($domain, [
        'from'    => 'postmaster@sandboxfd8a3e16f0e0401697ae771e4c921e4f.mailgun.org', // استخدم عنوان البريد الإلكتروني الذي يظهر في Mailgun
        'to'      => $email,
        'subject' => $subject,
        'text'    => $body
    ]);
}

$conn->close();
?>
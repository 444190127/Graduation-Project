<?php
session_start(); // بدء جلسة

// التحقق من تسجيل دخول الباحث
if (!isset($_SESSION['seeker_id'])) {
    die("You are not logged in. Session contents: " . json_encode($_SESSION));
}

$seeker_id = $_SESSION['seeker_id']; // استخراج معرّف الباحث من الجلسة

// تحقق من قيمة seeker_id
if (empty($seeker_id)) {
    die("Seeker ID is empty.");
}

// الاتصال بقاعدة البيانات
$conn = new mysqli("localhost", "KKK", "Azoz1234", "ats");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// استعلام لجلب Job IDs المرتبطة بالSeekerID
$jobIdsStmt = $conn->prepare("SELECT JobID FROM application WHERE SeekerID = ?");
$jobIdsStmt->bind_param("i", $seeker_id);
$jobIdsStmt->execute();
$jobIdsResult = $jobIdsStmt->get_result();

$jobIds = [];
while ($jobId = $jobIdsResult->fetch_assoc()) {
    $jobIds[] = $jobId['JobID'];
}
$jobIdsStmt->close();

// تحقق من أنه تم استرجاع Job IDs
if (count($jobIds) == 0) {
    echo json_encode(['success' => false, 'message' => 'No jobs found for this seeker.']);
    exit;
}

// تحويل array إلى string للاستعلام SQL
$jobIdsString = implode(',', $jobIds);

// استعلام لجلب بيانات الوظائف بناءً على Job IDs
$jobsQuery = "SELECT * FROM job WHERE JobID IN ($jobIdsString) ORDER BY DatePosted DESC";
$jobsResult = $conn->query($jobsQuery);

if ($jobsResult === false) {
    die("Error in job query: " . $conn->error);
}

$jobsData = [];
while ($job = $jobsResult->fetch_assoc()) {
    $jobsData[] = $job;
}

// تحضير وإرسال الاستجابة
$response = [
    'success' => true,
    'jobs' => $jobsData
];

echo json_encode($response);
$conn->close();
?>

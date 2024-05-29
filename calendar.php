<?php
session_start();
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
$sql = "SELECT * FROM interview WHERE status = 'pending'";
$result = $conn->query($sql);

// التحقق من أن الاستعلام يرجع نتائج
if ($result && $result->num_rows > 0) {
    // تحويل النتائج إلى JSON
    $events = array_map(function($row) {
        return [
            'title' => 'Interview with Seeker ' . $row['SeekerID'],
            'start' => $row['Interview_date'],
            // افتراض أن كل مقابلة تستمر ساعة واحدة
            'end' => date('Y-m-d H:i:s', strtotime($row['Interview_date'] . ' +1 hour'))
        ];
    }, $result->fetch_all(MYSQLI_ASSOC));
    header('Content-Type: application/json');
    echo json_encode($events);
} else {
    echo "0 results";
}

// إغلاق الاتصال بقاعدة البيانات
$conn->close();
?>

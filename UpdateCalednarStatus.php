<?php
$servername = "localhost";
$username = "KKK";
$password = "Azoz1234";
$dbname = "ats";

// إنشاء الاتصال
$conn = new mysqli($servername, $username, $password, $dbname);

// التحقق من الاتصال
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// استعلام SQL لتحديث حالة المقابلات
$sql = "UPDATE interview SET Status='Ended' WHERE Interview_date < NOW() AND Status='pending'";

if ($conn->query($sql) === TRUE) {
  echo "Record updated successfully";
} else {
  echo "Error updating record: " . $conn->error;
}

$conn->close();
?>

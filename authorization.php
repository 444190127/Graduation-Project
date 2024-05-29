<?php
session_start();

// إعداد بيانات Zoom API
$zoomClientId = 'wZPi2PhwvL9-87I1b-vQfeNqPgT0ep1mQ';
$redirectUri = 'http://localhost/schedule.php';

// إعادة التوجيه إلى صفحة التوثيق الخاصة بـ Zoom
header('Location: https://zoom.us/oauth/authorize?response_type=code&client_id=' . $zoomClientId . '&redirect_uri=' . urlencode($redirectUri));
exit;
?>
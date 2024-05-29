<?php
ob_start();
session_start();
header('Content-Type: application/json');

// بدء الجلسة

// فرض أن المستخدم قد سجل دخول بنجاح
if (isset($_POST['email'])) {
    $_SESSION['email'] = $_POST['email']; // تخزين البريد الإلكتروني في الجلسة
} elseif (!isset($_SESSION['email'])) {
    // إذا لم يكن هناك بريد إلكتروني معين ولا يوجد بريد مخزن، أعد توجيه المستخدم لتسجيل الدخول
    header('Location: login.php');
    exit;
}

$db_host = "localhost";
$db_user = "KKK";
$db_password = "Azoz1234";
$db_name = "ats";

// الاتصال بقاعدة البيانات
$mysqli = new mysqli($db_host, $db_user, $db_password, $db_name);
if ($mysqli->connect_error) {
    echo json_encode(['success' => false, 'message' => "Connection failed: " . $mysqli->connect_error]);
    exit;
}

$response = ['success' => false, 'message' => 'An unknown error occurred'];

// التحقق من وجود المستخدم في الجلسة
if (!isset($_SESSION['email'])) {
    echo json_encode(['success' => false, 'message' => "User is not logged in."]);
    exit;
}

$email = $_SESSION['email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // تحديث البيانات
    $firstName = $_POST['FirstName'] ?? '';
    $lastName = $_POST['LastName'] ?? '';
    $contactNumber = $_POST['PhoneNumber'] ?? '';
    $bio = $_POST['Bio'] ?? '';
    $experience = $_POST['Experience'] ?? '';
    $degree = $_POST['Degree'] ?? '';
    $speciality = $_POST['Speciality'] ?? '';
    $languages = $_POST['Languages'] ?? '';
    $personalWebsite = $_POST['PersonalWebsite'] ?? '';
    $gender = $_POST['Gender'] ?? '';

    $updateStmt = $mysqli->prepare("UPDATE seeker SET FirstName=?, LastName=?, PhoneNumber=?, Bio=?, Experience=?, Degree=?, Speciality=?, Languages=?, PersonalWebsite=?, Gender=? WHERE Email=?");
    $updateStmt->bind_param("sssssssssss", $firstName, $lastName, $contactNumber, $bio, $experience, $degree, $speciality, $languages, $personalWebsite, $gender, $email);
    
    if ($updateStmt->execute()) {
        $response['success'] = true;
        $response['message'] = 'Profile updated successfully.';
    } else {
        $response['message'] = 'Failed to update profile: ' . $updateStmt->error;
    }
    $updateStmt->close();

    // رفع السيرة الذاتية
    if (isset($_FILES['cv_upload']) && $_FILES['cv_upload']['error'] == 0) {
        $targetDir = "localhost/Cv's/";
        $file = $_FILES['cv_upload'];
        $fileName = basename($file['name']);
        $targetFilePath = $targetDir . $fileName;
        
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        if (move_uploaded_file($file['tmp_name'], $targetFilePath)) {
            $updateCVStmt = $mysqli->prepare("UPDATE seeker SET CV=? WHERE Email=?");
            $updateCVStmt->bind_param("ss", $targetFilePath, $email);
            if ($updateCVStmt->execute()) {
                $response['cvSuccess'] = true;
                $response['cvFilename'] = $fileName;
                $response['message'] .= ' CV uploaded and profile updated successfully.';
            } else {
                $response['message'] = 'Failed to update CV path in the database.';
            }
            $updateCVStmt->close();
        } else {
            $response['message'] = 'Failed to upload CV.';
        }
    }
} else {
    // استرجاع بيانات المستخدم
    $userStmt = $mysqli->prepare("SELECT FirstName, LastName, PhoneNumber, Email, Bio, CV, Gender, Experience, Degree, Speciality, Languages, PersonalWebsite FROM seeker WHERE Email=?");
    $userStmt->bind_param("s", $email);
    $userStmt->execute();
    $result = $userStmt->get_result();
    if ($user = $result->fetch_assoc()) {
        $response = array_merge($response, ['success' => true, 'user' => $user]);
    } else {
        $response['message'] = 'User data could not be retrieved.';
    }
    $userStmt->close();
}

$mysqli->close();
echo json_encode($response);
?>

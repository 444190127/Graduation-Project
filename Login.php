<?php
session_start();
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__FILE__) . '/error.log');
error_reporting(E_ALL);

// Check if the login form has been submitted
if (isset($_POST['login'])) {
    // Check if email, password, and role have been provided
    if (!empty($_POST['emailaddress']) && !empty($_POST['password']) && !empty($_POST['role'])) {
        // Database configuration
        $servername = "localhost";
        $dbusername = "KKK"; // Replace with your actual database username
        $dbpassword = "Azoz1234"; // Replace with your actual database password
        $dbname = "ats";
        $role = $_POST['role']; // Role selected in the form

        // Create connection to the database
        $conn = new mysqli($servername, $dbusername, $dbpassword, $dbname);

        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Escape user inputs for security
        $email = $conn->real_escape_string($_POST['emailaddress']);
        $password = md5($_POST['password']); // Hashing the password with md5

        // Choose the table and email field based on the role
        if ($role == 'Recruiter') {
            $table = 'recruiter'; // تأكد من أن هذا هو اسم الجدول الصحيح
            $emailField = 'company_email'; // وهذا هو اسم العمود الصحيح
        } else {
            $table = 'Seeker'; // تأكد من أن هذا هو اسم الجدول الصحيح
            $emailField = 'Email'; // وهذا هو اسم العمود الصحيح
        }

        // Prepare the SQL statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT * FROM $table WHERE $emailField = ? AND password = ?");
        $stmt->bind_param("ss", $email, $password);
        $stmt->execute();
        $result = $stmt->get_result();

        // Fetch the user record
        if ($user = $result->fetch_assoc()) {
            // Set session variables
            $_SESSION['loggedin'] = true;
            $_SESSION['email'] = $email;
            $_SESSION['role'] = $role;

            // Redirect to the appropriate dashboard based on the role
            if ($role == 'Recruiter') {
                header("Location: recruiter-dashboard.html");
            } else {
                header("Location: Dashboard.html");
            }
            exit;
        } else {
            // User not found or incorrect password
            echo "Invalid email or password.";
        }
        
        // Close statement and connection
        $stmt->close();
        $conn->close();
    } else {
        // Required data not provided
        echo "Email address, password, or role not provided.";
    }
}
?>

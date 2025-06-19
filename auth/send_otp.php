<?php
require '../PHPMailer/src/PHPMailer.php';
require '../PHPMailer/src/SMTP.php';
require '../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require '../database/db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = $_POST['email'];
    $name = $_POST['name'];
    $password = password_hash($_POST['password'], PASSWORD_BCRYPT);
    $otp = rand(100000, 999999);

    // Check if email exists
    $checkEmail = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $checkEmail->bind_param("s", $email);
    $checkEmail->execute();
    $result = $checkEmail->get_result();

    if ($result->num_rows > 0) {
        echo "Email already registered!";
        exit;
    }

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO users (name, email, password, otp) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $name, $email, $password, $otp);

    if ($stmt->execute()) {
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'bhavyashahbvs@gmail.com'; // Replace with your Gmail address
        $mail->Password = 'drxa yrdf cmkq yrwn'; // Replace with your app-specific password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;

        $mail->setFrom('bhavyashahbvs@gmail.com', 'Quenzy');
        $mail->addAddress($email);

        $mail->isHTML(true);
        $mail->Subject = "OTP Verification - Quenzy Teacher's Registrations";
        $mail->Body = "Hello $name,<br><br>Your OTP for signup is: <b>$otp</b>.<br><br>Thank you for registering at Quenzy.";

        if ($mail->send()) {
            echo "OTP sent successfully!";
        } else {
            echo "Failed to send OTP!";
        }
    } else {
        echo "Failed to register user!";
    }
}
?>

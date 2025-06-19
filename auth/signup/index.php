<?php
session_start();
require '../../database/db.php'; // Ensure this file uses mysqli
require '../../PHPMailer/src/PHPMailer.php';
require '../../PHPMailer/src/SMTP.php';
require '../../PHPMailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Fetch avatars from the database
// $sql = "SELECT id, avatar_svg FROM avatars LIMIT 5";
// $result = $mysqli->query($sql);

// Initialize error and success messages
$error = '';
$success = '';

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    // Registration Form Submission
    if (isset($_POST['register'])) {
        $name = trim($_POST['name']);
        $email = trim($_POST['email']);
        $day = $_POST['day'];
        $month = $_POST['month'];
        $year = $_POST['year'];
        $country = $_POST['country'];
        // $avatar_id = $_POST['avatar_id'];

        // Basic validation
        if (empty($name) || empty($email) || empty($day) || empty($month) || empty($year) || empty($country) ) {
            $error = "Please fill all the fields.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format.";
        } else {
            $dob = $year . '-' . $month . '-' . $day;

            // Check if email already exists
            $stmt = $mysqli->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $error = "Email already registered. Please use a different email.";
            } else {
                // Generate and send OTP
                $otp = rand(100000, 999999);
                $_SESSION['otp'] = $otp;
                $_SESSION['user_details'] = [
                    'name' => $name,
                    'email' => $email,
                    'dob' => $dob,
                    'country' => $country,
                    // 'avatar_id' => $avatar_id
                ];

                // Send OTP using PHPMailer
                try {
                    $mail = new PHPMailer(true);
                    $mail->isSMTP();
                    $mail->Host = 'smtp.gmail.com';
                    $mail->SMTPAuth = true;
                    $mail->Username = 'bhavyashahbvs@gmail.com'; // Replace with your Gmail address
                    $mail->Password = 'drxa yrdf cmkq yrwn'; // Replace with your app-specific password
                    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                    $mail->Port = 587;

                    $mail->setFrom('bhavyashahbvs@gmail.com', 'LJP Quenzy - Question Paper Generator');
                    $mail->addAddress($email);

                    $mail->isHTML(true);
                    $mail->Subject = 'OTP Verification - Quenzy Registration';
                    $mail->Body = '
                    <!DOCTYPE html>
                    <html lang="en">
                    <head>
                        <meta charset="UTF-8">
                        <meta name="viewport" content="width=device-width, initial-scale=1.0">
                        <style>
                            body {
                                font-family: Arial, sans-serif;
                                background-color: #f4f4f4;
                                margin: 0;
                                padding: 0;
                            }
                            .email-container {
                                max-width: 600px;
                                margin: 20px auto;
                                background-color: #fff;
                                border-radius: 8px;
                                overflow: hidden;
                                box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
                            }
                            .email-header {
                                background-color: #007bff;
                                color: #fff;
                                padding: 20px;
                                text-align: center;
                            }
                            .email-content {
                                padding: 20px;
                                text-align: center;
                            }
                            .verify-button {
                                display: inline-block;
                                padding: 15px 25px;
                                font-size: 16px;
                                color: #fff;
                                background-color: #007bff;
                                text-decoration: none;
                                border-radius: 4px;
                            }
                            .verify-button:hover {
                                background-color: #0056b3;
                            }
                        </style>
                    </head>
                    <body>
                        <div class="email-container">
                            <div class="email-header">
                                <h1>Welcome to Our Website</h1>
                            </div>
                            <div class="email-content">
                                <p>Hello,</p>
                                <p>Thank you for registering on our website. Please enter this OTP where you registered. Here is your one-time password:</p>
                                <h1>OTP: ' . $otp . '</h1>
                                <p>Please do not share this OTP with anyone. If you did not register on our site, please ignore this email.</p>
                            </div>
                        </div>
                    </body>
                    </html>';

                    $mail->send();
                    $_SESSION['step'] = 2; // Move to OTP verification step
                    $success = "OTP has been sent to your email.";
                } catch (Exception $e) {
                    $error = "Could not send OTP. Mailer Error: {$mail->ErrorInfo}";
                }
            }
        }
    }

    // OTP Verification Form Submission
    if (isset($_POST['verify_otp'])) {
        $entered_otp = trim($_POST['otp']);
        if ($entered_otp == $_SESSION['otp']) {
            $_SESSION['step'] = 3; // Move to password setup step
            $success = "OTP verified successfully. Please set your password.";
        } else {
            $error = "Invalid OTP. Please try again.";
        }
    }

    // Password Setup Form Submission
    // Password Setup Form Submission
if (isset($_POST['set_password'])) {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Password validation
    if (empty($password) || empty($confirm_password)) {
        $error = "Please enter and confirm your password.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } elseif (strlen($password) < 8 || strlen($password) > 32) {
        $error = "Password must be between 8 and 32 characters.";
    } elseif (!preg_match('/[A-Z]/', $password)) {
        $error = "Password must contain at least one uppercase letter.";
    } elseif (!preg_match('/[a-z]/', $password)) {
        $error = "Password must contain at least one lowercase letter.";
    } elseif (!preg_match('/[0-9]/', $password)) {
        $error = "Password must contain at least one number.";
    } elseif (!preg_match('/[#\/_@]/', $password)) {
        $error = "Password must contain at least one special character (#, /, _, @).";
    } elseif (preg_match('/[^A-Za-z0-9#\/_@]/', $password)) {
        $error = "Password contains invalid characters. Only letters, numbers, and #, /, _, @ are allowed.";
    } else {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $user_details = $_SESSION['user_details'];

        // Insert user into the database
        $stmt = $mysqli->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $user_details['name'], $user_details['email'], $hashed_password);
        if ($stmt->execute()) {
            $success = "Registration successful! You can now log in.";
            // Clear session
            session_unset();
            session_destroy();
        } else {
            $error = "Registration failed. Please try again.";
        }
    }
}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Qunzy Teacher Signup</title>
    <link rel="stylesheet" href="../../styles/login.css">
    <style>
        .message {
            text-align: center;
            margin-bottom: 15px;
            font-weight: bold;
        }
        .error {
            color: #f44336;
        }
        .success {
            color: #4CAF50;
        }
        body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    display: flex;
    justify-content: center;
    align-items: center;
    height: 100vh;
}
.container {
    background: white;
    padding: 20px;
    border-radius: 5px;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
}
.validation {
    text-align: center;
    color: lightcoral;
}
.validation.valid {
    color: green;
}
.error-messages {
    color: red;
}
    </style>
    <script>
        // Script to handle avatar selection
        /*document.addEventListener('DOMContentLoaded', function () {
            const avatars = document.querySelectorAll('.avatar');
            avatars.forEach(avatar => {
                avatar.addEventListener('click', function () {
                    avatars.forEach(av => av.classList.remove('selected'));
                    avatar.classList.add('selected');
                    document.getElementById('avatar_id').value = avatar.getAttribute('data-avatar');
                });
            });
        });*/
    </script>
</head>
<body>

<div class="container">
<div class="left-section"></div>
<div class="right-section">
    <?php if(!empty($error)): ?>
        <div class="message error"><?php echo $error; ?></div>
    <?php endif; ?>
    <?php if(!empty($success)): ?>
        <div class="message success"><?php echo $success; ?></div>
    <?php endif; ?>

    <?php
    // Determine which form to display
    if (isset($_SESSION['step']) && $_SESSION['step'] == 2):
    ?>
        <!-- OTP Verification Form -->
        <h1>OTP Verification</h1>
        <form id="otp-form" method="POST" action="index.php">
                <div class="form-group">
                    <label for="otp">Enter OTP</label>
                    <input type="text" id="otp" name="otp" required>
                </div>
                <div class="form-buttons">
                    <button type="submit" name="verify_otp" class="register-btn">VERIFY OTP</button>
                </div>
            </form>

    <?php elseif (isset($_SESSION['step']) && $_SESSION['step'] == 3): ?>
        <!-- Password Setup Form -->
        <h1>Set Password</h1>
        <form id="password-form" method="POST" action="#">
                <div class="form-group">
                    <label for="password">Set Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <div class="form-group">
                    <label for="confirm-password">Confirm Password</label>
                    <input type="password" id="confirm-password" name="confirm_password" required>
                </div>
                <div class="form-buttons">
                    <button type="submit" name="set_password" class="register-btn">SET PASSWORD</button>
                </div>
            </form>
            <div id="validation-messages">
                <p class="validation" id="length">At least 8 characters long</p>
                <p class="validation" id="uppercase">At least one uppercase letter</p>
                <p class="validation" id="number">At least one number</p>
                <p class="validation" id="special">At least one special character (#, /, _, @)</p>
            </div>

    <?php else: ?>
        <!-- Registration Form -->  
        <h1>SIGN UP</h1>
        <form id="signup-form" method="POST" action="#">
                <div class="form-group">
                    <label for="name">Name</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <div class="form-group">
                    <label for="dob">DOB</label>
                    <select id="day" name="day" required>
                        <option value="" disabled selected>DD</option>
                        <!-- Generate options from 1 to 31 -->
                        <?php
                            for($i=0;$i<31;$i++){
                                echo "<option>".$i."</option>";
                            }
                        ?>
                    </select>
                    <select id="month" name="month" required>
                        <option value="" disabled selected>MM</option>
                        <option value="1">January</option>
                        <option value="2">February</option>
                        <option value="3">March</option>
                        <option value="4">April</option>
                        <option value="5">May</option>
                        <option value="6">June</option>
                        <option value="7">July</option>
                        <option value="8">August</option>
                        <option value="9">September</option>
                        <option value="10">October</option>
                        <option value="11">November</option>
                        <option value="12">December</option>
                    </select>
                    <select id="year" name="year" required>
                        <option value="" disabled selected>YYYY</option>
                        <!-- Generate options from 1920 to the current year -->
                        <?php

                            for($i=1920;$i<2024;$i++){
                                echo "<option>".$i."</option>";
                            }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="country">Country</label>
                    <select id="country" name="country" required>
                        <option value="" disabled selected></option>
                        <!-- Populate with all countries -->
                        <option value="India">India</option>
                        <option value="United States">United States</option>
                        <option value="United Kingdom">United Kingdom</option>
                        <option value="Australia">Australia</option>
                        <!-- Add more countries here -->
                    </select>
                </div>
                
                <div class="form-buttons">
                    <button type="reset" class="cancel-btn">CANCEL</button>
                    <button type="submit" name="register" class="register-btn">REGISTER</button>
                </div>
                <div class="form-footer" style="text-align: center;">
                <p>Already have an account? <a href="../login/index.php">Login</a></p>
            </div>
            </form>
    <?php endif; ?>
</div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const passwordInput = document.getElementById('password');
    const validationMessages = document.querySelectorAll('.validation');
    const resetButton = document.getElementById('reset-button');

    function validatePassword() {
        const password = passwordInput.value;
        let allValid = true;

        // Reset all validation messages
        validationMessages.forEach(msg => {
            msg.classList.remove('valid');
            if (!msg.classList.contains('valid')) allValid = false;
        });

        // Validate length
        if (password.length >= 8) {
            document.getElementById('length').classList.add('valid');
        } else {
            allValid = false;
        }

        // Validate uppercase letter
        if (/[A-Z]/.test(password)) {
            document.getElementById('uppercase').classList.add('valid');
        } else {
            allValid = false;
        }

        // Validate number
        if (/[0-9]/.test(password)) {
            document.getElementById('number').classList.add('valid');
        } else {
            allValid = false;
        }

        // Validate special character
        if (/[#/_@]/.test(password)) {
            document.getElementById('special').classList.add('valid');
        } else {
            allValid = false;
        }

        // Enable or disable the reset button
        resetButton.disabled = !allValid;
    }

    passwordInput.addEventListener('input', validatePassword);
});
</script>
</body>
</html>

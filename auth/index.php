<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="refresh" content="0;url=signup/">
    <title>Teacher Signup</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f9f9f9;
            margin: 0; padding: 0;
        }
        .container {
            max-width: 400px; margin: auto; padding: 20px;
            background: white; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 { text-align: center; color: #333; }
        input, button {
            width: 100%; padding: 10px; margin-top: 10px; border: 1px solid #ddd;
            border-radius: 5px; box-sizing: border-box;
        }
        button {
            background-color: #007BFF; color: white; border: none; cursor: pointer;
        }
        button:hover { background-color: #0056b3; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Teacher Signup</h1>
        <form action="send_otp.php" method="POST">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Signup</button>
        </form>
    </div>
</body>
</html>

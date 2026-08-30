<?php
session_start();
include 'db.php';

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Check if fields are empty
    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields";
    }
    // Password validation min 3 max 6
    elseif (strlen($password) < 3 || strlen($password) > 6) {
        $error = "Password must be between 3 and 6 characters";
    }
    else {
        $query = "SELECT * FROM Users WHERE Email = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && $user['Password'] === $password) {
            $_SESSION['user_id'] = $user['UserID'];
            $_SESSION['role'] = $user['Role'];
            session_regenerate_id(true);

            if (!headers_sent()) {
                $base = "./"; 

                if ($user['Role'] == 'Donor') {
                    header("Location: " . $base . "donor/dashboard.php");
                } 
                elseif ($user['Role'] == 'Receiver') {
                    header("Location: " . $base . "reciever/dashboard.php");
                } 
                elseif ($user['Role'] == 'Agent') {
                    header("Location: " . $base . "agent/dashboard.php");
                } 
                elseif ($user['Role'] == 'Handler') {
                    header("Location: " . $base . "admin/dashboard.php");
                }
                exit();
            } else {
                $error = "Login successful but redirect failed. Clear browser cache.";
            }
        } else {
             $error = "Invalid email or password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>CharityHub - Login</title>

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    height: 100vh;
    display: flex;
    justify-content: flex-start;
    align-items: center;
    padding-left: 8vw;
    background: url('assets/images/image.jpg') no-repeat center center/cover;
    position: relative;
}

body::before {
    content: "";
    position: absolute;
    width: 100%;
    height: 100%;
    );
    top: 0;
    left: 0;
    z-index: 0;
}

.login-box {
    position: relative;
    z-index: 1;
    width: 340px;
    min-height: 520px;
    padding: 30px 25px;
    display: flex;
    flex-direction: column;
    justify-content: center;
    gap: 30px;
    background: rgba(27, 67, 50, 0.4);
    backdrop-filter: blur(20px);
    border-radius: 28px;
    text-align: center;
    box-shadow: 
        0 40px 100px rgba(0, 0, 0, 0.6),
        0 0 40px rgba(27, 67, 50, 0.3);
    border: 1px solid rgba(27, 67, 50, 0.6);
}

h2 {
    margin: 0 0 8px 0;
    font-size: 24px;
    font-weight: 700;
    color: #D8F3DC;
    letter-spacing: 1.2px;
    text-shadow: 0 2px 8px rgba(0,0,0,0.4);
}

.subtitle {
    color: rgba(216, 243, 220, 0.95);
    font-size: 13px;
    font-weight: 400;
    margin: 0 0 10px 0;
}

.error {
    color: #e74c3c;
    font-size: 14px;
    font-weight: 500;
    padding: 0;
    margin: 2px 0;
    text-align: center;
    letter-spacing: 0.2px;
    line-height: 0.5; /* Tighter line height */
}

form {
    display: flex;
    flex-direction: column;
    gap: 20px;
}

.form-group {
    position: relative;
}

.label {
    display: block;
    text-align: left;
    font-size: 13px;
    font-weight: 600;
    color: #D8F3DC;
    margin-bottom: 8px;
    letter-spacing: 0.5px;
    text-shadow: 0 1px 3px rgba(0,0,0,0.4);
}

input {
    width: 100%;
    padding: 9px 10px;
    height: 38px;
    border-radius: 13px;
    border: 2px solid rgba(216, 243, 220, 0.4);
    outline: none;
    background: rgba(255, 255, 255, 0.97);
    font-size: 14px;
    font-weight: 500;
    color: #1B4332;
    box-sizing: border-box;
}

input:focus {
    border-color: #D8F3DC;
    box-shadow: 0 0 0 4px rgba(216, 243, 220, 0.3);
    background: rgba(255, 255, 255, 1);
}

.password-box {
    position: relative;
}

.toggle-btn {
    position: absolute;
    right: 14px;
    top: 50%;
    transform: translateY(-50%);
    border: none;
    background: transparent;
    cursor: pointer;
    font-size: 16px;
    color: #1B4332;
}

.btn-login {
    width: 100%;
    padding: 12px;
    border-radius: 11px;
    border: none;
    font-weight: 700;
    cursor: pointer;
    text-transform: uppercase;
    font-size: 12px;
    letter-spacing: 1px;
    background: linear-gradient(135deg, #1B4332 0%, #2D5A47 100%);
    color: white;
    box-shadow: 0 8px 25px rgba(27, 67, 50, 0.5);
    border: 1px solid rgba(216, 243, 220, 0.4);
    margin-top: 9px;
}

.btn-login:hover {
    transform: translateY(-3px);
    box-shadow: 0 0 30px rgba(216, 243, 220, 0.6);
}

.register-link a {
    color: #D8F3DC;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.3s ease;
}

.register-link a:hover {
    color: #ffffff;
    text-shadow: 0 0 10px rgba(216, 243, 220, 0.9),
                  0 0 18px rgba(216, 243, 220, 0.7);
}

.register-link {
    margin-top: 10px;
    color: rgba(216, 243, 220, 0.95);
    font-size: 11px;
}
</style>
</head>

<body>

<div class="login-box">

    <div class="welcome-text">
        <h2>Welcome Back</h2>
        <p class="subtitle">Please sign in to your charity dashboard</p>
    </div>

    <?php if ($error !== ""): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST">

        <div class="form-group">
            <label class="label">Email Address</label>
            <input type="email" name="email" placeholder="Enter your email address" required>
        </div>

        <div class="form-group">
            <label class="label">Password</label>
            <div class="password-box">
                <input type="password" id="password" name="password" placeholder="Enter your password" required>
                <button type="button" class="toggle-btn" onclick="togglePassword()">
                    <i id="eyeIcon" class="fas fa-eye"></i>
                </button>
            </div>
        </div>

        <button type="submit" class="btn-login">Sign In to Dashboard</button>

    </form>

    <div class="register-link">
        Don't have an account? <a href="register.php">Create one now</a>
    </div>

</div>

<script>
function togglePassword() {
    const passInput = document.getElementById("password");
    const eyeIcon = document.getElementById("eyeIcon");

    if (passInput.type === "password") {
        passInput.type = "text";
        eyeIcon.classList.replace("fa-eye-slash", "fa-eye");
    } else {
        passInput.type = "password";
        eyeIcon.classList.replace("fa-eye", "fa-eye-slash");
    }
}
</script>

</body>
</html>
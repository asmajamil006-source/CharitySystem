<?php
include 'db.php';

$locations = [
    "Gulshan-e-Iqbal",
    "North Nazimabad",
    "Nazimabad",
    "DHA",
    "Clifton",
    "Bahadurabad",
    "PECHS",
    "Johar",
    "Malir",
    "Saddar",
    "Korangi",
    "Landhi",
    "Orangi Town",
    "Lyari",
    "Federal B Area",
    "Buffer Zone",
    "Scheme 33",
    "Gulistan-e-Johar",
    "Surjani Town",
    "Shah Faisal Colony"
];

$success = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $type = $_POST['type'] ?? '';
    $cnic = trim($_POST['cnic'] ?? '');

$area = trim($_POST['area'] ?? '');
$block = trim($_POST['block'] ?? '');
$street = trim($_POST['street'] ?? '');
$landmark = trim($_POST['landmark'] ?? '');

    if (empty($name) || empty($email) || empty($password) || empty($role) || empty($type) || empty($cnic)) {
        $error = "All fields are required";
    }
    // Role validation
    elseif (!in_array($role, ['Donor', 'Receiver'])) {
        $error = "Invalid role selected";
    }
    // Password validation
    elseif (strlen($password) < 3 || strlen($password) > 6) {
        $error = "Password must be between 3 and 6 characters";
    }
    // 🔥 FIXED: Duplicate check ONLY runs if all validations pass
    else {
        try {
            $check = $conn->prepare("SELECT COUNT(*) FROM Users WHERE Email = ? OR CNIC = ?");
            $check->execute([$email, $cnic]);
            $count = $check->fetchColumn();

            if ($count > 0) {
                $error = "Email or CNIC already registered";
            } else {
                // Insert user
                $address = $area . ", " . $block . ", " . $street;

                if (!empty($landmark)) {
                $address .= ", Near " . $landmark;
                }

$query = "INSERT INTO Users
(Name, Email, Password, Role, Type, CNIC, Address)
VALUES (?, ?, ?, ?, ?, ?, ?)";
                $stmt = $conn->prepare($query);
                $stmt->execute([$name,$email,$password,$role,$type,$cnic,$address]);
                $success = "Account created! <a href='login.php'>Sign in now</a>";
            }
        } catch (PDOException $e) {
            $error = "Registration failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
<title>CharityHub - Register</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
<style>
* { box-sizing: border-box; }

body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
    background: linear-gradient(135deg,
        rgba(27, 67, 50, 0.95) 0%,
        rgba(45, 90, 71, 0.95) 50%,
        rgba(27, 67, 50, 0.95) 100%);
}

.register-box {
    width: 340px;
    padding: 23px 21px;
    background: rgba(27, 67, 50, 0.4);
    backdrop-filter: blur(20px);
    border-radius: 24px;
    box-shadow: 0 40px 100px rgba(0, 0, 0, 0.6),
                0 0 40px rgba(27, 67, 50, 0.3);
    border: 1px solid rgba(216, 243, 220, 0.5); 
}
h2 {
    text-align: center;
    color: #D8F3DC;
    margin-bottom: 10px;
    font-size: 20px;
}

.message-box {
    height: 24px; /* Fixed height = 1 line space */
    margin-bottom: 12px;
}

.success, .error {
    padding: 10px;
    border-radius: 10px;
    text-align: center;
}

.success { 
    color: #a5d6a7; 
    background: rgba(165, 214, 167, 0.25); 
}
.error {
    color: #e74c3c;
    font-size: 14px;
    font-weight: 500;
    padding: 0;
    margin: 0;
    text-align: center;
    letter-spacing: 0.2px;
    line-height: 1.2;
    height: 100%; /* Fills the reserved space */
    display: flex;
    align-items: center;
    justify-content: center;
}

form {
    display: flex;
    flex-direction: column;
    gap: 4px;
}

.label {
    color: #fff;
    font-size: 12px;
    font-weight: 600;
    margin-bottom: 2px;
}

.input-box {
    position: relative;
    display: flex;
    flex-direction: column;
}

input, select {
    width: 100%;
    height: 34px;
    padding: 0 42px 0 46px;
    line-height: 32px;
    border-radius: 11px;
    border: 2px solid rgba(216, 243, 220, 0.4);
    background: #fff;
    outline: none;
    font-size: 14px;
    display: flex;
    align-items: center;
}

input:-webkit-autofill,
input:-webkit-autofill:hover,
input:-webkit-autofill:focus {
    -webkit-box-shadow: 0 0 0px 1000px white inset !important;
    -webkit-text-fill-color: #000 !important;
}

select {
    appearance: none;
    -webkit-appearance: none;
    -moz-appearance: none;
    cursor: pointer;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%231B4332' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3e%3cpolyline points='6 9 12 15 18 9'%3e%3c/polyline%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 12px center;
    background-size: 14px;
}

.icon {
    position: absolute;
    left: 14px;
    bottom: 9px;
    pointer-events: none;
    z-index: 2;
    font-size: 16px;
}

.toggle-eye {
    position: absolute;
    right: 11px;
    top: 66%;
    transform: translateY(-50%);
    cursor: pointer;
    color: #1B4332;
}

button {
    width: 100%;
    padding: 10px;
    border-radius: 10px;
    border: none;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: uppercase;
    font-size: 13px;
    letter-spacing: 1px;
    background: linear-gradient(135deg, #1B4332 0%, #2D5A47 100%);
    color: white;
    box-shadow: 0 8px 25px rgba(27, 67, 50, 0.5);
    border: 1px solid rgba(216, 243, 220, 0.4);
    margin-top: 5px;
}

button:hover {
    transform: translateY(-3px);
    box-shadow: 0 0 30px rgba(216, 243, 220, 0.6);
}

.login-link {
    text-align: center;
    margin-top: 14px;
    font-size: 13px;
    color: #ffffff;
}
.login-link a {
    color: #D8F3DC;
    font-weight: 700;
    text-decoration: none;
    transition: all 0.3s ease;
}
.login-link a:hover {
    color: #ffffff;
    text-shadow: 0 0 8px rgba(216, 243, 220, 0.9),
                 0 0 16px rgba(216, 243, 220, 0.7);
}
</style>
</head>

<body>
<div class="register-box">
    <h2>Create Account</h2>

    <!-- 🔥 FIXED: Proper message display logic -->
    <div class="message-box">
        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php elseif ($success): ?>
            <div class="success"><?= $success ?></div>
        <?php endif; ?>
    </div>

    <!-- 🔥 FIXED: Show form ONLY if no success -->
    <?php if (!$success): ?>
        <form method="POST">
            <div class="input-box">
                <label class="label">Full Name</label>
                <span class="icon">👤</span>
                <input type="text" name="name" placeholder="Enter your full name" 
                       value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required>
            </div>

            <div class="input-box">
                <label class="label">Email</label>
                <span class="icon">📧</span>
                <input type="email" name="email" placeholder="Enter your email address" 
                       value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
            </div>

            <div class="input-box">
                <label class="label">Password</label>
                <span class="icon">🔒</span>
                <input type="password" id="password" name="password" 
                       placeholder="Enter your password" minlength="3" maxlength="6" required>
                <i class="fa-solid fa-eye toggle-eye" id="eye" onclick="togglePassword()"></i>
            </div>

            <div class="input-box">
                <label class="label">Role</label>
                <span class="icon">👥</span>
                <select name="role" required>
                    <option value="">Select your role</option>
                    <option value="Donor" <?= ($_POST['role'] ?? '') === 'Donor' ? 'selected' : '' ?>>Donor</option>
                    <option value="Receiver" <?= ($_POST['role'] ?? '') === 'Receiver' ? 'selected' : '' ?>>Receiver</option>
                </select>
            </div>

            <div class="input-box">
                <label class="label">Type</label>
                <span class="icon">🏷️</span>
                <select name="type" required>
                    <option value="">Select type</option>
                    <option value="Individual" <?= ($_POST['type'] ?? '') === 'Individual' ? 'selected' : '' ?>>Individual</option>
                    <option value="Organization" <?= ($_POST['type'] ?? '') === 'Organization' ? 'selected' : '' ?>>Organization</option>
                </select>
            </div>
<div class="input-box">
    <label class="label">Area</label>

    <select name="area" required>
        <option value="">Select Area</option>

        <?php foreach($locations as $loc){ ?>
            <option value="<?php echo $loc; ?>">
                <?php echo $loc; ?>
            </option>
        <?php } ?>
    </select>
</div>

<div class="input-box">
    <label class="label">Block / Sector</label>

    <input type="text"
           name="block"
           placeholder="Enter Block / Sector"
           required>
</div>

<div class="input-box">
    <label class="label">Street / House</label>

    <input type="text"
           name="street"
           placeholder="Enter Street / House No"
           required>
</div>

<div class="input-box">
    <label class="label">Landmark (Optional)</label>

    <input type="text"
           name="landmark"
           placeholder="Nearby famous place">
</div>
            <div class="input-box">
                <label class="label">CNIC</label>
                <span class="icon">🪪</span>
                <input type="text" name="cnic" placeholder="Enter your CNIC (e.g. 12345-1234567-1)" 
                       value="<?= htmlspecialchars($_POST['cnic'] ?? '') ?>" required>
            </div>

            <button type="submit">Register</button>
        </form>
    <?php endif; ?>

    <div class="login-link">
        Have account? <a href="login.php">Sign in</a>
    </div>
</div>

<script>
function togglePassword() {
    const pass = document.getElementById("password");
    const eye = document.getElementById("eye");
    if (pass.type === "password") {
        pass.type = "text";
        eye.classList.remove("fa-eye");
        eye.classList.add("fa-eye-slash");
    } else {
        pass.type = "password";
        eye.classList.remove("fa-eye-slash");
        eye.classList.add("fa-eye");
    }
}
</script>
</body>
</html>
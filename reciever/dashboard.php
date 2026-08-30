<?php
session_start();
include '../db.php';

/* KARACHI AREAS */
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

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$receiverID = $_SESSION['user_id'];

// Clear old messages on page load
if (!isset($_SESSION['messages'])) {
    $_SESSION['messages'] = [];
}

// UPDATE ADDRESS - SAVE TO USER PROFILE
if (isset($_POST['action']) && $_POST['action'] == 'update_address') {
    $area = trim($_POST['area']);
    $block = trim($_POST['block']);
    $street = trim($_POST['street']);
    $landmark = trim($_POST['landmark']);

    $address = $area . ", " . $block . ", " . $street;
    if (!empty($landmark)) {
        $address .= ", Near " . $landmark;
    }

    $update = $conn->prepare("UPDATE Users SET Address = ? WHERE UserID = ?");
    $update->execute([$address, $receiverID]);

    $_SESSION['success'] = "Address Updated Successfully";
    header("Location: receiver.php");
    exit();
}

// HANDLE REQUEST
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['action'])) {
    $donationID = (int)$_POST['donation_id'];
    $qty = (int)$_POST['req_qty'];
    
    // Get current user address
    $userAddrQuery = $conn->prepare("SELECT Address FROM Users WHERE UserID = ?");
    $userAddrQuery->execute([$receiverID]);
    $userAddr = $userAddrQuery->fetchColumn();
    
    if (empty($userAddr)) {
        $_SESSION['messages'][$donationID] = "⚠ Please update your address first";
    } else {
        // Check available quantity AT THE MOMENT OF REQUEST
        $q1 = "SELECT Quantity FROM Donations WHERE DonationID = ?";
        $stmt1 = $conn->prepare($q1);
        $stmt1->execute([$donationID]);
        $don = $stmt1->fetch();

        $q2 = "SELECT ISNULL(SUM(RequestedQty),0) as total FROM Requests WHERE DonationID = ? AND Status != 'Rejected'";
        $stmt2 = $conn->prepare($q2);
        $stmt2->execute([$donationID]);
        $req = $stmt2->fetch();

        $available = $don['Quantity'] - $req['total'];

        if ($qty > 0 && $qty <= $available) {
            $insert = "INSERT INTO Requests (DonationID, ReceiverID, RequestedQty, Address, Status) VALUES (?, ?, ?, ?, 'Pending')";
            $stmt3 = $conn->prepare($insert);
            $stmt3->execute([$donationID, $receiverID, $qty, $userAddr]);
            $_SESSION['messages'][$donationID] = "✔ Request Sent Successfully";
        } else {
            $_SESSION['messages'][$donationID] = "❌ Invalid quantity or out of stock";
        }
    }
    header("Location: receiver.php?" . http_build_query($_GET));
    exit();
}

// GET CURRENT USER ADDRESS
$currentAddressQuery = $conn->prepare("SELECT Address FROM Users WHERE UserID = ?");
$currentAddressQuery->execute([$receiverID]);
$currentAddress = $currentAddressQuery->fetchColumn();

// NOTIFICATIONS - SHOW PENDING REQUESTS
$notifQuery = "SELECT TOP 5 RequestedQty, DonationID, Status FROM Requests WHERE ReceiverID = ? AND Status = 'Pending' ORDER BY RequestID DESC";
$stmtN = $conn->prepare($notifQuery);
$stmtN->execute([$receiverID]);
$notifications = $stmtN->fetchAll();
$count = count($notifications);

// FILTER
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

$query = "
SELECT 
    d.DonationID, d.ItemName, d.Category, d.Quantity, d.ExpiryDate,
    ISNULL(SUM(CASE WHEN r.Status != 'Rejected' THEN r.RequestedQty ELSE 0 END),0) as RequestedTotal
FROM Donations d
LEFT JOIN Requests r ON d.DonationID = r.DonationID
WHERE 1=1
";

$params = [];
if ($search != '') {
    $query .= " AND d.ItemName LIKE ?";
    $params[] = "%$search%";
}
if ($category != '') {
    $query .= " AND d.Category = ?";
    $params[] = $category;
}

$query .= "
GROUP BY 
    d.DonationID, d.ItemName, d.Category, d.Quantity, d.ExpiryDate
HAVING (d.Quantity - ISNULL(SUM(CASE WHEN r.Status != 'Rejected' THEN r.RequestedQty ELSE 0 END),0)) > 0
ORDER BY 
    d.Category,
    CASE WHEN (d.Quantity - ISNULL(SUM(CASE WHEN r.Status != 'Rejected' THEN r.RequestedQty ELSE 0 END),0)) = 0 THEN 1 ELSE 0 END,
    d.ItemName
";

$stmt = $conn->prepare($query);
$stmt->execute($params);
?>


<!DOCTYPE html>
<html>
<head>
<title>Receiver Panel</title>

<style>
/* ===== YOUR EXACT CSS - NO CHANGES ===== */
body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    min-height: 100vh;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 50%, #f1f5f9 100%);
}

.header {
    text-align: center;
    font-size: 26px;
    font-weight: 800;
    color: #D8F3DC;
    position: relative;
    padding: 16px;
    background: rgba(27, 67, 50, 0.95); 
    backdrop-filter: blur(20px);
    border-bottom: 2px solid rgba(216, 243, 220, 0.5);
}

.logo {
    position: absolute;
    left: 20px;
    top: 50%;
    transform: translateY(-50%); 
    display: flex;
    align-items: center;
    gap: 12px;
}

.logo-icon {
    width: 52px;
    height: 52px;
    border-radius: 50%;
    background: #fff url("../assets/images/image2.jpg") center / 44px 44px no-repeat;
    border: 2px solid rgba(255, 255, 255, 0.9);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    image-rendering: -webkit-optimize-contrast;
    image-rendering: crisp-edges;
}

.logo-text {
    font-size: 26px;
    font-weight: 800;
    letter-spacing: 0.5px;
}

.share { color: #ffffff; }
.hub { color: #95d5b2; }

.bell {
    position: absolute;
    right: 20px;
    top: 18px;
    cursor: pointer;
    font-size: 20px;
}
.badge {
    background: #ff6b6b;
    border-radius: 50%;
    padding: 3px 7px;
    font-size: 12px;
    position: absolute;
    top: -8px;
    right: -10px;
}

.dropdown {
    display: none;
    position: absolute;
    right: 20px;
    top: 60px;
    background: rgba(27, 67, 50, 0.98);
    backdrop-filter: blur(10px);
    width: 250px;
    border-radius: 14px;
    padding: 10px;
    border: 1px solid rgba(216, 243, 220, 0.3);
}
.dropdown div {
    color: #D8F3DC;
    padding: 8px;
    border-bottom: 1px solid rgba(216,243,220,0.1);
    font-size: 13px;
}

.sub {
    text-align: center;
    color: #1B4332;
    font-size: 17px;
    margin: 8px 0 20px;
    font-weight: 700;
}

.filter-bar {
    display: flex;
    justify-content: center;
    margin-bottom: 25px;
}
.filter-bar form {
    display: flex;
    gap: 20px;
    padding: 15px;
    border-radius: 18px;
    background: rgba(27, 67, 50, 0.9);
    backdrop-filter: blur(20px);
    border: 1px solid rgba(216, 243, 220, 0.5);
    box-shadow: 0 40px 100px rgba(0,0,0,0.2);
}
.filter-bar input, .filter-bar select {
    width: 150px;
    padding: 10px;
    border-radius: 12px;
    border: 2px solid rgba(216, 243, 220, 0.4);
    outline: none;
    font-size: 13px;
    background: #fff;
}
.filter-bar button {
    padding: 12px 16px;
    border-radius: 14px;
    border: none;
    cursor: pointer;
    font-weight: 700;
    color: #fff;
    background: linear-gradient(135deg, #1B4332 0%, #2D5A47 100%);
    box-shadow: 0 8px 25px rgba(27, 67, 50, 0.5);
}
.filter-bar button:hover {
    transform: translateY(-3px);
    box-shadow: 0 0 30px rgba(216, 243, 220, 0.6);
}

.category-title {
    text-align: center;
    font-size: 18px;
    font-weight: 800;
    color: #1B4332;
    margin: 8px 0 2px;
}

.container {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    padding: 2px 10px;
}

.card {
    width: 290px;
    margin: 10px;
    padding: 18px;
    background: rgba(27, 67, 50, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 28px;
    border: 1px solid rgba(216, 243, 220, 0.5);
    box-shadow: 0 20px 50px rgba(0,0,0,0.15);
    color: #D8F3DC;
    transition: 0.3s ease;
}
.card:hover { transform: translateY(-5px); }

.card-header {
    text-align: center;
    font-size: 16px;
    font-weight: 700;
    padding: 10px;
    border-radius: 12px;
    background: rgba(216, 243, 220, 0.15);
    border: 1px solid rgba(216, 243, 220, 0.4);
    margin-bottom: 12px;
    color: #fff;
    transition: all 0.3s ease;
    cursor: pointer;
}
.card-header:hover {
    background: rgba(216, 243, 220, 0.25);
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

.card b { color: #fff; }

.progress {
    height: 6px;
    background: rgba(255,255,255,0.2);
    border-radius: 5px;
    margin-top: 10px;
}
.progress-bar {
    height: 6px;
    background: #a5d6a7;
}

/* ========== BUTTON STYLES ========== */
.address-toggle, .request-btn {
    width: 100%;
    padding: 12px;
    border-radius: 14px;
    border: 1px solid rgba(255,255,255,0.2);
    color: #D8F3DC;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    font-size: 13px;
    margin-top: 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    background: rgba(255,255,255,0.08);
}
.address-toggle:hover, .request-btn:hover {
    background: rgba(255,255,255,0.15);
    border-color: #ffffff;
    transform: translateY(-2px);
    box-shadow: 0 6px 15px rgba(255,255,255,0.1);
}

.address-form, .request-form {
    margin-top: 15px;
}
.address-form input, .address-form select, .request-form input, .request-form select {
    width: 100%;
    height: 38px;
    padding: 0 12px;
    margin: 5px 0;
    border-radius: 10px;
    border: 2px solid rgba(216,243,220,0.4);
    background: #fff;
    font-size: 13px;
    outline: none;
    box-sizing: border-box;
}
.address-form button, .request-form button {
    width: 100%;
    padding: 12px;
    border-radius: 12px;
    background: linear-gradient(135deg, #1B4332 0%, #2D5A47 100%);
    border: none;
    color: #fff;
    font-weight: 700;
    cursor: pointer;
    margin-top: 5px;
    transition: all 0.3s ease;
}
.address-form button:hover, .request-form button:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(27,67,50,0.4);
}

.current-address {
    background: rgba(255,255,255,0.08);
    padding: 12px;
    border-radius: 12px;
    border: 1px solid rgba(255,255,255,0.2);
    margin-top: 10px;
    font-size: 13px;
}

.out-of-stock {
    color: #ff6b6b !important;
    font-weight: bold;
    padding: 12px;
    border-radius: 12px;
    background: rgba(255,107,107,0.1);
    border: 1px solid rgba(255,107,107,0.3);
    margin-top: 55px;
    text-align: center;
}

button:active {
    transform: translateY(1px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}
</style>
</head>

<body>

<!-- HEADER WITH LOGO + NOTIFICATION BELL -->
<div class="header">
    <div class="logo">
        <div class="logo-icon"></div>
        <div class="logo-text">
            <span class="share">Share</span><span class="hub">Hub</span>
        </div>
    </div>
    
    Receiver Panel
    
    <div class="bell" onclick="toggleNotif()">
        🔔<?php if ($count > 0) { ?><span class="badge"><?php echo $count; ?></span><?php } ?>
    </div>
    <div id="notifBox" class="dropdown">
        <?php
        if ($count == 0) echo "<div>No notifications</div>";
        else foreach ($notifications as $n) echo "<div>Requested ".$n['RequestedQty']." items</div>";
        ?>
    </div>
</div>

<div class="sub">Explore Available Donations</div>

<!-- FILTER BAR -->
<div class="filter-bar">
<form method="GET">
    <input type="text" name="search" placeholder="Search item..." value="<?php echo htmlspecialchars($search); ?>">
    <select name="category">
        <option value="">All Categories</option>
        <option value="Food" <?= $category=='Food'?'selected':'' ?>>Food</option>
        <option value="Apparel" <?= $category=='Apparel'?'selected':'' ?>>Apparel</option>
        <option value="Entertainment" <?= $category=='Entertainment'?'selected':'' ?>>Entertainment</option>
        <option value="Other">Other</option>
    </select>
    <button type="submit">Filter</button>
</form>
</div>

<?php
$currentCategory = "";
echo "<div class='container'>";

while ($row = $stmt->fetch()) {
    $available = max(0, (int)$row['Quantity'] - (int)$row['RequestedTotal']);
    $cleanCategory = strtolower($row['Category']);

    if ($currentCategory !== $cleanCategory) {
        $currentCategory = $cleanCategory;
        echo "</div><div class='category-title'>".strtoupper($cleanCategory)."</div><div class='container'>";
    }

    echo "<div class='card'>";
    
    if (isset($messages[$row['DonationID']])) {
        echo "<div style='color:#a5d6a7;font-weight:bold;margin-bottom:10px;text-align:center;'>".$messages[$row['DonationID']]."</div>";
    }

    echo "<div class='card-header'>".htmlspecialchars($row['ItemName'])."</div>";

    echo "<b>Total Stock:</b> ".$row['Quantity']."<br>";
    echo "<b>Claimed:</b> ".$row['RequestedTotal']."<br>";
    echo "<b>Available:</b> <span style='color:#fff; font-weight:800;'>".$available."</span><br>";

    $percent = $row['Quantity'] > 0 ? min(100, ($row['RequestedTotal'] / $row['Quantity']) * 100) : 0;
    echo "<div class='progress'><div class='progress-bar' style='width:".$percent."%'></div></div>";

    // ========== CURRENT ADDRESS DISPLAY ==========
    if (!empty($currentAddress)) {
        echo "<div class='current-address'>📍 Current: ".htmlspecialchars($currentAddress)."</div>";
    }

    // ========== UPDATE ADDRESS BUTTON (ONLY IF STOCK AVAILABLE) ==========
    if ($available > 0) {
        echo "<button type='button' class='address-toggle' onclick='toggleAddress(this)'>✏️ Update Address</button>";
        echo "<div class='address-form' style='display:none;'>";
        echo "<form method='POST'>";
        echo "<input type='hidden' name='action' value='update_address'>";
        echo "<select name='area' required><option value=''>Select Area</option>";
        foreach($locations as $loc) echo "<option value='$loc'>$loc</option>";
        echo "</select>";
        echo "<input type='text' name='block' placeholder='Block / Sector' required>";
        echo "<input type='text' name='street' placeholder='Street / House No' required>";
        echo "<input type='text' name='landmark' placeholder='Nearby Landmark (Optional)'>";
        echo "<button type='submit' class='address-toggle'>💾 Save Address</button>";
        echo "</form>";
        echo "</div>";
    }

    // ========== REQUEST BUTTON (ONLY IF STOCK AVAILABLE) ==========
    if ($available > 0) {
        echo "<button type='button' class='request-btn' onclick='toggleRequest(this)'>📦 Request Item</button>";
        echo "<div class='request-form' style='display:none;'>";
        echo "<form method='POST'>";
        echo "<input type='hidden' name='donation_id' value='".$row['DonationID']."'>";
        echo "<input type='number' name='req_qty' min='1' max='$available' placeholder='Quantity (Max: $available)' required>";
        echo "<button type='submit'>✅ Send Request</button>";
        echo "</form>";
        echo "</div>";
    } else {
        echo "<div class='out-of-stock'>Out of Stock</div>";
    }
    
    echo "</div>";
}
echo "</div>";
?>

<script>
function toggleNotif() {
    let box = document.getElementById("notifBox");
    box.style.display = box.style.display === "block" ? "none" : "block";
}

function toggleAddress(button) {
    const form = button.nextElementSibling;
    if (form.style.display === 'none' || form.style.display === '') {
        form.style.display = 'block';
        button.textContent = '✕ Hide Update';
        button.style.background = 'rgba(255,107,107,0.2)';
    } else {
        form.style.display = 'none';
        button.textContent = '✏️ Update Address';
        button.style.background = 'rgba(255,255,255,0.08)';
    }
}



function toggleRequest(button) {
    const form = button.nextElementSibling;
    if (form.style.display === 'none' || form.style.display === '') {
        form.style.display = 'block';
        button.textContent = '✕ Hide Request';
        button.style.background = 'rgba(255,107,107,0.2)';
    } else {
        form.style.display = 'none';
        button.textContent = '📦 Request Item';
        button.style.background = 'rgba(255,255,255,0.08)';
    }
}
</script>

</body>
</html>
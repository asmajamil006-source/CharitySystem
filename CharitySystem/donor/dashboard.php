<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

$donorID = $_SESSION['user_id'];

/* DELETE */
if (isset($_POST['action']) && $_POST['action'] == 'delete') {
    $delID = $_POST['delete_id'];
    $conn->prepare("DELETE FROM Donations WHERE DonationID = ? AND DonorID=?")
         ->execute([$delID, $donorID]);
}

/* ADD DONATION */
if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($_POST['action'])) {
    $item = $_POST['item'];
    $category = $_POST['category'];
    $quantity = $_POST['quantity'];

    if ($category == "Other") {
        $category = $_POST['other_category'];
    }

    $expiry = null;
    if ($category == "Food" || $category == "Other") {
        $expiry = $_POST['expiry'];
    }

    $stmt = $conn->prepare("
        INSERT INTO Donations (DonorID, ItemName, Category, Quantity, ExpiryDate)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->execute([$donorID, $item, $category, $quantity, $expiry]);

    header("Location: dashboard.php");
    exit();
}

/* NOTIFICATIONS */
$notif = $conn->prepare("
SELECT TOP 5 d.ItemName, r.Status
FROM Requests r
JOIN Donations d ON r.DonationID = d.DonationID
WHERE d.DonorID = ?
ORDER BY r.RequestID DESC
");
$notif->execute([$donorID]);
$notifications = $notif->fetchAll();
$count = count($notifications);
?>

<!DOCTYPE html>
<html>
<head>
<title>Donor Dashboard</title>

<style>
/* ===== GLOBAL THEME (EXACT MATCH ADMIN) ===== */
body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    min-height: 100vh;
    display: flex;
    flex-direction: column;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 50%, #f1f5f9 100%);
}

/* ===== HEADER (MATCHING ADMIN & REGISTER) ===== */
.ribbon {
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
/* LOGO POSITIONING & ALIGNMENT */
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
    /* Same high-res 44x44 logo treatment */
    background: #fff url("../assets/images/image2.jpg") center / 44px 44px no-repeat;
    border: 2px solid rgba(255, 255, 255, 0.9);
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
    
    /* Keeps the logo edges sharp */
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
.sub {
    text-align: center;
    color: #1B4332;
    font-size: 17px;
    margin: 8px 0 20px;
    font-weight: 700;
}

/* Keep Bell & Badge relative to the new ribbon height */
.bell {
    position: absolute;
    right: 25px;
    top: 50%;
    transform: translateY(-50%);
    cursor: pointer;
    font-size: 20px;
}

.badge {
    background: #ff6b6b;
    color: white;
    border-radius: 50%;
    padding: 2px 6px;
    font-size: 10px;
    position: absolute;
    top: -5px;
    right: -8px;
    border: 1px solid white;
}

/* NOTIF DROPDOWN */
.dropdown {
    display: none;
    position: absolute;
    right: 20px;
    top: 65px;
    background: rgba(27, 67, 50, 0.98);
    backdrop-filter: blur(10px);
    width: 260px;
    border-radius: 18px;
    padding: 10px;
    border: 1px solid rgba(216, 243, 220, 0.3);
    box-shadow: 0 20px 50px rgba(0,0,0,0.3);
    z-index: 100;
}
.dropdown div {
    color: #D8F3DC;
    padding: 10px;
    border-bottom: 1px solid rgba(216, 243, 220, 0.1);
    font-size: 13px;
}

.container {
    width: 90%;
    max-width: 800px; /* Limits width to look clean as long cards */
    margin: 20px auto;
}

/* SECTION TITLES */
h3 {
    text-align: center;
    color: #1B4332;
    font-size: 18px;
    margin: 30px 0 15px;
    font-weight: 700;
}

/* THE CARDS (MATCHING ADMIN GREEN GLASS) */
.card {
    background: rgba(27, 67, 50, 0.95);
    backdrop-filter: blur(20px);
    border-radius: 28px;
    padding: 25px;
    margin-bottom: 25px;
    border: 1px solid rgba(216, 243, 220, 0.5);
    box-shadow: 0 20px 50px rgba(0,0,0,0.15);
    color: #D8F3DC;
    transition: 0.3s ease;
    width: 100%; /* Makes them long cards */
    box-sizing: border-box;
}

.card:hover { transform: translateY(-5px); }

/* ITEM HEADER */
.item-header {
    text-align: center;
    font-size: 16px;
    font-weight: 700;
    padding: 12px;
    border-radius: 12px;
    background: rgba(216, 243, 220, 0.15);
    border: 1px solid rgba(216, 243, 220, 0.4);
    margin-bottom: 18px;
    color: #fff;
    /* No transition or hover block needed anymore */
}

.item-header:hover {
    background: rgba(216, 243, 220, 0.25); /* Just a tiny bit brighter */
    /* Removed transform and shadow to keep it clean */
}

/* FORM INPUTS & SELECT FIX */
input, select {
    width: 100%;
    height: 45px;
    padding: 0 15px;
    margin: 10px 0;
    border-radius: 12px;
    border: 2px solid rgba(216, 243, 220, 0.4);
    background: #ffffff;
    font-size: 14px;
    outline: none;
    box-sizing: border-box;
    display: block;
}

/* SPECIFIC FIX FOR THE "V" ARROW POSITION */
select {
    appearance: none; /* Removes the default ugly browser arrow */
    -webkit-appearance: none;
    -moz-appearance: none;
    background-image: url("data:image/svg+xml;charset=UTF-8,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%231B4332' stroke-width='3' stroke-linecap='round' stroke-linejoin='round'%3e%3cpath d='M6 9l6 6 6-6'%3e%3c/path%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 15px center; /* Locks the V position to the right */
    background-size: 14px;
    cursor: pointer;
    padding-right: 40px; /* Space so text doesn't overlap the V */
}

/* Hover effect for the dropdown */
select:hover {
    border-color: rgba(27, 67, 50, 0.5);
    background-color: #fcfcfc;
}

/* BUTTONS */
/* WHITE GHOST STYLE - MATCHES LOGIN PAGE */
button[type="submit"] {
    width: 100%;
    padding: 14px;
    border-radius: 14px;
    background: rgba(255, 255, 255, 0.05); /* Very faint white tint */
    border: 1px solid rgba(255, 255, 255, 0.18);
    color: #ffffff;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.3s ease;
    text-transform: uppercase;
    font-size: 13px;
    margin-top: 10px;
    letter-spacing: 1px;
}

button[type="submit"]:hover {
    background: rgba(255, 255, 255, 0.15); /* Brightens the glass */
    border-color: #ffffff; /* Solid white outline on hover */
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(255, 255, 255, 0.1);
}

/* STATUS COLORS */
.pending { 
    color: #ffe082; 
    background: rgba(255, 243, 205, 0.1);
    padding: 6px 15px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: bold;
    display: inline-block;
    margin: 12px 0;
}

.delivered-box {
    margin-top: 12px;
    padding: 8px 18px;
    border-radius: 20px;
    background: rgba(165, 214, 167, 0.2);
    color: #a5d6a7;
    font-weight: bold;
    font-size: 12px;
    display: inline-block;
    border: 1px solid rgba(165, 214, 167, 0.4);
}

/* DELETE BUTTON BASE STYLE */
.delete-btn {
    background: rgba(255, 107, 107, 0.15) !important;
    border: 1px solid rgba(255, 107, 107, 0.4) !important;
    color: #ffcccb !important;
    box-shadow: none !important;
    width: auto; 
    padding: 10px 22px;
    border-radius: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease !important; /* Ensures smooth animation */
    font-size: 14px;
    letter-spacing: 0.5px;
}

/* DELETE BUTTON HOVER - LIFT AND RED GLOW */
.delete-btn:hover {
    transform: translateY(-3px) !important;
    background: rgba(255, 107, 107, 0.3) !important;
    border-color: #ff6b6b !important;
    color: #fff !important;
    box-shadow: 0 10px 25px rgba(255, 107, 107, 0.25) !important;
}

/* CLICK EFFECT */
.delete-btn:active {
    transform: translateY(1px) !important;
}
</style>

<script>
function toggleNotif() {
    let box = document.getElementById("notifBox");
    box.style.display = box.style.display === "block" ? "none" : "block";
}

function toggleExpiry() {
    let cat = document.getElementById("category").value;
    document.getElementById("expiryField").style.display = (cat === "Food" || cat === "Other") ? "block" : "none";
}

function toggleOther() {
    let cat = document.getElementById("category").value;
    document.getElementById("otherField").style.display = cat === "Other" ? "block" : "none";
}
</script>

</head>

<body>

<div class="ribbon">

    <!-- NEW LOGO SECTION -->
    <div class="logo">
        <div class="logo-icon"></div>
        <div class="logo-text">
            <span class="share">Share</span><span class="hub">Hub</span>
        </div>
    </div>
    
    Donor Panel

    <!-- NOTIFICATION BELL -->
    <div class="bell" onclick="toggleNotif()">
        🔔<?php if ($count > 0) { ?><span class="badge"><?php echo $count; ?></span><?php } ?>
    </div>
    
    <div id="notifBox" class="dropdown">
        <?php
        if ($count == 0) echo "<div>No notifications</div>";
        foreach ($notifications as $n) {
            echo "<div>".$n['ItemName']." → <b>".$n['Status']."</b></div>";
        }
        ?>
    </div>
</div>

<div class="sub">Manage Your Contributions</div>

<div class="container">

    <div class="card">
        <div class="item-header">Add New Donation</div>
        <form method="POST">
            <input type="text" name="item" placeholder="Item Name" required>
            <select name="category" id="category" onchange="toggleOther(); toggleExpiry();" required>
                <option value="Food">Food</option>
                <option value="Apparel">Apparel</option>
                <option value="Entertainment">Entertainment</option>
                <option value="Other">Other</option>
            </select>

            <div id="otherField" style="display:none;">
                <input type="text" name="other_category" placeholder="Specify category">
            </div>

            <input type="number" name="quantity" placeholder="Quantity" required>

            <div id="expiryField">
                <input type="date" name="expiry">
            </div>

            <button type="submit">List Donation</button>
        </form>
    </div>

    <h3>Your Active Donations</h3>

    <?php
    $query = "
    SELECT
        d.DonationID, d.ItemName, d.Category, d.Quantity, d.ExpiryDate,
        COUNT(r.RequestID) as TotalRequests,
        SUM(CASE WHEN r.Status = 'Delivered' THEN 1 ELSE 0 END) as DeliveredCount
    FROM Donations d
    LEFT JOIN Requests r ON d.DonationID = r.DonationID
    WHERE d.DonorID = ?
    GROUP BY d.DonationID, d.ItemName, d.Category, d.Quantity, d.ExpiryDate
    ORDER BY d.DonationID DESC";

    $stmt = $conn->prepare($query);
    $stmt->execute([$donorID]);

    while ($row = $stmt->fetch()) {
        echo "<div class='card'>";
        echo "<div class='item-header'>".$row['ItemName']."</div>";
        echo "<b>Category:</b> ".$row['Category']."<br>";
        echo "<b>Quantity:</b> ".$row['Quantity']."<br>";

        if ($row['Category'] == "Food" || $row['Category'] == "Other") {
            echo "<b>Expiry:</b> ".$row['ExpiryDate']."<br>";
        }

        if ($row['TotalRequests'] > 0 && $row['TotalRequests'] == $row['DeliveredCount']) {
            echo "<div class='delivered-box'>🚚 Delivered Successfully</div>";
        } else {
            echo "<div class='pending'>Status: Active</div>";
        }

        echo "<form method='POST' style='margin-top:15px;'>
                <input type='hidden' name='delete_id' value='".$row['DonationID']."'>
                <button name='action' value='delete' class='delete-btn'>🗑 Delete Donation</button>
              </form>";
        echo "</div>";
    }
    ?>

</div>

</body>
</html>
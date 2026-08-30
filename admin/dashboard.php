<?php
session_start();
include '../db.php';

$_SESSION['scroll_position'] = isset($_GET['scroll']) ? $_GET['scroll'] : 0;

/* HANDLE ACTION */
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id = $_POST['request_id'];

    if ($_POST['action'] == "approve") {
        $stmt = $conn->prepare("UPDATE Requests SET Status='Approved' WHERE RequestID=?");
        $stmt->execute([$id]);
    }

    if ($_POST['action'] == "reject") {
        $stmt = $conn->prepare("UPDATE Requests SET Status='Rejected' WHERE RequestID=?");
        $stmt->execute([$id]);
    }

    header("Location: dashboard.php?updated=1");
    exit();
}

/* FILTER VALUES */
$search = $_GET['search'] ?? '';
$category = $_GET['category'] ?? '';
$status_filter = $_GET['status'] ?? '';

$query = "
SELECT r.RequestID, r.RequestedQty, r.Status, r.CreatedDate,
d.ItemName, d.Category, u.Email
FROM Requests r
JOIN Donations d ON r.DonationID = d.DonationID
JOIN Users u ON r.ReceiverID = u.UserID
WHERE 1=1 
AND d.ItemName LIKE ? 
";

$params = ["%$search%"];

if ($category != "") {
    $query .= " AND d.Category = ?";
    $params[] = $category;
}

if ($status_filter != "") {
    $query .= " AND r.Status = ?";
    $params[] = $status_filter;
} else {
    // Show all non-delivered requests by default (Pending, Approved, Rejected)
    $query .= " AND r.Status != 'Delivered'";
}

$query .= " ORDER BY r.RequestID DESC";

$stmt = $conn->prepare($query);
$stmt->execute($params);
?>

<!DOCTYPE html>
<html>
<head>
<title>Admin Panel</title>

<style>

/* ===== GLOBAL THEME (EXACT MATCH LOGIN/REGISTER) ===== */

/* ===== GLOBAL BACKGROUND (WHITE THAT MATCHES GREEN) ===== */
body {
    margin: 0;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    min-height: 100vh;

    display: flex;
    flex-direction: column;

    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 50%, #f1f5f9 100%);
}

/* ===== HEADER (MATCHING REGISTER THEME GREEN) ===== */
.ribbon {
    text-align: center;
    font-size: 26px;
    font-weight: 800;
    color: #D8F3DC;
    position: relative;
    padding: 16px;

    /* Increased opacity to keep it Green on white background */
    background: rgba(27, 67, 50, 0.95); 
    backdrop-filter: blur(20px);

    /* Exact Register Page Border */
    border-bottom: 2px solid rgba(216, 243, 220, 0.5);
}

.logo {
    position: absolute;
    left: 20px;
    /* Vertically center the entire logo group */
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
    
    /* Keeps the 44x44 image from blurring */
    image-rendering: -webkit-optimize-contrast;
    image-rendering: crisp-edges;
}

.logo-img {
}

.logo-text {
    font-size: 26px;
    font-weight: 800;
    letter-spacing: 0.5px;
}

.share {
    color: #ffffff;
}

.hub {
    color: #95d5b2;
}

.sub {
    text-align: center;
    color: #1B4332; /* Changed to dark green for readability on white bg */
    font-size: 17px;
    margin: 8px 0 20px;
    font-weight: 700;
}

/* ===== FILTER BAR (MATCHING REGISTER THEME GREEN) ===== */
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

    /* Matching register box background */
    background: rgba(27, 67, 50, 0.9);
    backdrop-filter: blur(20px);

    border: 1px solid rgba(216, 243, 220, 0.5);
    box-shadow: 0 40px 100px rgba(0,0,0,0.2);
}

.filter-bar input,
.filter-bar select {
    width: 150px;
    padding: 10px;

    border-radius: 12px;
    border: 2px solid rgba(216, 243, 220, 0.4);

    outline: none;
    font-size: 13px;

    background: #fff;
}

/* SAME BUTTON STYLE AS REGISTER PAGE */
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

/* ===== CONTAINER ===== */
.container {
    display: flex;
    flex-wrap: wrap;
    justify-content: center;
    padding: 10px;
}

/* ===== CARD (MATCHING REGISTER THEME GREEN) ===== */
.card {
    width: 290px;
    margin: 10px;
    padding: 18px;

    /* High opacity so the green pops against the white bg */
    background: rgba(27, 67, 50, 0.95);
    backdrop-filter: blur(20px);

    border-radius: 28px;

    /* Exact Register Page Border */
    border: 1px solid rgba(216, 243, 220, 0.5);

    box-shadow: 0 20px 50px rgba(0,0,0,0.15);

    color: #D8F3DC;

    transition: 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
}

/* CARD HEADER */
.card-header {
    text-align: center;
    font-size: 16px;
    font-weight: 700;
    transition: all 0.3s ease;
    cursor: pointer;

    padding: 10px;
    border-radius: 12px;

    background: rgba(216, 243, 220, 0.15);
    border: 1px solid rgba(216, 243, 220, 0.4);

    margin-bottom: 12px;
    color: #fff;
}

/* HOVER EFFECT - MATCHES DONOR STYLE */
.card-header:hover {
    background: rgba(216, 243, 220, 0.25); /* Tiny bit brighter on hover */
    transform: translateY(-2px); /* Subtle lift */
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2); /* Added depth */
}

/* ===== STATUS (SOFT CLEAN UI MATCH LOGIN COLORS) ===== */
.status {
    margin-top: 10px;
    display: inline-block;
    padding: 6px 14px;

    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
}

.Approved {
    background: rgba(165, 214, 167, 0.25);
    color: #a5d6a7;
}

.Rejected {
    background: rgba(255, 107, 107, 0.25);
    color: #ffcccb;
}

.Pending {
    background: rgba(255, 243, 205, 0.25);
    color: #ffe082;
}

/* ===== BUTTONS (KEEP YOUR FUNCTIONAL STYLE, ONLY VISUAL MATCH) ===== */
.btns {
    margin-top: 15px;
    display: flex;
    gap: 12px;
}

button {
    flex: 1;
    padding: 12px;

    border-radius: 14px;
    border: none;

    font-weight: 700;
    cursor: pointer;

    transition: all 0.3s ease;
}

/* APPROVE (MATCH REGISTER BUTTON GREEN) */
.approve {
    background: linear-gradient(135deg, #1B4332 0%, #2D5A47 100%);
    color: white;

    box-shadow: 0 8px 25px rgba(27, 67, 50, 0.5);
    border: 1px solid rgba(216, 243, 220, 0.3);
}

/* REJECT (SOFT GLASS RED - NOT HARSH) */
.reject {
    background: rgba(255, 107, 107, 0.25);
    color: #ffcccb;

    border: 1px solid rgba(255, 107, 107, 0.4);
}
/* APPROVE HOVER - GLOWING GREEN LIFT */
.approve:hover {
    transform: translateY(-3px);
    background: linear-gradient(135deg, #2D5A47 0%, #1B4332 100%);
    box-shadow: 0 12px 30px rgba(27, 67, 50, 0.6), 
                0 0 15px rgba(216, 243, 220, 0.4);
    border: 1px solid rgba(216, 243, 220, 0.8);
}

/* REJECT HOVER - SOFT RED GLOW */
.reject:hover {
    transform: translateY(-3px);
    background: rgba(255, 107, 107, 0.35);
    box-shadow: 0 12px 30px rgba(255, 107, 107, 0.2);
    border: 1px solid rgba(255, 107, 107, 0.6);
    color: #fff;
}

/* CLICK EFFECT FOR BOTH */
.approve:active, .reject:active {
    transform: translateY(1px);
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
}

</style>
</head>

<body>

<div class="ribbon">

    <div class="logo">
    <div class="logo-icon"></div> <!-- The image is inside here via CSS background -->
    <div class="logo-text">
        <span class="share">Share</span><span class="hub">Hub</span>
    </div>
    </div>
    

    Admin Panel

</div>
<div class="sub">Manage Donation Requests</div>

<div class="filter-bar">
<form method="GET">
    <input type="text" name="search" placeholder="Search item..." value="<?php echo htmlspecialchars($search); ?>">
    
    <select name="category">
        <option value=""> All Categories </option>
        <option value="Food" <?php if($category=="Food") echo "selected"; ?>>Food</option>
        <option value="Apparel" <?php if($category=="Apparel") echo "selected"; ?>>Apparel</option>
        <option value="Entertainment" <?php if($category=="Entertainment") echo "selected"; ?>>Entertainment</option>
    </select>

    <select name="status">
        <option value=""> All Statuses </option>
        <option value="Pending" <?php if($status_filter=="Pending") echo "selected"; ?>>Pending</option>
        <option value="Approved" <?php if($status_filter=="Approved") echo "selected"; ?>>Approved</option>
        <option value="Rejected" <?php if($status_filter=="Rejected") echo "selected"; ?>>Rejected</option>
    </select>

    <button type="submit">Filter</button>
</form>
</div>

<div class="container">

<?php while($row = $stmt->fetch()) { ?>

<div class="card">

    <div class="card-header">
        <?php echo htmlspecialchars($row['ItemName']); ?>
    </div>

    <b>Quantity:</b> <?php echo htmlspecialchars($row['RequestedQty']); ?><br>
    <b>Receiver:</b> <?php echo htmlspecialchars($row['Email']); ?><br>

    <div class="status <?php echo htmlspecialchars($row['Status']); ?>">
        <?php echo htmlspecialchars($row['Status']); ?>
    </div>

    <div class="btns">

        <form method="POST">
            <input type="hidden" name="request_id" value="<?php echo $row['RequestID']; ?>">
            <button class="approve" name="action" value="approve">Approve</button>
        </form>

        <form method="POST">
            <input type="hidden" name="request_id" value="<?php echo $row['RequestID']; ?>">
            <button class="reject" name="action" value="reject">Reject</button>
        </form>

    </div>

</div>

<?php } ?>

</div>
<script>
// RESTORE SCROLL POSITION AFTER RELOAD
window.addEventListener('load', function() {
    const scrollPos = sessionStorage.getItem('scrollPos') || 0;
    window.scrollTo(0, parseInt(scrollPos));
});

// SAVE SCROLL POSITION BEFORE FORM SUBMIT
document.querySelectorAll('form').forEach(form => {
    form.addEventListener('submit', function() {
        sessionStorage.setItem('scrollPos', window.scrollY);
    });
});
</script>
</body>
</html>
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
/* KARACHI LOCATIONS - REMOVED hardcoded locations, now uses actual DB addresses */
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

/* HANDLE ACTIONS */
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'];

    if ($action == "pickup") {
        $donationID = $_POST['donation_id'];
        $requestID = $_POST['request_id'];
        $agentID = $_SESSION['user_id'];

        $conn->prepare("INSERT INTO Pickup (DonationID, RequestID, AgentID, PickupTime, Status)
                        VALUES (?, ?, ?, GETDATE(), 'Picked')")
             ->execute([$donationID, $requestID, $agentID]);

        header("Location: dashboard.php#req".$requestID);
        exit();
    }

    if ($action == "deliver") {
        $requestID = $_POST['request_id'];

        // update pickup
        $query = "UPDATE Pickup
                  SET DeliveryTime = GETDATE(), Status = 'Delivered'
                  WHERE RequestID = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$requestID]);

        $query2 = "UPDATE Requests SET Status = 'Delivered' WHERE RequestID = ?";
        $stmt2 = $conn->prepare($query2);
        $stmt2->execute([$requestID]);

        header("Location: dashboard.php#req".$requestID);
        exit();
    }
}

/* FILTER - Changed to text search instead of hardcoded locations */
$origin = $_GET['origin'] ?? '';
$destination = $_GET['destination'] ?? '';

$query = "
SELECT
    r.RequestID,
    r.RequestedQty,
    d.DonationID,
    d.ItemName,
    donor.Address AS DonorAddress,
    receiver.Address AS ReceiverAddress,
    p.Status AS PickupStatus
FROM Requests r
JOIN Donations d ON r.DonationID = d.DonationID
JOIN Users donor ON d.DonorID = donor.UserID
JOIN Users receiver ON r.ReceiverID = receiver.UserID
LEFT JOIN Pickup p ON r.RequestID = p.RequestID
WHERE r.Status = 'Approved'
";

$params = [];

if ($origin != "") {
    $query .= " AND donor.Address LIKE ?";
    $params[] = "%$origin%";
}

if ($destination != "") {
    $query .= " AND receiver.Address LIKE ?";
    $params[] = "%$destination%";
}

/* SORT: Pending → Picked → Delivered */
$query .= "
ORDER BY
CASE 
    WHEN p.Status IS NULL THEN 0
    WHEN p.Status='Picked' THEN 1
    WHEN p.Status='Delivered' THEN 2
END,
r.RequestID DESC
";

$stmt = $conn->prepare($query);
$stmt->execute($params);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Agent Delivery Panel</title>
    <style>
        /* ===== GLOBAL THEME (EXACT MATCH ADMIN/DONOR) ===== */
        body {
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 50%, #f1f5f9 100%);
        }

        /* ===== HEADER RIBBON ===== */
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

        /* ===== LOGO (EXACT COPY FROM ADMIN) ===== */
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
         color: #1B4332;
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
            gap: 14px;

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

        /* ===== CONTAINER & CARDS ===== */
        .container {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
            padding: 10px;
        }

         .card {
         width: 310px;
         margin: 15px;
         padding: 28px;
         background: rgba(27, 67, 50, 0.95);
         backdrop-filter: blur(20px);
         border-radius: 45px;
         border: 1px solid rgba(216, 243, 220, 0.5);
         box-shadow: 0 20px 50px rgba(0,0,0,0.15);
         color: #D8F3DC;
         transition: all 0.3s ease;
         }

         .card:hover {
         transform: translateY(-5px);
         }

        .card-header {
            text-align: center;
            font-size: 17px;
            font-weight: 700;
            padding: 13px;
            border-radius: 12px;
            background: rgba(216, 243, 220, 0.15);
            border: 1px solid rgba(216, 243, 220, 0.4);
            margin-bottom: 15px;
            color: #fff;
        }

        .card-header:hover {
         background: rgba(216, 243, 220, 0.25);
         transform: translateY(-2px);
         box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
         }

        /* ===== ADRESS SECTIONS ===== */
        .section {
            margin-top: 12px;
            padding: 12px;
            border-radius: 14px;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(216, 243, 220, 0.1);
            font-size: 13px;
            line-height: 1.4;
        }

        .section b { color: #fff; display: block; margin-bottom: 4px; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; }

        /* ===== STATUS BADGES ===== */
        .status {
            margin: 15px 0;
            display: inline-block;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 700;
        }

        .pending { background: rgba(255, 243, 205, 0.2); color: #ffe082; border: 1px solid rgba(255, 206, 84, 0.2); }
        .picked { background: rgba(216, 243, 220, 0.2); color: #D8F3DC; border: 1px solid rgba(116, 198, 157, 0.2);}
        .delivered { background: rgba(165, 214, 167, 0.25); color: #a5d6a7; border: 1px solid rgba(165, 214, 167, 0.3); }

        /* ===== BUTTONS ===== */
        /* CONFIRM PICKUP - MATCHES PRIMARY ACTION STYLE */
    .pickup-btn {
    width: 100%;
    padding: 14px;

    border-radius: 14px;

    background: rgba(255, 255, 255, 0.05);

    border: 1px solid rgba(255, 255, 255, 0.18);

    color: #ffffff;

    font-weight: 700;
    cursor: pointer;

    transition: all 0.3s ease;

    text-transform: uppercase;
    font-size: 13px;
    letter-spacing: 1px;

    margin-top: auto;

    backdrop-filter: blur(12px);
}

.pickup-btn:hover {
    background: rgba(255, 255, 255, 0.15);

    border-color: rgba(255, 255, 255, 0.35);

    transform: translateY(-2px);

    box-shadow: 0 8px 20px rgba(255, 255, 255, 0.1);
}

.pickup-btn:active {
    transform: translateY(1px);
}
    /* MARK AS DELIVERED - MATCHES DONOR GHOST BUTTON */
    .deliver-btn {
        width: 100%;
        padding: 14px;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.18);
        color: #ffffff;
        font-weight: 700;
        cursor: pointer;
        transition: all 0.3s ease;
        text-transform: uppercase;
        font-size: 13px;
        letter-spacing: 1px;
        margin-top: 10px;
    }

    .deliver-btn:hover {
        background: rgba(255, 255, 255, 0.15);
        border-color: rgba(255, 255, 255, 0.35);
        transform: translateY(-2px);
        box-shadow: 0 8px 20px rgba(255, 255, 255, 0.1);
    }
    </style>
</head>

<body>

<div class="ribbon">
    <!-- LOGO EXACTLY LIKE ADMIN -->
    <div class="logo">
        <div class="logo-icon"></div> <!-- The image is inside here via CSS background -->
        <div class="logo-text">
            <span class="share">Share</span><span class="hub">Hub</span>
        </div>
    </div>
    
    Agent Panel
</div>

<div class="sub">Manage Deliveries & Pickups</div>

<!-- FILTER BAR -->
<div class="filter-bar">
    <form method="GET">
<select name="origin">
    <option value="">Any Origin</option>
    <?php foreach($locations as $loc){ ?>
        <option value="<?php echo $loc; ?>" <?php if($origin==$loc) echo "selected"; ?>>
            <?php echo $loc; ?>
        </option>
    <?php } ?>
</select>

<select name="destination">
    <option value="">Any Destination</option>
    <?php foreach($locations as $loc){ ?>
        <option value="<?php echo $loc; ?>" <?php if($destination==$loc) echo "selected"; ?>>
            <?php echo $loc; ?>
        </option>
    <?php } ?>
</select>

        <button type="submit" class="filter-btn">Filter</button>
    </form>
</div>

<div class="container">
    <?php
    while ($row = $stmt->fetch()) {
        $pickupLoc = $row['DonorAddress'] ?: "Unknown Location";
        $deliverLoc = $row['ReceiverAddress'] ?: "Unknown Location";

        echo "<div id='req".$row['RequestID']."' class='card'>";
            echo "<div class='card-header'>".$row['ItemName']." (x".$row['RequestedQty'].")</div>";

            echo "<div class='section'><b>Pickup Point</b>".$pickupLoc."</div>";
            echo "<div class='section'><b>Delivery Point</b>".$deliverLoc."</div>";

            $status = strtolower($row['PickupStatus'] ?? '');

            if ($status == "picked") {
                echo "<div class='status picked'>● Picked Up</div>";
            } elseif ($status == "delivered") {
                echo "<div class='status delivered'>✔ Delivered</div>";
            } else {
                echo "<div class='status pending'>● Awaiting Pickup</div>";
            }

            if (!$row['PickupStatus']) {
    ?>
                <form method="POST">
                    <input type="hidden" name="donation_id" value="<?php echo $row['DonationID']; ?>">
                    <input type="hidden" name="request_id" value="<?php echo $row['RequestID']; ?>">
                    <button class="pickup-btn" name="action" value="pickup">Confirm Pickup</button>
                </form>
    <?php
            }

            if ($status == "picked") {
    ?>
                <form method="POST">
                    <input type="hidden" name="request_id" value="<?php echo $row['RequestID']; ?>">
                    <button class="deliver-btn" name="action" value="deliver">Mark as Delivered</button>
                </form>
    <?php
            }
        echo "</div>";
    }
    ?>
</div>

</body>
</html>
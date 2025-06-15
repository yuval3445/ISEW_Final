<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Database connection
$server = "sql103.byethost9.com";
$username = "b9_38656470";
$password = "rs4cbxzm";
$dbname = "b9_38656470_pizzeria";

$conn = new mysqli($server, $username, $password, $dbname);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// Get order details
$orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : 0;
$orderDetails = null;

if ($orderId > 0) {
  $query = "SELECT * FROM Orders WHERE orderID = ?";
  $stmt = $conn->prepare($query);
  $stmt->bind_param("i", $orderId);
  $stmt->execute();
  $result = $stmt->get_result();
  $orderDetails = $result->fetch_assoc();
  $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Order Confirmation - Pizza Ordering System</title>
  <style>
    html, body {
      margin: 0;
      padding: 0;
      min-height: 100vh;
      width: 100%;
      font-family: Arial, sans-serif;
      background-image: url("imgs/pizza_background3.png");
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
    }

    header {
      position: fixed;
      top: 0;
      width: 100%;
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 20px;
      background-color: rgba(245, 235, 220, 0.85);
      border-bottom: 2px solid burlywood;
      z-index: 1000;
    }

    .nav-links {
      display: flex;
      gap: 20px;
    }

    .nav-links a {
      text-decoration: black;
      color: gold;
      font-size: 20px;
      font-weight: bold;
       -webkit-text-stroke: 1px black;
        text-shadow:
    -1px -1px 0 black,
     1px -1px 0 black,
    -1px  1px 0 black,
     1px  1px 0 black;
    }

    .confirmation-container {
      margin-top: 120px;
      padding: 30px;
      max-width: 800px;
      margin-left: auto;
      margin-right: auto;
      background-color: rgba(255, 255, 255, 0.9);
      border-radius: 20px;
      box-shadow: 0 0 15px rgba(0,0,0,0.2);
      text-align: center;
    }

    .confirmation-icon {
      font-size: 80px;
      color: #4CAF50;
      margin-bottom: 20px;
    }
    
    .confirmation-message {
      font-size: 32px;
      font-weight: bold;
      color: #4CAF50;
      margin-bottom: 20px;
      text-transform: uppercase;
      letter-spacing: 1px;
    }

    .order-details {
      margin-top: 30px;
      text-align: left;
      padding: 20px;
      background-color: #f9f9f9;
      border-radius: 10px;
    }

    .detail-row {
      display: flex;
      justify-content: space-between;
      margin-bottom: 10px;
      padding-bottom: 10px;
      border-bottom: 1px solid #eee;
    }

    .buttons {
      margin-top: 30px;
      display: flex;
      justify-content: center;
      gap: 20px;
    }

    .btn {
      padding: 12px 25px;
      border-radius: 5px;
      text-decoration: none;
      font-weight: bold;
      cursor: pointer;
    }

    .btn-primary {
      background-color: #4CAF50;
      color: white;
    }

    .btn-secondary {
      background-color: #f0f0f0;
      color: #333;
      border: 1px solid #ddd;
    }
  </style>
</head>
<body>
  <header>
    <div class="nav-links">
      <a href="index.html">Home</a>
      <a href="menu.html">Menu</a>
      <a href="buyPage/buypage2.html">Order Now!</a>
      <a href="track_order.php">Track Your Order!</a>
      <a href="aboutUs.html">About Us</a>
      <a href="reviews.php">Reviews</a>
    </div>
  </header>

  <div class="confirmation-container">
    <div class="confirmation-icon">✓</div>
    <div class="confirmation-message">YOUR ORDER HAS BEEN RECEIVED!</div>
    <p>Thank you for your order. We're preparing it now and will update you on its status.</p>
    
    <?php if ($orderDetails): ?>
    <div class="order-details">
      <div class="detail-row">
        <span>Order ID:</span>
        <span>#<?= $orderDetails['orderID'] ?></span>
      </div>
      <div class="detail-row">
        <span>Order Date:</span>
        <span><?= date('F j, Y, g:i a', strtotime($orderDetails['orderTime'])) ?></span>
      </div>
      <div class="detail-row">
        <span>Customer Name:</span>
        <span><?= htmlspecialchars($orderDetails['CustomerName']) ?></span>
      </div>
      <div class="detail-row">
        <span>Status:</span>
        <span><?= htmlspecialchars($orderDetails['stage']) ?></span>
      </div>
    </div>
    <?php else: ?>
    <div class="order-details">
      <p>Order details not found. Please contact customer support.</p>
    </div>
    <?php endif; ?>
    
    <div class="buttons">
      <a href="index.html" class="btn btn-primary">Return to Home</a>
    </div>
  </div>
</body>
</html>


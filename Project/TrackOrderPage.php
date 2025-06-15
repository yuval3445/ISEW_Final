<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$stageMessage = '';
$orderId = $_GET['order_id'] ?? null;

$server = "sql103.byethost9.com";
$username = "b9_38656470";
$password = "rs4cbxzm";
$dbname = "b9_38656470_pizzeria";

$conn = new mysqli($server, $username, $password, $dbname);
if ($conn->connect_error) {
    $stageMessage = "❌ Connection failed: " . $conn->connect_error;
} else {
    $stages = ["Ordered!", "in the making", "ready", "out for delivery", "done"];
    $sql = "SELECT OrderID, stage FROM Orders";
    $result = $conn->query($sql);

    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $oid = $row['OrderID'];
            $currentStage = $row['stage'];

            $currentIndex = array_search($currentStage, $stages);
            if ($currentIndex !== false && $currentStage !== "done") {
                $nextStage = $stages[$currentIndex + 1];
                $conn->query("UPDATE Orders SET stage = '$nextStage' WHERE OrderID = $oid");
            }
        }
    }

    if ($orderId !== null && is_numeric($orderId)) {
        $orderId = intval($orderId);
        $sql = "SELECT stage FROM Orders WHERE OrderID = $orderId";
        $result = $conn->query($sql);

        if ($result && $result->num_rows > 0) {
            $row = $result->fetch_assoc();
            $stageMessage = "Stage: " . $row['stage'];
        } else {
            $stageMessage = "❌ Order not found.";
        }
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <title>Track Your Order</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
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
      background-color: transparent;
      display: flex;
      flex-direction: column;
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
      backdrop-filter: blur(4px);
      border-bottom: 2px solid burlywood;
      z-index: 1000;
    }

    .nav-links {
      display: flex;
      gap: 20px;
    }

    .nav-links a {
      text-decoration: none;
      color: gold;
      font-size: 20px;
      font-weight: bold;
      padding: 6px 10px;
      border-radius: 5px;
      transition: background-color 0.3s ease, color 0.3s ease;
      text-shadow: -1px -1px 0 black, 1px -1px 0 black, -1px 1px 0 black, 1px 1px 0 black;
    }

    .nav-links a:hover {
      background-color: rgba(255, 215, 0, 0.3);
      color: white;
    }

    .header-right {
      display: flex;
      align-items: center;
      gap: 20px;
      margin-right: 30px;
    }

    .logo, .cart {
      display: flex;
      flex-direction: column;
      align-items: center;
      color: burlywood;
      font-weight: bold;
      text-shadow: -1px -1px 0 black, 1px -1px 0 black, -1px 1px 0 black, 1px 1px 0 black;
    }

    .logo img {
      width: 100px;
    }

    .cart img {
      width: 50px;
      height: 55px;
    }

    .main-container {
      flex: 1;
      display: flex;
      justify-content: center;
      align-items: center;
      padding-top: 140px;
    }

    #tracker {
      background: rgba(255,255,255,0.9);
      padding: 40px;
      border-radius: 20px;
      text-align: center;
      max-width: 600px;
      width: 90%;
      box-shadow: 0 0 20px rgba(0,0,0,0.2);
    }

    input[type="number"], button {
      font-size: 18px;
      padding: 12px;
      border-radius: 10px;
      border: 1px solid lightgray;
      margin: 10px 0;
      width: 80%;
    }

    button {
      background-color: red;
      color: white;
      border: none;
      cursor: pointer;
    }

    .status {
      margin-top: 20px;
      padding: 15px;
      font-size: 20px;
      border-radius: 10px;
      background-color: mistyrose;
      color: orangered;
    }

    .done {
      background-color: honeydew;
      color: darkgreen;
    }

@media (max-width: 768px) {
  header {
    flex-direction: column;
    align-items: center;
    padding: 10px;
    gap: 10px;
    text-align: center;
  }

  .nav-links {
    flex-wrap: wrap;
    flex-direction: row;
    justify-content: center;
    gap: 10px;
    width: 100%;
  }

  .nav-links a {
    font-size: 16px;
    padding: 8px 12px;
    border-radius: 6px;
  }

  .header-right {
    flex-direction: row;
    justify-content: center;
    gap: 15px;
    margin: 0;
  }

  .logo img {
    width: 70px;
  }

  .cart img {
    width: 40px;
    height: auto;
  }

  .logo p, .cart p {
    font-size: 12px;
    margin: 0;
  }

  .main-container {
    padding-top: 180px;
    padding-left: 10px;
    padding-right: 10px;
  }

  #tracker {
    padding: 30px 20px;
  }

  h1 {
    font-size: 24px;
  }

  input[type="number"],
  button {
    width: 100%;
    font-size: 16px;
    padding: 10px;
  }

  .status {
    font-size: 16px;
    padding: 10px;
  }
}
  </style>
</head>
<body>
  <!-- Header -->
  <header>
    <div class="nav-links">
      <a href="index.html">Home</a>
      <a href="menu.html">Menu</a>
      <a href="order.php">Order Now!</a>
      <a href="TrackOrderPage.php">Track Your Order!</a>
      <a href="aboutUs.html">About Us</a>
      <a href="reviews.php">Reviews</a>
      <a href="swf.php">SplitWithFriends!</a>
    </div>
    <div class="header-right">
      <div class="cart">
        <img src="imgs/Cart.png" alt="Cart" />
        <p>Cart</p>
      </div>
      <div class="logo">
        <img src="imgs/pizza_logo.png" alt="Logo" />
        <p>Contact us: 777777777777</p>
      </div>
    </div>
  </header>

  <!-- Main Container -->
  <div class="main-container">
    <div id="tracker">
      <h1>🍕 Track Your Order</h1>
      <form method="get" action="TrackOrderPage.php">
        <input type="number" name="order_id" placeholder="Enter your Order ID" required />
        <br />
        <button type="submit">Track Order</button>
      </form>

      <?php if ($orderId !== null): ?>
        <div class="status <?php echo (stripos($stageMessage, 'done') !== false) ? 'done' : ''; ?>">
          <?php echo htmlspecialchars($stageMessage); ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
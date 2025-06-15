<?php
// Set timezone to Jerusalem
date_default_timezone_set("Asia/Jerusalem");

$message = "";
$reviews = [];
$showMessage = false;

$server = "sql103.byethost9.com";
$username = "b9_38656470";
$password = "rs4cbxzm";
$dbname = "b9_38656470_pizzeria";

// DB connection
$conn = new mysqli($server, $username, $password, $dbname);
if ($conn->connect_error) {
    $message = "❌ Connection failed: " . $conn->connect_error;
} else {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = trim($_POST['name'] ?? '');
        $rating = floatval($_POST['rating'] ?? 0);
        $comment = trim($_POST['comment'] ?? '');
        $reviewDate = date("Y-m-d H:i:s");

        if ($rating >= 1.00 && $rating <= 5.00) {
            $stmt = $conn->prepare("INSERT INTO reviews (name, rating, comment, reviewDate) VALUES (?, ?, ?, ?)");
            $stmt->bind_param("sdss", $name, $rating, $comment, $reviewDate);
            if ($stmt->execute()) {
                $message = "✅ Review submitted!";
                $showMessage = true;
            }
            $stmt->close();
        }
    }

    $result = $conn->query("SELECT name, rating, comment, reviewDate FROM reviews ORDER BY reviewDate DESC");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $reviews[] = $row;
        }
    }

    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>NoY Pizza - Reviews</title>
  <style>
    html, body {
      margin: 0;
      padding: 0;
      font-family: Arial, sans-serif;
      background-image: url("imgs/pizza_background3.png");
      background-size: cover;
      background-repeat: no-repeat;
      background-position: center;
      min-height: 100vh;
    }

   header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 10px 20px;
  background-color: rgba(245, 235, 220, 0.85); /* light warm beige */
  backdrop-filter: blur(4px);
  border-bottom: 2px solid burlywood;
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
      width: 55px;
    }

    .review-table {
      width: 100%;
      margin-top: 20px;
      background-color: rgba(255,255,255,0.95);
      border-collapse: collapse;
    }

    .review-table th, .review-table td {
      border: 1px solid #999;
      padding: 8px 12px;
      text-align: left;
    }

    .review-table th {
      background-color: burlywood;
      color: white;
    }

    .bottom-left-form {
      position: absolute;
      bottom: 20px;
      left: 20px;
      background-color: rgba(255,255,255,0.95);
      padding: 25px;
      border-radius: 15px;
      width: 300px;
    }

    .bottom-left-form h4 {
      margin: 0 0 10px;
      color: darkred;
      font-size: 20px;
      text-align: center;
    }

    .form-input, .form-textarea {
      width: 100%;
      margin-bottom: 10px;
      padding: 10px;
      border-radius: 10px;
      border: 1px solid #ccc;
      font-size: 14px;
    }

    .form-textarea {
      height: 90px;
      resize: vertical;
    }

    .submit-btn {
      background-color: burlywood;
      color: white;
      border: none;
      padding: 10px;
      border-radius: 10px;
      font-weight: bold;
      width: 100%;
      cursor: pointer;
    }

    .submit-btn:hover {
      background-color: burlywood;
    }

    .inline-message {
      font-size: 13px;
      color: green;
      margin-top: 6px;
      text-align: center;
    }

   @media (max-width: 768px) {
  .nav-links {
    flex-wrap: wrap;
    gap: 12px;
    justify-content: center;
  }

  .header-right {
    flex-direction: row;
    justify-content: center;
    flex-wrap: wrap;
    margin-top: 10px;
  }

  .logo img,
  .cart img {
    width: 70px;
  }

  .review-table {
    font-size: 13px;
    overflow-x: auto;
    display: block;
    width: 95%;
    margin: 20px auto 0;
  }

  .bottom-left-form {
    position: static;
    width: 90%;
    margin: 20px auto;
  }

  .form-input, .form-textarea {
    font-size: 14px;
  }
}

@media (max-width: 480px) {
  .nav-links a {
    font-size: 16px;
  }

  .logo img,
  .cart img {
    width: 60px;
  }

  .review-table {
    font-size: 12px;
    width: 100%;
  }

  .bottom-left-form {
    width: 95%;
    padding: 15px;
  }

  .form-input, .form-textarea {
    font-size: 13px;
    padding: 8px;
  }

  .submit-btn {
    font-size: 14px;
    padding: 8px;
  }

  .inline-message {
    font-size: 12px;
  }
}

  </style>
</head>
<body>
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
        <img src="imgs/Cart.png" alt="Shopping Cart">
        <p>Cart</p>
      </div>
      <div class="logo">
        <img src="imgs/pizza_logo.png" alt="NoY Pizza Logo">
        <p>Contact us: 777777777777</p>
      </div>
    </div>
  </header>

  <table class="review-table">
    <thead>
      <tr>
        <th>Name</th>
        <th>Rating</th>
        <th>Comment</th>
        <th>Date</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($reviews)): ?>
        <tr><td colspan="4">No reviews yet.</td></tr>
      <?php else: ?>
        <?php foreach ($reviews as $review): ?>
          <tr>
            <td><?= htmlspecialchars($review['name']) ?></td>
            <td><?= htmlspecialchars($review['rating']) ?></td>
            <td><?= htmlspecialchars($review['comment']) ?></td>
            <td><?= htmlspecialchars($review['reviewDate']) ?></td>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
  </table>

  <div class="bottom-left-form">
    <h4>Write a Comment</h4>
    <form method="POST">
      <input class="form-input" type="text" name="name" placeholder="Name" required>
      <input class="form-input" type="number" step="any" name="rating" placeholder="Rating (1–5)" required>
      <textarea class="form-textarea" name="comment" placeholder="Your comment..." required></textarea>
      <button type="submit" class="submit-btn">Submit</button>
      <?php if ($showMessage): ?>
        <div class="inline-message">Review submitted!</div>
      <?php endif; ?>
    </form>
  </div>
</body>
</html>
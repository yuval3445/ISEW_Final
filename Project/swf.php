<?php
function calculatePerPerson($total, $tipPercent, $people) {
    $totalWithTip = $total + ($total * ($tipPercent / 100));
    return round($totalWithTip / $people, 2);
}

function formatPrice($amount) {
    return "₪" . number_format($amount, 2);
}

function createMessages($people, $amountFormatted) {
    $msgs = [];
    for ($i = 1; $i <= $people; $i++) {
        $msgs[] = "Person #$i pays $amountFormatted";
    }
    return $msgs;
}

$pricePerPerson = null;
$messages = [];
$tipOptions = range(0, 30);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'total' => floatval($_POST['total'] ?? 0),
        'tip' => floatval($_POST['tip'] ?? 0),
        'people' => intval($_POST['people'] ?? 1)
    ];

    if ($data['people'] > 0) {
        $price = calculatePerPerson($data['total'], $data['tip'], $data['people']);
        $pricePerPerson = formatPrice($price);
        $messages = createMessages($data['people'], $pricePerPerson);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Split With Friends</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: url("imgs/pizza_background3.png") no-repeat center center fixed;
            background-size: cover;
            margin: 0;
        }
        header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            background-color: rgba(245, 235, 220, 0.85);
            backdrop-filter: blur(4px);
            border-bottom: 2px solid burlywood;
            flex-wrap: wrap;
        }
        .nav-links {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
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
            flex-wrap: wrap;
            justify-content: center;
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
            height: auto;
        }
        .cart img {
            width: 50px;
            height: 55px;
        }
        @media (max-width: 768px) {
            .logo img {
                width: 70px;
            }
            .cart img {
                width: 70px;
            }
        }
        @media (max-width: 480px) {
            header {
                flex-direction: column;
                align-items: center;
                gap: 10px;
            }
            .nav-links {
                justify-content: center;
                gap: 10px;
            }
            .nav-links a {
                font-size: 16px;
            }
            .logo img, .cart img {
                width: 60px;
            }
            .header-right {
                flex-direction: row;
                justify-content: center;
                gap: 30px;
                width: 100%;
            }
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            text-align: center;
            width: 300px;
            margin: 50px auto;
        }
        input, select, button {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 16px;
        }
        button {
            background-color: gold;
            border: none;
            font-weight: bold;
            cursor: pointer;
        }
        button:hover {
            background-color: darkorange;
            color: white;
        }
        .result {
            margin-top: 15px;
            font-size: 18px;
            color: darkgreen;
        }
    </style>
</head>
<body>
    <header>
        <div class="nav-links">
            <a href="index.html">Home</a>
            <a href="menu.html">Menu</a>
            <a href="order.php">Order Now!</a>
            <a href="TrackOrderPage.html">Track Your Order!</a>
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

    <div class="container">
        <h2>Split With Friends</h2>
        <form method="POST">
            <input type="number" step="0.01" name="total" placeholder="Total Order Amount" required>

            <select name="tip">
                <?php foreach ($tipOptions as $tip): ?>
                    <option value="<?= $tip ?>"><?= $tip === 0 ? 'No Tip' : $tip . '% Tip' ?></option>
                <?php endforeach; ?>
            </select>

            <input type="number" name="people" min="1" placeholder="Number of People" required>
            <button type="submit">Calculate</button>
        </form>

        <?php if (!is_null($pricePerPerson)): ?>
        <div class="result">
            <p>💸 Each person pays: <strong><?php echo $pricePerPerson; ?></strong></p>
            <ul>
                <?php foreach ($messages as $msg): ?>
                    <li><?php echo htmlspecialchars($msg); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>
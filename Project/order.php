<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Debug POST data
echo "<!-- POST data: " . print_r($_POST, true) . " -->";

// Process clear order first, before any other logic
if (isset($_POST['clear_order'])) {
  // Reset all order values and redirect to clear POST data
  header("Location: " . $_SERVER['PHP_SELF']);
  exit;
}

$server = "sql103.byethost9.com";
$username = "b9_38656470";
$password = "rs4cbxzm";
$dbname = "b9_38656470_pizzeria";

$conn = new mysqli($server, $username, $password, $dbname);
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

$pizzaSizes = [];
$pizzaToppings = [];
$extraItems = [];
$drinkSizes = [];

$result = $conn->query("SELECT id, item_name, kind FROM items");
if (!$result) {
  die("Items query failed: " . $conn->error);
}
while ($row = $result->fetch_assoc()) {
  switch ($row['kind']) {
    case 'pizzaSize': $pizzaSizes[] = $row; break;
    case 'topping': $pizzaToppings[] = $row; break;
    case 'extras': $extraItems[] = $row; break;
    case 'drinkOption': $drinkSizes[] = $row; break;
  }
}

$discounts = [];
$discount_query = "SELECT d.DiscountsID, d.name, d.price, di.itemID, di.numberOfItems, i.item_name
  FROM discounts d
  JOIN item_discounts di ON d.DiscountsID = di.discountID
  JOIN items i ON di.itemID = i.id
  ORDER BY d.DiscountsID";
$discount_result = $conn->query($discount_query);
if (!$discount_result) {
  die("Discount query failed: " . $conn->error);
}
while ($row = $discount_result->fetch_assoc()) {
  $id = $row['DiscountsID'];
  if (!isset($discounts[$id])) {
    $discounts[$id] = [
      'name' => $row['name'],
      'price' => $row['price'],
      'items' => []
    ];
  }
  $discounts[$id]['items'][] = [
    'name' => $row['item_name'],
    'count' => $row['numberOfItems']
  ];
}

$selectedDiscount = isset($_POST['selected_discount']) ? intval($_POST['selected_discount']) : null;
if ($selectedDiscount !== null && isset($discounts[$selectedDiscount])) {
  // Reset counts when selecting a discount
  $pizzaCount = 0;
  $drinkCount = 0;
  $extraCount = 0;
  $showPizza = false;
  $showDrinks = false;
  $showExtras = false;
  
  // Debug output
  echo "<!-- Selected discount: " . $discounts[$selectedDiscount]['name'] . " -->";
  
  // Only process base categories (pizza, drinks, extras)
  foreach ($discounts[$selectedDiscount]['items'] as $item) {
    $name = strtolower($item['name']);
    echo "<!-- Processing item: " . $item['name'] . " x" . $item['count'] . " -->";
    
    // Direct matching by category name only
    if ($name === 'pizza') {
      $pizzaCount += $item['count'];
      $showPizza = true;
      echo "<!-- Matched as pizza category -->";
    }
    else if ($name === 'drinks') {
      $drinkCount += $item['count'];
      $showDrinks = true;
      echo "<!-- Matched as drinks category -->";
    }
    else if ($name === 'extras') {
      $extraCount += $item['count'];
      $showExtras = true;
      echo "<!-- Matched as extras category -->";
    }
    else {
      echo "<!-- Ignoring specific item: " . $item['name'] . " -->";
    }
  }
  
  echo "<!-- Final counts: Pizza=" . $pizzaCount . ", Drinks=" . $drinkCount . ", Extras=" . $extraCount . " -->";
} else {
  $pizzaCount = isset($_POST['pizza_count']) ? intval($_POST['pizza_count']) : 1;
  $drinkCount = isset($_POST['drink_count']) ? intval($_POST['drink_count']) : 1;
  $extraCount = isset($_POST['extra_count']) ? intval($_POST['extra_count']) : 1;
  $showPizza = isset($_POST['pizzaToggle']);
  $showDrinks = isset($_POST['drinkToggle']);
  $showExtras = isset($_POST['extraToggle']);
}

// Add this section to handle the "Add More" buttons
if (isset($_POST['add_more_pizza'])) {
  $pizzaCount += 1;
  $showPizza = true;
}
if (isset($_POST['add_more_drink'])) {
  $drinkCount += 1;
  $showDrinks = true;
}
if (isset($_POST['add_more_extra'])) {
  $extraCount += 1;
  $showExtras = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Pizza Order UI</title>
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

    .main-container {
      margin-top: 120px;
      padding: 30px;
      display: grid;
      grid-template-areas: "discount order";
      grid-template-columns: 1fr 3fr;
      gap: 20px;
      justify-content: center;
    }
    .main-layout-grid {
      display: grid;
      grid-template-areas: "discount order";
      grid-template-columns: 1fr 3fr;
      gap: 20px;
      justify-content: center;
      margin-top: 120px;
      padding: 30px;
    }

    .discount-panel {
      grid-area: discount;
      background-color: rgba(255, 255, 255, 0.9);
      border-radius: 20px;
      padding: 15px;
      overflow-y: auto;
      border-right: 2px solid #ccc;
      box-shadow: 0 0 15px rgba(0,0,0,0.2);
    }

    .order-panel {
      grid-area: order;
      background-color: rgba(255, 255, 255, 0.9);
      border-radius: 20px;
      padding: 20px;
      overflow-y: auto;
      box-shadow: 0 0 15px rgba(0,0,0,0.2);
    }

    .discount-card {
      background: #e7ffe7;
      border: 1px solid #88cc88;
      padding: 10px;
      margin-bottom: 15px;
      border-radius: 8px;
      display: block;
      width: 100%;
      text-align: left;
    }

    .discount-card h3 { margin: 0 0 5px 0; }
    .discount-card ul { margin: 0; padding-left: 18px; }

    .section { margin-bottom: 25px; }
    .toppings-row, .drink-row, .extra-row {
      display: flex;
      flex-wrap: wrap;
      gap: 10px;
      margin-top: 10px;
    }

    @media (max-width: 1000px) {
      .main-layout-grid {
        display: flex;
        flex-direction: column;
        align-items: center;
      }

      .discount-panel, .order-panel {
        width: 90%;
        margin-bottom: 20px;
      }
    }
  </style>
</head>
<body>
<header>
  <div class="nav-links">
    <a href="index.html" onclick="return confirmLeave()">Home</a>
    <a href="menu.html" onclick="return confirmLeave()">Menu</a>
    <a href="order.php" onclick="return confirmLeave()">Order Now!</a>
    <a href="TrackOrderPage.php" onclick="return confirmLeave()">Track Your Order!</a>
    <a href="aboutUs.html" onclick="return confirmLeave()">About Us</a>
    <a href="reviews.php" onclick="return confirmLeave()">Reviews</a>
    <a href="swf.php" onclick="return confirmLeave()">SplitWithFriends!</a>
  </div>
</header>
<form method="post">
<div class="main-layout-grid">
  <div class="discount-panel">
    <h2>Discounts</h2>
    <?php foreach ($discounts as $id => $disc): ?>
      <button type="submit" name="selected_discount" value="<?= $id ?>" class="discount-card">
        <h3><?= htmlspecialchars($disc['name']) ?> (₪<?= htmlspecialchars($disc['price']) ?>)</h3>
        <ul>
          <?php foreach ($disc['items'] as $item): ?>
            <li><?= htmlspecialchars($item['name']) ?> × <?= $item['count'] ?></li>
          <?php endforeach; ?>
        </ul>
      </button>
    <?php endforeach; ?>
  </div>
  <div class="order-panel">
    <input type="hidden" name="pizza_count" value="<?= $pizzaCount ?>">
    <input type="hidden" name="drink_count" value="<?= $drinkCount ?>">
    <input type="hidden" name="extra_count" value="<?= $extraCount ?>">
    <?php if ($selectedDiscount !== null): ?>
    <input type="hidden" name="selected_discount" value="<?= $selectedDiscount ?>">
    <?php endif; ?>
    
    <div class="section">
      <?php if ($showPizza): ?>
        <input type="hidden" name="pizzaToggle" value="on">
      <?php endif; ?>
      <label><input type="checkbox" name="pizzaToggle" <?= $showPizza ? 'checked' : '' ?> onchange="this.form.submit()"> Add Pizza</label>
      <?php if ($showPizza): ?>
        <?php for ($i = 1; $i <= $pizzaCount; $i++): ?>
          <p><strong>Pizza #<?= $i ?></strong> Size:
            <select name="pizza_size_<?= $i ?>">
              <?php foreach ($pizzaSizes as $s): ?>
                <option value="<?= $s['id'] ?>"><?= $s['item_name'] ?></option>
              <?php endforeach; ?>
            </select>
          </p>
          <div class="toppings-row">
            <?php foreach (["Top-Left", "Top-Right", "Bottom-Left", "Bottom-Right"] as $pos): ?>
              <label><?= $pos ?>:
                <select name="pizza_<?= $i ?>_<?= strtolower(str_replace('-', '', $pos)) ?>">
                  <?php foreach ($pizzaToppings as $t): ?>
                    <option value="<?= $t['id'] ?>"><?= $t['item_name'] ?></option>
                  <?php endforeach; ?>
                </select>
              </label>
            <?php endforeach; ?>
          </div>
          <hr>
        <?php endfor; ?>
        <button type="submit" name="add_more_pizza" value="1">Add More Pizza</button>
      <?php endif; ?>
    </div>

    <div class="section">
      <?php if ($showDrinks): ?>
        <input type="hidden" name="drinkToggle" value="on">
      <?php endif; ?>
      <label><input type="checkbox" name="drinkToggle" <?= $showDrinks ? 'checked' : '' ?> onchange="this.form.submit()"> Add Drink</label>
      <?php if ($showDrinks): ?>
        <?php for ($j = 1; $j <= $drinkCount; $j++): ?>
          <div class="drink-row">
            <label>Brand:
              <select name="drink_brand_<?= $j ?>">
                <option>Coca-Cola</option>
                <option>Pepsi</option>
                <option>Fanta</option>
                <option>Sprite</option>
                <option>Dr Pepper</option>
                <option>7UP</option>
              </select>
            </label>
            <label>Size:
              <select name="drink_size_<?= $j ?>">
                <?php foreach ($drinkSizes as $d): ?>
                  <option value="<?= $d['id'] ?>"><?= $d['item_name'] ?></option>
                <?php endforeach; ?>
              </select>
            </label>
          </div>
          <hr>
        <?php endfor; ?>
        <button type="submit" name="add_more_drink" value="1">Add More Drink</button>
      <?php endif; ?>
    </div>

    <div class="section">
      <?php if ($showExtras): ?>
        <input type="hidden" name="extraToggle" value="on">
      <?php endif; ?>
      <label><input type="checkbox" name="extraToggle" <?= $showExtras ? 'checked' : '' ?> onchange="this.form.submit()"> Add Extras</label>
      <?php if ($showExtras): ?>
        <?php for ($k = 1; $k <= $extraCount; $k++): ?>
          <div class="extra-row">
            <label>Extra #<?= $k ?>:
              <select name="extra_item_<?= $k ?>">
                <?php foreach ($extraItems as $x): ?>
                  <option value="<?= $x['id'] ?>"><?= $x['item_name'] ?></option>
                <?php endforeach; ?>
              </select>
            </label>
          </div>
          <hr>
        <?php endfor; ?>
        <button type="submit" name="add_more_extra" value="1">Add More Extra</button>
      <?php endif; ?>
    </div>

    <div style="display: flex; gap: 10px; margin-top: 20px;">
      <button type="submit" name="submit_final" value="1" formaction="payment.php">Submit Order</button>
      <button type="button" onclick="window.location.href='<?= $_SERVER['PHP_SELF'] ?>'" style="background-color: #ffeeee; border: 1px solid #ffaaaa;">Clear Order</button>
    </div>
  </div>
</div>
</form>
<script>
let isFormDirty = false;

window.addEventListener("DOMContentLoaded", () => {
  if (!sessionStorage.getItem("orderAlertShown")) {
    window.alert("Welcome to the order page: you can choose one discount per order.... if you want it click on it");
    sessionStorage.setItem("orderAlertShown", "true");
  }

  const inputs = document.querySelectorAll("input, select, textarea");
  inputs.forEach(el => {
    el.addEventListener("change", () => {
      isFormDirty = true;
    });
  });
});

function confirmLeave() {
  if (isFormDirty) {
    const answer = window.prompt("נראה ששינית את ההזמנה. אם אתה רוצה לעזוב, הקלד YES");
    return answer && answer.toLowerCase() === 'yes';
  }
  return true;
}
</script>
</body>
</html>

























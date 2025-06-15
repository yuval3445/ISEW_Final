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

// Fetch all items at once
$allItems = [];
$result = $conn->query("SELECT id, item_name, price, kind FROM items");
if ($result) {
  while ($row = $result->fetch_assoc()) {
    $allItems[$row['id']] = [
      'name' => $row['item_name'],
      'price' => $row['price'],
      'kind' => $row['kind']
    ];
  }
}

// Initialize variables
$orderTotal = 0;
$orderItems = [];
$discountApplied = null;
$discountAmount = 0;
$processedPizzas = []; 
$processedDrinks = []; 
$processedExtras = []; 

// Arrays to track items included in discount
$discountPizzaCount = 0;
$discountDrinkCount = 0;
$discountExtraCount = 0;
$discountToppingCount = 0;

// Check if we have order data
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || 
    (!isset($_POST['pizzaToggle']) && !isset($_POST['drinkToggle']) && 
     !isset($_POST['extraToggle']) && !isset($_POST['selected_discount']))) {
  // No order data, redirect to order page
  header("Location: full_pizza_order_page.php");
  exit;
}

// Process the order data from the previous page
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // Get discount information if applied
  if (isset($_POST['selected_discount']) && !empty($_POST['selected_discount'])) {
    $discountId = intval($_POST['selected_discount']);
    $discount_query = "SELECT name, price FROM discounts WHERE DiscountsID = ?";
    $stmt = $conn->prepare($discount_query);
    $stmt->bind_param("i", $discountId);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
      $discountName = $row['name'];
      $discountAmount = $row['price'];
      
      // Get the items included in this discount
      $discount_items_query = "SELECT di.itemID, di.numberOfItems, i.item_name, i.kind 
                              FROM item_discounts di 
                              JOIN items i ON di.itemID = i.id 
                              WHERE di.discountID = ?";
      $stmt = $conn->prepare($discount_items_query);
      $stmt->bind_param("i", $discountId);
      $stmt->execute();
      $discount_items_result = $stmt->get_result();

      $discountItems = [];
      $includedToppingsCount = 0;
      $requiredPizzaSizes = [];
      $requiredDrinkSizes = [];
      $requiredExtras = [];

      // Reset counters
      $discountPizzaCount = 0;
      $discountDrinkCount = 0;
      $discountExtraCount = 0;

      while ($item_row = $discount_items_result->fetch_assoc()) {
        // If this is the topping item (ID 18), store the number of included toppings
        if ($item_row['itemID'] == 18) {
          $includedToppingsCount = $item_row['numberOfItems'];
          $discountToppingCount = $item_row['numberOfItems'];
        } else {
          $discountItems[] = [
            'id' => $item_row['itemID'],
            'name' => $item_row['item_name'],
            'count' => $item_row['numberOfItems'],
            'kind' => $item_row['kind']
          ];
          
          // Track required items by kind and count items in discount
          $kind = strtolower($item_row['kind']);
          $name = strtolower($item_row['item_name']);
          
          // Check if this is a pizza size item
          if ($kind === 'pizzasize') {
            $requiredPizzaSizes[] = [
              'id' => $item_row['itemID'],
              'name' => $item_row['item_name'],
              'count' => $item_row['numberOfItems']
            ];
            $discountPizzaCount += $item_row['numberOfItems'];
          } 
          // Check if this is a base pizza item
          else if ($kind === 'base' && $name === 'pizza') {
            $discountPizzaCount += $item_row['numberOfItems'];
          } 
          // Check if this is a drink size item
          else if ($kind === 'drinkoption') {
            $requiredDrinkSizes[] = [
              'id' => $item_row['itemID'],
              'name' => $item_row['item_name'],
              'count' => $item_row['numberOfItems']
            ];
            $discountDrinkCount += $item_row['numberOfItems'];
          } 
          // Check if this is a base drink item
          else if ($kind === 'base' && $name === 'drinks') {
            $discountDrinkCount += $item_row['numberOfItems'];
          } 
          // Check if this is an extra item
          else if ($kind === 'extras') {
            $requiredExtras[] = [
              'id' => $item_row['itemID'],
              'name' => $item_row['item_name'],
              'count' => $item_row['numberOfItems']
            ];
            $discountExtraCount += $item_row['numberOfItems'];
          } 
          // Check if this is a base extras item
          else if ($kind === 'base' && $name === 'extras') {
            $discountExtraCount += $item_row['numberOfItems'];
          }
        }
      }

      // Count total items in order
      $totalPizzaCount = 0;
      for ($i = 1; isset($_POST["pizza_size_$i"]); $i++) {
        $totalPizzaCount++;
      }
      
      $totalDrinkCount = 0;
      for ($i = 1; isset($_POST["drink_size_$i"]); $i++) {
        $totalDrinkCount++;
      }
      
      $totalExtraCount = 0;
      for ($i = 1; isset($_POST["extra_item_$i"]); $i++) {
        $totalExtraCount++;
      }
      
      // Validate that the order matches the discount requirements
      $discountValid = true;
      $validationErrors = [];
      
      // Check pizza sizes
      if (!empty($requiredPizzaSizes)) {
        $pizzaSizeCounts = [];
        for ($i = 1; isset($_POST["pizza_size_$i"]); $i++) {
          $sizeId = intval($_POST["pizza_size_$i"]);
          if (!isset($pizzaSizeCounts[$sizeId])) {
            $pizzaSizeCounts[$sizeId] = 0;
          }
          $pizzaSizeCounts[$sizeId]++;
        }
        
        foreach ($requiredPizzaSizes as $required) {
          if (!isset($pizzaSizeCounts[$required['id']]) || $pizzaSizeCounts[$required['id']] < $required['count']) {
            $discountValid = false;
            $validationErrors[] = "Missing required pizza size: " . $required['name'] . " (need " . $required['count'] . ")";
          }
        }
      }
      
      if ($discountValid) {
        // Apply the discount
        $discountApplied = $discountName;
        
        // Start with discount amount
        $orderTotal = $discountAmount;

        // Add the discount package as a single item
        $orderItems[] = [
          'name' => $discountApplied . ' Package',
          'price' => $discountAmount,
          'isDiscount' => true
        ];

        // First, let's determine the actual number of pizzas in the order
        $totalPizzaCount = 0;
        for ($i = 1; isset($_POST["pizza_size_$i"]); $i++) {
          $totalPizzaCount++;
        }

        // Now explicitly check if there are additional pizzas beyond the discount limit
        $additionalPizzaCount = max(0, $totalPizzaCount - $discountPizzaCount);

        // Process pizzas in the discount
        $processedPizzas = [];
        $pizzaCount = 0;

        // Process pizzas up to discount limit
        for ($i = 1; $i <= $totalPizzaCount && $pizzaCount < $discountPizzaCount; $i++) {
          if (isset($_POST["pizza_size_$i"])) {
            $sizeId = intval($_POST["pizza_size_$i"]);
            
            // Get topping price from item ID 18
            $toppingPrice = isset($allItems[18]) ? $allItems[18]['price'] : 2; // Default to 2 if not found
            
            // Find cheese ID
            $cheeseId = 0;
            foreach ($allItems as $id => $item) {
              if ($item['kind'] === 'topping' && strtolower($item['name']) === 'cheese') {
                $cheeseId = $id;
                break;
              }
            }
            
            // Count different toppings (excluding cheese)
            $toppings = [];
            foreach (["topleft", "topright", "bottomleft", "bottomright"] as $pos) {
              $toppingKey = "pizza_{$i}_{$pos}";
              if (isset($_POST[$toppingKey])) {
                $toppingId = intval($_POST[$toppingKey]);
                // Only count if it's not cheese and not already counted
                if ($toppingId != $cheeseId && !in_array($toppingId, $toppings) && $toppingId > 0) {
                  $toppings[] = $toppingId;
                }
              }
            }
            
            // Calculate unique topping count and price
            $uniqueToppingCount = count($toppings);
            $extraToppings = max(0, $uniqueToppingCount - $includedToppingsCount);
            $extraToppingCost = $extraToppings * $toppingPrice;
            
            // Get topping names for display
            $toppingNames = [];
            foreach ($toppings as $toppingId) {
              if (isset($allItems[$toppingId])) {
                $toppingNames[] = $allItems[$toppingId]['name'];
              }
            }
            
            // Only add extra toppings cost if there are any
            if ($extraToppingCost > 0) {
              $orderItems[] = [
                'name' => 'Extra toppings (' . $extraToppings . ') for Pizza #' . $i,
                'price' => $extraToppingCost
              ];
              $orderTotal += $extraToppingCost;
            }
            
            // Add the pizza details as a note (not charged)
            $orderItems[] = [
              'name' => $allItems[$sizeId]['name'] . ' Pizza with ' . 
                       ($uniqueToppingCount > 0 ? implode(', ', $toppingNames) : 'no toppings'),
              'price' => 0,
              'isNote' => true,
              'inDiscount' => true
            ];
            
            // Mark this pizza as processed
            $processedPizzas[] = $i;
            $pizzaCount++;
          }
        }

        // Process additional pizzas (beyond discount limit) as regular items
        if ($additionalPizzaCount > 0) {
          // Start from the first unprocessed pizza
          for ($i = 1; isset($_POST["pizza_size_$i"]); $i++) {
            // Only process pizzas that weren't already included in the discount
            if (!in_array($i, $processedPizzas)) {
              $sizeId = intval($_POST["pizza_size_$i"]);
              if (isset($allItems[$sizeId])) {
                // Find the base pizza price
                $basePizzaPrice = 0;
                foreach ($allItems as $item) {
                  if ($item['kind'] === 'base' && strtolower($item['name']) === 'pizza') {
                    $basePizzaPrice = $item['price'];
                    break;
                  }
                }
                
                // Get topping price from item ID 18
                $toppingPrice = isset($allItems[18]) ? $allItems[18]['price'] : 2; // Default to 2 if not found
                
                // Find cheese ID
                $cheeseId = 0;
                foreach ($allItems as $id => $item) {
                  if ($item['kind'] === 'topping' && strtolower($item['name']) === 'cheese') {
                    $cheeseId = $id;
                    break;
                  }
                }
                
                // Count different toppings (excluding cheese)
                $toppings = [];
                foreach (["topleft", "topright", "bottomleft", "bottomright"] as $pos) {
                  $toppingKey = "pizza_{$i}_{$pos}";
                  if (isset($_POST[$toppingKey])) {
                    $toppingId = intval($_POST[$toppingKey]);
                    // Only count if it's not cheese and not already counted
                    if ($toppingId != $cheeseId && !in_array($toppingId, $toppings) && $toppingId > 0) {
                      $toppings[] = $toppingId;
                    }
                  }
                }
                
                // Calculate unique topping count and price
                $uniqueToppingCount = count($toppings);
                $toppingCost = $uniqueToppingCount * $toppingPrice;
                
                // Calculate total price including base price and toppings
                $totalPizzaPrice = $allItems[$sizeId]['price'] + $basePizzaPrice + $toppingCost;
                
                // Get topping names for display
                $toppingNames = [];
                foreach ($toppings as $toppingId) {
                  if (isset($allItems[$toppingId])) {
                    $toppingNames[] = $allItems[$toppingId]['name'];
                  }
                }
                
                $orderItems[] = [
                  'name' => 'ADDITIONAL: ' . $allItems[$sizeId]['name'] . ' Pizza' . 
                           ($uniqueToppingCount > 0 ? ' with ' . implode(', ', $toppingNames) : ''),
                  'price' => $totalPizzaPrice,
                  'isAdditional' => true
                ];
                $orderTotal += $totalPizzaPrice;
              }
            }
          }
        }
        
        // Process drinks in the discount
        $processedDrinkIndexes = [];
        $drinkCount = 0;

        // Process drinks up to discount limit
        for ($i = 1; $i <= $totalDrinkCount && $drinkCount < $discountDrinkCount; $i++) {
          if (isset($_POST["drink_size_$i"])) {
            $sizeId = intval($_POST["drink_size_$i"]);
            $brand = isset($_POST["drink_brand_$i"]) ? $_POST["drink_brand_$i"] : 'Generic';
            
            // Add the drink details as a note (not charged)
            $orderItems[] = [
              'name' => $brand . ' ' . (isset($allItems[$sizeId]) ? $allItems[$sizeId]['name'] : 'Unknown Size'),
              'price' => 0,
              'isNote' => true,
              'inDiscount' => true
            ];
            
            // Mark this drink as processed
            $processedDrinks[] = $i;
            $drinkCount++;
          }
        }

        // Calculate additional drinks
        $additionalDrinkCount = max(0, $totalDrinkCount - $discountDrinkCount);

        // Process additional drinks (beyond discount limit) as regular items
        for ($i = 1; isset($_POST["drink_size_$i"]); $i++) {
          if (!in_array($i, $processedDrinks)) {
            $sizeId = intval($_POST["drink_size_$i"]);
            $brand = isset($_POST["drink_brand_$i"]) ? $_POST["drink_brand_$i"] : 'Generic';
            if (isset($allItems[$sizeId])) {
              // Find the base drink
              $baseDrinkPrice = 0;
              foreach ($allItems as $item) {
                if ($item['kind'] === 'base' && strtolower($item['name']) === 'drinks') {
                  $baseDrinkPrice = $item['price'];
                  break;
                }
              }
              
              // Calculate total price including base price
              $totalDrinkPrice = $allItems[$sizeId]['price'] + $baseDrinkPrice;
              
              $orderItems[] = [
                'name' => 'ADDITIONAL: ' . $brand . ' ' . $allItems[$sizeId]['name'],
                'price' => $totalDrinkPrice,
                'isAdditional' => true
              ];
              $orderTotal += $totalDrinkPrice;
            }
          }
        }
        
        // Process extras in the discount
        $processedExtraIndexes = [];
        $extraCount = 0;

        // Process extras up to discount limit
        for ($i = 1; $i <= $totalExtraCount && $extraCount < $discountExtraCount; $i++) {
          if (isset($_POST["extra_item_$i"])) {
            $itemId = intval($_POST["extra_item_$i"]);
            
            // Add the extra details as a note (not charged)
            $orderItems[] = [
              'name' => isset($allItems[$itemId]) ? $allItems[$itemId]['name'] : 'Unknown Extra',
              'price' => 0,
              'isNote' => true,
              'inDiscount' => true
            ];
            
            // Mark this extra as processed
            $processedExtras[] = $i;
            $extraCount++;
          }
        }

        // Calculate additional extras
        $additionalExtraCount = max(0, $totalExtraCount - $discountExtraCount);

        // Process additional extras (beyond discount limit) as regular items
        for ($i = 1; isset($_POST["extra_item_$i"]); $i++) {
          if (!in_array($i, $processedExtras)) {
            $itemId = intval($_POST["extra_item_$i"]);
            if (isset($allItems[$itemId])) {
              // Find the base extras price
              $baseExtrasPrice = 0;
              foreach ($allItems as $item) {
                if ($item['kind'] === 'base' && strtolower($item['name']) === 'extras') {
                  $baseExtrasPrice = $item['price'];
                  break;
                }
              }
              
              // Calculate total price
              $totalExtrasPrice = $allItems[$itemId]['price'] + $baseExtrasPrice;
              
              $orderItems[] = [
                'name' => 'ADDITIONAL: ' . $allItems[$itemId]['name'],
                'price' => $totalExtrasPrice,
                'isAdditional' => true
              ];
              $orderTotal += $totalExtrasPrice;
            }
          }
        }
      } else {
        // Discount is not valid, process as regular order
        $discountApplied = null;
        $discountAmount = 0;
        
        // Add validation error as a note
        $orderItems[] = [
          'name' => 'Discount "' . $discountName . '" could not be applied:',
          'price' => 0,
          'isNote' => true
        ];
        
        foreach ($validationErrors as $error) {
          $orderItems[] = [
            'name' => '- ' . $error,
            'price' => 0,
            'isNote' => true
          ];
        }
      }
    }
    $stmt->close();
  } else {
    // Process all POST data to find items by name pattern
    foreach ($_POST as $key => $value) {
      // Check for pizza items by name pattern
      if (stripos($key, 'pizza') !== false && stripos($key, 'count') === false && stripos($key, 'Toggle') === false) {
        // Extract pizza size ID if it's in the format pizza_size_X
        if (preg_match('/pizza_size_(\d+)/', $key, $matches)) {
          $pizzaNum = $matches[1];
          // Track that we've processed this pizza
          $processedPizzas[] = $pizzaNum;
          
          $sizeId = intval($value);
          if (isset($allItems[$sizeId])) {
            // Find the base pizza price (ID 1 in your table)
            $basePizzaPrice = 0;
            foreach ($allItems as $item) {
              if ($item['kind'] === 'base' && strtolower($item['name']) === 'pizza') {
                $basePizzaPrice = $item['price'];
                break;
              }
            }
            
            // Get topping price from item ID 18
            $toppingPrice = isset($allItems[18]) ? $allItems[18]['price'] : 2; // Default to 2 if not found
            
            // Find cheese ID
            $cheeseId = 0;
            foreach ($allItems as $id => $item) {
              if ($item['kind'] === 'topping' && strtolower($item['name']) === 'cheese') {
                $cheeseId = $id;
                break;
              }
            }
            
            // Count different toppings (excluding cheese)
            $toppings = [];
            foreach (["topleft", "topright", "bottomleft", "bottomright"] as $pos) {
              $toppingKey = "pizza_{$pizzaNum}_{$pos}";
              if (isset($_POST[$toppingKey])) {
                $toppingId = intval($_POST[$toppingKey]);
                // Only count if it's not cheese and not already counted
                if ($toppingId != $cheeseId && !in_array($toppingId, $toppings) && $toppingId > 0) {
                  $toppings[] = $toppingId;
                }
              }
            }

            // Calculate unique topping count and price
            $uniqueToppingCount = count($toppings);
            $toppingCost = $uniqueToppingCount * $toppingPrice;

            // Calculate total price including base price and toppings
            $totalPizzaPrice = $allItems[$sizeId]['price'] + $basePizzaPrice + $toppingCost;

            // Get topping names for display
            $toppingNames = [];
            foreach ($toppings as $toppingId) {
              if (isset($allItems[$toppingId])) {
                $toppingNames[] = $allItems[$toppingId]['name'];
              }
            }

            $orderItems[] = [
              'name' => $allItems[$sizeId]['name'] . ' Pizza' . 
                       ($uniqueToppingCount > 0 ? ' with ' . implode(', ', $toppingNames) : ''),
              'price' => $totalPizzaPrice
            ];
            $orderTotal += $totalPizzaPrice;
          }
        }
      }
      
      // Check for drink items by name pattern
      if (stripos($key, 'drink_size') !== false) {
        $sizeId = intval($value);
        // Find the corresponding brand if available
        $drinkNum = '';
        if (preg_match('/drink_size_(\d+)/', $key, $matches)) {
          $drinkNum = $matches[1];
          // Track that we've processed this drink
          $processedDrinks[] = $drinkNum;
        }
        $brand = isset($_POST["drink_brand_$drinkNum"]) ? $_POST["drink_brand_$drinkNum"] : 'Generic';
        
        if (isset($allItems[$sizeId])) {
          // Find the base drink price
          $baseDrinkPrice = 0;
          foreach ($allItems as $item) {
            if ($item['kind'] === 'base' && strtolower($item['name']) === 'drinks') {
              $baseDrinkPrice = $item['price'];
              break;
            }
          }
          
          // Calculate total price including base price
          $totalDrinkPrice = $allItems[$sizeId]['price'] + $baseDrinkPrice;
          
          $orderItems[] = [
            'name' => $brand . ' ' . $allItems[$sizeId]['name'] . ' (includes base price)',
            'price' => $totalDrinkPrice
          ];
          $orderTotal += $totalDrinkPrice;
        }
      }
      
      // Check for extra items by name pattern
      if (stripos($key, 'extra_item') !== false) {
        // Extract extra number
        if (preg_match('/extra_item_(\d+)/', $key, $matches)) {
          $extraNum = $matches[1];
          // Track that we've processed this extra
          $processedExtras[] = $extraNum;
        }
        
        $itemId = intval($value);
        if (isset($allItems[$itemId])) {
          // Find the base extras price
          $baseExtrasPrice = 0;
          foreach ($allItems as $item) {
            if ($item['kind'] === 'base' && strtolower($item['name']) === 'extras') {
              $baseExtrasPrice = $item['price'];
              break;
            }
          }
          
          // Calculate total price including base price
          $totalExtrasPrice = $allItems[$itemId]['price'] + $baseExtrasPrice;
          
          $orderItems[] = [
            'name' => $allItems[$itemId]['name'] . ' (includes base price)',
            'price' => $totalExtrasPrice
          ];
          $orderTotal += $totalExtrasPrice;
        }
      }
    }
    
    // Keep the existing processing code as a fallback but use the cached items
    // Process pizzas
    if (isset($_POST['pizzaToggle']) && empty($processedPizzas)) {
      $pizzaCount = isset($_POST['pizza_count']) ? intval($_POST['pizza_count']) : 0;
      for ($i = 1; $i <= $pizzaCount; $i++) {
        if (isset($_POST["pizza_size_$i"])) {
          // Add this pizza number to processed list
          $processedPizzas[] = $i;
          
          $sizeId = intval($_POST["pizza_size_$i"]);
          if (isset($allItems[$sizeId])) {
            // Find the base pizza price
            $basePizzaPrice = 0;
            foreach ($allItems as $item) {
              if ($item['kind'] === 'base' && strtolower($item['name']) === 'pizza') {
                $basePizzaPrice = $item['price'];
                break;
              }
            }
            
            // Get topping price from item ID 18
            $toppingPrice = isset($allItems[18]) ? $allItems[18]['price'] : 2; // Default to 2 if not found
            
            // Find cheese ID
            $cheeseId = 0;
            foreach ($allItems as $id => $item) {
              if ($item['kind'] === 'topping' && strtolower($item['name']) === 'cheese') {
                $cheeseId = $id;
                break;
              }
            }
            
            // Count different toppings (excluding cheese)
            $toppings = [];
            foreach (["topleft", "topright", "bottomleft", "bottomright"] as $pos) {
              $toppingKey = "pizza_{$i}_{$pos}";
              if (isset($_POST[$toppingKey])) {
                $toppingId = intval($_POST[$toppingKey]);
                // Only count if it's not cheese and not already counted
                if ($toppingId != $cheeseId && !in_array($toppingId, $toppings)) {
                  $toppings[] = $toppingId;
                }
              }
            }
            
            // Calculate unique topping count and price
            $uniqueToppingCount = count($toppings);
            $toppingCost = $uniqueToppingCount * $toppingPrice;
            
            // Calculate total price including base price and toppings
            $totalPizzaPrice = $allItems[$sizeId]['price'] + $basePizzaPrice + $toppingCost;
            
            $orderItems[] = [
              'name' => $allItems[$sizeId]['name'] . ' Pizza with ' . $uniqueToppingCount . ' different toppings',
              'price' => $totalPizzaPrice
            ];
            $orderTotal += $totalPizzaPrice;
          }
        }
      }
    }
    
    // Process drinks
    if (isset($_POST['drinkToggle']) && empty($processedDrinks)) {
      $drinkCount = isset($_POST['drink_count']) ? intval($_POST['drink_count']) : 0;
      for ($j = 1; $j <= $drinkCount; $j++) {
        if (isset($_POST["drink_size_$j"])) {
          $sizeId = intval($_POST["drink_size_$j"]);
          $brand = isset($_POST["drink_brand_$j"]) ? $_POST["drink_brand_$j"] : 'Generic';
          if (isset($allItems[$sizeId])) {
            // Find the base drink price
            $baseDrinkPrice = 0;
            foreach ($allItems as $item) {
              if ($item['kind'] === 'base' && strtolower($item['name']) === 'drinks') {
                $baseDrinkPrice = $item['price'];
                break;
              }
            }
            
            // Calculate total price including base price
            $totalDrinkPrice = $allItems[$sizeId]['price'] + $baseDrinkPrice;
            
            $orderItems[] = [
              'name' => $brand . ' ' . $allItems[$sizeId]['name'] . ' (includes base price)',
              'price' => $totalDrinkPrice
            ];
            $orderTotal += $totalDrinkPrice;
          }
        }
      }
    }
    
    // Process extras
    if (isset($_POST['extraToggle']) && empty($processedExtras)) {
      $extraCount = isset($_POST['extra_count']) ? intval($_POST['extra_count']) : 0;
      for ($k = 1; $k <= $extraCount; $k++) {
        if (isset($_POST["extra_item_$k"])) {
          $itemId = intval($_POST["extra_item_$k"]);
          if (isset($allItems[$itemId])) {
            // Find the base extras price
            $baseExtrasPrice = 0;
            foreach ($allItems as $item) {
              if ($item['kind'] === 'base' && strtolower($item['name']) === 'extras') {
                $baseExtrasPrice = $item['price'];
                break;
              }
            }
            
            // Calculate total price including base price
            $totalExtrasPrice = $allItems[$itemId]['price'] + $baseExtrasPrice;
            
            $orderItems[] = [
              'name' => $allItems[$itemId]['name'] . ' (includes base price)',
              'price' => $totalExtrasPrice
            ];
            $orderTotal += $totalExtrasPrice;
          }
        }
      }
    }
  }
}

// Process payment if submitted
$paymentSuccess = false;
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['process_payment'])) {
  $cardNumber = $_POST['card_number'] ?? '';
  $cardName = $_POST['card_name'] ?? '';
  $expiryDate = $_POST['expiry_date'] ?? '';
  $cvv = $_POST['cvv'] ?? '';
  $customerId = $_POST['customer_id'] ?? '';
  
  // Simple validation
  if (empty($cardNumber) || empty($cardName) || empty($expiryDate) || empty($cvv)) {
    $errorMessage = 'All payment fields are required';
  } elseif (strlen($cardNumber) < 13 || strlen($cardNumber) > 19) {
    $errorMessage = 'Invalid card number';
  } elseif (strlen($cvv) < 3 || strlen($cvv) > 4) {
    $errorMessage = 'Invalid CVV';
  } else {
    // In a real application, you would process the payment through a payment gateway
    // For this demo, we'll just simulate a successful payment
    $paymentSuccess = true;
    
    // Save order to database
    $orderTime = date('Y-m-d H:i:s');
    $stage = 'Ordered!'; // Set initial stage to None
    
    // Insert order into database
    $order_query = "INSERT INTO Orders (buyerID, CustomerName, orderTime, stage) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($order_query);
    $stmt->bind_param("ssss", $customerId, $cardName, $orderTime, $stage);
    $stmt->execute();
    $orderId = $conn->insert_id;
    
    // Insert order items into ItemOrder table
    $item_query = "INSERT INTO ItemOrder (orderID, itemID, numberOfItem) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($item_query);
    
    // Process pizzas
    if (isset($_POST['pizzaToggle'])) {
      for ($i = 1; isset($_POST["pizza_size_$i"]); $i++) {
        $sizeId = intval($_POST["pizza_size_$i"]);
        $numberOfItem = 1;
        $stmt->bind_param("iii", $orderId, $sizeId, $numberOfItem);
        $stmt->execute();
        
        // Process toppings for this pizza
        $toppings = [];
        if (isset($_POST["pizza_{$i}_topleft"])) $toppings[] = intval($_POST["pizza_{$i}_topleft"]);
        if (isset($_POST["pizza_{$i}_topright"])) $toppings[] = intval($_POST["pizza_{$i}_topright"]);
        if (isset($_POST["pizza_{$i}_bottomleft"])) $toppings[] = intval($_POST["pizza_{$i}_bottomleft"]);
        if (isset($_POST["pizza_{$i}_bottomright"])) $toppings[] = intval($_POST["pizza_{$i}_bottomright"]);
        
        // Insert unique toppings
        $uniqueToppings = array_unique($toppings);
        foreach ($uniqueToppings as $toppingId) {
          if ($toppingId > 0) {
            $numberOfItem = 1;
            $stmt->bind_param("iii", $orderId, $toppingId, $numberOfItem);
            $stmt->execute();
          }
        }
      }
    }
    
    // Process drinks
    if (isset($_POST['drinkToggle'])) {
      for ($i = 1; isset($_POST["drink_size_$i"]); $i++) {
        $drinkId = intval($_POST["drink_size_$i"]);
        $numberOfItem = 1;
        $stmt->bind_param("iii", $orderId, $drinkId, $numberOfItem);
        $stmt->execute();
      }
    }
    
    // Process extras
    if (isset($_POST['extraToggle'])) {
      for ($i = 1; isset($_POST["extra_item_$i"]); $i++) {
        $extraId = intval($_POST["extra_item_$i"]);
        $numberOfItem = 1;
        $stmt->bind_param("iii", $orderId, $extraId, $numberOfItem);
        $stmt->execute();
      }
    }
    
    // Redirect to confirmation page
    header("Location: order_confirmation.php?order_id=$orderId");
    exit;
  }
}

// Check if split check is requested
$showSplitCheck = false;
$splitAmount = 0;
$numPeople = 2; // Default value
$splitAmountRounded = 0;
$remainder = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['calculate_split'])) {
  $showSplitCheck = true;
  $numPeople = isset($_POST['num_people']) ? intval($_POST['num_people']) : 2;
  
  // Ensure number of people is at least 2
  if ($numPeople < 2) {
    $numPeople = 2;
  }
  
  // Calculate the split amount
  $splitAmount = $orderTotal / $numPeople;
  $splitAmountRounded = ceil($splitAmount * 100) / 100; // Round up to nearest cent
  
  // Calculate if there's any remainder due to rounding
  $remainder = ($splitAmountRounded * $numPeople) - $orderTotal;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Payment - Pizza Ordering System</title>
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
      grid-template-columns: 1fr 1fr;
      gap: 20px;
      max-width: 1200px;
      margin-left: auto;
      margin-right: auto;
    }

    .order-summary, .payment-form, .split-check {
      background-color: rgba(255, 255, 255, 0.9);
      border-radius: 20px;
      padding: 20px;
      box-shadow: 0 0 15px rgba(0,0,0,0.2);
    }

    .order-item {
      display: flex;
      justify-content: space-between;
      margin-bottom: 10px;
      padding-bottom: 10px;
      border-bottom: 1px solid #eee;
    }

    .total-row {
      font-weight: bold;
      margin-top: 20px;
      padding-top: 10px;
      border-top: 2px solid #ddd;
    }

    .discount-row {
      color: green;
    }

    .form-group {
      margin-bottom: 15px;
    }

    label {
      display: block;
      margin-bottom: 5px;
      font-weight: bold;
    }

    input[type="text"], input[type="number"] {
      width: 100%;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 16px;
    }

    .card-row {
      display: flex;
      gap: 15px;
    }

    .expiry-cvv {
      display: flex;
      gap: 15px;
    }

    .expiry-cvv div {
      flex: 1;
    }

    button {
      background-color: #4CAF50;
      color: white;
      border: none;
      padding: 12px 20px;
      border-radius: 4px;
      cursor: pointer;
      font-size: 16px;
      margin-top: 10px;
    }

    button:hover {
      background-color: #45a049;
    }

    .error-message {
      color: red;
      margin-bottom: 15px;
    }

    .note-item {
      color: #666;
      font-style: italic;
      font-size: 0.9em;
      border-bottom: none;
      padding-bottom: 0;
      margin-bottom: 5px;
      padding-left: 15px;
    }

    .discount-item {
      font-weight: bold;
      color: #4CAF50;
    }

    .separator {
      margin-top: 20px;
      padding-top: 10px;
      border-top: 2px dashed #4CAF50;
      font-weight: bold;
      color: #4CAF50;
    }

    .split-button {
      background-color: #2196F3;
      margin-left: 10px;
    }

    .split-button:hover {
      background-color: #0b7dda;
    }

    .back-button {
      background-color: #607D8B;
    }

    .back-button:hover {
      background-color: #455A64;
    }

    .person-card {
      margin-top: 20px;
      padding: 15px;
      background-color: #f5f5f5;
      border-radius: 10px;
      border-left: 4px solid #2196F3;
    }

    .split-row {
      color: #2196F3;
      font-weight: bold;
      font-size: 1.2em;
    }

    /* Media query for screens smaller than 1000px */
    @media (max-width: 1000px) {
      .main-container {
        grid-template-columns: 1fr;
        grid-template-rows: auto auto;
      }
      
      .order-summary {
        grid-row: 1;
      }
      
      .payment-form, .split-check {
        grid-row: 2;
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
  </header>

  <div class="main-container">
    <div class="order-summary">
      <h2 id="order-summary-heading"></h2>
      <script>
        document.getElementById('order-summary-heading').innerHTML = 'Order Summary';
      </script> 
      <?php if ($discountApplied): ?>
        <div class="order-item discount-item">
          <span><?= htmlspecialchars($discountApplied) ?> Package</span>
          <span>₪<?= number_format($discountAmount, 2) ?></span>
        </div>
      <?php endif; ?>
      <?php foreach ($orderItems as $item): ?>
        <?php if (isset($item['isNote']) && $item['isNote']): ?>
          <div class="order-item note-item">
            <span><?= htmlspecialchars($item['name']) ?></span>
            <span>Included</span>
          </div>
        <?php elseif (!isset($item['isDiscount']) || !$item['isDiscount']): ?>
          <div class="order-item">
            <span><?= htmlspecialchars($item['name']) ?></span>
            <span>₪<?= number_format($item['price'], 2) ?></span>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
      
      <div class="order-item total-row">
        <span>Total</span>
        <span>₪<?= number_format($orderTotal, 2) ?></span>
      </div>
    </div>

    <?php if ($showSplitCheck): ?>
      <div class="split-check">
        <h2>Split Check</h2>
        
        <div class="split-details">
          <p>Splitting the bill between <strong><?= $numPeople ?></strong> people:</p>
          
          <?php for ($i = 1; $i <= $numPeople; $i++): ?>
            <div class="person-card">
              <h4>Person #<?= $i ?></h4>
              <div class="order-item split-row">
                <span>Amount to pay:</span>
                <span>₪<?= number_format($splitAmountRounded, 2) ?></span>
              </div>
            </div>
          <?php endfor; ?>
          
          <?php if (abs($remainder) > 0.01): ?>
            <p style="color: #FF9800; font-style: italic; margin-top: 15px;">
              Note: Due to rounding, the total collected will be ₪<?= number_format($splitAmountRounded * $numPeople, 2) ?>, 
              which is ₪<?= number_format(abs($remainder), 2) ?> <?= $remainder > 0 ? 'more' : 'less' ?> than the actual bill.
            </p>
          <?php endif; ?>
        </div>
        
        <form method="post">
          <!-- Preserve order data -->
          <?php foreach ($_POST as $key => $value): ?>
            <?php if ($key !== 'calculate_split' && $key !== 'num_people'): ?>
              <?php if (is_array($value)): ?>
                <?php foreach ($value as $k => $v): ?>
                  <input type="hidden" name="<?= htmlspecialchars($key) ?>[<?= htmlspecialchars($k) ?>]" value="<?= htmlspecialchars($v) ?>">
                <?php endforeach; ?>
              <?php else: ?>
                <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
              <?php endif; ?>
            <?php endif; ?>
          <?php endforeach; ?>
          
          <button type="submit" class="back-button">Back to Payment</button>
        </form>
      </div>
    <?php else: ?>
      <div class="payment-form">
        <h2>Payment Details</h2>
        
        <?php if ($errorMessage): ?>
          <div class="error-message"><?= htmlspecialchars($errorMessage) ?></div>
        <?php endif; ?>
        
        <form method="post">
          <!-- Preserve order data -->
          <?php foreach ($_POST as $key => $value): ?>
            <?php if (is_array($value)): ?>
              <?php foreach ($value as $k => $v): ?>
                <input type="hidden" name="<?= htmlspecialchars($key) ?>[<?= htmlspecialchars($k) ?>]" value="<?= htmlspecialchars($v) ?>">
              <?php endforeach; ?>
            <?php else: ?>
              <input type="hidden" name="<?= htmlspecialchars($key) ?>" value="<?= htmlspecialchars($value) ?>">
            <?php endif; ?>
          <?php endforeach; ?>
          
          <div class="form-group">
            <label for="card_name">Name on Card</label>
            <input type="text" id="card_name" name="card_name" placeholder="John Doe" required>
          </div>
          
          <div class="form-group">
            <label for="customer_id">Customer ID</label>
            <input type="text" id="customer_id" name="customer_id" placeholder="Optional ID number">
          </div>
          
          <div class="form-group">
            <label for="card_number">Card Number</label>
            <input type="text" id="card_number" name="card_number" placeholder="1234 5678 9012 3456" maxlength="19" required>
          </div>
          
          <div class="form-group expiry-cvv">
            <div>
              <label for="expiry_date">Expiry Date</label>
              <input type="text" id="expiry_date" name="expiry_date" placeholder="MM/YY" maxlength="5" required>
            </div>
            <div>
              <label for="cvv">CVV</label>
              <input type="text" id="cvv" name="cvv" placeholder="123" maxlength="4" required>
            </div>
          </div>
          
          <div style="display: flex; gap: 10px; align-items: center;">
            <button type="submit" name="process_payment">Pay Now ₪<?= number_format($orderTotal, 2) ?></button>
          </div>
        </form>
      </div>
    <?php endif; ?>
  </div>
  </div>
</body>
</html>









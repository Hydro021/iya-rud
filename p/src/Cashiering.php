<?php
session_start();

$email = $_SESSION["email"];

// Database connection
$servername = "localhost";
$username = "root"; // Change this to your database username
$password = ""; // Change this to your database password
$dbname = "admin"; // Change this to your database name 

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve user information from the database
$firstname = "User"; // Default value
$gender = ""; // Default value

$stmt = $conn->prepare("SELECT firstname, gender FROM acc WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $firstname = $row["firstname"];
    $gender = $row["gender"];
}

$productImage = '';
$errorMsg = '';

// Check if search query is submitted
if(isset($_POST['search'])) {
    $search_query = htmlspecialchars($_POST['search']);
    
    // Prepare and execute query to fetch product details based on search query
    $stmt = $conn->prepare("SELECT photo, price, product_name FROM products WHERE product_name = ?");
    $stmt->bind_param("s", $search_query);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $productImage = $row["photo"];
        $product_name = $row["product_name"];
        $price = $row["price"];
    } else {
        $errorMsg = 'Product not found.';
    }
}

// Handle addition to cart
if(isset($_POST['search']) && isset($_POST['quantity']) && isset($_POST['customer_name'])) {
    // Retrieve form data
    $product_name = $_POST['search'];
    $price = isset($price) ? $price : 0; // Default price to 0 if not set
    $quantity = $_POST['quantity'];
    $customer_name = $_POST['customer_name'];
    $total_price = isset($_POST['totalprice']) ? $_POST['totalprice'] : 0; // Retrieve total price from form, default to 0 if not set

     // Insert into the cart table
     $stmt = $conn->prepare("INSERT INTO cart (email, username, product, price, quantity, totalprice) VALUES (?, ?, ?, ?, ?, ?)");
     $stmt->bind_param("ssssss", $email, $customer_name, $product_name, $price, $quantity, $total_price);
     $stmt->execute();

    // Check if the insertion was successful
    if ($stmt->affected_rows > 0) {
        // Cart item added successfully
        echo "";
    }
}

$cartItems = array();
$stmt = $conn->prepare("SELECT email, username, product, price, quantity FROM cart WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $cartItems[] = $row;
    }
}
// Check if the product name and email are provided
if (isset($_POST['product']) && isset($_SESSION['email'])) {
    // Get the product name and user's email from the request
    $productName = $_POST['product'];
    $email = $_SESSION['email'];

    // Database connection and deletion process
    $servername = "localhost";
    $username = "root"; // Change this to your database username
    $password = ""; // Change this to your database password
    $dbname = "admin"; // Change this to your database name

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Prepare and execute the SQL statement to delete the item from the cart
    $stmt = $conn->prepare("DELETE FROM cart WHERE product = ? AND email = ?");
    $stmt->bind_param("ss", $productName, $email);
    $stmt->execute();

    // Check if deletion was successful
    if ($stmt->affected_rows > 0) {
        // Deletion successful
        // You can echo a message here if you want
    } else {
        // Deletion failed
        // You can handle this case accordingly
    }

    // Close the database connection
    $stmt->close();
    $conn->close();
}

// Fetch cart items from the database
$cartItems = array();
$stmt = $conn->prepare("SELECT product, price, quantity FROM cart WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        // Calculate total price for each item
        $totalPrice = floatval($row['price']) * intval($row['quantity']);
        // Add total price to the row
        $row['totalprice'] = $totalPrice;
        // Add the row to cartItems array
        $cartItems[] = $row;
    }
}


// Calculate total amount
$totalAmount = 0;
foreach ($cartItems as $item) {
    $totalAmount += $item['totalprice'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cashiering</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
    <style>
       @import url('https://fonts.googleapis.com/css2?family=Madelyn&display=swap');

.admin-btn {
    background-color: white;
}
h1 {
    font-family: 'Madelyn', cursive;
    font-style: italic;
}
.welcome-text {
    color: black;
    font-size: 2rem;
    text-align: center;
}

.center-logo {
    width: 200px; /* Set the width of the logo */
    display: flex;
    justify-content: center;
    align-items: center;
    margin-bottom: 20px; /* Add margin for spacing */
}

/* Modal styles */
.modal {
    display: none;
    position: absolute;
    z-index: 1001;
    top: calc(100% + 10px); /* Adjust the distance from the top of the button */
    left: 50%; /* Center horizontally */
    transform: translateX(-50%);
    background-color:#d3d3d3;
    padding: 20px;
    border: 1px solid #888;
    border-radius: 20px;
    text-align: center;
    width:300px;
}

/* Style for the email */
#userEmail {
    border-bottom: 2px solid none;
}
#logoutBtn {
    border-top: 2px solid none; 
    color:red;
}
.border-line {
    border-bottom: 1px solid black; /* Add a bottom border */
    margin: 20px 0; /* Adjust margin for spacing */
}

/* Style for the greeting and logout button */
#greeting,
#logoutBtn {
    margin-top: 16px;
}

/* Style for the admin icon */
#adminIcon {
    font-size: 40px;
    color: #333;
    margin-bottom: 20px;
}
.confirmation-modal {
    position: fixed;
    background-color: #ffffff;
    border: 1px solid #ccc;
    border-radius: 8px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    padding: 20px;
    z-index: 9999;
}

.confirmation-content {
    text-align: center;
}

.btn-container {
    display: flex;
    justify-content: center;
    margin-top: 20px;
}

.confirmation-modal button {
    padding: 10px 20px;
    margin: 0 10px;
    cursor: pointer;
    border: none;
    border-radius: 4px;
}

#confirmLogout {
    background-color: #4CAF50; /* Green */
    color: white;
}

#cancelLogout {
    background-color: #f44336; /* Red */
    color: white;
}

.left-navigation {
position: absolute;
top: calc(100% + 1px);
left: 0;
height: 480px;
display: flex;
flex-direction: column;
align-items: center;
border-right: 2px solid black; /* Add border on the right side */
padding-right: 10px; /* Add padding to separate from the main content */
background-color: black;
width: 200px;
transition: width 0.5s, opacity 0.5s; /* Add transition for smooth animation */
opacity: 1; /* Initially visible */
}

.left-navigation.minimized {
width: 0; /* Width when minimized */
overflow: hidden; /* Hide the minimized navigation */
opacity: 0; /* Make it invisible */
}

.main-content {
transition: margin-left 0.5s; /* Remove width transition */
}

.main-content.expanded {
margin-left: -202px; /* Adjusted margin to account for the left navigation */
}
.main-content.expanded h2{
margin-left:110px;
}
.main-content.expanded .slideshow-container{
padding-left:-95px;
width:86.4%;
}

.left-navigation-item {
    margin-bottom: -10px;
    font-size: 20px;
    color: white;
    cursor: pointer;
    display: flex;
    align-items: center;
    margin-top:90px;
    margin-left:10px;
}

.left-navigation-item i {
    margin-right: 8px; /* Adjust margin for spacing */
}

.btn{
    border:5px solid black;
    background:black;
    color:white;
    width:2%;
    margin-left:200.2px;
    margin-top:-230px;
    border-top-right-radius: 15px;
    border-bottom-right-radius: 15px;
    font-size:25px;
}
.main-content {
    position: relative; /* Add relative positioning */
}

#productDisplay {
    width: 150px; /* Adjust width as needed */
    height: 150px; /* Adjust height as needed */
    margin: 0 auto; /* Center horizontally */
    text-align: center; /* Center the content */
    margin-top: 20px; /* Add margin for spacing */
    border: 5px solid black; /* Add border */
    background-size: cover; /* Cover the entire container */
    background-position: center; /* Center the background image */
}

.product-image {
    display: none; /* Hide the image element */
}

/* Product search input */
#productNameInput,
#usernameInput {
    width: 300px; /* Adjust width as needed */
    padding: 2px; /* Add padding for better appearance */
    font-size: 16px; /* Adjust font size */
    border: 2px solid #ccc; /* Add border */
    border-radius: 5px; /* Add border radius for rounded corners */
    margin: 20px auto; /* Center horizontally and add margin for spacing */
    display: block; /* Ensure the input box is displayed as a block element */
    box-sizing: border-box; /* Include padding and border in the element's total width and height */
    margin-left:247px;
}
.current-date-time{
    margin-left:247px;
    border:2px solid black;
    width: 300px;
    margin-top:8px;
}
#searchMessage {
    position: absolute;
    top: calc(100% + 5px); /* Position the error message below the input */
    left: 0;
    width: 100%;
    text-align: center; /* Center the text horizontally */
    margin-top:1px;
}
.container{
    margin-left:-240px;
}
p{
    width: 300px; /* Adjust width as needed */
    padding: 2px; /* Add padding for better appearance */
    font-size: 16px; /* Adjust font size */
    border: 2px solid #ccc; /* Add border */
    border-radius: 5px; /* Add border radius for rounded corners */
    margin: 20px auto; /* Center horizontally and add margin for spacing */
    display: block; /* Ensure the input box is displayed as a block element */
    box-sizing: border-box; /* Include padding and border in the element's total width and height */
    margin-left:490px;
}
#quantityInput ,
#customerNameInput 
{
    width: 300px; /* Adjust the width as needed */
    padding: 2px; /* Add padding for better appearance */
    font-size: 16px; /* Adjust font size */
    border: 2px solid #ccc; /* Add border */
    border-radius: 5px; /* Add border radius for rounded corners */
    margin: 20px auto; /* Center horizontally and add margin for spacing */
    display: block; /* Ensure the input box is displayed as a block element */
    box-sizing: border-box; /* Include padding and border in the element's total width and height */
    margin-left: 250px; /* Align with the product name input */
}
/* Button style for Add to Cart */
.product-details button,
 #saveSaleBtn{
    background-color: #4CAF50; /* Green */
    color: white;
    padding: 5px 10px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    font-size: 16px;
    margin-top: 10px; /* Add margin for spacing */
    display: block; /* Ensure the button is displayed as a block element */
    margin-left: 448px; /* Align with the product name input */
}
#shoppingCart {
    max-width: 600px; /* Set a maximum width for the shopping cart */
    background-color: white; /* Set background color */
    border: 1px solid #ccc; /* Add a border */
    border-radius: 5px; /* Add border radius for rounded corners */
    padding: 10px; /* Add padding for spacing */
    margin-left:580px;
    
}

/* Styling for table headers */
#shoppingCart th {
    background-color: #f2f2f2; /* Light gray background */
    padding: 8px; /* Add padding */
    text-align: left; /* Align text to the left */
}

/* Styling for table rows */
#shoppingCart td {
    padding: 8px; /* Add padding */
    border: 1px solid black; /* Add black border around each cell */
}

/* Alternating row colors */
#shoppingCart tr:nth-child(even) {
    background-color: #f9f9f9; /* Lighter gray background for even rows */
}

/* Hover effect for table rows */
#shoppingCart tr:hover {
    background-color: #f2f2f2; /* Light gray background on hover */
}

</style>
</head>
<body>
   <nav class="bg-cover bg-center bg-no-repeat bg-opacity-80 border-b-4 border-gray-700 flex justify-between items-center p-4 relative" style="background-image: url('background.png');">
        <div class="flex items-center text-white mx-4">
            <img src="new logo.png" alt="logo" class="w-16 mr-2">
            <h1 class="text-2xl text-white">R.V.M</h1>
        </div>

        <div class="left-navigation">
        <div class="left-navigation-item">
    <a href="Dashboard.php" class="text-white">
        <i class="fas fa-tachometer-alt"></i>
        Dashboard
    </a>
</div>
            <div class="left-navigation-item">
            <a href="Cashiering.php" class="text-white">
                <i class="fas fa-cash-register"></i>
                Cashiering
            </div>
            <div class="left-navigation-item">
            <a href="ProductList.php" class="text-white">
                <i class="fas fa-box"></i>
                Product Lists
            </div>
            <div class="left-navigation-item">
            <a href="Settings.php" class="text-white">
                <i class="fas fa-cog"></i>
                Settings
            </div>
        </div>
        <!-- Administrator Button -->
        <div class="flex items-center relative mx-10"> <!-- Make the container relative -->
            <h2 id="adminBtn" class="flex items-center text-black font-semibold py-2 px-7 transition duration-300 rounded-full admin-btn">
                <i class="fas fa-user-tie mr-2"></i>
                Administrator
                <button id="toggleModalBtn" class="flex items-center justify-center bg-transparent border-none"><i id="caretIcon" class="fas fa-caret-down ml-2"></i></button>
            </h2>
            <!-- Administrator Modal -->
            <div id="adminModal" class="modal -ml-0">
                <!-- User's email -->
                <h3 id="userEmail" class="text-bold mb-2"><?php echo $email; ?></h3>
                <div class="border-line"></div>  
                <!-- Greeting message -->
                <div class="bg-white pt-3 pb-3">
                    <i id="adminIcon" class="fas fa-user-tie"></i> <!-- Admin icon -->
                    <h2 id="greeting" class="text-2xl mb-4">Hello, <?php echo $firstname; ?></h2>
                    <!-- Manage account button -->
                   <!-- Manage account button -->
<a href="Manage.php" class="bg-blue-500 text-white text-bold py-2 px-4 rounded-full hover:bg-blue-600">Manage Account</a>

                </div>
                <div class="border-line"></div>
                <!-- Logout button -->
                <button id="logoutBtn" class="text-bold py-2 px-4 float-right">Log Out</button>
            </div>
        </div>
    </nav>

    <div class="main-content">
    <div class="current-date-time">
    <?php
    // Get the current date and time
    $currentDateTime = date('Y-m-d H:i:s');
    echo $currentDateTime;
    ?>
</div>
<form method="post" action="" class="search-form">
        <!-- Input for searching product -->
        <div style="position: relative;">
        <input type="text" name="search" id="productNameInput" placeholder="Product Name" value="<?php echo isset($product_name) ? $product_name : ''; ?>" onkeyup="searchProduct()">
        </div>
    </form>
<div class="container">
    <div id="productDisplay" class="photo-container">
        <!-- Echo the product image if found -->
        <?php
        if($productImage !== '') {
            echo '<img id="productImage" class="product-image" src="' . $productImage . '" alt="Product Image">';
        }
        ?>
        <!-- Error message container -->
        <div id="searchMessage" class="text-red-500 text-sm absolute bottom-0 mt-2"><?php echo $errorMsg; ?></div>
    </div>
    <!-- Product details section -->
    <div class="product-details">
        <p><?php echo isset($price) ? '₱' . number_format($price, 2) : 'Price not available'; ?></p>
    </div>
</div>
<!-- Product details section -->
<div class="product-details">
    <form id="addToCartForm" method="post" action="">
        <input type="hidden" name="search" value="<?php echo isset($search_query) ? htmlspecialchars($search_query) : ''; ?>">
        <input type="number" name="quantity" id="quantityInput" placeholder="Quantity" min="1" required>
        <input type="text" name="customer_name" id="customerNameInput" placeholder="Customer Name" required>
        <input type="hidden" name="totalprice" value="0">
        <button type="button" id="addToCartBtn">Add to Cart</button>
    </form>
</div>


<!-- HTML for the element to be positioned in the top right corner -->
<div id="topRightElement">
    <div id="shoppingCart" class="absolute -top-1 bg-white border rounded p-4"> <!-- Remove 'hidden' class -->
    <table id="cartTable" class="table-auto">
    <thead>
        <tr>
            <th class="px-4 py-2">Product Name</th>
            <th class="px-4 py-2">Price</th>
            <th class="px-4 py-2">Quantity</th>
            <th class="px-4 py-2">Total Price</th> <!-- New column -->
        </tr>
    </thead>
    <tbody id="cartItems">
        <?php foreach ($cartItems as $item): ?>
            <tr>
                <td class="px-4 py-2"><?php echo $item['product']; ?></td>
                <td class="px-4 py-2"><?php echo '₱' . number_format($item['price'], 2); ?></td>
                <td class="px-4 py-2"><?php echo $item['quantity']; ?></td>
                <td class="px-4 py-2"><?php echo '₱' . number_format($item['totalprice'], 2); ?></td> <!-- New column -->
                <td class="px-4 py-2"><button class="delete-btn" data-product="<?php echo $item['product']; ?>">Delete</button></td>    
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<div>
    Total Amount: <span id="totalAmount">₱<?php echo number_format($totalAmount, 2); ?></span>
</div>
<div id="paymentContainer">
    <input type="number" id="paymentInput" placeholder="Payment Amount" required>
</div>
<div>
    Change: <span id="changeAmount">₱0.00</span>
</div>
<button id="saveSaleBtn">Save Sale</button>
</div>
    </div>
  
    <!-- JavaScript Section -->
    <script>
        
        const adminBtn = document.getElementById('adminBtn');
        const adminModal = document.getElementById('adminModal');
        const caretButton = document.querySelector('#adminBtn button'); // Select the button with the caret icon

        // Function to toggle modal visibility and caret icon
        function toggleModal() {
            if (adminModal.style.display === 'block') {
                adminModal.style.display = 'none';
                caretButton.querySelector('i').classList.remove('fa-caret-up'); // Remove caret-up class
                caretButton.querySelector('i').classList.add('fa-caret-down'); // Add caret-down class
            } else {
                adminModal.style.display = 'block';
                caretButton.querySelector('i').classList.remove('fa-caret-down'); // Remove caret-down class
                caretButton.querySelector('i').classList.add('fa-caret-up'); // Add caret-up class
            }
        }

        // Open or close modal when caret button is clicked
        caretButton.addEventListener('click', toggleModal);

        // Logout action
        logoutBtn.addEventListener('click', () => {
            // Create the confirmation dialog
            const confirmationDiv = document.createElement('div');
            confirmationDiv.classList.add('confirmation-modal');
            confirmationDiv.innerHTML = `
                <div class="confirmation-content">
                    <p>Are you sure you want to log out?</p>
                    <div class="btn-container">
                        <button id="confirmLogout">OK</button>
                        <button id="cancelLogout">Cancel</button>
                    </div>
                </div>
            `;

            // Append the confirmation dialog to the body
            document.body.appendChild(confirmationDiv);

            // Center the confirmation dialog
            confirmationDiv.style.top = `${(window.innerHeight - confirmationDiv.offsetHeight) / 2}px`;
            confirmationDiv.style.left = `${(window.innerWidth - confirmationDiv.offsetWidth) / 2}px`;

            // Event listener for confirm logout button
            const confirmLogoutBtn = document.getElementById('confirmLogout');
            confirmLogoutBtn.addEventListener('click', () => {
                // Redirect to Login.php after logout
                window.location.href = 'Login.php';
            });

            // Event listener for cancel logout button
            const cancelLogoutBtn = document.getElementById('cancelLogout');
            cancelLogoutBtn.addEventListener('click', () => {
                // Remove the confirmation dialog from the DOM
                confirmationDiv.remove();
            });
        });

        const toggleNavigationBtn = document.getElementById('toggleNavigationBtn');
const leftNavigation = document.querySelector('.left-navigation');
const mainContent = document.querySelector('.main-content');

function toggleNavigation() {
    leftNavigation.classList.toggle('minimized');
    mainContent.classList.toggle('expanded');
    slideshowContainer.classList.toggle('expanded'); // Add this line to toggle slideshow container width
}

toggleNavigationBtn.addEventListener('click', toggleNavigation);
</script>
<script>
function updateProductInfo(productName) {
    // Make an AJAX request to get product information
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'Cashiering.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.success) {
                    // Update product image and details
                    productDisplay.style.backgroundImage = `url('${response.productImage}')`;
                    document.getElementById('price').innerText = `₱${response.price}`;
                } else {
                    // Product not found, clear product image and details
                    productDisplay.style.backgroundImage = 'none';
                    document.getElementById('price').innerText = 'Price not available';
                }
            } else {
                console.error('Error fetching product information:', xhr.status);
            }
        }
    };
    xhr.send('search=' + encodeURIComponent(productName));
}

// Add event listener to the search input for keyup event
document.getElementById('productNameInput').addEventListener('keyup', function() {
    const productName = this.value.trim();
    if (productName !== '') {
        updateProductInfo(productName);
    } else {
        // Clear product image and details when input is empty
        productDisplay.style.backgroundImage = 'none';
        document.getElementById('price').innerText = 'Price not available';
    }
});


if (productImage.src) {
    productDisplay.style.backgroundImage = `url('${productImage.src}')`;
} else {
    productDisplay.style.backgroundImage = 'none'; // If no image, clear the background
}  

// Add event listener to the "Add to Cart" button
document.getElementById('addToCartBtn').addEventListener('click', addToCart);

// Function to add product to cart
function addToCart() {
    const productName = document.getElementById('productNameInput').value;
    const price = <?php echo json_encode($price); ?>;
    const quantity = document.getElementById('quantityInput').value;
    const customerName = document.getElementById('customerNameInput').value;
    const totalprice = price * quantity;
    // Create a new row for the cart
    const cartRow = document.createElement('tr');
    cartRow.innerHTML = `
        <td class="px-4 py-2">${customerName}</td>
        <td class="px-4 py-2">${productName}</td>
        <td class="px-4 py-2">${price}</td>
        <td class="px-4 py-2">${quantity}</td>
    `;

    // Add the new row to the cart
    const cartItemsContainer = document.getElementById('cartItems');
    cartItemsContainer.appendChild(cartRow);

    // Show the shopping cart table
    const shoppingCart = document.getElementById('shoppingCart');
    shoppingCart.style.display = 'block';

    document.getElementById('addToCartForm').elements.namedItem('totalprice').value = totalprice;

// Submit the form
document.getElementById('addToCartForm').submit();
}
// Add event listener to each delete button
const deleteButtons = document.querySelectorAll('.delete-btn');
deleteButtons.forEach(button => {
    button.addEventListener('click', () => {
        const productName = button.getAttribute('data-product');
        
        // Disable the button to prevent multiple clicks
        button.disabled = true;
        
        // Find the parent row of the delete button
        const row = button.closest('tr');

        // AJAX request
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'Cashiering.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
        xhr.onreadystatechange = function() {
            if (xhr.readyState === XMLHttpRequest.DONE) {
                if (xhr.status === 200) {
                    // Remove the parent row from the table
                    row.remove();
                } else {
                    console.error('Error deleting cart item:', xhr.status);
                    // Re-enable the button if there was an error
                    button.disabled = false;
                }
            }
        };
        xhr.send('product=' + encodeURIComponent(productName)); // Send the product name as a parameter
    });
});

// Function to calculate and display total amount
function calculateTotalAmount() {
    const cartRows = document.querySelectorAll('#cartItems tr');
    let totalAmount = 0;

    cartRows.forEach(row => {
        // Get the total price from the row and add it to totalAmount
        const totalPrice = parseFloat(row.querySelector('td:last-child').innerText);
        totalAmount += totalPrice;
    });

    // Update the total amount display
    document.getElementById('totalAmount').innerText = `₱${totalAmount.toFixed(2)}`;
}
// Add event listener to the payment input for input event
document.getElementById('paymentInput').addEventListener('input', calculateChange);
// Function to calculate and display change
function calculateChange() {
    const paymentAmount = parseFloat(document.getElementById('paymentInput').value);
    const totalAmount = parseFloat(document.getElementById('totalAmount').innerText.replace('₱', ''));

    // Calculate the change by subtracting the total amount from the payment amount
    let changeAmount = paymentAmount - totalAmount;

    // Display the change amount
    document.getElementById('changeAmount').innerText = `₱${changeAmount.toFixed(2)}`;
}
// Function to handle saving the sale
function saveSale() {
    // Make an AJAX request to handle saving the sale to the database
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'Cashiering.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === XMLHttpRequest.DONE) {
            if (xhr.status === 200) {
                // If the sale was successfully saved, reload the page to clear the cart
                window.location.reload();
            } else {
                console.error('Error saving sale:', xhr.status);
                // Handle error appropriately, maybe show an alert to the user
            }
        }
    };

    // Calculate total quantity and send it with the request
    const cartRows = document.querySelectorAll('#cartItems tr');
    let totalQuantity = 0;
    cartRows.forEach(row => {
        totalQuantity += parseInt(row.querySelector('td:nth-child(4)').innerText);
    });

    // Send the request with total quantity
    xhr.send('totalQuantity=' + encodeURIComponent(totalQuantity));
}

// Add event listener to the "Save Sale" button
document.getElementById('saveSaleBtn').addEventListener('click', saveSale);


</script>

</body>
</html>
<?php
// Handle the AJAX request
if (isset($_POST['totalQuantity'])) {
    $totalQuantity = $_POST['totalQuantity'];

    // Perform database operations to save the sale and clear the cart
    // You may need to adjust this part based on your database structure and logic

    // Clear the cart after saving the sale
    $stmt = $conn->prepare("DELETE FROM cart WHERE email = ?");
    $stmt->bind_param("s", $_SESSION['email']);
    $stmt->execute();

    // Insert total quantity and sales data into sales tables
    $currentDate = date("Y-m-d");
    $stmt = $conn->prepare("INSERT INTO sales_today (date, total_quantity, total_sales) VALUES (?, ?, ?)");
    $stmt->bind_param("sid", $currentDate, $totalQuantity, $totalSales);
    $stmt->execute();

    // You would do similar operations for sales_yearly and sales_weekly tables
}
?>
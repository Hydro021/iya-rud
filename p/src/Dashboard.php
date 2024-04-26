<?php
session_start();
// Retrieve email from session variable
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

// Prepare and execute query to fetch firstname based on email
$stmt = $conn->prepare("SELECT firstname, gender FROM acc WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

// Check if query returned a result
if ($result->num_rows > 0) {
    // Fetch the firstname
    $row = $result->fetch_assoc();
    $firstname = $row["firstname"];
} else {
    // If no result found, handle accordingly (e.g., display a default value)
    $firstname = "User"; // Default value
}

$stmt = $conn->prepare("SELECT product_name, price, stock, photo FROM products WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

// Check if query returned a result
if ($result->num_rows > 0) {
    // Fetch the product list
    $productList = array();
    while ($row = $result->fetch_assoc()) {
        $productList[] = $row;
    }
} else {
    // If no result found, handle accordingly (e.g., display a default message)
    $productList = array(); // Empty array
}

// Close connection
$conn->close();

// Function to fetch sales data from the database
function fetchSalesData() {
    // Perform database connection
    global $servername, $username, $password, $dbname, $email;
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Query to fetch sales data based on email
    $stmt = $conn->prepare("SELECT sales_today, sales_weekly, sales_yearly FROM sales WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    // Fetch sales data if available
    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        return $row;
    } else {
        // If no sales data found, return default zero values
        return array("sales_today" => 0, "sales_weekly" => 0, "sales_yearly" => 0);
    }
}

// Check if Sales button is clicked
if (isset($_POST['fetchSales'])) {
    $salesData = fetchSalesData();
    echo json_encode($salesData); // Return sales data as JSON
    exit(); // Stop further execution
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
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
    height: 495.6px;
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

.border_line{
    padding:1px;
    border-bottom:5px solid blue;
    width:99.9%;
    margin-left:1px;
}
.btn {
    border: 5px solid black;
    background: black;
    color: white;
    width: 3%;
    margin-left: 200.2px;
    margin-top: 110px;
    font-size: 25px;
    text-align: center;
    transition: margin-left 0.5s; /* Dugang transition property para sa margin-left */
    border-top-right-radius: 15px;
    border-bottom-right-radius: 15px;
}

.left-navigation.minimized {
    width: 0; /* Width when minimized */
    overflow: hidden; /* Hide the minimized navigation */
    opacity: 0; /* Make it invisible */
}

.right-item {
    display: inline-flex;
    align-items: center;
    margin-left: 220px; /* Initial position */
    margin-top: 10px;
    height: 120px;
    transition: margin-left 0.5s; /* Add transition for smooth animation */
}

.right-item.moved {
    margin-left: 20px; /* New position when moved */
}

.main-content.expanded .right-item {
    margin-left: 20px; /* Adjusted margin when left navigation is minimized */
}



.right-item i {
    margin-right: 10px;
    font-size: 40px;
    padding: 10px;
    border-radius: 50%;
}

.right-item h2 {
    font-size: 30px;
    color: black;
    margin: 0;
    padding: 0;
}

.product-lists {
    border: 3px solid blue; /* Make border thicker and blue */
    padding: 25px; /* Add padding to separate items */
    border-radius: 25px;
}

.product-lists h2 {
    margin-top: 10px;
    background: blue;
    color: white;
    font-weight: 600;
    padding: 5px;
    border-radius: 20px;
    width:230px;
    text-align:center;
}

.product-lists i {
    background: white;
    color: blue;
    width: 70px;
    text-align:center;
    border-radius:25px;
    border:2px solid blue;
}
.product-lists h3{
    font-size:60px;
    margin-top:55px;
    margin-left:-20px;
    color:blue;
}
.sales {
    border: 3px solid #ddd; /* Make border thicker and gray */
    padding: 25px; /* Add padding to separate items */
    border-radius: 25px;
    width:300px;
    margin-left:50px;
}
.sales h2{
    margin-top: 10px;
    background: gray;
    color: white;
    font-weight: 600;
    padding: 5px;
    border-radius: 20px;
    margin-left:18px;
    width:200px;
    text-align:center;
}

.sales i {
    background: gray;
    color: white;
    width: 70px;
    text-align:center;
    border-radius:25px;
    border:2px solid gray;
}
.sales h3{
    font-size:60px;
    margin-top:55px;
    margin-left:-20px;
    color:gray;
}
#salesTable {
    font-size:10px;
            margin-top: -125px;
            border-collapse: collapse;
            width: 76%;
            margin-left:240px;
            height:280px;
        }

        #salesTable th, #salesTable td {
            border: none;
            padding: 8px;
            text-align: center;
            border-right:2px solid black;
            border-bottom:2px solid black;
            border-left:2px solid black;
            font-size:20px; 
        }
        #salesTable td{
            text-align:left;
        }
        #salesTable th {
            background-color: #f2f2f2;
            border-right:2px solid black;
            border-bottom:2px solid black;
            border-top:2px solid black;
            border-left:2px solid black;
            font-size:20px; 
        }

        #salesTable tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .slideshow-image {
    width: 100%; /* Set the width to fill the container */
    height: 265px; /* Set a specific height for the images */
    object-fit: cover; /* Ensure the entire image is visible while maintaining aspect ratio */
    border-radius: 5px; /* Add rounded corners */
}


    /* Slideshow container */
.slideshow-container {
    position: relative;
    width: 80%;
    height:200px;
    overflow: hidden; 
    margin-left:240px;
    margin-top:-150px;
}

.hidden {
    display: none;
}
/* Update CSS for product list container */
.productList {
    margin-left: 240px;
    border: 2px solid black;
    width: 79.9%;
    font-size: 20px;
    height: 340px; /* Fixed height */
    overflow-y: auto; /* Enable vertical scroll bar */
    border-collapse: collapse;
    margin-top: -200px;
}


/* Add borders between rows */
.productList ul li {
    border-bottom: 1px solid #ccc; /* Add bottom border between rows */
    padding-bottom: 10px; /* Adjust padding as needed */
}

/* Ensure proper display for flex container */
.productList ul {
    display: flex;
    flex-wrap: wrap; /* Allow items to wrap into multiple rows */
    padding: 0; /* Remove default padding */
    list-style: none; /* Remove default list styles */
}

/* Adjust spacing between items */
.productList ul li {
    width: calc(33.33% - 20px); /* Set width for each item (subtract margin) */
    margin-right: 20px; /* Add space between items */
    margin-bottom: 20px; /* Add space between rows */
    box-sizing: border-box; /* Include padding and border in the width */
}

/* Add borders between columns */
.productList ul li:not(:nth-child(3n)) {
    border-right: 1px solid #ccc; /* Add right border between columns */
    padding-right: 10px; /* Adjust padding as needed */
}



    </style>
</head>
<body>
    <nav class="bg-cover bg-center bg-no-repeat bg-opacity-80 border-b-4 border-gray-700 flex justify-between items-center p-3 relative" style="background-image: url('background.png');">
        <div class="flex items-center text-white mx-4">
            <img src="new logo.png" alt="logo" class="w-16 mr-1">
            <h1 class="text-2xl text-white">R.V.M</h1>
        </div>

        <!-- New Left Navigation -->
        <div class="left-navigation">
         <!-- Home button -->
    <div class="left-navigation-item">
        <a href="Home.php" class="text-white">
            <i class="fas fa-home"></i>
            Home
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
                <p id="userEmail" class="text-bold mb-2"><?php echo $email; ?></p>
                <div class="border-line"></div>  
                <!-- Greeting message -->
                <div class="bg-white pt-3 pb-3">
                    <i id="adminIcon" class="fas fa-user-tie"></i> <!-- Admin icon -->
                    <h2 id="greeting" class="text-2xl mb-4">Hello, <?php echo $firstname; ?></h2>
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
    <div class="border_line"></div>
<div class="right">
<div class="right-item product-lists">
<button>
    <i class="fas fa-box mr-2"></i>
</button>
            <h2>Product Lists</h2>
            <h3>....</h3>
        </div>

    <div class="right-item sales">
        <button id="fetchSalesBtn">
                <i class="fas fa-chart-line mr-2"></i>
            </button>
        <h2>Sales</h2>
        <h3>....</h3>
    </div>
</div>
</div>
    <div class="btn" id="toggleNavigationBtn"><button><</button></div>
</div>
<div class="main-content">
<div id="salesData"></div>
<div id="slideshowContainer" class="slideshow-container hidden">
      
</div>
<div id="productList" class="productList hidden">
<h3>Product List:</h3>
<ul style="display: inline-flex; flex-wrap: wrap;">
    <?php foreach ($productList as $product): ?>
        <li style="margin-right: 10px; display: flex; align-items: center; margin-bottom: 10px;">
            <img class="mt-2 ml-2 mb-2" src="<?php echo $product['photo']; ?>" alt="Product Photo" style="width: 100px; height: 120px; margin-right: 10px; border-radius: 5px;">
            <div style="display: flex; flex-direction: column;">
                <span style="font-size: 24px;"><?php echo $product['product_name']; ?></span>
                <span style="margin-top: 5px;">₱<?php echo $product['price']; ?> (Stock: <?php echo $product['stock']; ?>)</span>
            </div>
        </li>
    <?php endforeach; ?>
</ul>



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
const rightItem = document.querySelector('.right-item');
const button = document.getElementById('toggleNavigationBtn'); // Kinahanglan idini pareho sa id sa button element sa HTML

function toggleNavigation() {
    leftNavigation.classList.toggle('minimized'); // I-toggle ang 'minimized' class
    rightItem.classList.toggle('moved'); // I-toggle ang 'moved' class
    
    // Ayuson ang margin-left sa right-item ug button base sa kahimtang sa left-navigation
    if (leftNavigation.classList.contains('minimized')) {
        rightItem.style.marginLeft = '20px'; // I-move ang right-item pakadto sa left-navigation
        button.style.marginLeft = '-5px'; // I-move ang button pakadto sa left-navigation
    } else {
        rightItem.style.marginLeft = '220px'; // I-balik ang orihinal nga margin-left sa right-item
        button.style.marginLeft = '200.2px'; // I-balik ang orihinal nga margin-left sa button
    }
}

toggleNavigationBtn.addEventListener('click', toggleNavigation);

// Add an isSalesDataVisible variable to keep track of the visibility state
let isSalesDataVisible = false;

document.getElementById('fetchSalesBtn').addEventListener('click', function(event) {
    event.preventDefault(); // Prevent default link behavior
    toggleSalesDataVisibility(); // Toggle sales data visibility
});

// Function to toggle sales data visibility
function toggleSalesDataVisibility() {
    if (isSalesDataVisible) {
        hideSalesData(); // Hide sales data if it's visible
    } else {
        fetchAndDisplaySalesData(); // Fetch and display sales data if it's hidden
    }
}

// Function to hide sales data
function hideSalesData() {
    const salesDataContainer = document.getElementById('salesData');
    salesDataContainer.innerHTML = ''; // Clear sales data container
    isSalesDataVisible = false; // Update visibility state
}

// Function to fetch and display sales data
function fetchAndDisplaySalesData() {
    fetch('Dashboard.php', {
        method: 'POST',
        body: new URLSearchParams({ 'fetchSales': true }),
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded'
        }
    })
    .then(response => response.json())
    .then(data => {
        const salesDataContainer = document.getElementById('salesData');
        salesDataContainer.innerHTML = ''; // Clear previous data
        const salesTable = document.createElement('table');
        salesTable.id = 'salesTable';
        const tableHeaders = ['Sales Today', 'Sales Weekly', 'Sales Yearly'];
        const tableData = [data.sales_today, data.sales_weekly, data.sales_yearly];
        const tableHeaderRow = document.createElement('tr');
        tableHeaders.forEach(headerText => {
            const th = document.createElement('th');
            th.textContent = headerText;
            tableHeaderRow.appendChild(th);
        });
        salesTable.appendChild(tableHeaderRow);
        const tableDataRow = document.createElement('tr');
        tableData.forEach(dataValue => {
            const td = document.createElement('td');
            td.textContent = dataValue;
            tableDataRow.appendChild(td);
        });
        salesTable.appendChild(tableDataRow);
        salesDataContainer.appendChild(salesTable);
        isSalesDataVisible = true; // Update visibility state
    })
    .catch(error => console.error('Error fetching sales data:', error));
}
        
        document.querySelector('.right-item.product-lists button').addEventListener('click', function() {
    // Toggle the visibility of the slideshow container
    const slideshowContainer = document.getElementById('slideshowContainer');
    slideshowContainer.classList.toggle('hidden');

    const productlistDiv = document.getElementById('productList');
    productlistDiv.classList.toggle('hidden');
});
// Function to adjust the height of the product list to avoid overflow
function adjustProductListHeight() {
    const productList = document.querySelector('#productList ul');
    const productListItems = document.querySelectorAll('#productList ul li');
    const totalHeight = Array.from(productListItems).reduce((acc, item) => acc + item.offsetHeight, 0);
    const maxHeight = window.innerHeight - productList.offsetTop - 20; // Subtract 20 for buffer

    if (totalHeight > maxHeight) {
        productList.style.maxHeight = maxHeight + 'px';
        productList.style.overflowY = 'auto';
    } else {
        productList.style.maxHeight = '';
        productList.style.overflowY = '';
    }
}

// Call the adjustProductListHeight function when the page loads and when a new product is added
window.addEventListener('load', adjustProductListHeight);


 </script>
</body>
</html>
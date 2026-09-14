<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="../Assets/index.css">
    <link rel="stylesheet" href="../Assets/components.css">
    <link rel="stylesheet" href="../Assets/marketplace.css">

    <!-- Basic Icons -->
    <link href="https://cdn.boxicons.com/3.0.8/fonts/basic/boxicons.min.css" rel="stylesheet">
    <!-- Filled Icons -->
    <link href="https://cdn.boxicons.com/3.0.8/fonts/filled/boxicons-filled.min.css" rel="stylesheet">
    <!-- Brand Icons -->
    <link href="https://cdn.boxicons.com/3.0.8/fonts/brands/boxicons-brands.min.css" rel="stylesheet">
</head>

<body>

    <?php include __DIR__ . '../navbar.php'; ?>


    <div class="container">
       
        <div class="market-container">

             <div class="search-bar">
               <input type="text" id="searchInput" placeholder="Search products...">
        </div>
     

        <div class="filters">
            <button class="filter-btn active" type="button">All</button>
            <button class="filter-btn" type="button"
                >Vegetables</button>
            <button class="filter-btn" type="button">Fruits</button>
                        <button class="filter-btn" type="button">Roots</button>

            <button class="filter-btn" type="button">Animals</button>
        </div>


        <div class="product-grid">
            <div class="product-card">
                <div class="product-img">
                    <img src="image-95.png" alt="Tomatoes">
                </div>
                <div class="product-name">Tomatoes</div>
                <div class="product-price">5000FCFA</div>
                <button class="contact-btn">Contact Seller</button>
            </div>
            <div class="product-card">
                <div class="product-img"><img src="image-95.png" alt="Rice"></div>
                <div class="product-name">rice</div>
                <div class="product-price">25000FCFA</div>
                <button class="contact-btn">Contact Seller</button>
            </div>
            <div class="product-card">
                <div class="product-img"><img src="image-95.png" alt="Cassava"></div>
                <div class="product-name">Cassava</div>
                <div class="product-price">10000FCFA</div>
                <button class="contact-btn">Contact Seller</button>
            </div>
            <div class="product-card">
                <div class="product-img"><img src="image-95.png" alt="Beans"></div>
                <div class="product-name">beans</div>
                <div class="product-price">15000FCFA</div>
                <button class="contact-btn">Contact Seller</button>
            </div>
            <div class="product-card">
                <div class="product-img"><img src="./images/ya.jpg" alt="Yams"></div>
                <div class="product-name">Yams</div>
                <div class="product-price">28000FCFA</div>
                <button class="contact-btn">Contact Seller</button>
            </div>
            <div class="product-card">
                <div class="product-img"><img src="image-95.png" alt="Banana"></div>
                <div class="product-name">Banana</div>
                <div class="product-price">25000FCFA</div>
                <button class="contact-btn">Contact Seller</button>
            </div>
            <div class="product-card">
                <div class="product-img"><img src="image-95.png" alt="Plantains"></div>
                <div class="product-name">Plantains</div>
                <div class="product-price">57000FCFA</div>
                <button class="contact-btn">Contact Seller</button>
            </div>
            <div class="product-card">
                <div class="product-img"><img src="image-95.png" alt="Cocoyams"></div>
                <div class="product-name">Cocoyams</div>
                <div class="product-price">29000FCFA</div>
                <button class="contact-btn">Contact Seller</button>
            </div>
            <div class="product-card">
                <div class='product-img'><img src=image-95.png alt='Potatoes'></div>
                <div class='product-name'>Potatoes</div>
                <div class='product-price'>80000FCFA</div>
                <button class='contact-btn'>Contact Seller</button>
            </div>
            <div class="product-card">
                <div class="product-img"><img src="image-95.png" alt="Garri"></div>
                <div class="product-name">Garri</div>
                <div class="product-price">75000FCFA</div>
                <button class="contact-btn">Contact Seller</button>
            </div>
            <div class="product-card">
                <div class="product-img"><img src="image-95.png" alt="Sugarcanes"></div>
                <div class="product-name">Sugarcanes</div>
                <div class="product-price">40000FCFA</div>
                <button class="contact-btn">Contact Seller</button>
            </div>
            <div class="product-card">
                <div class="product-img"><img src="image-95.png" alt="Sweet yams"></div>
                <div class="product-name">Sweet yams</div>
                <div class="product-price">25000FCFA</div>
                <button class="contact-btn">Contact Seller</button>
            </div>
        </div>
        </div>
    </div>

     <script src="../scripts/marketplace.js"></script>
   <?php include __DIR__ . '../footer.php' ?>
</body>

</html>
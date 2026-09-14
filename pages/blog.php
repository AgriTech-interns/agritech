<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AgriTech | Blog & Agricultural Insights</title>
    <link rel="stylesheet" href="../Assets/blog.css">
    <link rel="stylesheet" href="../Assets/components.css">
    <link rel="stylesheet" href="../Assets/index.css">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Inter:wght@400;500;600;700&family=Playfair+Display:wght@600;700;800&family=DM+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Icon Libraries -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <link href="https://cdn.boxicons.com/3.0.8/fonts/basic/boxicons.min.css" rel="stylesheet">

    <!-- CSS Stylesheets -->
    <link rel="stylesheet" href="blog.css">

    <!-- Small additions so the single-page filtering/article view behaves correctly
         even if these classes aren't already defined in blog.css -->
    <style>
        .hidden { display: none !important; }

        .pill { cursor: pointer; user-select: none; }
        .pill.active { outline: 2px solid currentColor; border-radius: 999px; }
        .pill.active a, .pill.active { font-weight: 700; }

        .card { cursor: pointer; }
        .card .tag a, .featured .tag a { pointer-events: none; }

        #backToBlog { cursor: pointer; text-decoration: none; color: inherit; }

        #articleView .related-grid .card { cursor: pointer; }

        .no-results { text-align: center; color: #6B7280; padding: 40px 0; grid-column: 1 / -1; }
    </style>
</head>
<body>

<?php include __DIR__ . '../navbar.php' ?>
    


    <!-- =========================================================
         BLOG LISTING VIEW (hero, filters, grid)
         ========================================================= -->
    <div id="blogListing">

        <!-- HERO SECTION -->
        <section class="blog-hero">
            <div class="eyebrow">Agritech Blog</div>
            <h1>Farming news, expert tips, and field-tested know-how.</h1>
            <p>Short reads from agronomists, vets, and growers — practical guidance you can use this season.</p>

            <!-- Interactive Search Bar -->
            <div class="search-row">
                <div class="search-box">
                    <div class="search-ic" style="border-color:#6B7280"></div>
                    <input type="text" id="searchInput" placeholder="Search articles — e.g. tilapia feeding, irrigation drip kits...">
                </div>
                <button class="btn-primary" id="searchBtn">Search</button>
            </div>
        </section>

        <!-- CATEGORY FILTER PILLS -->
        <nav class="filter-row" aria-label="Blog categories">
            <div class="pill active" data-filter="all">
                <span class="dot" style="background:#374151"></span>
                <span>All</span>
            </div>
            <div class="pill" data-filter="Crop Farming">
                <span class="dot" style="background:#7CB342"></span>
                <span>Crop Farming</span>
            </div>
            <div class="pill" data-filter="Fish Farming">
                <span class="dot" style="background:#2A9D8F"></span>
                <span>Fish Farming</span>
            </div>
            <div class="pill" data-filter="Livestock">
                <span class="dot" style="background:#8D6E63"></span>
                <span>Livestock</span>
            </div>
            <div class="pill" data-filter="Irrigation">
                <span class="dot" style="background:#4A90D9"></span>
                <span>Irrigation</span>
            </div>
            <div class="pill" data-filter="Technology">
                <span class="dot" style="background:#3D5A80"></span>
                <span>Technology</span>
            </div>
        </nav>

        <!-- MAIN CONTENT SECTION -->
        <main class="section" style="padding-top:24px;">

            <!-- FEATURED ARTICLE -->
            <article id="featuredArticle" data-category="Crop Farming">
                <div class="featured">
                    <div class="img">
                        <img src="./images/cocoayield.jpg" alt="Cocoa Yields">
                    </div>
                    <div class="body">
                        <div class="tag" style="background:#7CB342">Crop Farming</div>
                        <h2>Cocoa yields are up 18% this season — here's what changed on the ground</h2>
                        <p class="excerpt">Farmers around Buea are pairing shade-grown planting with new pest-tracking habits. We break down the three changes driving the jump, and what to try next.</p>
                        <div class="meta">
                            <div class="avatar"></div> Dr. Anna Mbua · Jul 24, 2026 · 6 min read
                        </div>
                    </div>
                </div>
            </article>

            <div class="grid-title">
                <h3>Latest Articles</h3>
            </div>

            <!-- ARTICLE GRID (populated by blog.js, markup below is the no-JS fallback) -->
            <div class="grid" id="articlesGrid">

                <article class="card" data-id="tilapia-stocking" data-category="Fish Farming">
                    <div class="thumb">
                        <div class="bar" style="background:#2A9D8F"></div>
                        <img src="./images/fishfarming.jpeg" alt="Fish Farming">
                    </div>
                    <div class="body">
                        <div class="tag" style="background:#2A9D8F">Fish Farming</div>
                        <h4>Tilapia pond stocking: getting density right for the dry season</h4>
                        <p>A simple ratio to keep growth steady when water levels start to drop.</p>
                        <div class="meta">Dr. James Etta · Jul 20, 2026</div>
                    </div>
                </article>

                <article class="card" data-id="vaccination-windows" data-category="Livestock">
                    <div class="thumb">
                        <div class="bar" style="background:#8D6E63"></div>
                        <img src="./images/pigs.jpeg" alt="Livestock">
                    </div>
                    <div class="body">
                        <div class="tag" style="background:#8D6E63">Livestock</div>
                        <h4>Vaccination windows every cattle owner should mark on the calendar</h4>
                        <p>Missing this one window is the top reason for preventable outbreaks.</p>
                        <div class="meta">Dr. Samuel Ngu · Jul 28, 2026</div>
                    </div>
                </article>

                <article class="card" data-id="drip-sprinkler" data-category="Irrigation">
                    <div class="thumb">
                        <div class="bar" style="background:#4A90D9"></div>
                        <img src="./images/irrigation.jpg" alt="Irrigation">
                    </div>
                    <div class="body">
                        <div class="tag" style="background:#4A90D9">Irrigation</div>
                        <h4>Drip vs. sprinkler: a real cost comparison for a 2-acre plot</h4>
                        <p>We priced out both systems for a smallholder farm — the payback gap surprised us.</p>
                        <div class="meta">Grace Nkemayang · Jul 14, 2026</div>
                    </div>
                </article>

                <article class="card" data-id="soil-sensors" data-category="Technology">
                    <div class="thumb">
                        <div class="bar" style="background:#3D5A80"></div>
                        <img src="./images/Technology.jpg" alt="Technology">
                    </div>
                    <div class="body">
                        <div class="tag" style="background:#3D5A80">Technology</div>
                        <h4>Soil sensors under 17000 XAF: which ones actually hold up in red clay</h4>
                        <p>We field-tested four budget sensors over six weeks. Two survived.</p>
                        <div class="meta">Samuel Eyong · Jul 10, 2026</div>
                    </div>
                </article>

                <article class="card" data-id="intercropping" data-category="Crop Farming">
                    <div class="thumb">
                        <div class="bar" style="background:#7CB342"></div>
                        <img src="./images/CropFarming.jpg" alt="Crop Farming">
                    </div>
                    <div class="body">
                        <div class="tag" style="background:#7CB342">Crop Farming</div>
                        <h4>Intercropping maize and beans: a beginner's planting map</h4>
                        <p>A row layout that improves soil nitrogen without cutting into maize yield.</p>
                        <div class="meta">Dr. Anna Mbua · Jul 6, 2026</div>
                    </div>
                </article>

                <article class="card" data-id="water-clarity" data-category="Fish Farming">
                    <div class="thumb">
                        <div class="bar" style="background:#2A9D8F"></div>
                        <img src="./images/ponds.jpg" alt="Fish Farming">
                    </div>
                    <div class="body">
                        <div class="tag" style="background:#2A9D8F">Fish Farming</div>
                        <h4>Reading water clarity as an early warning sign, not an afterthought</h4>
                        <p>Three visual checks that catch oxygen problems before fish do.</p>
                        <div class="meta">Dr. James Etta · Jul 18, 2026</div>
                    </div>
                </article>

            </div>
        </main>
    </div>


    <!-- =========================================================
         ARTICLE VIEW (hidden until a card is clicked; blog.js fills it in)
         ========================================================= -->
    <div id="articleView" class="hidden">
        <div class="article-wrap">
            <div class="article-conn">

                <div class="breadcrumb">
                    <a id="backToBlog">Blog</a> / <b id="articleCategoryCrumb">Category</b>
                </div>

                <div class="tag" id="articleTag" style="background:#7CB342">Category</div>
                <h1 id="articleTitle">Article title</h1>

                <div class="article-meta">
                    <div class="avatar"></div>
                    <div class="who">
                        <b id="articleAuthor">Author</b>
                        <span id="articleByline">Role · Date · Read time</span>
                    </div>
                </div>

                <div class="hero-img">
                    <img id="articleImage" src="" alt="">
                </div>

                <p class="lead" id="articleLead"></p>

                <div id="articleBody"></div>

                <div class="iframe" id="articleVideoWrap" style="display:none;">
                    <iframe id="articleVideo" width="560" height="315" src="" title="YouTube video player" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" referrerpolicy="strict-origin-when-cross-origin" allowfullscreen></iframe>
                </div>

                <div class="tags-row" id="articleTagsRow"></div>

                <h3 class="related-title">Related Articles</h3>
                <div class="related-grid" id="articleRelated"></div>

            </div>
        </div>
    </div>

 <?php include __DIR__ . '../footer.php' ?>
  
    <!-- Blog app logic: filtering + inline article view -->
    <script src="../scripts/blog.js"></script>
</body>
</html>

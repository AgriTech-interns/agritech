/* ===================================================================
   AgriTech Blog — single-page filtering + inline article view
   =================================================================== */

(function () {

    /* ---------- article content (merged from the individual article pages) ---------- */
    const ARTICLES = {

        "cocoa-yields": {
            category: "Crop Farming",
            color: "#7CB342",
            title: "Cocoa yields are up 18% this season — here's what changed on the ground",
            image: "./images/cocoayield.jpg",
            author: "Dr. Anna Mbua",
            byline: "Agronomist · Jul 24, 2026 · 6 min read",
            lead: "Across cooperatives near Buea, this year's cocoa harvest is beating last season's numbers by a wide margin. We spoke with three farms to find out what actually changed.",
            body: [
                { h: "1. Shade-grown planting is back", p: "Farmers who reintroduced native shade trees alongside cocoa saw steadier moisture levels through the dry weeks, cutting stress-related pod loss noticeably compared to open-sun plots." },
                { h: "2. Pest tracking moved from guesswork to a weekly log", p: "Several cooperatives adopted a simple paper log for capsid sightings, checked every Monday. Early detection meant treatment happened before infestations spread past a single row." },
                { h: "3. Fertilizer timing, not just quantity", p: "The biggest shift wasn't how much fertilizer was used, but when. Splitting the application around flowering rather than a single dose at planting made the difference growers noticed most." },
            ],
            tags: ["Cocoa", "Yield", "Shade Farming", "Pest Control"],
            related: ["intercropping", "drip-sprinkler", "soil-sensors"],
        },

        "tilapia-stocking": {
            category: "Fish Farming",
            color: "#2A9D8F",
            title: "Tilapia pond stocking: getting density right for the dry season",
            image: "./images/fishfarming.jpeg",
            author: "Dr. James Etta",
            byline: "Aquaculture Specialist · Jul 20, 2026 · 4 min read",
            lead: "A simple ratio to keep growth steady when water levels start to drop.",
            body: [
                { h: "Why density matters more in the dry months", p: "During Cameroon's dry season, reduced water availability directly impacts oxygen levels and waste concentration in ponds. Lowering fish density per square meter before water levels fall prevents stunted growth and keeps mortality rates low." },
            ],
            tags: ["Tilapia", "Pond Management", "Dry Season"],
            related: ["water-clarity", "cocoa-yields"],
        },

        "vaccination-windows": {
            category: "Livestock",
            color: "#8D6E63",
            title: "Vaccination windows every cattle owner should mark on the calendar",
            image: "./images/pigs.jpeg",
            author: "Dr. Samuel Ngu",
            byline: "Veterinarian · Jul 28, 2026 · 5 min read",
            lead: "Missing this one window is the top reason for preventable outbreaks.",
            body: [
                { h: "Time it around transhumance, not the calendar month", p: "In major cattle-producing zones across Cameroon, maintaining strict vaccination schedules before the onset of seasonal transhumance protects herds against major viral and bacterial threats." },
                { h: "Routine beats reactive", p: "Establishing routine immunization windows allows livestock keepers to safeguard their animals, maintain milk and meat productivity, and prevent widespread herd losses." },
            ],
            tags: ["Cattle", "Vaccination", "Herd Health"],
            video: "https://www.youtube.com/embed/qLRh78s9_dk?si=jSbAb3msmufDMDFh",
            videoTitle: "Watch Step-by-Step Guide",
            related: ["drip-sprinkler", "tilapia-stocking"],
        },

        "drip-sprinkler": {
            category: "Irrigation",
            color: "#4A90D9",
            title: "Drip vs. sprinkler: a real cost comparison for a 2-acre plot",
            image: "./images/irrigation.jpg",
            author: "Grace Nkemayang",
            byline: "Irrigation Consultant · Jul 14, 2026 · 5 min read",
            lead: "We priced out both systems for a smallholder farm — the payback gap surprised us.",
            body: [
                { h: "Why irrigation choice matters here", p: "In Cameroon, where prolonged dry seasons in regions like the Far North and seasonal dry spells in the West often restrict cultivation to single-crop years, modern irrigation is key to unlocking year-round yields." },
                { h: "What drip changes", p: "Transitioning from rainfall reliance to low-cost drip irrigation allows farmers to deliver water and soluble nutrients directly to crop roots. This reduces evaporation loss by up to 40% compared to overhead sprinklers, protecting vital water reserves while boosting off-season vegetable profits." },
            ],
            tags: ["Irrigation", "Drip Kits", "Water Management"],
            video: "https://www.youtube.com/embed/Elimzt9l-fg?si=ZgqsfL2DdiSS9HIB",
            videoTitle: "Watch: Setting Up a Drip Line",
            related: ["soil-sensors", "cocoa-yields"],
        },

        "soil-sensors": {
            category: "Technology",
            color: "#3D5A80",
            title: "Soil sensors under 17000 XAF: which ones actually hold up in red clay",
            image: "./images/Technology.jpg",
            author: "Samuel Eyong",
            byline: "AgriTech Reviewer · Jul 10, 2026 · 4 min read",
            lead: "We field-tested four budget sensors over six weeks. Two survived.",
            body: [
                { h: "What red clay does to cheap probes", p: "Compacted red clay soils are unforgiving on low-cost moisture probes — the tips corrode faster and readings drift after repeated wet-dry cycles. Two of the four sensors we tested lost calibration within three weeks." },
                { h: "What held up", p: "The two sensors that survived shared sealed probe housings and simple companion apps that let growers log readings against rainfall, which made it easier to catch drift early rather than trusting a bad reading." },
            ],
            tags: ["Soil Sensors", "Budget Tools", "Red Clay"],
            related: ["drip-sprinkler", "intercropping"],
        },

        "intercropping": {
            category: "Crop Farming",
            color: "#7CB342",
            title: "Intercropping maize and beans: a beginner's planting map",
            image: "./images/CropFarming.jpg",
            author: "Dr. Anna Mbua",
            byline: "Agronomist · Jul 6, 2026 · 4 min read",
            lead: "Maximize yield in regions like the West and Northwest by pairing maize with nitrogen-fixing common beans.",
            body: [
                { h: "The 2:1 row layout", p: "A simple 2:1 row layout — two rows of maize to one row of beans — improves soil health naturally while reducing weed competition during the rainy season." },
                { h: "Why it works", p: "Beans fix nitrogen in the soil that the maize can draw on later in the season, so the pairing improves yield without adding extra fertilizer cost." },
            ],
            tags: ["Maize", "Beans", "Intercropping"],
            video: "https://www.youtube.com/embed/POkuTcl99mc?si=3-RB0Uu10QhYc7WU",
            videoTitle: "Watch Step-by-Step Guide",
            related: ["cocoa-yields", "soil-sensors"],
        },

        "water-clarity": {
            category: "Fish Farming",
            color: "#2A9D8F",
            title: "Reading water clarity as an early warning sign, not an afterthought",
            image: "./images/ponds.jpg",
            author: "Dr. James Etta",
            byline: "Aquaculture Specialist · Jul 18, 2026 · 4 min read",
            lead: "Three visual checks that catch oxygen problems before fish do.",
            body: [
                { h: "What clouded water is telling you", p: "Excessive algae blooms or muddy runoff can block sunlight and deplete oxygen levels fast. By performing routine Secchi disc or visual depth checks, farmers can balance feed inputs and manage water exchange before aquatic life is endangered." },
            ],
            tags: ["Water Quality", "Pond Management"],
            related: ["tilapia-stocking", "vaccination-windows"],
        },

    };

    /* ---------- element refs ---------- */
    const listingView = document.getElementById("blogListing");
    const articleView = document.getElementById("articleView");
    const grid = document.getElementById("articlesGrid");
    const featured = document.getElementById("featuredArticle");
    const pills = document.querySelectorAll(".pill");
    const searchInput = document.getElementById("searchInput");
    const searchBtn = document.getElementById("searchBtn");
    const backToBlog = document.getElementById("backToBlog");

    let activeFilter = "all";

    /* ---------- filtering (category pills + search, combined) ---------- */

    function matchesFilter(el) {
        const category = el.dataset.category;
        const categoryOk = activeFilter === "all" || category === activeFilter;

        const term = searchInput.value.trim().toLowerCase();
        const text = el.textContent.toLowerCase();
        const searchOk = term === "" || text.includes(term);

        return categoryOk && searchOk;
    }

    function applyFilters() {
        let visibleCount = 0;

        if (featured) {
            const show = matchesFilter(featured);
            featured.classList.toggle("hidden", !show);
        }

        grid.querySelectorAll(".card").forEach((card) => {
            const show = matchesFilter(card);
            card.classList.toggle("hidden", !show);
            if (show) visibleCount++;
        });

        let noResults = grid.querySelector(".no-results");
        if (visibleCount === 0) {
            if (!noResults) {
                noResults = document.createElement("p");
                noResults.className = "no-results";
                noResults.textContent = "No articles match your search.";
                grid.appendChild(noResults);
            }
        } else if (noResults) {
            noResults.remove();
        }
    }

    pills.forEach((pill) => {
        pill.addEventListener("click", () => {
            pills.forEach((p) => p.classList.remove("active"));
            pill.classList.add("active");
            activeFilter = pill.dataset.filter;
            applyFilters();
        });
    });

    searchInput.addEventListener("input", applyFilters);
    searchBtn.addEventListener("click", (e) => {
        e.preventDefault();
        applyFilters();
    });

    /* ---------- article view ---------- */

    function openArticle(id) {
        const data = ARTICLES[id];
        if (!data) return;

        document.getElementById("articleCategoryCrumb").textContent = data.category;
        document.getElementById("articleTag").textContent = data.category;
        document.getElementById("articleTag").style.background = data.color;
        document.getElementById("articleTitle").textContent = data.title;
        document.getElementById("articleAuthor").textContent = data.author;
        document.getElementById("articleByline").textContent = data.byline;
        document.getElementById("articleImage").src = data.image;
        document.getElementById("articleImage").alt = data.title;
        document.getElementById("articleLead").textContent = data.lead;

        const bodyEl = document.getElementById("articleBody");
        bodyEl.innerHTML = "";
        data.body.forEach((section) => {
            const h3 = document.createElement("h3");
            h3.textContent = section.h;
            const p = document.createElement("p");
            p.textContent = section.p;
            bodyEl.appendChild(h3);
            bodyEl.appendChild(p);
        });

        const videoWrap = document.getElementById("articleVideoWrap");
        const video = document.getElementById("articleVideo");
        if (data.video) {
            video.src = data.video;
            videoWrap.style.display = "";
        } else {
            video.src = "";
            videoWrap.style.display = "none";
        }

        const tagsRow = document.getElementById("articleTagsRow");
        tagsRow.innerHTML = "";
        (data.tags || []).forEach((tag) => {
            const chip = document.createElement("div");
            chip.className = "chip";
            chip.textContent = tag;
            tagsRow.appendChild(chip);
        });

        const relatedGrid = document.getElementById("articleRelated");
        relatedGrid.innerHTML = "";
        (data.related || []).forEach((relId) => {
            const relData = ARTICLES[relId];
            if (!relData) return;

            const card = document.createElement("div");
            card.className = "card";
            card.innerHTML = `
                <div class="thumb">
                    <div class="bar" style="background:${relData.color}"></div>
                    <img src="${relData.image}" alt="${relData.category}">
                </div>
                <div class="body">
                    <div class="tag" style="background:${relData.color}">${relData.category}</div>
                    <h4>${relData.title}</h4>
                    <div class="meta">${relData.byline}</div>
                </div>
            `;
            card.addEventListener("click", () => openArticle(relId));
            relatedGrid.appendChild(card);
        });

        listingView.classList.add("hidden");
        articleView.classList.remove("hidden");
        window.scrollTo({ top: 0, behavior: "smooth" });
    }

    function closeArticle() {
        articleView.classList.add("hidden");
        listingView.classList.remove("hidden");
        window.scrollTo({ top: 0, behavior: "smooth" });
    }

    // featured article + grid cards open the article view
    if (featured) {
        featured.addEventListener("click", () => openArticle("cocoa-yields"));
    }

    grid.querySelectorAll(".card").forEach((card) => {
        card.addEventListener("click", () => openArticle(card.dataset.id));
    });

    backToBlog.addEventListener("click", (e) => {
        e.preventDefault();
        closeArticle();
    });

})();

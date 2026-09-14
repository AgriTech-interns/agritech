const filterButtons = document.querySelectorAll(".filter-btn");
const searchInput = document.querySelector("#searchInput"); // new
let category = "all"
let searchTerm = ""; // new

filterButtons.forEach((button) =>{
    button.addEventListener("click", ()=>{
        filterButtons.forEach((btn)=>{btn.classList.remove('active')})
        button.classList.add('active')
        category = button.textContent.toLowerCase();
        displayProduct()
    })
})

// Listen for typing in search bar
searchInput.addEventListener("input", (e) => {
    searchTerm = e.target.value.toLowerCase();
    displayProduct();
})

const productArray = [
    {id:1, name:"Tomatoes", price: 5000, image: "../pages/images/tom.jpg", category: "vegetables"},
    {id:2, name:"Carrots", price: 2000, image:"../pages/images/carr.jpg", category: "vegetables"},
    {id:3, name:"Oranges", price: 500, image:"../pages/images/oran.jpg" ,category: "fruits"},
    {id:4, name:"Pigs", price: 15500, image:"../pages/images/pi.jpg",category: "animals"},
    {id:5, name:"Apple", price: 2500, image:"../pages/images/ap.jpg", category: "fruits"},
    {id:6, name:"Cow", price: 15000, image:"../pages/images/c.jpg", category: "animals"},
    {id:7, name:"Onions", price: 500, image:"../pages/images/o.jpg", category: "vegetables"},
    {id:8, name:"Goats", price: 35000, image:"../pages/images/go.jpg", category: "animals"},
    {id:9, name:"Watermelons", price:2000, image:"../pages/images/wa.jpg", category: "fruits"},
    {id:10, name:"Spices", price: 4500, image:"../pages/images/spi.jpg", category: "vegetables"},
    {id:11, name:"Corn", price: 2500, image:"../pages/images/con.jpg", category: "vegetables"},
    {id:12, name:"Pineapple", price: 400, image:"../pages/images/pin.jpg", category: "fruits"},
    {id:13, name:"Banana", price: 600, image:"../pages/images/b.jpg", category: "fruits"},
    {id:14, name:"Wheat", price: 4000, image:"../pages/images/we.jpg", category: "roots"},
    {id:15, name:"Mustard", price: 7000, image:"../pages/images/mus.jpg", category: "roots"},
    {id:16, name:"Beetroot", price: 3000, image:"../pages/images/beet.jpg", category: "roots"},
    {id:17, name:"Orcid", price: 1500, image:"../pages/images/o.jpg", category: "roots"},
    {id:18, name:"Shallot", price: 2500, image:"../pages/images/s.jpg", category: "vegetables"},
    {id:19, name:"Cats", price: 3000, image:"../pages/images/cat.jpg", category: "animals"},
    {id:20, name:"Dogs", price: 25000, image:"../pages/images/dog.jpg", category: "animals"}, // fixed "nimals"
    {id:21, name:"Berries", price: 800, image:"../pages/images/Black.jpg", category: "fruits"},
    {id:22, name:"Yam", price: 30000, image:"../pages/images/ya.jpg", category: "vegetables"}, // fixed "vegitables"
    {id:23, name:"Cassava", price: 25000, image:"../pages/images/cas.jpg", category: "roots"}, // fixed spelling
    {id:24, name:"Onion", price: 300, image:"../pages/images/o.jpg", category: "vegetables"}, // fixed category
    // {id:25, name:"Sweet potatoes", price: 1000, image:"../pages/images/swet.jpg", category: "vegetables"},
    // {id:26, name:"Strawberry", price: 700, image:"../pages/images/Strawb.jpg", category: "fruits"},
    // {id:27, name:"Chicken", price: 3000, image:"../pages/images/chic.jpg", category: "animals"},
    // {id:28, name:"Parrot", price: 3000, image:"../pages/images/Birds.jpg", category: "animals"}
]

const productGrid  = document.querySelector(".product-grid");

function displayProduct(){
    const filteredProducts = productArray.filter(product => {
        const matchCategory = category === "all" || product.category.toLowerCase() === category;
        const matchSearch = product.name.toLowerCase().includes(searchTerm); // search by name
        return matchCategory && matchSearch;
    });

    if(filteredProducts.length === 0){
        productGrid.innerHTML = `<p>No products found</p>`;
        return;
    }

    productGrid.innerHTML = filteredProducts.map(product => `
        <div class="product-card" key=${product.id}>
            <div class="product-img">
                <img src=${product.image || 'placeholder.jpg'} alt="${product.name}">
            </div>
            <h3 class="product-name">${product.name}</h3>
            <p class="product-price">Price: ${(product.price / 100).toFixed(2)} FCFA</p>
        </div>
    `).join("");
}

displayProduct()
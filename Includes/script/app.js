import { Router } from './router.js'; // Import the class
import { showDashboard } from "./pages/dashboard.js";
import { showProducts } from "./pages/products.js";
import { showProductCategories } from "./pages/productCategories.js"; // This now imports your refactored controller/page setup

document.addEventListener('DOMContentLoaded', () => {
    // Instantiate the RouteDispatcher
    const router = new Router('app-root'); // Pass the ID of your app's root element

    // If you want to make it globally accessible like before (optional)
    // window.myAppRouter = router;

    router.addRoute('#/', showDashboard);
    router.addRoute('#/products', showProducts);
    router.addRoute('#/product-categories', showProductCategories);

    // Initial route handling
    router.handleRouteChange();

    // Navigation link active state handling (remains mostly the same)
    const navLinks = document.querySelectorAll('.sidebar nav a');
    function setActiveLink() {
        const currentHash = window.location.hash || '#/';
        navLinks.forEach(link => {
            if (link.getAttribute('href') === currentHash) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    }
    setActiveLink();
    window.addEventListener('hashchange', setActiveLink);
});
import {Router} from './router.js';
import {showDashboard} from "./pages/dashboard.js";
import {showProducts} from "./pages/products.js";
import {showProductCategories} from "./pages/productCategories.js";

document.addEventListener('DOMContentLoaded', () => {
    const router = new Router('app-root');

    router.addRoute('#/', showDashboard);
    router.addRoute('#/products', showProducts);
    router.addRoute('#/product-categories', showProductCategories);

    router.handleRouteChange();

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
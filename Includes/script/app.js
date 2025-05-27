import {showDashboard} from "./pages/dashboard.js";
import {showProducts} from "./pages/products.js";
import {showProductCategories} from "./pages/categories/productCategories.js"

document.addEventListener('DOMContentLoaded', () => {
    if (!window.myAppRouter) {
        console.error('Router not found! Make sure router.js is loaded before app.js.');
        return;
    }

    window.myAppRouter.init('app-root');

    window.myAppRouter.addRoute('#/', showDashboard);
    window.myAppRouter.addRoute('#/products', showProducts);
    window.myAppRouter.addRoute('#/product-categories', showProductCategories);

    window.myAppRouter.handleRouteChange();

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
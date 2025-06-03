export class Router {
    constructor(appRootId) {
        this.appRoot = document.getElementById(appRootId);
        this.routes = {};

        if (!this.appRoot) {
            console.error(`Element with ID '${appRootId}' not found. Router cannot initialize.`);
            return;
        }

        window.addEventListener('hashchange', () => this.handleRouteChange());
        console.log("RouteDispatcher initialized.");
    }

    addRoute(path, handler) {
        if (typeof handler !== 'function') {
            console.error(`Handler for path '${path}' is not a function.`);
            return;
        }
        this.routes[path] = handler;
        console.log(`Router added: ${path}`);
    }

    handleRouteChange() {
        const currentPath = window.location.hash || '#/';
        console.log('Current path:', currentPath);

        const handler = this.routes[currentPath];

        if (this.appRoot) {
            this.appRoot.innerHTML = '';

            if (handler) {
                const pageContent = handler();

                if (typeof pageContent === 'string') {
                    this.appRoot.innerHTML = pageContent;
                } else if (pageContent instanceof Node) {
                    this.appRoot.appendChild(pageContent);
                } else if (pageContent !== undefined) {
                    console.warn(`Route handler for '${currentPath}' did not return a string or DOM Node.`);
                }
            } else {
                console.warn(`No route handler found for path: ${currentPath}`);
                this.appRoot.innerHTML =
                    '<h1>404 - Page Not Found</h1><p>Sorry, the page you are looking for does not exist.</p>';
            }
        }
    }

    navigateTo(path) {
        window.location.hash = path;
    }
}
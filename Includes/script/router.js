const router = {
    routes: {},
    appRoot: null,
    init: function (appRootId) {
        this.appRoot = document.getElementById(appRootId)
        if (!this.appRoot) {
            console.error(`Element with ID '${appRootId}' not found. Router cannot initialize.`)
            return
        }

        window.addEventListener('hashchange', () => this.handleRouteChange())

        console.log("Router initialized.")
    },

    addRoute: function (path, handler) {
        if (typeof handler !== 'function') {
            console.error(`Handler for path '${path}' is not a function.`)
            return
        }

        this.routes[path] = handler
        console.log(`Router added: ${path}`)
    },

    handleRouteChange: function () {
        const currentPath = window.location.hash || '#/'
        console.log('Current path:', currentPath)

        const handler = this.routes[currentPath]

        if (handler) {
            const pageContent = handler()

            if (this.appRoot) {
                this.appRoot.innerHTML = ''
            }

            if (typeof pageContent === 'string') {
                this.appRoot.innerHTML = pageContent;
            }

            if (pageContent instanceof Node) {
                this.appRoot.appendChild(pageContent)
            }
        } else {
            console.warn(`No route handler found for path: ${currentPath}`)
            console.log(handler)
            if (this.appRoot) {
                this.appRoot.innerHTML = '<h1>404 - Page Not Found</h1>' +
                    '<p>Sorry, the page you are looking for does not exist.</p>'
            }
        }
    },

    navigateTo: function (path) {
        window.location.hash = path
    }
}

window.myAppRouter = router
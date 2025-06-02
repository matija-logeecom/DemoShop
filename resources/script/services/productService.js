import { AjaxService } from '../ajax.js';

export class ProductService {
    constructor(apiBaseUrl = 'api') {
        this.ajaxService = new AjaxService();
        this.apiBaseUrl = apiBaseUrl;
    }

    /**
     * Creates a new product.
     * @param {FormData} formData - The product data, including the image file.
     * @returns {Promise<Object>} The response from the server.
     */
    async createProduct(formData) {
        // When sending FormData, the browser will set the Content-Type
        // to 'multipart/form-data' automatically, so we don't set it in headers here.
        // The AjaxService's _request method needs to be aware of this or allow overriding.
        // For now, let's assume AjaxService can handle FormData directly or we'll adjust it.

        // We need to make a POST request. The URL would be something like '/api/products'
        // or '/api/createProduct'. Let's use '/api/products' for a RESTful approach.
        return this.ajaxService.postWithFormData(`${this.apiBaseUrl}/product/create`, formData);
    }

    /**
     * Fetches a paginated list of products.
     * @param {number} page - The page number to fetch.
     * @param {number} perPage - Number of items per page.
     * @returns {Promise<Object>} The paginated response from the server.
     * Expected response structure: { data: [], current_page: 1, last_page: 5, total: 50, ... }
     */
    async getProducts(page = 1, perPage = 10) { // Defaulting perPage to 10
        // The backend controller's getProducts method takes 'page' from query params.
        // The perPage is hardcoded to 10 on the backend for now.
        // We can add perPage to the query if the backend supports it: `${this.apiBaseUrl}/products?page=${page}&perPage=${perPage}`
        return this.ajaxService.get(`${this.apiBaseUrl}/products?page=${page}`);
    }

    async deleteProducts(productIdsArray) {
        if (!Array.isArray(productIdsArray) || productIdsArray.length === 0) {
            return Promise.reject(new Error("Product IDs must be a non-empty array."));
        }
        // The backend ProductController::deleteProducts expects JSON body: {"ids": [1,2,3]}
        const payload = { ids: productIdsArray };
        return this.ajaxService.delete(`${this.apiBaseUrl}/products`, payload);
    }

    /**
     * Updates the enabled status for a list of products.
     * @param {number[]} productIdsArray - An array of product IDs.
     * @param {boolean} isEnabled - The new enabled status (true for enabled, false for disabled).
     * @returns {Promise<Object>} The response from the server.
     */
    async updateProductsEnabledStatus(productIdsArray, isEnabled) {
        if (!Array.isArray(productIdsArray) || productIdsArray.length === 0) {
            return Promise.reject(new Error("Product IDs must be a non-empty array."));
        }
        if (typeof isEnabled !== 'boolean') {
            return Promise.reject(new Error("Enabled status must be a boolean value (true or false)."));
        }

        const payload = {
            ids: productIdsArray,
            is_enabled: isEnabled
        };

        return this.ajaxService.put(`${this.apiBaseUrl}/products/status-batch`, payload);
    }
}
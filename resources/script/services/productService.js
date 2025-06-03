import {AjaxService} from '../ajax.js';

export class ProductService {
    constructor(apiBaseUrl = 'api') {
        this.ajaxService = new AjaxService();
        this.apiBaseUrl = apiBaseUrl;
    }

    async createProduct(formData) {
        return this.ajaxService.postWithFormData(`${this.apiBaseUrl}/products`, formData);
    }

    async getProducts(page = 1, perPage = 10, filters = {}) {
        let queryParams = `page=${page}`;

        const filterParams = new URLSearchParams();
        for (const key in filters) {
            if (filters.hasOwnProperty(key) &&
                (filters[key] !== null && filters[key] !== undefined && filters[key] !== '')) {
                filterParams.append(key, filters[key]);
            }
        }

        const filterString = filterParams.toString();
        if (filterString) {
            queryParams += `&${filterString}`;
        }

        return this.ajaxService.get(`${this.apiBaseUrl}/products?${queryParams}`);
    }

    async deleteProducts(productIdsArray) {
        if (!Array.isArray(productIdsArray) || productIdsArray.length === 0) {
            return Promise.reject(new Error("Product IDs must be a non-empty array."));
        }
        const payload = {ids: productIdsArray};
        return this.ajaxService.delete(`${this.apiBaseUrl}/products`, payload);
    }

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

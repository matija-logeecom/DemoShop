import { AjaxService } from '../ajax.js'; // Adjust path if AjaxService moved

export class CategoryService {
    constructor(apiBaseUrl = 'api') { // Default API base URL
        this.ajaxService = new AjaxService(); // Instantiate AjaxService
        this.apiBaseUrl = apiBaseUrl;
    }

    async fetchCategories() {
        return this.ajaxService.get(`${this.apiBaseUrl}/categories`);
    }

    async createCategory(categoryData) {
        return this.ajaxService.post(`${this.apiBaseUrl}/createCategory`, categoryData);
    }

    async updateCategory(categoryId, categoryData) {
        return this.ajaxService.put(`${this.apiBaseUrl}/update/${categoryId}`, categoryData);
    }

    async removeCategory(categoryId) {
        return this.ajaxService.delete(`${this.apiBaseUrl}/delete/${categoryId}`);
    }
}
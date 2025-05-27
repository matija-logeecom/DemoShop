import { getData, postData, putData, deleteData } from "../../ajax.js";

const API_BASE_URL = 'api';

export async function fetchCategories() {
    return getData(`${API_BASE_URL}/categories`);
}

export async function createCategory(categoryData) {
    return postData(`${API_BASE_URL}/createCategory`, categoryData);
}

export async function updateCategory(categoryId, categoryData) {
    return putData(`${API_BASE_URL}/update/${categoryId}`, categoryData);
}

export async function removeCategory(categoryId) {
    return deleteData(`${API_BASE_URL}/delete/${categoryId}`);
}
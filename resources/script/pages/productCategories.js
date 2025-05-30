import {CategoryPageController} from '../controllers/categoryPageController.js';

export function showProductCategories() {
    const wrapper = document.createElement('div');
    new CategoryPageController(wrapper);
    return wrapper;
}
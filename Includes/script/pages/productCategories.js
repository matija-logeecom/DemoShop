import { CategoryPageController } from '../controllers/categoryPageController.js'; // Adjust path
export function showProductCategories() {
    const wrapper = document.createElement('div');
    new CategoryPageController(wrapper); // Controller manages its own content within wrapper
    return wrapper;
}
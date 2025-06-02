import { CategoryService } from '../services/categoryService.js';
import { ProductService } from '../services/productService.js'; // Import ProductService

export function showProducts() {
    const wrapper = document.createElement('div');
    wrapper.id = 'products-page-container';
    wrapper.innerHTML = '<p>Loading products...</p>';

    // Instantiate ProductService once for the page
    const productService = new ProductService();

    fetch('/resources/pages/products.html') // Ensure this path is correct
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(htmlText => {
            wrapper.innerHTML = '';
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = htmlText;
            while (tempDiv.firstChild) {
                wrapper.appendChild(tempDiv.firstChild);
            }
            // Pass productService instance to setupEventListeners
            setupEventListeners(wrapper, productService);
            const categoryService = new CategoryService();
            populateCategoryDropdown(wrapper, categoryService);
        })
        .catch(error => {
            console.error('Error fetching products.html:', error);
            wrapper.innerHTML = '<p>Error loading products page. Please try again later.</p>';
        });

    return wrapper;
}

async function populateCategoryDropdown(container, categoryService) {
    const selectElement = container.querySelector('#product-category');
    if (!selectElement) {
        console.error('Category select element (#product-category) not found.');
        return;
    }
    try {
        const categories = await categoryService.fetchCategories();
        selectElement.innerHTML = '<option value="">Select Category</option>';
        if (categories && categories.length > 0) {
            categories.forEach(category => {
                const option = document.createElement('option');
                option.value = category.id;
                option.textContent = category.title;
                selectElement.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Failed to fetch or populate categories:', error);
        selectElement.innerHTML = '<option value="">Error loading categories</option>';
        selectElement.disabled = true;
    }
}

function setupEventListeners(container, productService) { // productService is now passed in
    const addProductView = container.querySelector('#add-product-view');
    const productListView = container.querySelector('#product-list-view');
    const btnAddNewProduct = container.querySelector('#btn-add-new-product');
    const btnCancelAddProduct = container.querySelector('#btn-cancel-add-product');
    const addProductForm = container.querySelector('#form-add-product');
    const productImageInput = container.querySelector('#product-image');
    const productImagePreview = container.querySelector('#product-image-preview');
    const productImageError = container.querySelector('#product-image-error');
    const btnSaveProduct = container.querySelector('#btn-save-product');
    const categorySelect = container.querySelector('#product-category');


    const resetFormAndImage = () => {
        if (addProductForm) addProductForm.reset();
        if (productImageInput) productImageInput.value = null;
        if (productImagePreview) productImagePreview.innerHTML = '<span class="preview-text">Image Preview</span>';
        if (productImageError) productImageError.textContent = '';
        if (categorySelect) categorySelect.selectedIndex = 0;
    };

    if (btnAddNewProduct && addProductView && productListView) {
        btnAddNewProduct.addEventListener('click', () => {
            productListView.classList.add('view-hidden');
            addProductView.classList.remove('view-hidden');
            resetFormAndImage();
            if (btnSaveProduct) {
                btnSaveProduct.disabled = false;
                btnSaveProduct.textContent = 'Save Product';
            }
        });
    }

    if (btnCancelAddProduct && addProductView && productListView) {
        btnCancelAddProduct.addEventListener('click', () => {
            addProductView.classList.add('view-hidden');
            productListView.classList.remove('view-hidden');
            resetFormAndImage();
        });
    }

    if (addProductForm && productService) { // Check if productService is available
        addProductForm.addEventListener('submit', async (event) => {
            event.preventDefault();

            // Re-check image validation status before submission
            if (productImageInput.files.length > 0 && productImageError.textContent !== '') {
                alert('Please correct the image errors before saving.');
                return;
            }
            // Basic client-side validation for required fields (HTML5 'required' helps, but JS check is good)
            const sku = container.querySelector('#product-sku').value.trim();
            const title = container.querySelector('#product-title').value.trim();
            const price = container.querySelector('#product-price').value.trim();
            const categoryId = categorySelect.value;

            if (!sku || !title || !price || !categoryId) {
                alert('Please fill in all required fields: SKU, Title, Category, and Price.');
                return;
            }


            if (btnSaveProduct) {
                btnSaveProduct.disabled = true;
                btnSaveProduct.textContent = 'Saving...';
            }

            const formData = new FormData(addProductForm);
            // Note: For unchecked checkboxes ('enabled', 'featured'), if the backend
            // expects a '0' value, you might need to manually check and append:
            // if (!container.querySelector('#product-enabled').checked) formData.set('enabled', '0');
            // if (!container.querySelector('#product-featured').checked) formData.set('featured', '0');
            // However, the current PHP controller handles missing fields for checkboxes as 'false'.
            // If 'enabled' or 'featured' are checked, FormData(addProductForm) includes them with value="1".

            try {
                const response = await productService.createProduct(formData); // productService is defined in the outer scope
                alert(response.message || 'Product created successfully!');

                resetFormAndImage();
                addProductView.classList.add('view-hidden');
                productListView.classList.remove('view-hidden');

                // TODO: Add logic here to refresh the product list table in productListView
                // For example: await fetchAndDisplayProducts();

            } catch (error) {
                console.error('Failed to create product:', error);
                let errorMessage = 'Failed to create product. Please try again.';
                if (error.responseBody && error.responseBody.message) {
                    errorMessage = error.responseBody.message;
                    if (error.responseBody.errors) { // If backend sends specific field errors
                        let fieldErrors = [];
                        for (const key in error.responseBody.errors) {
                            fieldErrors.push(`${key}: ${error.responseBody.errors[key]}`);
                        }
                        errorMessage += '\nDetails:\n' + fieldErrors.join('\n');
                    }
                } else if (error.message) {
                    errorMessage = error.message;
                }
                alert(errorMessage);
            } finally {
                if (btnSaveProduct) {
                    btnSaveProduct.disabled = false;
                    btnSaveProduct.textContent = 'Save Product';
                }
            }
        });
    }

    // Image validation logic (from previous step, assumed to be complete)
    if (productImageInput && productImagePreview && productImageError) {
        productImageInput.addEventListener('change', function() {
            const file = this.files[0];
            productImageError.textContent = '';

            if (file) {
                if (!file.type.startsWith('image/')) {
                    productImageError.textContent = 'Invalid file type. Please select an image.';
                    if (productImageInput) productImageInput.value = null;
                    if (productImagePreview) productImagePreview.innerHTML = '<span class="preview-text">Image Preview</span>';
                    return;
                }
                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = new Image();
                    img.onload = function() {
                        const width = img.naturalWidth;
                        const height = img.naturalHeight;
                        if (height === 0) {
                            productImageError.textContent = 'Image height cannot be zero.';
                            if (productImageInput) productImageInput.value = null;
                            if (productImagePreview) productImagePreview.innerHTML = '<span class="preview-text">Image Preview</span>';
                            return;
                        }
                        const ratio = width / height;
                        const minWidth = 600;
                        const minRatio = (4 / 3) - 0.001;
                        const maxRatio = (16 / 9) + 0.001;
                        let isValid = true;
                        let errors = [];
                        if (width < minWidth) {
                            isValid = false;
                            errors.push(`Image width must be at least ${minWidth}px (is ${width}px).`);
                        }
                        if (ratio < minRatio || ratio > maxRatio) {
                            isValid = false;
                            errors.push(`Aspect ratio must be between 4:3 (1.33) and 16:9 (1.78). Yours is ~${ratio.toFixed(2)}:1.`);
                        }
                        if (isValid) {
                            productImagePreview.innerHTML = `<img src="${e.target.result}" alt="Image preview">`;
                            productImageError.textContent = '';
                        } else {
                            productImageError.innerHTML = errors.join('<br>');
                            productImagePreview.innerHTML = '<span class="preview-text">Image Preview</span>';
                            if (productImageInput) productImageInput.value = null;
                        }
                    };
                    img.onerror = function() {
                        productImageError.textContent = 'Could not load image. Please select a valid image file.';
                        if (productImageInput) productImageInput.value = null;
                        if (productImagePreview) productImagePreview.innerHTML = '<span class="preview-text">Image Preview</span>';
                    };
                    img.src = e.target.result;
                };
                reader.onerror = function() {
                    productImageError.textContent = 'Error reading file.';
                    if (productImageInput) productImageInput.value = null;
                    if (productImagePreview) productImagePreview.innerHTML = '<span class="preview-text">Image Preview</span>';
                };
                reader.readAsDataURL(file);
            } else {
                if (productImageInput) productImageInput.value = null;
                if (productImagePreview) productImagePreview.innerHTML = '<span class="preview-text">Image Preview</span>';
                if (productImageError) productImageError.textContent = '';
            }
        });
    }
}
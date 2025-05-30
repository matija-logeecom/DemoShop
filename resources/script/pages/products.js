import { CategoryService } from '../services/categoryService.js';

export function showProducts() {
    const wrapper = document.createElement('div');
    wrapper.id = 'products-page-container';
    wrapper.innerHTML = '<p>Loading products...</p>';

    fetch('/resources/pages/products.html')
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
            setupEventListeners(wrapper);
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

function setupEventListeners(container) {
    const addProductView = container.querySelector('#add-product-view');
    const productListView = container.querySelector('#product-list-view');
    const btnAddNewProduct = container.querySelector('#btn-add-new-product');
    const btnCancelAddProduct = container.querySelector('#btn-cancel-add-product');
    const addProductForm = container.querySelector('#form-add-product');
    const productImageInput = container.querySelector('#product-image');
    const productImagePreview = container.querySelector('#product-image-preview');
    const productImageError = container.querySelector('#product-image-error'); // Get the error div

    // Helper to clear image input and messages
    const resetImageFields = () => {
        if (productImageInput) productImageInput.value = null; // Clear the file input
        if (productImagePreview) productImagePreview.innerHTML = '<span class="preview-text">Image Preview</span>';
        if (productImageError) productImageError.textContent = '';
    };

    if (btnAddNewProduct && addProductView && productListView) {
        btnAddNewProduct.addEventListener('click', () => {
            productListView.classList.add('view-hidden');
            addProductView.classList.remove('view-hidden');
            resetImageFields(); // Reset image fields when opening form
            if (addProductForm) addProductForm.reset(); // Reset other form fields
            const categorySelect = container.querySelector('#product-category');
            if (categorySelect) categorySelect.selectedIndex = 0; // Reset category dropdown
        });
    }

    if (btnCancelAddProduct && addProductView && productListView) {
        btnCancelAddProduct.addEventListener('click', () => {
            addProductView.classList.add('view-hidden');
            productListView.classList.remove('view-hidden');
            if (addProductForm) addProductForm.reset();
            resetImageFields();
            const categorySelect = container.querySelector('#product-category');
            if (categorySelect) categorySelect.selectedIndex = 0;
        });
    }

    if (addProductForm) {
        addProductForm.addEventListener('submit', event => {
            event.preventDefault();
            // Before submitting, you might want to re-validate the image if a file is selected
            // or ensure that a valid image (if mandatory) has been chosen.
            if (productImageInput.files.length > 0 && productImageError.textContent !== '') {
                alert('Please select a valid image that meets the requirements.');
                return;
            }
            console.log('Product form submitted. Implement save logic.');
        });
    }

    if (productImageInput && productImagePreview && productImageError) {
        productImageInput.addEventListener('change', function() {
            const file = this.files[0];
            productImageError.textContent = ''; // Clear previous errors

            if (file) {
                if (!file.type.startsWith('image/')) {
                    productImageError.textContent = 'Invalid file type. Please select an image.';
                    resetImageFields(); // Ensure resetImageFields clears productImageInput.value
                    return;
                }

                const reader = new FileReader();
                reader.onload = function(e) {
                    const img = new Image();
                    img.onload = function() {
                        const width = img.naturalWidth;
                        const height = img.naturalHeight;

                        // Ensure height is not zero to avoid division by zero
                        if (height === 0) {
                            productImageError.textContent = 'Image height cannot be zero.';
                            resetImageFields();
                            return;
                        }
                        const ratio = width / height;

                        const minWidth = 600;
                        // Define ratios with a small epsilon for tolerance
                        const minRatio = (4 / 3) - 0.001; // Approx 1.3333 - 0.001 = 1.3323
                        const maxRatio = (16 / 9) + 0.001; // Approx 1.7777 + 0.001 = 1.7787

                        let isValid = true;
                        let errors = [];

                        if (width < minWidth) {
                            isValid = false;
                            errors.push(`Image width must be at least ${minWidth}px (is ${width}px).`);
                        }

                        // Check if the calculated ratio is outside the slightly adjusted tolerant range
                        if (ratio < minRatio || ratio > maxRatio) {
                            isValid = false;
                            // Provide more precise feedback for debugging the ratio
                            errors.push(`Aspect ratio must be between 4:3 (1.33) and 16:9 (1.78). Yours is ~${ratio.toFixed(2)}:1.`);
                        }

                        if (isValid) {
                            productImagePreview.innerHTML = `<img src="${e.target.result}" alt="Image preview">`;
                            productImageError.textContent = '';
                        } else {
                            productImageError.innerHTML = errors.join('<br>');
                            productImagePreview.innerHTML = '<span class="preview-text">Image Preview</span>';
                            if (productImageInput) productImageInput.value = null; // Reset the file input
                        }
                    };
                    img.onerror = function() {
                        productImageError.textContent = 'Could not load image. Please select a valid image file.';
                        resetImageFields();
                    };
                    img.src = e.target.result;
                };
                reader.onerror = function() {
                    productImageError.textContent = 'Error reading file.';
                    resetImageFields();
                };
                reader.readAsDataURL(file);
            } else {
                resetImageFields();
            }
        });
    }
}
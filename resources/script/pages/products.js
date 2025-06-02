import { CategoryService } from '../services/categoryService.js';
import { ProductService } from '../services/productService.js';

// Store current page and last page info for pagination controls
let currentPage = 1;
let lastPage = 1;
let currentContainer = null; // To store the main wrapper for use in pagination clicks
let currentProductServiceInstance = null; // To store the productService instance

export function showProducts() {
    const wrapper = document.createElement('div');
    wrapper.id = 'products-page-container';
    wrapper.innerHTML = '<p>Loading products...</p>';

    // Store wrapper and service for access by pagination handlers
    currentContainer = wrapper;
    currentProductServiceInstance = new ProductService();

    fetch('/resources/pages/products.html') // Ensure this path is correct for your setup
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(htmlText => {
            wrapper.innerHTML = ''; // Clear "Loading products..."
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = htmlText;
            while (tempDiv.firstChild) {
                wrapper.appendChild(tempDiv.firstChild);
            }

            // Pass currentProductServiceInstance to setupEventListeners
            setupEventListeners(wrapper, currentProductServiceInstance);

            const categoryService = new CategoryService();
            populateCategoryDropdown(wrapper, categoryService);

            // Initial fetch and display of products for page 1
            fetchAndDisplayProducts(1); // Uses global currentContainer and currentProductServiceInstance
        })
        .catch(error => {
            console.error('Error fetching products.html:', error);
            wrapper.innerHTML = '<p>Error loading products page. Please try again later.</p>';
        });

    return wrapper;
}

async function fetchAndDisplayProducts(page = 1) {
    if (!currentContainer || !currentProductServiceInstance) {
        console.error('Container or ProductService not initialized for fetchAndDisplayProducts.');
        return;
    }

    const productTableBody = currentContainer.querySelector('#products-table-body');
    if (!productTableBody) {
        console.error('Product table body not found');
        return;
    }
    productTableBody.innerHTML = '<tr><td colspan="9">Loading products...</td></tr>';

    try {
        const paginatedResult = await currentProductServiceInstance.getProducts(page);

        renderProductTable(paginatedResult.data || [], productTableBody);
        updatePaginationUI(paginatedResult); // Uses global currentContainer and currentProductServiceInstance

    } catch (error) {
        console.error('Failed to fetch products:', error);
        productTableBody.innerHTML = '<tr><td colspan="9">Error loading products. Please try again.</td></tr>';
        const pageInfoEl = currentContainer.querySelector('#page-info');
        if (pageInfoEl) pageInfoEl.textContent = 'Page N/A';
        currentContainer.querySelectorAll('.pagination-controls button').forEach(btn => btn.disabled = true);
    }
}

function renderProductTable(products, tableBodyElement) {
    tableBodyElement.innerHTML = '';

    if (!products || products.length === 0) {
        tableBodyElement.innerHTML = '<tr><td colspan="9">No products found.</td></tr>';
        return;
    }

    products.forEach(product => {
        const row = tableBodyElement.insertRow();
        row.insertCell().innerHTML = `<input type="checkbox" class="product-select-row" data-product-id="${product.id}">`;
        row.insertCell().textContent = product.title || 'N/A';
        row.insertCell().textContent = product.sku || 'N/A';
        row.insertCell().textContent = product.brand || 'N/A';

        // Display Category Hierarchy
        row.insertCell().textContent = product.category_hierarchy_display || (product.category_id ? `ID: ${product.category_id}` : 'N/A');

        row.insertCell().textContent = product.short_description || '';
        row.insertCell().textContent = product.price !== null ? parseFloat(product.price).toFixed(2) : 'N/A';

        const enabledCell = row.insertCell();
        const enabledCheckbox = document.createElement('input');
        enabledCheckbox.type = 'checkbox';
        enabledCheckbox.checked = !!product.is_enabled;
        enabledCheckbox.disabled = true;
        enabledCell.appendChild(enabledCheckbox);
        enabledCell.style.textAlign = 'center';

        row.insertCell().innerHTML = `
            <button class="admin-button edit-button" data-product-id="${product.id}" title="Edit">✏️</button>
            <button class="admin-button delete-button" data-product-id="${product.id}" title="Delete">🗑️</button>
        `;
    });
}

function updatePaginationUI(paginationData) {
    if (!currentContainer || !paginationData) {
        if (currentContainer) {
            currentContainer.querySelectorAll('.pagination-controls button').forEach(btn => btn.disabled = true);
            const pageInfoElNull = currentContainer.querySelector('#page-info');
            if (pageInfoElNull) pageInfoElNull.textContent = 'Page N/A';
        }
        return;
    }

    const pageInfoEl = currentContainer.querySelector('#page-info');
    const btnFirstPage = currentContainer.querySelector('#btn-first-page');
    const btnPrevPage = currentContainer.querySelector('#btn-prev-page');
    const btnNextPage = currentContainer.querySelector('#btn-next-page');
    const btnLastPage = currentContainer.querySelector('#btn-last-page');

    currentPage = paginationData.current_page;
    lastPage = paginationData.last_page;

    if (pageInfoEl) pageInfoEl.textContent = `Page ${currentPage} / ${lastPage}`;

    if (btnFirstPage) btnFirstPage.disabled = (currentPage <= 1);
    if (btnPrevPage) btnPrevPage.disabled = (currentPage <= 1);
    if (btnNextPage) btnNextPage.disabled = (currentPage >= lastPage);
    if (btnLastPage) btnLastPage.disabled = (currentPage >= lastPage);
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

function setupEventListeners(container, productService) {
    // ... (Add/Cancel Product form event listeners remain the same)
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

    if (addProductForm && productService) {
        addProductForm.addEventListener('submit', async (event) => {
            event.preventDefault();
            if (productImageInput.files.length > 0 && productImageError.textContent !== '') {
                alert('Please correct the image errors before saving.');
                return;
            }
            const sku = container.querySelector('#product-sku').value.trim();
            const title = container.querySelector('#product-title').value.trim();
            const price = container.querySelector('#product-price').value.trim();
            const categoryIdVal = categorySelect.value;
            if (!sku || !title || !price || !categoryIdVal) {
                alert('Please fill in all required fields: SKU, Title, Category, and Price.');
                return;
            }
            if (btnSaveProduct) {
                btnSaveProduct.disabled = true;
                btnSaveProduct.textContent = 'Saving...';
            }
            const formData = new FormData(addProductForm);
            try {
                const response = await productService.createProduct(formData);
                alert(response.message || 'Product created successfully!');
                resetFormAndImage();
                addProductView.classList.add('view-hidden');
                productListView.classList.remove('view-hidden');
                fetchAndDisplayProducts(1); // Refresh list to page 1
            } catch (error) {
                console.error('Failed to create product:', error);
                let errorMessage = 'Failed to create product. Please try again.';
                if (error.responseBody && error.responseBody.message) {
                    errorMessage = error.responseBody.message;
                    if (error.responseBody.errors) {
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

    // Image validation logic (from previous step)
    if (productImageInput && productImagePreview && productImageError) {
        productImageInput.addEventListener('change', function() { /* ... existing image validation logic ... */
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
                        if (width < minWidth) { isValid = false; errors.push(`Image width must be at least ${minWidth}px (is ${width}px).`); }
                        if (ratio < minRatio || ratio > maxRatio) { isValid = false; errors.push(`Aspect ratio must be between 4:3 (1.33) and 16:9 (1.78). Yours is ~${ratio.toFixed(2)}:1.`); }
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


    // --- Pagination Button Event Listeners ---
    const btnFirstPage = container.querySelector('#btn-first-page');
    const btnPrevPage = container.querySelector('#btn-prev-page');
    const btnNextPage = container.querySelector('#btn-next-page');
    const btnLastPage = container.querySelector('#btn-last-page');

    if (btnFirstPage) {
        btnFirstPage.addEventListener('click', () => {
            if (currentPage > 1) fetchAndDisplayProducts(1);
        });
    }
    if (btnPrevPage) {
        btnPrevPage.addEventListener('click', () => {
            if (currentPage > 1) fetchAndDisplayProducts(currentPage - 1);
        });
    }
    if (btnNextPage) {
        btnNextPage.addEventListener('click', () => {
            // Ensure lastPage is a number and currentPage is less than lastPage
            if (typeof lastPage === 'number' && currentPage < lastPage) {
                fetchAndDisplayProducts(currentPage + 1);
            }
        });
    }
    if (btnLastPage) {
        btnLastPage.addEventListener('click', () => {
            if (typeof lastPage === 'number' && currentPage < lastPage) {
                fetchAndDisplayProducts(lastPage);
            }
        });
    }
}
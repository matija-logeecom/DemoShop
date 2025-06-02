import { CategoryService } from '../services/categoryService.js';
import { ProductService } from '../services/productService.js';

// Module-level variables to store state and instances
let currentPage = 1;
let lastPage = 1;
let currentContainer = null; // Stores the main wrapper for the products page
let currentProductServiceInstance = null; // Stores the instance of ProductService

export function showProducts() {
    const wrapper = document.createElement('div');
    wrapper.id = 'products-page-container';
    wrapper.innerHTML = '<p>Loading products...</p>'; // Initial loading message

    // Assign to module-level variables for broader access within this module
    currentContainer = wrapper;
    currentProductServiceInstance = new ProductService();

    fetch('/resources/pages/products.html') // Config to your HTML structure
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

            setupEventListeners(wrapper, currentProductServiceInstance);

            const categoryService = new CategoryService();
            populateCategoryDropdown(wrapper, categoryService);

            // Initial fetch and display of products for page 1
            fetchAndDisplayProducts(1);
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
        console.error('Product table body (#products-table-body) not found.');
        return;
    }
    productTableBody.innerHTML = '<tr><td colspan="9">Loading products...</td></tr>'; // colspan matches number of columns

    try {
        const paginatedResult = await currentProductServiceInstance.getProducts(page);

        renderProductTable(paginatedResult.data || [], productTableBody);
        updatePaginationUI(paginatedResult);

    } catch (error) {
        console.error('Failed to fetch products:', error);
        productTableBody.innerHTML = '<tr><td colspan="9">Error loading products. Please try again.</td></tr>';
        const pageInfoEl = currentContainer.querySelector('#page-info');
        if (pageInfoEl) pageInfoEl.textContent = 'Page N/A';
        if (currentContainer) {
            currentContainer.querySelectorAll('.pagination-controls button').forEach(btn => btn.disabled = true);
        }
    }
}

function renderProductTable(products, tableBodyElement) {
    tableBodyElement.innerHTML = '';

    if (!products || products.length === 0) {
        tableBodyElement.innerHTML = '<tr><td colspan="9">No products found.</td></tr>'; // colspan matches
        return;
    }

    products.forEach(product => {
        const row = tableBodyElement.insertRow();
        // Column 1: Individual Checkbox
        row.insertCell().innerHTML = `<input type="checkbox" class="product-select-row" data-product-id="${product.id}">`;
        // Column 2: Title
        row.insertCell().textContent = product.title || 'N/A';
        // Column 3: SKU
        row.insertCell().textContent = product.sku || 'N/A';
        // Column 4: Brand
        row.insertCell().textContent = product.brand || 'N/A';
        // Column 5: Category (Hierarchy)
        row.insertCell().textContent = product.category_hierarchy_display || (product.category_id ? `ID: ${product.category_id}` : 'N/A');
        // Column 6: Short Description
        row.insertCell().textContent = product.short_description || '';
        // Column 7: Price
        row.insertCell().textContent = product.price !== null ? parseFloat(product.price).toFixed(2) : 'N/A';

        // Column 8: Enabled
        const enabledCell = row.insertCell();
        const enabledCheckbox = document.createElement('input');
        enabledCheckbox.type = 'checkbox';
        enabledCheckbox.checked = !!product.is_enabled;
        enabledCheckbox.disabled = true; // Readonly display
        enabledCell.appendChild(enabledCheckbox);
        enabledCell.style.textAlign = 'center';

        // Column 9: Actions
        row.insertCell().innerHTML = `
            <button class="admin-button edit-button" data-product-id="${product.id}" title="Edit">🖊️</button>
            <button class="admin-button delete-button" data-product-id="${product.id}" title="Delete">🗑️</button>
        `;
    });
}

function updatePaginationUI(paginationData) {
    if (!currentContainer || !paginationData || typeof paginationData.current_page === 'undefined') {
        if (currentContainer) {
            currentContainer.querySelectorAll('.pagination-controls button').forEach(btn => btn.disabled = true);
            const pageInfoElNull = currentContainer.querySelector('#page-info');
            if (pageInfoElNull) pageInfoElNull.textContent = 'Page N/A';
        }
        currentPage = 1;
        lastPage = 1;
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
        console.error('Failed to fetch or populate categories for dropdown:', error);
        selectElement.innerHTML = '<option value="">Error loading categories</option>';
        selectElement.disabled = true;
    }
}

function setupEventListeners(container, productService) {
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
    const productTableBody = container.querySelector('#products-table-body'); // Moved for broader scope

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
                fetchAndDisplayProducts(1);
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
                        const minWidth = 600;
                        let isValid = true;
                        let errors = [];
                        if (width < minWidth) {
                            isValid = false;
                            errors.push(`Image width must be at least ${minWidth}px (is ${width}px).`);
                        }
                        // Integer math for aspect ratio
                        const isAtLeast4x3 = (width * 3) >= (height * 4);
                        const isAtMost16x9 = (width * 9) <= (height * 16);
                        if (!isAtLeast4x3 || !isAtMost16x9) {
                            isValid = false;
                            const calculatedRatioForDisplay = width / height;
                            errors.push(`Aspect ratio must be between 4:3 (approx 1.33) and 16:9 (approx 1.78). Yours is ~${calculatedRatioForDisplay.toFixed(2)}:1.`);
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

    // Pagination Button Event Listeners
    const btnFirstPage = container.querySelector('#btn-first-page');
    const btnPrevPage = container.querySelector('#btn-prev-page');
    const btnNextPage = container.querySelector('#btn-next-page');
    const btnLastPage = container.querySelector('#btn-last-page');

    if (btnFirstPage) btnFirstPage.addEventListener('click', () => { if (currentPage > 1) fetchAndDisplayProducts(1); });
    if (btnPrevPage) btnPrevPage.addEventListener('click', () => { if (currentPage > 1) fetchAndDisplayProducts(currentPage - 1); });
    if (btnNextPage) btnNextPage.addEventListener('click', () => { if (typeof lastPage === 'number' && currentPage < lastPage) fetchAndDisplayProducts(currentPage + 1); });
    if (btnLastPage) btnLastPage.addEventListener('click', () => { if (typeof lastPage === 'number' && currentPage < lastPage) fetchAndDisplayProducts(lastPage); });

    // Event Listener for Table Actions (Delete and Row Checkbox for selection)
    if (productTableBody && productService) {
        productTableBody.addEventListener('click', async (event) => {
            const targetElement = event.target;

            // Handle individual delete button clicks
            const deleteButton = targetElement.closest('.delete-button');
            if (deleteButton) {
                const productId = deleteButton.dataset.productId;
                if (!productId) {
                    console.error('Product ID not found on delete button.');
                    return;
                }
                if (confirm(`Are you sure you want to delete product ID ${productId}?`)) {
                    try {
                        const response = await productService.deleteProducts([parseInt(productId, 10)]);
                        alert(response.message || `Product(s) deleted successfully.`);
                        fetchAndDisplayProducts(currentPage);
                    } catch (error) {
                        console.error('Failed to delete product:', error);
                        let errorMessage = 'Failed to delete product.';
                        if (error.responseBody && error.responseBody.message) {
                            errorMessage = error.responseBody.message;
                        } else if (error.message) {
                            errorMessage = error.message;
                        }
                        alert(errorMessage);
                    }
                }
            }
            // Individual row checkboxes (.product-select-row) are now just for selection.
            // No "select all" sync logic needed here anymore.
        });
    }

    // "Delete Selected" button functionality
    const btnDeleteSelected = container.querySelector('#btn-delete-selected-products');
    if (btnDeleteSelected && productTableBody && productService) {
        btnDeleteSelected.addEventListener('click', async () => {
            const selectedCheckboxes = productTableBody.querySelectorAll('.product-select-row:checked');
            const selectedIds = Array.from(selectedCheckboxes).map(cb => parseInt(cb.dataset.productId, 10));

            if (selectedIds.length === 0) {
                alert('Please select products to delete.');
                return;
            }

            if (confirm(`Are you sure you want to delete ${selectedIds.length} selected product(s)?`)) {
                try {
                    const response = await productService.deleteProducts(selectedIds);
                    alert(response.message || `${response.deletedCount || selectedIds.length} product(s) deleted successfully.`);
                    fetchAndDisplayProducts(currentPage);
                } catch (error) {
                    console.error('Failed to delete selected products:', error);
                    let errorMessage = 'Failed to delete selected products.';
                    if (error.responseBody && error.responseBody.message) {
                        errorMessage = error.responseBody.message;
                    } else if (error.message) {
                        errorMessage = error.message;
                    }
                    alert(errorMessage);
                }
            }
        });
    }

    // Event Listeners for Batch Enable/Disable Buttons
    const btnEnableSelected = container.querySelector('#btn-enable-selected-products');
    const btnDisableSelected = container.querySelector('#btn-disable-selected-products');

    const handleBatchStatusUpdate = async (isEnabledStatus) => {
        if (!productTableBody || !productService) return; // Ensure dependencies are available

        const selectedCheckboxes = productTableBody.querySelectorAll('.product-select-row:checked');
        const selectedIds = Array.from(selectedCheckboxes).map(cb => parseInt(cb.dataset.productId, 10));

        if (selectedIds.length === 0) {
            alert('Please select products to update.');
            return;
        }

        const actionText = isEnabledStatus ? "enable" : "disable";
        if (confirm(`Are you sure you want to ${actionText} ${selectedIds.length} selected product(s)?`)) {
            try {
                const response = await productService.updateProductsEnabledStatus(selectedIds, isEnabledStatus);
                // Backend response includes 'message' and 'updatedCount' (though updatedCount isn't explicitly in JsonResponse in controller yet)
                alert(response.message || `Product status updated successfully.`);
                fetchAndDisplayProducts(currentPage);
            } catch (error) {
                console.error(`Failed to ${actionText} selected products:`, error);
                let errorMessage = `Failed to ${actionText} products.`;
                if (error.responseBody && error.responseBody.message) {
                    errorMessage = error.responseBody.message;
                } else if (error.message) {
                    errorMessage = error.message;
                }
                alert(errorMessage);
            }
        }
    };

    if (btnEnableSelected) {
        btnEnableSelected.addEventListener('click', () => handleBatchStatusUpdate(true));
    }

    if (btnDisableSelected) {
        btnDisableSelected.addEventListener('click', () => handleBatchStatusUpdate(false));
    }
}
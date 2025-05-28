// demoshop/Includes/script/controllers/CategoryPageController.js
import { CategoryService } from '../services/categoryService.js';
import * as categoryDOM from '../ui/categoryDOM.js';

export class CategoryPageController {
    constructor(wrapperElement) {
        this.wrapper = wrapperElement;
        this.categoryService = new CategoryService();

        // State variables
        this.allCategoriesFlat = [];
        this.categoryTree = [];
        this.selectedCategory = null;
        this.isEditingOrCreating = false;

        // DOM Element References, populated by _initializeUI
        this.domElements = {};

        this._initializeUI();
        this._loadInitialData();
    }

    _initializeUI() {
        this.wrapper.classList.add('categories-page-wrapper');
        // initializePageStructure creates the page skeleton and returns references to elements
        this.domElements = categoryDOM.initializePageStructure(this.wrapper);
        this._attachEventListeners();
    }

    _attachEventListeners() {
        this.domElements.addRootCategoryBtnEl.addEventListener('click', () => this.handleAddRootCategory());
        this.domElements.addSubCategoryBtnEl.addEventListener('click', () => this.handleAddSubCategory());
        this.domElements.editCategoryBtnEl.addEventListener('click', () => this.handleEditCategory());
        this.domElements.saveCategoryBtnEl.addEventListener('click', async () => await this.handleSaveButton());
        this.domElements.cancelCategoryBtnEl.addEventListener('click', () => this.handleCancelOperation());
        this.domElements.deleteCategoryBtnEl.addEventListener('click', async () => await this.handleDeleteCategory());
        // Note: The click listener for category selection is attached within categoryDOM.renderCategoryTree
        // by passing this.handleCategorySelect.bind(this) or an arrow function.
    }

    async _loadInitialData() {
        try {
            if (this.domElements.categoryTreeContainerEl) {
                this.domElements.categoryTreeContainerEl.innerHTML = 'Loading data...';
            }
            const rawCategories = await this.categoryService.fetchCategories();
            this.allCategoriesFlat = Array.isArray(rawCategories) ? rawCategories : [];
            this.categoryTree = this._buildCategoryTreeFromServer(this.allCategoriesFlat);

            this._renderTree(); // Call internal method to render tree
            this.updateAndRenderDetailsPanel(null, true); // Initial state for details panel
            this.updateUIStates(); // Initial UI states

        } catch (error) {
            console.error("CategoryPageController: Error in _loadInitialData:", error);
            if (this.domElements.categoryTreeContainerEl) {
                this.domElements.categoryTreeContainerEl.innerHTML = '<li>Error loading categories. Please try again.</li>';
            }
            this.updateUIStates(); // Ensure UI state reflects error if needed
        }
    }

    _renderTree() {
        if (categoryDOM.renderCategoryTree && this.domElements.categoryTreeContainerEl) {
            categoryDOM.renderCategoryTree(
                this.domElements.categoryTreeContainerEl,
                this.categoryTree,
                this.selectedCategory,
                (id) => this.handleCategorySelect(id), // Callback for selection
                this.allCategoriesFlat
            );
        }
    }

    updateUIStates() {
        const categoryIsSelected = !!this.selectedCategory;

        if (this.domElements.categoryDetailsPanelEl) {
            this.domElements.categoryDetailsPanelEl.style.display = (categoryIsSelected || this.isEditingOrCreating) ? '' : 'none';
        }

        if (this.domElements.categoryParentDisplayEl && this.domElements.categoryParentSelectEl) {
            const isEditingExisting = this.isEditingOrCreating && this.domElements.categoryFormEl && this.domElements.categoryFormEl.dataset.editingId;
            this.domElements.categoryParentDisplayEl.style.display = isEditingExisting ? 'none' : 'block';
            this.domElements.categoryParentSelectEl.style.display = isEditingExisting ? 'block' : 'none';
        }

        if (this.domElements.addRootCategoryBtnEl) this.domElements.addRootCategoryBtnEl.disabled = this.isEditingOrCreating;
        if (this.domElements.addSubCategoryBtnEl) {
            this.domElements.addSubCategoryBtnEl.style.visibility = categoryIsSelected ? 'visible' : 'hidden';
            this.domElements.addSubCategoryBtnEl.disabled = !(categoryIsSelected && !this.isEditingOrCreating);
        }
        if (this.domElements.deleteCategoryBtnEl) {
            this.domElements.deleteCategoryBtnEl.style.display = categoryIsSelected ? 'inline-block' : 'none';
            if (categoryIsSelected) this.domElements.deleteCategoryBtnEl.disabled = this.isEditingOrCreating;
        }
        if (this.domElements.editCategoryBtnEl) {
            this.domElements.editCategoryBtnEl.style.display = (categoryIsSelected && !this.isEditingOrCreating) ? 'inline-block' : 'none';
        }
        if (this.domElements.saveCategoryBtnEl) this.domElements.saveCategoryBtnEl.style.display = this.isEditingOrCreating ? 'inline-block' : 'none';
        if (this.domElements.cancelCategoryBtnEl) this.domElements.cancelCategoryBtnEl.style.display = this.isEditingOrCreating ? 'inline-block' : 'none';

        if (this.domElements.h3DetailsEl) {
            if (this.isEditingOrCreating) {
                if (this.domElements.categoryFormEl && this.domElements.categoryFormEl.dataset.editingId) {
                    this.domElements.h3DetailsEl.textContent = 'Edit Category';
                } else if (this.domElements.categoryFormEl && this.domElements.categoryFormEl.dataset.parentIdForNew === "null") {
                    this.domElements.h3DetailsEl.textContent = 'Create Root Category';
                } else if (this.domElements.categoryFormEl && this.domElements.categoryFormEl.dataset.parentIdForNew) {
                    this.domElements.h3DetailsEl.textContent = 'Create Subcategory';
                } else {
                    this.domElements.h3DetailsEl.textContent = 'New Category Details';
                }
            } else if (categoryIsSelected) {
                this.domElements.h3DetailsEl.textContent = 'Selected Category';
            } else {
                this.domElements.h3DetailsEl.textContent = 'Category Details';
            }
        }
        if (this.domElements.categoryTreeContainerEl) {
            this.domElements.categoryTreeContainerEl.style.pointerEvents = this.isEditingOrCreating ? 'none' : 'auto';
            this.domElements.categoryTreeContainerEl.style.opacity = this.isEditingOrCreating ? '0.5' : '1';
        }
    }

    updateAndRenderDetailsPanel(categoryData, makeFieldsReadOnly = true) {
        categoryDOM.updateDetailsPanelContent(
            this.domElements,
            categoryData,
            this.isEditingOrCreating,
            this.domElements.categoryFormEl ? this.domElements.categoryFormEl.dataset : {},
            (id) => this.getParentName(id),
            (editingId, parentName) => categoryDOM.populateParentCategorySelect(
                this.domElements.categoryParentSelectEl, this.allCategoriesFlat, editingId, parentName
            )
        );
        [this.domElements.categoryTitleInputEl, this.domElements.categoryCodeInputEl, this.domElements.categoryDescriptionInputEl]
            .forEach(input => { if(input) input.readOnly = makeFieldsReadOnly; });
        if(this.domElements.categoryParentDisplayEl) this.domElements.categoryParentDisplayEl.readOnly = true;
    }

    setFormEditable(isNowEditable) {
        this.isEditingOrCreating = isNowEditable;
        const makeFieldsReadOnly = !isNowEditable;
        [this.domElements.categoryTitleInputEl, this.domElements.categoryCodeInputEl, this.domElements.categoryDescriptionInputEl]
            .forEach(input => { if(input) input.readOnly = makeFieldsReadOnly; });
        this.updateUIStates();
    }

    _buildCategoryTreeFromServer(flatList, parentNameKey = null) {
        const children = [];
        if (!Array.isArray(flatList)) { return children; }
        for (const category of flatList) {
            const categoryName = category.title || category.name;
            const effectiveParentName = (category.parent === "" || category.parent === "Root" || category.parent === null) ? null : category.parent;
            if (effectiveParentName === parentNameKey) {
                const nestedChildren = this._buildCategoryTreeFromServer(flatList, categoryName);
                children.push({ ...category, children: nestedChildren, isExpanded: category.isExpanded || false }); // Preserve or default isExpanded
            }
        }
        return children.sort((a, b) => (a.title || a.name).localeCompare(b.title || b.name));
    }

    _findCategoryInTree(id, categoriesToSearch = this.categoryTree) {
        for (const category of categoriesToSearch) {
            if (String(category.id) === String(id)) { return category; }
            if (category.children && category.children.length > 0) {
                const found = this._findCategoryInTree(id, category.children);
                if (found) { return found; }
            }
        }
        return null;
    }

    getParentName(parentIdentifier) {
        if (parentIdentifier === null || parentIdentifier === "null" || parentIdentifier === undefined || parentIdentifier === "") {
            return 'Root';
        }
        // Assuming parentIdentifier is the name of the parent category.
        // If it were an ID, you'd find the category by ID in allCategoriesFlat and return its title/name.
        return parentIdentifier;
    }

    handleCategorySelect(categoryId) {
        if (this.isEditingOrCreating) { return; }
        const newlySelected = this._findCategoryInTree(categoryId);

        if (newlySelected) {
            this.selectedCategory = newlySelected;
            if (this.domElements.categoryFormEl) {
                delete this.domElements.categoryFormEl.dataset.editingId;
                delete this.domElements.categoryFormEl.dataset.parentIdForNew;
            }
            this.updateAndRenderDetailsPanel(this.selectedCategory, true);
        } else {
            console.warn(`Category with ID ${categoryId} not found in tree.`);
            this.selectedCategory = null;
            this.updateAndRenderDetailsPanel(null, true);
        }
        this._renderTree(); // Re-render tree to update selection highlight
        this.updateUIStates();
    }

    handleAddRootCategory() {
        if (this.isEditingOrCreating) { return; }
        this.selectedCategory = null;
        this._renderTree(); // Re-render tree to deselect
        if (this.domElements.categoryFormEl) {
            this.domElements.categoryFormEl.dataset.parentIdForNew = "null";
            delete this.domElements.categoryFormEl.dataset.editingId;
        }
        this.setFormEditable(true);
        this.updateAndRenderDetailsPanel({}, false); // Pass empty object for new category
        if (this.domElements.categoryParentDisplayEl) this.domElements.categoryParentDisplayEl.value = "Root";
        if (this.domElements.categoryTitleInputEl) this.domElements.categoryTitleInputEl.focus();
    }

    handleAddSubCategory() {
        if (this.isEditingOrCreating) { return; }
        if (!this.selectedCategory || !this.selectedCategory.id) {
            alert("Please select a parent category first.");
            return;
        }
        const parentName = this.selectedCategory.title || this.selectedCategory.name;
        if (this.domElements.categoryFormEl) {
            this.domElements.categoryFormEl.dataset.parentIdForNew = parentName;
            delete this.domElements.categoryFormEl.dataset.editingId;
        }
        this.setFormEditable(true);
        this.updateAndRenderDetailsPanel({}, false);
        if (this.domElements.categoryParentDisplayEl) this.domElements.categoryParentDisplayEl.value = parentName;
        if (this.domElements.categoryTitleInputEl) this.domElements.categoryTitleInputEl.focus();
    }

    handleEditCategory() {
        if (!this.selectedCategory || !this.selectedCategory.id || this.isEditingOrCreating) { return; }
        if (this.domElements.categoryFormEl) {
            this.domElements.categoryFormEl.dataset.editingId = this.selectedCategory.id;
            delete this.domElements.categoryFormEl.dataset.parentIdForNew;
        }
        this.setFormEditable(true);
        this.updateAndRenderDetailsPanel(this.selectedCategory, false);
        // populateParentCategorySelect is called within updateAndRenderDetailsPanel's callback chain
        if (this.domElements.categoryTitleInputEl) this.domElements.categoryTitleInputEl.focus();
    }

    handleCancelOperation() {
        const wasEditingId = this.domElements.categoryFormEl ? this.domElements.categoryFormEl.dataset.editingId : null;
        if (this.domElements.categoryFormEl) {
            delete this.domElements.categoryFormEl.dataset.editingId;
            delete this.domElements.categoryFormEl.dataset.parentIdForNew;
        }
        this.setFormEditable(false);

        if (wasEditingId) {
            this.selectedCategory = this._findCategoryInTree(wasEditingId); // Reselect if was editing
        }
        // If creating, selectedCategory remains what it was or null
        this.updateAndRenderDetailsPanel(this.selectedCategory, true);
        this._renderTree();
        this.updateUIStates();
    }

    async handleSaveButton() {
        const title = this.domElements.categoryTitleInputEl.value.trim();
        const code = this.domElements.categoryCodeInputEl.value.trim();
        const description = this.domElements.categoryDescriptionInputEl.value.trim();
        const editingId = this.domElements.categoryFormEl.dataset.editingId;
        let parentValueForDb = null;

        if (editingId) {
            parentValueForDb = (this.domElements.categoryParentSelectEl.value === "null" || this.domElements.categoryParentSelectEl.value === "")
                ? null : this.domElements.categoryParentSelectEl.value;
        } else if (this.domElements.categoryFormEl.dataset.parentIdForNew) {
            parentValueForDb = this.domElements.categoryFormEl.dataset.parentIdForNew === "null"
                ? null : this.domElements.categoryFormEl.dataset.parentIdForNew;
        }

        if (!title || !code) {
            alert("Title and Code are required.");
            return;
        }
        const dataToSend = { title, parent: parentValueForDb, code, description };

        try {
            let response;
            if (editingId) {
                response = await this.categoryService.updateCategory(editingId, dataToSend);
            } else {
                response = await this.categoryService.createCategory(dataToSend);
            }
            alert('Category saved successfully!');

            if (this.domElements.categoryFormEl) {
                delete this.domElements.categoryFormEl.dataset.editingId;
                delete this.domElements.categoryFormEl.dataset.parentIdForNew;
            }

            const idToSelectAfterSave = editingId || (response && response.id); // Backend create might return new ID in response.id

            await this._loadInitialData(); // Reload all categories and re-render tree

            if (idToSelectAfterSave) {
                this.selectedCategory = this._findCategoryInTree(idToSelectAfterSave);
            } else {
                this.selectedCategory = null; // Deselect if no specific ID to reselect
            }

            this.updateAndRenderDetailsPanel(this.selectedCategory, true); // Update panel based on selection
            this.setFormEditable(false); // Out of editing mode
            this._renderTree(); // Ensure tree reflects new state and selection

        } catch (error) {
            console.error('There was a problem saving the category:', error);

            let displayMessage = error.message || 'An unknown error occurred while saving the category.';
            if (error.responseBody && error.responseBody.error) {
                displayMessage = error.responseBody.error;
            } else if (error.responseBody && error.responseBody.message) {
                displayMessage = error.responseBody.message;
            }

            alert(`Error saving category: ${displayMessage}`);
        }
    }

    async handleDeleteCategory() {
        if (!this.selectedCategory || !this.selectedCategory.id) {
            alert("Please select a category to delete.");
            return;
        }
        const idToDelete = this.selectedCategory.id;
        const categoryNameToDelete = this.selectedCategory.title || this.selectedCategory.name || "this category";

        if (!confirm(`Are you sure you want to delete "${categoryNameToDelete}"? This action cannot be undone.`)) {
            return;
        }
        try {
            // categoryService.removeCategory will call the backend:
            // DELETE /api/delete/{id}
            // The backend CategoryController::deleteCategory now returns:
            // - Success: JsonResponse(['id' => $idToDelete, 'message' => 'Category deleted successfully.'], 200);
            // - Not Found: JsonResponse::createNotFound("Category with ID {$idToDelete} not found.");
            // - Server Error: JsonResponse::createInternalServerError("Failed to delete the category. Please try again later.");
            const response = await this.categoryService.removeCategory(idToDelete);

            // Use the message from the successful response if available
            alert(response.message || `Category "${categoryNameToDelete}" has been deleted successfully.`);

            this.selectedCategory = null;
            await this._loadInitialData();
            this.updateAndRenderDetailsPanel(null, true);
            this.setFormEditable(false);
        } catch (error) {
            console.error(`Error deleting category "${categoryNameToDelete}":`, error);

            let displayMessage = error.message || 'An unknown error occurred while deleting the category.';
            if (error.responseBody && error.responseBody.error) {
                displayMessage = error.responseBody.error; // Prioritize the .error field from our JsonResponse
            } else if (error.responseBody && error.responseBody.message) {
                displayMessage = error.responseBody.message;
            }

            alert(`Error deleting category "${categoryNameToDelete}": ${displayMessage}`);
        }
    }
}
import * as categoryService from './categoryService.js';
import * as categoryDOM from './categoryDOM.js';

// --- STATE VARIABLES ---
let allCategoriesFlat = [];
let categoryTree = [];
let selectedCategory = null;
let isEditingOrCreating = false;

// --- DOM Element References ---
let domElements = {};

// --- UI STATE MANAGEMENT ---
function updateUIStates() {
    const categoryIsSelected = !!selectedCategory;

    if (domElements.categoryDetailsPanelEl) {
        domElements.categoryDetailsPanelEl.style.display = (categoryIsSelected || isEditingOrCreating) ? '' : 'none';
    }

    if (domElements.categoryParentDisplayEl && domElements.categoryParentSelectEl) {
        const isEditingExisting = isEditingOrCreating &&
            domElements.categoryFormEl && domElements.categoryFormEl.dataset.editingId;
        domElements.categoryParentDisplayEl.style.display = isEditingExisting ? 'none' : 'block';
        domElements.categoryParentSelectEl.style.display = isEditingExisting ? 'block' : 'none';
    }


    if (domElements.addRootCategoryBtnEl) domElements.addRootCategoryBtnEl.disabled = isEditingOrCreating;
    if (domElements.addSubCategoryBtnEl) {
        domElements.addSubCategoryBtnEl.style.visibility = categoryIsSelected ? 'visible' : 'hidden';
        domElements.addSubCategoryBtnEl.disabled = !(categoryIsSelected && !isEditingOrCreating);
    }
    if (domElements.deleteCategoryBtnEl) {
        domElements.deleteCategoryBtnEl.style.display = categoryIsSelected ? 'inline-block' : 'none';
        if (categoryIsSelected) domElements.deleteCategoryBtnEl.disabled = isEditingOrCreating;
    }
    if (domElements.editCategoryBtnEl) {
        domElements.editCategoryBtnEl.style.display =
            (categoryIsSelected && !isEditingOrCreating) ? 'inline-block' : 'none';
    }
    if (domElements.saveCategoryBtnEl) domElements.saveCategoryBtnEl.style.display =
        isEditingOrCreating ? 'inline-block' : 'none';
    if (domElements.cancelCategoryBtnEl) domElements.cancelCategoryBtnEl.style.display =
        isEditingOrCreating ? 'inline-block' : 'none';

    if (domElements.h3DetailsEl) {
        if (isEditingOrCreating) {
            if (domElements.categoryFormEl && domElements.categoryFormEl.dataset.editingId) {
                domElements.h3DetailsEl.textContent = 'Edit Category';
            } else if (domElements.categoryFormEl && domElements.categoryFormEl.dataset.parentIdForNew === "null") {
                domElements.h3DetailsEl.textContent = 'Create Root Category';
            } else if (domElements.categoryFormEl && domElements.categoryFormEl.dataset.parentIdForNew) {
                domElements.h3DetailsEl.textContent = 'Create Subcategory';
            } else {
                domElements.h3DetailsEl.textContent = 'New Category Details';
            }
        } else if (categoryIsSelected) {
            domElements.h3DetailsEl.textContent = 'Selected Category';
        } else {
            domElements.h3DetailsEl.textContent = 'Category Details';
        }
    }
    if (domElements.categoryTreeContainerEl) {
        domElements.categoryTreeContainerEl.style.pointerEvents = isEditingOrCreating ? 'none' : 'auto';
        domElements.categoryTreeContainerEl.style.opacity = isEditingOrCreating ? '0.5' : '1';
    }
}

function updateAndRenderDetailsPanel(categoryData, makeFieldsReadOnly = true) {
    categoryDOM.updateDetailsPanelContent(
        domElements,
        categoryData,
        isEditingOrCreating,
        domElements.categoryFormEl ? domElements.categoryFormEl.dataset : {},
        getParentName,
        (editingId, parentName) => categoryDOM.populateParentCategorySelect(
            domElements.categoryParentSelectEl, allCategoriesFlat, editingId, parentName
        )
    );
    [domElements.categoryTitleInputEl, domElements.categoryCodeInputEl, domElements.categoryDescriptionInputEl]
        .forEach(input => input.readOnly = makeFieldsReadOnly);
    domElements.categoryParentDisplayEl.readOnly = true;
}


function setFormEditable(isNowEditable) {
    isEditingOrCreating = isNowEditable;
    const makeFieldsReadOnly = !isNowEditable;
    [domElements.categoryTitleInputEl, domElements.categoryCodeInputEl, domElements.categoryDescriptionInputEl]
        .forEach(input => input.readOnly = makeFieldsReadOnly);
    updateUIStates();
}

// --- DATA FETCHING & Initial Rendering ---
async function loadAndRenderCategories() {
    try {
        domElements.categoryTreeContainerEl.innerHTML = 'Loading data...';
        const rawCategories = await categoryService.fetchCategories();
        allCategoriesFlat = Array.isArray(rawCategories) ? rawCategories : [];
        categoryTree = buildCategoryTreeFromServer(allCategoriesFlat);
        categoryDOM.renderCategoryTree(
            domElements.categoryTreeContainerEl,
            categoryTree, selectedCategory, handleCategorySelect, allCategoriesFlat
        );
    } catch (error) {
        console.error("Error loading categories:", error);
        if (domElements.categoryTreeContainerEl) {
            domElements.categoryTreeContainerEl.innerHTML = '<li>Error loading categories. Please try again.</li>';
        }
    }
    updateUIStates();
}

// --- DATA STRUCTURE HELPERS ---
function buildCategoryTreeFromServer(flatList, parentNameKey = null) {
    const children = [];
    if (!Array.isArray(flatList)) return children;
    for (const category of flatList) {
        const categoryName = category.title;
        const effectiveParentName =
            (category.parent === "" || category.parent === "Root" || category.parent === null)
                ? null : category.parent;

        if (effectiveParentName === parentNameKey) {
            const nestedChildren = buildCategoryTreeFromServer(flatList, categoryName);
            children.push({ ...category, children: nestedChildren, isExpanded: false });
        }
    }
    return children.sort((a, b) => a.title.localeCompare(b.title));
}

function findCategoryInTree(id, categoriesToSearch = categoryTree) {
    for (const category of categoriesToSearch) {
        if (String(category.id) === String(id)) return category;
        if (category.children && category.children.length > 0) {
            const found = findCategoryInTree(id, category.children);
            if (found) return found;
        }
    }

    return null;
}

function getParentName(parentIdentifier) {
    if (parentIdentifier === null || parentIdentifier === "null" ||
        parentIdentifier === undefined || parentIdentifier === "") {
        return 'Root';
    }

    return parentIdentifier;
}


// --- EVENT HANDLERS ---
function handleCategorySelect(categoryId) {
    if (isEditingOrCreating) return;
    const newlySelected = findCategoryInTree(categoryId);
    if (newlySelected) {
        selectedCategory = newlySelected;
        if (domElements.categoryFormEl) {
            delete domElements.categoryFormEl.dataset.editingId;
            delete domElements.categoryFormEl.dataset.parentIdForNew;
        }
        updateAndRenderDetailsPanel(selectedCategory, true);
    } else {
        console.warn(`Category with ID ${categoryId} not found in tree.`);
        selectedCategory = null;
        updateAndRenderDetailsPanel(null, true);
    }
    categoryDOM.renderCategoryTree(
        domElements.categoryTreeContainerEl, categoryTree, selectedCategory, handleCategorySelect, allCategoriesFlat
    );
    updateUIStates();
}

function handleAddRootCategory() {
    if (isEditingOrCreating) return;
    selectedCategory = null;
    categoryDOM.renderCategoryTree(
        domElements.categoryTreeContainerEl, categoryTree, selectedCategory, handleCategorySelect, allCategoriesFlat
    );
    if (domElements.categoryFormEl) {
        domElements.categoryFormEl.dataset.parentIdForNew = "null";
        delete domElements.categoryFormEl.dataset.editingId;
    }
    setFormEditable(true);
    updateAndRenderDetailsPanel({}, false);
    domElements.categoryParentDisplayEl.value = "Root";
    if (domElements.categoryTitleInputEl) domElements.categoryTitleInputEl.focus();
}

function handleAddSubCategory() {
    if (isEditingOrCreating || !selectedCategory || !selectedCategory.id) {
        if (!selectedCategory || !selectedCategory.id) alert("Please select a parent category first.");
        return;
    }
    if (domElements.categoryFormEl) {
        domElements.categoryFormEl.dataset.parentIdForNew =
            String(selectedCategory.title);
        delete domElements.categoryFormEl.dataset.editingId;
    }
    setFormEditable(true);
    updateAndRenderDetailsPanel({}, false);
    domElements.categoryParentDisplayEl.value = selectedCategory.title || "Selected Parent";
    if (domElements.categoryTitleInputEl) domElements.categoryTitleInputEl.focus();
}

function handleEditCategory() {
    if (!selectedCategory || !selectedCategory.id || isEditingOrCreating) return;
    if (domElements.categoryFormEl) {
        domElements.categoryFormEl.dataset.editingId = selectedCategory.id;
        delete domElements.categoryFormEl.dataset.parentIdForNew;
    }
    setFormEditable(true);
    updateAndRenderDetailsPanel(selectedCategory, false);
    if (domElements.categoryTitleInputEl) domElements.categoryTitleInputEl.focus();
}

function handleCancelOperation() {
    const wasEditingId = domElements.categoryFormEl ? domElements.categoryFormEl.dataset.editingId : null;
    if (domElements.categoryFormEl) {
        delete domElements.categoryFormEl.dataset.editingId;
        delete domElements.categoryFormEl.dataset.parentIdForNew;
    }
    setFormEditable(false);
    if (wasEditingId) {
        selectedCategory = findCategoryInTree(wasEditingId);
    }
    updateAndRenderDetailsPanel(selectedCategory, true);
    categoryDOM.renderCategoryTree(
        domElements.categoryTreeContainerEl, categoryTree, selectedCategory, handleCategorySelect, allCategoriesFlat
    );
    updateUIStates();
}

async function handleSaveButton() {
    const title = domElements.categoryTitleInputEl.value.trim();
    const code = domElements.categoryCodeInputEl.value.trim();
    const description = domElements.categoryDescriptionInputEl.value.trim();
    const editingId = domElements.categoryFormEl.dataset.editingId;
    let parentValueForDb = null;

    if (editingId) {
        parentValueForDb =
            (domElements.categoryParentSelectEl.value === "null" || domElements.categoryParentSelectEl.value === "")
            ? null : domElements.categoryParentSelectEl.value;
    } else if (domElements.categoryFormEl.dataset.parentIdForNew) {
        parentValueForDb = domElements.categoryFormEl.dataset.parentIdForNew === "null"
            ? null : domElements.categoryFormEl.dataset.parentIdForNew;
    }

    if (!title || !code) {
        alert("Title and Code are required.");

        return;
    }
    const dataToSend = { title, parent: parentValueForDb, code, description };

    try {
        if (editingId) {
            await categoryService.updateCategory(editingId, dataToSend);
        } else {
            await categoryService.createCategory(dataToSend);
        }
        alert('Category saved successfully!');

        if (domElements.categoryFormEl) {
            delete domElements.categoryFormEl.dataset.editingId;
            delete domElements.categoryFormEl.dataset.parentIdForNew;
        }
        await loadAndRenderCategories();
        selectedCategory = null;
        updateAndRenderDetailsPanel(selectedCategory, true);
        setFormEditable(false);

    } catch (error) {
        console.error('There was a problem saving the category:', error);
        alert(`Error saving category: ${error.message || 'Unknown error.'}`);
    }
}

async function handleDeleteCategory() {
    if (!selectedCategory || !selectedCategory.id) {
        alert("Please select a category to delete.");

        return;
    }
    const idToDelete = selectedCategory.id;
    const categoryNameToDelete = selectedCategory.title || "this category";
    if (!confirm(`Are you sure you want to delete "${categoryNameToDelete}"? This action cannot be undone.`)) {
        return;
    }
    try {
        await categoryService.removeCategory(idToDelete);
        alert(`Category "${categoryNameToDelete}" has been deleted successfully.`);
        selectedCategory = null;
        await loadAndRenderCategories();
        updateAndRenderDetailsPanel(null, true);
        setFormEditable(false);
    } catch (error) {
        console.error(`Error deleting category "${categoryNameToDelete}":`, error);
        alert(`Error deleting category "${categoryNameToDelete}": ${error.message || 'Unknown error.'}`);
    }
}

// --- Main function to create and show the Product Categories page structure ---
export function showProductCategories() {
    const wrapper = document.createElement('div');
    wrapper.classList.add('categories-page-wrapper');

    // Initialize page structure and get DOM element references
    domElements = categoryDOM.initializePageStructure(wrapper);

    // Attach event listeners
    domElements.addRootCategoryBtnEl.addEventListener('click', handleAddRootCategory);
    domElements.addSubCategoryBtnEl.addEventListener('click', handleAddSubCategory);
    domElements.editCategoryBtnEl.addEventListener('click', handleEditCategory);
    domElements.saveCategoryBtnEl.addEventListener('click', handleSaveButton);
    domElements.cancelCategoryBtnEl.addEventListener('click', handleCancelOperation);
    domElements.deleteCategoryBtnEl.addEventListener('click', handleDeleteCategory);

    // Initial Page Setup
    loadAndRenderCategories().catch(() => console.error('Error rendering categories'));
    selectedCategory = null;
    isEditingOrCreating = false;
    updateAndRenderDetailsPanel(null, true);
    updateUIStates();

    return wrapper;
}
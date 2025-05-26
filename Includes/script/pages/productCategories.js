import {deleteData, getData, postData, putData} from "../ajax.js";

// --- STATE VARIABLES ---
let allCategoriesFlat = [];
let categoryTree = [];
let selectedCategory = null;
let isEditingOrCreating = false;

// --- DOM Element References ---
let categoryTreeContainerEl, categoryFormEl, categoryTitleInputEl, categoryParentDisplayEl,
    categoryCodeInputEl, categoryDescriptionInputEl, addRootCategoryBtnEl, addSubCategoryBtnEl,
    deleteCategoryBtnEl, editCategoryBtnEl, saveCategoryBtnEl, cancelCategoryBtnEl,
    categoryTreePanelEl, categoryDetailsPanelEl, categoryParentSelectEl, h3DetailsEl;

// --- Helper function to create a form group (label + input) ---
function createFormGroup(labelText, inputId, inputName, inputType = 'text',
                         isTextarea = false, readOnly = true) {
    const div = document.createElement('div');
    div.className = 'form-group';
    const label = document.createElement('label');
    label.setAttribute('for', inputId);
    label.textContent = labelText;
    let inputElement =
        isTextarea ? document.createElement('textarea') : document.createElement('input');
    if (!isTextarea) inputElement.type = inputType;
    inputElement.id = inputId;
    inputElement.name = inputName;
    if (readOnly) inputElement.readOnly = true;
    div.appendChild(label);
    div.appendChild(inputElement);
    return { groupDiv: div, inputEl: inputElement };
}

// --- Helper function to create a button ---
function createButton(id, text, type = 'button', initiallyHidden = false) {
    const button = document.createElement('button');
    button.type = type;
    button.id = id;
    button.textContent = text;
    if (initiallyHidden) button.style.display = 'none';
    return button;
}

// --- UI STATE MANAGEMENT ---
function updateUIStates() {
    const categoryIsSelected = !!selectedCategory;

    if (categoryDetailsPanelEl) {
        categoryDetailsPanelEl.style.display = (categoryIsSelected || isEditingOrCreating) ? '' : 'none';
    }

    if (categoryParentDisplayEl && categoryParentSelectEl) {
        if (isEditingOrCreating && categoryFormEl && categoryFormEl.dataset.editingId) {
            categoryParentDisplayEl.style.display = 'none';
            categoryParentSelectEl.style.display = 'block';
        } else {
            categoryParentDisplayEl.style.display = 'block';
            categoryParentSelectEl.style.display = 'none';
        }
    }

    if (addRootCategoryBtnEl) addRootCategoryBtnEl.disabled = isEditingOrCreating;

    if (addSubCategoryBtnEl) {
        const showAddSub = categoryIsSelected && !isEditingOrCreating;
        addSubCategoryBtnEl.style.visibility = categoryIsSelected ? 'visible' : 'hidden';
        addSubCategoryBtnEl.disabled = !showAddSub;
    }

    if (deleteCategoryBtnEl) {
        const showDelete = categoryIsSelected;
        deleteCategoryBtnEl.style.display = showDelete ? 'inline-block' : 'none';
        if (showDelete) {
            deleteCategoryBtnEl.disabled = isEditingOrCreating;
        }
    }

    if (editCategoryBtnEl) {
        editCategoryBtnEl.style.display = (categoryIsSelected && !isEditingOrCreating) ? 'inline-block' : 'none';
    }

    if (saveCategoryBtnEl) saveCategoryBtnEl.style.display = isEditingOrCreating ? 'inline-block' : 'none';
    if (cancelCategoryBtnEl) cancelCategoryBtnEl.style.display = isEditingOrCreating ? 'inline-block' : 'none';

    if (h3DetailsEl) {
        if (isEditingOrCreating) {
            if (categoryFormEl && categoryFormEl.dataset.editingId) {
                h3DetailsEl.textContent = 'Edit Category';
            } else if (categoryFormEl && categoryFormEl.dataset.parentIdForNew === "null") {
                h3DetailsEl.textContent = 'Create Root Category';
            } else if (categoryFormEl && categoryFormEl.dataset.parentIdForNew) {
                h3DetailsEl.textContent = 'Create Subcategory';
            } else {
                h3DetailsEl.textContent = 'New Category Details';
            }
        } else if (categoryIsSelected) {
            h3DetailsEl.textContent = 'Selected Category';
        } else {
            h3DetailsEl.textContent = 'Category Details';
        }
    }

    if (categoryTreeContainerEl) {
        categoryTreeContainerEl.style.pointerEvents = isEditingOrCreating ? 'none' : 'auto';
        categoryTreeContainerEl.style.opacity = isEditingOrCreating ? '0.5' : '1';
    }
}

function updateDetailsPanel(categoryData, makeFieldsReadOnly = true) {
    if (!categoryFormEl) return;

    if (categoryData) {
        categoryTitleInputEl.value = categoryData.title || '';
        categoryCodeInputEl.value = categoryData.code || '';
        categoryDescriptionInputEl.value = categoryData.description || '';

        if (isEditingOrCreating && categoryFormEl.dataset.editingId) {
            populateParentCategorySelect(categoryData.id, categoryData.parent);
        } else {
            let parentDisplayText = 'Root';
            if (
                isEditingOrCreating &&
                categoryFormEl.dataset.parentIdForNew && categoryFormEl.dataset.parentIdForNew !== "null"
            ) {
                parentDisplayText = getParentName(categoryFormEl.dataset.parentIdForNew);
            } else if (isEditingOrCreating && categoryFormEl.dataset.parentIdForNew === "null") {
                parentDisplayText = getParentName(categoryFormEl.dataset.parentIdForNew);
            } else if (!isEditingOrCreating && categoryData.parent) {
                parentDisplayText = getParentName(categoryData.parent);
            } else if (!isEditingOrCreating && !categoryData.parent) {
                parentDisplayText = 'Root';
            }
            categoryParentDisplayEl.value = parentDisplayText;
        }
    } else {
        categoryFormEl.reset();
        categoryParentDisplayEl.value = (
            isEditingOrCreating && categoryFormEl.dataset.parentIdForNew === "null") ? 'Root' : '';

        if (categoryParentSelectEl) {
            categoryParentSelectEl.innerHTML = '';
            const noParentOption = document.createElement('option');
            noParentOption.value = "null";
            noParentOption.textContent = "Root";
            categoryParentSelectEl.appendChild(noParentOption);
            categoryParentSelectEl.value = "null";
        }
    }

    [categoryTitleInputEl, categoryCodeInputEl, categoryDescriptionInputEl]
        .forEach(input => input.readOnly = makeFieldsReadOnly);
    categoryParentDisplayEl.readOnly = true;
}

function populateParentCategorySelect(editingCategoryId, currentParentName) {
    if (!categoryParentSelectEl) return;
    categoryParentSelectEl.innerHTML = '';

    const noParentOption = document.createElement('option');
    noParentOption.value = "null";
    noParentOption.textContent = "Root";
    categoryParentSelectEl.appendChild(noParentOption);

    allCategoriesFlat.forEach(cat => {
        if (String(cat.id) !== String(editingCategoryId)) {
            const option = document.createElement('option');
            const catName = cat.title || cat.name;
            option.value = catName;
            option.textContent = catName;
            if (catName === currentParentName) {
                option.selected = true;
            }
            categoryParentSelectEl.appendChild(option);
        }
    });

    if (currentParentName === null ||
        currentParentName === undefined ||
        currentParentName === "" ||
        (typeof currentParentName === 'string' && currentParentName.toLowerCase() === "root")) {
        categoryParentSelectEl.value = "null";
    } else {
        categoryParentSelectEl.value = currentParentName;
    }
}

function setFormEditable(isNowEditable) {
    isEditingOrCreating = isNowEditable;
    const makeFieldsReadOnly = !isNowEditable;
    [categoryTitleInputEl, categoryCodeInputEl, categoryDescriptionInputEl]
        .forEach(input => input.readOnly = makeFieldsReadOnly);
    updateUIStates();
}

// --- DATA FETCHING & RENDERING ---
async function loadCategories() {
    try {
        const rawCategories = await getData('api/categories')

        allCategoriesFlat = Array.isArray(rawCategories) ? rawCategories : [];
        categoryTree = buildCategoryTreeFromServer(allCategoriesFlat);
        renderCategoryTree();
    } catch (error) {
        console.error("Error loading categories:", error);
        if (categoryTreeContainerEl) categoryTreeContainerEl.innerHTML = '' +
            '<li>Error loading categories. Please try again.</li>';
    }
    updateUIStates();
}

function buildCategoryTreeFromServer(flatList, parentNameKey = null) {
    const children = [];
    if (!Array.isArray(flatList)) return children;

    for (const category of flatList) {
        const categoryName = category.title || category.name;
        const effectiveParentName = (category.parent === "" ||
            category.parent === "Root" ||
            category.parent === null) ? null : category.parent;
        if (effectiveParentName === parentNameKey) {
            const nestedChildren = buildCategoryTreeFromServer(flatList, categoryName);
            children.push({ ...category, children: nestedChildren, isExpanded: false });
        }
    }
    return children.sort((a, b) => (a.title || a.name).localeCompare(b.title || b.name));
}

function renderCategoryTree() {
    if (!categoryTreeContainerEl) return;
    categoryTreeContainerEl.innerHTML = '';

    const createTreeItemRecursive = (category, level) => {
        const li = document.createElement('li');
        li.style.marginLeft = `${level * 10}px`;
        li.dataset.categoryId = category.id;
        li.classList.add('category-item');

        const contentWrapper = document.createElement('div');
        contentWrapper.classList.add('category-item-content');
        contentWrapper.addEventListener('click', () => { handleCategorySelect(category.id); });
        contentWrapper.style.cursor = 'pointer';

        let subUl = null;
        if (category.children && category.children.length > 0) {
            const toggler = document.createElement('span');
            toggler.textContent = category.isExpanded ? '[-] ' : '[+] ';
            toggler.classList.add('tree-toggler');
            toggler.addEventListener('click', (e) => {
                e.stopPropagation();
                category.isExpanded = !category.isExpanded;
                toggler.textContent = category.isExpanded ? '[-] ' : '[+] ';
                if (subUl) subUl.style.display = category.isExpanded ? 'block' : 'none';
            });
            contentWrapper.appendChild(toggler);
        } else {
            const placeholder = document.createElement('span');
            placeholder.classList.add('tree-toggler-placeholder');
            contentWrapper.appendChild(placeholder);
        }

        const nameSpan = document.createElement('span');
        nameSpan.textContent = category.title || category.name || 'Unnamed Category';
        nameSpan.classList.add('category-name');
        contentWrapper.appendChild(nameSpan);
        li.appendChild(contentWrapper);

        if (selectedCategory && category.id === selectedCategory.id) {
            li.classList.add('selected');
        }

        if (category.children && category.children.length > 0) {
            subUl = document.createElement('ul');
            subUl.classList.add('nested-category-list');
            subUl.style.display = category.isExpanded ? 'block' : 'none';
            category.children.forEach(child => subUl.appendChild(createTreeItemRecursive(child, level + 1)));
            li.appendChild(subUl);
        }
        return li;
    };

    if (categoryTree && categoryTree.length > 0) {
        categoryTree.forEach(rootCategory => categoryTreeContainerEl
            .appendChild(createTreeItemRecursive(rootCategory, 0)));
    } else if (allCategoriesFlat.length > 0 && categoryTree.length === 0) {
        categoryTreeContainerEl.innerHTML = '<li>No root categories found. Check parent references in data.</li>';
    } else {
        categoryTreeContainerEl.innerHTML = '<li>No categories available.</li>';
    }
}

// --- CATEGORY SELECTION & HELPERS ---
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
    if (parentIdentifier === null ||
        parentIdentifier === "null" ||
        parentIdentifier === undefined ||
        parentIdentifier === "") {
        return 'Root';
    }
    return parentIdentifier;
}

function handleCategorySelect(categoryId) {
    if (isEditingOrCreating) return;

    const newlySelected = findCategoryInTree(categoryId);
    if (newlySelected) {
        selectedCategory = newlySelected;
        if (categoryFormEl) {
            delete categoryFormEl.dataset.editingId;
            delete categoryFormEl.dataset.parentIdForNew;
        }
        updateDetailsPanel(selectedCategory, true);
    } else {
        console.warn(`Category with ID ${categoryId} not found in tree.`);
        selectedCategory = null;
        updateDetailsPanel(null, true);
    }
    renderCategoryTree();
    updateUIStates();
}

// --- EVENT HANDLERS ---
function handleAddRootCategory() {
    if (isEditingOrCreating) return;

    selectedCategory = null;
    renderCategoryTree();

    if (categoryFormEl) {
        categoryFormEl.dataset.parentIdForNew = "null";
        delete categoryFormEl.dataset.editingId;
    }

    setFormEditable(true);
    updateDetailsPanel({}, false);

    if (categoryTitleInputEl) categoryTitleInputEl.focus();
}

function handleAddSubCategory() {
    if (isEditingOrCreating) return;
    if (!selectedCategory || !selectedCategory.id) {
        alert("Please select a parent category first.");
        return;
    }

    if (categoryFormEl) {
        categoryFormEl.dataset.parentIdForNew = String(selectedCategory.id);
        delete categoryFormEl.dataset.editingId;
    }

    setFormEditable(true);
    updateDetailsPanel({}, false);

    if (categoryParentDisplayEl) {
        categoryParentDisplayEl.value = selectedCategory.title || selectedCategory.name || "Selected Parent";
    }

    if (categoryTitleInputEl) categoryTitleInputEl.focus();
}

function handleEditCategory() {
    if (!selectedCategory || !selectedCategory.id || isEditingOrCreating) return;

    if (categoryFormEl) {
        categoryFormEl.dataset.editingId = selectedCategory.id;
        delete categoryFormEl.dataset.parentIdForNew;
    }
    setFormEditable(true);
    updateDetailsPanel(selectedCategory, false);

    if (categoryTitleInputEl) categoryTitleInputEl.focus();
}

function handleCancelOperation() {
    const wasEditingId = categoryFormEl ? categoryFormEl.dataset.editingId : null;

    if (categoryFormEl) {
        delete categoryFormEl.dataset.editingId;
        delete categoryFormEl.dataset.parentIdForNew;
    }
    setFormEditable(false);

    if (wasEditingId) {
        selectedCategory = selectedCategory && String(selectedCategory.id) === String(wasEditingId) ?
            selectedCategory : findCategoryInTree(wasEditingId);
    }

    updateDetailsPanel(selectedCategory, true);
    renderCategoryTree();
    updateUIStates();
}

function handleSaveButton() {
    const categoryTitle = categoryTitleInputEl.value.trim();
    const code = categoryCodeInputEl.value.trim();
    const description = categoryDescriptionInputEl.value.trim();
    let parentValueForDb = null;
    const editingId = categoryFormEl.dataset.editingId;

    if (editingId) {
        parentValueForDb = (categoryParentSelectEl.value === "null" || categoryParentSelectEl.value === "")
            ? null : categoryParentSelectEl.value;
    } else if (categoryFormEl.dataset.parentIdForNew) {
        parentValueForDb = categoryFormEl.dataset.parentIdForNew === "null" ? null : categoryParentDisplayEl.value;
    }

    if (!categoryTitle || !code) {
        alert("Title and Code are required.");
        return;
    }
    const dataToSend = { title: categoryTitle, parent: parentValueForDb, code: code, description: description };

    const afterSaveSuccess = (response) => {
        alert('Category saved successfully!');
        const idBeingOperatedOn = editingId || (response && response.id);

        if (categoryFormEl) {
            delete categoryFormEl.dataset.editingId;
            delete categoryFormEl.dataset.parentIdForNew;
        }

        loadCategories().then(() => {
            selectedCategory = idBeingOperatedOn ? findCategoryInTree(idBeingOperatedOn) : null;
            updateDetailsPanel(selectedCategory, true);
            setFormEditable(false);
        }).catch(err => {
            console.error("Failed to reload categories after save:", err);
            selectedCategory = null;
            updateDetailsPanel(null, true);
            setFormEditable(false);
        });
    };

    const afterSaveError = (error) => {
        console.error('There was a problem saving the category:', error);
        alert(`Error saving category: ${error.message || 'Unknown error.'}`);
    };

    if (editingId) {
        putData(`api/update/${editingId}`, dataToSend).then(afterSaveSuccess).catch(afterSaveError);
    } else {
        postData('api/createCategory', dataToSend).then(afterSaveSuccess).catch(afterSaveError);
    }
}

function handleDeleteCategory() {
    if (!selectedCategory || !selectedCategory.id) {
        alert("Please select a category to delete.");
        return;
    }

    const idToDelete = selectedCategory.id;
    const categoryNameToDelete = selectedCategory.title || selectedCategory.name || "this category";

    if (!confirm(
        `Are you sure you want to delete the category "${categoryNameToDelete}"? This action cannot be undone.`
    )) {
        return;
    }

    const afterDeleteSuccess = (response) => {
        alert(`Category "${categoryNameToDelete}" has been deleted successfully.`);
        loadCategories().then(() => {
            selectedCategory = null;
            setFormEditable(false);
            updateDetailsPanel(null, true);
        });
    };

    const afterDeleteError = (error) => {
        console.error(`Error deleting category "${categoryNameToDelete}":`, error);
        alert(`Error deleting category "${categoryNameToDelete}": ${error.message || 'Unknown error.'}`);
    };

    deleteData(`api/delete/${idToDelete}`)
        .then(afterDeleteSuccess)
        .catch(afterDeleteError);
}

// --- Main function to create and show the Product Categories page structure ---
export function showProductCategories() {
    const wrapper = document.createElement('div');
    wrapper.classList.add('categories-page-wrapper');

    const h2 = document.createElement('h2');
    h2.textContent = 'Product Categories';
    wrapper.appendChild(h2);

    const categoriesContainer = document.createElement('div');
    categoriesContainer.className = 'categories-container';
    wrapper.appendChild(categoriesContainer);

    // Left Panel: Category Tree
    categoryTreePanelEl = document.createElement('div');
    categoryTreePanelEl.className = 'content-panel category-tree-panel';
    categoriesContainer.appendChild(categoryTreePanelEl);

    categoryTreeContainerEl = document.createElement('ul');
    categoryTreeContainerEl.id = 'pc_categoryTreeRoot';
    categoryTreeContainerEl.className = 'category-tree';
    categoryTreeContainerEl.innerHTML = 'Loading data...';
    categoryTreePanelEl.appendChild(categoryTreeContainerEl);

    const treeActionsDiv = document.createElement('div');
    treeActionsDiv.className = 'tree-actions';
    addRootCategoryBtnEl = createButton('pc_addRootCategoryBtn', 'Add root category');
    addRootCategoryBtnEl.addEventListener('click', handleAddRootCategory);

    addSubCategoryBtnEl = createButton('pc_addSubCategoryBtn', 'Add subcategory');
    addSubCategoryBtnEl.addEventListener('click', handleAddSubCategory);

    treeActionsDiv.appendChild(addRootCategoryBtnEl);
    treeActionsDiv.appendChild(addSubCategoryBtnEl);
    categoryTreePanelEl.appendChild(treeActionsDiv);

    // Right Panel: Category Details
    categoryDetailsPanelEl = document.createElement('div');
    categoryDetailsPanelEl.className = 'content-panel category-details-panel';
    categoriesContainer.appendChild(categoryDetailsPanelEl);

    h3DetailsEl = document.createElement('h3');
    categoryDetailsPanelEl.appendChild(h3DetailsEl);

    categoryFormEl = document.createElement('form');
    categoryFormEl.id = 'pc_categoryForm';
    categoryFormEl.addEventListener('submit', (e) => e.preventDefault());
    categoryDetailsPanelEl.appendChild(categoryFormEl);

    // Form Groups
    const titleGroup = createFormGroup(
        'Title:', 'pc_categoryTitle', 'title'
    );
    categoryTitleInputEl = titleGroup.inputEl;
    categoryFormEl.appendChild(titleGroup.groupDiv);

    const parentFormGroupDiv = document.createElement('div');
    parentFormGroupDiv.className = 'form-group';
    const parentLabel = document.createElement('label');
    parentLabel.setAttribute('for', 'pc_categoryParentDisplay');
    parentLabel.textContent = 'Parent category:';
    parentFormGroupDiv.appendChild(parentLabel);

    categoryParentDisplayEl = document.createElement('input');
    categoryParentDisplayEl.type = 'text';
    categoryParentDisplayEl.id = 'pc_categoryParentDisplay';
    categoryParentDisplayEl.name = 'parentDisplay';
    categoryParentDisplayEl.readOnly = true;
    parentFormGroupDiv.appendChild(categoryParentDisplayEl);

    categoryParentSelectEl = document.createElement('select');
    categoryParentSelectEl.id = 'pc_categoryParentSelect';
    categoryParentSelectEl.name = 'parentSelect';
    categoryParentSelectEl.style.display = 'none';
    const noParentOption = document.createElement('option');
    noParentOption.value = "null";
    noParentOption.textContent = "Root";
    categoryParentSelectEl.appendChild(noParentOption);
    parentFormGroupDiv.appendChild(categoryParentSelectEl);
    categoryFormEl.appendChild(parentFormGroupDiv);

    const codeGroup = createFormGroup(
        'Code:', 'pc_categoryCode', 'code'
    );
    categoryCodeInputEl = codeGroup.inputEl;
    categoryFormEl.appendChild(codeGroup.groupDiv);

    const descriptionGroup = createFormGroup(
        'Description:', 'pc_categoryDescription', 'description', 'text', true
    );
    categoryDescriptionInputEl = descriptionGroup.inputEl;
    categoryFormEl.appendChild(descriptionGroup.groupDiv);

    // Action Buttons for the Details Form
    const detailsActionsDiv = document.createElement('div');
    detailsActionsDiv.className = 'details-actions';
    categoryFormEl.appendChild(detailsActionsDiv);

    deleteCategoryBtnEl = createButton('pc_deleteCategoryBtn', 'Delete');
    deleteCategoryBtnEl.addEventListener('click', handleDeleteCategory);

    const actionsRightGroup = document.createElement('div');
    actionsRightGroup.className = 'actions-right';
    actionsRightGroup.style.display = 'flex';
    actionsRightGroup.style.marginLeft = 'auto';

    editCategoryBtnEl = createButton('pc_editCategoryBtn', 'Edit');
    editCategoryBtnEl.addEventListener('click', handleEditCategory);

    cancelCategoryBtnEl = createButton('pc_cancelCategoryBtn', 'Cancel', 'button', true);
    cancelCategoryBtnEl.addEventListener('click', handleCancelOperation);

    saveCategoryBtnEl = createButton('pc_saveCategoryBtn', 'Save', 'button', true);
    saveCategoryBtnEl.addEventListener('click', handleSaveButton);

    actionsRightGroup.appendChild(cancelCategoryBtnEl);
    actionsRightGroup.appendChild(saveCategoryBtnEl);
    actionsRightGroup.appendChild(editCategoryBtnEl);

    detailsActionsDiv.appendChild(deleteCategoryBtnEl);
    detailsActionsDiv.appendChild(actionsRightGroup);

    // Initial Page Setup
    loadCategories().catch(err => {
        console.error('Error during initial category load:', err);
        if(categoryTreeContainerEl) categoryTreeContainerEl.innerHTML = "" +
            "<li>Failed to load categories. Check API and network.</li>";
    });
    selectedCategory = null;
    isEditingOrCreating = false;
    updateDetailsPanel(null, true);
    updateUIStates();

    return wrapper;
}
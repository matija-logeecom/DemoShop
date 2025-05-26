import {deleteData, getData, postData, putData} from "../ajax.js"; // Make sure ajax.js path is correct

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
function createFormGroup(labelText, inputId, inputName, inputType = 'text', isTextarea = false, readOnly = true) {
    const div = document.createElement('div');
    div.className = 'form-group';
    const label = document.createElement('label');
    label.setAttribute('for', inputId);
    label.textContent = labelText;
    let inputElement = isTextarea ? document.createElement('textarea') : document.createElement('input');
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

    // Right Panel (Details Panel) Visibility
    if (categoryDetailsPanelEl) {
        categoryDetailsPanelEl.style.display = (categoryIsSelected || isEditingOrCreating) ? '' : 'none';
    }

    // Parent Field Display Logic (Text input vs Select dropdown)
    if (categoryParentDisplayEl && categoryParentSelectEl) {
        if (isEditingOrCreating && categoryFormEl && categoryFormEl.dataset.editingId) {
            categoryParentDisplayEl.style.display = 'none';
            categoryParentSelectEl.style.display = 'block';
        } else {
            categoryParentDisplayEl.style.display = 'block';
            categoryParentSelectEl.style.display = 'none';
        }
    }

    // Button States
    if (addRootCategoryBtnEl) addRootCategoryBtnEl.disabled = isEditingOrCreating;

    if (addSubCategoryBtnEl) {
        const showAddSub = categoryIsSelected && !isEditingOrCreating;
        addSubCategoryBtnEl.style.visibility = showAddSub ? 'visible' : 'hidden';
        addSubCategoryBtnEl.disabled = !showAddSub;
    }

    if (deleteCategoryBtnEl) {
        const showDelete = categoryIsSelected; // Show if a category is selected
        deleteCategoryBtnEl.style.display = showDelete ? 'inline-block' : 'none';
        if (showDelete) {
            deleteCategoryBtnEl.disabled = isEditingOrCreating; // Disable if editing/creating
        }
    }

    if (editCategoryBtnEl) {
        editCategoryBtnEl.style.display = (categoryIsSelected && !isEditingOrCreating) ? 'inline-block' : 'none';
    }

    if (saveCategoryBtnEl) saveCategoryBtnEl.style.display = isEditingOrCreating ? 'inline-block' : 'none';
    if (cancelCategoryBtnEl) cancelCategoryBtnEl.style.display = isEditingOrCreating ? 'inline-block' : 'none';

    // Heading Text
    if (h3DetailsEl) {
        if (isEditingOrCreating) {
            if (categoryFormEl && categoryFormEl.dataset.editingId) h3DetailsEl.textContent = 'Edit Category';
            else if (categoryFormEl && categoryFormEl.dataset.parentIdForNew === "null") h3DetailsEl.textContent = 'Create Root Category';
            else if (categoryFormEl && categoryFormEl.dataset.parentIdForNew) h3DetailsEl.textContent = 'Create Subcategory';
            else h3DetailsEl.textContent = 'New Category Details';
        } else if (categoryIsSelected) h3DetailsEl.textContent = 'Selected category';
        else h3DetailsEl.textContent = 'Category Details';
    }

    // Tree interactivity
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
            if (isEditingOrCreating && categoryFormEl.dataset.parentIdForNew && categoryFormEl.dataset.parentIdForNew !== "null") {
                parentDisplayText = getParentName(categoryFormEl.dataset.parentIdForNew);
            } else if (!isEditingOrCreating && categoryData.parent) {
                parentDisplayText = getParentName(categoryData.parent);
            }
            categoryParentDisplayEl.value = parentDisplayText;
        }
    } else {
        categoryFormEl.reset();
        categoryParentDisplayEl.value = (isEditingOrCreating && categoryFormEl.dataset.parentIdForNew === "null") ? 'Root' : '';
        if (categoryParentSelectEl) {
            const firstOption = categoryParentSelectEl.options[0];
            categoryParentSelectEl.innerHTML = '';
            if(firstOption && (firstOption.value === "null" || firstOption.value === "")) categoryParentSelectEl.appendChild(firstOption);
            categoryParentSelectEl.value = "null";
        }
    }
    [categoryTitleInputEl, categoryCodeInputEl, categoryDescriptionInputEl].forEach(input => input.readOnly = makeFieldsReadOnly);
    categoryParentDisplayEl.readOnly = true;
}

function populateParentCategorySelect(editingCategoryId, currentParentName) {
    if (!categoryParentSelectEl) return;
    categoryParentSelectEl.innerHTML = ''; // Clear existing options

    // Add the "Root" option
    const noParentOption = document.createElement('option');
    noParentOption.value = "null"; // The value is the string "null"
    noParentOption.textContent = "Root";
    categoryParentSelectEl.appendChild(noParentOption);

    // Add other categories as options
    allCategoriesFlat.forEach(cat => {
        if (String(cat.id) !== String(editingCategoryId)) {
            const option = document.createElement('option');
            const catName = cat.title; // Assuming cat.title is the correct display name
            option.value = catName;    // Value for actual categories is their name/title
            option.textContent = catName;

            if (catName === currentParentName) {
                option.selected = true; // This attempts to select an actual category by name
            }
            categoryParentSelectEl.appendChild(option);
        }
    });

    // Refined logic for setting the selected value:
    // Check if currentParentName signifies a root category in any of the expected ways.
    if (currentParentName === null ||
        currentParentName === undefined ||
        currentParentName === "" ||
        (typeof currentParentName === 'string' && currentParentName.toLowerCase() === "root")) {
        // If it's a root category, set the select element's value to "null" (the string)
        // to match the <option value="null">Root</option>.
        categoryParentSelectEl.value = "null";
    } else {
        // Otherwise, currentParentName is an actual category name;
        // set the select value to this name to select the corresponding category.
        categoryParentSelectEl.value = currentParentName;
    }
}

function setFormEditable(isNowEditable) {
    isEditingOrCreating = isNowEditable;
    const makeFieldsReadOnly = !isNowEditable;
    [categoryTitleInputEl, categoryCodeInputEl, categoryDescriptionInputEl].forEach(input => input.readOnly = makeFieldsReadOnly);
    updateUIStates();
}

// --- DATA FETCHING & RENDERING ---
async function loadCategories() {
    try {
        const rawCategories = await getData('api/categories');
        allCategoriesFlat = Array.isArray(rawCategories) ? rawCategories : [];
        categoryTree = buildCategoryTreeFromServer(allCategoriesFlat);
        renderCategoryTree();
    } catch (error) {
        console.error("Error loading categories:", error);
        if (categoryTreeContainerEl) categoryTreeContainerEl.innerHTML = '<li>Error loading categories.</li>';
    }
    updateUIStates();
}

function buildCategoryTreeFromServer(flatList, parentNameKey = null) {
    const children = [];
    if (!Array.isArray(flatList)) return children;
    for (const category of flatList) {
        const categoryName = category.title || category.name;
        const effectiveParentName = (category.parent === "" || category.parent === "Root" || category.parent === null) ? null : category.parent;
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
                e.stopPropagation(); category.isExpanded = !category.isExpanded;
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
        if (selectedCategory && category.id === selectedCategory.id) li.classList.add('selected');
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
        categoryTree.forEach(rootCategory => categoryTreeContainerEl.appendChild(createTreeItemRecursive(rootCategory, 0)));
    } else if (allCategoriesFlat.length > 0 && categoryTree.length === 0) {
        categoryTreeContainerEl.innerHTML = '<li>No root categories found.</li>';
    } else {
        categoryTreeContainerEl.innerHTML = '<li>No categories available.</li>';
    }
    console.log("renderCategoryTree executed with tree:", categoryTree);
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
    if (parentIdentifier === null || parentIdentifier === "null" || parentIdentifier === undefined || parentIdentifier === "") {
        return 'Root';
    }
    return parentIdentifier;
}

function handleCategorySelect(categoryId) {
    if (isEditingOrCreating) return;
    const newlySelected = findCategoryInTree(categoryId);
    if (newlySelected) {
        selectedCategory = newlySelected;
        if(categoryFormEl) {
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
    if (isEditingOrCreating) return; // Guard against re-entry if button not disabled fast enough

    selectedCategory = null;
    renderCategoryTree(); // Visually deselect in tree

    if (categoryFormEl) {
        categoryFormEl.dataset.parentIdForNew = "null";
        delete categoryFormEl.dataset.editingId;
    }

    updateDetailsPanel({ parentName: 'Root' }, false); // Prepare form content for new root
    setFormEditable(true); // Set mode, make fields writable, this calls updateUIStates

    if(categoryTitleInputEl) categoryTitleInputEl.focus();
}

function handleEditCategory() {
    if (!selectedCategory || isEditingOrCreating) return;

    if (categoryFormEl) {
        categoryFormEl.dataset.editingId = selectedCategory.id;
        delete categoryFormEl.dataset.parentIdForNew;
    }

    setFormEditable(true); // Set mode, make fields writable, this calls updateUIStates
    updateDetailsPanel(selectedCategory, false); // Populate form, including parent select

    if(categoryTitleInputEl) categoryTitleInputEl.focus();
}

function handleCancelOperation() {
    const wasEditingId = categoryFormEl ? categoryFormEl.dataset.editingId : null;

    if (categoryFormEl) {
        delete categoryFormEl.dataset.editingId;
        delete categoryFormEl.dataset.parentIdForNew;
    }

    setFormEditable(false); // Set mode to NOT editing, makes fields read-only, calls updateUIStates

    if (wasEditingId) {
        // If we were editing, selectedCategory should still hold the category.
        // If not, try to find it. This ensures the panel reverts to showing its details.
        selectedCategory = selectedCategory && String(selectedCategory.id) === String(wasEditingId) ?
            selectedCategory : findCategoryInTree(wasEditingId);
    } else {
        // If we were creating, selectedCategory should be null
        selectedCategory = null;
    }
    updateDetailsPanel(selectedCategory, true); // Restore/clear details panel
    renderCategoryTree(); // Refresh tree selection highlight
    updateUIStates(); // Ensure final UI state is correct
}

function handleSaveButton() {
    const categoryTitle = categoryTitleInputEl.value.trim();
    const code = categoryCodeInputEl.value.trim();
    const description = categoryDescriptionInputEl.value.trim();
    let parentValueForDb = null;
    const editingId = categoryFormEl.dataset.editingId;

    if (editingId) {
        parentValueForDb = (categoryParentSelectEl.value === "null" || categoryParentSelectEl.value === "") ? null : categoryParentSelectEl.value;
    } else if (categoryFormEl.dataset.parentIdForNew) {
        parentValueForDb = categoryFormEl.dataset.parentIdForNew === "null" ? null : categoryFormEl.dataset.parentIdForNew;
    }

    if (!categoryTitle || !code) {
        alert("Title and Code are required.");
        return;
    }
    const dataToSend = { title: categoryTitle, parent: parentValueForDb, code: code, description: description };
    console.log("Sending data to backend for save:", dataToSend, "Editing ID:", editingId);

    const afterSaveSuccess = (response) => {
        console.log(`Category operation successful.`, response);
        alert('Category saved successfully!');
        const previouslyEditingId = categoryFormEl.dataset.editingId;

        if (categoryFormEl) {
            delete categoryFormEl.dataset.editingId;
            delete categoryFormEl.dataset.parentIdForNew;
        }

        loadCategories().then(() => {
            if (previouslyEditingId && response && response.id === previouslyEditingId) { // Example if API returns updated/created object
                selectedCategory = findCategoryInTree(response.id); // Re-select the (potentially updated) category
            } else {
                selectedCategory = null; // Or select the newly created one if ID is in response
            }
            updateDetailsPanel(selectedCategory, true);
            setFormEditable(false); // This calls updateUIStates
        }).catch(err => {
            console.error("Failed to reload categories after save", err);
            selectedCategory = null; // Fallback
            updateDetailsPanel(null, true);
            setFormEditable(false);
        });
    };

    const afterSaveError = (error) => {
        console.error('There was a problem saving the category:', error);
        alert(`Error saving category: ${error.message || 'Unknown error.'}`);
    };

    if (editingId) {
        // alert("Edit (PUT) save not yet fully implemented with API call.");
        putData(`api/update/${editingId}`, dataToSend).then(afterSaveSuccess).catch(afterSaveError)
        // afterSaveSuccess({simulated: true, id: editingId}); // Simulate for UI reset
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
    const categoryNameToDelete = selectedCategory.title;

    if (!confirm(`Are you sure you want to delete the category "${categoryNameToDelete}"? This action cannot be undone.`)) {
        return;
    }

    const afterDeleteSuccess = () => {
        console.log('Delete successful.');
        alert('Category has been deleted successfully.');

        loadCategories().then(() => {
            selectedCategory = null;

            updateDetailsPanel(null, true); // Prepare form content for new root
            setFormEditable(false);

        });
    }

    const afterDeleteError = (error) => {
        console.error('There was a problem deleting the category:', error);
        alert(`Error deleting category: ${error.message || 'Unknown error.'}`);
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
    categoryTreePanelEl.appendChild(categoryTreeContainerEl);

    const treeActionsDiv = document.createElement('div');
    treeActionsDiv.className = 'tree-actions';
    addRootCategoryBtnEl = createButton('pc_addRootCategoryBtn', 'Add root category');
    addRootCategoryBtnEl.addEventListener('click', handleAddRootCategory);

    addSubCategoryBtnEl = createButton('pc_addSubCategoryBtn', 'Add subcategory');
    // addSubCategoryBtnEl.addEventListener('click', handleAddSubCategory); // To be implemented

    treeActionsDiv.appendChild(addRootCategoryBtnEl);
    treeActionsDiv.appendChild(addSubCategoryBtnEl);
    categoryTreePanelEl.appendChild(treeActionsDiv);

    // Right Panel: Category Details
    categoryDetailsPanelEl = document.createElement('div');
    categoryDetailsPanelEl.className = 'content-panel category-details-panel';
    categoriesContainer.appendChild(categoryDetailsPanelEl);

    h3DetailsEl = document.createElement('h3');
    // Initial text set by updateUIStates
    categoryDetailsPanelEl.appendChild(h3DetailsEl);

    categoryFormEl = document.createElement('form');
    categoryFormEl.id = 'pc_categoryForm';
    categoryDetailsPanelEl.appendChild(categoryFormEl);

    // Form Groups (Title, Code, Description first)
    const titleGroup = createFormGroup('Title:', 'pc_categoryTitle', 'title');
    categoryTitleInputEl = titleGroup.inputEl;
    categoryFormEl.appendChild(titleGroup.groupDiv);

    // Parent Category Form Group (Label, Text Input for display, Select for edit)
    const parentFormGroupDiv = document.createElement('div');
    parentFormGroupDiv.className = 'form-group';
    const parentLabel = document.createElement('label');
    parentLabel.setAttribute('for', 'pc_categoryParentDisplay'); // Points to the visible field
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
    categoryParentSelectEl.style.display = 'none'; // Initially hidden
    const noParentOption = document.createElement('option');
    noParentOption.value = "null";
    noParentOption.textContent = "Root";
    categoryParentSelectEl.appendChild(noParentOption);
    parentFormGroupDiv.appendChild(categoryParentSelectEl);
    categoryFormEl.appendChild(parentFormGroupDiv);

    const codeGroup = createFormGroup('Code:', 'pc_categoryCode', 'code');
    categoryCodeInputEl = codeGroup.inputEl;
    categoryFormEl.appendChild(codeGroup.groupDiv);

    const descriptionGroup = createFormGroup('Description:', 'pc_categoryDescription', 'description', 'text', true);
    categoryDescriptionInputEl = descriptionGroup.inputEl;
    categoryFormEl.appendChild(descriptionGroup.groupDiv);

    // Action Buttons for the Details Form
    const detailsActionsDiv = document.createElement('div');
    detailsActionsDiv.className = 'details-actions';
    // NOTE: The CSS class '.details-actions' is expected to set 'display: flex'.
    // If it also has 'justify-content: space-between', that's fine.
    categoryFormEl.appendChild(detailsActionsDiv);

    deleteCategoryBtnEl = createButton('pc_deleteCategoryBtn', 'Delete');
    deleteCategoryBtnEl.addEventListener('click', handleDeleteCategory);

    const actionsRightGroup = document.createElement('div');
    actionsRightGroup.className = 'actions-right';
    // ---- MODIFICATION START ----
    // Ensure this group is a flex container (as per CSS, but good for robustness)
    // and push it to the right using margin-left: auto.
    // The CSS class '.actions-right' is expected to set 'display: flex' and 'gap: 5px'.
    actionsRightGroup.style.display = 'flex'; // Reinforces the CSS for .actions-right.
    actionsRightGroup.style.marginLeft = 'auto'; // This pushes the group to the right.
    // The 'gap' between buttons within this group is handled by the CSS rule for .actions-right (gap: 5px).
    // ---- MODIFICATION END ----

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

    loadCategories().catch(err => {
        console.error('Error during initial category load:', err);
        if(categoryTreeContainerEl) categoryTreeContainerEl.innerHTML = "<li>Failed to load categories.</li>";
    });

    allCategoriesFlat.forEach(cat => categoryParentSelectEl.appendChild(cat));
    selectedCategory = null;
    isEditingOrCreating = false;
    updateDetailsPanel(null, true);
    updateUIStates();

    return wrapper;
}
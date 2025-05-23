// demoshop/Includes/script/pages/productCategories.js
import { getData, postData } from "../ajax.js"; // Make sure ajax.js path is correct

// --- STATE VARIABLES ---
let allCategoriesFlat = []; // To store the flat list from API
let categoryTree = [];      // To store the processed tree structure
let selectedCategory = null;
let isEditingOrCreating = false; // To manage form mode

// --- DOM Element References ---
let categoryTreeContainerEl; // The UL element for the tree
let categoryFormEl;
let categoryTitleInputEl, categoryParentDisplayEl, categoryCodeInputEl, categoryDescriptionInputEl;
let addRootCategoryBtnEl, addSubCategoryBtnEl, deleteCategoryBtnEl, editCategoryBtnEl, saveCategoryBtnEl, cancelCategoryBtnEl;
let categoryTreePanelEl, categoryDetailsPanelEl;
let h3DetailsEl;

// --- Helper function to create a form group (label + input) ---
function createFormGroup(labelText, inputId, inputName, inputType = 'text',
                         isTextarea = false, readOnly = true) {
    const div = document.createElement('div');
    div.className = 'form-group';

    const label = document.createElement('label');
    label.setAttribute('for', inputId);
    label.textContent = labelText;
    let inputElement;
    if (isTextarea) {
        inputElement = document.createElement('textarea');
    } else {
        inputElement = document.createElement('input');
        inputElement.type = inputType;
    }
    inputElement.id = inputId;
    inputElement.name = inputName;
    if (readOnly) {
        inputElement.readOnly = true;
    }
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
    if (initiallyHidden) {
        button.style.display = 'none';
    }
    return button;
}

// --- UI STATE MANAGEMENT ---
function updateUIStates() {
    const categoryIsSelected = !!selectedCategory;

    // Right Panel (Details Panel) Visibility
    if (categoryDetailsPanelEl) { // Ensure element exists
        if (categoryIsSelected || isEditingOrCreating) {
            categoryDetailsPanelEl.style.display = '';
        } else {
            categoryDetailsPanelEl.style.display = 'none';
        }
    }

    // Button States
    if (editCategoryBtnEl) editCategoryBtnEl.style.display = (categoryIsSelected && !isEditingOrCreating) ? 'inline-block' : 'none';
    if (addSubCategoryBtnEl) addSubCategoryBtnEl.style.display = (categoryIsSelected && !isEditingOrCreating) ? 'inline-block' : 'none';
    if (deleteCategoryBtnEl) deleteCategoryBtnEl.style.display = (categoryIsSelected && !isEditingOrCreating) ? 'inline-block' : 'none';
    if (addRootCategoryBtnEl) addRootCategoryBtnEl.disabled = isEditingOrCreating;
    if (saveCategoryBtnEl) saveCategoryBtnEl.style.display = isEditingOrCreating ? 'inline-block' : 'none';
    if (cancelCategoryBtnEl) cancelCategoryBtnEl.style.display = isEditingOrCreating ? 'inline-block' : 'none';

    // Heading Text for Details Panel
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
            h3DetailsEl.textContent = 'Selected category';
        } else {
            h3DetailsEl.textContent = 'Category Details';
        }
    }

    // Tree interactivity
    if (categoryTreeContainerEl) {
        categoryTreeContainerEl.style.pointerEvents = isEditingOrCreating ? 'none' : 'auto';
        categoryTreeContainerEl.style.opacity = isEditingOrCreating ? '0.5' : '1';
    }
}

function updateDetailsPanel(categoryData, readOnly = true) {
    if (!categoryFormEl) return; // Ensure form element exists

    if (categoryData) {
        categoryTitleInputEl.value = categoryData.title || categoryData.name || '';
        categoryParentDisplayEl.value = getParentName(categoryData.parent); // Use getParentName
        categoryCodeInputEl.value = categoryData.code || '';
        categoryDescriptionInputEl.value = categoryData.description || '';
    } else {
        categoryFormEl.reset();
        categoryParentDisplayEl.value = '';
        if (isEditingOrCreating && categoryFormEl.dataset.parentIdForNew) {
            categoryParentDisplayEl.value = categoryFormEl.dataset.parentIdForNew === "null" ? 'N/A (Root)' : getParentName(categoryFormEl.dataset.parentIdForNew);
        }
    }
    [categoryTitleInputEl, categoryCodeInputEl, categoryDescriptionInputEl].forEach(input => input.readOnly = readOnly);
    categoryParentDisplayEl.readOnly = true;
}

function setFormEditable(editable) {
    isEditingOrCreating = editable;
    [categoryTitleInputEl, categoryCodeInputEl, categoryDescriptionInputEl].forEach(input => input.readOnly = !editable);
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
        if (categoryTreeContainerEl) {
            categoryTreeContainerEl.innerHTML = '<li>Error loading categories. Please try again.</li>';
        }
    }
    updateUIStates(); // Update UI after loading (e.g., to hide details if no selection)
}

function buildCategoryTreeFromServer(flatList, parentNameKey = null) {
    const children = [];
    if (!Array.isArray(flatList)) return children;

    for (const category of flatList) {
        const categoryName = category.title || category.name;
        const effectiveParentName = (category.parent === "" || category.parent === "N/A (Root)") ? null : category.parent;

        if (effectiveParentName === parentNameKey) {
            const nestedChildren = buildCategoryTreeFromServer(flatList, categoryName);
            children.push({
                ...category,
                children: nestedChildren,
                isExpanded: false // Default to collapsed
            });
        }
    }
    return children.sort((a, b) => (a.title || a.name).localeCompare(b.title || b.name));
}

// In pages/productCategories.js

function renderCategoryTree() {
    if (!categoryTreeContainerEl) return;
    categoryTreeContainerEl.innerHTML = ''; // Clear existing tree

    const createTreeItemRecursive = (category, level) => {
        const li = document.createElement('li');
        li.style.marginLeft = `${level * 10}px`;
        li.dataset.categoryId = category.id;
        li.classList.add('category-item');

        const contentWrapper = document.createElement('div');
        contentWrapper.classList.add('category-item-content');

        // Add click listener to the entire contentWrapper for category selection
        contentWrapper.addEventListener('click', () => {
            // Note: We don't need e.stopPropagation() here unless this li
            // is inside another clickable parent that we don't want to trigger.
            // For selecting the category, this is the intended target.
            handleCategorySelect(category.id);
        });
        contentWrapper.style.cursor = 'pointer'; // Indicate the whole area is clickable for selection

        let subUl = null;

        if (category.children && category.children.length > 0) {
            const toggler = document.createElement('span');
            toggler.textContent = category.isExpanded ? '[-] ' : '[+] ';
            toggler.classList.add('tree-toggler');

            toggler.addEventListener('click', (e) => {
                e.stopPropagation();
                category.isExpanded = !category.isExpanded;
                toggler.textContent = category.isExpanded ? '[-] ' : '[+] ';
                if (subUl) {
                    subUl.style.display = category.isExpanded ? 'block' : 'none';
                }
            });
            contentWrapper.appendChild(toggler);
        } else {
            const placeholder = document.createElement('span');
            placeholder.classList.add('tree-toggler-placeholder');
            placeholder.style.display = 'inline-block';
            placeholder.style.width = '20px'; // Approximate toggler width for alignment
            placeholder.style.marginRight = '5px'; // Consistent spacing
            contentWrapper.appendChild(placeholder);
        }

        const nameSpan = document.createElement('span');
        const categoryName = category.title;
        nameSpan.textContent = categoryName;
        nameSpan.classList.add('category-name');
        // REMOVE the click listener from nameSpan if it was specifically for selection
        // The contentWrapper now handles selection.

        contentWrapper.appendChild(nameSpan);
        li.appendChild(contentWrapper);

        if (selectedCategory && category.id === selectedCategory.id) {
            li.classList.add('selected');
        }

        if (category.children && category.children.length > 0) {
            subUl = document.createElement('ul');
            subUl.classList.add('nested-category-list');
            subUl.style.display = category.isExpanded ? 'block' : 'none';

            category.children.forEach(child => {
                subUl.appendChild(createTreeItemRecursive(child, level + 1));
            });
            li.appendChild(subUl);
        }
        return li;
    };

    if (categoryTree && categoryTree.length > 0) {
        categoryTree.forEach(rootCategory => {
            categoryTreeContainerEl.appendChild(createTreeItemRecursive(rootCategory, 0));
        });
    } else if (allCategoriesFlat.length > 0 && categoryTree.length === 0) {
        categoryTreeContainerEl.innerHTML = '<li>No root categories found (check parent values).</li>';
    } else {
        categoryTreeContainerEl.innerHTML = '<li>No categories available.</li>';
    }
    console.log("renderCategoryTree executed with tree:", categoryTree);
}


// --- CATEGORY SELECTION & HELPERS ---
function findCategoryInTree(id, categoriesToSearch = categoryTree) {
    for (const category of categoriesToSearch) {
        if (String(category.id) === String(id)) return category; // Compare as strings if IDs might be numbers/strings
        if (category.children && category.children.length > 0) {
            const found = findCategoryInTree(id, category.children);
            if (found) return found;
        }
    }
    return null;
}

function getParentName(parentIdentifier) {
    // parentIdentifier is the string name of the parent from the category.parent field, or null.
    if (parentIdentifier === null || parentIdentifier === "null" || parentIdentifier === undefined || parentIdentifier === "") {
        return 'N/A (Root)';
    }
    // Since parentIdentifier is already the name in your current design:
    return parentIdentifier;
}

function handleCategorySelect(categoryId) {
    if (isEditingOrCreating) return;

    const newlySelected = findCategoryInTree(categoryId);

    if (newlySelected) {
        selectedCategory = newlySelected;
        if(categoryFormEl) { // Clear any lingering create/edit context
            delete categoryFormEl.dataset.editingId;
            delete categoryFormEl.dataset.parentIdForNew;
        }
        updateDetailsPanel(selectedCategory, true);
    } else {
        console.warn(`Category with ID ${categoryId} not found in tree.`);
        selectedCategory = null;
        updateDetailsPanel(null, true);
    }
    renderCategoryTree(); // Re-render to update selection highlight
    updateUIStates();
}

// --- EVENT HANDLERS ---
function handleAddRootCategory() {
    if (isEditingOrCreating && addRootCategoryBtnEl.disabled) return;

    selectedCategory = null;
    renderCategoryTree();

    if (categoryFormEl) {
        categoryFormEl.dataset.parentIdForNew = "null";
        delete categoryFormEl.dataset.editingId;
    }

    isEditingOrCreating = true;
    updateDetailsPanel({ parentName: 'N/A (Root)', parentId: null }, false); // parentId is for context if needed, parentName for display
    setFormEditable(true);
    categoryTitleInputEl.focus();
}

function handleCancelAddRoot() { // Consider renaming to handleCancel
    isEditingOrCreating = false;
    // If a category was selected before initiating create/edit, restore it
    // For now, just clears and hides if no category is actively selected.
    // A more robust cancel would remember the state before edit/create.
    if (categoryFormEl) {
        delete categoryFormEl.dataset.editingId;
        delete categoryFormEl.dataset.parentIdForNew;
    }
    // selectedCategory should still be null or the one that was selected before action
    updateDetailsPanel(selectedCategory, true); // Revert to selected category's details or clear
    setFormEditable(false); // This calls updateUIStates
}

function handleSaveButton() {
    const categoryTitle = categoryTitleInputEl.value.trim();
    const code = categoryCodeInputEl.value.trim();
    const description = categoryDescriptionInputEl.value.trim();

    let parentValueForDb = null;
    if (categoryFormEl && categoryFormEl.dataset.parentIdForNew) {
        if (categoryFormEl.dataset.parentIdForNew !== "null") {
            parentValueForDb = categoryFormEl.dataset.parentIdForNew;
        }
    }
    // Note: Logic for editing (PUT) and getting parent for existing category needed here

    if (!categoryTitle || !code) {
        alert("Title and Code are required.");
        return;
    }

    const dataToSend = {
        title: categoryTitle,
        parent: parentValueForDb,
        code: code,
        description: description
    };

    console.log("Sending data to backend for save:", dataToSend);
    const editingId = categoryFormEl.dataset.editingId;

    if (editingId) {
        // putData(`api/categories/${editingId}`, dataToSend) ...
        alert("Edit save not yet implemented.");
    } else {
        postData('api/createCategory', dataToSend)
            .then(response => {
                console.log(`Category '${categoryTitle}' created successfully.`, response);
                alert('Category created successfully!');
                isEditingOrCreating = false;
                selectedCategory = null;
                loadCategories().catch(() => console.error('Error loading categories.')); // Reload categories to include the new one
                updateDetailsPanel(null, true);
                // updateUIStates() will be called by loadCategories -> renderCategoryTree -> or directly if needed
            })
            .catch(error => {
                console.error('There was a problem creating the category:', error);
                alert(`Error creating category: ${error.message || 'Unknown error.'}`);
            });
    }
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
    treeActionsDiv.appendChild(addRootCategoryBtnEl);
    categoryTreePanelEl.appendChild(treeActionsDiv);

    categoryDetailsPanelEl = document.createElement('div');
    categoryDetailsPanelEl.className = 'content-panel category-details-panel';
    categoriesContainer.appendChild(categoryDetailsPanelEl);

    h3DetailsEl = document.createElement('h3');
    categoryDetailsPanelEl.appendChild(h3DetailsEl);

    categoryFormEl = document.createElement('form');
    categoryFormEl.id = 'pc_categoryForm';
    categoryDetailsPanelEl.appendChild(categoryFormEl);

    const titleGroup = createFormGroup('Title:', 'pc_categoryTitle', 'title');
    categoryTitleInputEl = titleGroup.inputEl;
    categoryFormEl.appendChild(titleGroup.groupDiv);

    const parentGroup = createFormGroup('Parent category:', 'pc_categoryParent', 'parent');
    categoryParentDisplayEl = parentGroup.inputEl;
    categoryFormEl.appendChild(parentGroup.groupDiv);

    const codeGroup = createFormGroup('Code:', 'pc_categoryCode', 'code');
    categoryCodeInputEl = codeGroup.inputEl;
    categoryFormEl.appendChild(codeGroup.groupDiv);

    const descriptionGroup = createFormGroup('Description:', 'pc_categoryDescription', 'description', 'text', true);
    categoryDescriptionInputEl = descriptionGroup.inputEl;
    categoryFormEl.appendChild(descriptionGroup.groupDiv);

    const detailsActionsDiv = document.createElement('div');
    detailsActionsDiv.className = 'details-actions';
    categoryFormEl.appendChild(detailsActionsDiv);

    editCategoryBtnEl = createButton('pc_editCategoryBtn', 'Edit');
    addSubCategoryBtnEl = createButton('pc_addSubCategoryBtn', 'Add subcategory');
    deleteCategoryBtnEl = createButton('pc_deleteCategoryBtn', 'Delete');
    saveCategoryBtnEl = createButton('pc_saveCategoryBtn', 'Save', 'button', true);
    cancelCategoryBtnEl = createButton('pc_cancelCategoryBtn', 'Cancel', 'button', true);

    detailsActionsDiv.appendChild(editCategoryBtnEl);
    detailsActionsDiv.appendChild(addSubCategoryBtnEl);
    detailsActionsDiv.appendChild(deleteCategoryBtnEl);
    detailsActionsDiv.appendChild(saveCategoryBtnEl);
    detailsActionsDiv.appendChild(cancelCategoryBtnEl);

    // Event listeners that were in your provided code
    cancelCategoryBtnEl.addEventListener('click', handleCancelAddRoot); // Consider renaming handleCancelAddRoot
    saveCategoryBtnEl.addEventListener('click', handleSaveButton);
    // editCategoryBtnEl.addEventListener('click', handleEditCategory);
    // addSubCategoryBtnEl.addEventListener('click', handleAddSubCategory);
    // deleteCategoryBtnEl.addEventListener('click', handleDeleteCategory);


    loadCategories().catch(() => console.error('Error loading categories.')); // Initial data load
    selectedCategory = null;
    isEditingOrCreating = false;
    updateDetailsPanel(null, true);
    updateUIStates();

    return wrapper;
}
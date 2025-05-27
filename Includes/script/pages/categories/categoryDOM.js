/**
 * Creates a form group (label + input/textarea).
 */
export function createFormGroup(labelText, inputId, inputName,
                                inputType = 'text',
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

/**
 * Creates a button element.
 */
export function createButton(id, text, type = 'button', initiallyHidden = false) {
    const button = document.createElement('button');
    button.type = type;
    button.id = id;
    button.textContent = text;
    if (initiallyHidden) button.style.display = 'none';
    return button;
}

/**
 * Renders the category tree.
 */
export function renderCategoryTree(categoryTreeContainerEl, categoryTreeData,
                                   selectedCategory, onCategorySelect, allCategoriesFlat) {
    if (!categoryTreeContainerEl) return;
    categoryTreeContainerEl.innerHTML = '';

    const createTreeItemRecursive = (category, level) => {
        const li = document.createElement('li');
        li.style.marginLeft = `${level * 10}px`;
        li.dataset.categoryId = category.id;
        li.classList.add('category-item');

        const contentWrapper = document.createElement('div');
        contentWrapper.classList.add('category-item-content');
        contentWrapper.style.cursor = 'pointer';
        contentWrapper.addEventListener('click', (e) => {
            e.stopPropagation();
            onCategorySelect(category.id);
        });

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
        nameSpan.textContent = category.title || 'Unnamed Category';
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

    if (categoryTreeData && categoryTreeData.length > 0) {
        categoryTreeData.forEach(rootCategory =>
            categoryTreeContainerEl.appendChild(createTreeItemRecursive(rootCategory, 0)));
    } else if (allCategoriesFlat && allCategoriesFlat.length > 0 && categoryTreeData.length === 0) {
        categoryTreeContainerEl.innerHTML = '<li>No root categories found. Check parent references in data.</li>';
    } else {
        categoryTreeContainerEl.innerHTML = '<li>No categories available.</li>';
    }
}


/**
 * Updates the category details panel UI elements.
 */
export function updateDetailsPanelContent(elements, categoryData,
                                          isEditingOrCreating, formDataSet,
                                          getParentNameCallback, populateParentSelectCallback) {
    const {
        categoryFormEl, categoryTitleInputEl, categoryCodeInputEl, categoryDescriptionInputEl,
        categoryParentDisplayEl, categoryParentSelectEl
    } = elements;

    if (!categoryFormEl) return;

    if (categoryData) {
        categoryTitleInputEl.value = categoryData.title || '';
        categoryCodeInputEl.value = categoryData.code || '';
        categoryDescriptionInputEl.value = categoryData.description || '';

        if (isEditingOrCreating && formDataSet && formDataSet.editingId) {
            populateParentSelectCallback(categoryData.id, categoryData.parent);
        } else {
            let parentDisplayText = 'Root';
            if (isEditingOrCreating && formDataSet &&
                formDataSet.parentIdForNew && formDataSet.parentIdForNew !== "null") {
                parentDisplayText = getParentNameCallback(formDataSet.parentIdForNew);
            } else if (isEditingOrCreating && formDataSet && formDataSet.parentIdForNew === "null") {
                parentDisplayText = 'Root';
            } else if (!isEditingOrCreating && categoryData.parent) {
                parentDisplayText = getParentNameCallback(categoryData.parent);
            }
            categoryParentDisplayEl.value = parentDisplayText;
        }
    } else {
        categoryFormEl.reset();
        categoryParentDisplayEl.value = (isEditingOrCreating && formDataSet &&
            formDataSet.parentIdForNew === "null")
            ? 'Root' : '';
        if (categoryParentSelectEl) {
            categoryParentSelectEl.innerHTML = '';
            const noParentOption = document.createElement('option');
            noParentOption.value = "null";
            noParentOption.textContent = "Root";
            categoryParentSelectEl.appendChild(noParentOption);
            categoryParentSelectEl.value = "null";
        }
    }
}

/**
 * Populates the parent category select dropdown.
 */
export function populateParentCategorySelect(categoryParentSelectEl, allCategoriesFlat,
                                             editingCategoryId, currentParentName) {
    if (!categoryParentSelectEl) return;
    categoryParentSelectEl.innerHTML = '';

    const noParentOption = document.createElement('option');
    noParentOption.value = "null";
    noParentOption.textContent = "Root";
    categoryParentSelectEl.appendChild(noParentOption);

    allCategoriesFlat.forEach(cat => {
        if (String(cat.id) !== String(editingCategoryId)) {
            const option = document.createElement('option');
            const catName = cat.title;
            option.value = catName;
            option.textContent = catName;
            if (catName === currentParentName) {
                option.selected = true;
            }
            categoryParentSelectEl.appendChild(option);
        }
    });
    if (currentParentName === null || currentParentName === undefined || currentParentName === "" ||
        (typeof currentParentName === 'string' && currentParentName.toLowerCase() === "root")) {
        categoryParentSelectEl.value = "null";
    } else {
        categoryParentSelectEl.value = currentParentName || "null";
    }
}

/**
 * Initializes the basic HTML structure for the product categories page.
 * Returns an object with references to key DOM elements.
 */
export function initializePageStructure(wrapper) {
    const elements = {};

    const h2 = document.createElement('h2');
    h2.textContent = 'Product Categories';
    wrapper.appendChild(h2);

    const categoriesContainer = document.createElement('div');
    categoriesContainer.className = 'categories-container';
    wrapper.appendChild(categoriesContainer);

    // Left Panel: Category Tree
    elements.categoryTreePanelEl = document.createElement('div');
    elements.categoryTreePanelEl.className = 'content-panel category-tree-panel';
    categoriesContainer.appendChild(elements.categoryTreePanelEl);

    elements.categoryTreeContainerEl = document.createElement('ul');
    elements.categoryTreeContainerEl.id = 'pc_categoryTreeRoot';
    elements.categoryTreeContainerEl.className = 'category-tree';
    elements.categoryTreeContainerEl.innerHTML = 'Loading data...';
    elements.categoryTreePanelEl.appendChild(elements.categoryTreeContainerEl);

    const treeActionsDiv = document.createElement('div');
    treeActionsDiv.className = 'tree-actions';
    elements.addRootCategoryBtnEl = createButton('pc_addRootCategoryBtn', 'Add root category');
    elements.addSubCategoryBtnEl = createButton('pc_addSubCategoryBtn', 'Add subcategory');
    treeActionsDiv.appendChild(elements.addRootCategoryBtnEl);
    treeActionsDiv.appendChild(elements.addSubCategoryBtnEl);
    elements.categoryTreePanelEl.appendChild(treeActionsDiv);

    // Right Panel: Category Details
    elements.categoryDetailsPanelEl = document.createElement('div');
    elements.categoryDetailsPanelEl.className = 'content-panel category-details-panel';
    categoriesContainer.appendChild(elements.categoryDetailsPanelEl);

    elements.h3DetailsEl = document.createElement('h3');
    elements.categoryDetailsPanelEl.appendChild(elements.h3DetailsEl);

    elements.categoryFormEl = document.createElement('form');
    elements.categoryFormEl.id = 'pc_categoryForm';
    elements.categoryFormEl.addEventListener('submit', (e) => e.preventDefault());
    elements.categoryDetailsPanelEl.appendChild(elements.categoryFormEl);

    // Form Groups
    const titleGroup = createFormGroup(
        'Title:', 'pc_categoryTitle', 'title', 'text', false, true
    );
    elements.categoryTitleInputEl = titleGroup.inputEl;
    elements.categoryFormEl.appendChild(titleGroup.groupDiv);

    const parentFormGroupDiv = document.createElement('div');
    parentFormGroupDiv.className = 'form-group';
    const parentLabel = document.createElement('label');
    parentLabel.setAttribute('for', 'pc_categoryParentDisplay');
    parentLabel.textContent = 'Parent category:';
    parentFormGroupDiv.appendChild(parentLabel);
    elements.categoryParentDisplayEl = document.createElement('input');
    elements.categoryParentDisplayEl.type = 'text';
    elements.categoryParentDisplayEl.id = 'pc_categoryParentDisplay';
    elements.categoryParentDisplayEl.name = 'parentDisplay';
    elements.categoryParentDisplayEl.readOnly = true;
    parentFormGroupDiv.appendChild(elements.categoryParentDisplayEl);
    elements.categoryParentSelectEl = document.createElement('select');
    elements.categoryParentSelectEl.id = 'pc_categoryParentSelect';
    elements.categoryParentSelectEl.name = 'parentSelect';
    elements.categoryParentSelectEl.style.display = 'none';
    parentFormGroupDiv.appendChild(elements.categoryParentSelectEl);
    elements.categoryFormEl.appendChild(parentFormGroupDiv);


    const codeGroup = createFormGroup(
        'Code:', 'pc_categoryCode', 'code', 'text', false, true
    );
    elements.categoryCodeInputEl = codeGroup.inputEl;
    elements.categoryFormEl.appendChild(codeGroup.groupDiv);

    const descriptionGroup = createFormGroup(
        'Description:', 'pc_categoryDescription', 'description',
        'text', true, true
    );
    elements.categoryDescriptionInputEl = descriptionGroup.inputEl;
    elements.categoryFormEl.appendChild(descriptionGroup.groupDiv);

    // Action Buttons for the Details Form
    const detailsActionsDiv = document.createElement('div');
    detailsActionsDiv.className = 'details-actions';
    elements.categoryFormEl.appendChild(detailsActionsDiv);

    elements.deleteCategoryBtnEl = createButton('pc_deleteCategoryBtn', 'Delete');

    const actionsRightGroup = document.createElement('div');
    actionsRightGroup.className = 'actions-right';
    elements.editCategoryBtnEl = createButton('pc_editCategoryBtn', 'Edit');
    elements.cancelCategoryBtnEl = createButton(
        'pc_cancelCategoryBtn', 'Cancel', 'button', true
    );
    elements.saveCategoryBtnEl = createButton('pc_saveCategoryBtn', 'Save', 'button', true);
    actionsRightGroup.appendChild(elements.cancelCategoryBtnEl);
    actionsRightGroup.appendChild(elements.saveCategoryBtnEl);
    actionsRightGroup.appendChild(elements.editCategoryBtnEl);

    detailsActionsDiv.appendChild(elements.deleteCategoryBtnEl);
    detailsActionsDiv.appendChild(actionsRightGroup);

    return elements;
}
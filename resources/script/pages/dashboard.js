import {AjaxService} from "../ajax.js";

const ajaxService = new AjaxService();

export function showDashboard() {
    const wrapper = document.createElement('div');
    wrapper.innerHTML = '<h2>Dashboard</h2><p>Loading data...</p>';

    ajaxService.get('api/dashboard')
        .then(data => {
            wrapper.innerHTML = '';
            wrapper.appendChild(renderDashboard(data));
        })
        .catch(error => {
            console.error("Failed to load dashboard data:", error);
            wrapper.innerHTML = '<h2>Dashboard</h2><p>Error loading data. Please try again later.</p>';
        });

    return wrapper;
}

function renderDashboard(data) {
    const h2 = document.createElement("h2");
    h2.textContent = "Dashboard";

    const container = document.createElement("div");
    container.className = "dashboard-flex-container";

    // Panel 1: Site Statistics
    const panel1 = document.createElement("div");
    panel1.className = "dashboard-panel";
    const h4_1 = document.createElement("h4");
    h4_1.textContent = "Site Statistics";
    const p1 = document.createElement("p");
    const span1 = document.createElement("span");
    span1.textContent = "Products count:";
    const input1 = document.createElement("input");
    input1.type = "text";
    input1.className = "input-w50";
    input1.value = data.productsCount;
    input1.readOnly = true;
    p1.append(span1, input1);
    const p2 = document.createElement("p");
    const span2 = document.createElement("span");
    span2.textContent = "Categories count:";
    const input2 = document.createElement("input");
    input2.type = "text";
    input2.className = "input-w50";
    input2.value = data.categoriesCount;
    input2.readOnly = true;
    p2.append(span2, input2);
    panel1.append(h4_1, p1, p2);

    // Panel 2: Usage Metrics
    const panel2 = document.createElement("div");
    panel2.className = "dashboard-panel";
    const h4_2 = document.createElement("h4");
    h4_2.textContent = "Usage Metrics";
    const p3 = document.createElement("p");
    const span3 = document.createElement("span");
    span3.textContent = "Home page opening count:";
    const input3 = document.createElement("input");
    input3.type = "text";
    input3.className = "input-w50";
    input3.value = data.homePageOpeningCount;
    input3.readOnly = true;
    p3.append(span3, input3);
    const p4 = document.createElement("p");
    const span4 = document.createElement("span");
    span4.textContent = "Most often viewed product:";
    const input4 = document.createElement("input");
    input4.type = "text";
    input4.className = "input-w120";
    input4.value = data.mostOftenViewedProduct;
    input4.readOnly = true;
    p4.append(span4, input4);
    const p5 = document.createElement("p");
    const span5 = document.createElement("span");
    span5.textContent = "Number of prod1 views:";
    const input5 = document.createElement("input");
    input5.type = "text";
    input5.className = "input-w50";
    input5.value = data.numberOfProd1Views;
    input5.readOnly = true;
    p5.append(span5, input5);
    panel2.append(h4_2, p3, p4, p5);

    container.append(panel1, panel2);

    const fragment = document.createDocumentFragment();
    fragment.append(h2, container);

    return fragment;
}
// noinspection ExceptionCaughtLocallyJS

export function getQueryParameter(name) {
    const urlParams = new URLSearchParams(window.location.search)
    return urlParams.get(name)
}

export async function getData(url) {
    try {
        const response = await fetch(url);
        if (!response.ok) throw new Error("Error fetching data.");
        return await response.json();
    } catch (err) {
        console.error(err);
        throw err;
    }
}

export async function postData(url, data = {}) {
    try {
        const response = await fetch(url, {
            method: "POST",
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        if (!response.ok) throw new Error("Error sending data.");
        return await response.json();
    } catch (err) {
        console.error(err);
        throw err;
    }
}

export async function putData(url, data = {}) {
    try {
        const response = await fetch(url, {
            method: "PUT",
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        if (!response.ok) throw new Error("Error updating data.");
        return await response.json();
    } catch (err) {
        console.error(err);
        throw err;
    }
}

export async function deleteData(url) {
    try {
        const response = await fetch(url, {
            method: "DELETE"
        });
        if (!response.ok) throw new Error("Error deleting data.");
        return await response.json();
    } catch (err) {
        console.error(err);
        throw err;
    }
}
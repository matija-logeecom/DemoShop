// demoshop/Includes/script/ajax.js
export class AjaxService {
    async _request(url, method = 'GET', data = null, headers = {}) {
        const options = {
            method: method,
            headers: {
                'Content-Type': 'application/json',
                ...headers,
            },
        };

        if (data && (method === 'POST' || method === 'PUT' || method === 'PATCH')) {
            options.body = JSON.stringify(data);
        }

        try {
            const response = await fetch(url, options);

            if (!response.ok) {
                let errorPayload = { // Default error structure
                    message: `HTTP error! Status: ${response.status}`,
                    status: response.status,
                    responseBody: null
                };
                try {
                    // Attempt to parse the JSON error response from the server
                    const serverErrorJson = await response.json();
                    // Our server sends {"error": "message"} for JsonResponse::createInternalServerError etc.
                    // Or {"message": "message"} for successful JsonResponses in CategoryController
                    errorPayload.message = serverErrorJson.error || serverErrorJson.message || errorPayload.message;
                    errorPayload.responseBody = serverErrorJson; // Store the full parsed body
                } catch (e) {
                    // If response body isn't JSON, or parsing fails, stick with the HTTP status
                    console.warn(`Could not parse JSON error response from ${url}`, e);
                    // Optionally, you could try response.text() here as a fallback for non-JSON errors
                }
                console.error(`Error ${method}ing data to ${url}:`, errorPayload.message, 'Full error payload:', errorPayload);

                // Throw an error object that includes the status and potentially the parsed body
                const error = new Error(errorPayload.message);
                error.status = errorPayload.status;
                error.responseBody = errorPayload.responseBody;
                throw error;
            }

            if (response.status === 204) { // No Content
                return null;
            }
            return await response.json(); // For successful responses
        } catch (err) {
            // This catches network errors or errors thrown from the !response.ok block above
            console.error(`Request failed ${method} to ${url}:`, err.message, err);
            // Ensure the error being re-thrown has the details if it's one we constructed
            if (!err.status && !err.responseBody) { // If it's a generic network error, not our custom one
                const networkError = new Error(`Network error or an issue with the request to ${url}: ${err.message}`);
                networkError.isNetworkError = true;
                throw networkError;
            }
            throw err; // Re-throw our custom error or other caught errors
        }
    }

    // GET, POST, PUT, DELETE methods remain the same
    async get(url, headers = {}) {
        return this._request(url, 'GET', null, headers);
    }

    async post(url, data, headers = {}) {
        return this._request(url, 'POST', data, headers);
    }

    async put(url, data, headers = {}) {
        return this._request(url, 'PUT', data, headers);
    }

    async delete(url, headers = {}) {
        return this._request(url, 'DELETE', null, headers);
    }

    static getQueryParameter(name) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(name);
    }
}
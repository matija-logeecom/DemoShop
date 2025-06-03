export class AjaxService {
    async _request(url, method = 'GET',
                   data = null, headers = {}, isFormData = false) {
        const options = {
            method: method,
            headers: {...headers},
        };

        if (!isFormData && (method === 'POST' || method === 'PUT' || method === 'PATCH' || method === 'DELETE')) {
            options.headers['Content-Type'] = 'application/json';
        }

        if (data) {
            if (isFormData) {
                options.body = data;
            } else if (method === 'POST' || method === 'PUT' || method === 'PATCH' || method === 'DELETE') {
                options.body = JSON.stringify(data);
            }
        }

        try {
            const response = await fetch(url, options);

            if (!response.ok) {
                let errorPayload = {
                    message: `HTTP error! Status: ${response.status}`,
                    status: response.status,
                    responseBody: null
                };
                try {
                    const serverErrorJson = await response.json();
                    errorPayload.message = serverErrorJson.error || serverErrorJson.message || errorPayload.message;
                    errorPayload.responseBody = serverErrorJson;
                } catch (e) {
                    try {
                        const serverErrorText = await response.text();
                        errorPayload.message = serverErrorText || errorPayload.message;
                        errorPayload.responseBody = {raw: serverErrorText};
                    } catch (textErr) {
                        console.warn(`Could not parse error response from ${url} as JSON or text`, textErr);
                    }
                }
                console.error(`Error ${method}ing data to ${url}:`,
                    errorPayload.message, 'Full error payload:', errorPayload);

                const error = new Error(errorPayload.message);
                error.status = errorPayload.status;
                error.responseBody = errorPayload.responseBody;
                throw error;
            }

            if (response.status === 204 || response.headers.get("content-length") === "0") {
                return null;
            }
            return await response.json();
        } catch (err) {
            console.error(`Request failed ${method} to ${url}:`, err.message, err);
            if (!err.status && !err.responseBody) {
                const networkError = new Error(
                    `Network error or an issue with the request to ${url}: ${err.message}`);
                networkError.isNetworkError = true;
                throw networkError;
            }
            throw err;
        }
    }

    async get(url, headers = {}) {
        return this._request(url, 'GET', null, headers);
    }

    async post(url, data, headers = {}) {
        return this._request(url, 'POST', data, headers, false);
    }

    async postWithFormData(url, formData, headers = {}) {
        return this._request(url, 'POST', formData, headers, true);
    }

    async put(url, data, headers = {}) {
        return this._request(url, 'PUT', data, headers, false);
    }

    async delete(url, data = null, headers = {}) {
        return this._request(url, 'DELETE', data, headers, false);
    }

    static getQueryParameter(name) {
        const urlParams = new URLSearchParams(window.location.search);
        return urlParams.get(name);
    }
}
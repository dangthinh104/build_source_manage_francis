/**
 * API Response Wrapper
 * 
 * Standardized API response handler following JSend specification.
 * Provides type-safe accessors for response data.
 * 
 * @see https://github.com/omniti-labs/jsend
 */

export class ApiResponse {
    constructor(axiosResponse) {
        this.raw = axiosResponse;
        this.body = axiosResponse.data;
    }

    /**
     * Check if response is successful
     * @returns {boolean}
     */
    get isSuccess() {
        return this.body.status === 'success';
    }

    /**
     * Check if response is a client error (fail)
     * @returns {boolean}
     */
    get isFail() {
        return this.body.status === 'fail';
    }

    /**
     * Check if response is a server error
     * @returns {boolean}
     */
    get isError() {
        return this.body.status === 'error';
    }

    /**
     * Get response data (only available for success responses)
     * @returns {any|null}
     */
    get data() {
        return this.body.data !== undefined ? this.body.data : null;
    }

    /**
     * Get response message
     * @returns {string}
     */
    get message() {
        return this.body.message || '';
    }

    /**
     * Get validation/fail errors
     * @returns {object}
     */
    get errors() {
        return this.body.errors || {};
    }

    /**
     * Get debug information (only in development)
     * @returns {object|null}
     */
    get debug() {
        return this.body.debug || null;
    }

    /**
     * Display toast notification based on response status
     * @param {Function} toast - Toast notification function
     * @param {string|null} successMessage - Override success message
     */
    handleToast(toast, successMessage = null) {
        if (this.isSuccess) {
            const msg = successMessage || this.message || 'Operation successful';
            toast(msg, { type: 'success' });
        } else if (this.isFail) {
            toast(this.message || 'Operation failed', { type: 'warning' });
        } else {
            toast(this.message || 'An error occurred', { type: 'error' });
        }
    }

    /**
     * Throw error if response is not successful
     * @throws {Error}
     */
    throwIfNotSuccess() {
        if (!this.isSuccess) {
            throw new Error(this.message || 'Request failed');
        }
    }

    /**
     * Get first validation error message
     * @returns {string|null}
     */
    getFirstError() {
        const errorKeys = Object.keys(this.errors);
        if (errorKeys.length > 0) {
            const firstKey = errorKeys[0];
            const firstError = this.errors[firstKey];
            return Array.isArray(firstError) ? firstError[0] : firstError;
        }
        return null;
    }
}

/**
 * Wrap axios response in ApiResponse object
 * @param {Promise<AxiosResponse>} axiosPromise
 * @returns {Promise<ApiResponse>}
 */
export async function wrapResponse(axiosPromise) {
    try {
        const response = await axiosPromise;
        return new ApiResponse(response);
    } catch (error) {
        // If axios throws error (4xx, 5xx status codes), wrap the error response
        if (error.response) {
            return new ApiResponse(error.response);
        }
        // Network error or other issues
        throw error;
    }
}

/**
 * Extract data from response (backward compatible)
 * @param {AxiosResponse} axiosResponse
 * @returns {any}
 */
export function extractData(axiosResponse) {
    const body = axiosResponse.data;

    // New format (JSend)
    if (body.status === 'success' && body.data !== undefined) {
        return body.data;
    }

    // Old format (flat data)
    return body;
}

/**
 * Check if response is successful
 * @param {AxiosResponse} axiosResponse
 * @returns {boolean}
 */
export function isSuccess(axiosResponse) {
    const body = axiosResponse.data;

    // JSend format
    if (body.status === 'success') {
        return true;
    }

    // Legacy formats
    if (body.status === true || body.status === 1) {
        return true;
    }

    return false;
}

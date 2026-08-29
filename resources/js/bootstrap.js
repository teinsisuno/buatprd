window._originalFetch = window.fetch.bind(window);

window.fetch = async (url, options = {}) => {
    options.headers = {
        ...options.headers,
        'X-Requested-With': 'XMLHttpRequest',
        'Accept': 'application/json',
    };

    if (options.body && typeof options.body === 'object' && !(options.body instanceof FormData)) {
        options.body = JSON.stringify(options.body);
        options.headers['Content-Type'] = 'application/json';
    }

    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    if (token) {
        options.headers['X-CSRF-TOKEN'] = token;
    }

    const response = await window._originalFetch(url, options);

    if (response.status === 419) {
        window.location.reload();
    }

    return response;
};


async function apiFetch({url,  method = 'GET', headers = {}, body = null }) {
    const token = localStorage.getItem('masterTokenWebsite')
    try {
         const options = {
            method,
            headers: {
                ...headers,
                Authorization: token ? `Bearer ${token}` : undefined,
            },
        };

        if (body) {
            if (headers['Content-Type'] === 'application/x-www-form-urlencoded') {
                options.body = new URLSearchParams(body).toString();
            } else if (headers['Content-Type'] === 'application/json') {
                options.body = JSON.stringify(body);
            } else {
                options.body = body;
            }
        }

        const response = await fetch(url, options);

        if (!response.ok) {
            const errorText = await response.text();
            console.error('Fetch error:', response.status, errorText);
            throw new Error(`HTTP error! Status: ${response.status}`);
        }

        return await response.json();
    } catch (error) {
        console.error('Fetch error:', error);
        throw error;
    }
}

export default apiFetch
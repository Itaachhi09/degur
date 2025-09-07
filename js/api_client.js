// Simple API client for REST endpoints
const API_BASE = window.API_BASE_URL || '/php/rest/index.php?r=';

export async function apiFetch(path, { method = 'GET', body = null, requireAuth = false } = {}) {
    const headers = { 'Accept': 'application/json' };
    if (body && !(body instanceof FormData)) {
        headers['Content-Type'] = 'application/json';
        body = JSON.stringify(body);
    }
    const token = localStorage.getItem('jwt_token');
    if (requireAuth && token) headers['Authorization'] = 'Bearer ' + token;

    const res = await fetch(API_BASE + path, { method, headers, body });
    const contentType = res.headers.get('content-type') || '';
    if (!res.ok) {
        let err = { status: res.status };
        if (contentType.includes('application/json')) err = Object.assign(err, await res.json());
        throw err;
    }
    if (contentType.includes('application/json')) return await res.json();
    return await res.text();
}

export async function login(username, password) {
    const res = await apiFetch('auth/login', { method: 'POST', body: { username, password } });
    if (res.token) {
        localStorage.setItem('jwt_token', res.token);
    }
    return res;
}

export function logout() {
    localStorage.removeItem('jwt_token');
}

export function getToken() { return localStorage.getItem('jwt_token'); }

export default { apiFetch, login, logout, getToken };

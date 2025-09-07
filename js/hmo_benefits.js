// HMO & Benefits JS Module
// Uses new REST API client at /php/rest/index.php?r=hmo
import * as apiClient from './api_client.js';

export async function fetchHmoBenefits() {
    const res = await apiClient.apiFetch('hmo');
    return res;
}

export async function addHmoBenefit(data) {
    const res = await apiClient.apiFetch('hmo', { method: 'POST', body: data, requireAuth: true });
    return res;
}

export async function updateHmoBenefit(data) {
    if (!data.id) throw new Error('id required');
    const res = await apiClient.apiFetch(`hmo/${data.id}`, { method: 'PUT', body: data, requireAuth: true });
    return res;
}

export async function deleteHmoBenefit(id) {
    const res = await apiClient.apiFetch(`hmo/${id}`, { method: 'DELETE', requireAuth: true });
    return res;
}

// UI integration functions can be added here to match other modules

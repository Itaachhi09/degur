// HMO & Benefits JS Module
// Handles frontend/backend logic for HMO & Benefits

const HmoBenefitsAPI = '/php/api/hmo_benefits.php';

export async function fetchHmoBenefits() {
    const res = await fetch(HmoBenefitsAPI);
    return res.json();
}

export async function addHmoBenefit(data) {
    const res = await fetch(HmoBenefitsAPI, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });
    return res.json();
}

export async function updateHmoBenefit(data) {
    const res = await fetch(HmoBenefitsAPI, {
        method: 'PUT',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(data)
    });
    return res.json();
}

export async function deleteHmoBenefit(id) {
    const res = await fetch(HmoBenefitsAPI, {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id })
    });
    return res.json();
}

// UI integration functions can be added here to match other modules

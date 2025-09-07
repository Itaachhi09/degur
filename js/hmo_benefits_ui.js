// Display HMO & Benefits Section
import { fetchHmoBenefits, addHmoBenefit, updateHmoBenefit, deleteHmoBenefit } from './hmo_benefits.js';

function createEl(tag, attrs = {}, children = []) {
    const el = document.createElement(tag);
    Object.entries(attrs).forEach(([k, v]) => {
        if (k === 'class') el.className = v;
        else if (k === 'html') el.innerHTML = v;
        else el.setAttribute(k, v);
    });
    (Array.isArray(children) ? children : [children]).forEach(child => {
        if (!child) return;
        if (typeof child === 'string') el.appendChild(document.createTextNode(child));
        else el.appendChild(child);
    });
    return el;
}

function renderTable(rows) {
    const table = createEl('table', { class: 'w-full border-collapse' });
    const thead = createEl('thead', { class: 'bg-gray-100' });
    const headerRow = createEl('tr');
    ['ID', 'Employee ID', 'Provider', 'Type', 'Details', 'Actions'].forEach(h => {
        headerRow.appendChild(createEl('th', { class: 'text-left p-2 border' }, h));
    });
    thead.appendChild(headerRow);
    table.appendChild(thead);

    const tbody = createEl('tbody');
    rows.forEach(r => {
        const tr = createEl('tr');
        tr.appendChild(createEl('td', { class: 'p-2 border' }, String(r.id || '')));
        tr.appendChild(createEl('td', { class: 'p-2 border' }, String(r.employee_id || '')));
        tr.appendChild(createEl('td', { class: 'p-2 border' }, r.hmo_provider || ''));
        tr.appendChild(createEl('td', { class: 'p-2 border' }, r.benefit_type || ''));
        tr.appendChild(createEl('td', { class: 'p-2 border' }, r.benefit_details || ''));

        const actionsTd = createEl('td', { class: 'p-2 border' });
        const editBtn = createEl('button', { class: 'mr-2 px-2 py-1 bg-yellow-300 rounded' }, 'Edit');
        const delBtn = createEl('button', { class: 'px-2 py-1 bg-red-400 text-white rounded' }, 'Delete');

        editBtn.addEventListener('click', () => openEditForm(r));
        delBtn.addEventListener('click', async () => {
            if (!confirm('Delete this HMO/benefit record?')) return;
            try {
                await deleteHmoBenefit(r.id);
                await displayHmoBenefitsSection();
            } catch (err) { console.error(err); alert('Failed to delete'); }
        });

        actionsTd.appendChild(editBtn);
        actionsTd.appendChild(delBtn);
        tr.appendChild(actionsTd);
        tbody.appendChild(tr);
    });
    table.appendChild(tbody);
    return table;
}

function createForm(onSubmit, initial = {}) {
    const form = createEl('form', { class: 'space-y-2 p-4 border rounded bg-white' });

    const empId = createEl('input', { type: 'text', placeholder: 'Employee ID', value: initial.employee_id || '', class: 'w-full p-2 border' });
    const provider = createEl('input', { type: 'text', placeholder: 'HMO Provider', value: initial.hmo_provider || '', class: 'w-full p-2 border' });
    const type = createEl('input', { type: 'text', placeholder: 'Benefit Type', value: initial.benefit_type || '', class: 'w-full p-2 border' });
    const details = createEl('textarea', { placeholder: 'Benefit Details', class: 'w-full p-2 border' }, initial.benefit_details || '');

    form.appendChild(createEl('label', {}, 'Employee ID')); form.appendChild(empId);
    form.appendChild(createEl('label', {}, 'Provider')); form.appendChild(provider);
    form.appendChild(createEl('label', {}, 'Type')); form.appendChild(type);
    form.appendChild(createEl('label', {}, 'Details')); form.appendChild(details);

    const submit = createEl('button', { type: 'submit', class: 'px-3 py-2 bg-blue-600 text-white rounded' }, 'Save');
    const cancel = createEl('button', { type: 'button', class: 'px-3 py-2 bg-gray-300 rounded ml-2' }, 'Cancel');
    form.appendChild(createEl('div', { class: 'pt-2' }, [submit, cancel]));

    cancel.addEventListener('click', (e) => { e.preventDefault(); if (form.parentElement) form.parentElement.innerHTML = ''; });
    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const payload = {
            employee_id: empId.value.trim(),
            hmo_provider: provider.value.trim(),
            benefit_type: type.value.trim(),
            benefit_details: details.value.trim()
        };
        try {
            await onSubmit(payload);
            await displayHmoBenefitsSection();
        } catch (err) { console.error(err); alert('Save failed'); }
    });

    return form;
}

let currentEditId = null;
function openEditForm(record) {
    currentEditId = record.id;
    const container = document.getElementById('main-content-area');
    const form = createForm(async (payload) => {
        payload.id = currentEditId;
        await updateHmoBenefit(payload);
    }, record);
    if (container) {
        container.innerHTML = '';
        container.appendChild(createEl('h3', { class: 'text-lg font-semibold mb-2' }, 'Edit HMO Benefit'));
        container.appendChild(form);
    }
}

export async function displayHmoBenefitsSection() {
    const container = document.getElementById('main-content-area');
    if (!container) {
        console.error('Main content area not found');
        return;
    }
    container.innerHTML = '';
    // Centered wrapper for consistent dashboard theming
    const wrapper = createEl('div', { class: 'max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6' });
    const inner = createEl('div', { class: 'bg-white p-4 rounded-lg shadow-sm' });

    const header = createEl('div', { class: 'flex items-center justify-between mb-4' });
    header.appendChild(createEl('h3', { class: 'text-xl font-semibold' }, 'HMO & Benefits'));
    const addBtn = createEl('button', { class: 'px-3 py-2 bg-green-600 text-white rounded' }, 'Add HMO/Benefit');
    header.appendChild(addBtn);
    inner.appendChild(header);

    addBtn.addEventListener('click', () => {
        const form = createForm(async (payload) => {
            await addHmoBenefit(payload);
        });
        inner.innerHTML = '';
        inner.appendChild(createEl('h3', { class: 'text-xl font-semibold mb-2' }, 'Add HMO/Benefit'));
        inner.appendChild(form);
    });

    try {
        const res = await fetchHmoBenefits();
        const rows = res.HMO_Benefits || res.hmo_benefits || res.data || [];
        inner.appendChild(renderTable(rows));
    } catch (err) {
        console.error('Failed to load HMO benefits', err);
        inner.appendChild(createEl('p', { class: 'text-red-500' }, 'Failed to load HMO/Benefits.'));
    }

    wrapper.appendChild(inner);
    container.appendChild(wrapper);
}

// end of file

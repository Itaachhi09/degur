/**
 * Shared Utilities for HR Management System
 * Updated for REST API integration
 */

// Base URL for API calls
export const API_BASE_URL = 'php/api/';
export const PYTHON_API_URL = 'http://localhost:5000/api/';

// JWT token management
let authToken = localStorage.getItem('auth_token');

export function setAuthToken(token) {
    authToken = token;
    localStorage.setItem('auth_token', token);
}

export function getAuthToken() {
    return authToken || localStorage.getItem('auth_token');
}

export function clearAuthToken() {
    authToken = null;
    localStorage.removeItem('auth_token');
}

// API request helper with authentication
export async function apiRequest(endpoint, options = {}) {
    const url = endpoint.startsWith('http') ? endpoint : `${API_BASE_URL}${endpoint}`;
    
    const defaultOptions = {
        headers: {
            'Content-Type': 'application/json',
        }
    };
    
    // Add authentication header if token exists
    const token = getAuthToken();
    if (token) {
        defaultOptions.headers['Authorization'] = `Bearer ${token}`;
    }
    
    const finalOptions = {
        ...defaultOptions,
        ...options,
        headers: {
            ...defaultOptions.headers,
            ...options.headers
        }
    };
    
    try {
        const response = await fetch(url, finalOptions);
        
        // Handle 401 Unauthorized
        if (response.status === 401) {
            clearAuthToken();
            window.location.href = '/login';
            return;
        }
        
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({}));
            throw new Error(errorData.error?.message || `HTTP error! status: ${response.status}`);
        }
        
        return await response.json();
    } catch (error) {
        console.error('API request failed:', error);
        throw error;
    }
}

// Python API request helper
export async function pythonApiRequest(endpoint, options = {}) {
    const url = `${PYTHON_API_URL}${endpoint}`;
    return apiRequest(url, options);
}

/**
 * Fetches employees and populates a given select element.
 * @param {string} selectElementId - The ID of the select element to populate.
 * @param {boolean} [includeAllOption=false] - Whether to include an "All Employees" option.
 */
export async function populateEmployeeDropdown(selectElementId, includeAllOption = false) {
    const selectElement = document.getElementById(selectElementId);
    if (!selectElement) {
        console.error(`[populateEmployeeDropdown] Element with ID '${selectElementId}' NOT FOUND.`);
        return;
    }

    // Clear existing options except potential placeholder if needed later
    selectElement.innerHTML = ''; // Clear existing options first

    // Create and add the placeholder/default option
    let placeholderOption = document.createElement('option');
    if (includeAllOption) {
        placeholderOption.value = "";
        placeholderOption.textContent = "All Employees";
    } else {
        placeholderOption.value = "";
        placeholderOption.textContent = "-- Select Employee --";
        placeholderOption.disabled = true; // Disable selection of placeholder initially
        placeholderOption.selected = true;
    }
    selectElement.appendChild(placeholderOption);

    try {
        const response = await apiRequest('employees');
        const employees = response.data?.employees || response.employees || [];

        if (response.error) {
            console.error(`[populateEmployeeDropdown] API error for '${selectElementId}':`, response.error);
            placeholderOption.textContent = 'Error loading!';
            placeholderOption.disabled = true;
        } else if (!Array.isArray(employees)) {
             console.error(`[populateEmployeeDropdown] Invalid data format for '${selectElementId}'. Expected array.`);
             placeholderOption.textContent = 'Invalid data!';
             placeholderOption.disabled = true;
        } else if (employees.length === 0) {
            // If 'All Employees' is allowed, keep it, otherwise show 'No employees'
            if (!includeAllOption) {
                placeholderOption.textContent = 'No employees available';
                placeholderOption.disabled = true;
            } else {
                 // Keep "All Employees" selectable even if list is empty for filtering purposes
                 placeholderOption.disabled = false;
            }
        } else {
             // Enable placeholder if it was disabled and we have employees
             if (!includeAllOption) placeholderOption.disabled = false;

            // Populate with fetched employees
            employees.forEach(emp => {
                const option = document.createElement('option');
                option.value = emp.EmployeeID;
                // Use textContent for security
                option.textContent = `${emp.FirstName} ${emp.LastName} (ID: ${emp.EmployeeID})`;
                selectElement.appendChild(option);
            });
        }
    } catch (error) {
        console.error(`[populateEmployeeDropdown] Failed for '${selectElementId}':`, error);
        // Reset to placeholder but indicate error
        selectElement.innerHTML = ''; // Clear again
        placeholderOption.textContent = 'Error loading!';
        placeholderOption.value = "";
        placeholderOption.disabled = true;
        placeholderOption.selected = true;
        selectElement.appendChild(placeholderOption);
    }
}


/**
 * Fetches shifts and populates a given select element.
 * @param {string} selectElementId - The ID of the select element to populate.
 */
export async function populateShiftDropdown(selectElementId) {
    const selectElement = document.getElementById(selectElementId);
    if (!selectElement) {
         console.error(`[populateShiftDropdown] Element with ID '${selectElementId}' NOT FOUND.`);
         return;
    }

    // Preserve the first option (likely a placeholder like "-- No Specific Shift --")
    const firstOptionHTML = selectElement.options[0] ? selectElement.options[0].outerHTML : '<option value="">-- Select Shift --</option>';
    selectElement.innerHTML = firstOptionHTML; // Keep only the first option initially

    try {
        const response = await fetch(`${API_BASE_URL}get_shifts.php`);
        if (!response.ok) throw new Error(`HTTP error! status: ${response.status}`);
        const shifts = await response.json();

        if (shifts.error) {
            console.error("Error fetching shifts for dropdown:", shifts.error);
            // Optionally add an error option or modify the placeholder
            selectElement.options[0].textContent = "Error loading shifts";
            selectElement.options[0].disabled = true;
        } else if (shifts.length > 0) {
            shifts.forEach(shift => {
                const option = document.createElement('option');
                option.value = shift.ShiftID;
                // Format time nicely for display
                const startTime = shift.StartTimeFormatted || (shift.StartTime ? new Date(`1970-01-01T${shift.StartTime}`).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true }) : 'N/A');
                const endTime = shift.EndTimeFormatted || (shift.EndTime ? new Date(`1970-01-01T${shift.EndTime}`).toLocaleTimeString([], { hour: '2-digit', minute: '2-digit', hour12: true }) : 'N/A');
                option.textContent = `${shift.ShiftName} (${startTime} - ${endTime})`;
                selectElement.appendChild(option);
            });
        } else {
             // No shifts found, maybe update placeholder text
             if (selectElement.options[0].value === "") { // Only if it's a generic placeholder
                 selectElement.options[0].textContent = "-- No shifts available --";
             }
        }
    } catch (error) {
        console.error('Error populating shift dropdown:', error);
        // Update placeholder to show error
        selectElement.options[0].textContent = "Error loading shifts";
        selectElement.options[0].disabled = true;
    }
}

// Add other shared utility functions here as needed (e.g., showNotification, formatDate)


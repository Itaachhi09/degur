/**
 * Payroll - Payroll Runs Module
 */
import { API_BASE_URL } from '../utils.js'; // Import base URL
import apiClient from '../api_client.js';

/**
 * Displays the Payroll Runs section.
 */
export async function displayPayrollRunsSection() {
    console.log("[Display] Displaying Payroll Runs Section...");
    const pageTitleElement = document.getElementById('page-title');
    const mainContentArea = document.getElementById('main-content-area');
    if (!pageTitleElement || !mainContentArea) {
        console.error("displayPayrollRunsSection: Core DOM elements not found.");
        return;
    }
    pageTitleElement.textContent = 'Payroll Runs';

    // Inject HTML structure (wrapped in standardized container for consistent layout)
    mainContentArea.innerHTML = `
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="bg-white p-6 rounded-lg shadow-md border border-[#F7E6CA] space-y-6">
                <div class="border-b border-gray-200 pb-4">
                    <h3 class="text-lg font-semibold text-[#4E3B2A] mb-3">Create New Payroll Run</h3>
                    <form id="create-payroll-run-form" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label for="pr-start-date" class="block text-sm font-medium text-gray-700 mb-1">Pay Period Start Date:</label>
                                <input type="date" id="pr-start-date" name="pay_period_start_date" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                            </div>
                            <div>
                                <label for="pr-end-date" class="block text-sm font-medium text-gray-700 mb-1">Pay Period End Date:</label>
                                <input type="date" id="pr-end-date" name="pay_period_end_date" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                            </div>
                            <div>
                                <label for="pr-payment-date" class="block text-sm font-medium text-gray-700 mb-1">Payment Date:</label>
                                <input type="date" id="pr-payment-date" name="payment_date" required class="w-full p-2 border border-gray-300 rounded-md shadow-sm focus:ring-[#4E3B2A] focus:border-[#4E3B2A]">
                            </div>
                        </div>
                        <div class="pt-2">
                            <button type="submit" class="px-4 py-2 bg-[#594423] text-white rounded-md hover:bg-[#4E3B2A] focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-[#594423] transition duration-150 ease-in-out">
                                Create Payroll Run
                            </button>
                            <span id="create-pr-status" class="ml-4 text-sm"></span>
                        </div>
                    </form>
                </div>

                <div>
                    <h3 class="text-lg font-semibold text-[#4E3B2A] mb-3">Payroll Run History</h3>
                    <div id="payroll-runs-list-container" class="overflow-x-auto">
                        <p>Loading payroll runs...</p>
                    </div>
                </div>
            </div>
        </div>`;

    // Add listeners after HTML injection
    requestAnimationFrame(async () => {
        const createRunForm = document.getElementById('create-payroll-run-form');
        if (createRunForm) {
            if (!createRunForm.hasAttribute('data-listener-attached')) {
                createRunForm.addEventListener('submit', handleCreatePayrollRun);
                createRunForm.setAttribute('data-listener-attached', 'true');
            }
        } else { console.error("Create Payroll Run form not found."); }

        await loadPayrollRuns(); // Load initial list
    });
}

/**
 * Fetches payroll run records from the API.
 */
async function loadPayrollRuns() {
    console.log("[Load] Loading Payroll Runs...");
    const container = document.getElementById('payroll-runs-list-container');
    if (!container) return;
    container.innerHTML = '<p class="text-center py-4">Loading payroll runs...</p>';

    try {
        const payrollRuns = await apiClient.apiFetch('payroll', { method: 'GET', requireAuth: true });
        renderPayrollRunsTable(payrollRuns);
    } catch (error) {
        console.error('Error loading payroll runs:', error);
        container.innerHTML = `<p class="text-red-500 text-center py-4">Could not load payroll runs. ${error.message || error.error || ''}</p>`;
    }
}

/**
 * Renders the payroll runs data into an HTML table.
 * @param {Array} payrollRuns - An array of payroll run objects.
 */
function renderPayrollRunsTable(payrollRuns) {
    console.log("[Render] Rendering Payroll Runs Table...");
    const container = document.getElementById('payroll-runs-list-container');
    if (!container) return;

    if (!payrollRuns || payrollRuns.length === 0) {
        container.innerHTML = '<p class="text-center py-4 text-gray-500">No payroll runs found.</p>';
        return;
    }

    let tableHtml = `
        <table class="min-w-full divide-y divide-gray-200 border border-gray-300">
            <thead class="bg-gray-50">
                <tr>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Run ID</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pay Period Start</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Pay Period End</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Payment Date</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Processed Date</th>
                    <th scope="col" class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">`;

    payrollRuns.forEach(run => {
        // Determine status color
        let statusColor = 'text-gray-600'; // Default
        if (run.Status === 'Completed') statusColor = 'text-green-600';
        else if (run.Status === 'Failed') statusColor = 'text-red-600';
        else if (run.Status === 'Processing') statusColor = 'text-blue-600';
        else if (run.Status === 'Pending') statusColor = 'text-yellow-600';

        tableHtml += `
            <tr id="pr-row-${run.PayrollID}">
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">${run.PayrollID}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">${run.PayPeriodStartDateFormatted || run.PayPeriodStartDate}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">${run.PayPeriodEndDateFormatted || run.PayPeriodEndDate}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-700">${run.PaymentDateFormatted || run.PaymentDate}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm font-semibold ${statusColor}">${run.Status}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm text-gray-500">${run.ProcessedDateFormatted || '-'}</td>
                <td class="px-4 py-3 whitespace-nowrap text-sm font-medium space-x-2">
                     <button class="text-blue-600 hover:text-blue-800 view-pr-details-btn" data-pr-id="${run.PayrollID}" title="View Details">
                        <i class="fas fa-eye"></i>
                    </button>
                    ${run.Status === 'Pending' ? `
                    <button class="text-green-600 hover:text-green-800 process-pr-btn" data-pr-id="${run.PayrollID}" title="Process Run">
                        <i class="fas fa-cogs"></i>
                    </button>
                    ` : ''}
                     </td>
            </tr>`;
    });

    tableHtml += `</tbody></table>`;
    container.innerHTML = tableHtml;

    // Add event listeners for action buttons (View Details, Process) after rendering
    attachPayrollRunActionListeners();
}

/**
 * Attaches event listeners for payroll run actions (View, Process).
 */
function attachPayrollRunActionListeners() {
    const container = document.getElementById('payroll-runs-list-container');
    if (!container) return;

    container.querySelectorAll('.view-pr-details-btn').forEach(button => {
        button.addEventListener('click', (e) => {
            const payrollId = e.currentTarget.dataset.prId;
            alert(`View Details for Payroll Run ID: ${payrollId} (Not Implemented)`);
            // TODO: Implement view details functionality (likely opens a modal or new page)
            // Requires a get_payroll_run_details.php endpoint
        });
    });

     container.querySelectorAll('.process-pr-btn').forEach(button => {
        button.addEventListener('click', (e) => {
            const payrollId = e.currentTarget.dataset.prId;
             if (confirm(`Are you sure you want to PROCESS Payroll Run ID ${payrollId}? This may take time.`)) {
                 processPayrollRun(payrollId);
             }
        });
    });
}

/**
 * Handles the submission of the create payroll run form.
 */
async function handleCreatePayrollRun(event) {
    event.preventDefault();
    const form = event.target;
    const statusSpan = document.getElementById('create-pr-status');
    const submitButton = form.querySelector('button[type="submit"]');
    if (!form || !statusSpan || !submitButton) {
        console.error("Create Payroll Run form elements missing.");
        return;
    }

    // Client-side validation
    const startDate = form.elements['pay_period_start_date'].value;
    const endDate = form.elements['pay_period_end_date'].value;
    const paymentDate = form.elements['payment_date'].value;

    if (!startDate || !endDate || !paymentDate) {
        statusSpan.textContent = 'Start Date, End Date, and Payment Date are required.';
        statusSpan.className = 'ml-4 text-sm text-red-600';
        return;
    }
    if (endDate < startDate) {
        statusSpan.textContent = 'End Date cannot be before Start Date.';
        statusSpan.className = 'ml-4 text-sm text-red-600';
        return;
    }
     if (paymentDate < endDate) {
        statusSpan.textContent = 'Payment Date cannot be before Pay Period End Date.';
        statusSpan.className = 'ml-4 text-sm text-red-600';
        return;
    }


    const formData = {
        pay_period_start_date: startDate,
        pay_period_end_date: endDate,
        payment_date: paymentDate
    };

    statusSpan.textContent = 'Creating payroll run...';
    statusSpan.className = 'ml-4 text-sm text-blue-600';
    submitButton.disabled = true;

    try {
    const result = await apiClient.apiFetch('payroll', { method: 'POST', body: { start_date: startDate, end_date: endDate, payment_date: paymentDate }, requireAuth: true });
    statusSpan.textContent = result.message || 'Payroll run created successfully!';
    statusSpan.className = 'ml-4 text-sm text-green-600';
    form.reset();
    await loadPayrollRuns();
    setTimeout(() => { if(statusSpan.textContent === (result.message || 'Payroll run created successfully!')) statusSpan.textContent = ''; }, 5000);

    } catch (error) {
        console.error('Error creating payroll run:', error);
        statusSpan.textContent = `Error: ${error.message}`;
        statusSpan.className = 'ml-4 text-sm text-red-600';
    } finally {
        submitButton.disabled = false;
    }
}

/**
 * Initiates the processing of a specific payroll run.
 * NOTE: This is a placeholder. Actual processing is complex and done server-side.
 * @param {string} payrollId - The ID of the payroll run to process.
 */
async function processPayrollRun(payrollId) {
     console.log(`[Process] Initiating processing for Payroll Run ID: ${payrollId}`);
     // Find the status span or add one dynamically if needed
     const statusSpan = document.getElementById('create-pr-status'); // Reuse or create specific one
     if(statusSpan) {
         statusSpan.textContent = `Processing Payroll Run ${payrollId}... Please wait.`;
         statusSpan.className = 'ml-4 text-sm text-blue-600';
     } else {
         alert(`Processing Payroll Run ${payrollId}... Please wait.`);
     }

     // Disable process buttons while one is running?
     document.querySelectorAll('.process-pr-btn').forEach(btn => btn.disabled = true);


    try {
        // This endpoint would handle the complex logic server-side
    const result = await apiClient.apiFetch(`payroll/${payrollId}`, { method: 'PUT', body: { Status: 'Processing' }, requireAuth: true });
    const message = result.message || `Payroll Run ${payrollId} processed successfully.`;
         if(statusSpan) {
            statusSpan.textContent = message;
            statusSpan.className = 'ml-4 text-sm text-green-600';
        } else {
            alert(message);
        }
        await loadPayrollRuns(); // Refresh the list to show updated status

         if(statusSpan) {
            setTimeout(() => { if(statusSpan.textContent === message) statusSpan.textContent = ''; }, 7000);
        }


    } catch (error) {
        console.error(`Error processing payroll run ${payrollId}:`, error);
        const errorMsg = `Error processing run ${payrollId}: ${error.message}`;
         if(statusSpan) {
            statusSpan.textContent = errorMsg;
            statusSpan.className = 'ml-4 text-sm text-red-600';
        } else {
            alert(errorMsg);
        }
    } finally {
         // Re-enable process buttons
         document.querySelectorAll('.process-pr-btn').forEach(btn => btn.disabled = false);
    }
}


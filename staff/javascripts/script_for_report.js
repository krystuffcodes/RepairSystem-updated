    // API base relative to staff pages
    const API_BASE_URL = '../backend/api/';
        const CUSTOMER_APPLIANCE_API_URL = API_BASE_URL + 'customer_appliance_api.php';
        const PARTS_API_URL = API_BASE_URL + 'parts_api.php';
        const SERVICES_API_URL = API_BASE_URL + 'service_api.php';
        const STAFF_API_URL = API_BASE_URL + 'staff_api.php';
        const SERVICE_PRICE_API_URL = API_BASE_URL + 'service_price_api.php';

        // Global state for form selections (so we don't rely on hidden selects)
        window.formState = {
            selectedCustomerId: null,
            selectedCustomerName: '',
            selectedApplianceId: null,
            selectedApplianceName: '',
            selectedDateIn: ''
        };

        // Helper to consistently hide the Service Report List modal and cleanup
        function hideServiceReportListModal() {
            const $modal = $('#serviceReportListModal');
            $modal.modal('hide');
            setTimeout(() => {
                $('.modal-backdrop').remove();
                $('body').removeClass('modal-open');
            }, 120);
        }

        // Basic debounce helper to reduce frequent filtering calls
        function debounce(fn, wait) {
            let t;
            return function() {
                const args = arguments;
                const ctx = this;
                clearTimeout(t);
                t = setTimeout(() => fn.apply(ctx, args), wait);
            };
        }

        $(document).ready(function() {
            initializeServiceReport();
            calculateTotals();

            // Load service reports on page load to check for pending reports
            loadServiceReportsOnInit();

            $(".xp-menubar").on('click', function() {
                $('#sidebar').toggleClass('active');
                $('#content').toggleClass('active');
            });

            $(".xp-menubar,.body-overlay").on('click', function() {
                $('#sidebar,.body-overlay').toggleClass('show-nav');
            });

            // Handle close button for service report list modal (custom and default variants)
            $(document).on('click', '.close-modal-report', function(e) {
                e.preventDefault();
                hideServiceReportListModal();
            });

            // Also catch default bootstrap close buttons or any element inside the modal
            // that uses the typical `.close` class or `data-dismiss="modal"` attribute.
            $(document).on('click', '#serviceReportListModal .modal-header .close, #serviceReportListModal .close, #serviceReportListModal [data-dismiss="modal"]', function(e) {
                e.preventDefault();
                hideServiceReportListModal();
            });

            // Use event delegation for edit and delete buttons to prevent duplication
            // Hide the service report list modal with smooth fade transition when an action is clicked
            $(document).on('click', '.edit-report', function(e) {
                e.preventDefault();
                const reportId = $(this).data('id');
                const $modal = $('#serviceReportListModal');
                
                // Smooth fade transition
                $modal.find('.modal-content').fadeOut(300, function() {
                    $modal.modal('hide');
                    setTimeout(() => { 
                        $('.modal-backdrop').remove(); 
                        $('body').removeClass('modal-open'); 
                    }, 100);
                    loadReportForEditing(reportId);
                });
            });

            $(document).on('click', '.delete-report', function(e) {
                e.preventDefault();
                const reportId = $(this).data('id');
                const $modal = $('#serviceReportListModal');
                
                // Smooth fade transition
                $modal.find('.modal-content').fadeOut(300, function() {
                    $modal.modal('hide');
                    setTimeout(() => { 
                        $('.modal-backdrop').remove(); 
                        $('body').removeClass('modal-open'); 
                    }, 100);
                    deleteReport(reportId);
                });
            });

            // Print report button in list - hide list then load and show print modal
            $(document).on('click', '.print-report', async function(e) {
                e.preventDefault();
                const reportId = $(this).data('id');
                if (!reportId) return;
                const $modal = $('#serviceReportListModal');
                
                // Smooth fade transition
                $modal.find('.modal-content').fadeOut(300, async function() {
                    $modal.modal('hide');
                    setTimeout(() => { 
                        $('.modal-backdrop').remove(); 
                        $('body').removeClass('modal-open'); 
                    }, 100);
                    // Directly trigger print after loading the report
                    await renderPrintModal(reportId);
                    // Auto trigger print
                    setTimeout(() => {
                        window.print();
                    }, 500);
                });
            });

            // Print button inside modal
            $(document).on('click', '#print-report-btn', function() {
                window.print();
            });
        });

        // New function to load service reports on page initialization
        async function loadServiceReportsOnInit() {
            try {
                const response = await callServiceAPI('getAll');
                if (response.success && response.data) {
                    updateBadgeStatus(response.data);
                }
            } catch (error) {
                console.error("Failed to load service reports on init: ", error);
            }
        }

        // Function to update badge status based on reports data
        function updateBadgeStatus(reports) {
            let hasPendingReports = false;

            if (reports && reports.length > 0) {
                reports.forEach(report => {
                    if (report.status === 'Pending') {
                        hasPendingReports = true;
                    }
                });
            }

            const $badge = $('#report-badge');
            if (hasPendingReports) {
                $badge.show().addClass('blink-badge');
                console.log('Badge shown - Pending reports found'); // Debug log
            } else {
                $badge.hide().removeClass('blink-badge');
                console.log('Badge hidden - No pending reports'); // Debug log
            }
        }

        async function initializeServiceReport() {
            try {
                await loadServicePrices();
                bindEventHandlers();
                initCustomerSearch();
                await loadInitialData();
            } catch (error) {
                console.error("Initialization failed: ", error);
                showAlert('danger', 'Failed to initialize application: ' + error.message);
            }
        }

        function bindEventHandlers() {
            $('#serviceReportForm').on('submit', handleFormSubmit);

            $(document).on('click', '#add-part', function() {
                addPartRow();
            });
            $(document).on('click', '.remove-part', removePartRow);

            $(document).on('change', '.part-select', function() {
                const partId = $(this).val();
                if (partId && isPartAlreadyUsed(partId, $(this))) {
                    showAlert('warning', 'This part is already added/included in the service form');
                    $(this).val('');
                    return;
                }
                updatePartsDetails($(this).closest('.parts-row'));
                calculateTotals();
            });

            $(document).on('input', '.quantity-input', function() {
                updatePartsDetails($(this).closest('.parts-row'));
            });

            $('#installation, #repair, #cleaning, #checkup').on('change', calculateTotals);
            $('#labor-amount').on('input', calculateTotals);
            $('#pullout-delivery').on('input', calculateTotals);

            // Update formState when date-in changes
            $('#date-in').on('change', function() {
                window.formState.selectedDateIn = $(this).val() || '';
                console.log('Date-in updated, formState:', window.formState);
            });

            $('#cancel-button').click(resetForm);

            $('#serviceReportListModal').on('show.bs.modal', loadServiceReports);

            // search input for service reports (debounced)
            $('#service-report-search').on('input', debounce(function() {
                filterServiceReports($(this).val());
            }, 250));

            // status filter for the list modal
            $(document).on('change', '#service-report-status', function() {
                filterServiceReports($('#service-report-search').val());
            });

            $('select[name="status"]').on('change', function() {
                const reportId = $('#report_id').val();
                if (reportId) {
                    updateSubmitButton($(this).val(), reportId);
                }
            });

            // Ensure modal closes properly when clicking close button or backdrop
            $('#serviceReportListModal').on('hidden.bs.modal', function() {
                $('body').removeClass('modal-open');
                $('.modal-backdrop').remove();
                // Clear search input and re-render full list
                $('#service-report-search').val('');
                // reset status select to all and render full list
                $('#service-report-status').val('');
                filterServiceReports('');
            });
        }

        async function callServiceAPI(action, data = null, id = null) {
            try {
                const url = `${SERVICES_API_URL}?action=${action}${id ? `&id=${id}` : ''}`;

                switch (action) {
                    case 'create':
                        return await createService(url, data);
                    case 'update':
                        return await updateService(url, data);
                    case 'delete':
                        return await deleteService(url);
                    case 'getAll':
                        return await fetchService(url);
                    case 'getById':
                        return await fetchService(url);
                    default:
                        throw new Error(`Unknown action: ${action}`);
                }
            } catch (error) {
                console.error(`${action} Error: `, error);
                throw new Error(error.responseJSON?.message || error.statusText || 'API request failed');
            }
        }

        async function createService(url, data) {
            try {
                console.log('Creating service with data:', data);
                console.log('Customer name:', data.customer_name);
                console.log('Appliance name:', data.appliance_name);
                console.log('Date in:', data.date_in);
                console.log('Status:', data.status);
                console.log('Stringified payload:', JSON.stringify(data));
                const response = await $.ajax({
                    url: url,
                    method: 'POST',
                    dataType: 'json',
                    contentType: 'application/json',
                    data: JSON.stringify(data),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                console.log('Create service response:', response);
                return validateResponse(response, 'create');
            } catch (error) {
                console.error('Create service error:', error);
                console.error('Error status:', error.status);
                console.error('Error response:', error.responseText);
                // Try to extract a JSON message from the responseText if available
                let errorMessage = 'Failed to create service report';
                try {
                    if (error && error.responseJSON && error.responseJSON.message) {
                        errorMessage = error.responseJSON.message;
                    } else if (error && error.responseText) {
                        const parsed = JSON.parse(error.responseText);
                        if (parsed && parsed.message) errorMessage = parsed.message;
                    } else if (error && error.statusText) {
                        errorMessage = error.statusText;
                    }
                } catch (parseErr) {
                    console.warn('Failed to parse error responseText', parseErr);
                }
                throw new Error(errorMessage);
            }
        }

        async function updateService(url, data) {
            try {
                console.log('Updating service with data:', data);
                const response = await $.ajax({
                    url: url,
                    method: 'PUT',
                    dataType: 'json',
                    contentType: 'application/json',
                    data: JSON.stringify(data),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                console.log('Update service response:', response);
                return validateResponse(response, 'update');
            } catch (error) {
                console.error('Update service error:', error);
                let errorMessage = 'Failed to update service report';
                try {
                    if (error && error.responseJSON && error.responseJSON.message) {
                        errorMessage = error.responseJSON.message;
                    } else if (error && error.responseText) {
                        const parsed = JSON.parse(error.responseText);
                        if (parsed && parsed.message) errorMessage = parsed.message;
                    } else if (error && error.statusText) {
                        errorMessage = error.statusText;
                    }
                } catch (parseErr) {
                    console.warn('Failed to parse error responseText', parseErr);
                }
                throw new Error(errorMessage);
            }
        }

        async function deleteService(url) {
            try {
                console.log('Deleting service');
                const response = await $.ajax({
                    url: url,
                    method: 'DELETE',
                    dataType: 'json',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                console.log('Delete service response:', response);
                return validateResponse(response, 'delete');
            } catch (error) {
                console.error('Delete service error:', error);
                let errorMessage = 'Failed to delete service report';
                try {
                    if (error && error.responseJSON && error.responseJSON.message) {
                        errorMessage = error.responseJSON.message;
                    } else if (error && error.responseText) {
                        const parsed = JSON.parse(error.responseText);
                        if (parsed && parsed.message) errorMessage = parsed.message;
                    } else if (error && error.statusText) {
                        errorMessage = error.statusText;
                    }
                } catch (parseErr) {
                    console.warn('Failed to parse error responseText', parseErr);
                }
                throw new Error(errorMessage);
            }
        }

        async function fetchService(url) {
            try {
                console.log('Fetching service from:', url);
                const response = await $.ajax({
                    url: url,
                    method: 'GET',
                    dataType: 'json',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                console.log('Fetch service response:', response);
                return validateResponse(response, 'fetch');
            } catch (error) {
                console.error('Fetch service error:', error);
                let errorMessage = 'Failed to fetch service reports';
                try {
                    if (error && error.responseJSON && error.responseJSON.message) {
                        errorMessage = error.responseJSON.message;
                    } else if (error && error.responseText) {
                        const parsed = JSON.parse(error.responseText);
                        if (parsed && parsed.message) errorMessage = parsed.message;
                    } else if (error && error.statusText) {
                        errorMessage = error.statusText;
                    }
                } catch (parseErr) {
                    console.warn('Failed to parse error responseText', parseErr);
                }
                throw new Error(errorMessage);
            }
        }

        function validateResponse(response, action) {
            if (!response.success) {
                const defaultMessages = {
                    'create': 'Failed to create service report',
                    'update': 'Failed to update service report',
                    'delete': 'Failed to delete service report',
                    'fetch': 'Failed to fetch service reports\' data'
                };
                throw new Error(response.message || defaultMessages[action] || 'Operation failed');
            }
            return response;
        }

        function gatherFormData() {
            const formatDateForPHP = (dateStr) => {
                if (!dateStr) return '';
                return new Date(dateStr).toISOString().split('T')[0];
            };

            // Debug the select elements
            const $customerSelect = $('#customer-select');
            const $applianceSelect = $('#appliance-select');
            const allCustomerOptions = $customerSelect.find('option').map((i, opt) => `[${opt.value}] ${opt.text}`).get();
            console.log('===== DEBUG GATHER FORM DATA =====');
            console.log('Customer select - Total options:', allCustomerOptions.length);
            console.log('Customer select - All options:', allCustomerOptions);
            console.log('Customer select - Current value:', $customerSelect.val());
            
            const $selectedOption = $customerSelect.find('option:selected');
            console.log('Customer selected option count:', $selectedOption.length);
            console.log('Customer selected option value:', $selectedOption.val());
            console.log('Customer selected option text:', $selectedOption.text());

            // Get customer name robustly: prefer text of option matching selected value
            let customerName = '';
            try {
                const customerVal = $customerSelect.val();
                if (customerVal) {
                    const $optByVal = $customerSelect.find(`option[value="${customerVal}"]`);
                    if ($optByVal.length) {
                        customerName = $optByVal.text().trim();
                        console.log('1. Customer name resolved via value lookup:', customerName, 'value:', customerVal);
                    }
                }
            } catch (err) {
                console.warn('Error resolving customer name by value:', err);
            }

            // If still empty, try selected option text directly
            if (!customerName) {
                customerName = $customerSelect.find('option:selected').text() || '';
                if (customerName === 'Select Customer') customerName = '';
                if (customerName) console.log('2. Customer name from selected option fallback:', customerName);
            }

            // Final fallback: use formState
            if (!customerName && window.formState && window.formState.selectedCustomerName) {
                customerName = window.formState.selectedCustomerName;
                console.log('3. Using formState customer name fallback:', customerName);
            }
            
            if (!customerName) {
                console.log('WARNING: Customer name is still empty after all attempts!');
            }
            
            // Get appliance name robustly: prefer text of option matching selected value
            let applianceName = '';
            try {
                const applianceVal = $applianceSelect.val();
                if (applianceVal) {
                    const $applOpt = $applianceSelect.find(`option[value="${applianceVal}"]`);
                    if ($applOpt.length) {
                        applianceName = $applOpt.text().trim();
                        console.log('1. Appliance name resolved via value lookup:', applianceName, 'value:', applianceVal);
                    }
                }
            } catch (err) {
                console.warn('Error resolving appliance name by value:', err);
            }

            // If still empty, try selected option text directly
            if (!applianceName) {
                applianceName = $applianceSelect.find('option:selected').text() || '';
                if (applianceName === 'Select Appliance') applianceName = '';
                if (applianceName) console.log('2. Appliance name from selected option fallback:', applianceName);
            }

            // Final fallback: use formState
            if (!applianceName && window.formState && window.formState.selectedApplianceName) {
                applianceName = window.formState.selectedApplianceName;
                console.log('3. Using formState appliance name fallback:', applianceName);
            }
            
            const dateInValue = $('#date-in').val() || '';
            
            console.log('DEBUG gatherFormData:', {
                customerName,
                applianceName,
                dateInValue,
                formState: window.formState,
                customerSelectVal: $customerSelect.val(),
                applianceSelectVal: $applianceSelect.val()
            });

            // Gather location (ensure at least one is selected, default to 'shop' if none)
            let location = [];
            if ($('#shop').is(':checked')) location.push('shop');
            if ($('#field').is(':checked')) location.push('field');
            if ($('#out_wty').is(':checked')) location.push('out_wty');
            if (location.length === 0) location.push('shop'); // Default to shop

            // Gather service types (ensure at least one, default to 'repair' if none)
            let service_types = [];
            if ($('#installation').is(':checked')) service_types.push('installation');
            if ($('#repair').is(':checked')) service_types.push('repair');
            if ($('#cleaning').is(':checked')) service_types.push('cleaning');
            if ($('#checkup').is(':checked')) service_types.push('checkup');
            if (service_types.length === 0) service_types.push('repair'); // Default to repair

            const formData = {
                customer_name: customerName,
                appliance_name: applianceName,
                date_in: formatDateForPHP(dateInValue),
                status: $('select[name="status"]').val(),
                dealer: $('input[name="dealer"]').val(),
                findings: $('input[name="findings"]').val(),
                remarks: $('input[name="remarks"]').val(),
                location: location,
                service_types: service_types,
                date_repaired: formatDateForPHP($('input[name="date_repaired"]').val()),
                date_delivered: formatDateForPHP($('input[name="date_delivered"]').val()),
                complaint: $('textarea[name="complaint"]').val(),
                labor: parseFloat($('#labor-amount').val()) || 0,
                pullout_delivery: parseFloat($('#pullout-delivery').val()) || 0,
                parts_total_charge: parseFloat($('input[name="parts_charge"]').val()) || 0,
                service_charge: parseFloat($('#total-serviceCharge').val()) || 0,
                total_amount: parseFloat($('#total-amount-2').val()) || 0,
                receptionist: $('#receptionist-select option:selected').text(),
                manager: $('#manager-select option:selected').text(),
                technician: $('#technician-select option:selected').text(),
                released_by: $('#released-by-select option:selected').text(),
                parts: []
            };

            // Gather parts
            $('.parts-row').each(function() {
                const $partSelect = $(this).find('.part-select');
                const $selectedOption = $partSelect.find('option:selected');
                const partText = $selectedOption.text();
                const partName = partText.split(' (')[0];
                const quantity = parseFloat($(this).find('.quantity-input').val()) || 0;
                const unitPrice = parseFloat($selectedOption.data('price')) || 0;

                if (partName && quantity > 0) {
                    formData.parts.push({
                        part_name: partName,
                        quantity: quantity,
                        unit_price: unitPrice,
                        parts_total: quantity * unitPrice,
                        part_id: $partSelect.val()
                    });
                }
            });

            const reportId = $('#report_id').val();
            if (reportId) {
                formData.report_id = reportId;
            }

            // Include optional date keys only if set
            const dopVal = formatDateForPHP($('input[name="dop"]').val());
            const datePulledVal = formatDateForPHP($('input[name="date_pulled_out"]').val());
            if (dopVal) formData.dop = dopVal;
            if (datePulledVal) formData.date_pulled_out = datePulledVal;
            
            return formData;
        }

        //create process
        async function handleFormSubmit(e) {
            const $submitBtn = $('#submit-report-btn');
            if ($submitBtn.text().includes('Create Transaction')) {
                e.preventDefault();
                const reportId = $('#report_id').val();
                if (reportId) {
                    await createTransactionFromReport(reportId);
                }
                return;
            }

            e.preventDefault();

            // Try to auto-resolve customer selection if user typed but didn't click suggestion
            try {
                const typedCustomer = ($('#customer-search').val() || '').toString().trim();
                const $customerSelect = $('#customer-select');
                if (typedCustomer && $customerSelect.length && !$customerSelect.val() && Array.isArray(window.customersList)) {
                    // exact match first
                    let match = window.customersList.find(c => (c.name || '').toLowerCase() === typedCustomer.toLowerCase());
                    if (!match) {
                        // startsWith match
                        match = window.customersList.find(c => (c.name || '').toLowerCase().startsWith(typedCustomer.toLowerCase()));
                    }
                    if (match) {
                        // ensure option exists and set it
                        let $opt = $customerSelect.find(`option[value="${match.id}"]`);
                        if ($opt.length === 0) {
                            $opt = $(`<option></option>`).val(match.id).text(match.name);
                            $customerSelect.append($opt);
                        }
                        console.log('Auto-resolved customer to:', match.name, match.id);
                        $customerSelect.val(match.id).trigger('change');
                    }
                }

                // If appliance select has a selected option with data-date-in but date-in is empty, fill it
                const $applianceSelect = $('#appliance-select');
                const selectedAppl = $applianceSelect.find('option:selected');
                const dateInFromOption = selectedAppl.attr('data-date-in') || selectedAppl.data('dateIn') || '';
                if (dateInFromOption && !$('#date-in').val()) {
                    $('#date-in').val(formatDateForInput(dateInFromOption));
                }
            } catch (resolveErr) {
                console.warn('Auto-resolve customer/appliance failed', resolveErr);
            }

            const formData = gatherFormData();
            console.log('Form data before validation:', formData);
            console.log('Customer name value:', formData.customer_name);
            console.log('Customer name is empty?', !formData.customer_name);
            console.log('Customer name trim empty?', !formData.customer_name || formData.customer_name.trim() === '');
            
            let missingFields = [];
            if (!formData.customer_name || formData.customer_name.trim() === '' || formData.customer_name === 'Select Customer') {
                missingFields.push('Customer');
            }
            if (!formData.appliance_name || formData.appliance_name.trim() === '' || formData.appliance_name === 'Select Appliance') {
                missingFields.push('Appliance');
            }
            if (!formData.date_in) {
                missingFields.push('Date In');
            }
            if (!formData.status || formData.status.trim() === '' || formData.status === 'Select Status') {
                missingFields.push('Status');
            }
            
            if (missingFields.length > 0) {
                console.error('Missing fields detected:', missingFields);
                console.error('Form data at validation:', formData);
                   const fieldList = missingFields.join(', ');
                   showAlert('danger', `⚠️ REQUIRED: Please select/fill: ${fieldList}`);
                return;
            }

            try {
                showLoading(true);

                let action = 'create';
                const reportId = $('#report_id').val();

                if(reportId) {
                    action = 'update';
                }

                console.log('Submitting form data: ', formData);
                console.log('Form data keys:', Object.keys(formData));
                console.log('Form data customer_name:', formData.customer_name);
                console.log('Form data length:', Object.keys(formData).length);
                
                const response = await callServiceAPI(action, formData, reportId);
                
                if(!response || !response.success) {
                    throw new Error(response?.message || 'Failed to process report');
                }

                if(!reportId && response.data?.report_id) {
                    $('#report_id').val(response.data.report_id);
                }

                updateSubmitButton(formData.status, $('#report_id').val());

                let successMessage = reportId ? 'Report updated successfully' : 'Report created successfully';
                showAlert('success', successMessage);

                await loadDropdown('parts', '.part-select');
                
                if (!reportId) {
                    resetForm();
                }
                
                // Refresh the badge status after form submission
                await loadServiceReportsOnInit();
                await loadServiceReports();

            } catch (error) {
                console.error('Form submission error:', error);
                showAlert('danger', error.message || 'An error occurred while processing the service report');
            } finally {
                showLoading(false);
            }
        }

        //read process 
        async function loadInitialData() {
            try {
                console.log('=== LOADING INITIAL DATA ===');
                console.log('Loading customers and parts...');
                
                await Promise.all([
                    loadDropdown('customer', '.customer-select'),
                    loadDropdown('parts', '.part-select')
                ]);
                
                console.log('Customers loaded. Customer select options:', $('#customer-select').find('option').length);

                //load specific staff dropdowns
                await Promise.all([
                    ...$('.staff-select').map(function() {
                        return loadDropdown('staff', $(this));
                    }).get()
                ]);

               console.log('=== FORM LOADED SUCCESSFULLY ===');
               console.log('Customer dropdown has', $('#customer-select').find('option').length, 'options');
               console.log('Instructions: 1) Select a Customer, 2) Select an Appliance, 3) Fill other required fields, 4) Submit');
                const $applianceSelect = $('.appliance-select');
                $applianceSelect.empty()
                    .append($('<option></option>').val('').text('Select Appliance'));

                $(document).on('change', '#customer-select', function() {
                    console.log('Customer select changed, value:', $(this).val(), 'text:', $(this).find('option:selected').text());
                    const customerId = $(this).val();
                    const customerName = $(this).find('option:selected').text() || '';
                    
                    // Update formState with selected customer
                    window.formState.selectedCustomerId = customerId;
                    window.formState.selectedCustomerName = customerName;
                    console.log('Updated formState with customer:', window.formState);
                    
                    if (customerId) {
                        console.log('Loading appliances for customer:', customerId);
                        loadDropdown('appliance', '.appliance-select', customerId);
                    } else {
                        console.log('Customer cleared, clearing appliances');
                        $applianceSelect.empty()
                            .append($('<option></option>').val('').text('Select Appliance'));
                        // Clear date-in when no appliance/customer selected
                        $('#date-in').val('');
                        // Clear formState when customer is cleared
                        window.formState.selectedCustomerId = null;
                        window.formState.selectedCustomerName = '';
                    }
                });

                // Handle appliance selection to update formState
                $(document).on('change', '#appliance-select', function() {
                    const applianceId = $(this).val();
                    const selectedOption = $(this).find('option:selected');
                    const applianceName = selectedOption.text() || '';
                    const dateIn = selectedOption.attr('data-date-in') || selectedOption.data('dateIn') || '';

                    console.log('Appliance select changed:', {
                        applianceId,
                        applianceName,
                        dateIn,
                        selectedData: selectedOption.data()
                    });

                    // Save to formState
                    window.formState.selectedApplianceId = applianceId;
                    window.formState.selectedApplianceName = applianceName;
                    window.formState.selectedDateIn = dateIn;

                    // Update the date-in input if date_in is available from appliance
                    if (dateIn) {
                        $('#date-in').val(dateIn);
                    }

                    console.log('Updated formState:', window.formState);
                });

                $applianceSelect.on('mousedown', function() {
                    if (!$('#customer-select').val()) {
                        showAlert('warning', 'Please select a customer first');
                        $(this).val('');
                        return false;
                    }
                });

            } catch (error) {
                console.error("Failed to load initial data:", error);
                showAlert('danger', 'Failed to load initial data');
            }
        }

        async function loadDropdown(type, selector, customerId = null) {
            try {
                let url, transformFn, dependent = false;
                let currentValues = new Map(); // Store current selections
                
                // Save current selections if reloading parts dropdown
                if (type === 'parts') {
                    $(selector).each(function() {
                        const $select = $(this);
                        if ($select.val()) {
                            currentValues.set($select.closest('.parts-row').index(), {
                                value: $select.val(),
                                quantity: $select.closest('.parts-row').find('.quantity-input').val()
                            });
                        }
                    });
                }

                switch (type) {
                    case 'customer':
                        url = CUSTOMER_APPLIANCE_API_URL + '?action=getAllCustomers&page=1&itemsPerPage=1000';

                        transformFn = item => ({
                            value: item.customer_id,
                            text: `${item.FullName}`
                        });
                        break;

                    case 'appliance':
                        if (customerId) {
                            //dependent appliance looking for specific customer
                            url = CUSTOMER_APPLIANCE_API_URL + `?action=getAppliancesByCustomerId&customerId=${customerId}`;

                            transformFn = item => ({
                                value: item.appliance_id,
                                text: `${item.brand} - ${item.serial_no || item.model_no || 'No Serial'} (${item.category || 'No Model'})`,
                                date_in: item.date_in || '',
                                serial: item.serial_no || ''
                            });
                            dependent = true;
                        } else {
                            //independent appliance loading(show all appliance)
                            url = CUSTOMER_APPLIANCE_API_URL + '?action=getAllAppliances';

                            transformFn = item => ({
                                value: item.appliance_id,
                                text: `${item.brand} - ${item.serial_no || item.model_no || 'No Serial'} (${item.category})`,
                                date_in: item.date_in || '',
                                serial: item.serial_no || ''
                            });
                        }
                        break;

                    case 'parts':
                        url = PARTS_API_URL + '?action=getAllParts&page=1&itemsPerPage=1000';

                        transformFn = item => ({
                            value: item.part_id,
                            text: `${item.part_no} (${item.quantity_stock} available - ₱${item.price})`,
                            price: item.price
                        });
                        break;

                    case 'staff':
                        const role = $(selector).data('role');
                        if (role) {
                            url = STAFF_API_URL + `?action=getStaffsByRole&role=${encodeURIComponent(role)}`;
                        } else {
                            url = STAFF_API_URL + '?action=getAllStaffs&page=1&itemsPerPage=1000';
                        }

                        transformFn = item => ({
                            value: item.staff_id,
                            text: `${item.full_name || item.username} (${item.role})`
                        });
                        break;

                    default:
                        return;
                }

                const response = await $.ajax({
                    url: url,
                    type: 'GET',
                    dataType: 'json'
                });

                console.log(`Response for ${type}:`, response);
                console.log(`Response success?`, response?.success);
                console.log(`Response data?`, response?.data);

                if (response?.success && response.data) {
                    let items = [];
                    // Normalize various payload shapes into a flat array
                    if (Array.isArray(response.data)) {
                        items = response.data;
                    } else if (Array.isArray(response.data.data)) {
                        items = response.data.data;
                    } else if (Array.isArray(response.data.customers)) {
                        items = response.data.customers;
                        console.log(`Loaded ${items.length} customers from response.data.customers`);
                    } else if (Array.isArray(response.data.parts)) {
                        items = response.data.parts;
                    } else if (Array.isArray(response.data.staffs)) {
                        items = response.data.staffs;
                    } else if (Array.isArray(response.data.services)) {
                        items = response.data.services;
                    }
                    console.log(`Loading ${type} items. Total items: ${items.length}`);
                    console.log(`First item sample:`, items[0]);
                    
                    const $dropdowns = $(selector);
                    console.log(`Found ${$dropdowns.length} dropdowns for selector: ${selector}`);

                    $dropdowns.each(function() {
                        const $dropdown = $(this);
                        const defaultText = dependent ? 'Select an Appliance' : `Select ${type}`;

                        $dropdown.empty().append(`<option value="">${defaultText}</option>`);

                        if (items.length === 0 && dependent) {
                            $dropdown.append('<option value="">No appliances found</option>');
                        } else {
                            items.forEach(item => {
                                const optionData = transformFn(item);
                                const $option = $('<option></option>')
                                    .val(optionData.value)
                                    .text(optionData.text)
                                    .attr('data-date-in', optionData.date_in || '')
                                    .attr('data-serial', optionData.serial || '');

                                if (optionData.price) {
                                    $option.data('price', optionData.price);
                                }
                                $dropdown.append($option);
                            });
                            console.log(`Added ${items.length} options to ${type} dropdown for selector ${selector}`);

                        // Store customers list globally for the search input
                        if (type === 'customer') {
                            // Populate global customers list but do not show suggestions immediately
                            // Suggestions will be shown only after the user taps/clicks the input.
                            let customers = items.map(item => ({
                                id: item.customer_id,
                                name: item.FullName
                            }));
                            // Dedupe names to avoid duplicates in suggestions
                            const seen = new Set();
                            window.customersList = customers.filter(c => {
                                const key = (c.name || '').toLowerCase().trim();
                                if (seen.has(key)) return false;
                                seen.add(key);
                                return true;
                            });
                        }
                        }

                        //enable/disable based on dependency and content
                        if (type === 'appliance') {
                            $dropdown.prop('disabled', !customerId || items.length === 0);
                            if (items.length === 1 && customerId) {
                                const onlyOptionVal = $dropdown.find('option:not([value=""])').first().val();
                                if (onlyOptionVal) {
                                    $dropdown.val(onlyOptionVal).trigger('change');
                                }
                            }
                            $dropdown.off('change.autoDate').on('change.autoDate', function() {
                                const selected = $(this).find('option:selected');
                                const dateIn = selected.attr('data-date-in') || selected.data('dateIn') || '';
                                if (dateIn) {
                                    $('#date-in').val(formatDateForInput(dateIn));
                                } else {
                                    $('#date-in').val('');
                                }
                            });
                        }
                    });
                }

            } catch (error) {
                console.error(`Error loading ${type}:`, error);
                console.error(`Error response status:`, error.status);
                console.error(`Error response text:`, error.responseText);
                const $dropdowns = $(selector);
                $dropdowns.empty().append(`<option value="">Error loading ${type}</option>`).prop('disabled', true);

                if (type === 'appliance') {
                    showAlert('danger', `Error loading appliances: ${error.statusText || 'Unknown error'}`);
                }
            }
        }

        //update process
        async function loadReportForEditing(reportId) {
            try {
                showLoading(true, '#serviceReportForm');

                const response = await callServiceAPI('getById', null, reportId);
                if (!response.success || !response.data) {
                    throw new Error(response.message || 'Report not found');
                }

                const report = response.data;
                console.log('Report Data: ', report);

                resetForm();

                //basic report info
                $('#report_id').val(report.report_id);
                $('#date-in').val(report.date_in);
                $('select[name="status"]').val(report.status);
                $('input[name="dealer"]').val(report.dealer || '');
                $('input[name="dop"]').val(report.dop || '');
                $('input[name="date_pulled_out"]').val(report.date_pulled_out || '');
                $('input[name="findings"]').val(report.findings || '');
                $('input[name="remarks"]').val(report.remarks || '');
                $('textarea[name="complaint"]').val(report.complaint || '');

                updateSubmitButton(report.status, reportId);

                //location checkbox
                const location = Array.isArray(report.location) ? report.location : JSON.parse(report.location || '[]');
                $('#shop').prop('checked', location.includes('shop'));
                $('#field').prop('checked', location.includes('field'));
                $('#out_wty').prop('checked', location.includes('out_wty'));

                //service checkbox
                const serviceTypes = Array.isArray(report.service_types) ? report.service_types : JSON.parse(report.service_types || '[]');
                $('#installation').prop('checked', serviceTypes.includes('installation'));
                $('#repair').prop('checked', serviceTypes.includes('repair'));
                $('#cleaning').prop('checked', serviceTypes.includes('cleaning'));
                $('#checkup').prop('checked', serviceTypes.includes('checkup'));

                //numeric fields
                $('#labor-amount').val(report.labor || '0.00');
                $('#pullout-delivery').val(report.pullout_delivery || '0.00');
                $('#total-serviceCharge').val(report.service_charge || '0.00');
                $('#total-amount').val(report.total_amount || '0.00');
                $('#total-amount-2').val(report.total_amount || '0.00');
                $('input[name="parts_charge"]').val(report.parts_total_charge || '0.00');

                //dates
                $('input[name="date_repaired"]').val(report.date_repaired || '');
                $('input[name="date_delivered"]').val(report.date_delivered || '');

                await Promise.all([
                    loadDropdown('customer', '.customer-select'),
                    loadDropdown('parts', '.part-select'),
                    loadDropdown('staff', '#receptionist-select'),
                    loadDropdown('staff', '#manager-select'),
                    loadDropdown('staff', '#technician-select'),
                    loadDropdown('staff', '#released-by-select')
                ]);

                //small delay to ensure DOM is update
                await new Promise(resolve => setTimeout(resolve, 100));

                setDropdownValue('#customer-select', report.customer_name);
                $('#customer-select').trigger('change');
                if (report.customer_name) {
                    const customerVal = $('#customer-select').val();
                    if (customerVal) {
                        await loadDropdown('appliance', '.appliance-select', customerVal);
                        await new Promise(resolve => setTimeout(resolve, 100));
                    }
                }
                setDropdownValue('#appliance-select', report.appliance_name);
                // Ensure we trigger change so date-in populates from selected option
                $('#appliance-select').trigger('change');

                setDropdownValue('#receptionist-select', report.receptionist);
                setDropdownValue('#manager-select', report.manager);
                setDropdownValue('#technician-select', report.technician);
                setDropdownValue('#released-by-select', report.released_by);

                if (report.parts && report.parts.length > 0) {
                    $('#parts-container .parts-row:not(:first)').remove();

                    for (let i = 0; i < report.parts.length; i++) {
                        const part = report.parts[i];
                        let $row;

                        if (i === 0) {
                            $row = $('#parts-container .parts-row:first');
                        } else {
                            $row = addPartRow();
                        }

                        const $select = $row.find('.part-select');
                        let $option = $select.find(`option:contains("${part.part_name}")`);

                        if ($option.length === 0) {
                            $select.append(`
                                <option value="custom" selected data-price="${part.unit_price}">
                                    ${part.part_name} - ₱${part.unit_price}
                                </option>`);
                            $option = $select.find('option:last');
                        }

                        $option.prop('selected', true);
                        $row.find('.quantity-input').val(part.quantity);
                        $row.find('.amount-input').val((part.quantity * part.unit_price).toFixed(2));
                    }
                }

                calculateTotals();
                
                // FIX: Properly close the modal with better handling
                $('#serviceReportListModal').modal('hide');
                // Remove backdrop and reset body classes
                setTimeout(() => {
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                }, 500);

            } catch (error) {
                console.error('Error loading report:', error);
                showAlert('danger', 'Failed to load report: ' + error.message);
            } finally {
                showLoading(false, '#serviceReportForm');
            }
        }

        function setDropdownValue(selector, value) {
            if (!value) return;

            const $dropdown = $(selector);
            const $options = $dropdown.find('option');

            //method 1: exact match
            let $option = $options.filter((i, el) => $(el).text().trim() === value.trim());

            //method 2: check if the store values contains username and the option contains that username
            if ($option.length === 0) {
                const usernameMatch = value.match(/^([^\s(]+)/);
                if (usernameMatch) {
                    const username = usernameMatch[1];
                    $option = $options.filter((i, el) => {
                        const optionText = $(el).text();
                        return optionText.includes(username) && optionText !== 'Select staff';
                    });
                }
            }

            //method 3: partial match - if stored value contains part of option text or vice versa
            if ($option.length === 0) {
                $option = $options.filter((i, el) => {
                    const optionText = $(el).text().trim();
                    return optionText.includes(value) || value.includes(optionText);
                });
            }

            //method 4: try to match by extracting the core name/username
            if ($option.length === 0) {
                const cleanValue = value.replace(/\s*\([^]*\)\s*/g, '').trim();
                $option = $options.filter((i, el) => {
                    const cleanOptionText = $(el).text().replace(/\s*\([^)]*\)\s*/g, '').trim();
                    return cleanOptionText.includes(cleanValue) || cleanValue.includes(cleanOptionText);
                });
            }

            //method 5: match by data-serial attribute if present
            if ($option.length === 0) {
                const valLower = value.trim().toLowerCase();
                $option = $options.filter((i, el) => {
                    const ds = ($(el).attr('data-serial') || $(el).data('serial') || '').toString().toLowerCase();
                    if (!ds) return false;
                    return ds === valLower || valLower.includes(ds) || ds.includes(valLower);
                });
            }

            if ($option.length > 0) {
                $option.first().prop('selected', true);
            } else {
                $dropdown.append(`<option value="custom" selected>${value}</option>`);
            }
        }

        /* Customer search helpers */
        function initCustomerSearch() {
            const $input = $('#customer-search');
            const $hiddenSelect = $('#customer-select');
            const $suggestions = $('#customer-suggestions');

            if (!$input.length) return;

            // Hide suggestions initially
            $suggestions.hide();

            let allowSuggestionsOnFocus = false;
            $input.on('input', function() {
                const text = $(this).val().trim();
                renderCustomerSuggestions(text);
            });

            $input.on('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    const first = $suggestions.find('.list-group-item').first();
                    if (first.length) {
                        first.trigger('click');
                    }
                }
            });

            // Only show suggestions if the user physically interacted with the input
            // Support pointerdown, touchstart and mousedown for broad device coverage
            $input.on('pointerdown touchstart mousedown', function() {
                allowSuggestionsOnFocus = true;
            });

            $input.on('focus', function() {
                if (allowSuggestionsOnFocus) {
                    renderCustomerSuggestions('');
                }
                allowSuggestionsOnFocus = false;
            });

            // When the hidden select changes (e.g. set programmatically), update the visible input
            $hiddenSelect.on('change', function() {
                const text = $(this).find('option:selected').text();
                if (text && text !== 'Select Customer') {
                    $input.val(text);
                }
            });

            // hide suggestions on blur, small delay for clicks
            $input.on('blur', function() {
                setTimeout(() => $suggestions.hide(), 150);
            });

            // clicking outside hides suggestions
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#customer-search, #customer-suggestions').length) {
                    $suggestions.hide();
                }
            });
        }

        function renderCustomerSuggestions(filterText) {
            const $suggestions = $('#customer-suggestions');
            const $input = $('#customer-search');

            if (!window.customersList || window.customersList.length === 0) {
                $suggestions.hide();
                return;
            }

            const text = (filterText || '').toLowerCase();
            let matches;
            if (!text) {
                matches = window.customersList.slice(0, 20);
            } else {
                // Start-with matching (first letter or full word start)
                matches = window.customersList.filter(c => c.name && c.name.toLowerCase().startsWith(text)).slice(0, 20);
            }

            if (matches.length === 0) {
                $suggestions.hide();
                return;
            }

            $suggestions.empty();
            matches.forEach(c => {
                const $item = $(`<button type="button" class="list-group-item list-group-item-action">${c.name}</button>`);
                $item.data('id', c.id);
                $item.on('click', function() {
                    setCustomerFromSuggestion(c.id, c.name);
                });
                $suggestions.append($item);
            });

            // Position suggestion container under the input and set its size using the wrapper
            const $wrapper = $input.closest('.customer-search-wrapper');
            const wrapperWidth = $wrapper.length ? $wrapper.innerWidth() : $input.outerWidth();
            $suggestions.css({
                display: 'block',
                width: wrapperWidth + 'px'
            });
        }

        function setCustomerFromSuggestion(id, name) {
            console.log('setCustomerFromSuggestion called with:', { id, name });
            const $hiddenSelect = $('#customer-select');
            const $input = $('#customer-search');
            $input.val(name);

            // Store in global form state
            window.formState.selectedCustomerId = id;
            window.formState.selectedCustomerName = name;
            console.log('Saved to formState:', window.formState);

            // Also update the hidden select for compatibility with other code
            let $option = $hiddenSelect.find(`option[value="${id}"]`);
            if ($option.length === 0) {
                $option = $(`<option value="${id}">${name}</option>`);
                $hiddenSelect.append($option);
            }
            $hiddenSelect.val(id).trigger('change');
            $('#customer-suggestions').hide();
        }

        //delete process
        async function deleteReport(reportId) {
            if (!confirm('Are you sure you want to delete this report?\nThis action cannot be undone')) return;

            try {
                showLoading(true, '#serviceReportListModal .modal-body');
                await callServiceAPI('delete', null, reportId);
                showAlert('success', 'Report deleted successfully');
                
                // Refresh badge status after deletion
                await loadServiceReportsOnInit();
                await loadServiceReports();
                
            } catch (error) {
                showAlert('danger', error.message);
            } finally {
                showLoading(false, '#serviceReportListModal .modal-body');
            }
        }

        function renderServiceReportsRows(reports) {
            const $tbody = $('#serviceReportListModal tbody').empty();
            if (!reports || !Array.isArray(reports)) return;

            reports.forEach(report => {
                let serviceTypes = 'N/A';
                if (report.service_types && Array.isArray(report.service_types)) {
                    serviceTypes = report.service_types.join(', ');
                } else if (typeof report.service_types === 'string') {
                    serviceTypes = report.service_types;
                }

                const dateIn = report.date_in ? new Date(report.date_in).toLocaleDateString() : 'N/A';
                const statusBadge = report.status === 'Completed' ?
                    '<span class="badge badge-success">Completed</span>' :
                    '<span class="badge badge-warning">Pending</span>';

                const totalAmount = parseFloat(report.total_amount || 0);

                $tbody.append(`
                    <tr>
                        <td>${report.report_id}</td>
                        <td>${report.customer_name}</td>
                        <td>${report.appliance_name}</td>
                        <td>${serviceTypes}</td>
                        <td>${dateIn}</td>
                        <td>${statusBadge}</td>
                        <td>${totalAmount.toFixed(2)}</td>
                        <td class="actions-col">
                            <a href="#" class="edit-report" data-id="${report.report_id}">
                                <i class="material-icons text-primary">edit</i>
                            </a>
                            <a href="#" class="delete-report" data-id="${report.report_id}">
                                <i class="material-icons text-danger">delete</i>
                            </a>
                            <a href="#" class="print-report" data-id="${report.report_id}" title="Print Report">
                                <i class="material-icons text-secondary">print</i>
                            </a>
                        </td>
                    </tr>
                `);
            });
        }

        async function loadServiceReports() {
            try {
                showLoading(true, '#serviceReportListModal .modal-body');

                const response = await callServiceAPI('getAll');

                if (!response.success || !response.data) {
                    throw new Error(response.message || 'No service reports found');
                }

                // Store the reports data globally for local filtering/search
                window.serviceReportsData = response.data;
                // Render using current filters (search text + status)
                filterServiceReports($('#service-report-search').val());
                

                // Update badge status
                updateBadgeStatus(response.data);

                // REMOVED DUPLICATE EVENT HANDLERS - Using event delegation instead

            } catch (error) {
                console.error("Failed to load service reports: ", error);
                showAlert('danger', 'Failed to load service reports: ' + error.message);
            } finally {
                showLoading(false, '#serviceReportListModal .modal-body');
            }
        }
        function filterServiceReports(query) {
            query = (query || '').toString().toLowerCase().trim();
            const statusFilter = ($('#service-report-status').length ? $('#service-report-status').val() : '').toString();

            if (!window.serviceReportsData || !Array.isArray(window.serviceReportsData)) return;

            const filtered = window.serviceReportsData.filter(report => {
                // status filter
                if (statusFilter) {
                    if ((report.status || '').toString() !== statusFilter) return false;
                }

                if (!query) return true;

                const q = query;
                const matchesId = report.report_id && report.report_id.toString().includes(q);
                const matchesCustomer = report.customer_name && report.customer_name.toLowerCase().includes(q);
                const matchesAppliance = report.appliance_name && report.appliance_name.toLowerCase().includes(q);
                const serviceTypes = (report.service_types && Array.isArray(report.service_types)) ? report.service_types.join(', ') : (report.service_types || '');
                const matchesService = (serviceTypes || '').toLowerCase().includes(q);

                return matchesId || matchesCustomer || matchesAppliance || matchesService;
            });

            renderServiceReportsRows(filtered);
        }

        function updateSubmitButton(status, reportId = '') {
            const $submitBtn = $('#submit-report-btn');

            if(status === 'Completed' && reportId) {
                $submitBtn.html('Submit Report');
                $submitBtn.removeClass('btn-primary').addClass('btn-success');

                $submitBtn.off('click').on('click', async function(e) {
                    e.preventDefault();
                    try {
                        const formData = gatherFormData();
                        await callServiceAPI('update', formData, reportId);
                        await createTransactionFromReport(reportId);
                    } catch (err) {
                        console.error('Error updating report and creating transaction: ', err);
                        showAlert('danger', 'Failed to update report or create transaction: ' + (err.message || err));
                    }
                });
            } else {
                const buttonText = reportId ? 'Update Report' : 'Create Report';
                $submitBtn.html(buttonText);
                $submitBtn.removeClass('btn-success').addClass('btn-primary');
            }
            
        }

        function addPartRow(partData = {}) {
            let newRow;

            if ($('#parts-container .parts-row').length === 0) {
                newRow = $(`
                    <div class="row mb-2 align-items-center g-2 parts-row">
                        <div class="col-md-3 pe-1">
                            <select class="form-control part-select" name="part_used[]">
                                <option value="">Select Part</option>
                            </select>
                        </div>
                        <div class="col-md-2 px-1">
                            <input type="number" class="form-control quantity-input" name="quantity[]" placeholder="Quantity" min="1">
                        </div>
                        <div class="col-md-2 px-1">
                            <input type="number" class="form-control amount-input" name="amount[]" placeholder="Amount" readonly>
                        </div>
                        <div class="col-md-1 px-1 d-flex justify-content-end align-items-center">
                            <button type="button" class="btn btn-danger remove-part d-flex align-items-center justify-content-center" style="display:none;">
                                <span class="material-icons" style="font-size: 1.2em;">delete</span>
                            </button>
                        </div>
                    </div>
                `);
            } else {
                newRow = $('.parts-row').first().clone(true, true);
                newRow.find('select').val('');
                newRow.find('input').val('');
                newRow.find('.remove-part').show();

                $('#parts-container').append(newRow);
                updateRemoveButtons();

                return newRow;
            }
        }

        function removePartRow() {
            if ($('.parts-row').length > 1) {
                $(this).closest('.parts-row').remove();
                updateRemoveButtons();
                calculateTotals();
            }
        }

        function updateRemoveButtons() {
            const rows = $('#parts-container .parts-row');
            rows.find('.remove-part').toggle(rows.length > 1);
        }

        function updatePartsDetails(row) {
            const selectedOption = row.find('.part-select option:selected');
            const price = parseFloat(selectedOption.data('price')) || 0;
            const quantity = parseFloat(row.find('.quantity-input').val()) || 0;
            const amount = price * quantity;

            // Get the selected part's current stock from API
            if (selectedOption.val() && quantity > 0) {
                const partNo = selectedOption.text().split(' (')[0];

                // Get real-time stock level
                $.ajax({
                    url: PARTS_API_URL + '?action=getPartsById&id=' + selectedOption.val(),
                    type: 'GET',
                    success: function(response) {
                        if (response.success && response.data) {
                            const availableStock = response.data.quantity_stock;

                            if (quantity > availableStock) {
                                showAlert('warning', `Quantity exceeds available stock (${availableStock})`);
                                row.find('.quantity-input').val(availableStock);
                                row.find('.amount-input').val((price * availableStock).toFixed(2));
                            } else {
                                row.find('.amount-input').val(amount.toFixed(2));
                            }
                            calculateTotals();
                        }
                    },
                    error: function() {
                        showAlert('danger', 'Failed to verify stock quantity');
                    }
                });
            } else {
                row.find('.amount-input').val(amount.toFixed(2));
                calculateTotals();
            }
        }

        function calculateTotals() {
            const laborCharge = parseFloat($('#labor-amount').val()) || 0;
            const deliveryCharge = parseFloat($('#pullout-delivery').val()) || 0;
            const serviceCharge = calculateServiceCharge();
            const partsTotal = calculatePartsTotal();

            const grandTotal = (
                parseFloat(laborCharge.toFixed(2)) +
                parseFloat(deliveryCharge.toFixed(2)) +
                parseFloat(serviceCharge.toFixed(2)) +
                parseFloat(partsTotal.toFixed(2))
            ).toFixed(2);

            $('input[name="parts_charge"]').val(partsTotal.toFixed(2));
            $('#total-serviceCharge').val(serviceCharge.toFixed(2));
            $('#total-amount').val(grandTotal);
            $('#total-amount-2').val(grandTotal);
        }

        // use a global servicePrices to avoid duplicate declarations across staff scripts
        window.servicePrices = window.servicePrices || {};

        async function loadServicePrices() {
            try {
                const response = await $.ajax({
                    url: SERVICE_PRICE_API_URL + '?action=getAllForFrontend',
                    type: 'GET',
                    dataType: 'json'
                });

                if (response && response.success && Array.isArray(response.data)) {
                    // response.data is array of services {service_id, service_name, service_price}
                    window.servicePrices = {};
                    window.servicePricesList = response.data.map(s => {
                        // normalize and keep a map for price lookup
                        window.servicePrices[s.service_name] = parseFloat(s.service_price);
                        return s;
                    });
                    renderServiceTypeCheckboxes(window.servicePricesList);
                    console.log('Loaded service prices list: ', window.servicePricesList);
                } else {
                    // fallback: legacy object mapping
                    window.servicePrices = {
                        installation: 500,
                        repair: 300,
                        cleaning: 200,
                        checkup: 150
                    };
                    window.servicePricesList = Object.keys(window.servicePrices).map(k => ({ service_name: k, service_price: window.servicePrices[k] }));
                    renderServiceTypeCheckboxes(window.servicePricesList);
                    console.warn('Using fallback service prices');
                }

            } catch (error) {
                console.error('Failed to load service prices: ', error);

                // fallback to default prices 
                window.servicePrices = {
                    installation: 500,
                    repair: 300,
                    cleaning: 200,
                    checkup: 150
                };
                window.servicePricesList = Object.keys(window.servicePrices).map(k => ({ service_name: k, service_price: window.servicePrices[k] }));
                renderServiceTypeCheckboxes(window.servicePricesList);
                console.warn('Using fallback service prices');
            }
        }

        function calculateServiceCharge() {
            let total = 0;

            // Iterate through all checked service type checkboxes and sum their prices
            $('.service-type-checkbox:checked').each(function() {
                const price = parseFloat($(this).data('price')) || 0;
                total += price;
            });

            return parseFloat(total.toFixed(2));
        }

        function calculatePartsTotal() {
            let total = 0;

            $('.parts-row').each(function() {
                const quantity = parseFloat($(this).find('.quantity-input').val()) || 0;
                const price = parseFloat($(this).find('.part-select option:selected').data('price')) || 0;
                const amount = quantity * price;

                $(this).find('.amount-input').val(amount.toFixed(2));
                total += amount;
            });

            return parseFloat(total.toFixed(2));
        }

        function formatDateForInput(dateString) {
            if (!dateString) return '';

            if (dateString.includes('-')) {
                return dateString;
            }

            const parts = dateString.split('/');
            if (parts.length === 3) {
                return `${parts[2]}-${parts[0].padStart(2, '0')}-${parts[1].padStart(2, '0')}`;
            }
            return dateString;
        }

        function resetForm() {
            $('#serviceReportForm')[0].reset();

            const $firstRow = $('#parts-container .parts-row').first();
            $firstRow.find('select').val('');
            $firstRow.find('input').val('');
            if ($('#parts-container .parts-row').length > 1) {
                $('#parts-container .parts-row:not(:first)').remove();
            }

            $('.staff-select').val('');
            $('input[type="checkbox"]').prop('checked', false);
            $('#report_id').val('');
            // Clear visible customer search input
            $('#customer-search').val('');
        
            updateSubmitButton('', '');
            calculateTotals();
            updateRemoveButtons();
        }

        function showAlert(type, message) {
            $('.alert-notification').remove();
            const alertHtml = `
                <div class="alert-notification alert alert-${type} alert-disimissible fade show">
                    ${message}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            `;
            $('body').prepend(alertHtml);
            setTimeout(() => $('.alert-notification').alert('close'), 5000);
        }

        function showLoading(show, element) {
            const $element = $(element);
            if (show) {
                $element.addClass('loading');
                $element.append('<div class="loading-overlay"><div class="spinner-border"></div></div>');
            } else {
                $element.removeClass('loading');
                $element.find('.loading-overlay').remove();
            }
        }

        async function validateForm() {
            if (!$('#customer-select').val()) {
                showAlert('danger', 'Please select a customer');
                return false;
            }

            if (!$('#appliance-select').val()) {
                showAlert('danger', 'Please select an appliance');
                return false;
            }

            if (!$('select[name="status"]').val()) {
                showAlert('danger', 'Please select a status');
                return false;
            }

            // Validate parts quantities
            const partsValid = await validatePartsQuantities();
            if (!partsValid) {
                return false;
            }

            //if(!validateDates()) return false; - UNCOMMENT IF WORKIN NA

            return true;
        }

        async function validatePartsQuantities() {
            const parts = [];
            let isValid = true;

            $('.parts-row').each(function() {
                const partSelect = $(this).find('.part-select option:selected');
                const quantity = parseFloat($(this).find('.quantity-input').val()) || 0;

                if (partSelect.val() && quantity > 0) {
                    parts.push({
                        id: partSelect.val(),
                        quantity: quantity,
                        name: partSelect.text().split(' (')[0]
                    });
                }
            });

            if (parts.length === 0) {
                return true;
            }

            // Verify all parts quantities
            for (const part of parts) {
                try {
                    const response = await $.ajax({
                        url: PARTS_API_URL + '?action=getPartsById&id=' + part.id,
                        type: 'GET'
                    });

                    if (response.success && response.data) {
                        const availableStock = response.data.quantity_stock;
                        if (part.quantity > availableStock) {
                            showAlert('danger', `Insufficient stock for ${part.name}. Available: ${availableStock}`);
                            isValid = false;
                            break;
                        }
                    }
                } catch (error) {
                    showAlert('danger', `Failed to verify stock for ${part.name}`);
                    isValid = false;
                    break;
                }
            }

            return isValid;
        }

        function isPartAlreadyUsed(partId, currentSelect) {
            let used = false;
            $('.part-select').each(function() {
                if (this === currentSelect[0]) return true;

                if ($(this).val() === partId && $(this).val() !== '') {
                    used = true;
                    return false;
                }
            });
            return used;
        }

        async function createTransactionFromReport(reportId) {
            try {
                showLoading(true, '#serviceReportListModal .modal-body');

                // First check if transaction already exists
                const checkResponse = await $.ajax({
                    url: '../backend/api/transaction_api.php?action=getAll',
                    method: 'GET',
                    dataType: 'json'
                });

                if (checkResponse.success && checkResponse.data) {
                    const transactionsList = Array.isArray(checkResponse.data) ? checkResponse.data : (checkResponse.data.transactions || []);
                    const existingTransaction = transactionsList.find(t => t.report_id == reportId || t.report_id == reportId || t.reportId == reportId);
                    if (existingTransaction) {
                        showAlert('info', 'Transaction already exists for this report');
                        return;
                    }
                }

                // first get the complete report data 
                const response = await callServiceAPI('getById', null, reportId);
                if(!response.success || !response.data) {
                    throw new Error('Failed to load report data');
                }

                const reportData = response.data;

                const totalAmt = parseFloat(reportData.total_amount || 0);
                if (typeof totalAmt !== 'number' || isNaN(totalAmt)) {
                    throw new Error('Invalid total amount for this report');
                }
                const transactionData = {
                    report_id: reportId,
                    customer_name: reportData.customer_name,
                    appliance_name: reportData.appliance_name,
                    total_amount: totalAmt,
                    service_types: reportData.service_types || [],
                    payment_status: 'Pending' 
                };

                const transactionResponse = await $.ajax({
                    url: '../backend/api/transaction_api.php?action=createFromReport',
                    method: 'POST',
                    dataType: 'json',
                    contentType: 'application/json',
                    data: JSON.stringify(transactionData),
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if(!transactionResponse.success) {
                    const errMsg = transactionResponse.message || 'Failed to create transaction';
                    throw new Error(errMsg);
                }

                    showAlert('success', 'Transaction created successfully');
                    // Update button after successful creation
                    updateSubmitButton('Completed', reportId);

            } catch (error) {
                console.error('Error creating transaction:', error);
                if (error && error.responseJSON && error.responseJSON.message) {
                    showAlert('danger', `Failed to create transaction: ${error.responseJSON.message}`);
                } else {
                    showAlert('danger', 'Failed to create transaction: ' + (error.message || error));
                }
                console.error('Transaction creation payload:', JSON.stringify(transactionData));
            } finally {
                showLoading(false, '#serviceReportListModal .modal-body');
            }
        }

/* ===== STAFF SEARCH HELPERS (merged from admin JS, controlled) ===== */

// Initialize staff searchable inputs if present. Inputs should have class `staff-input`
// and a `data-target` attribute pointing to the matching select (e.g. `#receptionist-select`).
function initStaffSearch() {
    $('.staff-input').each(function() {
        const $input = $(this);
        const inputId = $input.attr('id');
        const targetSelector = $input.data('target');
        const suggestionsId = inputId ? `${inputId}-suggestions` : null;

        // create suggestions container if missing
        if (suggestionsId && $(`#${suggestionsId}`).length === 0) {
            const $parent = $input.parent();
            if ($parent.length && $parent.css('position') === 'static') {
                $parent.css('position', 'relative');
            }
            $parent.append(`<div id="${suggestionsId}" class="staff-suggestions list-group" style="display:none; max-height:220px; overflow-y:auto; position:absolute; left:0; top:calc(100% + 6px); z-index:2000; width:100%;"></div>`);
        }

        let allowSuggestionsOnFocus = false;

        $input.on('input', function() {
            const v = $(this).val().trim();
            renderStaffSuggestions($input, v);
        });

        $input.on('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                const $first = $(`#${suggestionsId}`).find('.list-group-item').first();
                if ($first.length) $first.trigger('click');
            }
        });

        $input.on('pointerdown touchstart mousedown', function() { allowSuggestionsOnFocus = true; });
        $input.on('focus', function() {
            if (allowSuggestionsOnFocus) renderStaffSuggestions($input, '');
            allowSuggestionsOnFocus = false;
        });

        $input.on('blur', function() { setTimeout(() => $(`#${suggestionsId}`).hide(), 150); });

        // clicking outside hides suggestions
        $(document).on('click', function(e) {
            if (!$(e.target).closest($input.selector + `, #${suggestionsId}`).length) {
                $(`#${suggestionsId}`).hide();
            }
        });
    });

    // keep selects -> inputs synced when programmatically set
    $(document).on('change', '.staff-select', function() {
        const selId = $(this).attr('id') || '';
        const inputSelector = `#${selId.replace('-select', '-input')}`;
        const text = $(this).find('option:selected').text() || '';
        if ($(inputSelector).length) $(inputSelector).val(text);
    });
}

// Render suggestions for a staff input. Uses window.staffLists[selectId] if populated,
// otherwise reads options from the corresponding select element.
function renderStaffSuggestions($input, filterText) {
    const inputId = $input.attr('id');
    if (!inputId) return;
    const suggestionsId = `${inputId}-suggestions`;
    const targetSelector = $input.data('target');
    const selectId = targetSelector ? $(targetSelector).attr('id') : inputId.replace('-input', '-select');

    let staffArray = [];
    if (window.staffLists && window.staffLists[selectId]) {
        staffArray = window.staffLists[selectId];
    } else if (selectId && $(`#${selectId}`).length) {
        $(`#${selectId} option`).each(function() {
            const t = $(this).text();
            const v = $(this).val();
            if (v) staffArray.push({ id: v, text: t });
        });
    }

    const $container = $(`#${suggestionsId}`);
    if (!staffArray || staffArray.length === 0) {
        $container.hide();
        return;
    }

    const q = (filterText || '').toLowerCase().trim();
    let matches = [];
    if (!q) {
        matches = staffArray.slice(0, 30);
    } else {
        // prioritize startsWith then includes
        matches = staffArray.filter(s => (s.text || '').toLowerCase().startsWith(q));
        if (matches.length === 0) matches = staffArray.filter(s => (s.text || '').toLowerCase().includes(q));
        matches = matches.slice(0, 30);
    }

    if (matches.length === 0) {
        $container.hide();
        return;
    }

    $container.empty();
    matches.forEach(s => {
        const $btn = $(`<button type="button" class="list-group-item list-group-item-action">${s.text}</button>`);
        $btn.data('id', s.id);
        $btn.on('click', function() {
            setStaffFromSuggestion(s.id, s.text, `#${inputId}`, `#${selectId}`);
        });
        $container.append($btn);
    });

    // position/width
    const $parent = $input.closest('.staff-input-wrapper');
    const width = $parent.length ? $parent.innerWidth() : $input.outerWidth();
    $container.css({ display: 'block', width: width + 'px' });
}

// When a suggestion is clicked, set the visible input and the hidden select correctly.
function setStaffFromSuggestion(id, text, inputSelector, selectSelector) {
    const $input = $(inputSelector);
    const $select = $(selectSelector);
    if ($input.length) $input.val(text);

    if ($select.length) {
        // try to find option by value
        let $option = $select.find(`option[value="${id}"]`);
        if ($option.length === 0) {
            // try to match by text
            $option = $select.find('option').filter(function() { return $(this).text().trim() === text.trim(); });
        }
        if ($option.length === 0) {
            // append custom option
            $select.append($(`<option></option>`).val(id).text(text));
            $select.val(id).trigger('change');
        } else {
            $option.first().prop('selected', true);
            $select.trigger('change');
        }
    }

    const suggestionsId = inputSelector.replace('#', '') + '-suggestions';
    $(`#${suggestionsId}`).hide();
}

// Initialize staff search on load (controlled merge)
try { 
    initStaffSearch(); 
    initStaffSelectSearch();
} catch (e) { /* ignore init errors */ }

// Initialize searchable suggestions attached to existing <select class="staff-select"> elements.
// This avoids changing HTML: a small search box + suggestion list is created and anchored under the select.
function initStaffSelectSearch() {
    $('.staff-select').each(function() {
        const $select = $(this);
        const selectId = $select.attr('id') || '';
        const wrapper = $select.parent();
        const suggestionsId = selectId ? `${selectId}-select-suggestions` : null;

        if (!suggestionsId) return;

        // create suggestions container with internal search input
        if (!$(`#${suggestionsId}`).length) {
            if (wrapper.length && wrapper.css('position') === 'static') wrapper.css('position', 'relative');
            const html = `
                <div id="${suggestionsId}" class="staff-select-suggestions list-group" style="display:none; max-height:260px; overflow:auto; position:absolute; left:0; top:calc(100% + 6px); z-index:2000; width:100%; background:#fff; border:1px solid #ddd; border-radius:6px; padding:6px;">
                    <input type="text" class="form-control form-control-sm staff-select-search" placeholder="Search..." style="margin-bottom:6px;">
                    <div class="staff-select-list"></div>
                </div>
            `;
            wrapper.append(html);
        }

        const $container = $(`#${suggestionsId}`);
        const $search = $container.find('.staff-select-search');
        const $list = $container.find('.staff-select-list');

        // populate from window.staffLists if available, otherwise from options
        function getStaffArray() {
            if (window.staffLists && window.staffLists[selectId]) return window.staffLists[selectId];
            const arr = [];
            $select.find('option').each(function() {
                const v = $(this).val();
                const t = $(this).text();
                if (v) arr.push({ id: v, text: t });
            });
            return arr;
        }

        function renderList(filter) {
            const all = getStaffArray();
            const q = (filter || '').toLowerCase().trim();
            let matches = [];
            if (!q) matches = all.slice(0, 50);
            else {
                matches = all.filter(s => (s.text || '').toLowerCase().includes(q));
            }
            $list.empty();
            if (matches.length === 0) {
                $list.append('<div class="list-group-item">No results</div>');
                return;
            }
            matches.forEach(s => {
                const $item = $(`<button type="button" class="list-group-item list-group-item-action">${s.text}</button>`);
                $item.data('id', s.id);
                $item.on('click', function() {
                    // set select value and trigger change
                    $select.val(s.id).trigger('change');
                    $container.hide();
                });
                $list.append($item);
            });
        }

        // show on click or focus
        $select.on('click focus', function() {
            renderList('');
            $container.show();
            $search.val('');
            $search.focus();
        });

        $search.on('input', function() { renderList($(this).val()); });

        // hide when clicking outside
        $(document).on('click', function(e) {
            if (!$(e.target).closest($select.add($container)).length) {
                $container.hide();
            }
        });
    });
}

// Render service type checkboxes dynamically from service prices
function renderServiceTypeCheckboxes(services) {
    const $container = $('#service-type-checkboxes');
    $container.empty();
    if (!Array.isArray(services) || services.length === 0) {
        $container.html('<div class="text-muted">No service types available</div>');
        return;
    }
    services.forEach(service => {
        const name = service.service_name || '';
        const label = name.charAt(0).toUpperCase() + name.slice(1);
        const price = parseFloat(service.service_price || 0) || 0;
        const id = `service-type-${name.replace(/\s+/g, '-')}`;
        const checkboxHtml = `
            <div class="form-check mr-3 mb-1">
                <input class="form-check-input service-type-checkbox" type="checkbox" id="${id}" value="${name}" data-price="${price}">
                <label class="form-check-label" for="${id}">${label}</label>
            </div>`;
        $container.append(checkboxHtml);
    });
    // Bind checkbox change event to recalculate totals
    $('.service-type-checkbox').off('change').on('change', function() {
        calculateTotals();
    });
}

// Render report into the Transaction/Print modal, then show it.
// This is a lightweight implementation used by the list-print flow.
async function renderPrintModal(reportId) {
    try {
        if (!reportId) throw new Error('Invalid report id');
        const response = await callServiceAPI('getById', null, reportId);
        if (!response || !response.success || !response.data) {
            throw new Error(response?.message || 'Report not found');
        }
        const r = response.data;

        // Basic fields in the transaction/print modal
        $('#update_report_id').val(r.report_id || '');
        $('#customer-field').val(r.customer_name || '');
        $('#appliance-field').val(r.appliance_name || '');
        $('#date-in-field').val(r.date_in || '');
        $('#status-field').val(r.status || '');
        $('#transactionForm input[name="dealer"]').val(r.dealer || '');
        $('#transactionForm input[name="dop"]').val(r.dop || '');
        $('#transactionForm input[name="date_pulled_out"]').val(r.date_pulled_out || '');
        $('#findings-field').val(r.findings || '');
        $('#transactionForm input[name="remarks"]').val(r.remarks || '');
        $('#transactionForm input[name="complaint"]').val(r.complaint || '');

        // Totals
        $('#total-serviceCharge-display').val(Number(r.service_charge || 0).toFixed(2));
        $('#transactionForm input[name="total_amount"]').val(Number(r.total_amount || 0).toFixed(2));

        // Parts: render simple list inside the modal's parts container if available
        try {
            const $partsContainer = $('#transactionForm #parts-container');
            $partsContainer.find('.parts-row:not(:first)').remove();
            if (Array.isArray(r.parts) && r.parts.length > 0) {
                // Ensure first row exists
                const $first = $partsContainer.find('.parts-row').first();
                $first.find('input').each(function() { $(this).val(''); });
                for (let i = 0; i < r.parts.length; i++) {
                    const p = r.parts[i];
                    if (i === 0) {
                        $first.find('input[name="part_name[]"]').val(p.part_name || '');
                        $first.find('input[name="quantity[]"]').val(p.quantity || '');
                        $first.find('input[name="part_amount[]"]').val((p.parts_total || (p.quantity * p.unit_price) || 0).toFixed ? (p.parts_total || (p.quantity * p.unit_price)).toFixed(2) : p.parts_total || '');
                    } else {
                        const $clone = $first.clone(true, true);
                        $clone.find('input[name="part_name[]"]').val(p.part_name || '');
                        $clone.find('input[name="quantity[]"]').val(p.quantity || '');
                        $clone.find('input[name="part_amount[]"]').val((p.parts_total || (p.quantity * p.unit_price) || 0).toFixed ? (p.parts_total || (p.quantity * p.unit_price)).toFixed(2) : p.parts_total || '');
                        $partsContainer.append($clone);
                    }
                }
            }
        } catch (e) {
            // Non-fatal if parts fail to render
            console.warn('Failed to render parts into print modal', e);
        }

        // Show modal
        $('#transactionFormModal').modal('show');
        return true;
    } catch (err) {
        console.error('renderPrintModal error:', err);
        showAlert('danger', 'Failed to prepare print view: ' + (err.message || err));
        return false;
    }
}

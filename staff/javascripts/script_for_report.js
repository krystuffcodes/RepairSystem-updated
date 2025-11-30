    // API base relative to staff pages
    const API_BASE_URL = '../backend/api/';
        const CUSTOMER_APPLIANCE_API_URL = API_BASE_URL + 'customer_appliance_api.php';
        const PARTS_API_URL = API_BASE_URL + 'parts_api.php';
        const SERVICES_API_URL = API_BASE_URL + 'service_api.php';
        const STAFF_API_URL = API_BASE_URL + 'staff_api.php';
        const SERVICE_PRICE_API_URL = API_BASE_URL + 'service_price_api.php';

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

            // Use event delegation for edit and delete buttons to prevent duplication
            $(document).on('click', '.edit-report', function(e) {
                e.preventDefault();
                const reportId = $(this).data('id');
                loadReportForEditing(reportId);
            });

            $(document).on('click', '.delete-report', function(e) {
                e.preventDefault();
                const reportId = $(this).data('id');
                deleteReport(reportId);
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

            $('#cancel-button').click(resetForm);

            $('#serviceReportListModal').on('show.bs.modal', loadServiceReports);

            // search input for service reports
            $('#service-report-search').on('input', function() {
                filterServiceReports($(this).val());
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
                const errorMessage = error.responseJSON?.message || error.statusText || 'Failed to create service report';
                throw new Error(errorMessage);
            }
        }

        async function updateService(url, data) {
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
            return validateResponse(response, 'update');
        }

        async function deleteService(url) {
            const response = await $.ajax({
                url: url,
                method: 'DELETE',
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            return validateResponse(response, 'delete');
        }

        async function fetchService(url) {
            const response = await $.ajax({
                url: url,
                method: 'GET',
                dataType: 'json',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            return validateResponse(response, 'fetch');
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
                if (!dateStr) return null;
                return new Date(dateStr).toISOString().split('T')[0];
            };

            const formData = {
                //service report 
                customer_name: $('#customer-select option:selected').text(),
                appliance_name: $('#appliance-select option:selected').text(),
                date_in: formatDateForPHP($('#date-in').val()),
                status: $('select[name="status"]').val(),
                dealer: $('input[name="dealer"]').val(),
                dop: formatDateForPHP($('input[name="dop"]').val()),
                date_pulled_out: formatDateForPHP($('input[name="date_pulled_out"]').val()),
                findings: $('input[name="findings"]').val(),
                remarks: $('input[name="remarks"]').val(),
                location: [],

                //service detials
                service_types: [],
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

            if ($('#shop').is(':checked')) formData.location.push('shop');
            if ($('#field').is(':checked')) formData.location.push('field');
            if ($('#out_wty').is(':checked')) formData.location.push('out_wty');

            if ($('#installation').is(':checked')) formData.service_types.push('installation');
            if ($('#repair').is(':checked')) formData.service_types.push('repair');
            if ($('#cleaning').is(':checked')) formData.service_types.push('cleaning');
            if ($('#checkup').is(':checked')) formData.service_types.push('checkup');

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
                        part_id: $partSelect.val() // Add the part ID from the select element
                    });
                }
            });

            const reportId = $('#report_id').val();
            if (reportId) {
                formData.report_id = reportId;
            }
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
            const isValid = await validateForm();
            if (!isValid) return;

            try {
                showLoading(true);
                const formData = gatherFormData();

                let action = 'create';
                const reportId = $('#report_id').val();

                if(reportId) {
                    action = 'update';
                }

                console.log('Submitting form data: ', formData);
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
                await Promise.all([
                    loadDropdown('customer', '.customer-select'),
                    loadDropdown('parts', '.part-select')
                ]);

                //load specific staff dropdowns
                await Promise.all([
                    ...$('.staff-select').map(function() {
                        return loadDropdown('staff', $(this));
                    }).get()
                ]);

                const $applianceSelect = $('.appliance-select');
                $applianceSelect.empty()
                    .append($('<option></option>').val('').text('Select Appliance'));

                $(document).on('change', '#customer-select', function() {
                    const customerId = $(this).val();
                    if (customerId) {
                        loadDropdown('appliance', '.appliance-select', customerId);
                    } else {
                        $applianceSelect.empty()
                            .append($('<option></option>').val('').text('Select Appliance'));
                        // Clear date-in when no appliance/customer selected
                        $('#date-in').val('');
                    }
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

                if (response?.success && response.data) {
                    let items = [];
                    // Normalize various payload shapes into a flat array
                    if (Array.isArray(response.data)) {
                        items = response.data;
                    } else if (Array.isArray(response.data.data)) {
                        items = response.data.data;
                    } else if (Array.isArray(response.data.customers)) {
                        items = response.data.customers;
                    } else if (Array.isArray(response.data.parts)) {
                        items = response.data.parts;
                    } else if (Array.isArray(response.data.staffs)) {
                        items = response.data.staffs;
                    } else if (Array.isArray(response.data.services)) {
                        items = response.data.services;
                    }
                    const $dropdowns = $(selector);

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
            const $hiddenSelect = $('#customer-select');
            const $input = $('#customer-search');
            $input.val(name);

            // If option exists, set it; otherwise add custom option
            let $option = $hiddenSelect.find(`option[value="${id}"]`);
            if ($option.length === 0) {
                $option = $(`<option></option>`).val(id).text(name);
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

                renderServiceReportsRows(window.serviceReportsData);
                

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
            if (!window.serviceReportsData || !Array.isArray(window.serviceReportsData)) return;

            if (!query) {
                renderServiceReportsRows(window.serviceReportsData);
                return;
            }

            const filtered = window.serviceReportsData.filter(report => {
                const q = query;
                return (report.report_id && report.report_id.toString().includes(q)) ||
                    (report.customer_name && report.customer_name.toLowerCase().includes(q)) ||
                    (report.appliance_name && report.appliance_name.toLowerCase().includes(q)) ||
                    (report.service_types && (Array.isArray(report.service_types) ? report.service_types.join(', ').toLowerCase().includes(q) : (report.service_types || '').toLowerCase().includes(q)));
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
                    url: SERVICE_PRICE_API_URL + '?action=getAll',
                    type: 'GET',
                    dataType: 'json'
                });

                if(response && response.success) {
                    window.servicePrices = response.data;
                    console.log('Loaded service prices: ', window.servicePrices);
                } else {
                    // fallback to default prices 
                    window.servicePrices = {
                        installation: 500,
                        repair: 300,
                        cleaning: 200,
                        checkup: 150
                    };
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
                console.warn('Using fallback service prices');
            }
        }

        function calculateServiceCharge() {
            let total = 0;

            const prices = window.servicePrices || {};
            if ($('#installation').is(':checked')) total += parseFloat(prices.installation || 0);
            if ($('#repair').is(':checked')) total += parseFloat(prices.repair || 0);
            if ($('#cleaning').is(':checked')) total += parseFloat(prices.cleaning || 0);
            if ($('#checkup').is(':checked')) total += parseFloat(prices.checkup || 0);

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

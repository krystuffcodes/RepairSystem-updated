<?php
require_once __DIR__ . '/../bootstrap.php';
session_start();
require __DIR__ . '/../backend/handlers/authHandler.php';
$auth = new AuthHandler();
$userSession = $auth->requireAuth('admin'); 
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Admin - Simple Service Report</title>
    <link rel="shortcut Icon" href="../img/Repair.png" type="image/x-icon">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/custom.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Icons" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f5f7fa;
        }

        .wrapper {
            display: flex;
            width: 100%;
            align-items: stretch;
        }

        #content {
            width: 100%;
            padding: 0;
            min-height: 100vh;
            transition: all 0.3s;
            margin-left: 250px;
        }

        .main-content {
            padding: 20px;
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.05);
            margin-bottom: 20px;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #e2e8f0;
            padding: 20px;
            border-radius: 10px 10px 0 0 !important;
        }

        .card-title {
            color: #2d3748;
            font-weight: 600;
            margin: 0;
        }

        .form-control, .form-select {
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 10px 15px;
            font-size: 14px;
        }

        .form-control:focus, .form-select:focus {
            border-color: #0066e6;
            box-shadow: 0 0 0 3px rgba(0, 102, 230, 0.1);
        }

        label {
            font-weight: 500;
            color: #4a5568;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .btn-primary {
            background-color: #0066e6;
            border: none;
            padding: 10px 30px;
            border-radius: 6px;
            font-weight: 500;
        }

        .btn-primary:hover {
            background-color: #0052cc;
        }

        .btn-secondary {
            padding: 10px 30px;
            border-radius: 6px;
            font-weight: 500;
        }

        .alert-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            animation: slideIn 0.3s ease-out;
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%);
                opacity: 0;
            }
            to {
                transform: translateX(0);
                opacity: 1;
            }
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
        }

        .table {
            margin-bottom: 0;
        }

        .table th {
            background-color: #f8f9fa;
            font-weight: 600;
            color: #2d3748;
            border-top: none;
        }

        .badge-success {
            background-color: #28a745;
        }

        .badge-warning {
            background-color: #ffc107;
        }

        .badge-primary {
            background-color: #0066e6;
        }

        .badge-danger {
            background-color: #dc3545;
        }

        .badge-secondary {
            background-color: #6c757d;
        }

        .actions-col a {
            display: inline-flex;
            align-items: center;
            margin-right: 8px;
            text-decoration: none;
            padding: 4px 8px;
            border-radius: 4px;
            transition: background-color 0.2s;
        }

        .actions-col a:hover {
            background-color: #f8f9fa;
        }

        .icon-btn {
            background: none;
            border: none;
            cursor: pointer;
            padding: 8px;
        }

        .modal-header {
            background-color: #0066e6 !important;
            color: white;
        }

        .modal-title {
            color: white !important;
        }

        .close {
            color: white !important;
            opacity: 1 !important;
        }
    </style>
</head>

<body>
    <div class="wrapper">
        <div class="body-overlay"></div>

        <!-- Sidebar -->
        <?php include __DIR__ . '/../layout/sidebar.php'; ?>

        <div id="content">
            <!-- Top Navbar -->
            <?php
            $pageTitle = 'Simple Service Report';
            $breadcrumb = 'Simple Service Report';
            include __DIR__ . '/../layout/navbar.php';
            ?>

            <!-- Main Content -->
            <div class="main-content">
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">Simple Service Report Form</h5>
                                <button type="button" class="btn icon-btn" data-toggle="modal" data-target="#serviceReportListModal">
                                    <i class="material-icons align-middle" style="font-size: 2em; color: #353b48;">list</i>
                                </button>
                            </div>
                            <div class="card-body">
                                <form id="serviceReportForm">
                                    <div class="container-fluid">
                                        <!-- Required Fields Row -->
                                        <div class="row mb-3">
                                            <div class="col-md-3">
                                                <label>Customer Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="customer_name" id="customer-name" required placeholder="Enter customer name">
                                            </div>
                                            <div class="col-md-3">
                                                <label>Appliance <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control" name="appliance_name" id="appliance-name" required placeholder="Enter appliance">
                                            </div>
                                            <div class="col-md-3">
                                                <label>Date In <span class="text-danger">*</span></label>
                                                <input type="date" class="form-control" name="date_in" id="date-in" required>
                                            </div>
                                            <div class="col-md-3">
                                                <label>Status <span class="text-danger">*</span></label>
                                                <select class="form-control" name="status" id="status" required>
                                                    <option value="">Select Status</option>
                                                    <option value="Pending">Pending</option>
                                                    <option value="Under Repair">Under Repair</option>
                                                    <option value="Unrepairable">Unrepairable</option>
                                                    <option value="Release Out">Release Out</option>
                                                    <option value="Completed">Completed</option>
                                                </select>
                                            </div>
                                        </div>

                                        <!-- Optional Fields Row -->
                                        <div class="row mb-3">
                                            <div class="col-md-4">
                                                <label>Dealer</label>
                                                <input type="text" class="form-control" name="dealer" placeholder="Enter dealer name">
                                            </div>
                                            <div class="col-md-4">
                                                <label>Findings</label>
                                                <input type="text" class="form-control" name="findings" placeholder="Enter findings">
                                            </div>
                                            <div class="col-md-4">
                                                <label>Remarks</label>
                                                <input type="text" class="form-control" name="remarks" placeholder="Enter remarks">
                                            </div>
                                        </div>

                                        <!-- Complaint Row -->
                                        <div class="row mb-3">
                                            <div class="col-md-12">
                                                <label>Complaint</label>
                                                <textarea class="form-control" name="complaint" rows="3" placeholder="Enter complaint details"></textarea>
                                            </div>
                                        </div>

                                        <!-- Buttons -->
                                        <div class="d-flex justify-content-end mt-4" style="gap: 1rem;">
                                            <button type="button" class="btn btn-secondary px-4" id="cancel-button">Cancel</button>
                                            <input type="hidden" name="report_id" id="report-id">
                                            <button type="submit" class="btn btn-primary px-4" id="submit-report-btn">Create Report</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Service Report List Modal -->
                <div class="modal fade" id="serviceReportListModal" tabindex="-1" aria-labelledby="serviceReportListModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-xl modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h4 class="modal-title w-100 text-center" id="serviceReportListModalLabel">Service Report List</h4>
                                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                    <span aria-hidden="true">&times;</span>
                                </button>
                            </div>
                            <div class="modal-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Report ID</th>
                                                <th>Customer</th>
                                                <th>Appliance</th>
                                                <th>Date In</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody id="reports-table-body">
                                            <!-- Data will be loaded here -->
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/popper.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>

    <script type="text/javascript">
        const API_URL = '../backend/api/service_simple_api.php';

        $(document).ready(function() {
            initializeServiceReport();

            $(".xp-menubar").on('click', function() {
                $('#sidebar').toggleClass('active');
                $('#content').toggleClass('active');
            });

            $(".xp-menubar,.body-overlay").on('click', function() {
                $('#sidebar,.body-overlay').toggleClass('show-nav');
            });

            $('#serviceReportListModal').on('show.bs.modal', loadServiceReports);

            $(document).on('click', '.edit-report', function(e) {
                e.preventDefault();
                const reportId = $(this).data('id');
                $('#serviceReportListModal').modal('hide');
                setTimeout(() => {
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                }, 300);
                loadReportForEditing(reportId);
            });

            $(document).on('click', '.delete-report', function(e) {
                e.preventDefault();
                const reportId = $(this).data('id');
                $('#serviceReportListModal').modal('hide');
                setTimeout(() => {
                    $('.modal-backdrop').remove();
                    $('body').removeClass('modal-open');
                }, 300);
                deleteReport(reportId);
            });
        });

        function initializeServiceReport() {
            $('#serviceReportForm').on('submit', handleFormSubmit);
            $('#cancel-button').on('click', resetForm);
        }

        async function handleFormSubmit(e) {
            e.preventDefault();

            // Validate required fields
            const customerName = $('#customer-name').val().trim();
            const applianceName = $('#appliance-name').val().trim();
            const dateIn = $('#date-in').val();
            const status = $('#status').val();

            if (!customerName) {
                showAlert('danger', 'Please enter customer name');
                return;
            }

            if (!applianceName) {
                showAlert('danger', 'Please enter appliance name');
                return;
            }

            if (!dateIn) {
                showAlert('danger', 'Please select date in');
                return;
            }

            if (!status) {
                showAlert('danger', 'Please select status');
                return;
            }

            try {
                showLoading(true);

                const formData = {
                    customer_name: customerName,
                    appliance_name: applianceName,
                    date_in: dateIn,
                    status: status,
                    dealer: $('input[name="dealer"]').val() || '',
                    findings: $('input[name="findings"]').val() || '',
                    remarks: $('input[name="remarks"]').val() || '',
                    complaint: $('textarea[name="complaint"]').val() || ''
                };

                const reportId = $('#report-id').val();
                let action = 'create';
                let url = `${API_URL}?action=create`;

                if (reportId) {
                    action = 'update';
                    url = `${API_URL}?action=update&id=${reportId}`;
                    formData.report_id = reportId;
                }

                const response = await $.ajax({
                    url: url,
                    method: 'POST',
                    dataType: 'json',
                    contentType: 'application/json',
                    data: JSON.stringify(formData)
                });

                if (response.success) {
                    showAlert('success', reportId ? 'Report updated successfully' : 'Report created successfully');
                    resetForm();
                } else {
                    throw new Error(response.message || 'Failed to process report');
                }

            } catch (error) {
                console.error('Form submission error:', error);
                const errorMsg = error.responseJSON?.message || error.message || 'An error occurred';
                showAlert('danger', errorMsg);
            } finally {
                showLoading(false);
            }
        }

        async function loadServiceReports() {
            try {
                showLoading(true);

                const response = await $.ajax({
                    url: `${API_URL}?action=getAll`,
                    method: 'GET',
                    dataType: 'json'
                });

                if (response.success && response.data) {
                    renderServiceReports(response.data);
                } else {
                    throw new Error('Failed to load reports');
                }

            } catch (error) {
                console.error('Load reports error:', error);
                showAlert('danger', 'Failed to load reports');
            } finally {
                showLoading(false);
            }
        }

        function renderServiceReports(reports) {
            const $tbody = $('#reports-table-body').empty();

            if (!reports || reports.length === 0) {
                $tbody.append('<tr><td colspan="6" class="text-center">No reports found</td></tr>');
                return;
            }

            reports.forEach(report => {
                const dateIn = report.date_in ? new Date(report.date_in).toLocaleDateString() : 'N/A';
                
                let statusBadge = '';
                switch (report.status) {
                    case 'Completed':
                        statusBadge = '<span class="badge badge-success">Completed</span>';
                        break;
                    case 'Pending':
                        statusBadge = '<span class="badge badge-warning">Pending</span>';
                        break;
                    case 'Under Repair':
                        statusBadge = '<span class="badge badge-primary">Under Repair</span>';
                        break;
                    case 'Unrepairable':
                        statusBadge = '<span class="badge badge-danger">Unrepairable</span>';
                        break;
                    case 'Release Out':
                        statusBadge = '<span class="badge badge-secondary">Release Out</span>';
                        break;
                    default:
                        statusBadge = `<span class="badge badge-light">${report.status || 'N/A'}</span>`;
                }

                $tbody.append(`
                    <tr>
                        <td>${report.report_id}</td>
                        <td>${report.customer_name}</td>
                        <td>${report.appliance_name}</td>
                        <td>${dateIn}</td>
                        <td>${statusBadge}</td>
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

        async function loadReportForEditing(reportId) {
            try {
                showLoading(true);

                const response = await $.ajax({
                    url: `${API_URL}?action=getById&id=${reportId}`,
                    method: 'GET',
                    dataType: 'json'
                });

                if (response.success && response.data) {
                    const report = response.data;
                    
                    $('#report-id').val(report.report_id);
                    $('#customer-name').val(report.customer_name);
                    $('#appliance-name').val(report.appliance_name);
                    $('#date-in').val(report.date_in);
                    $('#status').val(report.status);
                    $('input[name="dealer"]').val(report.dealer || '');
                    $('input[name="findings"]').val(report.findings || '');
                    $('input[name="remarks"]').val(report.remarks || '');
                    $('textarea[name="complaint"]').val(report.complaint || '');

                    $('#submit-report-btn').text('Update Report');
                } else {
                    throw new Error('Report not found');
                }

            } catch (error) {
                console.error('Load report error:', error);
                showAlert('danger', 'Failed to load report');
            } finally {
                showLoading(false);
            }
        }

        async function deleteReport(reportId) {
            if (!confirm('Are you sure you want to delete this report?')) return;

            try {
                showLoading(true);

                const response = await $.ajax({
                    url: `${API_URL}?action=delete&id=${reportId}`,
                    method: 'DELETE',
                    dataType: 'json'
                });

                if (response.success) {
                    showAlert('success', 'Report deleted successfully');
                    await loadServiceReports();
                } else {
                    throw new Error('Failed to delete report');
                }

            } catch (error) {
                console.error('Delete report error:', error);
                showAlert('danger', error.responseJSON?.message || 'Failed to delete report');
            } finally {
                showLoading(false);
            }
        }

        function resetForm() {
            $('#serviceReportForm')[0].reset();
            $('#report-id').val('');
            $('#submit-report-btn').text('Create Report');
        }

        function showAlert(type, message) {
            $('.alert-notification').remove();
            const alertHtml = `
                <div class="alert-notification alert alert-${type} alert-dismissible fade show">
                    ${message}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            `;
            $('body').prepend(alertHtml);
            setTimeout(() => $('.alert-notification').alert('close'), 5000);
        }

        function showLoading(show) {
            if (show) {
                $('body').append('<div class="loading-overlay"><div class="spinner-border text-light"></div></div>');
            } else {
                $('.loading-overlay').remove();
            }
        }
    </script>
</body>

</html>

<?php
require_once __DIR__ . '/../bootstrap.php';
session_start();
require __DIR__ . '/../backend/handlers/authHandler.php';
require __DIR__ . '/../backend/handlers/Database.php';
require __DIR__ . '/../backend/handlers/serviceHandler.php';
require __DIR__ . '/../backend/handlers/customersHandler.php';
require __DIR__ . '/../backend/handlers/partsHandler.php';
require __DIR__ . '/../backend/handlers/staffsHandler.php';

$auth = new AuthHandler();
$userSession = $auth->requireAuth('staff');

// Initialize database connection
try {
    $db = new Database();
    $conn = $db->getConnection();
} catch (Exception $e) {
    die("Database Connection Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff - Service Reports</title>
    <link rel="shortcut Icon" href="../img/Repair.png" type="image/x-icon">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Icons" rel="stylesheet">
    <link rel="stylesheet" href="../css/bootstrap.min.css">
    <link rel="stylesheet" href="../css/custom.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    <style>
        .icon-btn {
            background: #ececec;
            border: 1.5px solid #ececec;
            border-radius: 8px;
            transition: background 0.2s, border 0.2s, box-shadow 0.2s;
        }

        .icon-btn:hover,
        .icon-btn:focus {
            background: #f8f9fa;
            border-color: #bdbdbd;
            box-shadow: 0 2px 8px rgba(53, 59, 72, 0.10);
        }

        .blink-badge {
            animation: blink-badge 1.2s infinite alternate;
        }

        @keyframes blink-badge {
            0% {
                opacity: 1;
            }

            100% {
                opacity: 0.5;
            }
        }

        .actions-col {
            min-width: 180px;
        }

        .actions-col a {
            display: inline-block;
            margin-right: 8px;
            vertical-align: middle;
        }

        .actions-col a:last-child {
            margin-right: 0;
        }

        .alert-notification {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            min-width: 300px;
            animation: slideIn 0.5s forwards;
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

        .loading {
            position: relative;
        }

        .spinner-border {
            width: 3rem;
            height: 3rem;
        }

        .badge-success {
            background-color: #28a745;
            color: white;
        }

        .badge-warning {
            background-color: #ffc107;
            color: #212529;
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

        .actions-col a span {
            margin-left: 4px;
            font-size: 12px;
        }

        .actions-col a.edit-report-completed {
            color: #28a745;
        }

        .actions-col a.edit-report-completed:hover {
            background-color: #e8f5e8;
        }

        .actions-col a.edit-report {
            color: #007bff;
        }

        .actions-col a.edit-report:hover {
            background-color: #e3f2fd;
        }

        .actions-col a.delete-report {
            color: #dc3545;
        }

        .actions-col a.delete-report:hover {
            background-color: #fde8e8;
        }

        .part-used-section {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #ffffffff;
        }

        .part-used-header {
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
        }

        .parts-container-wrapper {
            margin-bottom: 15px;
        }

        .add-part-btn-container {
            display: flex;
            justify-content: flex-start;
            margin-top: 10px;
        }

        .section-border {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }

        #serviceReportListModal .modal-dialog {
            margin: 1.75rem auto;
            max-height: 90vh;
            display: flex;
            align-items: center;
        }

        #serviceReportListModal .modal-content {
            max-height: 90vh;
            overflow-y: auto;
        }

        #serviceReportListModal .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }
        
        .tabs {
            display: flex;
            border-bottom: 1px solid #dee2e6;
            margin-bottom: 20px;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        
        .tab.active {
            border-bottom-color: #007bff;
            color: #007bff;
            font-weight: 500;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }

        .findings-section {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #f8f9fa;
        }
        
        .findings-header {
            display: grid;
            grid-template-columns: 1fr auto auto auto;
            gap: 10px;
            margin-bottom: 10px;
            font-weight: 500;
        }
        
        .findings-row {
            display: grid;
            grid-template-columns: 1fr auto auto auto;
            gap: 10px;
            align-items: center;
        }
        
        .checkbox-container {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        
        .findings-checkbox {
            width: 20px;
            height: 20px;
            margin: 0;
        }

        .modal-xxl {
            max-width: 90vw !important;
            width: 90vw !important;
            margin: 0 auto;
        }

        .modal-xxl .modal-content {
            min-height: 96vh;
            height: auto;
        }
        
        .modal-title {
            color: #000000 !important;
            font-weight: 600;
        }

        @media print {
            body * {
                visibility: hidden;
            }

            .print-section,
            .print-section * {
                visibility: visible;
            }

            .print-section {
                position: absolute;
                left: 0;
                top: 0;
                width: 100%;
                padding: 20px;
                background: white;
            }

            .no-print {
                display: none !important;
            }

            .modal-xxl {
                max-width: 100% !important;
                width: 100% !important;
                margin: 0 !important;
            }

            .modal-content {
                box-shadow: none !important;
                border: 1px solid #ddd !important;
            }

            .modal-header {
                border-bottom: 2px solid #333 !important;
            }

            .table {
                border-collapse: collapse;
                width: 100%;
                font-size: 12px;
            }

            .table th,
            .table td {
                border: 1px solid #ddd;
                padding: 6px;
            }

            .table th {
                background-color: #f2f2f2;
                font-weight: bold;
            }

            .findings-section {
                border: 1px solid #000 !important;
                background-color: white !important;
            }

            .findings-checkbox:checked {
                background-color: #000 !important;
            }

            .card {
                border: none !important;
                box-shadow: none !important;
            }

            .card-header {
                background: white !important;
                border-bottom: 2px solid #333 !important;
            }

            .card-title {
                font-size: 18px;
                font-weight: bold;
                color: #000000 !important;
            }
            
            .modal-title {
                color: #000000 !important;
            }
        }

        #transactionForm input,
        #transactionForm select,
        #transactionForm textarea {
            background-color: #f8f9fa;
        }

        #transactionForm input:not([readonly]),
        #transactionForm select:not([readonly]),
        #transactionForm textarea:not([readonly]) {
            background-color: #ffffff;
        }

        .pagination .page-link {
            color: #242424ff;
            border-color: #dee2e6;
        }
        .pagination .page-item.active .page-link {
            background-color: #242424ff;
            border-color: #242424ff;
            color: #fff;
        }
        .pagination .page-item.disabled .page-link {
            color: #6c757d;
        }

        /* Service Report List Modal Styles */
        #serviceReportListModal .modal-dialog {
            margin: 1.75rem auto;
            max-height: 90vh;
            display: flex;
            align-items: center;
        }

        #serviceReportListModal .modal-content {
            max-height: 90vh;
            overflow-y: auto;
        }

        #serviceReportListModal .modal-body {
            max-height: 70vh;
            overflow-y: auto;
        }

        #serviceReportListModal .modal-header {
            background-color: #0066e6 !important;
        }

        #serviceReportListModal .modal-title {
            color: white !important;
        }

        #serviceReportListModal .close {
            color: white !important;
            opacity: 1 !important;
            font-size: 1.8rem;
            background: none;
            border: none;
        }

        #serviceReportListModal .close:hover {
            color: rgba(255, 255, 255, 0.8) !important;
        }

        /* Customer search suggestion styles */
        .customer-search-wrapper {
            position: relative;
        }
        .customer-suggestions {
            position: absolute;
            left: 0;
            top: calc(100% + 6px);
            width: 100%;
            z-index: 2000;
            box-shadow: 0 10px 30px rgba(50,50,93,0.12);
            border-radius: 8px;
            overflow: auto;
        }
        .customer-suggestions .list-group-item {
            cursor: pointer;
            background-color: #ffffff !important;
            color: #212529 !important;
            border: none;
        }
        .customer-suggestions .list-group-item:hover,
        .customer-suggestions .list-group-item:focus,
        .customer-suggestions .list-group-item.active {
            background-color: #f8f9fa !important;
            color: #212529 !important;
        }
    </style>
    <script>
        // Current logged-in staff name available for client JS
        window.currentStaffName = '<?php echo addslashes($_SESSION['user']['full_name'] ?? $_SESSION['user']['username'] ?? ''); ?>';
    </script>
</head>
<body>
    <div class="wrapper">
        <!-- Include Sidebar -->
        <?php include 'staff_sidebar.php'; ?>

        <div id="content">
            <!-- Navbar -->
            <?php
              $pageTitle = "Service Report";
              $pageCrumb = "Customer";
              include 'staffnavbar.php'; 
            ?>
       
            <div class="content-area">
                <!-- Service Report Form -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Service Report Form</h5>
                        <button type="button" class="btn icon-btn position-relative p-2" data-toggle="modal" data-target="#serviceReportListModal">
                            <i class="material-icons align-middle" style="font-size: 2em; color: #353b48;">list</i>
                            <span id="report-badge" class="position-absolute blink-badge" style="display:none; top: 2px; right: 2px; width: 12px; height: 12px; background: #ff6b6b; border-radius: 50%;"></span>
                        </button>
                    </div>
                    <div class="card-body">
                        <form id="serviceReportForm" method="post" action="">
                            <div class="container-fluid">
                                <!-- First Row: Customer, Appliance, Date In, Status -->
                                <div class="row mb-2">
                                    <div class="col-md-3">
                                        <label>Customer</label>
                                        <!-- Searchable customer input + hidden select to store id -->
                                        <div class="customer-search-wrapper" style="position: relative;">
                                            <!-- Search input hidden for now; use dropdown select instead -->
                                            <input type="text" id="customer-search" class="form-control" placeholder="Search customer by name" autocomplete="off" spellcheck="false" autocorrect="off" autocapitalize="off" style="display:none;">
                                            <select class="form-control customer-select" name="customer" id="customer-select">
                                                <option value="">Select Customer</option>
                                            </select>
                                            <div id="customer-suggestions" class="list-group customer-suggestions" style="display:none; max-height: 260px; overflow-y: auto; position: absolute; left: 0; top: calc(100% + 6px); width: 100%; z-index: 2000;"></div>
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Appliance</label>
                                        <select class="form-control appliance-select" name="appliance" id="appliance-select">
                                            <option>Select Appliance</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Date In</label>
                                                <input type="date" class="form-control" name="date_in" id="date-in" required>
                                    </div>
                                    <div class="col-md-3">
                                        <label>Status</label>
                                        <select class="form-control" name="status" id="create_status" required>
                                            <option value="">Select Status</option>
                                            <option value="Pending">Pending</option>
                                            <option value="Under Repair">Under Repair</option>
                                            <option value="Unrepairable">Unrepairable</option>
                                            <option value="Release Out">Release Out</option>
                                            <option value="Completed">Completed</option>
                                        </select>
                                    </div>
                                </div>
                                <!-- Second Row: Dealer, DOP, Date Pulled-Out -->
                                <div class="row mb-2">
                                    <div class="col-md-3">
                                        <label>Dealer</label>
                                        <input type="text" class="form-control" name="dealer">
                                    </div>
                                    <div class="col-md-3">
                                        <label>DOP</label>
                                        <input type="date" class="form-control" name="dop">
                                    </div>
                                    <div class="col-md-3">
                                        <label>Date Pulled - Out</label>
                                        <input type="date" class="form-control" name="date_pulled_out">
                                    </div>
                                </div>
                                <!-- Findings Row -->
                                <div class="row mb-0 align-items-end">
                                    <div class="col-md-9">
                                        <label>Findings</label>
                                    </div>
                                    <div class="col-md-1 text-center">
                                        <label class="form-label p-0 m-0 w-100" style="font-weight: normal;">Shop</label>
                                    </div>
                                    <div class="col-md-1 text-center">
                                        <label class="form-label p-0 m-0 w-100" style="font-weight: normal;">Field</label>
                                    </div>
                                    <div class="col-md-1 text-center">
                                        <label class="form-label p-0 m-0 w-100" style="font-weight: normal;">Out WTY</label>
                                    </div>
                                </div>
                                <div class="row mb-2 align-items-center">
                                    <div class="col-md-9">
                                        <input type="text" class="form-control" name="findings">
                                    </div>
                                    <div class="col-md-1 text-center d-flex flex-column align-items-center justify-content-center">
                                        <input class="form-check-input" type="checkbox" name="shop" id="shop" style="width: 1.4em; height: 1.4em;">
                                    </div>
                                    <div class="col-md-1 text-center d-flex flex-column align-items-center justify-content-center">
                                        <input class="form-check-input" type="checkbox" name="field" id="field" style="width: 1.4em; height: 1.4em;">
                                    </div>
                                    <div class="col-md-1 text-center d-flex flex-column align-items-center justify-content-center">
                                        <input class="form-check-input" type="checkbox" name="out_wty" id="out_wty" style="width: 1.4em; height: 1.4em;">
                                    </div>
                                </div>
                                <div class="row mb-2">
                                    <div class="col-md-9">
                                        <label>Remarks</label>
                                        <input type="text" class="form-control" name="remarks">
                                    </div>
                                </div>
                                
                                <!-- Part Used Section -->
                                <div class="part-used-section">
                                    <div class="row mb-2 pl-3">
                                       <button type="button" class="btn btn-primary add-part d-flex align-items-center justify-content-center" id="add-part">
                                            <i class="material-icons me-1">add</i> Add Part
                                        </button>
                                    </div>
                                    
                                    <!-- Parts Container for dynamic rows -->
                                    <div id="parts-container" class="parts-container-wrapper">
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
                                            <div class="col-md-1 px-1 d-flex justify-content-end align-items-center ml-3">
                                                <button type="button" class="btn btn-danger remove-part d-flex align-items-center justify-content-center" style="display:none;">
                                                    <span class="material-icons" style="font-size: 1.2em;">delete</span>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                
                                    <!-- Checkboxes for Service Type -->
                                    <div class="row mb-1">
                                        <div class="col-md-12">
                                            <div class="d-flex flex-row" style="gap: 2rem;">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="installation" id="installation">
                                                    <label class="form-check-label" for="installation">Installation</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="repair" id="repair">
                                                    <label class="form-check-label" for="repair">Repair</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="cleaning" id="cleaning">
                                                    <label class="form-check-label" for="cleaning">Cleaning</label>
                                                </div>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="checkup" id="checkup">
                                                    <label class="form-check-label" for="checkup">Check-Up</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Complaint, Date Repaired and Date Delivered Row -->
                                <div class="row mb-2">
                                    <div class="col-md-6 pe-1">
                                        <label>Complaint</label>
                                        <textarea class="form-control" name="complaint" id="complaint" rows="2"></textarea>
                                    </div>
                                    <div class="col-md-3 px-1">
                                        <label>Date Repaired</label>
                                        <input type="date" class="form-control" name="date_repaired">
                                    </div>
                                    <div class="col-md-3 ps-1">
                                        <label>Date Delivered</label>
                                        <input type="date" class="form-control" name="date_delivered">
                                    </div>
                                </div>
                                <!-- Charged Details Section Header -->
                                <div class="row mt-3 mb-1">
                                    <div class="col-md-12">
                                        <h5 class="fw-bold mb-1">Charged Details</h5>
                                    </div>
                                </div>
                                <!-- Charged Details Row -->
                                <div class="row mb-2 align-items-end">
                                    <div class="col-md-3 pe-1">
                                        <label class="mb-1">Labor:</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="labor" id="labor-amount" value="0.00" min="0" step="1.00">
                                        </div>
                                    </div>
                                    <div class="col-md-3 px-1">
                                        <label class="mb-1">Pull-Out Delivery:</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="pullout_delivery" id="pullout-delivery" value="0.00" min="0" step="1.00">
                                        </div>
                                    </div>
                                    <div class="col-md-3 px-1">
                                        <label class="mb-1">Total:</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="total_amount" id="total-amount-2" readonly>
                                        </div>
                                    </div>
                                    <div class="col-md-3 ps-1">
                                        <label class="mb-1">Parts Charge:</label>
                                        <div class="input-group">
                                            <span class="input-group-text">₱</span>
                                            <input type="number" class="form-control" name="parts_charge" readonly>
                                        </div>
                                    </div>
                                </div>
                                <!-- Signature Fields Row -->
                                <div class="row mb-2">
                                    <div class="col-md-3 pe-1 d-flex flex-column justify-content-end">
                                       <select name="receptionist" id="receptionist-select" class="form-control staff-select" data-role="Cashier">
                                            <option value="">Receptionist</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 px-1 d-flex flex-column justify-content-end">
                                        <label class="mb-1">Manager:</label>
                                        <select name="manager" id="manager-select" class="form-control staff-select" data-role="Manager">
                                            <option value="">Manager</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 px-1 d-flex flex-column justify-content-end">
                                        <label class="mb-1">Technician:</label>
                                        <select name="technician" id="technician-select" class="form-control staff-select" data-role="Technician">
                                            <option value="">Technician</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3 ps-1 d-flex flex-column justify-content-end">
                                        <label class="mb-1">Released By:</label>
                                        <select name="released_by" id="released-by-select" class="form-control staff-select" data-role="Cashier">
                                            <option value="">Released By</option>
                                        </select>
                                    </div>
                                </div>
                                <!-- Submit Button -->
                                <div class="d-flex justify-content-end mt-4" style="gap: 1rem;">
                                        <button type="button" class="btn btn-secondary px-4" id="cancel-button">Cancel</button>
                                        <input type="hidden" name="report_id" id="report_id">
                                        <button type="submit" class="btn btn-primary px-4" id="submit-report-btn" style="background-color: #0066e6; border: none;">Create Report</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Service Report List Modal -->
    <div class="modal fade" id="serviceReportListModal" tabindex="-1" aria-labelledby="serviceReportListModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 1200px; width: 95%;">
            <div class="modal-content" style="border-radius: 18px;">
                <div class="modal-header justify-content-center" style="background-color: #0066e6;">
                    <h4 class="modal-title w-100 text-center text-white" id="serviceReportListModalLabel">Service Report List</h4>
                    <button type="button" class="close close-modal-report position-absolute" data-dismiss="modal" aria-label="Close" style="right: 20px; top: 18px; color: white; background: none; border: none; cursor: pointer; font-size: 1.8rem; font-weight: 300; padding: 0; opacity: 1;">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body p-0">
                    <div class="px-3 py-2 border-bottom">
                        <div class="row">
                            <div class="col-md-6 d-flex align-items-center">
                                <div class="input-group input-group-sm me-2" style="min-width: 240px;">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text" id="serviceReportSearchIcon">🔍</span>
                                    </div>
                                    <input type="text" class="form-control form-control-sm" id="service-report-search" placeholder="Search reports by ID, customer, appliance, or type" aria-label="Search reports" aria-describedby="serviceReportSearchIcon" autocomplete="off">
                                </div>
                                <div>
                                    <select id="service-report-filter" class="form-control form-control-sm" style="width: 160px;">
                                        <option value="All">All</option>
                                        <option value="Completed">Completed</option>
                                        <option value="Pending">Pending</option>
                                        <option value="Under Repair">Under Repair</option>
                                        <option value="Unrepairable">Unrepairable</option>
                                        <option value="Release Out">Release Out</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table mb-0" style="font-family: 'Poppins', sans-serif;">
                            <thead class="thead-light">
                                <tr>
                                    <th style="white-space: nowrap;">Report ID</th>
                                    <th>Customer</th>
                                    <th>Appliance</th>
                                    <th>Service Type</th>
                                    <th>Date In</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!--Data are placed here-->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Report Modal -->
    <div class="modal fade" id="editReportModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form>
                    <div class="modal-header">
                        <h4 class="modal-title">Update Service Report</h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="report_id" id="edit_report_id">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Status</label>
                                    <select name="status" id="edit_status" class="form-control" required>
                                        <option value="">Select Status</option>
                                        <option value="Pending">Pending</option>
                                        <option value="Under Repair">Under Repair</option>
                                        <option value="Unrepairable">Unrepairable</option>
                                        <option value="Release Out">Release Out</option>
                                        <option value="Completed">Completed</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Cost</label>
                                    <input type="number" name="cost" id="edit_cost" class="form-control" step="0.01">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Date Repaired</label>
                                    <input type="date" name="date_repaired" id="edit_date_repaired" class="form-control">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label>Date Delivered</label>
                                    <input type="date" name="date_delivered" id="edit_date_delivered" class="form-control">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" name="update_status" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Transaction Details Modal -->
    <div class="modal fade" id="transactionDetailsModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Transaction Details</h5>
                    <button type="button" class="close" data-dismiss="modal">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="transaction-details">
                        <div class="form-row">
                            <div class="form-group">
                                <div class="info-label">Transaction ID</div>
                                <div class="info-value">TRX-001</div>
                            </div>
                            <div class="form-group">
                                <div class="info-label">Customer</div>
                                <div class="info-value">John Doe</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/popper.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <script src="javascripts/script_for_report.js"></script>
    <script type="text/javascript">
        // Customer search function
        function initCustomerSearch() {
            const $input = $('#customer-search');
            const $hiddenSelect = $('#customer-select');
            const $suggestions = $('#customer-suggestions');

            if (!$input.length) return;

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

            $input.on('pointerdown touchstart mousedown', function() {
                allowSuggestionsOnFocus = true;
            });

            $input.on('focus', function() {
                if (allowSuggestionsOnFocus) {
                    renderCustomerSuggestions('');
                }
                allowSuggestionsOnFocus = false;
            });

            $hiddenSelect.on('change', function() {
                const text = $(this).find('option:selected').text();
                if (text && text !== 'Select Customer') {
                    console.debug('hiddenSelect change -> setting #customer-search to:', text);
                    $input.val(text);
                    console.debug('#customer-search value after hiddenSelect change:', $input.val());
                }
            });

            $input.on('blur', function() {
                setTimeout(() => $suggestions.hide(), 150);
            });

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

            let $option = $hiddenSelect.find(`option[value="${id}"]`);
            if ($option.length === 0) {
                $option = $(`<option></option>`).val(id).text(name);
                $hiddenSelect.append($option);
            }
            $hiddenSelect.val(id).trigger('change');
            $('#customer-suggestions').hide();
        }
        
        // Function to set dropdown by matching text content (for staff names)
        function setDropdownValueByText(selector, value) {
            if (!value) return;

            // Skip placeholder/default values
            const placeholders = ['Select Appliance', 'Select an Appliance', 'Select staff', 'Receptionist', 'Manager', 'Technician', 'Released By'];
            if (placeholders.includes(value.trim())) {
                console.log(`Skipping placeholder value "${value}" for dropdown ${selector}`);
                return;
            }

            const $dropdown = $(selector);
            const $options = $dropdown.find('option');

            // Step 1: Extract the actual username/name (remove role in parentheses)
            // Stored value might be "admin123 (Manager)" or just "admin123"
            const storedNameMatch = value.match(/^([^(]+)/);
            const cleanStoredName = storedNameMatch ? storedNameMatch[1].trim().toLowerCase() : value.toLowerCase();

            console.log(`Setting dropdown ${selector} - Original value: "${value}", Clean name: "${cleanStoredName}"`);

            // Step 2: Try exact match on clean names
            let $option = $options.filter((i, el) => {
                const optionText = $(el).text();
                // Extract name from "Name (Role)" format
                const optionNameMatch = optionText.match(/^([^(]+)/);
                const cleanOptionName = optionNameMatch ? optionNameMatch[1].trim().toLowerCase() : optionText.toLowerCase();
                return cleanOptionName === cleanStoredName;
            });

            // Step 3: Try case-insensitive partial match if exact match failed
            if ($option.length === 0) {
                $option = $options.filter((i, el) => {
                    const optionText = $(el).text().toLowerCase();
                    return optionText.includes(cleanStoredName);
                });
            }

            // Set the selected option
            if ($option.length > 0) {
                $option.first().prop('selected', true);
                console.log(`Successfully set dropdown ${selector} to: "${$option.first().text()}"`);
            } else {
                console.warn(`Could not match value "${value}" in dropdown ${selector}. Available options:`, 
                    $options.map((i, el) => $(el).text()).get());
            }
        }

        $(document).ready(function() {
            // Initialize the application
            initializeServiceReport();
            bindEventHandlers();
        });

        function showAlert(type, message) {
            try {
                $('.alert-notification').remove();
                const alertHtml = `
                    <div class="alert-notification alert alert-${type} alert-dismissible fade show" role="alert">
                        ${message}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                `;
                $('body').prepend(alertHtml);
                setTimeout(() => {
                    const $alert = $('.alert-notification');
                    if ($alert.length) {
                        $alert.alert('close');
                    }
                }, 5000);
            } catch (error) {
                console.error('Error showing alert:', error);
            }
        }

        function loadCustomers() {
            $.ajax({
                url: '../backend/api/customer_appliance_api.php?action=getAllCustomers',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    console.log('Customer API Response:', data);
                    const select = $('#customer-select');
                    select.empty().append('<option value="">Select Customer</option>');
                    
                    // Extract customers from nested data structure
                    let customers = [];
                    if (data.success && data.data) {
                        if (Array.isArray(data.data)) {
                            customers = data.data;
                        } else if (data.data.customers && Array.isArray(data.data.customers)) {
                            customers = data.data.customers;
                        } else if (data.data.data && Array.isArray(data.data.data)) {
                            customers = data.data.data;
                        }
                    }
                    
                    if (customers.length > 0) {
                        customers.forEach(function(customer) {
                            const id = customer.customer_id || customer.id;
                            const name = customer.FullName || customer.name || (customer.first_name + ' ' + customer.last_name);
                            select.append(`<option value="${id}">${name}</option>`);
                        });
                    } else {
                        showAlert('warning', 'No customers found in database');
                        console.warn('No customers in response:', data);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading customers:', error);
                    console.error('Response:', xhr.responseText);
                    showAlert('error', 'Error loading customers: ' + error);
                }
            });
        }

        function loadAppliances(customerId, applianceIdToSelect = null) {
            return $.ajax({
                url: '../backend/api/customer_appliance_api.php?action=getAppliancesByCustomerId&customerId=' + customerId,
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    console.log('Appliances API Response:', data);
                    const select = $('#appliance-select');
                    select.empty().append('<option value="">Select Appliance</option>');
                    
                    let appliances = [];
                    if (data.success && data.data) {
                        if (Array.isArray(data.data)) {
                            appliances = data.data;
                        } else if (data.data.appliances && Array.isArray(data.data.appliances)) {
                            appliances = data.data.appliances;
                        } else if (data.data.data && Array.isArray(data.data.data)) {
                            appliances = data.data.data;
                        }
                    }
                    
                    if (appliances.length > 0) {
                        appliances.forEach(function(appliance) {
                            const id = appliance.appliance_id || appliance.applianceId || appliance.id || appliance.applianceId;
                            // Build name to match format used in service reports: Brand - SerialNo (Category)
                            let name = '';
                            
                            if (appliance.brand && appliance.serial_no && appliance.category) {
                                name = `${appliance.brand} - ${appliance.serial_no} (${appliance.category})`;
                            } else if (appliance.brand || appliance.product) {
                                const parts = [];
                                if (appliance.brand) parts.push(appliance.brand);
                                if (appliance.product) parts.push(appliance.product);
                                name = parts.join(' ').trim();
                            } else {
                                name = appliance.appliance_name || appliance.name || appliance.product || ('Appliance ' + (id || ''));
                            }
                            
                            select.append(`<option value="${id}">${name}</option>`);
                        });
                        
                        // Auto-select appliance if ID provided
                        if (applianceIdToSelect) {
                            select.val(applianceIdToSelect);
                            console.log('Auto-selected appliance ID:', applianceIdToSelect);
                        }
                    } else {
                        showAlert('warning', 'No appliances found for this customer');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading appliances:', error);
                    showAlert('error', 'Error loading appliances: ' + error);
                }
            });
        }

        function loadParts() {
            $.ajax({
                url: '../backend/api/parts_api.php?action=getAllParts',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    console.log('Parts API Response:', data);
                    if (data.success && data.data) {
                        let parts = [];
                        if (Array.isArray(data.data)) {
                            parts = data.data;
                        } else if (data.data.parts && Array.isArray(data.data.parts)) {
                            parts = data.data.parts;
                        } else if (data.data.data && Array.isArray(data.data.data)) {
                            parts = data.data.data;
                        }
                        $('body').data('parts', parts);
                        // Populate any existing part-select elements in the form
                        $('.part-select').each(function() {
                            const $sel = $(this);
                            $sel.empty().append('<option value="">Select Part</option>');
                            parts.forEach(function(part) {
                                const pid = part.part_id || part.id || part.partId || part.ID;
                                const price = part.price || part.part_price || 0;
                                const label = part.description || part.part_no || part.name || part.part_name || `Part ${pid}`;
                                $sel.append(`<option value="${pid}" data-price="${price}">${label}</option>`);
                            });
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading parts:', error);
                    showAlert('error', 'Error loading parts: ' + error);
                }
            });
        }

        function loadStaff() {
            $.ajax({
                url: '../backend/api/staff_api.php?action=getAllStaffs',
                method: 'GET',
                dataType: 'json',
                success: function(data) {
                    console.log('Staff API Response:', data);
                    if (data.success && data.data) {
                        let staffList = [];
                        if (Array.isArray(data.data)) {
                            staffList = data.data;
                        } else if (data.data.staffs && Array.isArray(data.data.staffs)) {
                            staffList = data.data.staffs;
                        } else if (data.data.data && Array.isArray(data.data.data)) {
                            staffList = data.data.data;
                        }
                        populateStaffSelects(staffList);
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error loading staff:', error);
                    showAlert('error', 'Error loading staff: ' + error);
                }
            });
        }

        // Load service reports and populate the modal table
        function loadServiceReports() {
            $.ajax({
                url: '../backend/api/service_api.php?action=getAll',
                method: 'GET',
                dataType: 'json',
                success: function(res) {
                    console.log('Service Reports API Response:', res);

                    if (!res.success || !res.data || !Array.isArray(res.data)) {
                        allServiceReports = [];
                        renderServiceReports([]);
                        updateBadgeFromReports([]);
                        return;
                    }

                    // Store all reports for filtering
                    allServiceReports = res.data;
                    
                    // Reset search/filter and render all reports
                    $('#service-report-search').val('');
                    $('#service-report-filter').val('All');
                    
                    renderServiceReports(allServiceReports);
                    updateBadgeFromReports(allServiceReports);

                    updateBadgeFromReports(res.data);
                },
                error: function(xhr) {
                    console.error('Error loading service reports:', xhr.responseText);
                    $('#serviceReportListModal tbody').html('<tr><td colspan="8">Failed to load reports</td></tr>');
                }
            });
        }

        // Refresh reports without resetting filters
        window.refreshServiceReports = function() {
            return $.ajax({
                url: '../backend/api/service_api.php?action=getAll',
                method: 'GET',
                dataType: 'json',
                success: function(res) {
                    console.log('Refreshing Service Reports:', res);

                    if (!res.success || !res.data || !Array.isArray(res.data)) {
                        return;
                    }

                    // Store all reports for filtering
                    allServiceReports = res.data;
                    
                    // Re-apply current filters
                    applyStatusAndSearch();
                    updateBadgeFromReports(allServiceReports);
                },
                error: function(xhr) {
                    console.error('Error refreshing service reports:', xhr.responseText);
                }
            });
        };

        // Optimistically update a report in the local cache
        window.updateReportInCache = function(reportId, updatedData) {
            const index = allServiceReports.findIndex(r => r.report_id == reportId);
            if (index !== -1) {
                // Merge updated data with existing report
                allServiceReports[index] = { ...allServiceReports[index], ...updatedData };
                console.log('Optimistically updated report in cache:', reportId);
                
                // Re-render with current filters
                applyStatusAndSearch();
                updateBadgeFromReports(allServiceReports);
            }
        };

        function updateBadgeFromReports(reports) {
            const $badge = $('#report-badge');
            const hasPending = Array.isArray(reports) && reports.some(r => {
                let status = r.status;
                // Map numeric status codes to text
                if (typeof status === 'number' || /^\d+$/.test(status)) {
                    const statusMap = {'0': 'Completed', '1': 'Pending', '2': 'Under Repair', '3': 'Unrepairable', '4': 'Release Out'};
                    status = statusMap[String(status)] || status;
                }
                return status === 'Pending';
            });
            if (hasPending) {
                $badge.show().addClass('blink-badge');
            } else {
                $badge.hide().removeClass('blink-badge');
            }
        }

        function populateStaffSelects(staffData) {
            const roles = {
                'receptionist-select': 'Cashier',
                'manager-select': 'Manager',
                'technician-select': 'Technician',
                'released-by-select': 'Cashier'
            };

            Object.entries(roles).forEach(([selectId, role]) => {
                const select = $('#' + selectId);
                select.empty().append(`<option value=\"\">Select ${role}</option>`);
                staffData.forEach(function(staff) {
                    // Normalize and support multiple field names for id, name and role
                    const staffId = staff.staff_id || staff.id || staff.staffId || staff.ID;
                    const staffName = staff.full_name || staff.fullName || staff.name || staff.staff_name || staff.username;
                    const staffRole = (staff.role || staff.position || staff.job_title || '').toString().toLowerCase();
                    if (staffRole === role.toString().toLowerCase()) {
                        select.append(`<option value=\"${staffId}\">${staffName}</option>`);
                    }
                });
            });
        }

        // Allow custom close button to hide the service report list modal
        $(document).on('click', '.close-modal-report', function(e) {
            e.preventDefault();
            $('#serviceReportListModal').modal('hide');
            setTimeout(() => { $('.modal-backdrop').remove(); $('body').removeClass('modal-open'); }, 200);
        });

        // Use event delegation for action buttons; hide the list modal when an action is clicked
        $(document).on('click', '.edit-report', function(e) {
            e.preventDefault();
            e.stopPropagation();
            const reportId = $(this).data('id');
            console.log('Edit button clicked for report ID:', reportId);
            
            // Force close the modal with multiple methods
            const $modal = $('#serviceReportListModal');
            $modal.modal('hide');
            $modal.removeClass('show');
            $modal.css('display', 'none');
            $modal.attr('aria-hidden', 'true');
            
            // Immediate and delayed cleanup of modal backdrop and body classes
            $('.modal-backdrop').remove();
            $('body').removeClass('modal-open').css({
                'overflow': '',
                'padding-right': ''
            });
            
            // Additional cleanup after brief delay
            setTimeout(() => { 
                $('.modal-backdrop').remove(); 
                $('body').removeClass('modal-open').css({
                    'overflow': '',
                    'padding-right': ''
                });
                console.log('Modal forcefully closed and cleaned up');
            }, 100);
            
            // Load report for editing
            loadReportForEditing(reportId);
        });

        $(document).on('click', '.delete-report', function(e) {
            e.preventDefault();
            const reportId = $(this).data('id');
            $('#serviceReportListModal').modal('hide');
            setTimeout(() => { $('.modal-backdrop').remove(); $('body').removeClass('modal-open'); }, 300);
            deleteReport(reportId);
        });

        // Assign-to-me button handler
        $(document).on('click', '.assign-report', function(e) {
            e.preventDefault();
            const reportId = $(this).data('id');
            const techName = window.currentStaffName || '';
            if (!techName) {
                showAlert('error', 'Cannot determine your staff name for assignment');
                return;
            }

            if (!confirm('Assign report #' + reportId + ' to you (' + techName + ')?')) return;

            $.ajax({
                url: '../backend/api/service_api.php?action=assign',
                method: 'POST',
                contentType: 'application/json',
                data: JSON.stringify({ report_id: reportId, technician: techName }),
                success: function(res) {
                    if (res && res.success) {
                        showAlert('success', 'Report assigned successfully');
                        loadServiceReports();
                        // notify other tabs/dashboards
                        try { localStorage.setItem('dashboardRefreshNeeded', Date.now()); } catch (e) {}
                    } else {
                        showAlert('error', 'Assign failed: ' + (res.message || 'Unknown'));
                    }
                },
                error: function(xhr) {
                    showAlert('error', 'Assign request failed');
                }
            });
        });

        // Service report list search input
        $(document).on('input', '#service-report-search', function() {
            filterServiceReports($(this).val());
        });

        $(document).on('change', '#service-report-filter', function() {
            // Re-render based on the selected status filter and current search query
            const q = $('#service-report-search').val();
            applyStatusAndSearch();
            if (q) {
                filterServiceReports(q);
            }
        });

        // Modal on show - load service reports
        $('#serviceReportListModal').on('show.bs.modal', loadServiceReports);

        // Ensure modal closes properly when clicking close button or backdrop
        $('#serviceReportListModal').on('hidden.bs.modal', function() {
            $('body').removeClass('modal-open');
        });

        function addPartRow() {
            const partSelect = $('<select class="form-control part-select" name="part_used[]"><option value="">Select Part</option></select>');
            
            const parts = $('body').data('parts') || [];
            parts.forEach(function(part) {
                const pid = part.part_id || part.id || part.partId || part.ID;
                const price = part.price || part.part_price || 0;
                const label = part.description || part.part_no || part.name || part.part_name || `Part ${pid}`;
                partSelect.append(`<option value="${pid}" data-price="${price}">${label}</option>`);
            });

            const row = `
                <div class="row mb-2 align-items-center g-2 parts-row">
                    <div class="col-md-3 pe-1">
                        ${partSelect[0].outerHTML}
                    </div>
                    <div class="col-md-2 px-1">
                        <input type="number" class="form-control quantity-input" name="quantity[]" placeholder="Quantity" min="1">
                    </div>
                    <div class="col-md-2 px-1">
                        <input type="number" class="form-control amount-input" name="amount[]" placeholder="Amount" readonly>
                    </div>
                    <div class="col-md-1 px-1 d-flex justify-content-end align-items-center ml-3">
                        <button type="button" class="btn btn-danger remove-part d-flex align-items-center justify-content-center">
                            <span class="material-icons" style="font-size: 1.2em;">delete</span>
                        </button>
                    </div>
                </div>
            `;

            $('#parts-container').append(row);
        }

        function calculateTotals() {
            // Sum part amounts (amount-input already holds price * qty)
            let partsTotal = 0;
            $('.parts-row').each(function() {
                const amount = parseFloat($(this).find('.amount-input').val() || 0) || 0;
                partsTotal += amount;
            });

            // Update parts charge display
            $('[name="parts_charge"]').val(partsTotal.toFixed(2));

            // Service charge (could be a separate field); read it as numeric
            const serviceCharge = parseFloat($('#total-serviceCharge').val() || 0) || 0;
            const labor = parseFloat($('#labor-amount').val() || 0) || 0;
            const pullout = parseFloat($('#pullout-delivery').val() || 0) || 0;

            // Grand total = service charge + labor + pullout + parts total
            const grandTotal = serviceCharge + labor + pullout + partsTotal;

            // Update total amount display fields
            $('#total-amount').val(grandTotal.toFixed(2));
            $('#total-amount-2').val(grandTotal.toFixed(2));
        }

        function submitServiceReport() {
            // Build a JSON payload because the API expects JSON in the body
            const payload = {};

            // Basic fields
            payload.customer_name = $('#customer-select').find('option:selected').text() || $('#customer-select').val();
            payload.customer_id = $('#customer-select').val();
            payload.appliance_name = $('#appliance-select').find('option:selected').text() || $('#appliance-select').val();
            payload.appliance_id = $('#appliance-select').val();
            payload.date_in = $('#date-in').val();
            payload.status = $('#create_status').val() || $('select[name="status"]').first().val();
            payload.dealer = $('input[name="dealer"]').val() || '';
            payload.dop = $('input[name="dop"]').val() || null;
            payload.date_pulled_out = $('input[name="date_pulled_out"]').val() || null;
            payload.findings = $('input[name="findings"]').val() || '';
            payload.remarks = $('input[name="remarks"]').val() || '';
            payload.location = ['shop'];

            // Service types from checkboxes
            const serviceTypes = [];
            if ($('#installation').is(':checked')) serviceTypes.push('installation');
            if ($('#repair').is(':checked')) serviceTypes.push('repair');
            if ($('#cleaning').is(':checked')) serviceTypes.push('cleaning');
            if ($('#checkup').is(':checked')) serviceTypes.push('checkup');
            if (serviceTypes.length === 0) serviceTypes.push('repair');
            payload.service_types = serviceTypes;

            // Charges
            payload.service_charge = parseFloat($('#total-serviceCharge').val() || 0) || 0;
            payload.labor = parseFloat($('#labor-amount').val() || 0) || 0;
            payload.pullout_delivery = parseFloat($('#pullout-delivery').val() || 0) || 0;
            payload.parts_total_charge = parseFloat($('[name="parts_charge"]').val() || 0) || 0;
            // total amount (service + labor + parts + pullout)
            payload.total_amount = parseFloat($('#total-amount').val() || 0) || 0;

            // Dates
            payload.date_repaired = $('input[name="date_repaired"]').val() || null;
            payload.date_delivered = $('input[name="date_delivered"]').val() || null;

            // Complaint
            payload.complaint = $('#complaint').val() || '';

            // Staff selections
            payload.receptionist = $('#receptionist-select').val() || '';
            payload.manager = $('#manager-select').val() || '';
            payload.technician = $('#technician-select').val() || '';
            payload.released_by = $('#released-by-select').val() || '';

            // Parts array: gather each parts-row and compute parts total
            payload.parts = [];
            let partsTotal = 0;
            $('#parts-container .parts-row').each(function() {
                const partId = $(this).find('.part-select').val();
                const partName = $(this).find('.part-select option:selected').text();
                const qty = parseInt($(this).find('.quantity-input').val() || 0, 10) || 0;
                const unitPrice = parseFloat($(this).find('.part-select option:selected').data('price') || 0) || 0;
                const rowAmount = parseFloat($(this).find('.amount-input').val() || 0) || (unitPrice * qty);
                partsTotal += rowAmount;
                if (partId && qty > 0) {
                    payload.parts.push({
                        part_id: partId,
                        part_name: partName,
                        quantity: qty,
                        unit_price: unitPrice
                    });
                }
            });

            // Refresh parts charge and compute grand total from numeric components to avoid mismatch
            payload.parts_total_charge = partsTotal;
            payload.service_charge = parseFloat($('#total-serviceCharge').val() || 0) || 0;
            payload.labor = parseFloat($('#labor-amount').val() || 0) || 0;
            payload.pullout_delivery = parseFloat($('#pullout-delivery').val() || 0) || 0;
            payload.total_amount = payload.service_charge + payload.labor + payload.pullout_delivery + partsTotal;

            // Make sure required fields exist to avoid 400
            if (!payload.customer_name || !payload.appliance_name || !payload.date_in || !payload.status) {
                showAlert('error', 'Please fill required fields: customer, appliance, date in, status');
                return;
            }

            // Check if this is an update or create
            const reportId = $('#report_id').val();
            const action = reportId ? 'update' : 'create';
            const method = reportId ? 'PUT' : 'POST';
            const url = reportId ? 
                ('../backend/api/service_api.php?action=update&id=' + reportId) : 
                '../backend/api/service_api.php?action=create';

            $.ajax({
                url: url,
                method: method,
                data: JSON.stringify(payload),
                contentType: 'application/json; charset=utf-8',
                dataType: 'json',
                success: function(response) {
                    const successMsg = reportId ? 'Service report updated successfully!' : 'Service report created successfully!';
                    showAlert('success', successMsg);
                    $('#serviceReportForm')[0].reset();
                    $('#report_id').val('');
                    $('#submit-report-btn').text('Create Report').css('background-color', '#0066e6');
                    loadCustomers();
                    loadServiceReports(); // Refresh the list
                    $('#appliance-select').empty().append('<option value="">Select Appliance</option>');
                    
                    // Update dashboard in real-time
                    updateDashboardData();
                },
                error: function(xhr) {
                    let msg = reportId ? 'Error updating service report' : 'Error creating service report';
                    try {
                        const res = xhr.responseJSON || JSON.parse(xhr.responseText || '{}');
                        msg = res.message || msg;
                    } catch (e) {
                        // ignore parse errors
                    }
                    showAlert('error', msg);
                    console.error('Service create error response:', xhr.responseText);
                }
            });
        }

        // Load report for editing (populate the form with existing data)
        function loadReportForEditing(reportId) {
            console.log('Starting loadReportForEditing for reportId:', reportId);
            
            $.ajax({
                url: '../backend/api/service_api.php?action=getById&id=' + reportId,
                method: 'GET',
                dataType: 'json',
                success: function(res) {
                    console.log('Raw API Response:', res);
                    if (res.success && res.data) {
                        const report = res.data;
                        console.log('Report data received:', report);
                        
                        // Populate form with data immediately
                        console.log('Populating form with data...');
                            
                            // Set report ID
                            $('#report_id').val(reportId);
                            console.log('Report ID set to:', reportId);
                            
                            // Set customer - try ID first, then fallback to name matching
                            const custId = report.customer_id || report.cust_id || report.customer;
                            console.log('Customer ID from report:', custId, 'Customer name:', report.customer_name);
                            let selectedCustId = null;
                            
                            // Try to set by ID first
                            if (custId) {
                                $('#customer-select').val(custId);
                                selectedCustId = $('#customer-select').val(); // Get the actual value after setting
                                console.log('Customer ID set to:', custId, 'Selected value:', selectedCustId);
                            }
                            
                            // If ID didn't work, find option by name
                            if (!selectedCustId && report.customer_name) {
                                $('#customer-select option').each(function() {
                                    if ($(this).text().includes(report.customer_name)) {
                                        $(this).prop('selected', true);
                                        selectedCustId = $(this).val();
                                        console.log('Customer selected by name:', report.customer_name, 'ID:', selectedCustId);
                                        return false;
                                    }
                                });
                            }
                            
                            // Set basic fields
                            $('#date-in').val(report.date_in || '');
                            
                            // Set status with validation
                            const statusValue = report.status || '';
                            console.log('Setting status to:', statusValue);
                            $('select[name="status"]').val(statusValue);
                            if ($('select[name="status"]').val() !== statusValue && statusValue) {
                                console.warn('Status value not found in dropdown options:', statusValue);
                            }
                            
                            $('input[name="dealer"]').val(report.dealer || '');
                            $('input[name="dop"]').val(report.dop || '');
                            $('input[name="date_pulled_out"]').val(report.date_pulled_out || '');
                            $('input[name="findings"]').val(report.findings || '');
                            $('input[name="remarks"]').val(report.remarks || '');
                            console.log('Basic fields populated');
                            
                            // Populate complaint and charges
                            $('#complaint').val(report.complaint || '');
                            $('#labor-amount').val(parseFloat(report.labor) || 0);
                            $('#pullout-delivery').val(parseFloat(report.pullout_delivery) || 0);
                            $('input[name="date_repaired"]').val(report.date_repaired || '');
                            $('input[name="date_delivered"]').val(report.date_delivered || '');
                            console.log('Complaint and charges populated');
                            
                            // Populate location checkboxes (shop, field, out_wty)
                            $('#shop, #field, #out_wty').prop('checked', false);
                            console.log('Location data:', report.location);
                            if (report.location && Array.isArray(report.location)) {
                                report.location.forEach(function(loc) {
                                    const locId = loc.toLowerCase().trim();
                                    console.log('Checking location:', locId);
                                    $('#' + locId).prop('checked', true);
                                });
                            }
                            console.log('Location checkboxes populated');
                            
                            // Clear and populate service type checkboxes
                            $('#installation, #repair, #cleaning, #checkup').prop('checked', false);
                            console.log('Service types before population:', report.service_types);
                            
                            if (report.service_types) {
                                let typesToCheck = [];
                                if (Array.isArray(report.service_types)) {
                                    typesToCheck = report.service_types;
                                } else if (typeof report.service_types === 'string') {
                                    typesToCheck = report.service_types.split(',').map(t => t.trim());
                                }
                                
                                console.log('Types to check:', typesToCheck);
                                typesToCheck.forEach(function(type) {
                                    const typeId = type.toLowerCase().trim();
                                    console.log('Checking:', typeId);
                                    $('#' + typeId).prop('checked', true);
                                });
                            }
                            console.log('Service type checkboxes populated');
                            
                            // Populate staff by matching names (backend now returns 'username (Role)' format)
                            console.log('Staff data - receptionist:', report.receptionist, 'manager:', report.manager, 'technician:', report.technician, 'released_by:', report.released_by);
                            
                            // Set staff dropdowns using the name matching function
                            if (report.receptionist) {
                                setDropdownValueByText('#receptionist-select', report.receptionist);
                                console.log('Receptionist set to:', report.receptionist);
                            }
                            
                            // For manager
                            if (report.manager) {
                                setDropdownValueByText('#manager-select', report.manager);
                                console.log('Manager set to:', report.manager);
                            }
                            
                            // For technician
                            if (report.technician) {
                                setDropdownValueByText('#technician-select', report.technician);
                                console.log('Technician set to:', report.technician);
                            }
                            
                            // For released by
                            if (report.released_by) {
                                setDropdownValueByText('#released-by-select', report.released_by);
                                console.log('Released by set to:', report.released_by);
                            }
                            console.log('Staff selections populated');
                            
                            // Load appliances for customer
                            const finalCustId = selectedCustId || custId || $('#customer-select').val();
                            console.log('Final customer ID for loading appliances:', finalCustId);
                            
                            if (finalCustId) {
                                const appId = report.appliance_id || report.app_id;
                                console.log('Appliance ID to select:', appId, 'Appliance name:', report.appliance_name);
                                
                                // Load appliances and auto-select the correct one
                                loadAppliances(finalCustId, appId).done(function() {
                                    console.log('Appliances loaded, checking selection...');
                                    const currentVal = $('#appliance-select').val();
                                    console.log('Current appliance select value:', currentVal);
                                    
                                    // If ID selection didn't work, try by name
                                    if (!currentVal || (appId && currentVal != appId)) {
                                        if (report.appliance_name) {
                                            console.log('Trying to select by name:', report.appliance_name);
                                            let found = false;
                                            $('#appliance-select option').each(function() {
                                                const optionText = $(this).text();
                                                if (optionText.includes(report.appliance_name) || report.appliance_name.includes(optionText)) {
                                                    $(this).prop('selected', true);
                                                    console.log('Appliance selected by name match:', optionText);
                                                    found = true;
                                                    return false;
                                                }
                                            });
                                            if (!found) {
                                                console.warn('Could not find appliance by name:', report.appliance_name);
                                            }
                                        }
                                    } else {
                                        console.log('Appliance successfully selected by ID:', currentVal);
                                    }
                                }).fail(function(error) {
                                    console.error('Failed to load appliances:', error);
                                });
                            } else {
                                console.warn('No customer ID available to load appliances');
                            }
                            
                            // Populate parts
                            if (report.parts && Array.isArray(report.parts) && report.parts.length > 0) {
                                console.log('Populating parts:', report.parts);
                                $('#parts-container .parts-row:not(:first)').remove();
                                
                                const firstRow = $('#parts-container .parts-row:first');
                                report.parts.forEach((part, index) => {
                                    let row = firstRow;
                                    if (index > 0) {
                                        row = firstRow.clone(true, true);
                                        $('#parts-container').append(row);
                                    }
                                    row.find('.part-select').val(part.part_id || part.id || '');
                                    row.find('.quantity-input').val(part.quantity || 0);
                                    row.find('.amount-input').val((part.quantity * (part.unit_price || part.price || 0)).toFixed(2));
                                });
                            }
                            
                            // Calculate totals
                            calculateTotals();
                            console.log('Totals calculated');
                            
                            // Update button
                            $('#submit-report-btn').text('Update Report').css('background-color', '#28a745');
                            
                            showAlert('success', 'Report loaded for editing');
                            console.log('Form population complete');
                            
                            // Scroll to top
                            $('html, body').animate({ scrollTop: 0 }, 'smooth');
                    } else {
                        console.error('Failed response:', res);
                        showAlert('error', 'Failed to load report: ' + (res.message || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX Error:', status, error);
                    console.error('Response text:', xhr.responseText);
                    showAlert('error', 'Error loading report');
                }
            });
        }

        // Filter service reports based on search query and status
        let allServiceReports = [];

        function filterServiceReports(query) {
            const status = $('#service-report-filter').val();
            const searchTerm = query.toLowerCase();
            
            let filtered = allServiceReports;
            
            // Filter by status
            if (status !== 'All') {
                filtered = filtered.filter(report => {
                    let reportStatus = report.status;
                    // Map numeric status codes to text
                    if (typeof reportStatus === 'number' || /^\d+$/.test(reportStatus)) {
                        const statusMap = {'0': 'Completed', '1': 'Pending', '2': 'Under Repair', '3': 'Unrepairable', '4': 'Release Out'};
                        reportStatus = statusMap[String(reportStatus)] || reportStatus;
                    }
                    return reportStatus === status;
                });
            }
            
            // Filter by search term
            if (searchTerm) {
                filtered = filtered.filter(report => {
                    const reportId = String(report.report_id || '').toLowerCase();
                    const customer = String(report.customer_name || '').toLowerCase();
                    const appliance = String(report.appliance_name || '').toLowerCase();
                    const serviceTypes = String(report.service_types || '').toLowerCase();
                    
                    return reportId.includes(searchTerm) || 
                           customer.includes(searchTerm) || 
                           appliance.includes(searchTerm) || 
                           serviceTypes.includes(searchTerm);
                });
            }
            
            renderServiceReports(filtered);
        }

        function applyStatusAndSearch() {
            const status = $('#service-report-filter').val();
            const searchTerm = $('#service-report-search').val().toLowerCase();
            
            let filtered = allServiceReports;
            
            // Filter by status
            if (status !== 'All') {
                filtered = filtered.filter(report => {
                    let reportStatus = report.status;
                    // Map numeric status codes to text
                    if (typeof reportStatus === 'number' || /^\d+$/.test(reportStatus)) {
                        const statusMap = {'0': 'Completed', '1': 'Pending', '2': 'Under Repair', '3': 'Unrepairable', '4': 'Release Out'};
                        reportStatus = statusMap[String(reportStatus)] || reportStatus;
                    }
                    return reportStatus === status;
                });
            }
            
            // Filter by search term
            if (searchTerm) {
                filtered = filtered.filter(report => {
                    const reportId = String(report.report_id || '').toLowerCase();
                    const customer = String(report.customer_name || '').toLowerCase();
                    const appliance = String(report.appliance_name || '').toLowerCase();
                    const serviceTypes = String(report.service_types || '').toLowerCase();
                    
                    return reportId.includes(searchTerm) || 
                           customer.includes(searchTerm) || 
                           appliance.includes(searchTerm) || 
                           serviceTypes.includes(searchTerm);
                });
            }
            
            renderServiceReports(filtered);
        }

        function renderServiceReports(reports) {
            const $tbody = $('#serviceReportListModal tbody');
            $tbody.empty();
            
            if (!Array.isArray(reports) || reports.length === 0) {
                $tbody.append('<tr><td colspan="8">No reports found</td></tr>');
                return;
            }
            
            reports.forEach(function(report) {
                const serviceTypes = Array.isArray(report.service_types) ? report.service_types.join(', ') : (report.service_types || 'N/A');
                const dateIn = report.date_in ? new Date(report.date_in).toLocaleDateString() : 'N/A';
                
                // Map numeric status codes to text (for legacy data compatibility)
                let statusText = report.status;
                if (typeof statusText === 'number' || /^\d+$/.test(statusText)) {
                    const statusMap = {
                        '0': 'Completed',
                        '1': 'Pending',
                        '2': 'Under Repair',
                        '3': 'Unrepairable',
                        '4': 'Release Out'
                    };
                    statusText = statusMap[String(statusText)] || statusText;
                }
                
                // Status badge with same styling as admin
                let statusBadge = '';
                switch (statusText) {
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
                        statusBadge = `<span class="badge badge-light">${statusText || 'N/A'}</span>`;
                }
                
                // show assign to me button when pending and not assigned
                let assignBtn = '';
                if (report.status === 'Pending' && (!report.technician || report.technician === '')) {
                    assignBtn = `<a href="#" class="assign-report" data-id="${report.report_id}" title="Assign to me"><i class="material-icons text-info">person_add</i></a>`;
                }
                
                $tbody.append(`
                    <tr>
                        <td>${report.report_id || ''}</td>
                        <td>${report.customer_name || ''}</td>
                        <td>${report.appliance_name || ''}</td>
                        <td>${serviceTypes}</td>
                        <td>${dateIn}</td>
                        <td>${statusBadge}</td>
                        <td class="actions-col">
                            <a href="#" class="edit-report" data-id="${report.report_id}"><i class="material-icons text-primary">edit</i></a>
                            <a href="#" class="delete-report" data-id="${report.report_id}"><i class="material-icons text-danger">delete</i></a>
                            ${assignBtn}
                        </td>
                    </tr>
                `);
            });
        }

        // Delete report
        function deleteReport(reportId) {
            $.ajax({
                url: '../backend/api/service_api.php?action=delete&id=' + reportId,
                method: 'DELETE',
                dataType: 'json',
                success: function(res) {
                    if (res.success) {
                        showAlert('success', 'Report deleted successfully');
                        loadServiceReports(); // Refresh the list
                    } else {
                        showAlert('error', 'Failed to delete report: ' + (res.message || 'Unknown error'));
                    }
                },
                error: function(xhr) {
                    console.error('Error deleting report:', xhr.responseText);
                    showAlert('error', 'Error deleting report');
                }
            });
        }
        
        // Function to update dashboard data in real-time
        function updateDashboardData() {
            $.ajax({
                url: '../backend/api/dashboard_api.php?action=getAll',
                method: 'GET',
                dataType: 'json',
                success: function(res) {
                    console.log('Dashboard data updated:', res);
                    if (res.success && res.data) {
                        // Update assigned reports card
                        const assigned = res.data.assignedReports || {};
                        const assignedTotal = assigned.total || 0;
                        const assignedChange = assigned.weekly_change || 0;
                        
                        // Try to update if dashboard elements exist
                        if (parent.document && parent.document.getElementById) {
                            // Update in parent/dashboard window
                            const assignedCard = parent.document.querySelector('[data-card="assigned"]');
                            if (assignedCard) {
                                assignedCard.querySelector('h2').textContent = assignedTotal;
                                assignedCard.querySelector('.growth').textContent = (assignedChange >= 0 ? '+' : '') + assignedChange + ' this week';
                            }
                            
                            // Update pending orders card
                            const pending = res.data.pendingOrders || {};
                            const pendingTotal = pending.total || 0;
                            const pendingChange = pending.daily_change || 0;
                            const pendingCard = parent.document.querySelector('[data-card="pending"]');
                            if (pendingCard) {
                                pendingCard.querySelector('h2').textContent = pendingTotal;
                                pendingCard.querySelector('.growth').textContent = (pendingChange >= 0 ? '+' : '') + pendingChange + ' today';
                            }
                            
                            // Update completed services card
                            const completed = res.data.completedServices || {};
                            const completedTotal = completed.total || 0;
                            const completedChange = completed.daily_change || 0;
                            const completedCard = parent.document.querySelector('[data-card="completed"]');
                            if (completedCard) {
                                completedCard.querySelector('h2').textContent = completedTotal;
                                completedCard.querySelector('.growth').textContent = (completedChange >= 0 ? '+' : '') + completedChange + ' today';
                            }
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error updating dashboard:', error);
                }
            });
            
            // Also broadcast to dashboard if it's in localStorage (for cross-tab communication)
            if (localStorage) {
                localStorage.setItem('dashboardRefreshNeeded', JSON.stringify({
                    timestamp: new Date().getTime(),
                    action: 'refresh'
                }));
            }
        }
    </script>
</body>
</html>

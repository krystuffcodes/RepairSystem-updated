<?php
require_once __DIR__ . '/../bootstrap.php';
session_start();
require __DIR__ . '/../backend/handlers/authHandler.php';
$auth = new AuthHandler();
$userSession = $auth->requireAuth('staff');
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
            /* Adjust as needed */
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

        /* New styles for the Part Used section */
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

        /* Fix modal positioning */
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
        
        /* Tab styles */
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
        
        /* Transaction table styles */
        .header-actions {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .search-box {
            position: relative;
        }
        
        .search-icon {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: #6c757d;
        }
        
        .search-box input {
            padding-left: 40px;
        }
        
        .filter-container {
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
            align-items: flex-end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            margin-bottom: 5px;
            font-size: 14px;
            font-weight: 500;
        }
        
        .btn-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        
        .btn-action:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }
        
        .btn-view {
            color: #17a2b8;
        }
        
        .btn-edit {
            color: #28a745;
        }
        
        .btn-delete {
            color: #dc3545;
        }
        
        /* Modal header actions */
        .modal-header-actions {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .print-btn {
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 5px;
            border-radius: 4px;
            transition: background-color 0.2s;
        }
        
        .print-btn:hover {
            background-color: rgba(0, 0, 0, 0.05);
        }
        
        /* Transaction details styles */
        .transaction-details .form-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
        }
        
        .transaction-details .form-group {
            flex: 1;
        }
        
        .info-label {
            font-size: 14px;
            color: #6c757d;
            margin-bottom: 5px;
        }
        
        .info-value {
            font-size: 16px;
            font-weight: 500;
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
    
    /* Make modal title black */
    .modal-title {
        color: #000000 !important;
        font-weight: 600;
    }
    
    /* Filter Controls Styles */
    .filter-controls {
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }
    
    .filter-controls select {
        min-width: 150px;
    }
    
    .sort-controls {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    
    .sort-controls .form-group {
        margin-bottom: 0;
    }
    
    .sort-controls label {
        margin-bottom: 0;
        margin-right: 5px;
        font-weight: 500;
    }
    
    /* Improved filter alignment */
    .filter-container {
        display: flex;
        align-items: center;
        gap: 15px;
        flex-wrap: wrap;
    }
    
    .filter-group {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .filter-group label {
        margin-bottom: 0;
        font-weight: 500;
        white-space: nowrap;
    }
    
    /* Print button styling */
    .print-header-btn {
        margin-left: auto;
    }
    
    /* Findings Section Styles */
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
    
    /* Print Styles */
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
    
    /* Ensure all form elements are readonly by default */
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
    
   .status-badge {
    display: inline-block;
    padding: 0.25em 1em;
    border-radius: 999px;
    font-size: 0.95em;
    font-weight: 500;
    color: #fff;
    min-width: 70px;
    text-align: center;
    letter-spacing: 1px;
}

.status-paid {
    
    color: #28a745;
}

.status-pending {
            color: #ffc107;
}
    /* Pagination footer */
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
    
    /* Transaction list print section */
    .transaction-list-print {
        display: none;
    }
    
    @media print {
        .transaction-list-print {
            display: block;
        }

        .transaction-list-print .card {
            page-break-inside: avoid;
        }
    }
    </style>
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
                <!-- tabs -->
                <div class="tabs">
                    <div class="tab active" data-tab="reports"> Service Reports</div>
                    <div class="tab" data-tab="transactions">Service Transactions</div>
                </div>
                
                <!-- Service Reports Tab (Admin-style form) -->
                <div class="tab-content active" id="reportsTab">
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
                                            <select class="form-control customer-select" name="customer" id="customer-select">
                                                <option value="">Select Customer</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label>Appliance</label>
                                            <select class="form-control appliance-select" name="appliance" id="appliance-select">
                                                <option>Select Appliance</option>
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label>Date In</label>
                                            <input type="date" class="form-control" name="date_in" id="date-in">
                                        </div>
                                        <div class="col-md-3">
                                            <label>Status</label>
                                            <select class="form-control" name="status" required>
                                                <option value="">Select Status</option>
                                                <option value="Pending">Pending</option>
                                                <option value="Completed">Completed</option>
                                            </select>
                                        </div>
                                    </div>
                                    <!-- Second Row: Dealer, DOP, Date Pulled-Out, Service Type -->
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
                                    
                                    <!-- Part Used Section with Border -->
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
                                    <!-- Checkboxes Row with Border -->
                                    
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
                                    
                                    <!-- Total Amount and Date Repaired Row -->
                                    <div class="row mb-2">
                                        <div class="col-md-8 pe-1">
                                            <label>Total Service Charge</label>
                                            <div class="input-group mb-0">
                                                <span class="input-group-text">₱</span>
                                                <input type="text" class="form-control" name="total_serviceCharge" id="total-serviceCharge" readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-8 pe-1">
                                            <label>Total Amount</label>
                                            <div class="input-group mb-0">
                                                <span class="input-group-text">₱</span>
                                                <input type="text" class="form-control" name="total_amount" id="total-amount" readonly>
                                            </div>
                                        </div>
                                        <div class="col-md-4 ps-1">
                                            <label>Date Repaired</label>
                                            <input type="date" class="form-control" name="date_repaired">
                                        </div>
                                    </div>
                                    <!-- Complaint and Date Delivered Row -->
                                    <div class="row mb-2">
                                        <div class="col-md-8 pe-1">
                                            <label>Complaint</label>
                                            <textarea class="form-control" name="complaint" id="complaint" rows="2"></textarea>
                                        </div>
                                        <div class="col-md-4 ps-1">
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
                
                <!-- Transactions Tab (Copied from admin's transactions.php) -->
                <div class="tab-content" id="transactionsTab">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">Transaction List</h5>
                            <div class="filter-container">
                                
                                <!-- For Date -->
                                <div class="filter-group">
                                    <label for="dateSort">Sort by:</label>
                                    <select id="dateSort" class="form-control">
                                        <option value="">Select</option>
                                        <option value="latest">Latest</option>
                                        <option value="oldest">Oldest</option>
                                    </select>
                                </div>
                                
                                <!-- Amount -->
                                <div class="filter-group">
                                    <label for="amountSort">Amount:</label>
                                    <select id="amountSort" class="form-control">
                                        <option value="">Select</option>
                                        <option value="highest">Highest</option>
                                        <option value="lowest">Lowest</option>
                                    </select>
                                </div>
                                
                                <!-- Status -->
                                <div class="filter-group">
                                    <label for="filterBy">Filter by:</label>
                                    <select id="filterBy" class="form-control">
                                        <option value="all">Select</option>
                                        <option value="Paid">Paid Only</option>
                                        <option value="Pending">Pending Only</option>
                                    </select>
                                </div>
                                
                                <!-- Print Button -->
                                <button type="button" class="btn btn-light border print-header-btn no-print print-transactions-btn" style="font-weight: 500;">
                                    <i class="material-icons align-middle">print</i> Print List
                                </button>
                            </div>
                        </div>

                        <!-- Card Body -->
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped" id="transactionsTable">
                                    <thead>
                                        <tr>
                                            <th>Transaction ID</th>
                                            <th>Customer</th>
                                            <th>Appliance</th>
                                            <th>Total Amount</th>
                                            <th>Payment Status</th>
                                            <th>Payment Date</th>
                                            <th>Received By</th>
                                            <th class="no-print">Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="transactionsTableBody">
                                        <!-- Transactions will be loaded dynamically -->
                                    </tbody>
                                </table>
                            </div>
                            <div class="d-flex justify-content-between align-items-center p-3 border-top">
                                <div class="text-muted">
                                    <span id="txPaginationInfo">Showing 0 to 0 of 0 entries</span>
                                </div>
                                <nav aria-label="Transactions navigation">
                                    <ul class="pagination pagination-sm mb-0" id="txPagination"></ul>
                                </nav>
                            </div>
                        </div>
                    </div>
                </div>
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

    <!-- Service Report List Modal -->
    <div class="modal fade" id="serviceReportListModal" tabindex="-1" aria-labelledby="serviceReportListModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered" style="max-width: 1200px; width: 95%;">
                <div class="modal-content" style="border-radius: 18px;">
                    <div class="modal-header justify-content-center">
                        <h4 class="modal-title w-100 text-center" id="serviceReportListModalLabel">Service Report List</h4>
                        <button type="button" class="close position-absolute" style="right: 20px; top: 18px;" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body p-0">
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
                                        <th>Total</th>
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

    <!-- Transaction Details Modal -->
    <div class="modal fade" id="transactionDetailsModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Transaction Details</h5>
                    <div class="modal-header-actions">
                        <button class="print-btn" id="printTransaction">
                            <i class="material-icons">print</i>
                        </button>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
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
                        <div class="form-row">
                            <div class="form-group">
                                <div class="info-label">Service Type</div>
                                <div class="info-value">Appliance Repair</div>
                            </div>
                            <div class="form-group">
                                <div class="info-label">Amount</div>
                                <div class="info-value">₱1,500.00</div>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-group">
                                <div class="info-label">Date</div>
                                <div class="info-value">2023-10-15</div>
                            </div>
                            <div class="form-group">
                                <div class="info-label">Status</div>
                                <div class="info-value"><span class="badge badge-success">Paid</span></div>
                            </div>
                        </div>
                        <div class="form-group">
                            <div class="info-label">Description</div>
                            <div class="info-value">Repair of refrigerator compressor and replacement of faulty parts.</div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="editTransaction">Edit</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden print section for transaction list -->
    <div class="transaction-list-print print-section">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Transaction List Report</h5>
                    <span id="print-date"></span>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-bordered" id="transactionsPrintTable">
                        <thead>
                            <tr>
                                <th>Transaction ID</th>
                                <th>Customer</th>
                                <th>Appliance</th>
                                <th>Total Amount</th>
                                <th>Payment Status</th>
                                <th>Payment Date</th>
                                <th>Received By</th>
                            </tr>
                        </thead>
                        <tbody id="transactionsPrintTableBody">
                            <!-- Will be populated by JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Update Payment Modal -->
    <div class="modal fade" id="updatePaymentModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <form id="updatePaymentForm">
                    <div class="modal-header">
                        <h4 class="modal-title">Update Payment Status</h4>
                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="transaction_id" id="update_transaction_id">
                        <div class="form-group">
                            <label>Payment Status</label>
                            <select name="payment_status" class="form-control" required>
                                <option value="Paid">Paid</option>
                                <option value="Pending">Pending</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Received By</label>
                            <select name="received_by" class="form-control" required>
                                <option value="">Select Staff</option>
                                <!-- Staff options will be populated dynamically -->
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Transaction Form Modal -->
    <div class="modal fade" id="transactionFormModal" tabindex="-1" role="dialog" aria-labelledby="transactionFormModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xxl" role="document">
            <div class="modal-content print-section">                   
                <div class="modal-header bg-light d-flex justify-content-between align-items-center" style="border-bottom: 1px solid #ccc;">
                    <div class="d-flex align-items-center w-100">
                        <h5 class="modal-title mb-0 " id="transactionFormModalLabel">Transaction Form</h5>
                    </div>
                    <div class="d-flex align-items-center no-print">
                        <button type="button" class="btn btn-warning border mr-2 no-print edit-btn" style="font-weight: 500;">
                            <i class="material-icons align-middle">edit</i> Edit Report
                        </button>
                        <button type="button" class="btn btn-light border mr-2 no-print print-btn" style="font-weight: 500;">
                            <i class="material-icons align-middle">print</i> Print
                        </button>
                        <button type="button" class="btn btn-success border mr-2 no-print finalize-edit-btn" style="font-weight: 500; display: none;">
                            <i class="material-icons align-middle">save</i> Finalize Edit
                        </button>
                    </div>
                    <button type="button" class="close no-print" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="transactionForm">
                    <input type="hidden" name="report_id" id="update_report_id">
                        <div class="container-fluid">
                            <div class="row mb-2">
                                <div class="col-md-3">
                                    <label>Customer</label>
                                    <input type="text" class="form-control" name="customer" id="customer-field" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label>Appliance</label>
                                    <input type="text" class="form-control" name="appliance" id="appliance-field" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label>Date In</label>
                                    <input type="text" class="form-control" name="date_in" id="date-in-field" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label>Status</label>
                                    <!-- <input type="text" class="form-control" name="status" readonly>  -->
                                        <select name="status" id="status-field" class="form-control" readonly>
                                        <option value="Pending">Pending</option>
                                        <option value="Completed">Completed</option>
                                        </select>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-3">
                                    <label>Dealer</label>
                                    <input type="text" class="form-control" name="dealer" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label>Date</label>
                                    <input type="text" class="form-control" name="dop" readonly>
                                </div>
                                <div class="col-md-3">
                                    <label>Date Pulled - Out</label>
                                    <input type="text" class="form-control" name="date_pulled_out" readonly>
                                </div>
                            </div>
                            
                            <!-- Fixed Findings Section -->
                            <div class="findings-section">
                                <div class="findings-header">
                                    <div>Findings</div>
                                    <div class="text-center">Shop</div>
                                    <div class="text-center">Field</div>
                                    <div class="text-center">Out WTY</div>
                                </div>
                                <div class="findings-row">
                                    <input type="text" class="form-control" name="findings" id="findings-field" readonly>
                                    <div class="checkbox-container">
                                        <input class="findings-checkbox" type="checkbox" name="shop" id="shop-field" disabled>
                                    </div>
                                    <div class="checkbox-container">
                                        <input class="findings-checkbox" type="checkbox" name="field" id="field-field" disabled>
                                    </div>
                                    <div class="checkbox-container">
                                        <input class="findings-checkbox" type="checkbox" name="out_wty" id="out_wty-field" disabled>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row mb-2">
                                <div class="col-md-9">
                                    <label>Remarks</label>
                                    <input type="text" class="form-control" name="remarks" readonly>
                                </div>
                            </div>
                            <div class="row mb-1 mt-3">
                                <div class="col-md-12">
                                    <h5 class="fw-bold mb-1">Part Used</h5>
                                </div>
                            </div>
                            <!-- Parts Used Container -->
                            <div id="parts-container">
                                <div class="row mb-2 align-items-center g-2 parts-row">
                                    <div class="col-md-3 pe-1">
                                        <input type="text" class="form-control" name="part_name[]" readonly>
                                    </div>
                                    <div class="col-md-2 px-1">
                                        <input type="text" class="form-control" name="quantity[]" readonly>
                                    </div>
                                    <div class="col-md-2 px-1">
                                        <input type="text" class="form-control" name="part_amount[]" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <div class="d-flex flex-row" style="gap: 2rem;">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="installation" id="installation-field" disabled>
                                            <label class="form-check-label" for="installation-field">Installation</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="repair" id="repair-field" disabled>
                                            <label class="form-check-label" for="repair-field">Repair</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="cleaning" id="cleaning-field" disabled>
                                            <label class="form-check-label" for="cleaning-field">Cleaning</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="checkup" id="checkup-field" disabled>
                                            <label class="form-check-label" for="checkup-field">Check-Up</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-8 pe-1">
                                    <label>Total Service Charge</label>
                                    <div class="input-group mb-0">
                                        <span class="input-group-text">₱</span>
                                        <input type="text" class="form-control" id="total-serviceCharge-display" readonly>
                                    </div>
                                </div>
                                <div class="col-md-4 ps-1">
                                    <label>Date Repaired</label>
                                    <input type="text" class="form-control" name="date_repaired" readonly>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-8 pe-1">
                                    <label>Total Amount</label>
                                    <div class="input-group mb-0">
                                        <span class="input-group-text">₱</span>
                                        <input type="text" class="form-control" name="total_amount" readonly>
                                    </div>
                                </div>
                                <div class="col-md-4 ps-1">
                                    <label>Date Delivered</label>
                                    <input type="text" class="form-control" name="date_delivered" readonly>
                                </div>
                            </div>
                            <div class="row mb-2">
                                <div class="col-md-12">
                                    <label>Complaint</label>
                                    <input type="text" class="form-control" name="complaint" readonly>
                                </div>
                            </div>
                            <div class="row mt-3 mb-1">
                                <div class="col-md-12">
                                    <h5 class="fw-bold mb-1">Charged Details</h5>
                                </div>
                            </div>
                            <div class="row mb-2 align-items-end">
                                <div class="col-md-3 pe-1 d-flex flex-column justify-content-end">
                                    <span class="input-group-text">₱</span>
                                    <label class="mb-1">Labor:</label>
                                    <input type="text" class="form-control" id="labor-amount" step="1" min="0" readonly>
                                </div>
                                <div class="col-md-3 px-1 d-flex flex-column justify-content-end">
                                    <span class="input-group-text">₱</span>
                                    <label class="mb-1">Pull-Out Delivery:</label>
                                    <input type="text" class="form-control" id="pullout-delivery" step="1" min="0" readonly>
                                </div>
                                <div class="col-md-3 px-1 d-flex flex-column justify-content-end">
                                    <span class="input-group-text">₱</span>
                                    <label class="mb-1">Total:</label>
                                    <input type="text" class="form-control" id="total-serviceCharge" step="1" min="0" readonly>
                                </div>
                                <div class="col-md-3 ps-1 d-flex flex-column justify-content-end">
                                    <span class="input-group-text">₱</span>
                                    <label class="mb-1">Parts Charge:</label>
                                    <input type="text" class="form-control" name="parts_charge" id="parts-charge" step="1" min="0" readonly>
                                </div>
                            </div>
                            <div class="row mb-4"></div>
                            <div class="row mb-2">
                                <div class="col-md-3 pe-1 d-flex flex-column justify-content-end">
                                    <label class="mb-1">Receptionist:</label>
                                    <input type="text" class="form-control" name="receptionist" readonly>
                                </div>
                                <div class="col-md-3 px-1 d-flex flex-column justify-content-end">
                                    <label class="mb-1">Manager:</label>
                                    <input type="text" class="form-control" name="manager" readonly>
                                </div>
                                <div class="col-md-3 px-1 d-flex flex-column justify-content-end">
                                    <label class="mb-1">Technician:</label>
                                    <input type="text" class="form-control" name="technician" readonly>
                                </div>
                                <div class="col-md-3 ps-1 d-flex flex-column justify-content-end">
                                    <label class="mb-1">Released By:</label>
                                    <input type="text" class="form-control" name="released_by" readonly>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="../js/jquery-3.3.1.min.js"></script>
    <script src="../js/popper.min.js"></script>
    <script src="../js/bootstrap.min.js"></script>
    <!-- Select2 for searchable selects (admin parity) -->
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <!-- html2canvas for print screenshots -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script type="text/javascript">
        // Minimal tab toggling; the heavy transaction logic lives in the external
        // `script_for_transaction.js`. This avoids redefining functions and keeps
        // staff behavior identical to the admin `views/transactions.php`.
        $(document).ready(function() {
            $('.tab').on('click', function() {
                $('.tab').removeClass('active');
                $('.tab-content').removeClass('active');

                $(this).addClass('active');
                const tabId = $(this).data('tab');
                $('#' + tabId + 'Tab').addClass('active');

                if (tabId === 'transactions') {
                    // initializeTransactionTab is provided by the external script
                    if (typeof initializeTransactionTab === 'function') {
                        initializeTransactionTab();
                    }
                }
            });

            // If the transactions tab is already active on page load, initialize it as well.
            const $initialActive = $('.tab.active');
            if ($initialActive.length) {
                const initialTab = $initialActive.data('tab');
                if (initialTab === 'transactions' && typeof initializeTransactionTab === 'function') {
                    initializeTransactionTab();
                }
            }
        });
    </script>
    
    <!-- Service report javascript -->
    <script src="javascripts/script_for_report.js"></script>

    <!-- Transactions javascript -->
    <script src="javascripts/script_for_transaction.js"></script>
</body>
</html>
<?php
require_once __DIR__ . '/../../bootstrap.php';
session_start();
require __DIR__ . '/../handlers/authHandler.php';
require_once __DIR__ . '/../handlers/Database.php';
require_once __DIR__ . '/../handlers/serviceHandler.php';
require_once __DIR__ . '/../handlers/transactionHandler.php';

header('Content-Type: application/json');

$auth = new AuthHandler();
$userSession = $auth->requireAuth('staff');

// Initialize database connection
$database = new Database();
$conn = $database->getConnection();

// Initialize handlers
$serviceHandler = new ServiceHandler($conn);
$transactionHandler = new TransactionHandler($conn);

// Get current staff member's username
$currentStaff = $_SESSION['user']['username'] ?? $_SESSION['user']['full_name'] ?? '';

$action = $_GET['action'] ?? '';

switch ($action) {
    case 'getAssignedReports':
        $data = $serviceHandler->getAssignedReportsForStaff($currentStaff);
        echo json_encode([
            'success' => true,
            'data' => $data,
            'message' => 'Assigned reports fetched'
        ]);
        break;
    
    case 'getPendingOrders':
        $data = $serviceHandler->getPendingOrdersForStaff($currentStaff);
        echo json_encode([
            'success' => true,
            'data' => $data,
            'message' => 'Pending orders fetched'
        ]);
        break;
    
    case 'getCompletedServices':
        $data = $serviceHandler->getCompletedServicesForStaff($currentStaff);
        echo json_encode([
            'success' => true,
            'data' => $data,
            'message' => 'Completed services fetched'
        ]);
        break;
    
    case 'getWorkStatus':
        $data = $serviceHandler->getWorkStatusForStaff($currentStaff);
        echo json_encode([
            'success' => true,
            'data' => $data,
            'message' => 'Work status fetched'
        ]);
        break;
    
    case 'getDailyTrends':
        $days = $_GET['days'] ?? 7;
        $data = $transactionHandler->getDailyServiceTrendsForStaff($currentStaff, $days);
        echo json_encode([
            'success' => true,
            'data' => $data,
            'message' => 'Daily trends fetched'
        ]);
        break;
    
    case 'getAll':
        // Get all dashboard data at once
        $assignedReports = $serviceHandler->getAssignedReportsForStaff($currentStaff);
        $pendingOrders = $serviceHandler->getPendingOrdersForStaff($currentStaff);
        $completedServices = $serviceHandler->getCompletedServicesForStaff($currentStaff);
        $workStatus = $serviceHandler->getWorkStatusForStaff($currentStaff);
        $dailyTrends = $transactionHandler->getDailyServiceTrendsForStaff($currentStaff, 7);
        
        // Global pending counts (all reports with status 'Pending')
        try {
            $stmt = $conn->prepare("SELECT COUNT(*) as total_pending FROM service_reports WHERE status = 'Pending'");
            $stmt->execute();
            $res = $stmt->get_result()->fetch_assoc();
            $pendingGlobal = intval($res['total_pending'] ?? 0);

            // Count pending reports that are unassigned (no technician in service_details)
            $stmt2 = $conn->prepare(
                "SELECT COUNT(*) as unassigned_pending FROM service_reports sr
                 LEFT JOIN service_details sd ON sd.report_id = sr.report_id
                 WHERE sr.status = 'Pending' AND (sd.technician IS NULL OR sd.technician = '')"
            );
            $stmt2->execute();
            $res2 = $stmt2->get_result()->fetch_assoc();
            $pendingUnassigned = intval($res2['unassigned_pending'] ?? 0);
        } catch (Exception $e) {
            error_log('Error fetching global pending counts: ' . $e->getMessage());
            $pendingGlobal = 0;
            $pendingUnassigned = 0;
        }
        
        echo json_encode([
            'success' => true,
            'data' => [
                'assignedReports' => $assignedReports,
                'pendingOrders' => $pendingOrders,
                'completedServices' => $completedServices,
                'workStatus' => $workStatus,
                'dailyTrends' => $dailyTrends,
                'pendingGlobal' => $pendingGlobal,
                'pendingUnassigned' => $pendingUnassigned
            ],
            'message' => 'All dashboard data fetched'
        ]);
        break;
    
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Invalid action'
        ]);
}
?>

<?php

require __DIR__ . '/../../backend/handlers/serviceHandler.php';
require __DIR__ . '/../../backend/handlers/partsHandler.php';

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

function sendResponse($success, $data = null, $message = '', $httpCode = 200)
{
    http_response_code($httpCode);
    echo json_encode([
        'success' => $success,
        'data' => $data,
        'message' => $message
    ]);
    exit;
}

$database = new Database();

$db = $database->getConnection();
if ($db->connect_error) {
    sendResponse(false, null, 'Database connection failed: ' . $db->connect_error, 500);
}
$serviceHandler = new ServiceHandler($db);

$method = $_SERVER['REQUEST_METHOD'];

$input = json_decode(file_get_contents('php://input'), true);
if (($method === 'POST' || $method === 'PUT') && json_last_error() !== JSON_ERROR_NONE) {
    sendResponse(false, null, 'Invalid JSON input', 400);
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'getAll':
            if ($method !== 'GET') {
                sendResponse(false, null, 'Method is incorrect', 405);
            }

            $limit = $_GET['limit'] ?? 100;
            $offset = $_GET['offset'] ?? 0;
            $result = $serviceHandler->getAll($limit, $offset);
            sendResponse($result['success'], $result['data'], $result['message']);

            break;

        case 'getById':
            try {
                if ($method !== 'GET') {
                    sendResponse(false, null, 'Method is incorrect', 405);
                }

                $id = $_GET['id'] ?? null;
                if (!$id || !is_numeric($id)) {
                    sendResponse(false, null, 'Valid Report ID required', 400);
                }

                $result = $serviceHandler->getById($id);
                if (!$result['success']) {
                    error_log("Error fetching report by ID: $id. Error: " . $result['message'] .
                        ". Data: " . var_export($result['data'], true));
                }
                sendResponse($result['success'], $result['data'], $result['message']);
            } catch (Exception $e) {
                error_log("Create API Error: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                sendResponse(false, null, 'Create failed: ' . $e->getMessage(), 500);
            }
            break;

        case 'create':
            if ($method !== 'POST') {
                sendResponse(false, null, 'Method is incorrect', 405);
            }

            $db->autocommit(false); 
            try {

                error_log("Received input data: " . print_r($input, true));

                $required = ['customer_name', 'appliance_name', 'date_in', 'status'];
                foreach ($required as $field) {
                    if (empty($input[$field])) {
                        sendResponse(false, null, "Missing required field: $field", 400);
                    }
                }

                // Create new parts handler for quantity management
                $partsHandler = new PartsHandler($db);

                // Verify parts quantities before proceeding
                if (!empty($input['parts'])) {
                    foreach ($input['parts'] as $part) {
                        $partId = isset($part['part_id']) ? $part['part_id'] : null;
                        if (!$partId) {
                            $db->rollback(); 
                            sendResponse(false, null, "Part ID not found for {$part['part_name']}", 400);
                        }
                        
                        $partDetails = $partsHandler->getPartsById($partId);
                        if (!$partDetails) {
                            $db->rollback(); 
                            sendResponse(false, null, "Part not found with ID: {$partId}", 400);
                        }
                        
                        if ($partDetails['quantity_stock'] < $part['quantity']) {
                            $db->rollback(); 
                            sendResponse(false, null, "Insufficient quantity for part {$part['part_name']}. Available: {$partDetails['quantity_stock']}", 400);
                        }
                    }
                }

                $report = new Service_report(
                    $input['customer_name'],
                    $input['appliance_name'],
                    new DateTime($input['date_in']),
                    $input['status'],
                    $input['dealer'] ?? '',
                    new DateTime($input['dop']),
                    !empty($input['date_pulled_out']) ? new DateTime($input['date_pulled_out']) : null,
                    $input['findings'] ?? '',
                    $input['remarks'] ?? '',
                    $input['location'] ?? ['shop']
                );
                error_log("Service report object created");

                $detail = new Service_detail(
                    $input['service_types'] ?? ['repair'], //default to repair if nor specified
                    (float)($input['service_charge'] ?? 0),
                    !empty($input['date_repaired']) ? new DateTime($input['date_repaired']) : null,
                    !empty($input['date_delivered']) ? new DateTime($input['date_delivered']) : null,
                    $input['complaint'] ?? '',
                    (float)($input['labor'] ?? 0),
                    (float)($input['pullout_delivery'] ?? 0),
                    (float)($input['parts_total_charge'] ?? 0),
                    (float)($input['total_amount'] ?? 0),
                    $input['receptionist'] ?? '',
                    $input['manager'] ?? '',
                    $input['technician'] ?? '',
                    $input['released_by'] ?? ''
                );
                error_log("Service detail object created");

                $partsData = [];
                if (!empty($input['parts'])) {
                    foreach ($input['parts'] as $part) {
                        $partsData[] = [
                            'part_name' => $part['Part_Name'] ?? $part['part_name'] ?? '',
                            'quantity' => (int)($part['Quantity'] ?? $part['quantity'] ?? 0),
                            'unit_price' => (float)($part['Unit_Price'] ?? $part['unit_price'] ?? 0)
                        ];
                    }
                }
                $partsUsed = new Parts_used(['parts' => $partsData]);

                $result = $serviceHandler->createCompleteServiceReport($report, $detail, $partsUsed);
                error_log("Create result: " . print_r($result, true));

                if (!$report || !$detail || !$partsUsed) {
                    $db->rollback(); 
                    sendResponse(false, null, 'Failed to create service objects', 400);
                }

                // Deduct parts quantities after successful service report creation
                if (!empty($input['parts'])) {
                    foreach ($input['parts'] as $part) {
                        try {
                            // Get the part ID from the selected option value
                            $partId = isset($part['part_id']) ? $part['part_id'] : null;
                            if (!$partId) {
                                error_log("Missing partId in input: " . print_r($part, true));
                                throw new Exception("Part ID not found for {$part['part_name']}");
                            }
                            $partsHandler->deductQuantity($partId, $part['quantity']);
                        } catch (Exception $e) {
                            $db->rollback(); 
                            sendResponse(false, null, "Failed to update quantity for part {$part['part_name']}: " . $e->getMessage(), 500);
                        }
                    }
                }

                $db->commit(); 
                sendResponse(true, ['ReportID' => $result['data']], 'Service report created successfully', 201);
            } catch (Exception $e) {
                error_log("Create API Error: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                sendResponse(false, null, 'Create failed: ' . $e->getMessage(), 500);
            }
            break;

        case 'update':
            // $db->autocommit(false); 
            try {

                if ($method !== 'PUT') {
                    sendResponse(false, null, 'Method is incorrect', 405);
                }

                error_log("Received input data: " . print_r($input, true));

                $id = $_GET['id'] ?? null;
                if (!$id) {
                    sendResponse(false, null, 'Report ID required', 400);
                }

                $required = ['customer_name', 'appliance_name', 'date_in', 'status'];
                foreach ($required as $field) {
                    if (empty($input[$field])) {
                        // $db->rollback(); 
                        sendResponse(false, null, "Missing required field: $field", 400);
                    }
                }

                //update service report
                $report = new Service_report(
                    $input['customer_name'] ?? '',
                    $input['appliance_name'] ?? '',
                    !empty($input['date_in']) ? new DateTime($input['date_in']) : new DateTime(),
                    $input['status'] ?? '',
                    $input['dealer'] ?? '',
                    !empty($input['dop']) ? new DateTime($input['dop']) : new DateTime(),
                    !empty($input['date_pulled_out']) ? new DateTime($input['date_pulled_out']) : null,
                    $input['findings'] ?? '',
                    $input['remarks'] ?? '',
                    $input['location'] ?? ['shop']
                );
                error_log("Service report object created");

                $result = $serviceHandler->updateReport($id, $report);
                if (!$result['success']) {
                    sendResponse(false, null, $result['message'], 400);
                }

                //update service details
                $detail = new Service_detail(
                    $input['service_types'] ?? ['repair'],
                    (float)($input['service_charge'] ?? 0),
                    !empty($input['date_repaired']) ? new DateTime($input['date_repaired']) : null,
                    !empty($input['date_delivered']) ? new DateTime($input['date_delivered']) : null,
                    $input['complaint'] ?? '',
                    (float)($input['labor'] ?? 0),
                    (float)($input['pullout_delivery'] ?? 0),
                    (float)($input['parts_total_charge'] ?? 0),
                    (float)($input['total_amount'] ?? 0),
                    $input['receptionist'] ?? '',
                    $input['manager'] ?? '',
                    $input['technician'] ?? '',
                    $input['released_by'] ?? ''
                );
                error_log("Service detail object created");

                $result = $serviceHandler->updateDetails($id, $detail);
                if (!$result['success']) {
                    sendResponse(false, null, $result['message'], 400);
                }

                $partsData = [];
                if (!empty($input['parts'])) {
                    foreach ($input['parts'] as $part) {
                        $partsData[] = [
                            'part_name' => $part['Part_Name'] ?? $part['part_name'] ?? '',
                            'quantity' => (int)($part['Quantity'] ?? $part['quantity'] ?? 0),
                            'unit_price' => (float)($part['Unit_Price'] ?? $part['unit_price'] ?? 0)
                        ];
                    }
                }
                $partsUsed = new Parts_used(['parts' => $partsData]);

                $result = $serviceHandler->updatePartsUsed($id, $partsUsed);
                error_log("Create result: " . print_r($result, true));

                if (!$result['success']) {
                    sendResponse(false, null, $result['message'], 400);
                }

                sendResponse(true, null, 'Service report updated successfully');
            } catch (Exception $e) {
                error_log("Update API Error: " . $e->getMessage());
                error_log("Stack trace: " . $e->getTraceAsString());
                sendResponse(false, null, 'Update failed: ' . $e->getMessage(), 500);
            }

            break;

        case 'delete':
            if ($method !== 'DELETE') {
                sendResponse(false, null, 'Method is incorrect', 405);
            }

            $id = $_GET['id'] ?? null;
            if (!$id) {
                sendResponse(false, null, 'Report ID required', 400);
            }

            error_log("Service API delete called: id=" . $id . ", method=" . $method);
            $result = $serviceHandler->delete($id);
            // log returned result for debugging
            if (is_array($result) && isset($result['success'])) {
                error_log('Service API delete result: ' . json_encode($result));
                sendResponse($result['success'], null, $result['message'] ?? '');
            } else {
                error_log('Service API delete returned unexpected result: ' . var_export($result, true));
                // if boolean false, send generic error
                if ($result === false) {
                    sendResponse(false, null, 'Failed to delete service report', 500);
                }
                // if true without format (unlikely), treat as success
                sendResponse(true, null, 'Service report deleted successfully');
            }

            break;

        default:
            sendResponse(false, null, 'Invalid action specified', 400);

            break;
    }
} catch (Exception $e) {
    error_log("API Exception: " . $e->getMessage());
    sendResponse(false, null, $e->getMessage(), 500);
}

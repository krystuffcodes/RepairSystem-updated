<?php
require_once __DIR__ . '/auditLogger.php';

class Service_report {
    public function __construct(
        public string $customer_name = '',
        public string $appliance_name = '',
        public ?DateTime $date_in = null,
        public string $status = '',
        public string $dealer = '',
        public ?DateTime $dop = null,
        public ?DateTime $date_pulled_out = null,
        public string $findings = '',
        public string $remarks = '',
        public array $location = ['shop', 'field', 'out_wty']
    ) {
        $this->validate();
    }

    private function validate() {
         if(empty($this->location)) {
            throw new InvalidArgumentException("At least one location type must be selected");
        }

        $requiredFields = [
            'customer_name' => $this->customer_name,
            'appliance_name' => $this->appliance_name,
            'date_in' => $this->date_in,
            'status' => $this->status,
            // dop (Date of Purchase) is optional
        ];

        foreach ($requiredFields as $field => $value) {
            if(empty($value)) {
                throw new InvalidArgumentException("Missing required fields: $field");
            }
        }

        $this->customer_name = htmlspecialchars($this->customer_name, ENT_QUOTES, 'UTF-8');
        $this->appliance_name = htmlspecialchars($this->appliance_name, ENT_QUOTES, 'UTF-8');
        $this->findings = htmlspecialchars($this->findings, ENT_QUOTES, 'UTF-8');
        $this->remarks = htmlspecialchars($this->remarks, ENT_QUOTES, 'UTF-8');
    }

    public function update($data) {
        foreach($data as $key => $value) {
            if(property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        $this->validate();
    }
}

class Service_detail {
    public function __construct(
        public array $service_types = ['installation', 'repair', 'cleaning', 'checkup'],
        public float $service_charge = 0.00,
        public ?DateTime $date_repaired = null,
        public ?DateTime $date_delivered = null,
        public string $complaint = '',
        public float $labor = 0.00,
        public float $pullout_delivery = 0.00,
        public float $parts_total_charge = 0.00,
        public float $total_amount = 0.00,
        public string $receptionist = '',
        public string $manager = '',
        public string $technician = '',
        public string $released_by = '',
        public ?DateTime $date_in = null
    ) {
        $this->validateBasic();
        $this->validate();
        
    }

    private function validateBasic() {
        if(empty($this->service_types)) {
            throw new InvalidArgumentException("At least one service type must be selected");
        }

        if($this->labor < 0 || $this->pullout_delivery < 0 || $this->parts_total_charge < 0 || $this->total_amount < 0) {
            throw new InvalidArgumentException("Financial values cannot be negative");
        }

        $calculatedTotal = $this->labor + $this->pullout_delivery + $this->service_charge + $this->parts_total_charge;
        $diff = abs(round($this->total_amount, 2) - round($calculatedTotal, 2)); 

        if($diff > 0.01) {
            throw new InvalidArgumentException(
                "Total amount mismatch. Expected: ".round($calculatedTotal, 2).
                " | Actual: ".round($this->total_amount, 2).
                " | Components: labor (".$this->labor."), ".
                "pullout deliery (".$this->pullout_delivery."), ".
                "service_charge (".$this->service_charge."), ".
                "parts (".$this->parts_total_charge.")" 
            );
            return $diff;
        }

        $this->complaint = htmlspecialchars($this->complaint, ENT_QUOTES, 'UTF-8');
        $this->receptionist = htmlspecialchars($this->receptionist, ENT_QUOTES, 'UTF-8');
        $this->manager = htmlspecialchars($this->manager, ENT_QUOTES, 'UTF-8');
        $this->technician = htmlspecialchars($this->technician, ENT_QUOTES, 'UTF-8');
        $this->released_by = htmlspecialchars($this->released_by, ENT_QUOTES, 'UTF-8');
    }

    private function validate() {
        $this->validateBasic();

        if($this->date_in) {
            if($this->date_repaired && $this->date_repaired < $this->date_in) {
                throw new InvalidArgumentException("Date repaired must be after Date in");
            }

            if($this->date_delivered && $this->date_repaired && $this->date_delivered < $this->date_repaired) {
                throw new InvalidArgumentException("Date delivered must be after Date repaired");
            }
        }
      
    }

    public function update($data) {
        foreach($data as $key => $value) {
            if(property_exists($this, $key)) {
                $this->$key = $value;
            }
        }
        $this->validate();
    } 
}

class Parts_used {
    public function __construct(
        public array $parts = []
    ) {
        $this->normalizePartsArray();
        $this->validate();
    }

    public function addPart(string $part_name, int $quantity, float $unit_price) {
        $this->parts[] = [
            'part_name' => htmlspecialchars($part_name, ENT_QUOTES, 'UTF-8'),
            'quantity' => $quantity,
            'unit_price' => $unit_price,
            'parts_total' => $quantity * $unit_price
        ];
        $this->validate();
    }

    public function normalizePartsArray() {
        if(empty($this->parts)) {
            return;
        }

        if(isset($this->parts['parts']) && is_array($this->parts['parts'])) {
            $this->parts = $this->parts['parts'];
            return;
        }

        $keys = array_keys($this->parts);
        if($keys === range(0, count($this->parts) - 1)) {
            return;
        }

        if(isset($this->parts['part_name'])) {
            $this->parts = [$this->parts];
        }
    }

    private function validate() {
        if(empty($this->parts)) {
            return;
        }

        foreach($this->parts as $part) {
            if(empty($part['part_name'])) {
                error_log("Invalid part data: " . print_r($part, true));
                throw new InvalidArgumentException("Part name cannot be empty");
            }
            
            if($part['quantity'] < 0) {
                throw new InvalidArgumentException("Parts quantity cannot be negative");
            }
            
            if($part['unit_price'] < 0) {
                throw new InvalidArgumentException("Unit price cannot be negative");
            }

            if(($part['parts_total'] ?? ($part['quantity'] * $part['unit_price'])) < 0) {
                throw new InvalidArgumentException("Total amount cannot be negative");
            }

            $calculatedTotal = $part['quantity'] * $part['unit_price'];

            $providedTotal = $part['parts_total'] ?? $calculatedTotal;
            if($providedTotal < 0) {
                throw new InvalidArgumentException("Total amount cannot be negative");
            }
            if (abs($providedTotal - $calculatedTotal) > 0.01) {
                throw new InvalidArgumentException(
                    "Total amount mismatch for part '{$part['part_name']}'. " .
                    "Expected: " . round($calculatedTotal, 2) . 
                    " | Actual: " . round($providedTotal, 2)
                );
            }

        }
    }

    public function update(array $data) {
        foreach($data as $key => $value) {
            if ($key === 'parts' && is_array($value)) {
                $this->parts = $value;
            }
        }
        $this->validate();
    } 
}



class ServiceHandler {
    private $conn;
    private $servicereport_table = "service_reports";
    private $servicedetail_table = "service_details";
    private $partsused_table = "parts_used";
    
    public function __construct($db) {
        if(!$db instanceof mysqli) {
            throw new InvalidArgumentException("Invalid database connection");
        }
        $this->conn = $db;
        // $this->conn->autocommit(false); 
    }

    private function formatResponse($success, $data = null, $message = '') {
        return [
            'success' => $success,
            'data' => $data,
            'message' => $message ?: ($success ? 'Operation successful' : 'Operation failed')
        ];
    }

    public function createCompleteServiceReport(Service_report $report, Service_detail $detail, Parts_used $partsUsed) {

        // if ($this->conn->autocommit(false)) {
        //     $this->conn->autocommit(true); // Ensure autocommit is on first
        // }
        
        $this->conn->begin_transaction();

        try {
            error_log("Starting service report creation...");
            
            $reportId = $this->createServiceReport($report);
            error_log("Service report created with ID: " . $reportId);

            $this->createServiceDetails($reportId, $detail);
            error_log("Service details created");

            $this->createPartsUsed($reportId, $partsUsed);
            error_log("Service partss created");

            $this->conn->commit();

            // Log the activity
            try {
                $service_report_data = $this->getServiceReportById($reportId);
                AuditLogger::logCreate($this->servicereport_table, $reportId, $service_report_data);
            } catch (Exception $e) {
                error_log('Audit logging error: ' . $e->getMessage());
            }

            return $this->formatResponse(true, $reportId, 'Service report created successfully');
                                                                            
        } catch(Exception $e) {
            $this->conn->rollback();
            error_log("ServiceHandler Error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            return $this->formatResponse(false, null, 'Create Failed: ' . $e->getMessage());
        }
    }

        private function createServiceReport(Service_report $report) {
            if($report->date_pulled_out && $report->date_pulled_out < $report->date_in) {
                throw new InvalidArgumentException("Pull out date cannot be before date in");
            }

            $stmt = $this->conn->prepare("
                INSERT INTO {$this->servicereport_table} 
                (customer_name, appliance_name, date_in, status, dealer, dop, date_pulled_out, findings, remarks, location)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $customer_name = $report->customer_name;
            $appliance_name = $report->appliance_name;
            $dateIn = $report->date_in ? $report->date_in->format('Y-m-d') : null;
            $status = $report->status;
            $dealer = $report->dealer ? $report->dealer : null;
            $dop = $report->dop ? $report->dop->format('Y-m-d') : null;
            $datePullOut = $report->date_pulled_out ? $report->date_pulled_out->format('Y-m-d') : null;
            $findings = $report->findings ? $report->findings : null;
            $remarks = $report->remarks ? $report->remarks : null;
            $locationJson = json_encode($report->location);

            $stmt->bind_param(
                "ssssssssss",
                $customer_name,
                $appliance_name,
                $dateIn,
                $status,
                $dealer,
                $dop,
                $datePullOut,
                $findings,
                $remarks,
                $locationJson
            );

            if(!$stmt->execute()) {
                throw new RuntimeException("Failed to create service report: " . $stmt->error);
            }

            return $this->conn->insert_id;
        }

        private function createServiceDetails($reportId, Service_detail $detail) {

            $stmt = $this->conn->prepare("
                INSERT INTO {$this->servicedetail_table} 
                (report_id, service_types, service_charge, date_repaired, date_delivered, complaint, labor, pullout_delivery,
                parts_total_charge, total_amount, receptionist, manager, technician, released_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            "); 

            $serviceTypesJson = json_encode($detail->service_types);
            $service_charge = $detail->service_charge ? $detail->service_charge : null;
            $dateRepaired = $detail->date_repaired ? $detail->date_repaired->format('Y-m-d') : null;
            $dateDelivered = $detail->date_delivered ? $detail->date_delivered->format('Y-m-d') : null;
            $complaint = $detail->complaint ? $detail->complaint : null;
            $labor = $detail->labor ? $detail->labor : null;
            $pullout_delivery = $detail->pullout_delivery ? $detail->pullout_delivery : null;
            $parts_total_charge = $detail->parts_total_charge ? $detail->parts_total_charge : null;
            $total_amount = $detail->total_amount ? $detail->total_amount : null;
            $receptionist = $detail->receptionist ? $detail->receptionist : null;
            $manager = $detail->manager ? $detail->manager : null;
            $technician = $detail->technician ? $detail->technician : null;
            $released_by = $detail->released_by ? $detail->released_by : null;

            $stmt->bind_param(
                "isdsssddddssss",
                $reportId,
                $serviceTypesJson,
                $service_charge,
                $dateRepaired,
                $dateDelivered,
                $complaint,
                $labor,
                $pullout_delivery,
                $parts_total_charge,
                $total_amount,
                $receptionist,
                $manager,
                $technician,
                $released_by
            );

            if(!$stmt->execute()) {
                throw new RuntimeException("Failed to create service detail: " . $stmt->error);
            }
            
        }

        private function createPartsUsed($reportId, Parts_used $partsUsed) {  
            foreach($partsUsed->parts as $part) {
                $stmt = $this->conn->prepare("
                    INSERT INTO {$this->partsused_table}
                    (report_id, part_name, quantity, unit_price, parts_total)
                    VALUES (?, ?, ?, ?, ?)
                ");

                $part_name = $part['part_name'] ? $part['part_name'] : null;
                $quantity = $part['quantity'] ? $part['quantity'] : null;
                $unit_price = $part['unit_price'] ? $part['unit_price'] : null;
                $parts_total = $part['parts_total'] ?? $part['quantity'] * $part['unit_price'] ?? null;

                $stmt->bind_param(
                    "isidd",
                    $reportId,
                    $part_name, 
                    $quantity,
                    $unit_price,
                    $parts_total
                );

                if(!$stmt->execute()) {
                    throw new RuntimeException("Failed to create parts used record: " . $stmt->error);
                }
            }  
            
        }


    public function getById($id) {
        try {
            //service report
            $stmt = $this->conn->prepare("
                SELECT * FROM {$this->servicereport_table} WHERE report_id = ?
            ");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $report = $stmt->get_result()->fetch_assoc();
                if(!$report) return $this->formatResponse(false, null, 'Report not found');
            
            //service details
            $stmt = $this->conn->prepare("
                SELECT * FROM {$this->servicedetail_table} WHERE report_id = ?
            ");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $detail = $stmt->get_result()->fetch_assoc();

            //service parts used
            $stmt = $this->conn->prepare("
                SELECT part_name, quantity, unit_price FROM {$this->partsused_table} WHERE report_id = ?
            ");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $parts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

            //combine data
            $data = array_merge($report, $detail ?: []);
            $data['Parts'] = $parts ?: []; 
            $data['parts'] = $data['Parts']; 

            //decode JSON fields
            $data['location'] = !empty($data['location']) ? json_decode($data['location'], true) : [];
            $data['service_types'] = !empty($data['service_types']) ? json_decode($data['service_types'], true) : [];

            return $this->formatResponse(true, $data);
        } catch(Exception $e) {
            return $this->formatResponse(false, null, 'Failed to retrieve report: ' . $e->getMessage());
        }
    }
    
    
    public function getServicesByStatus($status) {
        try {
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count FROM {$this->servicereport_table} 
                WHERE status = ?
            ");
            $stmt->bind_param("s", $status);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            return intval($row['count'] ?? 0);
        } catch (Exception $e) {
            error_log('Error getting services by status: ' . $e->getMessage());
            return 0;
        }
    }

    public function getTopPerformingStaff($limit = 3) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    sd.technician,
                    COUNT(*) as service_count,
                    COALESCE(SUM(sd.total_amount), 0) as total_revenue
                FROM {$this->servicedetail_table} sd
                WHERE sd.technician IS NOT NULL 
                AND sd.technician != ''
                AND sd.date_repaired >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                GROUP BY sd.technician
                ORDER BY service_count DESC, total_revenue DESC
                LIMIT ?
            ");
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            $result = $stmt->get_result();

            $staff = [];
            while($row = $result->fetch_assoc()) {
                $staff[] = [
                    'name' => $row['technician'],
                    'services' => intval($row['service_count']),
                    'revenue' => floatval($row['total_revenue'])
                ];
            }
            return $staff;
        } catch (Exception $e) {
            error_log('Error getting top performing staff: ' . $e->getMessage());
            return [];
        }
    }

    public function getAll($limit = 100, $offset = 0) {
        try {
            $stmt = $this->conn->prepare("
                SELECT sr.*, 
                sd.service_types, sd.total_amount
                FROM {$this->servicereport_table} sr 
                LEFT JOIN {$this->servicedetail_table} sd ON sr.report_id = sd.report_id
                ORDER BY sr.date_in DESC
                LIMIT ? OFFSET ?
            ");

            $stmt->bind_param("ii", $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();

            $reports = [];
            while($row = $result->fetch_assoc()) {
                $row['total_amount'] = floatval($row['total_amount'] ?? 0);

                if(!empty($row['service_types'])) {
                   if(substr($row['service_types'], 0, 1) === '[') {
                        $row['service_types'] = json_decode($row['service_types'], true);
                   } else {
                        $row['service_types'] = array_map('trim', explode(',', $row['service_types']));
                   }
                } else {
                    $row['service_types'] = [];
                }
                $reports[] = $row;
            }

            return $this->formatResponse(true, $reports);
        } catch(Exception $e) {
            return $this->formatResponse(false, null, 'Failed to retrieve reports: ' . $e->getMessage());
        }
    }


    public function updateReport($id, Service_report $report) {
        try {
            // Get old data before update for audit logging
            $old_data = $this->getServiceReportById($id);

            $stmt = $this->conn->prepare("
                UPDATE {$this->servicereport_table}
                SET customer_name = ?, appliance_name = ?, date_in = ?, status = ?,
                dealer = ?, dop = ?, date_pulled_out = ?, findings = ?, remarks = ?, location = ?
                WHERE report_id = ?
            ");

            $customer_name = $report->customer_name ? $report->customer_name : null;
            $appliance_name = $report->appliance_name ? $report->appliance_name : null; 
            $dateIn = $report->date_in ? $report->date_in->format('Y-m-d') : null;
            $status = $report->status ? $report->status : null;
            $dealer = $report->dealer ? $report->dealer : null;
            $dop = $report->dop ? $report->dop->format('Y-m-d') : null;
            $datePullOut = $report->date_pulled_out ? $report->date_pulled_out->format('Y-m-d') : null;
            $findings = $report->findings ? $report->findings : null;
            $remarks = $report->remarks ? $report->remarks : null;
            $locationJson = json_encode($report->location);

            $stmt->bind_param(
                "ssssssssssi",
                $customer_name,
                $appliance_name,
                $dateIn,
                $status,
                $dealer,
                $dop,
                $datePullOut,
                $findings,
                $remarks,
                $locationJson,
                $id
            );
            if(!$stmt->execute()) {
                    return $this->formatResponse(false, null, $stmt->error);
            }

            // Log the activity
            try {
                $new_data = $this->getServiceReportById($id);
                AuditLogger::logUpdate($this->servicereport_table, $id, $old_data, $new_data);
            } catch (Exception $e) {
                error_log('Audit logging error: ' . $e->getMessage());
            }

            return $this->formatResponse(true, null, 'Report updated successfully');

        } catch(Exception $e) {
            return $this->formatResponse(false, null, 'Failed to update reports: ' . $e->getMessage());
        }
    
       
    }
    

    public function updateDetails($reportId, Service_detail $detail) {
        $this->conn->begin_transaction();
        
        try {
            $report = $this->getById($reportId);
            if(!$report['success']) {
                throw new RuntimeException("Failed to get report for date validation");
            }

            $dateIn = DateTime::createFromFormat('Y-m-d', $report['data']['date_in']);

            $detail->date_in = $dateIn;

            //delete existing details
            $stmt = $this->conn->prepare("
                DELETE FROM {$this->servicedetail_table} WHERE report_id = ? 
            ");
            $stmt->bind_param("i", $reportId);
            $stmt->execute();

            //insert update details
            $stmt = $this->conn->prepare("
                INSERT INTO {$this->servicedetail_table} 
                (report_id, service_types, service_charge, date_repaired, date_delivered, complaint, labor,
                pullout_delivery, parts_total_charge, total_amount, receptionist, manager, technician, released_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $serviceTypesJson = json_encode($detail->service_types);
            $service_charge = $detail->service_charge ? $detail->service_charge : null;
            $dateRepaired = $detail->date_repaired ? $detail->date_repaired->format('Y-m-d') : null;
            $dateDelivered = $detail->date_delivered ? $detail->date_delivered->format('Y-m-d') : null;
            $complaint = $detail->complaint ? $detail->complaint : null;
            $labor = $detail->labor ? $detail->labor : null;
            $pullout_delivery = $detail->pullout_delivery ? $detail->pullout_delivery : null;
            $parts_total_charge = $detail->parts_total_charge ? $detail->parts_total_charge : null;
            $total_amount = $detail->total_amount ? $detail->total_amount : null;
            $receptionist = $detail->receptionist ? $detail->receptionist : null;
            $manager = $detail->manager ? $detail->manager : null;
            $technician = $detail->technician ? $detail->technician : null;
            $released_by = $detail->released_by ? $detail->released_by : null;

            $stmt->bind_param(
                "isdsssddddssss",
                $reportId,
                $serviceTypesJson,
                $service_charge,
                $dateRepaired,
                $dateDelivered,
                $complaint,
                $labor,
                $pullout_delivery,
                $parts_total_charge,
                $total_amount,
                $receptionist,
                $manager,
                $technician,
                $released_by
            );
            if(!$stmt->execute()) {
                throw new RuntimeException("Failed to insert service detail: " . $stmt->error);
            }
            

            $this->conn->commit();
            return $this->formatResponse(true, null, "Service detail updated successfully");

        } catch(Exception $e) {
            $this->conn->rollback();
            return $this->formatResponse(false, null, 'Failed to update details: ' . $e->getMessage());
        } 
    }


    public function updatePartsUsed($reportId, Parts_used $partsUsed) {
        $this->conn->begin_transaction();

        try {
            $stmt = $this->conn->prepare("
                DELETE FROM {$this->partsused_table} WHERE report_id = ?
            ");
            $stmt->bind_param("i", $reportId);
            $stmt->execute();

            foreach($partsUsed->parts as $part) {
                $stmt = $this->conn->prepare("
                    INSERT INTO {$this->partsused_table} 
                    (report_id, part_name, quantity, unit_price, parts_total)
                    VALUES (?, ?, ?, ?, ?)
                ");

                $part_name = $part['part_name'] ? $part['part_name'] : null;
                $quantity = $part['quantity'] ? $part['quantity'] : null;
                $unit_price = $part['unit_price'] ? $part['unit_price'] : null;
                $parts_total = $part['parts_total'] ?? $part['quantity'] * $part['unit_price'] ?? null;

                $stmt->bind_param(
                    "isidd",
                    $reportId,
                    $part_name, 
                    $quantity,
                    $unit_price,
                    $parts_total
                );
                
                if(!$stmt->execute()) {
                    throw new RuntimeException("Failed to update parts used record: " . $stmt->error);
                }
            }

            $this->conn->commit();
            return $this->formatResponse(true, null, "Parts used updated successfully");

        } catch(Exception $e) {
            $this->conn->rollback();
            return $this->formatResponse(false, null, 'Failed to update details: ' . $e->getMessage());
        }
    }


    public function delete($id) {
        $this->conn->begin_transaction();

        try {
            // Get all data before deletion for archiving
            $service_report = $this->getServiceReportById($id);
            $service_details = $this->getServiceDetailsByReportId($id);
            $parts_used = $this->getPartsUsedByReportId($id);
            $transactions = $this->getTransactionsByReportId($id);

            // Archive parts used first
            $archive_success = true;
            $archive_failed_items = [];
            foreach ($parts_used as $part) {
                try {
                    require_once __DIR__ . '/archiveHandler.php';
                    $archiveHandler = new ArchiveHandler($this->conn);
                    $res = $archiveHandler->archiveRecord($this->partsused_table, $part['part_used_id'], $part, $_SESSION['user_id'] ?? null, 'Part deleted with service report');
                    if (!$res) {
                        $archive_success = false;
                        $archive_failed_items[] = ['type' => 'parts_used', 'id' => $part['part_used_id']];
                    }
                } catch (Exception $e) {
                    error_log('Archive logging error for parts_used: ' . $e->getMessage());
                    $archive_success = false;
                    $archive_failed_items[] = ['type' => 'parts_used', 'id' => $part['part_used_id'], 'error' => $e->getMessage()];
                }
            }
                if (!$archive_success) {
                    $this->conn->rollback();
                    return $this->formatResponse(false, null, 'Failed to archive parts_used. Failed to archive: ' . json_encode($archive_failed_items));
                }

            // Delete parts used
            $stmt = $this->conn->prepare("
                DELETE FROM {$this->partsused_table} WHERE report_id = ?
            ");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            // Archive service details
            foreach ($service_details as $detail) {
                try {
                    require_once __DIR__ . '/archiveHandler.php';
                    $archiveHandler = new ArchiveHandler($this->conn);
                    $res = $archiveHandler->archiveRecord($this->servicedetail_table, $detail['detail_id'], $detail, $_SESSION['user_id'] ?? null, 'Service detail deleted with service report');
                    if (!$res) {
                        $archive_success = false;
                        $archive_failed_items[] = ['type' => 'service_details', 'id' => $detail['detail_id']];
                    }
                } catch (Exception $e) {
                    error_log('Archive logging error for service_details: ' . $e->getMessage());
                    $archive_success = false;
                    $archive_failed_items[] = ['type' => 'service_details', 'id' => $detail['detail_id'], 'error' => $e->getMessage()];
                }
            }
                if (!$archive_success) {
                    $this->conn->rollback();
                    return $this->formatResponse(false, null, 'Failed to archive service_details. Failed to archive: ' . json_encode($archive_failed_items));
                }

            // Delete service details
            $stmt = $this->conn->prepare("
                DELETE FROM {$this->servicedetail_table} WHERE report_id = ?
            ");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            // Archive transactions
            foreach ($transactions as $transaction) {
                try {
                    require_once __DIR__ . '/archiveHandler.php';
                    $archiveHandler = new ArchiveHandler($this->conn);
                    $res = $archiveHandler->archiveRecord('transactions', $transaction['transaction_id'], $transaction, $_SESSION['user_id'] ?? null, 'Transaction deleted with service report');
                    if (!$res) {
                        $archive_success = false;
                        $archive_failed_items[] = ['type' => 'transactions', 'id' => $transaction['transaction_id']];
                    }
                } catch (Exception $e) {
                    error_log('Archive logging error for transactions: ' . $e->getMessage());
                    $archive_success = false;
                    $archive_failed_items[] = ['type' => 'transactions', 'id' => $transaction['transaction_id'], 'error' => $e->getMessage()];
                }
            }
                if (!$archive_success) {
                    $this->conn->rollback();
                    return $this->formatResponse(false, null, 'Failed to archive transactions. Failed to archive: ' . json_encode($archive_failed_items));
                }

            // Delete transactions
            $stmt = $this->conn->prepare("
                DELETE FROM transactions WHERE report_id = ?
            ");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            // Archive service report
            try {
                require_once __DIR__ . '/archiveHandler.php';
                $archiveHandler = new ArchiveHandler($this->conn);
                $res = $archiveHandler->archiveRecord($this->servicereport_table, $id, $service_report, $_SESSION['user_id'] ?? null, 'Service report deleted');
                if (!$res) {
                    $archive_success = false;
                    $archive_failed_items[] = ['type' => 'service_reports', 'id' => $id];
                }
            } catch (Exception $e) {
                error_log('Archive logging error for service_reports: ' . $e->getMessage());
                $archive_success = false;
                $archive_failed_items[] = ['type' => 'service_reports', 'id' => $id, 'error' => $e->getMessage()];
            }
                if (!$archive_success) {
                    $this->conn->rollback();
                    return $this->formatResponse(false, null, 'Failed to archive service_reports. Failed to archive: ' . json_encode($archive_failed_items));
                }

            // Log the activity
            try {
                AuditLogger::logDelete($this->servicereport_table, $id, $service_report);
            } catch (Exception $e) {
                error_log('Audit logging error: ' . $e->getMessage());
            }

            // Delete service report 
            $stmt = $this->conn->prepare("
                DELETE FROM {$this->servicereport_table} WHERE report_id = ?
            ");
            $stmt->bind_param("i", $id);
            $stmt->execute();

            $this->conn->commit();
            $message = $archive_success ? 'Service Report archived successfully' : 'Service report deleted but some archiving operations failed';
            if (!$archive_success) {
                $message .= '. Failed to archive: ' . json_encode($archive_failed_items);
            }
            return $this->formatResponse($archive_success, null, $message);

        } catch(Exception $e) {
            $this->conn->rollback();
            return $this->formatResponse(false, null, 'Failed to archive service report: ' . $e->getMessage());
        }
    }

    // Helper methods for archiving
    private function getServiceReportById($id) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->servicereport_table} WHERE report_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->fetch_assoc();
    }

    private function getServiceDetailsByReportId($reportId) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->servicedetail_table} WHERE report_id = ?");
        $stmt->bind_param("i", $reportId);
        $stmt->execute();
        $result = $stmt->get_result();
        $details = [];
        while ($row = $result->fetch_assoc()) {
            $details[] = $row;
        }
        return $details;
    }

    private function getPartsUsedByReportId($reportId) {
        $stmt = $this->conn->prepare("SELECT * FROM {$this->partsused_table} WHERE report_id = ?");
        $stmt->bind_param("i", $reportId);
        $stmt->execute();
        $result = $stmt->get_result();
        $parts = [];
        while ($row = $result->fetch_assoc()) {
            $parts[] = $row;
        }
        return $parts;
    }

    private function getTransactionsByReportId($reportId) {
        $stmt = $this->conn->prepare("SELECT * FROM transactions WHERE report_id = ?");
        $stmt->bind_param("i", $reportId);
        $stmt->execute();
        $result = $stmt->get_result();
        $transactions = [];
        while ($row = $result->fetch_assoc()) {
            $transactions[] = $row;
        }
        return $transactions;
    }

    // Staff Dashboard Methods
    public function getAssignedReportsForStaff($staffName) {
        try {
            // Clean staff name to match database format
            $cleanStaffName = trim(str_replace([' (Technician)', ' (Manager)'], '', $staffName));
            
            // Get total assigned reports
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as total 
                FROM {$this->servicedetail_table} sd
                WHERE sd.technician LIKE ? 
                AND sd.technician IS NOT NULL 
                AND sd.technician != ''
            ");
            $searchPattern = '%' . $cleanStaffName . '%';
            $stmt->bind_param("s", $searchPattern);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $total = intval($row['total'] ?? 0);

            // Get weekly change
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as weekly_count 
                FROM {$this->servicedetail_table} sd
                WHERE sd.technician LIKE ? 
                AND sd.technician IS NOT NULL 
                AND sd.technician != ''
                AND sd.date_repaired >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)
            ");
            $stmt->bind_param("s", $searchPattern);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $weeklyCount = intval($row['weekly_count'] ?? 0);

            // Get previous week count for comparison
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as prev_weekly_count 
                FROM {$this->servicedetail_table} sd
                WHERE sd.technician LIKE ? 
                AND sd.technician IS NOT NULL 
                AND sd.technician != ''
                AND sd.date_repaired >= DATE_SUB(CURDATE(), INTERVAL 2 WEEK)
                AND sd.date_repaired < DATE_SUB(CURDATE(), INTERVAL 1 WEEK)
            ");
            $stmt->bind_param("s", $searchPattern);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $prevWeeklyCount = intval($row['prev_weekly_count'] ?? 0);

            $weeklyChange = $weeklyCount - $prevWeeklyCount;

            return [
                'total' => $total,
                'weekly_change' => $weeklyChange
            ];
        } catch (Exception $e) {
            error_log('Error getting assigned reports for staff: ' . $e->getMessage());
            return ['total' => 0, 'weekly_change' => 0];
        }
    }

    public function getPendingOrdersForStaff($staffName) {
        try {
            $cleanStaffName = trim(str_replace([' (Technician)', ' (Manager)'], '', $staffName));
            
            // Get total pending orders
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as total 
                FROM {$this->servicedetail_table} sd
                JOIN {$this->servicereport_table} sr ON sd.report_id = sr.report_id
                WHERE sd.technician LIKE ? 
                AND sd.technician IS NOT NULL 
                AND sd.technician != ''
                AND sr.status IN ('Pending', 'In Progress')
            ");
            $searchPattern = '%' . $cleanStaffName . '%';
            $stmt->bind_param("s", $searchPattern);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $total = intval($row['total'] ?? 0);

            // Get daily change
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as daily_count 
                FROM {$this->servicedetail_table} sd
                JOIN {$this->servicereport_table} sr ON sd.report_id = sr.report_id
                WHERE sd.technician LIKE ? 
                AND sd.technician IS NOT NULL 
                AND sd.technician != ''
                AND sr.status IN ('Pending', 'In Progress')
                AND sr.date_in >= CURDATE()
            ");
            $stmt->bind_param("s", $searchPattern);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $dailyCount = intval($row['daily_count'] ?? 0);

            // Get yesterday count for comparison
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as yesterday_count 
                FROM {$this->servicedetail_table} sd
                JOIN {$this->servicereport_table} sr ON sd.report_id = sr.report_id
                WHERE sd.technician LIKE ? 
                AND sd.technician IS NOT NULL 
                AND sd.technician != ''
                AND sr.status IN ('Pending', 'In Progress')
                AND sr.date_in >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)
                AND sr.date_in < CURDATE()
            ");
            $stmt->bind_param("s", $searchPattern);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $yesterdayCount = intval($row['yesterday_count'] ?? 0);

            $dailyChange = $dailyCount - $yesterdayCount;

            return [
                'total' => $total,
                'daily_change' => $dailyChange
            ];
        } catch (Exception $e) {
            error_log('Error getting pending orders for staff: ' . $e->getMessage());
            return ['total' => 0, 'daily_change' => 0];
        }
    }

    public function getCompletedServicesForStaff($staffName) {
        try {
            $cleanStaffName = trim(str_replace([' (Technician)', ' (Manager)'], '', $staffName));
            
            // Get total completed services
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as total 
                FROM {$this->servicedetail_table} sd
                JOIN {$this->servicereport_table} sr ON sd.report_id = sr.report_id
                WHERE sd.technician LIKE ? 
                AND sd.technician IS NOT NULL 
                AND sd.technician != ''
                AND sr.status = 'Completed'
            ");
            $searchPattern = '%' . $cleanStaffName . '%';
            $stmt->bind_param("s", $searchPattern);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $total = intval($row['total'] ?? 0);

            // Get daily change
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as daily_count 
                FROM {$this->servicedetail_table} sd
                JOIN {$this->servicereport_table} sr ON sd.report_id = sr.report_id
                WHERE sd.technician LIKE ? 
                AND sd.technician IS NOT NULL 
                AND sd.technician != ''
                AND sr.status = 'Completed'
                AND sr.date_in >= CURDATE()
            ");
            $stmt->bind_param("s", $searchPattern);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $dailyCount = intval($row['daily_count'] ?? 0);

            // Get yesterday count for comparison
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as yesterday_count 
                FROM {$this->servicedetail_table} sd
                JOIN {$this->servicereport_table} sr ON sd.report_id = sr.report_id
                WHERE sd.technician LIKE ? 
                AND sd.technician IS NOT NULL 
                AND sd.technician != ''
                AND sr.status = 'Completed'
                AND sr.date_in >= DATE_SUB(CURDATE(), INTERVAL 1 DAY)
                AND sr.date_in < CURDATE()
            ");
            $stmt->bind_param("s", $searchPattern);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $yesterdayCount = intval($row['yesterday_count'] ?? 0);

            $dailyChange = $dailyCount - $yesterdayCount;

            return [
                'total' => $total,
                'daily_change' => $dailyChange
            ];
        } catch (Exception $e) {
            error_log('Error getting completed services for staff: ' . $e->getMessage());
            return ['total' => 0, 'daily_change' => 0];
        }
    }

    public function getServiceReportTrendForStaff($staffName, $days = 7) {
        try {
            $cleanStaffName = trim(str_replace([' (Technician)', ' (Manager)'], '', $staffName));
            
            $labels = [];
            $data = [];
            
            // Generate labels for the last N days
            for ($i = $days - 1; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $dayName = date('D', strtotime($date));
                $labels[] = $dayName;
                
                // Get count for this day
                $stmt = $this->conn->prepare("
                    SELECT COUNT(*) as count 
                    FROM {$this->servicedetail_table} sd
                    WHERE sd.technician LIKE ? 
                    AND sd.technician IS NOT NULL 
                    AND sd.technician != ''
                    AND DATE(sd.date_repaired) = ?
                ");
                $searchPattern = '%' . $cleanStaffName . '%';
                $stmt->bind_param("ss", $searchPattern, $date);
                $stmt->execute();
                $result = $stmt->get_result();
                $row = $result->fetch_assoc();
                $data[] = intval($row['count'] ?? 0);
            }

            return [
                'labels' => $labels,
                'data' => $data
            ];
        } catch (Exception $e) {
            error_log('Error getting service report trend for staff: ' . $e->getMessage());
            return ['labels' => [], 'data' => []];
        }
    }

    public function getWorkStatusForStaff($staffName) {
        try {
            $cleanStaffName = trim(str_replace([' (Technician)', ' (Manager)'], '', $staffName));
            
            $stmt = $this->conn->prepare("
                SELECT sr.status, COUNT(*) as count 
                FROM {$this->servicedetail_table} sd
                JOIN {$this->servicereport_table} sr ON sd.report_id = sr.report_id
                WHERE sd.technician LIKE ? 
                AND sd.technician IS NOT NULL 
                AND sd.technician != ''
                GROUP BY sr.status
            ");
            $searchPattern = '%' . $cleanStaffName . '%';
            $stmt->bind_param("s", $searchPattern);
            $stmt->execute();
            $result = $stmt->get_result();

            $data = [];
            while ($row = $result->fetch_assoc()) {
                $data[$row['status']] = intval($row['count']);
            }

            return [
                'data' => $data
            ];
        } catch (Exception $e) {
            error_log('Error getting work status for staff: ' . $e->getMessage());
            return ['data' => []];
        }
    }
}
?>
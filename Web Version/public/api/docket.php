<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

include("../../include/database.php");

try {
    $stmt = $conn->prepare("
        SELECT c.caseid, c.defendent, c.hearing_date, c.courtroom, c.hearing_status,
               COALESCE(u1.charactername, u2.charactername, u3.charactername, u4.charactername, 'TBD') as judge_name
        FROM cases c 
        LEFT JOIN users u1 ON c.shared01 = u1.username AND u1.job = 'Judge'
        LEFT JOIN users u2 ON c.shared02 = u2.username AND u2.job = 'Judge'  
        LEFT JOIN users u3 ON c.shared03 = u3.username AND u3.job = 'Judge'
        LEFT JOIN users u4 ON c.shared04 = u4.username AND u4.job = 'Judge'
        WHERE c.hearing_date IS NOT NULL 
        AND c.hearing_status IN ('scheduled', 'completed') 
        ORDER BY c.hearing_date ASC
        LIMIT 100
    ");
    $stmt->execute();
    $hearings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $hearings,
        'last_updated' => date('c')
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Unable to fetch docket data'
    ]);
}
?>
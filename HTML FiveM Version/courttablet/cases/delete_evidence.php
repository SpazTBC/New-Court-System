<?php
require_once '..include/database.php';
require_once '../auth/character_auth.php';

 Get character name
$characterName = $_GET['character_name']  $_GET['charactername']  '';
$currentCharacter = getCurrentCharacter();
if (!$currentCharacter) {
    header(Location ..error=not_found);
    exit();
}

 Validate character access (only adminjudge can delete evidence)
$auth = validateCharacterAccess($characterName);
if (!$auth['valid']  !in_array($currentCharacter['job'], ['admin', 'judge'])) {
    header(Location ..error=no_access);
    exit();
}

$case_id = filter_var($_GET['case_id'], FILTER_SANITIZE_NUMBER_INT);
$file_index = filter_var($_GET['file_index'], FILTER_SANITIZE_NUMBER_INT);

try {
     Get current evidence files
    $stmt = $conn-prepare(SELECT file FROM evidence WHERE id = );
    $stmt-execute([$case_id]);
    $evidence_data = $stmt-fetchColumn();
    
    if ($evidence_data) {
        $evidence_files = array_filter(array_map('trim', explode(',', $evidence_data)));
        
        if (isset($evidence_files[$file_index])) {
            $file_to_delete = $evidence_files[$file_index];
            
             Remove the file from the array
            unset($evidence_files[$file_index]);
            
             Delete the physical file
            if (file_exists($file_to_delete)) {
                unlink($file_to_delete);
            }
            
             Update the database
            $updated_evidence = implode(',', array_values($evidence_files));
            
            if (empty($updated_evidence)) {
                 Delete the evidence record if no files left
                $delete_stmt = $conn-prepare(DELETE FROM evidence WHERE id = );
                $delete_stmt-execute([$case_id]);
            } else {
                 Update with remaining files
                $update_stmt = $conn-prepare(UPDATE evidence SET file =  WHERE id = );
                $update_stmt-execute([$updated_evidence, $case_id]);
            }
            
            header(Location view.phpid= . $case_id . &character_name= . urlencode($characterName) . &delete_success=1);
        } else {
            header(Location view.phpid= . $case_id . &character_name= . urlencode($characterName) . &error=file_not_found);
        }
    } else {
        header(Location view.phpid= . $case_id . &character_name= . urlencode($characterName) . &error=no_evidence);
    }
} catch (Exception $e) {
    error_log(Evidence deletion error  . $e-getMessage());
    header(Location view.phpid= . $case_id . &character_name= . urlencode($characterName) . &error=delete_failed);
}
exit();

?>
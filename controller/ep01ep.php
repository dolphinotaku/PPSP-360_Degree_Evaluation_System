<?php

$errors         = array();  	// array to hold validation errors
$data 			= array(); 		// array to pass back data

$responseArray = array();

function ProcessData($requestData){
    $processMessageList = ["Processed Result:"];
	$staffManager = new StaffManager();
    $evaProposalManager = new EvaProposalManager();
    
	$createRows = new stdClass();
    
    if(isset($requestData->Data->IsClearAll))
        $isClearAll = $requestData->Data->IsClearAll;
    else
        $isClearAll = false;
    $evaluationCode = $requestData->Data->evaluationCode->EvaluationCode;
    $deptRange = $requestData->Data->deptRange;
    
    $deptRangeStart = '"'.$deptRange->start.'"';
    $deptRangeEnd = '"'.$deptRange->end.'"';
    $query = "SELECT * FROM `staff` WHERE `DepartmentCode` BETWEEN ".$deptRangeStart." and ".$deptRangeEnd;
    
    $field = array("DepartmentCode");
    $selectionRange = new stdClass();
    $selectionRange->DepartmentCode = $deptRange;
    
    if($deptRange->start != "" && $deptRange->end != "")
        // get the valid staff in list
        $responseArray = $staffManager->selectRange($field, $selectionRange);
    else
        $responseArray = $staffManager->select();
    
    // Prepare evaluation proposal records
    $evaProposalRecord = $evaProposalManager->_;
    $evaProposalList = [];
    $staffList = $responseArray["data"];
    
	foreach ($staffList as $keyIndex => $rowItem) {
        
        $evaProposalRecordOfStaff = [];
        
        $supervisorID = $rowItem["SupervisorID"];
        $staffID = $rowItem["StaffID"];
        
        // find the supervisor
        $supervisorList = GetSupervisorInProposalList($evaProposalRecord, $staffID);
        
        // generate the record for self
        $selfList = GetSelfInProposalList($evaProposalRecord, $staffID);
        
        // find the collaborator(subordinate, external party
        $collaboratorList = GetCollaboratorInProposalList($evaProposalRecord, $staffID);
        
        $evaProposalRecordOfStaff = array_merge($evaProposalRecordOfStaff, $selfList);
        $evaProposalRecordOfStaff = array_merge($evaProposalRecordOfStaff, $supervisorList);
        $evaProposalRecordOfStaff = array_merge($evaProposalRecordOfStaff, $collaboratorList);
        
        $evaProposalList[$staffID] = $evaProposalRecordOfStaff;
    }
    
    // Delete all related record first
    if($isClearAll){
        $evaProposalManager->Initialize();
        $evaProposalManager->EvalautionCode = $evaluationCode;
        $deleteResponseArray = $evaProposalManager->select();
        $sameEvaCodeList = $deleteResponseArray["data"];
        foreach ($sameEvaCodeList as $keyIndex => $rowItem) {
            $evaProposalManager->_ = $rowItem;
            $responseArray = $evaProposalManager->delete();
        }
        array_push($processMessageList, "All records about Evaluation Code: ".$evaluationCode." were deleted.");
    }
    
//    print_r($evaProposalList);
    
    // Insert prepared evaluation proposal records
    $evaProposalManager = new EvaProposalManager();
    foreach($evaProposalList as $evaluator => $evaluateeList){
        
        foreach($evaluateeList as $keyIndex => $rowItem){
            $rowItem["EvaluationCode"] = $evaluationCode;
            $evaProposalManager->Initialize();
            
            // set default status as incomplete
            $evaProposalManager->_ = $rowItem;
            $evaProposalManager->EvaProQtnStatusCode = "I";
            
            // Check key exists
            $isKeyExists = $evaProposalManager->CheckDuplicateEvaluateeor();
            
            
            // insert record if not exists
            if(!$isKeyExists)
                $responseArray = $evaProposalManager->insert();
            
			if($responseArray['affected_rows'] > 0){
                if($isKeyExists)
                    $tempProcessMessage = "Record already exists, evaluatee: ".$evaProposalManager->Evaluatee." evaluator: ".$evaProposalManager->Evaluator;
                else
                    $tempProcessMessage = "Evaluation proposal generated, evaluatee: ".$evaProposalManager->Evaluatee." evaluator: ".$evaProposalManager->Evaluator;
                
                array_push($processMessageList, $tempProcessMessage);
            }
            
        }
    }
    
    $responseArray['processed_message'] = $processMessageList;
    $responseArray['access_status'] = Core::$access_status['OK'];

    return $responseArray;
}

function GetSupervisorInProposalList($proposalRecord, $evaluatee){
    $evaProposalRecordOfStaff = [];
    
    $staffManager = new StaffManager();
    $staffManager->StaffID = $evaluatee;
    $responseArray = $staffManager->select();

    $staffList = $responseArray["data"];

    if($responseArray["affected_rows"] > 0){
        // if the staff have no SupervisorID
        if(!$staffList[0]["SupervisorID"])
            return $evaProposalRecordOfStaff;


        $proposalRecord["Evaluatee"] = $evaluatee;
        $proposalRecord["Evaluator"] = $staffList[0]["SupervisorID"];
        $proposalRecord["EvaTypeCode"] = "B";
    }
    
    array_push($evaProposalRecordOfStaff, $proposalRecord);
    
    return $evaProposalRecordOfStaff;
}
function GetSelfInProposalList($proposalRecord, $evaluatee){
    $evaProposalRecordOfStaff = [];
    
        $staffManager = new StaffManager();
        $staffManager->StaffID = $evaluatee;
        $responseArray = $staffManager->select();

        if($responseArray["affected_rows"] > 0){
            $proposalRecord["Evaluatee"] = $evaluatee;
            $proposalRecord["Evaluator"] = $evaluatee;
            $proposalRecord["EvaTypeCode"] = "S";
        }
    
    array_push($evaProposalRecordOfStaff, $proposalRecord);
    
    return $evaProposalRecordOfStaff;
}
function GetCollaboratorInProposalList($proposalRecord, $evaluatee){
    $evaProposalRecordOfStaff = [];
    
	$staffManager = new StaffManager();
    $staffManager->SupervisorID = $evaluatee;
    $responseArray = $staffManager->select();
    
    $staffList = $responseArray["data"];    
    if($responseArray["affected_rows"] > 0){
        foreach ($staffList as $keyIndex => $rowItem) {
            $newRow = $proposalRecord;
            $newRow["Evaluatee"] = $evaluatee;
            $newRow["Evaluator"] = $rowItem["StaffID"];
            $newRow["EvaTypeCode"] = "C";
            array_push($evaProposalRecordOfStaff, $newRow);
        }
    }
    
    
    return $evaProposalRecordOfStaff;
}

?>
<?php

function ProcessData($requestData){
    $responseArray = Core::CreateResponseArray();
    
    $processMessageList = ["Processed Result:"];
    $evaluationManager = new EvaluationManager();
    $questionManager = new QuestionManager();
    $questionnaireManager = new QuestionnaireManager();
    $qtnResultManager = new QuestionnaireResultManager();
    
    $evaluationCode = $requestData->Data->evaluationCode->EvaluationCode;
    $evaProposalID = $requestData->Data->evaluationCode->EvaProposalID;
    
    $evaluationManager->EvaluationCode = $evaluationCode;
    $evaluationResponseArray = $evaluationManager->select();
    
    // if evaluation code found
    if(!$evaluationResponseArray["affected_rows"] > 0){
        array_push($processMessageList, "Evaluation Code not found");
        $responseArray['processed_message'] = $processMessageList;
        return $responseArray;
    }
    
    $questionnaireManager->EvaluationCode = $evaluationCode;
    $qtnnaireResponseArray = $questionnaireManager->select();
    
    // if questionnaire found
    if($qtnnaireResponseArray["affected_rows"] > 0){
        
    }else{
        $qtnnaireResponseArray = $questionnaireManager->insert();
    }
    
    $qtnnaireList = $qtnnaireResponseArray["data"];
    
    $questionManager->QuestionID = null;
    $questionManager->QuestionnaireID = $qtnnaireList[0]["QuestionnaireID"];
    $questionResponseArray = $questionManager->select();
    
    // find question saved result (drafted)
    $qtnResultManager->EvaProposalID = $evaProposalID;
    $qtnResultResponseArray = $qtnResultManager->select();
    
    $responseArray['evaluationTable'] = $evaluationResponseArray["data"];
    $responseArray['questionnaireTable'] = $qtnnaireResponseArray["data"];
    $responseArray['questionTable'] = $questionResponseArray["data"];
    
    $responseArray['questionResultTable'] = $qtnResultResponseArray["data"];
    
    $responseArray['processed_message'] = $processMessageList;
    $responseArray['access_status'] = Core::$access_status['OK'];

    return $responseArray;
}

function GetTableStructure(){
	$evaProposalManager = new EvaProposalManager();
    
    return $evaProposalManager->selectPrimaryKeyList();
}

function FindData($requestData){
	$evaProposalManager = new EvaProposalManager();
	$securityManager = new SecurityManager();
    $loginData = $securityManager->GetLoginData();
    $userID = $loginData["USER_ID"];

	$updateRows = new stdClass();
	$updateRows = $requestData->Data->Header;
    
	foreach ($updateRows as $keyIndex => $rowItem) {
        foreach ($rowItem as $columnName => $value) {
            $evaProposalManager->$columnName = $value;
        }
        $responseArray = $evaProposalManager->select();
        break;
    }
    
	return $responseArray;
}

function GetData($requestData){
	$evaProposalManager = new EvaProposalManager();
	$securityManager = new SecurityManager();
    $loginData = $securityManager->GetLoginData();
    $userID = $loginData["USER_ID"];
    $loginID = $loginData["LOGIN_ID"]; $loginID = strtoupper($loginID);
    
    $evaProposalManager->Evaluator = $loginID;
    
	$offsetRecords = 0;
	$offsetRecords = $requestData->Offset;
	$pageNum = $requestData->PageNum;
	$numOfRecordPerPage = $requestData->PageRecordsLimit;

	$responseArray = $evaProposalManager->selectPage($offsetRecords, $numOfRecordPerPage);
    
    $countResponseArray = $evaProposalManager->count();
    $totalRecordCount = -1;
    if($countResponseArray["data"][0]["count"])
        $totalRecordCount = $countResponseArray["data"][0]["count"];
    
    $responseArray['TotalRecordCount'] = $totalRecordCount;
	return $responseArray;

}
?>
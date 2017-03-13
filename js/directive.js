// JavaScript Document
"use strict";

var my_arguments;

app.directive('logout', ['Security', '$rootScope', function(Security, $rootScope) {
    return {
        restrict: 'A',
        link: function(scope, element, attrs) {
            element.bind('click', function() {
            	Security.LogoutNRedirect();
            });
        }
    };
}]);

/**
 * <pageview> a record set view element, store a list of records obtained from the database and display as a page view.
 * <pageview
    ng-model=""
    program-id=""
    edit-mode=""
    >
 * @param {Object} ng-model - store the data record, the data record may used in CRUD
 * @param {String} program-id - assign the program id to implement the behavior of CRUD
 * @param {String} edit-mode - define the mode [create | view | amend | delete |]
 */
app.directive('pageview', ['$rootScope', 
    '$timeout', 
    'Core', 
    'Security', 
    'LockManager', 
    'HttpRequeset',
    'MessageService', function($rootScope, $timeout, Core, Security, LockManager, HttpRequeset, MessageService) {
    function PageViewConstructor($scope, $element, $attrs) {
    	var constructor = this;
    	var $ctrl = $scope.pageviewCtrl;
        var tagName = $element[0].tagName.toLowerCase();

    	var recordStructure = {};

        function LockAllControls(){
            LockManager.LockAllControls($element, "pageview");
        }
        function UnLockAllControls(){
            LockManager.UnLockAllControls($element,"pageview");
        }

    	function InitializePageView() {
    		// check attribute programId
            var isProgramIdFound = false;
            if(typeof($attrs.programId) != undefined){
            	if($attrs.programId != null && $attrs.programId !=""){
            		isProgramIdFound = true;
            	}
            }
            if(isProgramIdFound){
            	$scope.programId = $attrs.programId;
            }
            else
            	alert("<pageview> Must declare a attribute of program-id");
    		// check attribute PageRecordsLimit
            var isPageRecordsLimit = false;
            var pageRecordsLimit = $attrs.pageRecordsLimit;
            if(typeof(pageRecordsLimit) != undefined){
            	if(pageRecordsLimit != null && pageRecordsLimit !=""){
            		isPageRecordsLimit = true;
            	}
            }
            if(isPageRecordsLimit){
            	$scope.numOfRecordPerPage = pageRecordsLimit;
            }
            else{
            	console.log("<"+$element[0].tagName+"> attribute of page-records-limit default as 10");
            	$scope.numOfRecordPerPage = 10;
            }

            $scope.criteriaObj = {};

            // Declare $scope.variable
            $scope.dataSource = []; // [{}, {}]
            $scope.sortedDataSource = []; // [{}, {}]
            $scope.currentPageRecords = {}; // [{}, {}]
            $scope.mousehoverRecord = {}; // mousehover's record
            $scope.pointedRecord = {}; // mouse clicked record
            $scope.selectedRecord = {}; // user tick to selected record

            $scope.maxRecordsCount = -1;

            $scope.lastPageNum = -1;

            $scope.pageNum = 1;

            $ctrl.ngModel = {};
            // $ctrl.ngModel = $scope.currentPageRecords;

            $scope.DisplayMessage = "";

            $scope.getNextPageTimes = 0;
    	}

        function EventListener(){
            console.log("scope.$id:"+$scope.$id+", may implement $scope.EventListener() function in webapge");
        }
        function ValidateRecord(){
            console.log("scope.$id:"+$scope.$id+", may implement $scope.ValidateRecord() function in webapge"); 
            return true;
        }
        function CustomGetDataResult(data_or_JqXHR, textStatus, jqXHR_or_errorThrown){
            var progID = $scope.programId;
            //console.log("scope.$id:"+$scope.$id+", programId:"+progID+", must implement $scope.CustomGetDataResult() function in webapge");
        }

    	function SetRecordStructure(dataJson){
            // if structure already defined, escape the function
            if(!jQuery.isEmptyObject(recordStructure)){
                return;
            }
            //console.log("Pageview SetRecordStructure() execute.");
            var tableSchema = dataJson.ActionResult.table_schema;

        	for(var rowIndex in tableSchema){
        		var row = tableSchema[rowIndex];
                var columnName = row.Field;
        		var colDataType = Core.ConvertMySQLDataType(row.Type);

                var isSystemField = Core.IsSystemField(columnName);
                if(isSystemField)
                    continue;

        		// is column exists in ngModel
        		if(typeof(recordStructure[columnName]) == "undefined"){
        			if(colDataType == "string"){
        				recordStructure[columnName] = "";
        			}
        			else if (colDataType == "date"){
        				recordStructure[columnName] = new Date(0, 0, 0);
        			}
        			else if (colDataType == "double"){
        				recordStructure[columnName] = 0.0;
        			}
        		}
        	}
    	}
    	function AppendToDataSource(pageNum, dataJson){
        	var singleItem;
            var tableSchema = dataJson.ActionResult.table_schema;
            var numOfRecordPerPage = $scope.numOfRecordPerPage;

            var recordNumberStart = (pageNum - 1) * numOfRecordPerPage;
            var recordNumberEnd = pageNum * numOfRecordPerPage - 1;

        	var dataSourceArray = jQuery.extend([], $scope.dataSource);
            // 20170112, keithpoon, fixed: total records count less than a page, delete one record in database and than refresh in pageview, the record set display incorrect
            if(dataSourceArray.length < $rootScope.serEnv.phpRecordLimit)
                dataSourceArray = [];

            var counter = recordNumberStart;
        	// add each getted row into DataSource
        	for(var itemRow in dataJson.ActionResult.data){
        		var singleItem = dataJson.ActionResult.data[itemRow];
        		var newRecordRow = jQuery.extend({}, recordStructure);

                for(var rowIndex in tableSchema){
                    var row = tableSchema[rowIndex];
                    var columnName = row.Field;
                    var colDataType = Core.ConvertMySQLDataType(row.Type);

                    var isSystemField = Core.IsSystemField(columnName);
                    if(isSystemField)
                        continue;

            		var newColumn = newRecordRow[columnName];

            		if (colDataType == "date"){
        				newColumn = new Date(singleItem[columnName]);
        			}else if (colDataType == "double"){
        				newColumn = parseFloat(singleItem[columnName]);
        			}else{
        				newColumn = singleItem[columnName];
        			}

        			newRecordRow[columnName] = newColumn;
            	} // columns end
                dataSourceArray[counter] = newRecordRow;
                counter++;

                // append the item records
                newRecordRow.Items = singleItem.Items;
        	}
        	$scope.dataSource = jQuery.extend([], dataSourceArray);
    	}
    	function SortingTheDataSource(){
            $scope.sortedDataSource = jQuery.extend([], $scope.dataSource);
    	}

    	function GetRecordStructure(){
    		var aCopyOfRecordStructure = jQuery.extend({}, recordStructure);
    		return aCopyOfRecordStructure;
    	}

        function TryToCallInitDirective(){
            if(typeof $scope.InitDirective == "function"){
                $scope.InitDirective($scope, $element, $attrs, $ctrl);
            }else{
                $scope.DefaultInitDirective();
            }
        }
        function TryToCallSetCriteriaBeforeGet(pageNum, lastRecordIndex, criteriaObj){
            if(typeof $scope.SetCriteriaBeforeGet == "function"){
                criteriaObj = $scope.SetCriteriaBeforeGet(pageNum, lastRecordIndex, criteriaObj);
            }
            return criteriaObj;
        }

        $scope.Initialize = function(){
            $scope.InitScope();
            if(typeof $scope.EventListener == "function"){
                $scope.EventListener($scope, $element, $attrs, $ctrl);
            }else{
                EventListener();
            }
            TryToCallInitDirective();
        }
        $scope.InitScope = function(){
            InitializePageView();
        }
        $scope.DefaultInitDirective = function(){
            $scope.GotoFirstPageRecord();
        }


        $scope.PointedToRecord = function(pRecord, event, rowScope){
            $scope.pointedRecord = pRecord;

            // remove all background color
            angular.element(event.currentTarget).parent().find("tr").removeClass("active");

            // change the background color
            angular.element(event.currentTarget).addClass("active");

            if(typeof $scope.CustomPointedToRecord == "function"){
                $scope.CustomPointedToRecord(pRecord, rowScope, $scope, $element, $ctrl);
            }else{
                console.log("<"+$element[0].tagName+">" +" Directive function CustomPointedToRecord() should be override.");
            }
        }

        $scope.SelectedToRecord = function(sRecord, event, rowScope){
            $scope.selectedRecord = jQuery.extend([], $scope.pointedRecord);

            $scope.pointedRecord = {};
            
//            var target = angular.element(event.target);
            
            // check parent scope, is editbox
            var isParentScopeFromEditbox = false;
            if(typeof ($scope.$parent.SetEditboxNgModel) == "function")
                isParentScopeFromEditbox = true;
            
//            console.log("<pageview> - isParentScopeFromEditbox: "+isParentScopeFromEditbox);
//            console.dir(sRecord)
            
            if(isParentScopeFromEditbox){
                $scope.$parent.SetEditboxNgModel(sRecord);
            }
            
            var sRecord = $scope.selectedRecord;
            if(typeof $scope.CustomSelectedToRecord == "function"){
                $scope.CustomSelectedToRecord(sRecord, rowScope, $scope, $element, $ctrl);
            }else{
                console.log("<"+$element[0].tagName+">" +" Directive function CustomSelectedToRecord() should be override.");
            }
            
            if(typeof $scope.ClosePageView == "function")
                $scope.ClosePageView();
        }

        $scope.ClearNRefreshData = function(){
            var pageNum = $scope.pageNum;

            $scope.DisplayMessage = "";
            // $scope.dataSource = [];
            // $scope.sortedDataSource = [];
            $scope.currentPageRecords = {};
            $ctrl.ngModel = {};
            // $scope.maxRecordsCount = -1;

            $scope.TryToDisplayPageNum(pageNum, true);
        }
        $scope.LockAllControls = function(){
            LockAllControls();
        }
        $scope.UnLockAllControls = function(){
            $timeout(function(){
                UnLockAllControls();
                }, 1000); // (milliseconds),  1s = 1000ms
        }

    	$scope.GotoFirstPageRecord = function(){
    		$scope.pageNum = 1;
    		var pageNum = $scope.pageNum;
    		$scope.TryToDisplayPageNum(pageNum);
    	}
    	$scope.GotoPreviousPageRecord = function(){
    		if($scope.pageNum > 1){
    		 	$scope.pageNum--;
    			var pageNum = $scope.pageNum;
    			$scope.TryToDisplayPageNum(pageNum);
    		}else{
    			// first of the page, cannot Goto Previous
    			console.log("This is the first page, cannot go previous.")
    		}
    	}
    	$scope.GotoNextPageRecord = function(){
    		if($scope.pageNum >= $scope.lastPageNum && $scope.lastPageNum!=-1){
    			$scope.DisplayMessage = "End of records.";
    			return;
    		}

    		$scope.pageNum++;
    		var pageNum = $scope.pageNum;
    		$scope.TryToDisplayPageNum(pageNum);
    	}
    	$scope.GotoLastPageRecord = function(){
    		if($scope.pageNum == $scope.lastPageNum)
    			return;
            if($scope.lastPageNum == -1){
                return;
            }
            if($scope.lastPageNum != -1){
    			// $scope.pageNum = $scope.lastPageNum;
                $scope.pageNum++;
    			$scope.TryToDisplayPageNum($scope.pageNum);
    			return;
    		}

    		// console.log("GotoLastPageRecord() have not implement")
    		// return;
    		$scope.pageNum++;
    		var pageNum = $scope.pageNum;

    		while($scope.lastPageNum == -1)
    		$scope.TryToDisplayPageNum(pageNum);
    	}

    	$scope.TryToDisplayPageNum = function(pageNum, clearNRefresh){
    		$scope.DisplayMessage = "";
    		var numOfRecordPerPage = $scope.numOfRecordPerPage;
    		// Check is sortedDataSource contains enough records
    		// pageNum = 2, numOfRecordPerPage = 10, record start from 11 to 20
    		var recordNumberStart = (pageNum - 1) * numOfRecordPerPage;
    		var recordNumberEnd = pageNum * numOfRecordPerPage - 1;
    		var isAllRecordsExists = true;

            if(typeof(clearNRefresh) == "undefined"){
                clearNRefresh = false;
            }

            if(!clearNRefresh){
        		for(var recordCounter = recordNumberStart; recordCounter < recordNumberEnd; recordCounter++){
        			if(typeof($scope.sortedDataSource[recordCounter]) == "undefined"){
        				isAllRecordsExists = false;
        				break;
        			}
        		}
            }else{
                isAllRecordsExists = false;
            }
            
    		if($scope.maxRecordsCount != $scope.dataSource.length || clearNRefresh){
	    		// Get data if records not enough
	    		if(!isAllRecordsExists){
                    var criteriaObj = $scope.criteriaObj;

	    			// pageview need ValidateRecord(), for inquiry the records with some criteria
	    			// if Buffer invalid, cannot send request
	    			var isBufferValid = true;
		    		if(typeof $scope.ValidateRecord == "function"){
						isBufferValid = $scope.ValidateRecord($scope, $element, $attrs, $ctrl);
					}else{
						isBufferValid = ValidateRecord();
					}

                    var lastRecordIndex = $scope.sortedDataSource.length;
                    
//                    console.log("TryToCallSetCriteriaBeforeGet() - pageNum: "+pageNum+", lastRecordIndex: "+lastRecordIndex+", ")

                    var newCriteriaObj = TryToCallSetCriteriaBeforeGet(pageNum, lastRecordIndex, criteriaObj);
                    
                    $scope.GetNextPageRecords(pageNum, lastRecordIndex, newCriteriaObj);

	    			return;
	    		}
    		}

    		DisplayPageNum(pageNum);
    	}

    	function DisplayPageNum(pageNum){
    		console.log("Going to display the Page no.("+pageNum + ") records.");
    		var numOfRecordPerPage = $scope.numOfRecordPerPage;

    		var recordNumberStart = (pageNum - 1) * numOfRecordPerPage;
    		var recordNumberEnd = pageNum * numOfRecordPerPage - 1;
            
            var currentPageRecords = [];
            $scope.currentPageRecords = [];
            $ctrl.ngModel = [];
    		if(typeof($scope.sortedDataSource[recordNumberStart]) == "undefined"){

    		}else{
	    		// assign records to current page according to the page number
	    		for(var recordCounter = recordNumberStart; recordCounter <=recordNumberEnd; recordCounter++){

	    			if(recordCounter >= $scope.maxRecordsCount && $scope.maxRecordsCount > 0)
	    				break;
	    			var newRow = jQuery.extend({}, $scope.sortedDataSource[recordCounter]);
                    if(!jQuery.isEmptyObject(newRow))
	    			    currentPageRecords[currentPageRecords.length] = newRow;
	    		}
    		}
            
            $ctrl.ngModel = $scope.currentPageRecords = currentPageRecords;
    	}

    	$scope.GetNextPageRecords = function(pageNum, lastRecordIndex, criteriaObj){
    		$scope.LockAllControls();

        	var clientID = Security.GetSessionID();
        	var programId = $scope.programId.toLowerCase();
            var pageNum = $scope.pageNum;
            var recordOffset = (pageNum-1) * $scope.numOfRecordPerPage;

            // $scope.DisplayMessage = "";
        	// Convert the Key Value to Upper Case
        	// console.dir(keyObj);
        	// if(typeof(keyObj) == "undefined"){
        	// 	keyObj = {};
        	// }
        	// for(var keyIndex in keyObj){
        	// 	if(typeof(keyObj[keyIndex]) == "string")
        	// 		keyObj[keyIndex] = keyObj[keyIndex].toUpperCase();
        	// }

			var submitData = {
				"Session": clientID,
				"Table": programId,
                "PageNum": pageNum,
				"Offset": recordOffset,
				criteria: criteriaObj
			};
            submitData.Action = "GetData";

            var requestOption = {
                method: 'POST',
                data: JSON.stringify(submitData)
            };

            var request = HttpRequeset.send(requestOption);
            var httpResponseObj = {};
            $scope.getNextPageTimes+=1;
            request.then(function(responseObj) {
                var data_or_JqXHR = responseObj.data;
                httpResponseObj = responseObj;
                $scope.UnLockAllControls();
                
//                if(data_or_JqXHR.Status != "success")
//                    throw data_or_JqXHR;
//                
//                if(typeof(data_or_JqXHR.ActionResult.data) == "undefined")
//                {
//                    if($scope.getNextPageTimes == 1){
//                        $scope.DisplayMessage = "Record Not Found.";
//                    }
//                }

                SetRecordStructure(data_or_JqXHR);
                AppendToDataSource(pageNum, data_or_JqXHR);
                SortingTheDataSource();
                
                // Object.keys Browser compatibility
                // https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Object/keys
                var recordCount = Object.keys(data_or_JqXHR.ActionResult.data).length;
                // 20170312, keithpoon, fixed: end page problem caused when the record counts is the multiple of 10
                if(!data_or_JqXHR.ActionResult.data || (recordCount < $rootScope.serEnv.phpRecordLimit && $scope.getNextPageTimes > 1) || recordCount == 0){
                    $scope.maxRecordsCount = $scope.sortedDataSource.length;
                    if($scope.getNextPageTimes == 1)
                        $scope.DisplayMessage = "Record Not Found.";
                    else
                        $scope.DisplayMessage = "End of records.";
                    // 20170312, keithpoon, fixed: page number goes 1 after the ended page number because the page number always +1 in the GotoNextPageRecord()
                    $scope.pageNum-=1;
                }else{
                    DisplayPageNum(pageNum);
                }
            }, function(reason) {
              console.error("Fail in GetNextPageRecords() - "+tagName + ":"+$scope.programId)
              Security.HttpPromiseFail(reason);
            }).finally(function() {
                // Always execute this on both error and success
                if(typeof $scope.CustomGetDataResult == "function"){
                    $scope.CustomGetDataResult(httpResponseObj.data.ActionResult.data, 
                        httpResponseObj.status, 
                        $scope, 
                        $element, 
                        $attrs, 
                        $ctrl);
                }else{
                     CustomGetDataResult(httpResponseObj, 
                        httpResponseObj.status);
                }
                
            }).catch(function(e) {
              Security.HttpPromiseFail(e);
            });

    	}

		$scope.$watch(
		  // This function returns the value being watched. It is called for each turn of the $digest loop
		  function() { return $scope.maxRecordsCount; },
		  // This is the change listener, called when the value returned from the above function changes
		  function(newValue, oldValue) {
		    if ( newValue !== oldValue ) {
		    	var reminders = $scope.maxRecordsCount % $scope.numOfRecordPerPage;
		    	$scope.lastPageNum = ($scope.maxRecordsCount - reminders) / 10;
		    	if(reminders > 0)
		    		$scope.lastPageNum++;
		    }
		  }
		);

        $scope.Initialize();

    }
    function templateFunction(tElement, tAttrs) {
        var template = '' +
        	'<div class="panel panel-default" style="margin: 0px; padding: 0px;">' +
        	'<div class="panel-body">' +
				// list win top bar
				// '<div class="row">' +
				// 	// search box
				// 	'<div class="col-sm-offset-2 col-sm-8 col-xs-12">' +
				// 		'<form class="pageview-search">' +
				// 		    '<div class="input-group">' +
				// 		      '<input type="text" class="form-control" placeholder="Search for...">' +
				// 		      '<span class="input-group-btn">' +
				// 		        '<button class="btn btn-default" type="button">Go!</button>' +
				// 		      '</span>' +
				// 		    '</div>' +
				// 		'</form>' +
				// 	'</div>'+
				// '</div>' +
				// inside of the ng-transclude
				//'<div ng-transclude></div>' +
				'<div class="custom-transclude"></div>' +
            '</div>' +
            '<div class="panel-footer">' +
				// button toolbar
				'<div class="btn-toolbar" role="toolbar" aria-label="Pageview with button groups">' +
					// refresh button
					'<div class="btn-group" role="group">' +
						'<button type="button" class="btn btn-default" ng-click="ClearNRefreshData()" aria-label="Reconnect and Refresh the data">' +
						  '<span class="glyphicon glyphicon-refresh" aria-hidden="true"></span> <span class="hidden-xs">Refresh</span>' +
						'</button>' +
					'</div>' +
					// arrow button
					'<div class="btn-group" role="group">' +
						'<button type="button" class="btn btn-default" ng-click="GotoFirstPageRecord()" aria-label="Go to the first record">' +
						  '<span class="glyphicon glyphicon-step-backward" aria-hidden="true"></span>' +
						'</button>' +
						'<button type="button" class="btn btn-default" ng-click="GotoPreviousPageRecord()" aria-label="Previous record">' +
						  '<span class="glyphicon glyphicon-triangle-left" aria-hidden="true"></span>' +
						'</button>' +
						'<button type="button" class="btn btn-default" ng-click="GotoNextPageRecord()" aria-label="Next record">' +
						  '<span class="glyphicon glyphicon-triangle-right" aria-hidden="true"></span>' +
						'</button>' +
						'<button type="button" class="btn btn-default" ng-click="GotoLastPageRecord()" aria-label="Go to the last record">' +
						  '<span class="glyphicon glyphicon-step-forward" aria-hidden="true"></span>' +
						'</button>' +
					'</div>' +
					// tick / select button
					'<div class="btn-group" role="group" aria-label="...">' +
						'<button type="button" class="btn btn-default" ng-click="SelectedToRecord()" aria-label="Select the pointed record">' +
						  '<span class="glyphicon glyphicon-ok" aria-hidden="true"></span> <span class="hidden-xs">Select</span>' +
						'</button>' +
					'</div>' +
				'</div>' +
				'<div ng-bind="DisplayMessage"></div>'
			'</div>' +
			'</div>' +
			'';
        return template;
    }

	return {
		require: ['ngModel'],
		restrict: 'EA', //'EA', //Default in 1.3+
		transclude: true,

		// scope: [false | true | {...}]
		// false = use parent scope
		// true =  A new child scope that prototypically inherits from its parent
		// {} = create a isolate scope
		scope: true,

		controller: PageViewConstructor,
		controllerAs: 'pageviewCtrl',

		//If both bindToController and scope are defined and have object hashes, bindToController overrides scope.
		bindToController: {
			ngModel: '=',
			numOfRecordPerPage: '=pageRecordsLimit',
			//criteria: '=',
			// editMode: '=?',
			// programId: '=',
			// EventListener: '=',
			// SubmitData: '=',
			// DisplayCustomData: '=',
			// DisplaySubmitDataResultMessage: '=',
		},
		template: templateFunction,
		compile: function compile(tElement, tAttrs, transclude) {
		    return {
		        pre: function preLink(scope, iElement, iAttrs, controller) {
		        },
		        post: function postLink(scope, iElement, iAttrs, controller) {
		        	transclude(scope, function(clone, scope) {
		        		iElement.find('.custom-transclude').append(clone);
		        	});
		        }
		    }
		},
	};
}]);

/**
 * <entry> a entry form use to provide Create/Read/Update/Delete behavior of a single table
 * <entry
    ng-model=""
    program-id=""
    edit-mode=""
    >
 * @param {Object} ng-model - store the data record, the data record may used in CRUD
 * @param {String} program-id - assign the program id to implement the behavior of CRUD
 * @param {String} edit-mode - define the mode [create | view | amend | delete |]
 */
app.directive('entry', ['$rootScope', 
    '$q',
    '$timeout', 
    'Core', 
    'Security', 
    'LockManager', 
    'LoadingModal',
    'HttpRequeset', 
    'MessageService', function($rootScope, $q, $timeout, Core, Security, LockManager, LoadingModal, HttpRequeset, MessageService) {
    function EntryConstructor($scope, $element, $attrs) {
    	var constructor = this;
    	var $ctrl = $scope.entryCtrl;
        var tagName = $element[0].tagName.toLowerCase();

    	var globalCriteria = $rootScope.globalCriteria;
        var backupNgModelObj = {};
        
        var DirectiveProperties = (function () {
            var editMode;
            var programID;

            function findEditMode() {
                var object = $scope.editMode = FindEditModeEnum($attrs.editMode);
                return object;
            }
            function findProgramID(){
                var object = $attrs.programId;
                return object;
            }

            return {
                getEditMode: function () {
                    if (!editMode) {
                        editMode = findEditMode();
                    }
                    return editMode;
                },
                getProgramID: function(){
                    var isProgramIdFound = false;
                    if(!programID){
                        programID = findProgramID;
                    }
                    if(typeof(programID) != undefined){
                        if(programID != null && programID !=""){
                            isProgramIdFound = true;
                        }
                    }
                    
                    if(isProgramIdFound){
                        $scope.programId = $attrs.programId;
                    }
                    else
                        alert("<entry> Must declare a attribute of program-id");
                }
            };
        })();

        function InitializeEntry() {
        	$scope.tableStructure = {};
            DirectiveProperties.getEditMode();
            DirectiveProperties.getProgramID();
            
            $scope.DisplayMessageList = MessageService.getMsg();
        }

        $scope.BackupNgModel = function(){ BackupNgModel(); }
        $scope.RestoreNgModel = function(){ RestoreNgModel(); }
        $scope.FindNClearChildEditbox = function(){ FindNClearChildEditbox(); }

        function BackupNgModel(){
            backupNgModelObj = jQuery.extend([], $ctrl.ngModel);
        }

        function RestoreNgModel(){
            // 20170108, keithpoon, must use option 2, otherwise will break the StatusChange of the watch listener
            // Option 1 will stick the ngModel with the defaulted value object 
            // Option 2 will keep the customized value on the page, such is the prefered language setting

            // Option 1: clone the default object as ngModel
//            $ctrl.ngModel = angular.copy(backupNgModelObj);

            // Option 2: append and overwrite the default value on ngModel
             jQuery.extend(true, $ctrl.ngModel, backupNgModelObj);
        }
        
        // 20170108, keithpoon, add: clear editbox after record created
        function FindNClearChildEditbox(){
            /*Get the elements with the attribute ng-model, in your case this could just be elm.children()*/
            var elms = [].slice.call($element[0].querySelectorAll('editbox[ng-model]'), 0);

            // get the ngModelControllerArray
            // var controllers = elms.map(function(el){
            //   return angular.element(el).controller('ngModel');
            // });
            var scopes = elms.map(function(el){
              return angular.element(el).scope();
            });

            scopes.forEach(function(editboxScope){
                editboxScope.ClearEditboxNgModel();
            });
        }
        
        $scope.ResetForm = function(){
            $scope.RestoreNgModel();
            $scope.FindNClearChildEditbox();
        }
		
		$scope.SetNgModel = function(dataRecord){
			var dataJson = {};
			//dataJson.data = {};
			//dataJson.data.Items = [];
			//dataJson.data.Items[1] = dataRecord;
			//console.dir(dataJson)
			//SetNgModel(dataJson);
			SetNgModel(dataRecord);
		}

        function SetNgModel(dataJson){
//        	var items = dataJson.data.Items[1];
//        	var itemsColumn = dataJson.data.DataColumns;
            var dataRecord = dataJson.ActionResult.data[0];
            
            var tableSchema = dataJson.ActionResult.table_schema;

        	for(var rowIndex in tableSchema){
        		var row = tableSchema[rowIndex];
                var columnName = row.Field;
        		var colDataType = Core.ConvertMySQLDataType(row.Type);

                var isSystemField = Core.IsSystemField(columnName);
                if(isSystemField)
                    continue;
                
                var newColumn = dataRecord[columnName];
                var dataValue = dataRecord[columnName];

//        		// is column exists in ngModel
        		if(typeof(dataValue) == "undefined" || !dataValue){
        			if(colDataType == "string"){
        				dataValue = "";
        			}
        			else if (colDataType == "date"){
        				dataValue = new Date(0, 0, 0);
        			}
        			else if (colDataType == "double"){
        				dataValue = 0.0;
        			}
        		}
                
                console.log(typeof dataValue);
                console.log(dataValue);
                
        		if (colDataType == "date"){
                    if(typeof dataValue == "string"){
                        var dateArray = dataValue.split("-");
                        var year = dateArray[0]; var month = dateArray[1]; var day = dateArray[2];
                        year = parseInt(year);
                        month = parseInt(month);
                        day = parseInt(day);
                        newColumn = new Date(year, month, day);
                    }else{
                        newColumn = dataValue;
                    }
    			}
    			else if (colDataType == "double"){
    				newColumn = parseFloat(dataValue);
    			}
//    			else{
//    				newColumn = items[colIndex];
//    			}

                $ctrl.ngModel[columnName] = newColumn;
        	}

//            if(items == null || typeof(items) == "undefined"){
//                console.log("Responsed {data:items{}} is null")
//                return;
//            }
//
//        	for(var colIndex in itemsColumn){
//        		var columnName = itemsColumn[colIndex];
//
//                var isSystemField = Core.IsSystemField(columnName);
//                if(isSystemField)
//                    continue;
//
//        		var colDataType = Core.ConvertMySQLDataType(itemsColumn[colIndex].type);
//
//        		// is column exists in ngModel
//        		if(typeof($ctrl.ngModel[columnName]) == "undefined"){
//        			if(colDataType == "string"){
//        				$ctrl.ngModel[columnName] = "";
//        			}
//        			else if (colDataType == "date"){
//        				$ctrl.ngModel[columnName] = new Date(0, 0, 0);
//        			}
//        			else if (colDataType == "double"){
////        				$ctrl.ngModel[columnName] = 0.0;
//        			}
//        		}
//        		var newColumn = $ctrl.ngModel[columnName];
//
//        		if (colDataType == "date"){
//					console.log(typeof items[colIndex]);
//					var dateArray = items[colIndex].split("-");
//					var year = dateArray[0]; var month = dateArray[1]; var day = dateArray[2];
//					year = parseInt(year);
//					month = parseInt(month);
//					day = parseInt(day);
//    				newColumn = new Date(year, month, day);
//    			}
//    			else if (colDataType == "double"){
//    				newColumn = parseFloat(items[colIndex]);
//    			}
//    			else{
//    				newColumn = items[colIndex];
//    			}
//
//                $ctrl.ngModel[columnName] = newColumn;
//        	}

        }
        function GetTableStructure(){
            // $scope.LockAllControls();
        	var url = $rootScope.serverHost;
        	var clientID = Security.GetSessionID();
        	var programId = $scope.programId.toLowerCase();
			var submitData = {
				"Session": clientID,
				"Table": programId
			};
            submitData.Action = "GetTableStructure";
            
            var globalCriteria = $rootScope.globalCriteria;

            var requestOption = {
                method: 'POST',
                data: JSON.stringify(submitData)
            };
            var request = HttpRequeset.send(requestOption);
            request.then(function(responseObj) {
                console.log("ProgramID: "+programId+", Table structure obtained.")
                var data = responseObj.data;
                SetTableStructure(data);
            }, function(reason) {
              console.error("Fail in GetTableStructure() - "+tagName + ":"+$scope.programId)
              Security.HttpPromiseFail(reason);
            }).finally(function() {
                // Always execute this on both error and success
            });

            return request;
        }
        function SetTableStructure(dataJson){
        	$scope.tableStructure = dataJson;
        	var itemsColumn = dataJson.DataColumns;

            if($ctrl.ngModel == null)
                $ctrl.ngModel = {};

        	for(var colIndex in itemsColumn){
        		var columnName = colIndex;
        		var colDataType = Core.ConvertMySQLDataType(itemsColumn[colIndex].type);

        		var isSystemField = Core.IsSystemField(columnName);

        		if(isSystemField)
        			continue;

        		var isScopeColExists = true;
        		var colObj;

        		// scope did not defined this column
        		if($ctrl.ngModel == null){
        			isScopeColExists = false;
        		}
        		else if(typeof($ctrl.ngModel[columnName]) == "undefined")
        		{
        			isScopeColExists = false;
        		}

    			if(colDataType == "string"){
    				colObj = "";
    			}
    			else if (colDataType == "date"){
    				colObj = new Date(0, 0, 0);
    			}
    			else if (colDataType == "double"){
//    				colObj = 0.0;
    			}

    			if(!isScopeColExists){

    			}else{

    				// if the data type equal
    				if(typeof($ctrl.ngModel[columnName]) === typeof(colObj)){
                        // if the scope already per-defined some value before GetTableStructure() and SetDefaultValue()
                        if($ctrl.ngModel[columnName] != colObj)
    					   colObj = $ctrl.ngModel[columnName];
    				}else{
    					console.warn("The pre-defined default value data type not match of the table structure");
    					console.warn("ProgramID: "+$scope.programId +
    						", colName:"+columnName+
    						", colDataType:"+colDataType+
    						", $ctrl.ngModel:"+$ctrl.ngModel[columnName]);
    				}
    			}

    			//$scope[columnName] = colObj;

    			$ctrl.ngModel[columnName] = colObj;
        	}
        }

        function ConvertKeyFieldToUppercase(recordObj, isRemoveNonKeyField){
            var isKeyValid = true;
            var upperRecordObj = {};

            var tbStructure = $scope.tableStructure;
            var itemsColumn = tbStructure.DataColumns;
            
            if(typeof(itemsColumn) == "undefined"){
                return recordObj;
            }

            if(typeof(isRemoveNonKeyField) == "undefined" || isRemoveNonKeyField == null)
                var isRemoveNonKeyField = false;

            var keyColumnList = tbStructure.KeyColumns;

            for(var keyIndex in keyColumnList){
                var colName = keyColumnList[keyIndex];
                var keyColIndex = 0;
                var colDataType = "";

                // key column in table structure not match with param
                if(!recordObj.hasOwnProperty(colName)){
                    isKeyValid = false;
                    break;
                }else{
                    upperRecordObj[colName] = recordObj[colName];
                }

                // find the key column data type
                for(var colNameIndex in itemsColumn){
                    if(colName == itemsColumn[colNameIndex])
                    {
                        keyColIndex = colNameIndex
                        break;
                    }
                }
                colDataType = itemsColumn[colName].type;
                colDataType = Core.ConvertMySQLDataType(colDataType)

                // convert to upper case if the key column is a string data type
                if(colDataType == "string"){
                    upperRecordObj[colName] = upperRecordObj[colName].toUpperCase();
                }
            }

            // console.dir(upperRecordObj)

            if(!isKeyValid){
                console.log("Avoid to FindData(), upperRecordObj was incomplete.");
                return;
            }

            if(!isRemoveNonKeyField){
                for(var colName in recordObj){
                    if(!upperRecordObj.hasOwnProperty(colName)){
                        upperRecordObj[colName] = recordObj[colName];
                    }
                }
            }

            return upperRecordObj;
        }

        function TryToCallInitDirective(){
            if(typeof $scope.InitDirective == "function"){
                $scope.InitDirective($scope, $element, $attrs, $ctrl);
            }else{
                $scope.DefaultInitDirective();
            }
        }
        $scope.Initialize = function(){
            $scope.InitScope();
            if(typeof $scope.EventListener == "function"){
                $scope.EventListener($scope, $element, $attrs, $ctrl);
            }else{
                EventListener();
            }
            TryToCallInitDirective();
        }
        $scope.InitScope = function(){
            InitializeEntry();
        }
        $scope.DefaultInitDirective = function(){
            var getTableStructurePromiseResult = GetTableStructure();
            getTableStructurePromiseResult.then(function(){
                // the controls inside the directive was locked in the post render
                if($scope.editMode == globalCriteria.editMode.Create){
                    TryToCallSetDefaultValue();   
                }
                $scope.BackupNgModel();
                if($scope.editMode != globalCriteria.editMode.Delete && $scope.editMode != globalCriteria.editMode.View)
                    $scope.UnLockAllControls();
            });
            
        }

        /**
         * Find a record by key value
         * @param {Object} tempKeyObj - provide keyObj to find the specified record
         */
        $scope.FindData = function(){
            var clientID = Security.GetSessionID();
            var programId = $scope.programId.toLowerCase();
            
            var tempKeyObj = $ctrl.ngModel;

            var isAllKeyExists = IsKeyInDataRow(tempKeyObj);
            if(!isAllKeyExists){
                return;
            }
                
            var isKeyValid = true;
            var keyObj = {};
            keyObj = ConvertKeyFieldToUppercase(tempKeyObj, true);

            if(!keyObj)
                isKeyValid = false;
            
            if(!isKeyValid){
                console.log("Avoid to FindData(), keyObj was incomplete.");
                return;
            }
            
        	var findObj = {
        		"Header":{}
        	}
        	findObj.Header[1] = {};
            findObj.Header[1] = keyObj;

        	var isRowEmpty = jQuery.isEmptyObject(findObj.Header[1])
        	if(isRowEmpty){
                return $q.reject("Cannot update a empty Record");
        	}

			var submitData = {
				"Session": clientID,
				"Table": programId,
				"Data": findObj,
				//"NextPage" : "true"
			};
            submitData.Action = "FindData";

            var requestOption = {
                method: 'POST',
                data: JSON.stringify(submitData)
            };

            var request = HttpRequeset.send(requestOption);
            request.then(function(responseObj) {
                var data_or_JqXHR = responseObj.data;
                // need to handle if record not found.
				SetNgModel(data_or_JqXHR);
            }, function(reason) {
              console.error("Fail in FindData() - "+tagName + ":"+$scope.programId)
              Security.HttpPromiseFail(reason);
            }).finally(function() {
                // Always execute this on both error and success
                // if(typeof $scope.CustomGetDataResult == "function"){
                //     $scope.CustomGetDataResult(data_or_JqXHR, textStatus, jqXHR_or_errorThrown, $scope, $element, $attrs, $ctrl);
                // }else{
                //     CustomGetDataResult(data_or_JqXHR, textStatus, jqXHR_or_errorThrown);
                // }
            });
            return request;
        }

        /**
         * Get the data in the result set as JSON format
         * @param {Object} keyObj - provide keyObj will read next to that key
         * @param {String} criteriaObj - the criteria will pass to the backend program, you need to extract and handle the criteria in it.
         */
        $scope.GetData = function(keyObj, criteriaObj){
            var clientID = Security.GetSessionID();
            var programId = $scope.programId.toLowerCase();
            for(var keyIndex in keyObj){
                if(typeof(keyObj[keyIndex]) == "string")
                    keyObj[keyIndex] = keyObj[keyIndex].toUpperCase();
            }
            //var criteriaObj = $scope.criteriaObj;

            var submitData = {
                "Session": clientID,
                "Table": programId,
                "key": keyObj,
                criteria: criteriaObj,
                "NextPage" : "true"
            };
            submitData.Action = "GetData";

            var requestOption = {
                method: 'POST',
                data: JSON.stringify(submitData)
            };

            var request = HttpRequeset.send(requestOption);
            request.then(function(responseObj) {
                var data_or_JqXHR = responseObj.data;
                $scope.$apply(function () {
                    SetNgModel(data_or_JqXHR);
                });
            }, function(reason) {
              console.error("Fail in GetData() - "+tagName + ":"+$scope.programId)
              Security.HttpPromiseFail(reason);
            }).finally(function() {
                // Always execute this on both error and success

                // if(typeof $scope.CustomGetDataResult == "function"){
                //     $scope.CustomGetDataResult(data_or_JqXHR, textStatus, jqXHR_or_errorThrown, $scope, $element, $attrs, $ctrl);
                // }else{
                //     CustomGetDataResult(data_or_JqXHR, textStatus, jqXHR_or_errorThrown);
                // }
            });
            return request;
        }

        $scope.SubmitData = function(){
        	console.log("<"+$element[0].tagName+"> submitting data")
            var globalCriteria = $rootScope.globalCriteria;

        	$scope.LockAllControls();
            
            if(!ValidateSubmitData()){
                return;
            }
            
            $scope.ShowLoadModal();
            SubmitData();
        }
		
        function ValidateSubmitData(){
            var isValid = true;
        	var editMode = DirectiveProperties.getEditMode();

        	// if Buffer invalid, cannot send request
        	var isBufferValid = true;
			if(typeof $scope.ValidateBuffer == "function"){
				isBufferValid = $scope.ValidateBuffer($scope, $element, $attrs, $ctrl);
			}else{
				isBufferValid = ValidateBuffer();
			}
            isValid = isValid && isBufferValid;
			if(!isBufferValid && editMode != globalCriteria.editMode.Delete){
                if(editMode == globalCriteria.editMode.Create || 
                    editMode == globalCriteria.editMode.Amend)
                $scope.UnLockAllControls();
            }

            var tbStructure = ValidateTableStructure();
            isValid = isValid && tbStructure;
            
            return isValid;
        }
        function SubmitData(){
            var httpResponseObj = {};
            var submitPromise;
        	var editMode = DirectiveProperties.getEditMode();
            var msg = "";
            
			if(editMode == globalCriteria.editMode.Create){
				if(typeof $scope.CreateData == "function"){
	            	submitPromise = scope.CreateData($ctrl.ngModel, $scope, $element, $attrs, $ctrl);
	            }else{
	            	submitPromise = CreateData($ctrl.ngModel);
	            }
                
                submitPromise.then(function(responseObj) {
                    httpResponseObj = responseObj;
                    var data_or_JqXHR = responseObj.data;
                    msg = data_or_JqXHR.Message;

                    $scope.ResetForm();
                }, function(reason) {
                  console.error(tagName + ":"+$scope.programId + " - Fail in CreateData()")
                  throw reason;
                });
			}
			else if(editMode == globalCriteria.editMode.Amend){
				if(typeof $scope.UpdateData == "function"){
	            	submitPromise = scope.UpdateData($ctrl.ngModel, $scope, $element, $attrs, $ctrl);
	            }else{
	            	submitPromise = UpdateData($ctrl.ngModel);
	            }
                
                submitPromise.then(function(responseObj) {
                    httpResponseObj = responseObj;
                    var data_or_JqXHR = responseObj.data;
                    msg = data_or_JqXHR.Message;

                    // the lastUpdateDate was changed after record updated, user cannot click the Amend button again.
                    // reget the record or clean the record
//                    $scope.ResetForm();
                    $scope.FindData();
                }, function(reason) {
                  console.error(tagName + ":"+$scope.programId + " - Fail in UpdateData()")
                  throw reason;
                })
			}
			else if(editMode == globalCriteria.editMode.Delete){
				if(typeof $scope.DeleteData == "function"){
	            	submitPromise = scope.DeleteData($ctrl.ngModel, $scope, $element, $attrs, $ctrl);
	            }else{
	            	submitPromise = DeleteData($ctrl.ngModel);
	            }
                submitPromise.then(function(responseObj) {
                    httpResponseObj = responseObj;
                    var data_or_JqXHR = responseObj.data;
                    msg = data_or_JqXHR.Message;

                    $scope.ResetForm();
                    SetTableStructure($scope.tableStructure);
                }, function(reason) {
                  console.error(tagName + ":"+$scope.programId + " - Fail in DeleteData()")
                  throw reason;
                })
			}
            
            submitPromise.catch(function(e){
                // handle errors in processing or in error.
                console.log("Submit data error catch in entry");
                Security.HttpPromiseFail(e);
            }).finally(function() {
                // Always execute unlock on both error and success
                $scope.UnLockAllControls();
                $scope.HideLoadModal();
                    
                MessageService.addMsg(msg);
                SubmitDataResult(httpResponseObj, httpResponseObj.status);
                $scope.HideLoadModal();
                
                if(typeof $scope.CustomSubmitDataResult == "function"){
                    $scope.CustomSubmitDataResult(httpResponseObj, 
                        httpResponseObj.status, 
                        $scope, 
                        $element, 
                        $attrs, 
                        $ctrl);
                }
            }).catch(function(e){
                // handle errors in processing or in error.
                console.warn(e)
            })
            
            return submitPromise;
        }
        $scope.LockAllControls = function(){
            LockAllControls();
        }
        $scope.LockAllInputBox = function(){
            LockAllInputBox();
        }
        $scope.UnLockSubmitButton = function(){
            UnLockSubmitButton();
        }
        $scope.UnLockAllControls = function(){
			$timeout(function(){
        		UnLockAllControls();
			  	}, 2000); // (milliseconds),  1s = 1000ms
        }
        
        $scope.ShowLoadModal = function(){
            LoadingModal.showModal();
        }
        $scope.HideLoadModal = function(){
            LoadingModal.hideModal();
        }

        // StatusChange() event listener
		$scope.$watch(
		  // This function returns the value being watched. It is called for each turn of the $digest loop
		  function() { return $ctrl.ngModel; },
		  // This is the change listener, called when the value returned from the above function changes
		  function(newValue, oldValue) {
		  	var changedField = "";
		  	var changedValue;

		    if ( newValue !== oldValue ) {
		    	for(var colIndex in $ctrl.ngModel){
	    			changedField = colIndex;
	    			changedValue = newValue[colIndex];

	    			if(oldValue!=null){
	    				if ( Object.prototype.hasOwnProperty ) {
			    			if(oldValue.hasOwnProperty(colIndex))
			    			{
                                if(oldValue[colIndex] === newValue[colIndex]){
                                    continue;
                                }
                                if(oldValue[colIndex] == newValue[colIndex]){
                                    continue;
                                }
			    			}
		    			}
	    			}

                    // Convert to Uppercase, if the chagned field is a Key and data type is string
                    // newValue = ConvertKeyFieldToUppercase(newValue, false);

					if(typeof $scope.StatusChange == "function"){
						$scope.StatusChange(colIndex, changedValue, newValue, $scope, $element, $attrs, $ctrl);
					}else{
						StatusChange();
					}
		    	}
		    }
		  },
		  true
		);

        function LockAllControls(){
            LockManager.LockAllControls($element, tagName);
        }
        function UnLockAllControls(){
            LockManager.UnLockAllControls($element, tagName);
        }
        function LockAllInputBox(){
            LockManager.LockAllInputBox($element, tagName);
        }
        function UnLockSubmitButton(){
            LockManager.UnLockSubmitButton($element, tagName);
        }

        function TryToCallSetDefaultValue(){
            if(typeof $scope.SetDefaultValue == "function"){
                $scope.SetDefaultValue($scope, $element, $attrs, $ctrl);
            }else{
                SetDefaultValue();
            }
        }

        function TryToCallIsLimitModelStrictWithSchema(){
            var isLimitModelStrictWithSchema = false;
            if(typeof $scope.IsLimitModelStrictWithSchema == "function"){
                isLimitModelStrictWithSchema = $scope.IsLimitModelStrictWithSchema($scope, $element, $attrs, $ctrl);
            }else{
                isLimitModelStrictWithSchema = IsLimitModelStrictWithSchema();
            }
            return isLimitModelStrictWithSchema;
        }

        function InitDirective(){
            console.log("scope.$id:"+$scope.$id+", may implement $scope.InitDirective() function in webapge");
        }
		function EventListener(){
			console.log("scope.$id:"+$scope.$id+", may implement $scope.EventListener() function in webapge");
		}
		function SetDefaultValue(){
			console.log("scope.$id:"+$scope.$id+", may implement $scope.SetDefaultValue() function in webapge");
		}
		function StatusChange(){
			console.log("scope.$id:"+$scope.$id+", may implement $scope.StatusChange() function in webapge");	
		}
		function ValidateBuffer(){
			console.log("scope.$id:"+$scope.$id+", may implement $scope.ValidateBuffer() function in webapge");	
			return true;
		}
        function ValidateTableStructure(){
            var isTbStructureValid = true;

            var tbStructure = $scope.tableStructure;
            var itemsColumn = tbStructure.DataColumns;

            if(typeof(itemsColumn) == "undefined"){
                alert("Table structure is null, avoid to execute.");
                isTbStructureValid = false;
            }
            return isTbStructureValid;
        }
        function IsLimitModelStrictWithSchema(){
            return true;
        }
        function CustomGetDataResult(data_or_JqXHR, textStatus, jqXHR_or_errorThrown){
            var progID = $scope.programId;
            //console.log("scope.$id:"+$scope.$id+", programId:"+progID+", must implement $scope.CustomGetDataResult() function in webapge");
        }
        function SubmitDataResult(data_or_JqXHR, textStatus, jqXHR_or_errorThrown){

        }

        /**
         * Valid the key columns
         * @param {Object} recordObj - provide the record which is going to perform CRUD
         * @return {bool} - true if key columns are exists, not null and empty. false otherwise
         */
        function IsKeyInDataRow(recordObj){
            var tbStructure = $scope.tableStructure;
            var itemsColumn = tbStructure.DataColumns;
            var keyColumn = tbStructure.KeyColumns;

            var isAllKeyExists = true;
            for(var keyIndex in keyColumn){
                var keyColName = keyColumn[keyIndex];
                if(typeof(recordObj[keyColName]) == "undefined"){
                    isAllKeyExists = false;
                    continue;
                }
                // find the data type
                var dataTypeFound = false;
                var keyColDataType = "";
                for (var colIndex in itemsColumn) {
                    var colName = colIndex;;
                    var colDataType = Core.ConvertMySQLDataType(itemsColumn[colIndex].type);
                    var colValue = recordObj[colName];
                    if(keyColName == colName){
                        dataTypeFound = true;
                        keyColDataType = colDataType;
                        break;
                    }
                }

                if(keyColDataType == "string"){
                    if(recordObj[keyColName] == null || recordObj[keyColName] == "")
                    {
                        isAllKeyExists = false;
                        continue;
                    }
                }

            }

            return isAllKeyExists;
        }

        /**
         * Convert the entry model strict with schema
         * @param {Object} recordObj - provide the ngModel of entry
         * @return {Object} strictObj - a new record row strict with the table schema
         */
        function ConvertEntryModelStrictWithSchema(recordObj){
            var tbStructure = $scope.tableStructure;
            var itemsColumn = tbStructure.DataColumns;

            var keyColumn = tbStructure.KeyColumns;

            var strictObj = {};
            for (var colIndex in itemsColumn) {
                var colName = colIndex;
                var colDataType = Core.ConvertMySQLDataType(itemsColumn[colIndex].type);
                var colValue = recordObj[colName];

                if(typeof(colValue) == "undefined"){
                    continue;
                }
                // 20170111, keithpoon, also allowed to assign empty, if the user want to update the record from text to empty
//                if(colDataType == "string"){
//                    if(colValue == null || colValue == ""){
//                        continue;
//                    }
//                }
//                if(colDataType == "double"){
//                    var colValueDouble = parseFloat(colValue);
//                    if(colValueDouble == 0){
//                        continue;
//                    }
//                }
                
                strictObj[colIndex] = colValue;
            }
            return strictObj;
        }

        function CreateData(recordObj){
        	var clientID = Security.GetSessionID();
        	var programId = $scope.programId.toLowerCase();

        	var tbStructure = $scope.tableStructure;
        	var itemsColumn = tbStructure.DataColumns;

            var isAllKeyExists = IsKeyInDataRow(recordObj);
            if(!isAllKeyExists){
                return $q.reject("Please provide the value of mandatory column. Avoid to create data.");
            }

        	var createObj = {
                "Header":{},
                "Items":{}
        	}
            var isModelStrictWithSchema = TryToCallIsLimitModelStrictWithSchema();

        	createObj.Header[1] = {}
            if(isModelStrictWithSchema)
                createObj.Header[1] = ConvertEntryModelStrictWithSchema(recordObj);
            else
                createObj.Header[1] = recordObj;

			var submitData = {
				"Session": clientID,
				"Table": programId,
				"Data": createObj,
			};
            submitData.Action = "CreateData";

            var requestOption = {
                method: 'POST',
                data: JSON.stringify(submitData)
            };

            var request = HttpRequeset.send(requestOption);
            return request;
        }

        function UpdateData(recordObj){
        	var clientID = Security.GetSessionID();
        	var programId = $scope.programId.toLowerCase();

        	var tbStructure = $scope.tableStructure;
        	var itemsColumn = tbStructure.DataColumns;

            var isAllKeyExists = IsKeyInDataRow(recordObj);
            if(!isAllKeyExists){
                return $q.reject("Please provide the value of mandatory column. Avoid to update data.");
            }
            
        	var updateObj = {
        		"Header":{},
        		"Items":{}
        	}
        	updateObj.Header[1] = {};
        	//updateObj.Header[1] = recordObj;            
            updateObj.Header[1] = ConvertEntryModelStrictWithSchema(recordObj);

        	var isRowEmpty = jQuery.isEmptyObject(updateObj.Header[1])
        	if(isRowEmpty){
                return $q.reject("Cannot update a empty Record");
        	}

			var submitData = {
				"Session": clientID,
				"Table": programId,
				"Data": updateObj,
				//"NextPage" : "true"
			};
            submitData.Action = "UpdateData";

            var requestOption = {
                method: 'POST',
                data: JSON.stringify(submitData)
            };

            var request = HttpRequeset.send(requestOption);
            return request;
        }

        function DeleteData(recordObj){
        	var clientID = Security.GetSessionID();
        	var programId = $scope.programId.toLowerCase();

        	var tbStructure = $scope.tableStructure;
        	var itemsColumn = tbStructure.DataColumns;
            var keyColumn = tbStructure.KeyColumns;

            var isAllKeyExists = IsKeyInDataRow(recordObj);
            if(!isAllKeyExists){
                return $q.reject("Key not complete in record, avoid to delete data.");
            }

        	var deleteObj = {
        		"Header":{},
        		"Items":{}
        	}
        	deleteObj.Header[1] = {};
        	//deleteObj.Header[1] = recordObj;
//            deleteObj.Header[1] = ConvertKeyFieldToUppercase(recordObj, true);
            deleteObj.Header[1] = ConvertKeyFieldToUppercase(recordObj, true);

        	var isRowEmpty = jQuery.isEmptyObject(deleteObj.Header[1]);

        	if(isRowEmpty){
                return $q.reject("Cannot delete a empty Record");
        	}

			var submitData = {
				"Session": clientID,
				"Table": programId,
				"Data": deleteObj,
				//"NextPage" : "true"
			};
            submitData.Action = "DeleteData";

            var requestOption = {
                method: 'POST',
                data: JSON.stringify(submitData)
            };

            var request = HttpRequeset.send(requestOption);
            return request;
        }

        $scope.Initialize();
    }

    function FindEditModeEnum(attrEditMode){
        var globalCriteria = $rootScope.globalCriteria;
        var isEditModeFound = false;
        var isEditModeNumeric = false;
        var editMode = 0;

        if(typeof(attrEditMode) != undefined){
            if(attrEditMode != null && attrEditMode !=""){
                isEditModeFound = true;
            }
        }
        if(isEditModeFound){
            isEditModeNumeric = !isNaN(parseInt(attrEditMode));
        }
        if(!isEditModeFound){
            editMode = globalCriteria.editMode.None;
        }else{
            if(isEditModeNumeric){
                editMode = attrEditMode;
            }
            else{
                attrEditMode = attrEditMode.toLowerCase();
                if(attrEditMode == "none"){
                    editMode = globalCriteria.editMode.None;
                }
                else if(attrEditMode == "create"){
                    editMode = globalCriteria.editMode.Create;
                }
                else if(attrEditMode == "amend"){
                    editMode = globalCriteria.editMode.Amend;
                }
                else if(attrEditMode == "delete"){
                    editMode = globalCriteria.editMode.Delete;
                }
                else if(attrEditMode == "view"){
                    editMode = globalCriteria.editMode.View;
                }
                else if(attrEditMode == "copy"){
                    editMode = globalCriteria.editMode.Copy;
                }
                else if(attrEditMode == "null"){
                    editMode = globalCriteria.editMode.Null;
                }
                else if(attrEditMode.indexOf("amend") >-1 && 
                        attrEditMode.indexOf("delete") >-1 )
                {
                        editMode = globalCriteria.editMode.AmendAndDelete;
                }
                else{
                    throw ("Unable to identify the edit mode '"+attrEditMode+"' on entry");
                }
            }
        }
        return editMode;
    }
    function templateFunction(tElement, tAttrs) {
        var globalCriteria = $rootScope.globalCriteria;

        var template = '' +
          // outside of the ng-transclude
          // '<div>'+
          // '</div>' +
          // '<div class="well well-sm">'+
          // '<p ng-repeat="dspMsg in DisplayMessageList track by $index" ng-bind="dspMsg"></p>'+
          // '</div>' +
          // inside of the ng-transclude
          //'<div ng-transclude></div>' +
          '<div class="custom-transclude"></div>';
        return template;
    }

	return {
		require: ['ngModel'],
		restrict: 'EA', //'EA', //Default in 1.3+
		transclude: true,

		// scope: [false | true | {...}]
		// false = use parent scope
		// true =  A new child scope that prototypically inherits from its parent
		// {} = create a isolate scope
		scope: true,

		controller: EntryConstructor,
		controllerAs: 'entryCtrl',

		//If both bindToController and scope are defined and have object hashes, bindToController overrides scope.
		bindToController: {
			ngModel: '=',
			//editMode: '=?',
			// programId: '=',
/*
			EventListener: '=',
			SubmitData: '=',
			*/
		},
		template: templateFunction,
		compile: function compile(tElement, tAttrs, transclude) {
		    return {
		        pre: function preLink(scope, iElement, iAttrs, controller) {
		            //console.log("entry preLink() compile");
		        },
		        post: function postLink(scope, iElement, iAttrs, controller) {
		            //console.log("entry postLink() compile");

                    // "scope" here is the directive's isolate scope 
                    // iElement.find('.custom-transclude').append(
                    // );
                    transclude(scope, function (clone, scope) {
                        iElement.find('.custom-transclude').append(clone);
                    })

                    // lock controls should put post here, 
                    var globalCriteria = $rootScope.globalCriteria;
                    if(scope.editMode == globalCriteria.editMode.None || 
                        scope.editMode == globalCriteria.editMode.Null ||
                        scope.editMode == globalCriteria.editMode.Create ||
                        scope.editMode == globalCriteria.editMode.View ||
                        scope.editMode == globalCriteria.editMode.Delete 
                    ){
                        // require table structure, lock all control.
                        // the controls will be unlock after table structre received.
//                        console.log("Mode is [View | Delete | None | Null], lock all controls")
                        iElement.ready(function() {
                            if(scope.editMode == globalCriteria.editMode.Delete)
                                scope.LockAllInputBox();
                            else
                                scope.LockAllControls();
                        })
                    }
		        }
		    }
		    // or
		    // return function postLink( ... ) { ... }
		},
	};
}]);

/**
 * <screen> element to display a share html
 * <screen
    program-id=""
    >
 * @param {String} program-id - optional to define, default as parent scope.programId
 */
app.directive('screen', ['Security', '$rootScope', function(Security, $rootScope) {
    function ScreenConstructor($scope, $element, $attrs) {
    	function Initialize() {
            $scope.screenURL = "";
    	}
    	Initialize();
    }
    function templateURLFunction(tElement, tAttrs) {
    	var templateURL = "";
    	var programId = "";
    	if(typeof(tAttrs.programId) != "undefined"){
    		if(tAttrs.programId != ""){
    			programId = tAttrs.programId;
    		}
    	}

    	templateURL = $rootScope.screenTemplate + programId.toLowerCase() + ".html";

    	return templateURL;
    }
    function templateFunction(tElement, tAttrs){
        var template = "" + 
            "<div ng-include='screenURL'></div>";

        return template;
    }

	return {
        require: ['?editbox', '?pageview', '^ngModel'],
		restrict: 'E',
		transclude: true,
		scope: true,

		controller: ScreenConstructor,
		controllerAs: 'screenCtrl',

		// bindToController: {
		// 	ngModel: '=',
		// },
		//templateUrl : templateURLFunction,
        template: templateFunction,
		compile: function compile(tElement, tAttrs, transclude) {
		    return {
		        pre: function preLink(scope, iElement, iAttrs, controller) {
                    transclude(scope, function(clone, scope) {
                        var element = angular.element(iElement);
                        var programId = "";

                        // find the attr programId
                        var isProgramIdFound = false;
                        if(typeof(iAttrs.programId) != undefined){
                            if(iAttrs.programId != null && iAttrs.programId !=""){
                                isProgramIdFound = true;
                            }
                        }
                        // assign parent programId if programId attribute not found
                        if(isProgramIdFound){
                            programId = iAttrs.programId;
                        }
                        else
                            programId = scope.$parent.programId.toLowerCase();

                        scope.screenURL = $rootScope.screenTemplate + programId.toLowerCase() + ".html";
                    });
		        },
		        post: function postLink(scope, iElement, iAttrs, controller) {
		        }
		    }
		},
	};
}]);

/**
 * <editbox> auto generate a invisible <pageview> element, Popup a modal and the pageview when the user click the edit button 
 * <editbox
    ng-model=""
    program-id=""
    >
 * @param {Object} ng-model - store the user selected record from the pageview, to display the record details on the UI.
 * @param {String} program-id - the pageview will display the records regarding to this program id
 */
app.directive('editbox', ['Security', '$rootScope', '$compile', function(Security, $rootScope, $compile) {
    function EditboxConstructor($scope, $element, $attrs) {
        var constructor = this;
        var $ctrl = $scope.editboxCtrl;

    	function InitializeEditBox() {
            $scope.editboxDataList = [];
            if(typeof($ctrl.ngModel) == "undefined" || $ctrl.ngModel == null)
                $ctrl.ngModel = {};

		    // check attribute programId
            var isProgramIdFound = false;
            if(typeof($attrs.programId) != undefined){
            	if($attrs.programId != null && $attrs.programId !=""){
            		isProgramIdFound = true;
            	}
            }
            if(isProgramIdFound){
            	$scope.programId = $attrs.programId;
            }
            else
            	alert("<editbox> Must declare a attribute of program-id");

		    // check attribute screenId
            var isScreenIdFound = false;
            if(typeof($attrs.screenId) != undefined){
            	if($attrs.screenId != null && $attrs.screenId !=""){
            		isScreenIdFound = true;
            	}
            }
            if(isScreenIdFound){
            	$scope.screenId = $attrs.screenId;
            }else{
                $scope.screenId = "";
                if(typeof($scope.programId) != "undefined")
                    $scope.screenId = $scope.programId;
            }
            
            var isInRange = IsParentInRange();
            if(isInRange){
                var isRangeProperty = false;
                if(typeof($attrs.range) != undefined){
                    if($attrs.range != null && $attrs.range !=""){
                        isRangeProperty = true;
                    }
                }
                if(isRangeProperty){
                    $scope.range = $attrs.range;
                }
                else{
                    console.warn("<editbox> Should declare a attribute of range, since it is under <range>");
                }
                
                var isRangeValuePropery = false;
                if(typeof($attrs.rangeValue) != undefined){
                    if($attrs.rangeValue != null){
                        isRangeValuePropery = true;
                    }
                }
                if(isRangeValuePropery){
                    // cannot assign ALL to $attrs.rangeValue, it will break the binding between $attrs.rangeValue <--> expression
                    // default set start as ALL
//                    if($attrs.range == "start")
//                        $attrs.rangeValue = "ALL";
                    
                    $attrs.$observe('rangeValue', function(interpolatedValue){                        
                        $scope.rangeValue = interpolatedValue;
                        
                        if(typeof ($scope.$parent.SetRange) == "function"){
                            $scope.$parent.SetRange($scope.range, interpolatedValue)
                        }
                    })
                }
                else{
                    console.warn("<editbox> Should declare a attribute of rangeValue, since it is under <range>");
                }
            }
    	}
        
        function IsParentInRange(){
            
            var isParentScopeFromRange = false;
            if(typeof ($scope.$parent.IsRange) == "function"){
                isParentScopeFromRange = true;
                isParentScopeFromRange = $scope.$parent.IsRange();
            }
            
            return isParentScopeFromRange;
        }

        function TryToCallInitDirective(){
            if(typeof $scope.InitDirective == "function"){
                $scope.InitDirective($scope, $element, $attrs, $ctrl);
            }else{
                $scope.DefaultInitDirective();
            }
        }

        $scope.DefaultInitDirective = function(){
            console.log("scope.$id:"+$scope.$id+", may implement $scope.InitDirective() function in webapge");
        }
        function EventListener(){
            console.log("$scope.$id:"+$scope.$id+", must implement $scope.EventListener() function in webapge");
        }
    	function PopupModal(){
            var modal = $element.find(".pageview-modal");
            modal.addClass("fade in");
            modal.show();

    		modal.click(function( event ) {
			  $scope.ClosePageView();
			});

    	}

        $scope.Initialize = function(){
            InitializeEditBox();
        }
        

    	$scope.PopupPageview = function(){
            var modalContainer = angular.element($element).find(".pageview-modal");
            var pageview = angular.element($element).find("pageview");

            // hide the body scroll bar when showing modal dialog
            $("body").css("overflow", "hidden");

            // var winHeight = jQuery(window).height();
            // var winWidth = jQuery(window).width();

            // var pageviewHeight = pageview.height();
            // var pageviewWidth = pageview.width();
            // var scrollTop = jQuery(window).scrollTop();
            // var scrollLeft = jQuery(window).scrollLeft();

            // var modalContainerHeight = modalContainer.height();
            // var modalContainerWidth = modalContainer.width();

            // console.log(winHeight);
            // console.log(winWidth);
            // console.log(pageviewHeight);
            // console.log(pageviewWidth);
            // console.log(scrollTop);
            // console.log(scrollLeft);

            // popup at center of the screen
            pageview.css("top", ( jQuery(window).height() - pageview.height() ) / 2 + "px");
            // should not specify the width in number, otherwise break the RWD
            //pageview.css("left", ( jQuery(window).width() - pageview.width() ) / 2 + "px");

    		pageview.show();
    	}

    	$scope.OpenPageView = function(){
    		PopupModal();
    		$scope.PopupPageview();
    	}

    	$scope.ClosePageView = function(){
            $("body").css("overflow", "scroll");

            var pageviewModal = $element.find(".pageview-modal");
            pageviewModal.hide();

            var pageview = $element.find("pageview");
            pageview.hide();
    	}
        
        // function call from pageview, set editbox ngModel by selected record from pageview
        $scope.SetEditboxNgModel = function(selectedRecord){
            $ctrl.ngModel = selectedRecord;
        }
        $scope.ClearEditboxNgModel = function(editboxNgModel){
            $ctrl.ngModel = {};
        }

        //process flow
        $scope.Initialize();
        if(typeof $scope.EventListener == "function"){
            $scope.EventListener($scope, $element, $attrs, $ctrl);
        }else{
            EventListener();
        }
        TryToCallInitDirective();
    }
    function templateFunction(tElement, tAttrs) {
    	var programId = "";
    	if(typeof(tAttrs.programId) != "undefined"){
    		if(tAttrs.programId != ""){
    			programId = tAttrs.programId;
    		}
    	}

    	var template = ''+
    		'<div class="custom-transclude"></div>';

    	return template;
    }

	return {
		require: ['?range', 'ngModel'],
		restrict: 'EA', //'EA', //Default in 1.3+
		transclude: true,

		// scope: [false | true | {...}]
		// false = use parent scope
		// true =  A new child scope that prototypically inherits from its parent
		// {} = create a isolate scope
		scope: true,

		controller: EditboxConstructor,
		controllerAs: 'editboxCtrl',

		//If both bindToController and scope are defined and have object hashes, bindToController overrides scope.
		 bindToController: {
		 	ngModel: '=',
		 },
		template: templateFunction,
		compile: function compile(tElement, tAttrs, transclude) {
		    return {
		        pre: function preLink(scope, iElement, iAttrs, controller) {
                    transclude(scope, function(clone, scope) {
                        var programId = scope.programId;
                        var screenId = scope.screenId;
                        var pageviewTemplate = ''+
                        '<div class="modal pageview-modal">'+
                        '</div>'+
                        '<pageview class="pageview-popup-list-win" ng-model="editboxDataList" program-id="'+programId+'">'+
                            '<screen program-id="'+screenId+'"></screen>'+
                        '</pageview>';

                        var linkFn = $compile(pageviewTemplate);
                        var pageviewElement = linkFn(scope);

                        iElement.find('.custom-transclude').append(clone);
                        iElement.find('.custom-transclude').append(pageviewElement);                        
                    });
                    scope.ClosePageView();
		        },
		        post: function postLink(scope, iElement, iAttrs, controller) {
                    // hiding the <pageview> element
					angular.element(iElement).find("pageview").hide();
                    
//                    iAttrs.$observe('rangeValue', function(interpolatedValue){
//                        
//                        if(typeof (scope.$parent.SetRange) == "function"){
//                            scope.$parent.SetRange(iAttrs.range, iAttrs.rangeValue)
//                        }
//                    })
		        }
		    }
		},
	};
}]);

app.directive('export', [
    '$rootScope',
    '$timeout', 
    'Core', 
    'Security', 
    'LockManager', 
    'LoadingModal',
    'HttpRequeset', 
    'MessageService', function($rootScope, $timeout, Core, Security, LockManager, LoadingModal, HttpRequeset, MessageService) {

    function ExportConstructor($scope, $element, $attrs) {

        var constructor = this;
        var $ctrl = $scope.exportCtrl;
        var tagName = $element[0].tagName.toLowerCase();

        var globalCriteria = $rootScope.globalCriteria;
        
        $scope.DisplayMessageList = MessageService.messgeList;

        $ctrl.ExportFileTypeAs = {
            availableOptions: [
                {id: '1', value: 'xlsx', name: 'xlsx'},
                {id: '2', value: 'xls', name: 'xls'},
                {id: '3', value: 'pdf', name: 'pdf'}
            ],
            selectedOption: {id: '1', value: 'xlsx', name: 'xlsx'} //This sets the default value of the select in the ui
        }

        function TryToCallInitDirective(){
            if(typeof $scope.InitDirective == "function"){
                $scope.InitDirective($scope, $element, $attrs, $ctrl);
            }else{
                $scope.DefaultInitDirective();
            }
        }
        $scope.DefaultInitDirective = function(){
            console.log("scope.$id:"+$scope.$id+", may implement $scope.InitDirective() function in webapge");
        }
        function InitializeExportDirective() {
            $scope.tableStructure = {};
            //$ctrl.ngModel = {};

            // check attribute programId
            var isProgramIdFound = false;
            if(typeof($attrs.programId) != undefined){
                if($attrs.programId != null && $attrs.programId !=""){
                    isProgramIdFound = true;
                }
            }
            if(isProgramIdFound){
                $scope.programId = $attrs.programId;
            }
            else
                alert("<export> Must declare a attribute of program-id");
        }

        function ExportData(recordObj){
            var url = $rootScope.serverHost;
            var clientID = Security.GetSessionID();
            var programId = $scope.programId.toLowerCase();

            var tbStructure = $scope.tableStructure;
            var itemsColumn = tbStructure.DataColumns;
            
            var exportFileTypeAs = $ctrl.ExportFileTypeAs.selectedOption.value;

            var exportObj = {
                "Header":{},
                "Items":{}
            }
            
            var submitData = {
                "Session": clientID,
                "Table": programId,
                "Data": exportObj,
                "ExportFileTypeAs": exportFileTypeAs
            };
            submitData.Action = "ExportData";

            var requestOption = {
                // url: url+'/model/ConnectionManager.php', // Optional, default to /model/ConnectionManager.php
                method: 'POST',
                data: JSON.stringify(submitData)
            };

            var request = HttpRequeset.send(requestOption);
            request.then(function(responseObj) {
                var data_or_JqXHR = responseObj.data;
                var msg = data_or_JqXHR.Message;

                var actionResult = data_or_JqXHR.ActionResult;
                SubmitDataSuccessResult(data_or_JqXHR);

                MessageService.addMsg(msg);
            }, function(reason) {
              console.error("Fail in ExportData() - "+tagName + ":"+$scope.programId)
              Security.HttpPromiseFail(reason);
            }).finally(function(resultObj, resultObj1, resultObj2, resultObj3) {
                // Always execute this on both error and success
                $scope.UnLockAllControls();
                $scope.HideLoadModal();

                // SubmitDataResult(data_or_JqXHR, textStatus, jqXHR_or_errorThrown);
                // if(typeof $scope.CustomSubmitDataResult == "function"){
                //     $scope.CustomSubmitDataResult(data_or_JqXHR, 
                //         textStatus, 
                //         jqXHR_or_errorThrown, 
                //         $scope, 
                //         $element, 
                //         $attrs, 
                //         $ctrl);
                // }
            });
            return request;
        }

        function SendPostRequest(path, params, method) {
            method = method || "post"; // Set method to post by default if not specified.

            // The rest of this code assumes you are not using a library.
            // It can be made less wordy if you use one.
            var form = document.createElement("form");
            form.setAttribute("method", method);
            form.setAttribute("action", path);

            for(var key in params) {
                if(params.hasOwnProperty(key)) {
                    var hiddenField = document.createElement("input");
                    hiddenField.setAttribute("type", "hidden");
                    hiddenField.setAttribute("name", key);
                    hiddenField.setAttribute("value", params[key]);

                    form.appendChild(hiddenField);
                 }
            }

            document.body.appendChild(form);
            form.submit();
        }

        $scope.Initialize = function(){
            $scope.InitScope();
            if(typeof $scope.EventListener == "function"){
                $scope.EventListener($scope, $element, $attrs, $ctrl);
            }else{
                EventListener();
            }
            TryToCallInitDirective();
        }
        $scope.InitScope = function(){
            InitializeExportDirective();
        }

        $scope.SubmitData = function(){
            // console.log("<"+$element[0].tagName+"> submitting data")
            var globalCriteria = $rootScope.globalCriteria;

            $scope.LockAllControls();
            $scope.ShowLoadModal();
            
            if(typeof $scope.ExportData == "function"){
                $scope.ExportData($ctrl.ngModel, $scope, $element, $attrs, $ctrl);
            }else{
                ExportData($ctrl.ngModel);
            }
        }
        $scope.LockAllControls = function(){
            LockAllControls();
        }
        $scope.LockAllInputBox = function(){
            LockAllInputBox();
        }
        $scope.UnLockSubmitButton = function(){
            UnLockSubmitButton();
        }
        $scope.UnLockAllControls = function(){
            $timeout(function(){
                UnLockAllControls();
                }, 2000); // (milliseconds),  1s = 1000ms
        }
        
        $scope.ShowLoadModal = function(){
            LoadingModal.showModal();
        }
        $scope.HideLoadModal = function(){
            LoadingModal.hideModal();
        }

        function LockAllControls(){
            LockManager.LockAllControls($element, tagName);
        }
        function UnLockAllControls(){
            LockManager.UnLockAllControls($element, tagName);
        }
        function LockAllInputBox(){
            LockManager.LockAllInputBox($element, tagName);
        }
        function UnLockSubmitButton(){
            LockManager.UnLockSubmitButton($element, tagName);
        }

        function InitDirective(){
            console.log("scope.$id:"+$scope.$id+", may implement $scope.InitDirective() function in webapge");
        }
        function EventListener(){
            console.log("scope.$id:"+$scope.$id+", may implement $scope.EventListener() function in webapge");
        }
        function SetDefaultValue(){
            console.log("scope.$id:"+$scope.$id+", may implement $scope.SetDefaultValue() function in webapge");
        }
        function StatusChange(){
            console.log("scope.$id:"+$scope.$id+", may implement $scope.StatusChange() function in webapge");   
        }

        function SubmitDataResult(data_or_JqXHR, textStatus, jqXHR_or_errorThrown){
            if(textStatus == "success"){
                var actionResult = data_or_JqXHR.ActionResult;
                if(data_or_JqXHR.Status == "success"){
                    // console.dir(actionResult.FileAsByteArray)
                    // console.dir(actionResult.FileAsByteString)
                    // console.dir(actionResult.FileAsBase64)

                    saveByteArray(actionResult.filename, actionResult.FileAsBase64);
                }
            }
        }
        function SubmitDataSuccessResult(data_or_JqXHR){
            var actionResult = data_or_JqXHR.ActionResult;
            saveByteArray(actionResult.filename, actionResult.FileAsBase64);
        }
        function saveByteArray(fileName, b64Data) {
            // http://stackoverflow.com/questions/16245767/creating-a-blob-from-a-base64-string-in-javascript
            var byteCharacters = atob(b64Data);
            var byteNumbers = new Array(byteCharacters.length);
            for (var i = 0; i < byteCharacters.length; i++) {
                byteNumbers[i] = byteCharacters.charCodeAt(i);
            }
            var byteArray = new Uint8Array(byteNumbers);

            var blob = new Blob([byteArray], {
                // type: "application/vnd.ms-excel;charset=charset=utf-8"
                // type: "Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
            });

            saveAs(blob, fileName);
        };
        function str2ab(str) {
          var buf = new ArrayBuffer(str.length*2); // 2 bytes for each char
          var bufView = new Uint16Array(buf);
          for (var i=0, strLen=str.length; i<strLen; i++) {
            bufView[i] = str.charCodeAt(i);
          }
          return buf;
        }
        $scope.Initialize();
    }

    function templateFunction(tElement, tAttrs) {
        var globalCriteria = $rootScope.globalCriteria;

        var template = '' +
          '<div class="custom-transclude"></div>';
        return template;
    }

    return {
        require: ['ngModel'],
        restrict: 'EA', //'EA', //Default in 1.3+
        transclude: true,

        // scope: [false | true | {...}]
        // false = use parent scope
        // true =  A new child scope that prototypically inherits from its parent
        // {} = create a isolate scope
        scope: true,

        controller: ExportConstructor,
        controllerAs: 'exportCtrl',

        //If both bindToController and scope are defined and have object hashes, bindToController overrides scope.
        bindToController: {
            ngModel: '=',
        },
        template: templateFunction,
        compile: function compile(tElement, tAttrs, transclude) {
            return {
                pre: function preLink(scope, iElement, iAttrs, controller) {
                    //console.log("entry preLink() compile");
                },
                post: function postLink(scope, iElement, iAttrs, controller) {
                    //console.log("entry postLink() compile");

                    // "scope" here is the directive's isolate scope 
                    // iElement.find('.custom-transclude').append(
                    // );
                    transclude(scope, function (clone, scope) {
                        iElement.find('.custom-transclude').append(clone);
                    })
                }
            }
        },
    };
}]);

app.directive('import', [
    '$rootScope',
    '$timeout', 
    'Core', 
    'Security', 
    'LockManager', 
    'LoadingModal',
    'HttpRequeset', 
    'MessageService', function($rootScope, $timeout, Core, Security, LockManager, LoadingModal, HttpRequeset, MessageService) {
    function ImportConstructor($scope, $element, $attrs) {
        var constructor = this;
        var $ctrl = $scope.importCtrl;
        var tagName = $element[0].tagName.toLowerCase();

        var globalCriteria = $rootScope.globalCriteria;
        
        $scope.DisplayMessageList = MessageService.getMsg();

        function TryToCallInitDirective(){
            if(typeof $scope.InitDirective == "function"){
                $scope.InitDirective($scope, $element, $attrs, $ctrl);
            }else{
                $scope.DefaultInitDirective();
            }
        }
        $scope.DefaultInitDirective = function(){
            console.log("scope.$id:"+$scope.$id+", may implement $scope.InitDirective() function in webapge");
        }
        function InitializeImportDirective() {
            $scope.tableStructure = {};
            $scope.importResult = {};

            // check attribute EditMode
            //$scope.editMode = FindEditModeEnum($attrs.editMode);

            // check attribute programId
            var isProgramIdFound = false;
            if(typeof($attrs.programId) != undefined){
                if($attrs.programId != null && $attrs.programId !=""){
                    isProgramIdFound = true;
                }
            }
            if(isProgramIdFound){
                $scope.programId = $attrs.programId;
            }
            else
                alert("<importExport> Must declare a attribute of program-id");
        }

        function ClearChildEditBox(){

        }

        function ImportData(uploadFileInfo){
            var url = $rootScope.serverHost;
            var clientID = Security.GetSessionID();
            var programId = $scope.programId.toLowerCase();
            
            var importExportObj = {
                "Header":{},
                "Items":{}
            }
            
            // console.dir(uploadFileInfo);
            // check File Object
            if(uploadFileInfo == null || uploadFileInfo.length <1){
                $scope.UnLockAllControls();
                return;
            }
            for (var i = 0; i<uploadFileInfo.length ; i++) {
                var resultObject = uploadFileInfo[i];
                if(resultObject.error != 0){
                    $scope.UnLockAllControls();
                    return;
                }
            }

            //MessageService.clear();

            var submitData = {
                "Session": clientID,
                "Table": programId,
                "FileUploadedResult": uploadFileInfo,
            };
            submitData.Action = "ImportData";

            var requestOption = {
                // url: url+'/model/ConnectionManager.php', // Optional, default to /model/ConnectionManager.php
                method: 'POST',
                data: JSON.stringify(submitData)
            };

            var request = HttpRequeset.send(requestOption);
            request.then(function(responseObj) {
                var data_or_JqXHR = responseObj.data;

                MessageService.setMsg(data_or_JqXHR.ActionResult.processed_message);
                
            }, function(reason) {
              console.error("Fail in ImportData() - "+tagName + ":"+$scope.programId)
              Security.HttpPromiseFail(reason);
            }).finally(function() {
                // Always execute this on both error and success
                $scope.UnLockAllControls();
                $scope.HideLoadModal();

                SubmitDataResult(data_or_JqXHR, textStatus, jqXHR_or_errorThrown);
                if(typeof $scope.CustomSubmitDataResult == "function"){
                    $scope.CustomSubmitDataResult(data_or_JqXHR, 
                        textStatus, 
                        jqXHR_or_errorThrown, 
                        $scope, 
                        $element, 
                        $attrs, 
                        $ctrl);
                }
            });
            return request;
        }

        $scope.Initialize = function(){
            $scope.InitScope();
            if(typeof $scope.EventListener == "function"){
                $scope.EventListener($scope, $element, $attrs, $ctrl);
            }else{
                EventListener();
            }
            TryToCallInitDirective();
        }
        $scope.InitScope = function(){
            InitializeImportDirective();
        }

        $scope.SubmitData = function(uploadFileInfo){
            // console.log("<"+$element[0].tagName+"> submitting data")
            // var editMode = $scope.editMode;
            // var globalCriteria = $rootScope.globalCriteria;

            $scope.LockAllControls();
            $scope.ShowLoadModal();
            
            ImportData(uploadFileInfo);

        }
        $scope.LockAllControls = function(){
            LockAllControls();
        }
        $scope.LockAllInputBox = function(){
            LockAllInputBox();
        }
        $scope.UnLockSubmitButton = function(){
            UnLockSubmitButton();
        }
        $scope.UnLockAllControls = function(){
            $timeout(function(){
                UnLockAllControls();
                }, 2000); // (milliseconds),  1s = 1000ms
        }
        
        $scope.ShowLoadModal = function(){
            LoadingModal.showModal();
        }
        $scope.HideLoadModal = function(){
            LoadingModal.hideModal();
        }

        function LockAllControls(){
            LockManager.LockAllControls($element, tagName);
        }
        function UnLockAllControls(){
            LockManager.UnLockAllControls($element, tagName);
        }
        function LockAllInputBox(){
            LockManager.LockAllInputBox($element, tagName);
        }
        function UnLockSubmitButton(){
            LockManager.UnLockSubmitButton($element, tagName);
        }

        function InitDirective(){
            console.log("scope.$id:"+$scope.$id+", may implement $scope.InitDirective() function in webapge");
        }
        function EventListener(){
            console.log("scope.$id:"+$scope.$id+", may implement $scope.EventListener() function in webapge");
        }
        function SetDefaultValue(){
            console.log("scope.$id:"+$scope.$id+", may implement $scope.SetDefaultValue() function in webapge");
        }
        function StatusChange(){
            console.log("scope.$id:"+$scope.$id+", may implement $scope.StatusChange() function in webapge");   
        }

        function SubmitDataResult(data_or_JqXHR, textStatus, jqXHR_or_errorThrown){
        }
        $scope.Initialize();
    }

    function templateFunction(tElement, tAttrs) {
        var globalCriteria = $rootScope.globalCriteria;

        var template = '' +
          '<div class="custom-transclude"></div>';
        return template;
    }

    return {
        require: ['ngModel'],
        restrict: 'EA', //'EA', //Default in 1.3+
        transclude: true,

        // scope: [false | true | {...}]
        // false = use parent scope
        // true =  A new child scope that prototypically inherits from its parent
        // {} = create a isolate scope
        scope: true,

        controller: ImportConstructor,
        controllerAs: 'importCtrl',

        //If both bindToController and scope are defined and have object hashes, bindToController overrides scope.
        bindToController: {
            ngModel: '=',
        },
        template: templateFunction,
        compile: function compile(tElement, tAttrs, transclude) {
            return {
                pre: function preLink(scope, iElement, iAttrs, controller) {
                    //console.log("entry preLink() compile");
                },
                post: function postLink(scope, iElement, iAttrs, controller) {
                    //console.log("entry postLink() compile");

                    // "scope" here is the directive's isolate scope 
                    // iElement.find('.custom-transclude').append(
                    // );
                    transclude(scope, function (clone, scope) {
                        iElement.find('.custom-transclude').append(clone);
                    })
                }
            }
        },
    };
}]);

app.directive('upload', [
    '$rootScope',
    '$timeout', 
    'Core', 
    'Security', 
    'LockManager', 
    'Upload',
    'MessageService', function($rootScope, $timeout, Core, Security, LockManager, Upload, MessageService) {
    function UploadConstructor($scope, $element, $attrs) {
        var constructor = this;
        var $ctrl = $scope.uploadCtrl;
        var tagName = $element[0].tagName.toLowerCase();
        $scope.DisplayMessageList = MessageService.messageList;
        
        function TryToCallInitDirective(){
            if(typeof $scope.InitDirective == "function"){
                $scope.InitDirective($scope, $element, $attrs, $ctrl);
            }else{
                $scope.DefaultInitDirective();
            }
        }
        $scope.DefaultInitDirective = function(){
            console.log("scope.$id:"+$scope.$id+", may implement $scope.InitDirective() function in webapge");
        }
        function InitializeUpload() {
            $scope.uploadInfo = [];
            $scope.uploadResult = [];
        }

        function UploadFileList(files){
            var isFiles = Array.isArray(files);

            $scope.uploadInfo = [];

            if(!isFiles){
                UploadFile(files);
            }
            else{
                for(var index in files){
                    UploadFile(files[index]);
                }
            }
        }

        function UploadFile(file, options, callback) {
            var url = $rootScope.serverHost;
            var uploadInfoRecord = {};
            var recordCount = $scope.uploadInfo.length;
            // create new row in uploadInfo, since upload in async
            $scope.uploadInfo[recordCount] = {};

            if (!file || file.$error) {
                return;
            }

            uploadInfoRecord.fileInfo = file;
            uploadInfoRecord.uploadResult = {};

            uploadInfoRecord.name = file.name;
            uploadInfoRecord.size = file.size;
            uploadInfoRecord.uploadProgress = 0;

            // File Object
            // console.dir(file)
            /*
                lastModified: 1474968722283
                lastModifiedDate: Tue Sep 27 2016 17:32:02 GMT+0800 (China Standard Time)
                name: "hu01ca.xlsx"
                size: 8629
                type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                upload: d
                webkitRelativePath: ""
            */

            // Upload Result from PHP
            /*
            {
              "name": "hu01ca.xlsx",
              "type": "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
              "tmp_name": "D:\\xampp\\tmp\\phpDFD9.tmp",
              "error": 0,
              "size": 8629,
              "movedTo": "D:\\xampp\\htdocs\\Develop\\model/../temp/upload/hu01ca.xlsx",
              "fileIntegrity-md5": "3e7992dbabfbc9ea84c621762831975b",
              "fileIntegrity-sha1": "691528b6437c8e686d342eeacd0f27620a6ba295",
              "errorMsg": ""
            }
            */

                var uploadAction = Upload.upload({
                  //url: 'https://angular-file-upload-cors-srv.appspot.com/upload',
                  url: url+'/controller/documentUploader.for12.2.21.php',
                  data: {file: file},
                });
                // http://api.jquery.com/deferred.then/#deferred-then-doneCallbacks-failCallbacks
                // deferred.then( doneCallbacks, failCallbacks [, progressCallbacks ] )
                uploadAction.then(function (response) {
                    uploadInfoRecord.uploadResult = response.data;
                    //if(response.data.error)
                    //$scope.errorMsg = response.data.error + " - "+response.data.errorMsg

                    $scope.uploadInfo[recordCount] = uploadInfoRecord;
                    // $ctrl.ngModel = $scope.uploadInfo;

                    $scope.uploadResult[$scope.uploadResult.length] = response.data;
                    $ctrl.ngModel = $scope.uploadResult;
                }, function (response) {
                    //if(response.data.error)
                    //$scope.errorMsg = response.data.error + " - "+response.data.errorMsg
                }, function (evt) {
                  // Math.min is to fix IE which reports 200% sometimes
                  var uploadedPercentage = Math.min(100, parseInt(100.0 * evt.loaded / evt.total));
                  uploadInfoRecord.uploadProgress = uploadedPercentage;
                });

                return uploadAction;


            // if(typeof callback == "function")
            //     callback($scope.uploadInfo[recordCount]);
        }

        $scope.UploadData = function(files){
            // console.dir(files)
            UploadFileList(files);
        }

        $scope.Initialize = function(){
            $scope.InitScope();
            if(typeof $scope.EventListener == "function"){
                $scope.EventListener($scope, $element, $attrs, $ctrl);
            }else{
                EventListener();
            }
            TryToCallInitDirective();
        }
        $scope.InitScope = function(){
            InitializeUpload();
        }

        function InitDirective(){
            console.log("scope.$id:"+$scope.$id+", may implement $scope.InitDirective() function in webapge");
        }
        function EventListener(){
            console.log("scope.$id:"+$scope.$id+", may implement $scope.EventListener() function in webapge");
        }
        // function SetDefaultValue(){
        //     console.log("scope.$id:"+$scope.$id+", may implement $scope.SetDefaultValue() function in webapge");
        // }
        function StatusChange(){
            console.log("scope.$id:"+$scope.$id+", may implement $scope.StatusChange() function in webapge");   
        }

        $scope.Initialize();
    }
    function templateFunction(tElement, tAttrs) {
        var globalCriteria = $rootScope.globalCriteria;

        var template = '' +
          '<div class="custom-transclude"></div>';
        return template;
    }

    return {
        require: ['ngModel', '?import'],
        restrict: 'EA', //'EA', //Default in 1.3+
        transclude: true,

        // scope: [false | true | {...}]
        // false = use parent scope
        // true =  A new child scope that prototypically inherits from its parent
        // {} = create a isolate scope
        scope: true,

        controller: UploadConstructor,
        controllerAs: 'uploadCtrl',

        //If both bindToController and scope are defined and have object hashes, bindToController overrides scope.
        bindToController: {
            ngModel: '=',
        },
        template: templateFunction,
        compile: function compile(tElement, tAttrs, transclude) {
            return {
                pre: function preLink(scope, iElement, iAttrs, controller) {
                    //console.log("entry preLink() compile");
                },
                post: function postLink(scope, iElement, iAttrs, controller) {
                    //console.log("entry postLink() compile");

                    // "scope" here is the directive's isolate scope 
                    // iElement.find('.custom-transclude').append(
                    // );
                    transclude(scope, function (clone, scope) {
                        iElement.find('.custom-transclude').append(clone);
                    })
                }
            }
        },
    };
}]);

app.directive('message', ['$rootScope', 
    '$timeout',
    'Security', 
    'MessageService', function($rootScope, $timeout, Security, MessageService) {
    function MessageConstructor($scope, $element, $attrs) {
        var constructor = this;
        var $ctrl = $scope.msgCtrl;
        var tagName = $element[0].tagName.toLowerCase();
        
        $scope.autoClose = false;
        var DirectiveProperties = (function () {
            var autoClose;
            
            function findAutoClose(){
                var object = $attrs.autoClose;
                if(typeof(object) != "undefined")
                    return true;
                else
                    return false;
            }

            return {
                getAutoClose: function () {
                    if (!autoClose) {
                        autoClose = findAutoClose();
                    }
                    $scope.autoClose = autoClose;
                    return autoClose;
                }
            };
        })();
        
        function TryToCallInitDirective(){
            if(typeof $scope.InitDirective == "function"){
                $scope.InitDirective($scope, $element, $attrs, $ctrl);
            }else{
                $scope.DefaultInitDirective();
            }
        }
        $scope.DefaultInitDirective = function(){
            console.log("scope.$id:"+$scope.$id+", may implement $scope.InitDirective() function in webapge");
        }
        function InitializeMessage() {
            DirectiveProperties.getAutoClose();
            
            if(!$ctrl.ngModel){
                $ctrl.ngModel = [];
            }
            MessageService.setMsg($ctrl.ngModel);
            $ctrl.ngModel = MessageService.getMsg();
        }
        $scope.Initialize = function(){
            $scope.InitScope();
            if(typeof $scope.EventListener == "function"){
                $scope.EventListener($scope, $element, $attrs, $ctrl);
            }else{
                EventListener();
            }
            TryToCallInitDirective();
        }
        $scope.InitScope = function(){
            InitializeMessage();
        }

        function InitDirective(){
            console.log("scope.$id:"+$scope.$id+", may implement $scope.InitDirective() function in webapge");
        }
        function EventListener(){
            console.log("scope.$id:"+$scope.$id+", may implement $scope.EventListener() function in webapge");
        }
        $scope.Initialize();
        
        $scope.$watchCollection(
          // This function returns the value being watched. It is called for each turn of the $digest loop
          function() { return $ctrl.ngModel },
//          function() { return MessageService.messageList },
            
          // This is the change listener, called when the value returned from the above function changes
          function(newValue, oldValue) {
//              console.dir(newValue)
//              console.dir(oldValue)

              var newValueLength = newValue.length;
              var oldValueLength = oldValue.length;
              
            if ( newValueLength !== oldValueLength ) {
//                if(newValueLength > oldValueLength){
                    if($scope.autoClose)
                        $timeout(function(){
                            MessageService.shiftMsg();
                        }, 7000); // (milliseconds),  1s = 1000ms
//                }
            }
          }
        );
    }
    function templateFunction(tElement, tAttrs) {
        var globalCriteria = $rootScope.globalCriteria;

        var template = '' +
          '<div class="custom-transclude"></div>';
        return template;
    }

    return {
        require: ['ngModel'],
        restrict: 'E', //'EA', //Default in 1.3+
        transclude: true,
        scope: true,

        controller: MessageConstructor,
        controllerAs: 'msgCtrl',

        //If both bindToController and scope are defined and have object hashes, bindToController overrides scope.
        bindToController: {
            ngModel: '=',
        },
        template: templateFunction,
        compile: function compile(tElement, tAttrs, transclude) {
            return {
                pre: function preLink(scope, iElement, iAttrs, controller) {
                    //console.log("entry preLink() compile");
                },
                post: function postLink(scope, iElement, iAttrs, controller) {
                    //console.log("entry postLink() compile");
                    
                    transclude(scope, function (clone, scope) {
                        iElement.find('.custom-transclude').append(clone);
                    })
                }
            }
        },
    };
}]);

app.directive('range', ['$rootScope', 
    '$timeout',
    'Security', 
    'MessageService', function($rootScope, $timeout, Security, MessageService) {
    function RangeConstructor($scope, $element, $attrs) {
        var constructor = this;
        var $ctrl = $scope.rangeCtrl;
        var tagName = $element[0].tagName.toLowerCase();
        
        var DirectiveProperties = (function () {
            var multi;
            
            function findMultiable(){
                var object = $attrs.multi;
                if(typeof(object) != "undefined")
                    return true;
                else
                    return false;
            }

            return {
                getMultiable: function () {
                    if (!multi) {
                        multi = findMultiable();
                    }
                    $scope.multi = multi;
                    return multi;
                }
            };
        })();
        
        function TryToCallInitDirective(){
            if(typeof $scope.InitDirective == "function"){
                $scope.InitDirective($scope, $element, $attrs, $ctrl);
            }else{
                $scope.DefaultInitDirective();
            }
        }
        $scope.DefaultInitDirective = function(){
            console.log("scope.$id:"+$scope.$id+", may implement $scope.InitDirective() function in webapge");
        }
        
        $scope.IsRange = function()
        {
            return true;
        }
        
        $scope.GetInitRange = function(){
            var range = {start: "ALL", end: ""};
            
            return range;
        }
        
        function InitializeRange() {
            DirectiveProperties.getMultiable();
            
            $ctrl.ngModel = $scope.GetInitRange();
            $ctrl.ngModel.isAll = true;
        }
        $scope.Initialize = function(){
            $scope.InitScope();
            if(typeof $scope.EventListener == "function"){
                $scope.EventListener($scope, $element, $attrs, $ctrl);
            }else{
                EventListener();
            }
            TryToCallInitDirective();
        }
        $scope.InitScope = function(){
            InitializeRange();
        }

        function InitDirective(){
            console.log("scope.$id:"+$scope.$id+", may implement $scope.InitDirective() function in webapge");
        }
        function EventListener(){
            console.log("scope.$id:"+$scope.$id+", may implement $scope.EventListener() function in webapge");
        }
        
        // chagneRagne = [start | end]
        function RangeStringChange(changeRange, ctrlModel){
            var isLocaleCompareSupport = localeCompareSupportsLocales();
            
//            var rangeObject = $scope.GetRange();
            var rangeObject = ctrlModel;
            
            var strStart = rangeObject.start;
            var strEnd = rangeObject.end;
            var stringDifference = 0;
            
            if(rangeObject.start == "ALL"){
                $ctrl.ngModel.isAll = true;
                rangeObject.end = "";
                return;
            }else{
                $ctrl.ngModel.isAll = false;
            }
            
            // string comparison, find the strStart position of strEnd
            if(isLocaleCompareSupport){
                 stringDifference = strStart.localeCompare(strEnd);
            }else{
                if(strStart < strEnd)
                    stringDifference = -1;
                if(strEnd < strStart)
                    stringDifference = 1;
                if(strStart == strEnd)
                    stringDifference = 0;
            }
            
            if(changeRange == "start"){
                
                if(stringDifference > 0)
                {
                    strEnd = strStart
                }
                if(stringDifference < 0)
                {

                }
            }else if(changeRange == "end"){
                if(stringDifference > 0)
                {
                    strStart = strEnd
                }
                if(stringDifference < 0)
                {
                    if(strStart == "")
                        strStart = strEnd
                }
            }
            
//            if(strStart != "ALL"){
//                $ctrl.ngModel.isAll = false;
//            }
                        
            rangeObject.start = strStart;
            rangeObject.end = strEnd;
            
            ctrlModel.start = strStart;
            ctrlModel.end = strEnd;
        }
        
        function localeCompareSupportsLocales() {
          try {
            'foo'.localeCompare('bar', 'i');
          } catch (e) {
            return e.name === 'RangeError';
          }
          return false;
        }
        $scope.Initialize();
        
        $scope.SetRange = function(rangeType, value){
            
            if(rangeType == "start"){
                $ctrl.ngModel.start = value;
            }else if(rangeType == "end"){
                $ctrl.ngModel.end = value;
            }
            
//            if($ctrl.ngModel.start == null || $ctrl.ngModel.start == null)
//                $ctrl.ngModel.start = "ALL";
            
            RangeStringChange(rangeType, $ctrl.ngModel)
        }
        
        $scope.GetRange = function(){
            var rangeList = [];
            var range = rangeList[0] = {start: "", end: ""};
            
            range.start = $ctrl.ngModel.start;
            range.end = $ctrl.ngModel.end;
            
            return range;
        }
        $scope.IsLockEndControl = function(){
            var isLock = false;
            var range = $scope.GetRange();
            if(range.start == "ALL"){
                isLock = true;
            }
            return isLock;
        }
        
        $scope.CheckAllRange = function(test){
            console.dir("CheckAllRange() function")
//            if(!$ctrl.ngModel.isAll){
//                $ctrl.ngModel.start = "";
//            }
        }
        
//        $scope.$watchCollection(
//
//          // This function returns the value being watched. It is called for each turn of the $digest loop
//          function() { return $ctrl.ngModel },
//            
//          // This is the change listener, called when the value returned from the above function changes
//          function(newValue, oldValue) {
//              
//          }
//        );
        
//        $scope.$watch(
//            function () {return $ctrl.ngModel.isAll},
//            function(newValue, oldValue){
//                if(newValue){
//                    $ctrl.ngModel.start = "ALL";
//                }else{
//                    $ctrl.ngModel.start = "";
//                }
//            }
//        );
    }
    function templateFunction(tElement, tAttrs) {
        var globalCriteria = $rootScope.globalCriteria;

        var template = '' +
          '<div class="custom-transclude"></div>';
        return template;
    }

    return {
        require: ['ngModel'],
        restrict: 'E', //'EA', //Default in 1.3+
        transclude: true,
        scope: true,

        controller: RangeConstructor,
        controllerAs: 'rangeCtrl',

        //If both bindToController and scope are defined and have object hashes, bindToController overrides scope.
        bindToController: {
            ngModel: '=',
        },
        template: templateFunction,
        compile: function compile(tElement, tAttrs, transclude) {
            return {
                pre: function preLink(scope, iElement, iAttrs, controller) {
                    //console.log("range preLink() compile");
                },
                post: function postLink(scope, iElement, iAttrs, controller) {
                    //console.log("range postLink() compile");
                    
                    transclude(scope, function (clone, scope) {
                        iElement.find('.custom-transclude').append(clone);
                    })
                }
            }
        },
    };
}]);


/**
 * <process> submit a set of criteria to perform action, for reporting, complex inquiry, processing
 * <process
    ng-model=""
    program-id=""
    edit-mode=""
    >
 * @param {Object} ng-model - store the process criteria
 * @param {String} program-id - assign the program id to implement the behavior of CRUD
 */
app.directive('process', ['$rootScope', 
    '$q',
    '$timeout', 
    'Core', 
    'Security', 
    'LockManager', 
    'LoadingModal',
    'HttpRequeset', 
    'MessageService', function($rootScope, $q, $timeout, Core, Security, LockManager, LoadingModal, HttpRequeset, MessageService) {
    function ProcessConstructor($scope, $element, $attrs) {
    	var constructor = this;
    	var $ctrl = $scope.processCtrl;
        var tagName = $element[0].tagName.toLowerCase();

    	var globalCriteria = $rootScope.globalCriteria;
        var backupNgModelObj = {};
        
        var DirectiveProperties = (function () {
            var editMode;
            var programID;

//            function findEditMode() {
//                var object = $scope.editMode = FindEditModeEnum($attrs.editMode);
//                return object;
//            }
            function findProgramID(){
                var object = $attrs.programId;
                return object;
            }

            return {
//                getEditMode: function () {
//                    if (!editMode) {
//                        editMode = findEditMode();
//                    }
//                    return editMode;
//                },
                getProgramID: function(){
                    var isProgramIdFound = false;
                    if(!programID){
                        programID = findProgramID;
                    }
                    if(typeof(programID) != undefined){
                        if(programID != null && programID !=""){
                            isProgramIdFound = true;
                        }
                    }
                    
                    if(isProgramIdFound){
                        $scope.programId = $attrs.programId;
                    }
                    else
                        alert("<process> Must declare a attribute of program-id");
                }
            };
        })();

        function InitializeProcess() {
        	$scope.tableStructure = {};
//            DirectiveProperties.getEditMode();
            DirectiveProperties.getProgramID();
            
            $scope.DisplayMessageList = MessageService.getMsg();
            $ctrl.ngModel = {};
        }
		
        function TryToCallInitDirective(){
            if(typeof $scope.InitDirective == "function"){
                $scope.InitDirective($scope, $element, $attrs, $ctrl);
            }else{
                $scope.DefaultInitDirective();
            }
        }
        $scope.Initialize = function(){
            $scope.InitScope();
            if(typeof $scope.EventListener == "function"){
                $scope.EventListener($scope, $element, $attrs, $ctrl);
            }else{
                EventListener();
            }
            TryToCallInitDirective();
        }
        $scope.InitScope = function(){
            InitializeProcess();
        }
        $scope.DefaultInitDirective = function(){
            
        }
		
        $scope.SubmitData = function(){
        	console.log("<"+$element[0].tagName+"> submitting data")
            var globalCriteria = $rootScope.globalCriteria;

        	$scope.LockAllControls();
            
            if(!ValidateSubmitData()){
                return;
            }
            $scope.ShowLoadModal();
            SubmitData();
        }
        function ValidateSubmitData(){
            var isValid = true;
        	// if Buffer invalid, cannot send request
        	var isBufferValid = true;
			if(typeof $scope.ValidateBuffer == "function"){
				isBufferValid = $scope.ValidateBuffer($scope, $element, $attrs, $ctrl);
			}else{
				isBufferValid = ValidateBuffer();
			}
            isValid = isValid && isBufferValid;
			if(!isBufferValid){
                $scope.UnLockAllControls();
            }
            
            return isValid;
        }
        function SubmitData(){
            var httpResponseObj = {};
            var submitPromise;
            var msg = "";
            
				if(typeof $scope.ProcessData == "function"){
	            	submitPromise = scope.ProcessData($ctrl.ngModel, $scope, $element, $attrs, $ctrl);
	            }else{
	            	submitPromise = ProcessData($ctrl.ngModel);
	            }
                
                submitPromise.then(function(responseObj) {
                    httpResponseObj = responseObj;
                    var data_or_JqXHR = responseObj.data;
                    msg = data_or_JqXHR.Message;

					MessageService.setMsg(data_or_JqXHR.ActionResult.processed_message);
                    
                }, function(reason) {
                  console.error(tagName + ":"+$scope.programId + " - Fail in ProcessData()")
                  throw reason;
                });
				
            
            submitPromise.catch(function(e){
                // handle errors in processing or in error.
                console.log("Submit data error catch in process");
                Security.HttpPromiseFail(e);
            }).finally(function() {
                // Always execute unlock on both error and success
                $scope.UnLockAllControls();
                $scope.HideLoadModal();

                if(msg.length > 0)
                    MessageService.addMsg(msg);
                SubmitDataResult(httpResponseObj, httpResponseObj.status);
                
                if(typeof $scope.CustomSubmitDataResult == "function"){
                    $scope.CustomSubmitDataResult(httpResponseObj, 
                        httpResponseObj.status, 
                        $scope, 
                        $element, 
                        $attrs, 
                        $ctrl);
                }
            }).catch(function(e){
                // handle errors in processing or in error.
                console.warn(e)
            })
            
            return submitPromise;
        }
        
        function ProcessData(recordObj){
        	var clientID = Security.GetSessionID();
        	var programId = $scope.programId.toLowerCase();
            
			var submitData = {
				"Session": clientID,
				"Table": programId,
				"Data": recordObj,
			};
            submitData.Action = "ProcessData";

            var requestOption = {
                method: 'POST',
                data: JSON.stringify(submitData)
            };

            var request = HttpRequeset.send(requestOption);
            return request;
        }
        
        $scope.LockAllControls = function(){
            LockAllControls();
        }
        $scope.LockAllInputBox = function(){
            LockAllInputBox();
        }
        $scope.UnLockSubmitButton = function(){
            UnLockSubmitButton();
        }
        $scope.UnLockAllControls = function(){
			$timeout(function(){
        		UnLockAllControls();
			  	}, 2000); // (milliseconds),  1s = 1000ms
        }
        
        $scope.ShowLoadModal = function(){
            LoadingModal.showModal();
        }
        $scope.HideLoadModal = function(){
            LoadingModal.hideModal();
        }

        // StatusChange() event listener
		$scope.$watch(
		  // This function returns the value being watched. It is called for each turn of the $digest loop
		  function() { return $ctrl.ngModel; },
		  // This is the change listener, called when the value returned from the above function changes
		  function(newValue, oldValue) {
		  	var changedField = "";
		  	var changedValue;

		    if ( newValue !== oldValue ) {
		    	for(var colIndex in $ctrl.ngModel){
	    			changedField = colIndex;
	    			changedValue = newValue[colIndex];

	    			if(oldValue!=null){
	    				if ( Object.prototype.hasOwnProperty ) {
			    			if(oldValue.hasOwnProperty(colIndex))
			    			{
                                if(oldValue[colIndex] === newValue[colIndex]){
                                    continue;
                                }
                                if(oldValue[colIndex] == newValue[colIndex]){
                                    continue;
                                }
			    			}
		    			}
	    			}

                    // Convert to Uppercase, if the chagned field is a Key and data type is string
                    // newValue = ConvertKeyFieldToUppercase(newValue, false);

					if(typeof $scope.StatusChange == "function"){
						$scope.StatusChange(colIndex, changedValue, newValue, $scope, $element, $attrs, $ctrl);
					}else{
						StatusChange();
					}
		    	}
		    }
		  },
		  true
		);

        function LockAllControls(){
            LockManager.LockAllControls($element, tagName);
        }
        function UnLockAllControls(){
            LockManager.UnLockAllControls($element, tagName);
        }
        function LockAllInputBox(){
            LockManager.LockAllInputBox($element, tagName);
        }
        function UnLockSubmitButton(){
            LockManager.UnLockSubmitButton($element, tagName);
        }

        function TryToCallSetDefaultValue(){
            if(typeof $scope.SetDefaultValue == "function"){
                $scope.SetDefaultValue($scope, $element, $attrs, $ctrl);
            }else{
                SetDefaultValue();
            }
        }

        function InitDirective(){
            console.log("scope.$id:"+$scope.$id+", may implement $scope.InitDirective() function in webapge");
        }
		function EventListener(){
			console.log("scope.$id:"+$scope.$id+", may implement $scope.EventListener() function in webapge");
		}
		function SetDefaultValue(){
			console.log("scope.$id:"+$scope.$id+", may implement $scope.SetDefaultValue() function in webapge");
		}
		function StatusChange(){
			console.log("scope.$id:"+$scope.$id+", may implement $scope.StatusChange() function in webapge");	
		}
		function ValidateBuffer(){
			console.log("scope.$id:"+$scope.$id+", may implement $scope.ValidateBuffer() function in webapge");	
			return true;
		}
        
        function CustomGetDataResult(data_or_JqXHR, textStatus, jqXHR_or_errorThrown){
            var progID = $scope.programId;
            //console.log("scope.$id:"+$scope.$id+", programId:"+progID+", must implement $scope.CustomGetDataResult() function in webapge");
        }
        function SubmitDataResult(data_or_JqXHR, textStatus, jqXHR_or_errorThrown){

        }
        
        $scope.Initialize();
    }

    function templateFunction(tElement, tAttrs) {
        var globalCriteria = $rootScope.globalCriteria;

        var template = '' +
          // outside of the ng-transclude
          // '<div>'+
          // '</div>' +
          // '<div class="well well-sm">'+
          // '<p ng-repeat="dspMsg in DisplayMessageList track by $index" ng-bind="dspMsg"></p>'+
          // '</div>' +
          // inside of the ng-transclude
          //'<div ng-transclude></div>' +
          '<div class="custom-transclude"></div>';
        return template;
    }

	return {
		require: ['ngModel'],
		restrict: 'EA', //'EA', //Default in 1.3+
		transclude: true,

		// scope: [false | true | {...}]
		// false = use parent scope
		// true =  A new child scope that prototypically inherits from its parent
		// {} = create a isolate scope
		scope: true,

		controller: ProcessConstructor,
		controllerAs: 'processCtrl',

		//If both bindToController and scope are defined and have object hashes, bindToController overrides scope.
		bindToController: {
			ngModel: '=',
		},
		template: templateFunction,
		compile: function compile(tElement, tAttrs, transclude) {
		    return {
		        pre: function preLink(scope, iElement, iAttrs, controller) {
		            //console.log("process preLink() compile");
		        },
		        post: function postLink(scope, iElement, iAttrs, controller) {
		            //console.log("process postLink() compile");

                    // "scope" here is the directive's isolate scope 
                    // iElement.find('.custom-transclude').append(
                    // );
                    transclude(scope, function (clone, scope) {
                        iElement.find('.custom-transclude').append(clone);
                    })
		        }
		    }
		    // or
		    // return function postLink( ... ) { ... }
		},
	};
}]);
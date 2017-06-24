// JavaScript Document
"use strict";

app.controller('mainCtrl', ['$scope', '$rootScope', 'Security', function ($scope, $rootScope, Security) {
	$scope.directiveScopeDict = {};
	$scope.directiveCtrlDict = {};
	
	$scope.EventListener = function(scope, iElement, iAttrs, controller){
		console.log("<"+iElement[0].tagName+">" +" Directive overried EventListener()");
		var prgmID = scope.programId;
		if($scope.directiveScopeDict[prgmID] == null || typeof($scope.directiveScopeDict[prgmID]) == "undefined"){
		  $scope.directiveScopeDict[prgmID] = scope;
		  $scope.directiveCtrlDict[prgmID] = controller;
		}
		
		if(prgmID == "ei01wu")
			scope.SubmitData();
		
		//http://api.jquery.com/Types/#Event
		//The standard events in the Document Object Model are:
		// blur, focus, load, resize, scroll, unload, beforeunload,
		// click, dblclick, mousedown, mouseup, mousemove, mouseover, mouseout, mouseenter, mouseleave,
		// change, select, submit, keydown, keypress, and keyup.
		iElement.ready(function() {
			
		})
	}
	
	$scope.CustomSubmitDataResult = function(responseObj, httpStatusCode, scope, element, attrs, ctrl){
		var prgmID = scope.programId;
		
		if(prgmID == "ei01wu"){
			//console.dir(responseObj);
			if(responseObj.status == 200){
				CheckMenu(responseObj.data.ActionResult.data[0]);
			}
		}
	}
}]);

function CheckMenu(userInfo){
	var userType = userInfo.USER_AccountType;
	//console.dir(userInfo);
	//console.log(userType);
	$(".menu-admin").hide();
	
	if(userType == "admin")
		ChangeAdminMenu();
	else if(userType == "user")
		ChangeUserMenu();
}

function ChangeAdminMenu(){
	$(".menu-admin").show();
}

function ChangeUserMenu(){
}
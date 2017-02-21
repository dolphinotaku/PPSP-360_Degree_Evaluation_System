// JavaScript Document
"use strict";

app.factory('LoadingModal', function ($window, $document) {
	var loadingModal = {};
	var loadingIcon = {};
	var loadingIconContainer = {};
    var root = {};
    root.showModal = function(msg){
    	if(typeof(msg) == "undefined" || msg == null || msg == "")
    		msg = "Loading...";

    	loadingModal = $( "<div/>", {
		  "class": "modal loading-modal",
		  // click: function() {
		  //   $( this ).toggleClass( "test" );
		  // }
		}).show().appendTo("body");

		loadingIcon = $("<div/>", {
			"class": "loading-icon",
			"html": '<i class="fa fa-circle-o-notch fa-spin fa-5x fa-fw"></i>',
		});

		loadingIconContainer = $("<div/>", {
		  "class": "modal",
		  "html": loadingIcon,
		}).show();

		loadingIconContainer.appendTo("body");
		loadingIcon.css("margin-top", ( jQuery(window).height() - loadingIcon.height() ) / 2 + "px");
    };
    root.hideModal = function(){
    	loadingModal.remove();
    	loadingIconContainer.remove();
    }
    return root;
});
app.service('MessageService', function($rootScope, $timeout){
	var self = this;
	self.messageList = [];
    $rootScope.$on('$routeChangeStart', function () {
		self.messageList = [];
    });
    
	self.getMsg = function(){
		return self.messageList;
	}
	self.addMsg = function(msg){
		self.messageList.push(msg);
    }
    self.shiftMsg = function(){
        self.messageList.shift();
    }
	self.setMsg = function(msgList){
		if(typeof(msgList) == "undefined" || msgList == null)
			return;
		if(msgList.length <= 0)
			return;
        
        // clear message list
        self.messageList.length = 0;
        
        // cannot copy or assign the object directly to the messageList, it will break the assign by reference between the message directive
		for(var index in msgList){
			self.addMsg(msgList[index]);
		}
        
	}
	self.clear = function(){
        for(var index in self.messageList){
            self.messageList.shift();
        }
	}
});

//
// call HttpRequest simple
/*
Object{
	data – {string|Object} – The response body transformed with the transform functions.
	status – {number} – HTTP status code of the response.
	headers – {function([headerName])} – Header getter function.
	config – {Object} – The configuration object that was used to generate the request.
	statusText – {string} – HTTP status text of the response.
}
e.g
Object{
	data: Object
	status: 200
	headers: function()
	config: Object
	statusText: "OK"
}
*/
/*
            var requestOption = {
                // url: url+'/model/ConnectionManager.php', // Optional, default to /model/ConnectionManager.php
                method: 'POST',
                data: JSON.stringify(submitData)
            };

            var request = HttpRequeset.send(requestOption);
            request.then(function(responseObj) {
                var data_or_JqXHR = responseObj.data;
            }, function(reason) {
              console.error("Fail in GetNextPageRecords() - "+tagName + ":"+$scope.programId)
              Security.HttpPromiseFail(reason);
            }).finally(function() {
			    // Always execute this on both error and success
			});
            return request;
*/
app.service('HttpRequeset', function($rootScope, $http){
	var self = this;
	// return $q(function(resolve, reject){
	// })
	self.send = function(requestOptions){
		var url = $rootScope.serverHost;
		if(typeof(requestOptions.url) == "undefined")
			requestOptions.url = url+'/model/ConnectionManager.php';
        
        requestOptions.cache = false;

		return $http(
			requestOptions
		);
	}
});
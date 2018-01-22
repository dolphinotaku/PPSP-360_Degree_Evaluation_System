// JavaScript Document
Object.defineProperty(Object.prototype, 'IsEmptyObject', {
  value : function() {
		var obj = this;
		for (var prop in obj) {
			if (obj.hasOwnProperty(prop))
				return false;
		}
	
		return true;
	},
  enumerable : false
});
String.prototype.IsNullOrEmpty = function(){
	  	var curStr = this;
		var isNullOrEmpty = false;
		if(typeof(curStr) == "undefined")
			isNullOrEmpty = true;
		else if(curStr == null)
			isNullOrEmpty = true;
		else if(curStr == "")
			isNullOrEmpty = true;
		return isNullOrEmpty;
}
String.prototype.ReplaceAll = function(search, replacement) {
    var target = this;
    return target.replace(new RegExp(search, 'g'), replacement);
};

function getFunctionName(fun) {
  var ret = fun.toString();
  ret = ret.substr('function '.length);
  ret = ret.substr(0, ret.indexOf('('));
  return ret;
}

<html>
<head>
<meta charset="utf-8">
<title>Test login</title>
<meta name="viewport" content="width=device-width, initial-scale=1, minmum-scale=1">
<link rel="stylesheet" type="text/css" href="../third-party/bootstrap/bootstrap-3.3.6-dist/css/bootstrap.min.css">
<script type="text/javascript" src="../third-party/jquery/jquery-2.2.1.min.js"></script>
<script type="text/javascript" src="../third-party/angularjs/angular-1.5.0/angular.min.js"></script>
<script>
    var serverUrl = "../model/ConnectionManager.php";
    
function Login(){
    var formData = $("#loginForm").serializeArray();
    var jsonData = {};
    for(var index in formData){
        var name = formData[index].name;
        var value = formData[index].value;
        jsonData[name] = value;
    }
    jsonData = JSON.stringify(jsonData);
    
    // send ajax
    $.ajax({
        url: serverUrl, 
        type : "POST",
        dataType : 'json', 
        data : jsonData, 
        success : function(data, textStatus, jqXHR) {
        },
        error: function(jqXHR, textStatus, errorThrown ) {
        },
        complete: function(dataOrJqXHR, textStatus){
            console.log(dataOrJqXHR, textStatus);
            var jsonData = JSON.stringify(dataOrJqXHR, null, "\t");
            $("#ajaxResult").html(jsonData);
        }
    })
    
    return false;
}
    
function Logout(){
    var formData = $("#logoutForm").serializeArray();
    var jsonData = {};
    for(var index in formData){
        var name = formData[index].name;
        var value = formData[index].value;
        jsonData[name] = value;
    }
    jsonData = JSON.stringify(jsonData);
    
    // send ajax
    $.ajax({
        url: serverUrl, 
        type : "POST",
        dataType : 'json', 
        data : jsonData, 
        success : function(data, textStatus, jqXHR) {
        },
        error: function(jqXHR, textStatus, errorThrown ) {
        },
        complete: function(dataOrJqXHR, textStatus){
            console.log(dataOrJqXHR, textStatus);
            var jsonData = JSON.stringify(dataOrJqXHR, null, "\t");
            $("#ajaxResult").html(jsonData);
        }
    })
    
    return false;
}
    
//$(document).ready(function(){
//    // click on button submit
//    $("#submit").on('click', function(){
//    });
//});
</script>
</head>
<body>
    <div class="container">
        PHP Session<br>
        <pre><?php
                if (session_status() == PHP_SESSION_NONE) {
                    session_start();
                    print_r($_SESSION);
                }
            ?>
        </pre>
        Ajax Result<br>
        <pre id="ajaxResult">
        </pre>
    </div>
    <div class="container">
    <form class="form-horizontal" id="loginForm" onsubmit="return Login()">
        <fieldset>
        <legend>Login</legend>
          <div class="form-group">
            <label for="UserCode" class="col-sm-2 control-label">LoginID</label>
            <div class="col-sm-10">
              <input type="text" class="form-control" id="UserCode" name="UserCode" placeholder="username">
            </div>
          </div>
          <div class="form-group">
            <label for="Password" class="col-sm-2 control-label">Password</label>
            <div class="col-sm-10">
              <input type="password" class="form-control" id="Password" name="Password" placeholder="Password">
            </div>
          </div>
          <div class="form-group">
            <div class="col-sm-offset-2 col-sm-10">
              <div class="checkbox">
                <label>
                  <input type="checkbox"> Remember me
                </label>
              </div>
            </div>
          </div>
          <div class="form-group sr-only">
            <label for="action" class="col-sm-2 control-label">Action</label>
            <div class="col-sm-10">
              <input type="text" class="form-control" id="action" name="Action" value="Login">
            </div>
          </div>
          <div class="form-group">
            <div class="col-sm-offset-2 col-sm-2">
              <button type="submit" class="btn btn-default">Sign in</button>
            </div>
          </div>
        </fieldset>
    </form>
	<form class="form-horizontal" id="logoutForm" onsubmit="return Logout()">
        <fieldset>
        <legend>Login</legend>
          <div class="form-group sr-only">
            <label for="action" class="col-sm-2 control-label">Action</label>
            <div class="col-sm-10">
              <input type="text" class="form-control" id="action" name="Action" value="Logout">
            </div>
          </div>
          <div class="form-group">
            <div class="col-sm-offset-2 col-sm-2">
              <button type="submit" class="btn btn-default">Logout</button>
            </div>
          </div>
        </fieldset>
    </form>
        </div>
</body>
</html>
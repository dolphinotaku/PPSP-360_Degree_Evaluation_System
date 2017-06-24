<?php
$boolarray = Array(false => 'false', true => 'true');
$result = IsNullOrEmptyString("2014-11-18 12:05:00");
echo $boolarray[$result]."<br>";
echo GetSQLValueString("2014-11-18 12:05:00");

	function IsNullOrEmptyString($question){
		return (!isset($question) || trim($question)==='');
	}
	function GetSQLValueString($getColumn)
	{
		$columnValue = $getColumn;
		$returnValue = "NULL";
		$type = "datetime";
		
		switch (true) {
			case strstr($type, "char"):
			case strstr($type, "varchar"):
			case strstr($type, "text"):
				if(is_string($columnValue)){
					$returnValue = $columnValue;
				}else{
					$returnValue = ($columnValue != "") ? "'" . $columnValue . "'" : "NULL";
				}
				break;
				//http://dev.mysql.com/doc/refman/5.0/en/integer-types.html
			case strstr($type, "tinyint"): // -128 to 127, 0 to 255
			case strstr($type, "smallint"): // -32768 to 32767, 0 to 65535
			case strstr($type, "mediumint"): // -8388608 to 8388607, 0 to 16777215
			case strstr($type, "int"): // -2147483648 to 2147483647, 0 to 4294967295
			case strstr($type, "bigint"): // -9223372036854775808 to 9223372036854775807, 0 to 18446744073709551615
				$returnValue = ($columnValue != "") ? intval($columnValue) : "NULL";
				break;
				//http://dev.mysql.com/doc/refman/5.0/en/fixed-point-types.html
				//http://dev.mysql.com/doc/refman/5.0/en/floating-point-types.html
			case "float":
			case "double":
				$returnValue = ($columnValue != "") ? doubleval($columnValue) : "NULL";
				break;
			case "date":
			/*
				if($this->IsNullOrEmptyString($columnValue)){
					$returnValue = date("Y-m-d");
				}else{
					// convert string to date
					$tmpDate = date_parse($columnValue);
					if(!$tmpDate["errors"] == 0 && checkdate($tmpDate["month"], $tmpDate["day"], $tmpDate["year"]))
						$returnValue = date("Y-m-d"); // if convert with error, use the current date
					else
						$returnValue = $tmpDate->format("Y-m-d");
				}
				$returnValue = "'" . $returnValue . "'";
				*/
				if(is_string($columnValue)){
					$returnValue = $columnValue;
				}else{
					$returnValue = ($columnValue != "") ? "'" . $columnValue . "'" : "NULL";
				}
				break;
			case "datetime":
			case "timestamp":
			/*
				if($this->IsNullOrEmptyString($columnValue)){
					$returnValue = date("Y-m-d H:i:s");
				}else{
					// convert string to date
					$tmpDate = date_parse($columnValue);
					if(!$tmpDate["errors"] == 0 && checkdate($tmpDate["month"], $tmpDate["day"], $tmpDate["year"]))
						$returnValue = date("Y-m-d H:i:s"); // if convert with error, use the current date
					else
						$returnValue = $tmpDate->format("Y-m-d H:i:s");
				}
				$returnValue = "'" . $returnValue . "'";
				*/
				//return $columnValue;
				if(is_string($columnValue)){
					$returnValue = $columnValue;
				}else{
					$returnValue = ($columnValue != "") ? "'" . $columnValue . "'" : "NULL";
				}
				break;
		}
		return $returnValue;
	}
?>
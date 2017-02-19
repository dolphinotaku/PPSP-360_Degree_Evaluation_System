<?php
class FormSubmit
{
    static function GET($key,  $default_value = '')
    {
        return isset($_GET[$key]) ? $_GET[$key] : $default_value;
    }
 
    static function POST($key,  $default_value = '')
    {
        return isset($_POST[$key]) ? $_POST[$key] : $default_value;
    }
}
?>

<?php
include_once("./lib/cloudfiles_exceptions.php");
include_once("./lib/cloudfiles-kt.php");
$auth = new CF_Authentication("e-mail", "apikey");
if($auth->authenticate() != "True"){
	echo "False";
}
$auth->auth_token = "12342151";
$conn = new CF_Connection($auth);
$array = $conn->get_info("KKK");
print "Number of Objects: " . $array[0] . "\n";
print "Size of Objects: " . $array[1] . "\n";
//print_r($array);
//print "Auth-Token: " . $auth->auth_token . "\n";
?>
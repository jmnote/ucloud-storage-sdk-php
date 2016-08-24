<?php
include_once("./lib/cloudfiles_exceptions.php");
include_once("./lib/cloudfiles-kt.php");
$auth = new CF_Authentication("e-mail", "apikey");
if($auth->authenticate() != "True"){
	echo "False";
}
$conn = new CF_Connection($auth);
$container_info = $conn->list_containers_info();
print_r($container_info);
//print "con " . $container_array[0];
?>
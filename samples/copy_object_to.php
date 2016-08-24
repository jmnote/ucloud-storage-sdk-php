<?php
include_once("./lib/cloudfiles_exceptions.php");
include_once("./lib/cloudfiles-kt.php");
$auth = new CF_Authentication("e-mail", "apikey");
if($auth->authenticate() != "True"){
	echo "False";
}
$auth->auth_token = "12342151";
$conn = new CF_Connection($auth);
$container = $conn->get_container("test");
$container->copy_object_to("3.jpg","KKK");
?>
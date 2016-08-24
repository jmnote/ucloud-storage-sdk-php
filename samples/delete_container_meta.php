<?php
include_once("./lib/cloudfiles_exceptions.php");
include_once("./lib/cloudfiles-kt.php");
$auth = new CF_Authentication("e-mail", "apikey");
if($auth->authenticate() != "True"){
	echo "False";
}
$conn = new CF_Connection($auth);
$cinfo = $conn->get_container("test");
$cinfo->delete_container_user_metadata("name2");
?>
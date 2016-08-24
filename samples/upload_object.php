<?php
include_once("./lib/cloudfiles_exceptions.php");
include_once("./lib/cloudfiles-kt.php");
$auth = new CF_Authentication("e-mail", "apikey");
if($auth->authenticate() != "True"){
	echo "False";
}
$conn = new CF_Connection($auth);
$container = $conn->get_container("KKK");
$object = $container->create_object("3.jpg");
$object->content_type = 'image/jpeg';
$object->load_from_filename("3.jpg");
?>
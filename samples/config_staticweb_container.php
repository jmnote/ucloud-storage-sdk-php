<?php
include_once("./lib/cloudfiles_exceptions.php");
include_once("./lib/cloudfiles-kt.php");
$auth = new CF_Authentication("e-mail", "apikey");
if($auth->authenticate() != "True"){
	echo "False";
}
$conn = new CF_Connection($auth);
$container = $conn->get_container("test");
//$container->enableStaticWebsiteConfig("index.html","401error.hmtl", "true", "test.css");
$container->disableStaticWebsiteConfig();
?>
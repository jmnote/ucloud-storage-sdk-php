<?php
include_once("./lib/cloudfiles-kt.php");
$auth = new CF_Authentication("e-mail", "apikey");
if($auth->authenticate() != "True"){
	echo "False";
}
?>
<p><?php echo $auth->storage_url; ?></p>
<p><?php echo $auth->auth_token; ?></p>


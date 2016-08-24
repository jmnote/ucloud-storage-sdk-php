<?php

namespace UcloudStorage;

use CF_Authentication;
use CF_Connection;

class UcloudStorageClient
{
	static function factory($username, $api_key, $auth_host, &$exception_message)
	{
		try {
			$auth = new CF_Authentication($username, $api_key, $auth_host);
			$auth->ssl_use_cabundle();
			$auth->authenticate();
			return new CF_Connection($auth);
		} catch (\Exception $e) {
		    $exception_message = $e->getMessage();
			return false;
		}
	}
}

<?php
/**
 * This is the PHP Cloud Files API.
 * Rackspace에서 공개한 PHP API를 KT ucloud storage 사용을 위해 수정하였습니다.
 * 인증 처리를 위한 응답헤더 수정
 * API 추가 및 수정
 * <code>
 *   Authenticate to Cloud Files.  The default is to automatically try
 *   to re-authenticate if an authentication token expires.
 *   
 *   NOTE: Some versions of cURL include an outdated certificate authority (CA)
 *         file.  This API ships with a newer version obtained directly from
 *         cURL's web site (http://curl.haxx.se).  To use the newer CA bundle,
 *         call the CF_Authentication instance's 'ssl_use_cabundle()' method.
 *   
 *   $auth = new CF_Authentication($username, $api_key);
 *   $auth->ssl_use_cabundle();  # bypass cURL's old CA bundle
 *   $auth->authenticate();
 *
 *   Establish a connection to the storage system
 *   
 *   NOTE: Some versions of cURL include an outdated certificate authority (CA)
 *         file.  This API ships with a newer version obtained directly from
 *         cURL's web site (http://curl.haxx.se).  To use the newer CA bundle,
 *         call the CF_Connection instance's 'ssl_use_cabundle()' method.
 *   
 *   $conn = new CF_Connection($auth);
 *   $conn->ssl_use_cabundle();  # bypass cURL's old CA bundle
 *
 *   Create a remote Container and storage Object
 *   
 *   $images = $conn->create_container("photos");
 *   $bday = $images->create_object("first_birthday.jpg");
 *
 *   Upload content from a local file by streaming it.  Note that we use
 *   a "float" for the file size to overcome PHP's 32-bit integer limit for
 *   very large files.
 *   
 *   $fname = "/home/user/photos/birthdays/birthday1.jpg";  # filename to upload
 *   $size = (float) sprintf("%u", filesize($fname));
 *   $fp = open($fname, "r");
 *   $bday->write($fp, $size);
 *
 *   Or... use a convenience function instead
 *   
 *   $bday->load_from_filename("/home/user/photos/birthdays/birthday1.jpg");
 * </code>
 *
 * 샘플 코드 참조를 위해서는 samples 디렉토리를 참조하시기 바랍니다.
 *
 * Requres PHP 5.x (for Exceptions and OO syntax) and PHP's cURL module.
 *
 * It uses the supporting "cloudfiles_http.php" module for HTTP(s) support and
 * allows for connection re-use and streaming of content into/out of Cloud Files
 * via PHP's cURL module.
 *
 * See COPYING for license information.
 * @package php-cloudfiles-kt
 */

define("DEFAULT_CF_API_VERSION", 1);
define("MAX_CONTAINER_NAME_LEN", 256);
define("MAX_OBJECT_NAME_LEN", 1024);
define("MAX_OBJECT_SIZE", 5*1024*1024*1024+1);
define("AUTHURL", "https://api.ucloudbiz.olleh.com/storage/v1/auth");
define("JPAUTHURL", "https://api.ucloudbiz.olleh.com/storage/v1/authjp");
/**
 * Class for handling Cloud Files Authentication, call it's {@link authenticate()}
 * method to obtain authorized service urls and an authentication token.
 *
 * Example:
 * <code>
 * Create the authentication instance
 * 
 * $auth = new CF_Authentication("ucloudbiz_포탈_ID", "api_key");
 *
 * $auth = new CF_Authentication("ucloudbiz_포탈_ID", "api_key", NULL, AUTHURL);
 * Using the AUTHURL keyword will force the api to use the 'https://api.ucloudbiz.olleh.com/storage/v1/auth'.
 * 만일 JPN 서비스를 이용하고자 할 경우, JPAUTHURL 을 사용하면 된다.   
 *
 * NOTE: Some versions of cURL include an outdated certificate authority (CA)
 *       file.  This API ships with a newer version obtained directly from
 *       cURL's web site (http://curl.haxx.se).  To use the newer CA bundle,
 *       call the CF_Authentication instance's 'ssl_use_cabundle()' method.
 * 
 * $auth->ssl_use_cabundle(); # bypass cURL's old CA bundle
 *
 * Perform authentication request
 * 
 * $auth->authenticate();
 * </code>
 *
 * @package php-cloudfiles-kt
 */
class CF_Authentication
{
    public $dbug;
    public $username;
    public $api_key;
    public $auth_host;
    public $account;

    /**
     * Instance variables that are set after successful authentication
     */
    public $storage_url;
    public $auth_token;
    public $cfs_http;  // added by KT(2013.09.17)

    /**
     * Class constructor (PHP 5 syntax)
     *
     * @param string $username ucloudbiz_포탈_ID
     * @param string $api_key API Access Key
     * @param string $account  <i>Account name</i>
     * @param string $auth_host  <i>Authentication service URI</i>
     */
    function __construct($username=NULL, $api_key=NULL, $auth_host=AUTHURL)
    {

        $this->dbug = False;
        $this->username = $username;
        $this->api_key = $api_key;
        $this->auth_host = $auth_host;

        $this->storage_url = NULL;
        $this->auth_token = NULL;

        $this->cfs_http = new CF_Http(DEFAULT_CF_API_VERSION);
    }

    /**
     * Use the Certificate Authority bundle included with this API
     *
     * Most versions of PHP with cURL support include an outdated Certificate
     * Authority (CA) bundle (the file that lists all valid certificate
     * signing authorities).  The SSL certificates used by the Cloud Files
     * storage system are perfectly valid but have been created/signed by
     * a CA not listed in these outdated cURL distributions.
     *
     * As a work-around, we've included an updated CA bundle obtained
     * directly from cURL's web site (http://curl.haxx.se).  You can direct
     * the API to use this CA bundle by calling this method prior to making
     * any remote calls.  The best place to use this method is right after
     * the CF_Authentication instance has been instantiated.
     *
     * You can specify your own CA bundle by passing in the full pathname
     * to the bundle.  You can use the included CA bundle by leaving the
     * argument blank.
     *
     * @param string $path Specify path to CA bundle (default to included)
     */
    function ssl_use_cabundle($path=NULL)
    {
    	 $this->cfs_http->ssl_use_cabundle($path);
    }

    /**
     * Attempt to validate Username/API Access Key
     *
     * Attempts to validate credentials with the authentication service.  It
     * either returns <kbd>True</kbd> or throws an Exception.  Accepts a single
     * (optional) argument for the storage system API version.
     *
     * Example:
     * <code>
     * Create the authentication instance
     * 
     * $auth = new CF_Authentication("username", "api_key");
     *
     * Perform authentication request
     * 
     * $auth->authenticate();
     * </code>
     *
     * @param string $version API version for Auth service (optional)
     * @return boolean <kbd>True</kbd> if successfully authenticated
     * @throws AuthenticationException invalid credentials
     * @throws InvalidResponseException invalid response
     */
    function authenticate($version=DEFAULT_CF_API_VERSION)
    {
        list($status,$reason,$surl,$atoken) =
        	$this->cfs_http->authenticate($this->username, $this->api_key, $this->auth_host);

        if ($status == 401) {
            throw new AuthenticationException("Invalid username or access key.");
        }
        if ($status < 200 || $status > 299) {
            throw new InvalidResponseException(
                "Unexpected response (".$status."): ".$reason);
        }

        if (!($surl || $curl) || !$atoken) {
            throw new InvalidResponseException(
                "Expected headers missing from auth service.");
        }
        $this->storage_url = $surl;
        $this->auth_token = $atoken;
        return True;
    }
    
	/**
	 * Use Cached Token and Storage URL's rather then grabbing from the Auth System
     *
     * Example:
 	 * <code>
     * Create an Auth instance
     * $auth = new CF_Authentication();
     * Pass Cached URL's and Token as Args
	 * $auth->load_cached_credentials("auth_token", "storage_url");
     * </code>
	 *
	 * @param string $auth_token A Cloud Files Auth Token (Required)
     * @param string $storage_url The Cloud Files Storage URL (Required)
     * @return boolean <kbd>True</kbd> if successful
	 * @throws SyntaxException If any of the Required Arguments are missing
     */
	function load_cached_credentials($auth_token, $storage_url)
    {
        if(!$storage_url)
        {
        	throw new SyntaxException("Missing Required Interface URL's!");
        	return False;
        }
        if(!$auth_token)
        {
        	throw new SyntaxException("Missing Auth Token!");
        	return False;
        }

        $this->storage_url = $storage_url;
        $this->auth_token  = $auth_token;
        return True;
    }
    
	/**
     * Grab Cloud Files info to be Cached for later use with the load_cached_credentials method.
     *
	 * Example:
     * <code>
     * Create an Auth instance
     * $auth = new CF_Authentication("UserName","API_Key");
     * $auth->authenticate();
     * $array = $auth->export_credentials();
     * </code>
     *
	 * @return array of a storage url and an auth token.
     */
    function export_credentials()
    {
        $arr = array();
        $arr['storage_url'] = $this->storage_url;
        $arr['auth_token']  = $this->auth_token;

        return $arr;
    }

    /**
     * Make sure the CF_Authentication instance has authenticated.
     *
     * Ensures that the instance variables necessary to communicate with
     * Cloud Files have been set from a previous authenticate() call.
     *
     * @return boolean <kbd>True</kbd> if successfully authenticated
     */
    function authenticated()
    {
        if (!($this->storage_url || !$this->auth_token)) {
            return False;
        }
        return True;
    }

    /**
     * Toggle debugging - set cURL verbose flag
     */
    function setDebug($bool)
    {
        $this->dbug = $bool;
        $this->cfs_http->setDebug($bool);
    }
}

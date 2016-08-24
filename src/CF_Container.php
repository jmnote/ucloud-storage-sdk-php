<?php

/**
 * Container operations
 *
 * Containers are storage compartments where you put your data (objects).
 * A container is similar to a directory or folder on a conventional filesystem
 * with the exception that they exist in a flat namespace, you can not create
 * containers inside of containers.
 *
 * You also have the option of marking a Container as "public" so that the
 * Objects stored in the Container are publicly available via the CDN.
 *
 * @package php-cloudfiles-kt
 */

namespace UcloudStorage;

class CF_Container
{
    public $cfs_auth;
    public $cfs_http;
    public $name;
    public $object_count;
    public $bytes_used;
    public $metadata;

    /**
     * Class constructor
     *
     * Constructor for Container
     *
     * @param obj $cfs_auth CF_Authentication instance
     * @param obj $cfs_http HTTP connection manager
     * @param string $name name of Container
     * @param int $count number of Objects stored in this Container
     * @param int $bytes number of bytes stored in this Container
     * @throws SyntaxException invalid Container name
     */
    function __construct(&$cfs_auth, &$cfs_http, $name, $count=0,
        $bytes=0)
    {
        if (strlen($name) > MAX_CONTAINER_NAME_LEN) {
            throw new SyntaxException("Container name exceeds "
                . "maximum allowed length.");
        }
        if (strpos($name, "/") !== False) {
            throw new SyntaxException(
                "Container names cannot contain a '/' character.");
        }
        $this->cfs_auth = $cfs_auth;
        $this->cfs_http = $cfs_http;
        $this->name = $name;
        $this->object_count = $count;
        $this->bytes_used = $bytes;
        $this->metadata = array();
    }

    /**
     * String representation of Container
     *
     * Pretty print the Container instance.
     *
     * @return string Container details
     */
    function __toString()
    {
        $me = sprintf("name: %s, count: %.0f, bytes: %.0f",
            $this->name, $this->object_count, $this->bytes_used);
        return $me;
    }

    /**
     * Create a new remote storage Object
     *
     * Return a new Object instance.  If the remote storage Object exists,
     * the instance's attributes are populated.
     *
     * Example:
     * <code>
     * ... authentication code excluded (see previous examples) ...
     * 
     * $conn = new CF_Connection($auth);
     *
     * $public_container = $conn->get_container("public");
     *
     * This creates a local instance of a storage object but only creates
     * it in the storage system when the object's write() method is called.
     * 
     * $pic = $public_container->create_object("baby.jpg");
     * </code>
     *
     * @param string $obj_name name of storage Object
     * @return obj CF_Object instance
     */
    function create_object($obj_name=NULL)
    {
        return new CF_Object($this, $obj_name);
    }

    /**
     * Return an Object instance for the remote storage Object
     *
     * Given a name, return a Object instance representing the
     * remote storage object.
     *
     * Example:
     * <code>
     * ... authentication code excluded (see previous examples) ...
     * 
     * $conn = new CF_Connection($auth);
     *
     * $public_container = $conn->get_container("public");
     *
     * This call only fetches header information and not the content of
     * the storage object.  Use the Object's read() or stream() methods
     * to obtain the object's data.
     * 
     * $pic = $public_container->get_object("baby.jpg");
     * </code>
     *
     * @param string $obj_name name of storage Object
     * @return obj CF_Object instance
     */
    function get_object($obj_name=NULL)
    {
        return new CF_Object($this, $obj_name, True);
    }

    /**
     * Return a list of Objects
     *
     * Return an array of strings listing the Object names in this Container.
     *
     * Example:
     * <code>
     * ... authentication code excluded (see previous examples) ...
     * 
     * $images = $conn->get_container("my photos");
     *
     * Grab the list of all storage objects
     * 
     * $all_objects = $images->list_objects();
     *
     * Grab subsets of all storage objects
     * 
     * $first_ten = $images->list_objects(10);
     *
     * Note the use of the previous result's last object name being
     * used as the 'marker' parameter to fetch the next 10 objects
     * 
     * $next_ten = $images->list_objects(10, $first_ten[count($first_ten)-1]);
     *
     * Grab images starting with "birthday_party" and default limit/marker
     * to match all photos with that prefix
     * 
     * $prefixed = $images->list_objects(0, NULL, "birthday");
     *
     * Assuming you have created the appropriate directory marker Objects,
     * you can traverse your pseudo-hierarchical containers
     * with the "path" argument.
     * 
     * $animals = $images->list_objects(0,NULL,NULL,"pictures/animals");
     * $dogs = $images->list_objects(0,NULL,NULL,"pictures/animals/dogs");
     * </code>
     *
     * @param int $limit <i>optional</i> only return $limit names
     * @param int $marker <i>optional</i> subset of names starting at $marker
     * @param string $prefix <i>optional</i> Objects whose names begin with $prefix
     * @param string $path <i>optional</i> only return results under "pathname"
     * @return array array of strings
     * @throws InvalidResponseException unexpected response
     */
    function list_objects($limit=0, $marker=NULL, $prefix=NULL, $path=NULL)
    {
        list($status, $reason, $obj_list) =
            $this->cfs_http->list_objects($this->name, $limit,
                $marker, $prefix, $path);
        if ($status < 200 || $status > 299) {
            throw new InvalidResponseException(
                "Invalid response (".$status."): ".$this->cfs_http->get_error());
        }
        return $obj_list;
    }

    /**
     * Return an array of Objects
     *
     * Return an array of Object instances in this Container.
     *
     * Example:
     * <code>
     * ... authentication code excluded (see previous examples) ...
     * 
     * $images = $conn->get_container("my photos");
     *
     * Grab the list of all storage objects
     * 
     * $all_objects = $images->get_objects();
     *
     * Grab subsets of all storage objects
     * 
     * $first_ten = $images->get_objects(10);
     *
     * Note the use of the previous result's last object name being
     * used as the 'marker' parameter to fetch the next 10 objects
     * 
     * $next_ten = $images->list_objects(10, $first_ten[count($first_ten)-1]);
     *
     * Grab images starting with "birthday_party" and default limit/marker
     * to match all photos with that prefix
     * 
     * $prefixed = $images->get_objects(0, NULL, "birthday");
     *
     * Assuming you have created the appropriate directory marker Objects,
     * you can traverse your pseudo-hierarchical containers
     * with the "path" argument.
     * 
     * $animals = $images->get_objects(0,NULL,NULL,"pictures/animals");
     * $dogs = $images->get_objects(0,NULL,NULL,"pictures/animals/dogs");
     * </code>
     *
     * @param int $limit <i>optional</i> only return $limit names
     * @param int $marker <i>optional</i> subset of names starting at $marker
     * @param string $prefix <i>optional</i> Objects whose names begin with $prefix
     * @param string $path <i>optional</i> only return results under "pathname"
     * @return array array of strings
     * @throws InvalidResponseException unexpected response
     */
    function get_objects($limit=0, $marker=NULL, $prefix=NULL, $path=NULL, $delimiter=NULL)
    {
        list($status, $reason, $obj_array) =
            $this->cfs_http->get_objects($this->name, $limit,
                $marker, $prefix, $path, $delimiter);
        if ($status < 200 || $status > 299) {
            throw new InvalidResponseException(
                "Invalid response (".$status."): ".$this->cfs_http->get_error());
        }
        $objects = array();
        foreach ($obj_array as $obj) {
          if(!isset($obj['subdir'])) {
            $tmp = new CF_Object($this, $obj["name"], False, False);
            $tmp->content_type = $obj["content_type"];
            $tmp->content_length = (float) $obj["bytes"];
            $tmp->set_etag($obj["hash"]);
            $tmp->last_modified = $obj["last_modified"];
            $objects[] = $tmp;
          }
        }
        return $objects;
    }

    /**
     * Copy a remote storage Object to a target Container
     *
     * Given an Object instance or name and a target Container instance or name, copy copies the remote Object
     * and all associated metadata.
     *
     * Example:
     * <code>
     * ... authentication code excluded (see previous examples) ...
     * 
     * $conn = new CF_Connection($auth);
     *
     * $images = $conn->get_container("my photos");
     *
     * Copy specific object
     * 
     * $images->copy_object_to("disco_dancing.jpg","container_target");
     * </code>
     *
     * @param obj $obj name or instance of Object to copy
     * @param obj $container_target name or instance of target Container
     * @param string $dest_obj_name name of target object (optional - uses source name if omitted)
     * @param array $metadata metadata array for new object (optional)
     * @param array $headers header fields array for the new object (optional)
     * @return boolean <kbd>true</kbd> if successfully copied
     * @throws SyntaxException invalid Object/Container name
     * @throws NoSuchObjectException remote Object does not exist
     * @throws InvalidResponseException unexpected response
     */
    function copy_object_to($obj,$container_target,$dest_obj_name=NULL,$metadata=NULL,$headers=NULL)
    {
        $obj_name = NULL;
        if (is_object($obj)) {
            if (get_class($obj) == "CF_Object") {
                $obj_name = $obj->name;
            }
        }
        if (is_string($obj)) {
            $obj_name = $obj;
        }
        if (!$obj_name) {
            throw new SyntaxException("Object name not set.");
        }

		if ($dest_obj_name === NULL) {
            $dest_obj_name = $obj_name;
		}

        $container_name_target = NULL;
        if (is_object($container_target)) {
            if (get_class($container_target) == "CF_Container") {
                $container_name_target = $container_target->name;
            }
        }
        if (is_string($container_target)) {
            $container_name_target = $container_target;
        }
        if (!$container_name_target) {
            throw new SyntaxException("Container name target not set.");
        }

        $status = $this->cfs_http->copy_object($obj_name,$dest_obj_name,$this->name,$container_name_target,$metadata,$headers);
        if ($status == 404) {
            $m = "Specified object '".$this->name."/".$obj_name;
            $m.= "' did not exist as source to copy from or '".$container_name_target."' did not exist as target to copy to.";
            throw new NoSuchObjectException($m);
        }
        if ($status < 200 || $status > 299) {
            throw new InvalidResponseException(
                "Invalid response (".$status."): ".$this->cfs_http->get_error());
        }
        return true;
    }

    /**
     * Copy a remote storage Object from a source Container
     *
     * Given an Object instance or name and a source Container instance or name, copy copies the remote Object
     * and all associated metadata.
     *
     * Example:
     * <code>
     * ... authentication code excluded (see previous examples) ...
     * 
     * $conn = new CF_Connection($auth);
     *
     * $images = $conn->get_container("my photos");
     *
     * Copy specific object
     * 
     * $images->copy_object_from("disco_dancing.jpg","container_source");
     * </code>
     *
     * @param obj $obj name or instance of Object to copy
     * @param obj $container_source name or instance of source Container
     * @param string $dest_obj_name name of target object (optional - uses source name if omitted)
     * @param array $metadata metadata array for new object (optional)
     * @param array $headers header fields array for the new object (optional)
     * @return boolean <kbd>true</kbd> if successfully copied
     * @throws SyntaxException invalid Object/Container name
     * @throws NoSuchObjectException remote Object does not exist
     * @throws InvalidResponseException unexpected response
     */
    function copy_object_from($obj,$container_source,$dest_obj_name=NULL,$metadata=NULL,$headers=NULL)
    {
        $obj_name = NULL;
        if (is_object($obj)) {
            if (get_class($obj) == "CF_Object") {
                $obj_name = $obj->name;
            }
        }
        if (is_string($obj)) {
            $obj_name = $obj;
        }
        if (!$obj_name) {
            throw new SyntaxException("Object name not set.");
        }

				if ($dest_obj_name === NULL) {
            $dest_obj_name = $obj_name;
				}

        $container_name_source = NULL;
        if (is_object($container_source)) {
            if (get_class($container_source) == "CF_Container") {
                $container_name_source = $container_source->name;
            }
        }
        if (is_string($container_source)) {
            $container_name_source = $container_source;
        }
        if (!$container_name_source) {
            throw new SyntaxException("Container name source not set.");
        }

        $status = $this->cfs_http->copy_object($obj_name,$dest_obj_name,$container_name_source,$this->name,$metadata,$headers);
        if ($status == 404) {
            $m = "Specified object '".$container_name_source."/".$obj_name;
            $m.= "' did not exist as source to copy from or '".$this->name."/".$obj_name."' did not exist as target to copy to.";
            throw new NoSuchObjectException($m);
        }
        if ($status < 200 || $status > 299) {
            throw new InvalidResponseException(
                "Invalid response (".$status."): ".$this->cfs_http->get_error());
        }

        return true;
    }

    /**
     * Move a remote storage Object to a target Container
     *
     * Given an Object instance or name and a target Container instance or name, move copies the remote Object
     * and all associated metadata and deletes the source Object afterwards
     *
     * Example:
     * <code>
     * ... authentication code excluded (see previous examples) ...
     * 
     * $conn = new CF_Connection($auth);
     *
     * $images = $conn->get_container("my photos");
     *
     * Move specific object
     * 
     * $images->move_object_to("disco_dancing.jpg","container_target");
     * </code>
     *
     * @param obj $obj name or instance of Object to move
     * @param obj $container_target name or instance of target Container
     * @param string $dest_obj_name name of target object (optional - uses source name if omitted)
     * @param array $metadata metadata array for new object (optional)
     * @param array $headers header fields array for the new object (optional)
     * @return boolean <kbd>true</kbd> if successfully moved
     * @throws SyntaxException invalid Object/Container name
     * @throws NoSuchObjectException remote Object does not exist
     * @throws InvalidResponseException unexpected response
     */
    function move_object_to($obj,$container_target,$dest_obj_name=NULL,$metadata=NULL,$headers=NULL)
    {
    	$retVal = false;

        if(self::copy_object_to($obj,$container_target,$dest_obj_name,$metadata,$headers)) {
        	$retVal = self::delete_object($obj,$this->name);
        }

        return $retVal;
    }

    /**
     * Move a remote storage Object from a source Container
     *
     * Given an Object instance or name and a source Container instance or name, move copies the remote Object
     * and all associated metadata and deletes the source Object afterwards
     *
     * Example:
     * <code>
     * ... authentication code excluded (see previous examples) ...
     * 
     * $conn = new CF_Connection($auth);
     *
     * $images = $conn->get_container("my photos");
     *
     * Move specific object
     * 
     * $images->move_object_from("disco_dancing.jpg","container_target");
     * </code>
     *
     * @param obj $obj name or instance of Object to move
     * @param obj $container_source name or instance of target Container
     * @param string $dest_obj_name name of target object (optional - uses source name if omitted)
     * @param array $metadata metadata array for new object (optional)
     * @param array $headers header fields array for the new object (optional)
     * @return boolean <kbd>true</kbd> if successfully moved
     * @throws SyntaxException invalid Object/Container name
     * @throws NoSuchObjectException remote Object does not exist
     * @throws InvalidResponseException unexpected response
     */
    function move_object_from($obj,$container_source,$dest_obj_name=NULL,$metadata=NULL,$headers=NULL)
    {
    	$retVal = false;

        if(self::copy_object_from($obj,$container_source,$dest_obj_name,$metadata,$headers)) {
        	$retVal = self::delete_object($obj,$container_source);
        }

        return $retVal;
    }

    /**
     * Delete a remote storage Object
     *
     * Given an Object instance or name, permanently remove the remote Object
     * and all associated metadata.
     *
     * Example:
     * <code>
     * ... authentication code excluded (see previous examples) ...
     * 
     * $conn = new CF_Connection($auth);
     *
     * $images = $conn->get_container("my photos");
     *
     * Delete specific object
     * 
     * $images->delete_object("disco_dancing.jpg");
     * </code>
     *
     * @param obj $obj name or instance of Object to delete
     * @param obj $container name or instance of Container in which the object resides (optional)
     * @return boolean <kbd>True</kbd> if successfully removed
     * @throws SyntaxException invalid Object name
     * @throws NoSuchObjectException remote Object does not exist
     * @throws InvalidResponseException unexpected response
     */
    function delete_object($obj,$container=NULL)
    {
        $obj_name = NULL;
        if (is_object($obj)) {
            if (get_class($obj) == "CF_Object") {
                $obj_name = $obj->name;
            }
        }
        if (is_string($obj)) {
            $obj_name = $obj;
        }
        if (!$obj_name) {
            throw new SyntaxException("Object name not set.");
        }

        $container_name = NULL;

        if($container === NULL) {
        	$container_name = $this->name;
        }
        else {
	        if (is_object($container)) {
	            if (get_class($container) == "CF_Container") {
	                $container_name = $container->name;
	            }
	        }
	        if (is_string($container)) {
	            $container_name = $container;
	        }
	        if (!$container_name) {
	            throw new SyntaxException("Container name source not set.");
	        }
        }

        $status = $this->cfs_http->delete_object($container_name, $obj_name);
        if ($status == 404) {
            $m = "Specified object '".$container_name."/".$obj_name;
            $m.= "' did not exist to delete.";
            throw new NoSuchObjectException($m);
        }
        if ($status != 204) {
            throw new InvalidResponseException(
                "Invalid response (".$status."): ".$this->cfs_http->get_error());
        }
        return True;
    }
    
    /**
     * 해당 컨테이너를 static website로 설정한다. 
     * 서비스 이용방법은 가이드를 참조한다.
     * https://ucloudbiz.olleh.com/manual/ucloud_storage_Static_Web_service_user_guide.pdf
     *
     * Example:
     * <code>
     * ... authentication code excluded (see previous examples) ...
     * 
     * $conn = new CF_Connection($auth);
     *
     * $test_container = $conn->get_container("test");
     *
     * Config specific container
     * 
     * $test_container->enableStaticWebsiteConfig("index.html","error.hmtl", "true", "test.css");
     * </code>
     *
     * @param index $index static website에 대한 index 파일 지정(임의지정 가능)
     * @param error $error static website에 대한 에러 파일 suffix(임의지정 가능)
     * @param listing $listing index 파일 미 설정시 object를 리스트로 보여 줄 것인지 설정
     * @param css $css style sheet 파일 지정
     * @return boolean <kbd>True</kbd> if successfully removed
     */
    function enableStaticWebsiteConfig($index=NULL,$error=NULL,$listings=NULL,$css=NULL) {
	    $usermetadata = array();
	   	if($index != NULL) {
		   	$usermetadata[] = CONTAINER_METADATA_HEADER_PREFIX . "Web-Index:" . $index;
	   	} 
	   	if($error != NULL) {
		 	$usermetadata[] = CONTAINER_METADATA_HEADER_PREFIX . "Web-Error:" . $error;	   	
	   	}
	   	if($listings != NULL) {
		 	$usermetadata[] = CONTAINER_METADATA_HEADER_PREFIX . "Web-Listings:" . $listings;	   	
	   	}
	   	if($error != NULL) {
		 	$usermetadata[] = CONTAINER_METADATA_HEADER_PREFIX . "Web-Listings-Css:" . $css;	   	
	   	}	  
	   	$usermetadata[] = "X-Container-Read: " . ".r:*"; 	
	   	return $this->addUpdate_container_metadata($usermetadata);
    }
    
    /**
     * 해당 컨테이너의 static website 설정을 취소한다. 
     *
     * Example:
     * <code>
     * ... authentication code excluded (see previous examples) ...
     * 
     * $conn = new CF_Connection($auth);
     *
     * $test_container = $conn->get_container("test");
     *
     * Config specific container
     * 
     * $test_container->disableStaticWebsiteConfig();
     * </code>
     *
     * @return boolean <kbd>True</kbd> if successfully removed
     */
    function disableStaticWebsiteConfig() {
	    $usermetadata = array();
		$usermetadata[] = CONTAINER_REMOVE_METADATA_HEADER_PREFIX . "Web-Index:tempValue";
		$usermetadata[] = CONTAINER_REMOVE_METADATA_HEADER_PREFIX . "Web-Error:tempValue";	   	
		$usermetadata[] = CONTAINER_REMOVE_METADATA_HEADER_PREFIX . "Web-Listings:tempValue";	   	
		$usermetadata[] = CONTAINER_REMOVE_METADATA_HEADER_PREFIX . "Web-Listings-Css:tempValue";  	
	   	$usermetadata[] = "X-Remove-Container-Read: " . ".r:*";   	
	   	return $this->addUpdate_container_metadata($usermetadata);
    }
     
    /**
     * 해당 컨테이너를 대한 접근정보를 로그파일로 저장한다. 
     * 서비스 이용방법은 가이드를 참조한다.
     * https://ucloudbiz.olleh.com/manual/ucloud_storage_log_save_service_user_guide.pdf
     *
     * Example:
     * <code>
     * ... authentication code excluded (see previous examples) ...
     * 
     * $conn = new CF_Connection($auth);
     *
     * $test_container = $conn->get_container("test");
     *
     * Config specific container
     * 
     * $test_container->enableContainerLogging();
     * </code>
     *
     * @return boolean <kbd>True</kbd> if successfully removed
     */   
    function enableContainerLogging() {
	    $usermetadata = array();
	    $usermetadata[] = CONTAINER_METADATA_HEADER_PREFIX . "Access-Log-Delivery:true";
	    return $this->addUpdate_container_metadata($usermetadata);
    }
    
    /**
     * 해당 컨테이너에 대한 로깅 설정을 해제한다. 
     *
     * Example:
     * <code>
     * ... authentication code excluded (see previous examples) ...
     * 
     * $conn = new CF_Connection($auth);
     *
     * $test_container = $conn->get_container("test");
     *
     * Config specific container
     * 
     * $test_container->disableContainerLogging();
     * </code>
     *
     * @return boolean <kbd>True</kbd> if successfully removed
     */ 
    function disableContainerLogging() {
	    $usermetadata = array();
	    $usermetadata[] = CONTAINER_REMOVE_METADATA_HEADER_PREFIX . "Access-Log-Delivery:true";
	    return $this->addUpdate_container_metadata($usermetadata);
    }
    
    /**
     * 해당 컨테이너를 공개 설정한다. 공개 설정 할 경우, 인증 없이 
     * 컨테이너에 임의의 접근(읽기 및 조회)이 가능하다. 쓰기는 불가능하다.
     *
     * Example:
     * <code>
     * ... authentication code excluded (see previous examples) ...
     * 
     * $conn = new CF_Connection($auth);
     *
     * $test_container = $conn->get_container("test");
     *
     * Config specific container
     * 
     * $test_container->make_public();
     * </code>
     *
     * @return boolean <kbd>True</kbd> if successfully removed
     */ 
    function make_public() {
	    $usermetadata = array();
	    $usermetadata[] = "X-Container-Read: .r:*";  
	    return $this->addUpdate_container_metadata($usermetadata);		    
    }
    
    /**
     * 해당 컨테이너를 공개 설정를 취소한다.
     *
     * Example:
     * <code>
     * ... authentication code excluded (see previous examples) ...
     * 
     * $conn = new CF_Connection($auth);
     *
     * $test_container = $conn->get_container("test");
     *
     * Config specific container
     * 
     * $test_container->make_private();
     * </code>
     *
     * @return boolean <kbd>True</kbd> if successfully removed
     */ 
    function make_private() {
	 	$usermetadata = array();
	    $usermetadata[] = "X-Remove-Container-Read: .r:*";  
	    return $this->addUpdate_container_metadata($usermetadata);    
    }
    
    /**
     * 해당 컨테이너에 사용자 정의 metadata를 추가/갱신한다.
     * 사용자 metadata는 마지막 저장값이 유지된다. 즉, 특정 key에 대한 
     * metadata를 갱신할 경우 기존 값은 요청된 값으로 대체되고 이전 값은 
     * 삭제된다.
     *
     * Example:
     * <code>
     * ... authentication code excluded (see previous examples) ...
     * 
     * $conn = new CF_Connection($auth);
     *
     * $test_container = $conn->get_container("test");
     *
     * Config specific container
     * 
     * $test_container->addUpdate_container_user_metadata($key, $value);
     * </code>
     *
     * @param key $key 사용자 metadata에 대한 key 
     * @param value $value 사용자 metadata에 대한 value
     * @return boolean <kbd>True</kbd> if successfully removed
     */ 
    function addUpdate_container_user_metadata($key, $value) {
	    $usermetadata = array();
	    $user_header = "";
	    $user_value = "";
	    if (is_string($key)) {
            $user_header = CONTAINER_METADATA_HEADER_PREFIX . $key; 
        }
	    if (is_string($value)) {
            $usermetadata[] = $user_header . ": " . $value; 
        }
	    return $this->addUpdate_container_metadata($usermetadata);	    
    }
    
    /**
     * 해당 컨테이너의 사용자 정의 metadata를 삭제한다.
     * 지정된 특정 key에 대한 metadata를 삭제한다.
     *
     * Example:
     * <code>
     * ... authentication code excluded (see previous examples) ...
     * 
     * $conn = new CF_Connection($auth);
     *
     * $test_container = $conn->get_container("test");
     *
     * Config specific container
     * 
     * $test_container->delete_container_user_metadata($key);
     * </code>
     *
     * @param key $key 사용자 metadata에 대한 key 
     * @return boolean <kbd>True</kbd> if successfully removed
     */ 
    function delete_container_user_metadata($key) {
	    $usermetadata = array();
	    $user_header = "";
	    $user_value = "";
	    if (is_string($key)) {
            $user_header = CONTAINER_REMOVE_METADATA_HEADER_PREFIX . $key; 
        }
        $usermetadata[] = $user_header . ": tempValue"; 
	    return $this->addUpdate_container_metadata($usermetadata);	    
    }

    /**
     * Helper function to create "path" elements for a given Object name
     *
     * Given an Object whose name contains '/' path separators, this function
     * will create the "directory marker" Objects of one byte with the
     * Content-Type of "application/directory".
     *
     * It assumes the last element of the full path is the "real" Object
     * and does NOT create a remote storage Object for that last element.
     */
    function create_paths($path_name)
    {
        if ($path_name[0] == '/') {
            $path_name = mb_substr($path_name, 0, 1);
        }
        $elements = explode('/', $path_name, -1);
        $build_path = "";
        foreach ($elements as $idx => $val) {
            if (!$build_path) {
                $build_path = $val;
            } else {
                $build_path .= "/" . $val;
            }
            $obj = new CF_Object($this, $build_path);
            $obj->content_type = "application/directory";
            $obj->write(".", 1);
        }
    }
    
    private function addUpdate_container_metadata($usermetadata) {
	    if (!is_array($usermetadata)) {
            throw new SyntaxException("Metadata array is empty");
        }
        
        $this->metadata = $usermetadata;
        $status = $this->cfs_http->post_container($this);
	    
	    if ($status == 404) {
            $m = "Specified object '".$container_name."/".$obj_name;
            $m.= "' did not exist to delete.";
            throw new NoSuchObjectException($m);
        }
        if ($status != 204) {
            throw new InvalidResponseException(
                "Invalid response (".$status."): ".$this->cfs_http->get_error());
        }
        return True;    
    }
}

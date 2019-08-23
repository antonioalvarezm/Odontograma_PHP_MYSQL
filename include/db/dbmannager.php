<?php
error_reporting ( E_ALL & ~ E_NOTICE );
include 'config.php';
class DBManager {
	var $conn_id;
	var $result;
	var $record;
	var $fields;
	var $field;
	var $db = array ();
	var $port;
	var $error_sql;
	function DBManager() {
		global $GB_DB;
		$this->db = &$GB_DB;
		if (strpos ( $this->db ['host'], ':' ) !== FALSE) {
			list ( $host, $port ) = explode ( ":", $this->db ['host'] );
			$this->port = $port;
		} else {
			$this->port = 3306;
		}
	}
	function connect() {
		$this->conn_id = mysqli_connect ( $this->db ['host'], $this->db ['user'], $this->db ['pass'], $this->db ['dbName'] );
		if ($this->conn_id == 0) {
			$this->sql_error ( "Connection Error" );
		}
		mysqli_query ( $this->conn_id, "SET character_set_results = 'utf8', character_set_client = 'utf8', character_set_connection = 'utf8', character_set_database = 'utf8', character_set_server = 'utf8'" );
		return $this->conn_id;
	}
	function get_conn() {
		return $this->conn_id;
	}
	function get_result() {
		return $this->result;
	}
	function get_error() {
		return $this->error_sql;
	}
	function updated($query_string) {
		try {
			mysqli_query ( $this->conn_id, $query_string );
			return true;
		} catch ( Exception $e ) {
			$this->sql_error ( "Query Error " . $e );
			return false;
		}
	}
	function execute($query_string) {
		$id = 0;
		try {
			mysqli_query ( $this->conn_id, $query_string );
			$id = mysqli_insert_id ( $this->conn_id );
		} catch ( Exception $e ) {
			$this->sql_error ( "Query Error " . $e );
			$id = 0;
		}
		return $id;
	}
	function query($query_string) {
		$this->result = mysqli_query ( $this->conn_id, $query_string );
		if (! $this->result) {
			$this->sql_error ( "Query Error: " . $query_string );
		}
		return $this->result;
	}
	function exists($query_string) {
		$exists = false;
		$this->result = mysqli_query ( $this->conn_id, $query_string );
		while ( $rs = mysqli_fetch_assoc ( $this->result ) ) {
			if ($rs ["numero"] > 0) {
				$exists = true;
			}
		}
		if (! $this->result) {
			$this->sql_error ( "Query Error: " . $query_string );
		}
		return $exists;
	}
	function multiple_query($query_string) {
		$this->result = $this->conn_id->multi_query ( $query_string );
		if (! $this->result) {
			$this->sql_error ( "Query Error: " . $query_string );
		}
		return $this->result;
	}
	function call_stored_procedure($query) {
		$this->result = mysqli_prepare ( $this->conn_id, $query );
		if (! $this->result) {
			$this->sql_error ( "Stored Procedure Error: " . $query );
		}
		return $this->result;
	}
	function fields_array($query_id) {
		$this->fields = mysqli_fetch_fields ( $query_id );
		return $fields;
	}
	function field_name($query_id) {
		$this->fields = array ();
		while ( $finfo = $query_id->fetch_field () ) {
			$this->fields [$finfo->name] = $finfo->name;
		}
		return $this->fields;
	}
	function fetch_array($query_id) {
		$this->record = mysqli_fetch_array ( $query_id, MYSQL_ASSOC );
		return $this->record;
	}
	function num_rows($query_id) {
		return ($query_id) ? mysqli_num_rows ( $query_id ) : 0;
	}
	function num_fields($query_id) {
		return ($query_id) ? mysqli_num_fields ( $query_id ) : 0;
	}
	function free_result($query_id) {
		return mysqli_free_result ( $query_id );
	}
	function affected_rows() {
		return mysqli_affected_rows ( $this->conn_id );
	}
	function close_db() {
		if ($this->conn_id) {
			return mysqli_close ( $this->conn_id );
		} else {
			return false;
		}
	}
	function sql_error($message) {
		$error = array ();
		$detail = array ();
		$mysqlerror = mysql_error ();
		$number = mysql_errno ();
		if(empty($number)){
			$number = 1;
		}
		if($message==null){
		    $message = "Servicio no disponible ".$mysqlerror;
		}
		$error["code"] = $number;
		$error["category"] = "database";
		$error["message"] = $message;
		
		$detail["date"] = date ( "D, F j, Y H:i:s" );
		$detail["ip"] = getenv ( "REMOTE_ADDR" );
// 		$detail["browser"] = getenv ( "HTTP_USER_AGENT" ) ;
// 		$detail["referer"] = getenv ( "HTTP_REFERER" );
// 		$detail["php_version"] = PHP_VERSION;
// 		$detail["os"] = PHP_OS;
// 		$detail["server"] = getenv ( "SERVER_SOFTWARE" );
// 		$detail["server_name"] = getenv ( "SERVER_NAME" );
		$error["description"] = $detail;
		$error = array (
				"errores" => $error
		);
		$json = json_encode ( $error, JSON_UNESCAPED_UNICODE );
		echo $json;
		exit ();
	}
}

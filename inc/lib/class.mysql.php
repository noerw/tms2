<?php

// anti hack
defined('in_tms') or exit;

// manages mysql connection
class SQL {

	private $conn, $lastq;
	public $query_count = 0, $query_time = 0;


	// Singleton
	protected static $_fledging;

	public function Fledging($settings = array()) {
		$c = __CLASS__;
		if (!isset(self::$_fledging))
			self::$_fledging = new $c($settings);
		return self::$_fledging;
	}

	// constructor. connects upon class formation.
	function __construct ($settings) {

		list ($server, $user, $pw, $db, $persist) = $settings;

		// connect
		$this->conn = (
			$persist ? @mysql_pconnect($server, $user, $pw) :
						@mysql_connect($server, $user, $pw))
			or exit ("Error Connecting to MySQL: ".mysql_error()."\n");

		// select db
		@mysql_select_db($db, $this->conn) or
			exit ("Error Selecting MySQL DB: ".mysql_error() . "\n");

	#	$this->query("set names 'utf8'");
		

		return true;

	}

	// run a query
	function query($q) {
		
		// Time us
		$query_start = microtime(true);

		// Run it
		$this->lastq = @mysql_query($q, $this->conn) or
			$this->error_message ("Query Error: ".mysql_error()."\n\n$q\n");

		// Increment query count
		$this->query_count++;

		// Increment query time
		$this->query_time += (microtime(true) - $query_start);

		// return result
		return $this->lastq;
	}

	// run an unbuffered query. (used only a few times)
	function query_unbuff($q)
	{
		// Time us
		$query_start = microtime(true);

		// Run it
		$this->lastq = @mysql_unbuffered_query($q, $this->conn) or
			$this->error_message ("Query Error: ".mysql_error()."\n\n$q\n");

		// Increment query count
		$this->query_count++;

		// Increment query time
		$this->query_time += (microtime(true) - $query_start);

		// return result
		return $this->lastq;

	}

	// free a result
	function free($r) {
		@mysql_free_result($r);
	}

	// free last query result
	function freelast() {
		if (is_resource($this->conn) && is_resource($this->lastq))
			@mysql_free_result($this->lastq);
		return true;
	}

	// get result as a numerical array
	function fetch_row($r = null) {
		$r = @$r ? $r : $this->lastq; // 2b cool
		$result = @mysql_fetch_row($r);
		if (is_array($result))
			foreach ($result as $k => $v)
				$result[$k] = ctype_digit($v) ? $v : stripslashes($v);
		return $result;
	}

	// get result as a associative array
	function fetch_assoc($r = null) {
		$r = @$r ? $r : $this->lastq; // 2b cool
		$result = @mysql_fetch_assoc($r);
		if (is_array($result))
			foreach ($result as $k => $v)
				$result[$k] = ctype_digit($v) ? $v : stripslashes($v);
		return $result;
	}

	// get result as an object
	function fetch_object($r = null) {
		$r = @$r ? $r : $this->lastq; // 2b cool
		//return @mysql_fetch_object($r); // too slow?
		return (object) $this->fetch_assoc($r);
	}

	// get only one field 
	function fetch_one($r = null) {
		$r = @$r ? $r : $this->lastq; // 2b cool
		list($result) = $this->fetch_row($r);
		return is_numeric($result) ? $result : stripslashes($result);
	}

	// close mysql connection
	function close() {
		if (is_resource($this->conn))
			mysql_close($this->conn);
	}

	// escape
	function prot($s) {
		return is_numeric($s) ? $s : mysql_real_escape_string($s, $this->conn);
	}

	// escape for use in LIKE '' statements where there are other special characters
	function like_prot($s) {
		return str_replace(array('%', '_'), array('\%', '\_'), $this->prot($s));
	}

	// last id
	function lastid() {
		return mysql_insert_id($this->conn);
	}

	// rows affected by last update/replace/delete/insert
	function affected_rows() {
		return mysql_affected_rows($this->conn);
	}

	// show error message
	function error_message($msg) {

		// stop any html from getting out too. just show the error message
		while (ob_end_clean());

		// halt whilst showing the message
		#exit ("MySQL Error: \n".mysql_error($this->conn)."\n");
		exit($msg);
	}

	// are we connected?
	function is_connected() {
		return is_resource($this->conn);
	}

	// return number of results
	function num($r) {
		return mysql_num_rows($r);
	}
}

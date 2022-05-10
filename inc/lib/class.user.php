<?php

// TMS Rewritten - Early 2010
// By Joseph Robert Gillotti

defined('in_tms') or exit;	// Anti inclusion hack

// User management
class Ui {

	/*
	 * These pertain to a logged in user
	 */
	private
		$user = array(), // Array of their info
		$_logged_in = false,
		$verif_key,
		$verif_val,
		$is_admin = false; // Am I gifted?

	/*
	 * Overall and Daily hits
	 */
	public
		$hits = array();


	/*
	 * SQL handle
	 */
	protected $_sql;

	/*
	 * Settings
	 */
	const onlineTimeOut = 300;

	/*
	 * Singleton
	 */
	protected static $_fledging;

	public function Fledging($settings = array()) {
		$c = __CLASS__;
		if (!isset(self::$_fledging))
			self::$_fledging = new $c($settings);
		return self::$_fledging;
	}

	/*
	 * Start us off
	 */
	function __construct() {

		// Need layout class
		global $layout;

		// Localize SQL handle
		$this->_sql = SQL::Fledging();

		// Log us in
		$this->manageSession();

		// Are we banned?
		if ($this->isBanned()) {
			$layout->errorMsgMinimal('You are banned.');
			exit; // Redundantly prevent anything more
		}
	}

	/*
	 * Create a login session with a UID
	 */

  public function createAuthSession($uid){
    $_SESSION['uid'] = $uid;
    $this->manageSession();
  }

	/*
	 * Attempt to auth
	 */
	private function manageSession() {
		global $sp;

		// See if session variables are okay. If not, stop here
		if (!ctype_digit($_SESSION['uid'])|| !is_numeric($_SESSION['uid']))
			return;

		// Localize
		$sess_uid = (int) $_SESSION['uid'];

		// Might be satisfactory, test
		$check_user = $this->_sql->query("
		select
			m.id,
			m.username,
			m.banned,
			m.pending,
			m.theme,
			m.timezone_offset,
			m.nobwfilter,
			m.nummaps
		from
			members as m
		where
			m.id = '$sess_uid'
		limit 1
		");

		// No?
		if ($this->_sql->num($check_user) == 0) {

			$this->_sql->free($check_user);
			$this->killSession();
			return;
		}

		// Yes we have a match; get values
		list(
			$this->user['uid'], $this->user['username'], $this->user['banned'], $this->user['pending'],
			$this->user['theme'], $this->user['timezone_offset'], $this->user['filter_badwords'], $this->user['num_maps']
		)
		= $this->_sql->fetch_row($check_user);
		$this->_sql->free($check_user);

		// And we're logged in, btw
		$this->_logged_in = true;

		// Deal with user verification shiz
		$this->setUserVerification();

		// Useful when called in a long loop to avoid the overhead of a function call, $ui->userID();
		define('USER_ID', $this->user['uid']);

		// Reference later on
		$this->_sql->query("update members set lastaction = unix_timestamp() where id = '{$this->user['uid']}' limit 1");

		if (in_array($this->user['uid'], (array) @unserialize(SITE_ADMINS)))
			$this->is_admin = true;
	}

	/*
	 * Values that are limited to this session and this user
	 */
	private function setUserVerification() {
		// Need to be logged in to be of any use
		if (!$this->_logged_in)
			return;

		// Make them random but constant. Key must start with a non-number.
		$this->verif_key = 'k'.substr(sha1(md5($this->user['uid'].session_id())), 1, 15);
		$this->verif_val = sha1(md5(serialize($this->user).md5($this->user['uid'].session_id())));

		// Any scope access
		define('VERIF_KEY', $this->verif_key);
		define('VERIF_VAL', $this->verif_val);
	}

	/*
	 * Get vals for above
	 */
	public function verifKey() {
		return $this->_logged_in ? $this->verif_key : false;
	}

	/*
	 * Get key for above
	 */
	public function verifVal() {
		return $this->_logged_in ? $this->verif_val : false;
	}

	/*
	 * Are we verified?
	 */
	public function isVerified($strict = false) {
		// If we're not logged in, definitely not
		if (!$this->_logged_in) {
			return false;
		}

		// If a POST or GET value is our key. Not a val in session and definitely not in a cookie
		// If strict, only POST
		return $strict ? $_POST[$this->verifKey()] == $this->verifVal() : $_POST[$this->verifKey()] == $this->verifVal() || $_GET[$this->verifKey()] == $this->verifVal();
	}

	/*
	 * Am I an admin?
	 */
	public function isAdmin() {
		// Definitely not
		if (!$this->_logged_in)
			return false;

		// Eh?
		return (bool) $this->is_admin;
	}

	/*
	 * Log us out
	 */
	public function killSession() {

		unset($_SESSION['uid']);

		// Set us logged out.
		$this->_logged_in = false;

		// Make sure use array is empty
		$this->user = false;
	}

	/*
	 * Am I banned?
	 */
	public function isBanned() {

		// Check if user is banned
		if ($this->_logged_in) {
			return $this->user['banned'] == '1';
		}
	}


	/*
	 * Deal with online users
	 */
	function manageOnline() {

		// Should I be in there?
		if ($this->_logged_in)
			$this->_sql->query("
			insert into `online` set
				`uid` = '{$this->_sql->prot($this->user['uid'])}',
				`time` = UNIX_TIMESTAMP()
			on duplicate key update `time` = UNIX_TIMESTAMP()");

		// Remove old guys
		$this->_sql->query("delete from `online` where (UNIX_TIMESTAMP() - `time`) > ".self::onlineTimeOut);
	}

	/*
	 * Is current user a bot?
	 */
	function isBot() {
		// Some bots do not have a user agent
		if (trim($_SERVER['HTTP_USER_AGENT']) == '')
			return true;

		// Others are typical:
		$bots = array(
			'googlebot',
			'yahoo',
			'msn',
			'search',
			'bot',
			'index',
			'load',
			'Charlotte',
			'crawl',
			'Mediapartners',
			'feed',
			'facebookexternalhit',
			'fetch',
			'spider',
			'bot',
			'yandex',
			'lwp\-request',
			'W3C',
			'krawl',
			'baidu',
			'ahref',
			'PHP\/SMF',
		'alexa');

		// Go for it:
		return (bool) preg_match('/'.implode('|', $bots).'/i', $_SERVER['HTTP_USER_AGENT']);
	}

	/*
	 * Functions used all over the place to test stuff
	 */
	public function loggedIn() {
		return (bool) $this->_logged_in;
	}

	public function userID() {
		return $this->_logged_in ? (int) $this->user['uid'] : false;
	}

	public function userName() {
		return $this->_logged_in ? stripslashes($this->user['username']) : false;
	}

	public function userActivated() {
		return $this->_logged_in ? $this->user['pending'] == '0' : false;
	}

	public function userAdmin() {
		global $site_admins;
		return $this->_logged_in ? in_array($this->user['uid'], $site_admins) : false;
	}

	public function userTheme() {
		return $this->_logged_in ? $this->user['theme'] : (ctype_digit(@$_SESSION['theme']) ? $_SESSION['theme'] : 0);
	}

	public function userAvatar() {
		return $this->_logged_in ? (file_exists($this->user['avatar']) ? $this->user['avatar'] : 'theme/default_ave.png') : 'theme/default_ave.png';
	}

	public function mapDir($abs = true) {
		global $sp;
		return $this->_logged_in ? ($abs ? realpath( $sp['maps']  ) : 'maps') . '/u'.$this->user['uid'].'/' : false;
	}

	public function mapDirPlain() {
		return 'maps/u'.$this->user['uid'].'/';
	}

	/*
	 * Change my theme
	 */
	public function changeTheme($desired_theme) {

		// Sanity check
		if (!ctype_digit($desired_theme))
			return false;

		// If we're logged in, change user pref
		if ($this->loggedIn()) {
			$this->_sql->query("update members set theme = '$desired_theme' where id = '".$this->userID()."' limit 1");
		}

		// Otherwise, save it to session
		else {
			$_SESSION['theme'] = $desired_theme;
		}
	}

	/*
	 *
	 */
	public function myDate($format = null, $date = null) {
		return date($format ? $format : DATE_FORMAT, ($date == null ? time() : $date) + ($this->_logged_in ? $this->user['timezone_offset'] * 3600 : 0));
	}

}

/*
 * Get a user id from a username
 */
function username2uid($username) {

	// Avoid doing the same one more than once
	static $cache = array();

	// Gain access to SQL and current user id
	$sql = SQL::Fledging();
	$ui = Ui::Fledging();

	// Safety first
	$username = $sql->prot(trim($username));

	// Ugh
	if ($username == '')
		return false;

	// First off, is this cached?
	if (array_key_exists($username, $cache))
		return $cache[$username];

	// Is it me?
	if ($ui->loggedIn() && $ui->userName() == $username)
		return $ui->userID();

	// No. Search for it. :P
	$get = $sql->query("select m.id from members as m where m.username = '$username' limit 1");

	// Nothing?
	if ($sql->num($get) == 0) {
		$sql->free($get);
		return false;
	}

	// Got it.
	$uid = $sql->fetch_one($get);

	// Free ram
	$sql->free($get);

	// Cache it so we don't repeat ourselves next time
	$cache[$username] = $uid;

	// Return it, finally
	return $uid;
}

/*
 * Get a user name from a user id, while validating the id
 */
function uid2username($userid) {

	// Avoid doing the same one more than once
	static $cache = array();

	// Gain access to SQL and current user id
	$sql = SQL::Fledging();
	$ui = Ui::Fledging();

	// Ugh
	if (!is_numeric($userid) && $userid > 0)
		return false;

	// First off, is this cached?
	if (array_key_exists($userid, $cache))
		return $cache[$userid];

	// Is it me?
	if ($ui->loggedIn() && $ui->userID() == $userid)
		return $ui->userName();

	// No. Search for it. :P
	$get = $sql->query("select m.username from members as m where m.id = '$userid' limit 1");

	// Nothing?
	if ($sql->num($get) == 0) {
		$sql->free($get);
		return false;
	}

	// Got it.
	$username = $sql->fetch_one($get);

	// Free ram
	$sql->free($get);

	// Cache it so we don't repeat ourselves next time
	$cache[$userid] = $username;

	// Return it, finally
	return $username;
}

/*
 * Vanity. Decorate links to user's profiles depending on their status
 */
function userLink($uid, $name) {
	global $entry_point;
	static $site_admins;
	$site_admins = is_array($site_admins) ? $site_admins : (array)unserialize(SITE_ADMINS);
	$name = stringprep($name);
	if (in_array($uid, $site_admins))
		return '<a href="'.$entry_point.'?action=view_profile;u='.$uid.'" class="adminLink">'.$name.'</a>';
		#return '<img src="/images/forum_stars/green.png" alt="" /><a href="'.$entry_point.'?action=view_profile;u='.$uid.'">'.$name.'</a>';
	else
		return '<a href="'.$entry_point.'?action=view_profile;u='.$uid.'">'.$name.'</a>';
}

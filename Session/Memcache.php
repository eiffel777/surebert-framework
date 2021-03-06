<?php
/**
 * Save the sessions on a memcache server, requires memcache server be installed and running on the host and port specified
 * @author visco
 * @version 0.3 01/25/2009 01/25/2009
 * @package sb_Session
 */

class sb_Session_Memcache extends sb_Session{

	/**
	 * Instantiates a memcache session
	 * <code>
	 * #in /private/config/definitions.php
	 * new sb_Session_Memcache('localhost', 11211);
	 * </code>
	 *
	 * @param integer $host The memcache host to connect to
	 * @param integer $port  The port to connect on
	 */
	public function __construct($host, $port){
		
		$session_save_path = "tcp://$host:$port?persistent=1&weight=2&timeout=2&retry_interval=10,  ,tcp://$host:$port  ";
		ini_set('session.save_handler', 'memcache');
		ini_set('session.save_path', $session_save_path);
		session_start();
		
	}
	
}
?>
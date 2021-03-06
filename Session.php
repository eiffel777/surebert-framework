<?php
/**
 * Base class for custom sessions
 * @author visco
 * @version 0.4 01/24/2009 01/24/2009
 * @package sb_Session
 */

class sb_Session{

	public function __construct(){
        session_start();
	}

	/**
	 * Sets a value in the session 
	 * @param $key The key to store it by
	 * @param $val The value to store
	 */
	public function set($key, $val){
		$_SESSION[$key] = $val;
	}
	
	/**
	 * Gets a value from the session
	 * @param $key The key it is stored by
	 * @return * The value stored
	 */
	public function get($key){
		return $_SESSION[$key];
	}
	
}

?>
<?php

/**
 * Extends native PDOStatement class for logging and debugging, when the logging is set on an sb_PDO instance.  You would never access this directly
 * 
 * @author paul.visco@roswellpark.org
 * @version 1.9 09/27/2007 04/01/2009
 * @package sb_PDO
 */

class sb_PDO_Statement_Logger extends PDOStatement {
	
    public $connection;
    
    protected function __construct($connection='') {
    	
      	$this->connection = $connection;
    }
    
    /**
     * This function extends PDOStatement->execute in order to include logging
     *
     * @param unknown_type $arr
     * @return unknown
     */
    public function execute($arr = Array()){
    	$log = "Executing: ".$this->queryString;

    	if(count($arr)>0){
    		
    		foreach($arr as $key=>$val){
    			$log .= "\nBinding Values: ".$key.' = '.$val;
    		}
    		
    	} 
    	
    	$this->connection->write_to_log($log);
    	
    	if(empty($arr)){
    		return parent::execute();
    	} else {
    		return parent::execute($arr);
    	}
    }
    
    public function bindParam($key, &$val, $type=''){
    	$log = 'Binding Parameters: '.$key.'='.$val;
    	if(!empty($type)){
    		$log .= '| Type: '.$type;
		}
    	$this->connection->write_to_log($log);
    	
    	if(!empty($type)){
    		return parent::bindParam($key, $val, $type);
    	}
    	
    	return parent::bindParam($key, $val);
    	
    }
    
     public function bindValue($key, $val, $type=''){
     	$log = 'Binding Value: '.$key.'='.$val;
     	if(!empty($type)){
    		$log .= '| Type: '.$type;
		}
		
    	$this->connection->write_to_log($log);
    	
    	if(!empty($type)){
    		return parent::bindParam($key, $val, $type);
    	}
    	
    	return parent::bindParam($key, $val);
    	
    }
    
}

/**
 * Extends native PDO class for logging and debugging
 * 
 * @author paul.visco@roswellpark.org
 * @version 1.9 09/27/2007 04/01/2009
 * @package sb_PDO
 * 
 */
class sb_PDO_Logger extends sb_PDO_Debugger{
	   	
	/**
	 * An instance of sb_Logger used to do the logging
	 * @var sb_Logger
	 */
	private $logger = null;

    /**
     * Limits the logging to requests from one sbf session ID
     * @var string
     */
    private $sbf_id = '';
	
	/**
	 * Creates am extended PDO object that logs
	 *
	 * @param string $connection The pdo connection string
	 * @param string $user Username if required
	 * @param string $pass Password for connection if required
	 * 
	 * <code>
	 * $db=new sb_PDO_Logger("mysql:dbname=xxx;host=xxx", 'username', 'pass');
	 * $db=new sb_PDO_Logger("sqlite:myfile.db3');
	 * $db->set_logger(App::$logger);
	 * </code>
	 * 
	 */
	function __construct($connection, $user='', $pass=''){
        
        $this->log_str = str_replace(Array(":", ";", "=", "."), "_", $connection);
		parent::__construct($connection, $user, $pass);
		$this->setAttribute(PDO::ATTR_STATEMENT_CLASS, Array('sb_PDO_Statement_Logger', Array($this)));
	}
	
	/**
	 * Set the logger
	 *
	 * @param $logger sb_Logger An instance of sb_Logger or one is created using FileSystem logging
	 */
	public function set_logger($logger=null){
		
		if($logger instanceOf sb_Logger_Base){
			$this->logger = $logger;
			$this->logger->add_log_type($this->log_str);
		} else {
			$this->logger = new sb_Logger_FileSystem(Array($this->log_str));
		}
	}

    /**
     * Only logs for a specific SBF_ID
     * @param string $sbf_id The SBF_ID to log for
     */
    public function log_only_for_SBF_ID($sbf_id){
        $this->sbf_id = $sbf_id;
    }

     /**
     * Additionally Logs the errors
     * {@inheritdoc }
     */
    public function s2o($sql, $params=null, $class_name=''){
        try{
            return parent::s2o($sql, $params, $class_name);
        } catch(Exception $e){

            $trace = $e->getTrace();

            $message = "Error: ".__CLASS__ ." Exception in ".$trace[1]['file']." on line ".$trace[1]['line']." with db message: \n".$e->getMessage();
          
            $this->write_to_log($message);
           
            return Array();
        }
    }
	
	/**
	 * same as normal query, however, it allows logging if log file is set
	 *
	 * @param string $sql
	 * @return object PDO result set
	 */
	public function query($sql){
		
		$this->write_to_log("Querying: ".$sql);
		return parent::query($sql);
	}
	
	/**
	 * Used to issue statements which return no results but rather the number of rows affected
	 *
	 * @param string $sql
	 * @return integer The number of rows affected
	 */
	public function exec($sql){
		$this->write_to_log("Exec: ".$sql);
		$result = parent::exec($sql);
		return $result;
	}
	
	/**
	 * Used to prepare sql statements for value binding
	 *
	 * @param string $sql
	 * @return PDO_Statement A PDO_statment instance
	 */
	public function prepare($sql){
	
		$md5 = md5($sql);
		
		if(isset($this->prepared_sql[$md5])){
			return $this->prepared_sql[$md5];
		}
		
		$this->write_to_log("Preparing: ".$sql);
		$stmt = parent::prepare($sql);
		$this->prepared_sql[$md5] = $stmt;
		return $stmt;
	}
	
	/**
	 * Logs all sql statements to a file, if the log file is specified
	 *
	 * @param string $message The string to log
	 */
	public function write_to_log($message){

		if(is_null($this->logger)){
			$this->set_logger();
		}

        if(empty($this->sbf_id) || $this->sbf_id == Gateway::$cookie['SBF_ID']){
          
            $message = preg_replace("~\t~", " ", $message);
            return $this->logger->{$this->log_str}($message);
        }
	}

}

?>
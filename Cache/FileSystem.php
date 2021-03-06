<?php
/**
 * Stores cached data in the file system
 * 
 * @author visco
 * @version 1.0 01/23/2009 10/09/2009
 * @package sb_Cache
 *
 */
class sb_Cache_FileSystem implements sb_Cache_Base{
	
	/**
	 * The key to store the catalog in
	 * @var string
	 */
	protected $catalog_key = '/sb_Cache_Catalog';


	/**
	 * The file path that the cache is stored in
	 * @var string
	 */
	protected $file_path = '';


	/**
	 * Sets the filepath of the file system cache, defaults to ROOT/private/cache/
	 * 
	 * <code>
	 * $mycache = new sb_Cache_FileSystem();
	 * //store a string in /private/cache/dog/food for 10 seconds
	 * $mycache->store('/dog/food', 'Kibbles and Bits', 10);
	 *
	 * //load the data from the cache
	 * echo $mycache->fetch('/dog/food');
	 *
	 * //create an object to store in the cache
	 * $person = new stdClass();
	 * $person->dname = 'Visco, Paul';
	 *
	 * //store the person in the cache for 100 seconds
	 * $mycache->store('/dog/owner', $person, 100);
	 *
	 * //load the person from the cache
	 * print_r($mycache->fetch('/dog/owner'));
	 * </code>
	 *
	 * @param string $file_path Optional The filepath to store the cache in, must be writable
	 *
	 */
	public function __construct($file_path =''){

		if(empty($file_path)){
			$file_path = ROOT.'/private/cache/';
		}

		$this->set_cache_dir($file_path);
	}
	/**
	 * Stores the cached data in /private/cache filesystem
	 * (non-PHPdoc)
	 * @see trunk/private/framework/sb/sb_Cache#store()
	 */
	public function store($key, $data, $lifetime = 0){
		
		$file_path = $this->get_file_path($key);
		$dir = dirname($file_path);
		
		if(!is_dir($dir)){
            try{
                mkdir($dir, 0777, true);
            } catch (Exception $e){
                throw new Exception('Could create cache directory: '.$file_path." - ".$e->getMessage());
            }
		}

        try{
            $fh = fopen($file_path, 'a+');
        } catch (Exception $e){
            throw new Exception('Could not write to cache: '.$file_path." - ".$e->getMessage());
        }
	    
	    //exclusive lock
	    flock($fh, LOCK_EX); 

	    fseek($fh,0); 
	
	    ftruncate($fh,0); 
    
	    if($lifetime != 0){
	    	$lifetime = time() + $lifetime;
	    }
	    
	    $data = serialize(array($lifetime, $data));
	    
	    if (fwrite($fh, $data)===false){
	    	throw new Exception('Could not write to cache: '.$file_path);
	    }
	    
	    fclose($fh);
	    
	    if($key != $this->catalog_key){
	    	$this->catalog_key_add($key, $lifetime);
	    }
	    return true;
		
	}
	
	/**
	 * Retreeives data from /private/cache
	 * (non-PHPdoc)
	 * @see trunk/private/framework/sb/sb_Cache#store()
	 */
	public function fetch($key){
		
		$file_name = $this->get_file_path($key);
		if (!file_exists($file_name) || !is_readable($file_name)) {
			return false;
		} else {
			$h = fopen($file_name,'r');
			//lock file
			flock($h,LOCK_SH); 
		}
		
		$data = file_get_contents($file_name);
		
		//release lock
		fclose($h); 
		
		$data = @unserialize($data);
	
		//check to see if it expired
		if($data && ($data[0] == 0 || time() <= $data[0])){
			return $data[1];
		} else {
			$this->delete($key);
			return false;
		}
		
		return $data[1];
	}
	
	/**
	 * (non-PHPdoc)
	 * @see trunk/private/framework/sb/sb_Cache#delete()
	 */
	public function delete($key){
		
		$file = $this->get_file_path($key);
		
		if(is_dir($file)){
			$this->clear_dir($file);
			rmdir($file);
		} else if(file_exists($file)){
			return unlink($file);
		} else {
			return false;
		}
	}
	
	/**
	 * Delete all the info in the cache regardless of the key
	 * @return boolean
	 */
	public function clear_all(){
		
		$this->clear_dir($this->file_path.'/sb_Cache');
	}
	
	/**
	 * Clears out the contents of a cache directory
	 * @param $dir
	 * @return boolean
	 */
	protected function clear_dir($dir){
        
		$iterator = new DirectoryIterator($dir);
        foreach ($iterator as $file){

		  if ($file->isDir() && !$file->isDot() && !preg_match("~\.~", $file)) {
             $this->clear_dir($file->getPathname());
		     if(!rmdir($file->getPathname())){
		     	return false;
		     }
		  } else if($file->isFile()){
		    if(!unlink($file->getPathname())){
		    	return false;
		    }
		  }
		}

        return true;
	}
	
	/**
	 * takes the cache key and turns it into a file request, makes directory if required
	 * @param $key
	 * @return string The path of the cache file
	 */
	protected function get_file_path($key){
		return $this->file_path.'/sb_Cache'.$key;
	}
	
	protected function catalog_key_add($key, $lifetime){
		$catalog = $this->fetch($this->catalog_key);
		$catalog = is_array($catalog) ? $catalog : Array();
		$catalog[$key] = ($lifetime == 0) ? $lifetime : $lifetime+time();
		return $this->store($this->catalog_key, $catalog);
	}
	
	protected function cataglog_key_delete(){
		$catalog = $this->fetch($this->catalog_key);
		$catalog = is_array($catalog) ? $catalog : Array();
		if(isset($catalog[$key])){
			unset($catalog[$key]);
		};
		
		return $this->store('/sb_Cache_Catalog', $catalog);
	}

	/**
	 * Sets the file path to cache in
	 * @return string
	 */
	public function set_cache_dir($file_path){
		if(substr($file_path, -1, 1) != '/'){
			$file_path .= '/';
		}

		$this->file_path = $file_path;
	}

	/**
	 * Loads the current catalog
	 * @return Array a list of all keys stored in the cache
	 */
	public function get_keys(){
		$catalog = $this->fetch($this->catalog_key);
		$catalog = is_array($catalog) ? $catalog : Array();
		ksort($catalog);
		return $catalog;
	}
}

?>
<?php
/**
 * Stores data in APC - requires the apc extension is installed
 * @author visco
 * @version 1.0 01/23/2009 05/14/2009
 * @package sb_Cache
 */
class sb_Cache_APC implements sb_Cache_Base{
	
	/**
	 * The key to store the catalog in
	 * @var string
	 */
	private $catalog_key = '/sb_Cache_Catalog';
	
	/**
	 * The namespace for your cache.  By default this is empty, but if you are on a shared memcache server this will keep your values separate
	 * @var string
	 */
	private $namespace = '';
	
	/**
	 * Creates namespace for the data, as the cache may be shared between different apps. 
	 * @param $namespace The namespace required when sharing memcache server.  Must be totall unique, e.g. the name of your app?
	 */
	public function __construct($namespace){
		$this->namespace = $namespace;
	}
	
	/**
	 * Store the cache data in APC
	 * (non-PHPdoc)
	 * @see trunk/private/framework/sb/sb_Cache#store()
	 */
	public function store($key, $data, $lifetime = 0) {
		
		$key = $this->namespace.$key;
		
		$store = apc_store($key, $data, $lifetime);
		if($store && $key != $this->namespace.$this->catalog_key){
			$this->catalog_key_add($key, $lifetime);
		}
		return $store;
	}
	        
	/**
	 * Fetches the cache from APC
	 * (non-PHPdoc)
	 * @see trunk/private/framework/sb/sb_Cache#fetch()
	 */
	public function fetch($key) {
		$key = $this->namespace.$key;
		
		return apc_fetch($key);
	}
	
	/**
	 * Deletes cache data
	 * (non-PHPdoc)
	 * @see trunk/private/framework/sb/sb_Cache#delete()
	 */
	public function delete($key) {
	
		$deleted = false;
		
		$catalog = array_keys($this->get_keys());
		foreach($catalog as $k){
			
			if(substr($k, 0, strlen($key)) == $key){
			
				$delete = apc_delete($this->namespace.$k);
				if($delete){
					$this->catalog_key_delete($k);
					$deleted = true;
				}
			}
			
		}
		
		return $deleted;
	} 
	
	/**
	 * Clears the whole cache
	 * (non-PHPdoc)
	 * @see private/framework/sb/sb_Cache#clear_all()
	 */
	public function clear_all(){
		return apc_clear_cache('user');
	}
	
	/**
	 * Keeps track of the data stored in the cache to make deleting groups of data possible
	 * @param $key
	 * @return boolean If the catalog is stored or not
	 */
	private function catalog_key_add($key, $lifetime){
		
		$catalog = $this->fetch($this->catalog_key);
		$catalog = is_array($catalog) ? $catalog : Array();
		$catalog[$key] = ($lifetime == 0) ? $lifetime : $lifetime+time();
		return $this->store($this->catalog_key, $catalog);
	}
	
	/**
	 * Delete keys from the data catalog
	 * @param $key
	 * @return boolean If the catalog is stored or not
	 */
	private function catalog_key_delete($key){
		
		$catalog = $this->fetch($this->catalog_key);
		$catalog = is_array($catalog) ? $catalog : Array();
		if(isset($catalog[$key])){
			unset($catalog[$key]);
		};
		return $this->store($this->catalog_key, $catalog);
	}
	
	/**
	 * Loads the current catalog
	 * @return Array a list of all keys stored in the cache
	 */
	public function get_keys(){
	
		$catalog = $this->fetch($this->catalog_key);
		$catalog = is_array($catalog) ? $catalog : Array();
	
		$arr = Array();
		foreach($catalog as $k=>$v){
			$arr[preg_replace("~^".$this->namespace."~", '', $k)] = $v;
		}
		ksort($arr);
		return $arr;
	}
	
}
?>
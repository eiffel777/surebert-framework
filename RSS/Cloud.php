<?php
/**
 * Used to create an sb_RSSFeed cloud for the channel
 *
 * They look like this <cloud domain="rpc.sys.com" port="80" path="/RPC2" registerProcedure="myCloud.rssPleaseNotify" protocol="xml-rpc" />
 * 
 * more info http://cyber.law.harvard.edu/rss/rss.html#ltcloudgtSubelementOfLtchannelgt
 * 
 * @author Paul Visco
 * @package sb_RSS
 *
 */
class sb_RSS_Cloud{
	
	public $domain;
	public $port;
	public $path;
	public $registerProcedure;
	public $protocol;
	
	/**
	 * Used to create a sb_RSS_Cloud object
	 *
	 * @param string $domain
	 * @param integer $port
	 * @param string $path
	 * @param string $registerProcedure
	 * @param string $protocol
	 * @return object
	 */
	public function __construct($domain, $port, $path, $registerProcedure, $protocol){
		
        $this->domain = $domain;
        $this->port = $port;
        $this->path = $path;
        $this->registerProcedure = $registerProcedure;
        $this->protocol = $protocol;
        
        return true;
	}
}

?>
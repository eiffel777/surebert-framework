<?php
/**
 * A data object with the details and records of a paged MySQL query
 *
 * @author Tony Cashaw
 * @version 1.0 2008-02-01
 * @package sb_PDO
 */
class sb_PDO_RecordPage {

	/**
	 * The page number that this object is set to
	 *
	 * @var integer unsigned
	 */
	public $current_page;

	/**
	 * The total number of pages possible
	 *
	 * @var integer
	 */
	public $page_count = -1;

	/**
	 * The number of total records in the super set of data
	 *
	 * @var integer
	 */
	public $record_count;

	/**
	 *  This array is the requested subset of data.
	 *  An array of of stdClass objects that reflect the rows of the statement.
	 *
	 * @var array
	 */
	public $rows = array();

    public function prev_page(){
		if($this->page_null == 1){return 0;}
		return ($this->current_page <= 1)?1:$this->current_page - 1;
	}
	
	public function next_page(){
		if($this->page_null == 1){return 0;}
		return ($this->current_page >= $this->page_count)?$this->page_count:$this->current_page + 1;
	}

}
?>

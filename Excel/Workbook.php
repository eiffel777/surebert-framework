<?php

/**
 * Used to create excel xml documents
 * @author Paul Visco paul.visco@roswellpark.org
 * @package sb_Excel
 */
class sb_Excel_Workbook extends DOMDocument{

	/**
	 * The workbook Element
	 * @var DOMElement
	 */
	protected $workbook;

	/**
	 * The current active worksheet
	 * @var DOMElement
	 */
	protected $active_worksheet;

	/**
	 * An array of all of the active worksheets
	 * @todo replace with xpath lookup
	 * @var array
	 */
	public $worksheets = Array();

	/**
	 * Determines if types are auto converted
	 * @var boolean
	 */
	public $auto_convert_types = false;

	/**
	 * Creates a new Excel Workbook
	 * @param string $title The first worksheet's name
	 * @param boolean $auto_convert_types Should types be auto converted
	 * @param string $encoding The default encoding to use
	 * <code>
	 * $workbook = new sb_Excel_Workbook("SMy worksheet");
	 * $cell1 = $workbook->set_cell_by_alpha_index('A3', 'xxx');
	 * $cell2 = $workbook->set_cell_by_alpha_index('A3', 'yyy');
	 * $style = $workbook->add_style('paul', Array(
	 * 'Color' => '#FFACAC',
	 * 'Bold' => 1
	 * ));
	 * $row = $workbook->set_row(4, Array('a', 'b', 'c', 'd', 'e', 'f'));
	 * echo $workbook->output_with_headers('somefile');
	 * </code>
	 */
	public function  __construct($title = 'Table1', $auto_convert_types=false, $encoding='UTF-8') {

		parent::__construct('1.0', $encoding);
		$this->auto_convert_types = $auto_convert_types? true : false;

		$this->workbook = $this->appendChild($this->createElement('Workbook'));
		$this->workbook->appendChild($this->create_attribute('xmlns', 'urn:schemas-microsoft-com:office:spreadsheet'));
		$this->workbook->appendChild($this->create_attribute('xmlns:o', 'urn:schemas-microsoft-com:office:office'));
		$this->workbook->appendChild($this->create_attribute('xmlns:x', 'urn:schemas-microsoft-com:office:excel'));
		$this->workbook->appendChild($this->create_attribute('xmlns:ss', 'urn:schemas-microsoft-com:office:spreadsheet'));
		$this->workbook->appendChild($this->create_attribute('xmlns:html', 'http://www.w3.org/TR/REC-html40'));

		$this->styles = $this->workbook->appendChild($this->createElement('ss:Styles'));
		$this->active_worksheet = $this->add_worksheet($title);

		$this->xpath = new DOMXPath($this);
		//this does nothing because it is broken in PHP when used on class that extends DOMDocument
		$this->xpath->registerNamespace('ss', "urn:schemas-microsoft-com:office:spreadsheet");

	}

	/**
	 * Creates a new style node and adds it to the styles section
	 * @param string $name The name of the style, used when assigning it
	 * @param array $properties http://msdn.microsoft.com/en-us/library/aa140066%28office.10%29.aspx#odc_xmlss_ss:font  e.g. Array('Color' => '#FF0000', 'Bold' => 1);
	 * @return DOMElement The Style tag itself
	 */
	public function add_style($name, $properties){
		$style = $this->createElement('ss:Style');
		$style->setAttribute('ss:ID', $name);
		$this->styles->appendChild($style);
		$font = $this->createElement('ss:Font');
		$style->appendChild($font);
		foreach($properties as $prop=>$val){
			$font->setAttribute('ss:'.$prop, $val);
		}
		return $style;
	}

	/**
	 * Sets the style for an element Row, Cell
	 * @param DOMElement $item The item to assign the style for
	 * @param DOMElement $style The style to assign to the item
	 */
	public function set_style(DOMElement $item, DOMElement $style){
		$item->appendChild($this->create_attribute('ss:StyleID', $style->getAttribute('ss:ID')));
	}

	/**
	 * Adds a new worksheet to the workbook
	 * @param string $title
	 * @return DOMElement The worksheet itself
	 */
	public function add_worksheet($title){
		$worksheet = $this->workbook->appendChild($this->createElement('Worksheet'));
		$title = preg_replace("/[\\\|:|\/|\?|\*|\[|\]]/", "", $title);
		$title = substr($title, 0, 31);
		$worksheet->appendChild($this->create_attribute('ss:Name', $title));
		$worksheet->table = $worksheet->appendChild($this->createElement('Table'));
		$this->worksheets[md5($title)] = $worksheet;
		return $worksheet;
	}

	/**
	 * Sets the active worksheet
	 * @param string $worksheet The name of the worksheet or a reference to the DOMElement itself
	 * @return DOMElement The active worksheet
	 */
	public function set_active_worksheet($worksheet){
		if(is_string($worksheet)){
			$worksheet = $this->worksheets[md5($worksheet)];
		}
		$this->active_worksheet = $worksheet;
		return $worksheet;

	}

	 /**
     * Creates and returns a DOM node attribute for appending
     *
     * @param string $name The name of the attribute
     * @param unknown_type $value The value of the attribute
     * @return object attribute node
     */
	protected function create_attribute($name, $value){
		$attribute = $this->createAttribute($name);
		$val = $this->createTextNode($value);
		$attribute->appendChild($val);
		return $attribute;
	}

	/**
	 * Sets an entire row of data
	 * @param integer $row_index The 1 based row index to use
	 * @param array $values_arr An array of values
	 * @param string $type ss:Type attribute
	 * @return DOMElement The row
	 */
	public function set_row($row_index, $values_arr, $type=null){
		$cell = null;
		foreach($values_arr as $col_index=>$val){
			$cell = $this->set_cell($col_index+1, $row_index, $val, $type);
		}

		if($cell){
			return $cell->parentNode;
		}
		return false;
	}

	/**
	 * Sets a cell by 1 based column and row index
	 * @param integer $col_index 1based column index
	 * @param integer $row_index 1 based row index
	 * @param mixed $val The value to insert
	 * @param string $type The ss:Type attribute
	 * @return DOMElement the cell itself
	 */
	public function set_cell($col_index, $row_index, $val, $type=null){

			$result = $this->xpath->query("//Row[@Index='".$row_index."']/Cell[@Index='".$col_index."']/Data", $this);
			$data = $result->item(0);
			if($data){
				$data->setAttribute('ss:Type', $type);
				$data->firstChild->nodeValue = $val;
			} else {

			//echo '<br />adding ('.$col_index.', '.$row_index.') '.$val;
			$result = $this->xpath->query("//Row[@Index='".$row_index."']");

			$row = $result->item(0);
		
			if(!$row){
				//echo '<br />creating (row '.$row_index.') '.$val;
				$row = $this->createElement('Row');
				$this->active_worksheet->table->appendChild($row);
				$row->appendChild($this->create_attribute('Index', $row_index));
			} else {
				//echo '<br />exists (row '.$row_index.') '.$val;
			}

			$result = $this->xpath->query("//Row[@Index='".$row_index."']/Cell[@Index='".$col_index."']", $row);
			$cell = $result->item(0);
			if(!$cell){
				//echo '<br />creating (cell '.$col_index.') '.$val;
				$cell = $this->createElement('Cell');
				$row->appendChild($cell);
				$cell->appendChild($this->create_attribute('Index', $col_index));
				$data = $this->createElement('Data');
				$cell->appendChild($data);
				$data->appendChild($this->create_attribute('ss:Type', $type));
				$data->appendChild($this->createTextNode($val));
			} else {
				//echo '<br />exists (cell '.$col_index.') '.$val;
				$data = $cell->getElementsByTagName('Data')->item(0);
				$data->setAttribute('ss:Type', $type);
				$data->firstChild->nodeValue = $val;
			}

			}
		return $data->parentNode;

	}

	/**
	 * Sets a cells value by alpha index e.g. A4
	 * @param string $alpha_index A4
	 * @param mixed $val the value to set the cell to
	 * @param string $type The ss:Type attribute
	 * @return DomElement The cell itself
	 */
	public function set_cell_by_alpha_index($alpha_index, $val, $type=null){
		$pos = $this->alpha_index_to_numeric($alpha_index);
		return $this->set_cell($pos[0], $pos[1], $val, $type);

	}
	
	/**
	 * Used to generate the xml with output headers
	 * @param string $filename Name of excel file to generate (...xls) default worksheet.xls
	 */
	public function output_with_headers($filename='worksheet') {
		$filename = preg_replace('/[^aA-zZ0-9\_\-]/', '', $filename);

		header("Content-Type: application/msexcel; charset=" . $this->encoding);
		header("Content-Disposition: inline; filename=\"" . $filename . ".xlsx\"");
		echo $this->__toString();
	}

	/**
	 * Converts the xml to a string
	 * @return string
	 */
	public function  __toString() {
		//stupid kludge to deal with DOMXPath bug that prevented the queries from working on the DOMDocument with namespaced elements
		$nodes = $this->xpath->query("//*[@Index]");
		
		for($x=0;$x<$nodes->length;$x++){
			$node = $nodes->item($x);
			$node->setAttribute('ss:Index', $node->getAttribute('Index'));
			$node->removeAttribute('Index');
		}

		return $this->saveXML();
	}
	
	/**
	 * Determines value type
	 * @param string $val
	 * @return string
	 */
	protected function get_value_type($val){
		$type = 'String';
		if ($this->auto_convert_types === true && is_numeric($v)) {
			$type = 'Number';
		}

		return $type;
	}

	/**
	 * Converts Alpha column data to numeric indexes
	 * @param string $alpha_index e.g. A1 or AA4
	 * @return array (col, row)
	 */
	protected function alpha_index_to_numeric($alpha_index){
		preg_match("~([A-Z]+)(\d)+~", $alpha_index, $match);
		$letters = range('A', 'Z');
		$letters = array_flip($letters);
		$col_index = 0;
		$strlen = strlen($match[1]);

		if($strlen== 1){
			$col_index += ($letters[$match[1]]+1);
		} else if($strlen > 1){
			$arr = str_split($match[1]);
			$last = array_pop($arr);
			foreach($arr as $letter){
				$col_index += ($letters[$letter]+1)*26;
			}
			$col_index += ($letters[$last]+1);
		}


		return Array($col_index, $match[2]);
	}
}
?>
<?

/**
 * Base class to create graphs using php  Requires sb_Math_RangeMapper
 * @author Greg Dean
 * @version 1.0  2/21/2010
 * @package sb_Graph
 * 
 */
class sb_Graph_Base {

    

    /**
     * Determines if the hinted y axis is shown
     *
     * @var boolean
     */
    public $y_axis_hints = 1;

    /**
     * Determines if the hinted x axis is shown
     *
     * @var boolean
     */
    public $x_axis_hints = 1;

    /**
     * The offset for each axis labels
     *
     * @var integer
     */
    public $axis_offset = 30;

    /**
     * The number of decimal places to use for rounding
     * @var integer
     */
    public $precision = 0;

    /**
     * The image resource that is being drawn
     *
     * @var GD resource
     */
    public $im;

    /**
     * The values derived from the from last argument of the constructor
     *
     * @var Array
     */
    private $values = Array();

    /**
     * Create the blank graph image
     *
     * @param integer $width  The total width of the graph in pixels
     * @param integer $height The total height of the graph in pixels
     * @param string $values A line resturn delimted, comma-delimited value pair decribing the label and value for each point plotted. 
     * <code>
     * //set the graph width and height plus values and labels
     * //set the graph width and height plus values and labels
     * $chart = new sb_Graph_Point(600, 300,  Array(
     * 	'A' => 1.27,
     * 	'B' => 1.45,
     * 	'C' => 1.20,
     * 	'D' => 1.55,
     * 	'E' => null, //graphs nothing for that column but still adds the column
     * 	'F' => 2.55,
     * 	'G' => 1.45,
     * 	'H' => 1.35,
     * 	'I' => 1.33,
     * 	'J' => 0.98
     * ));
     *
     * //these all have defaults, optional
     * $chart->set_y_axis_label_increment(0.5);
     * $chart->connect_points = 1;
     * $chart->x_axis_hints = 1;
     * $chart->y_axis_hints = 1;
     *
     * //setting the colors, optional
     * $chart->set_background_color(25, 45, 65);
     * $chart->set_text_color(255, 255, 255);
     * $chart->set_axis_color(95, 95, 95);
     * $chart->set_point_color(223, 65, 15);
     * $chart->set_line_color(145, 45, 45);
     *
     * //add additional horizontal lines, optional
     * $chart->add_horizontal_line(1.54, 'red', 'average');
     * $chart->add_horizontal_line(2.0, 'purple', 'otherLine');
     * //draw the chart
     * $chart->draw();
     * header("Content-type: image/gif");
     * // return the image using imagepng or imagejpeg.
     * imagegif($chart->output());
     * </code>
     */
    public function __construct($width, $height, $values) {
        $this->width = $width;
        $this->height = $height;

        $this->graph_width = $width - 130;
        $this->graph_height = $height - $this->axis_offset;

        $this->im = imagecreatetruecolor($this->width, $this->height);
        $this->allocate_colors();
        $this->set_values($values);
        $this->create_background();
    }

    /**
     * Sets the spacing increment inteval for the y axis legend
     *
     * @param float $increment
     */
    public function set_y_axis_label_increment($increment=''){
        if (is_numeric($increment)) {
            $this->y_axis_legend_increment = $increment;
        }
    }

    /**
     * Sets the point color in rgb format
     *
     * @param integer $r The red value 0-255
     * @param integer $g The green value 0-255
     * @param integer $b The blue value 0-255
     */
    public function set_point_color($r, $g, $b){
        $this->ink['point'] = imagecolorallocate($this->im, $r, $g, $b);
    }

    /**
     * Sets the line color in rgb format
     *
     * @param integer $r The red value 0-255
     * @param integer $g The green value 0-255
     * @param integer $b The blue value 0-255
     */
    public function set_line_color($r, $g, $b){
        $this->ink['line'] = imagecolorallocate($this->im, $r, $g, $b);
    }

    /**
     * Sets the text color in rgb format - labels
     *
     * @param integer $r The red value 0-255
     * @param integer $g The green value 0-255
     * @param integer $b The blue value 0-255
     */
    public function set_text_color($r, $g, $b){
        $this->ink['text'] = imagecolorallocate($this->im, $r, $g, $b);
    }

    /**
     * Sets the axis color in rgb format - axis
     *
     * @param integer $r The red value 0-255
     * @param integer $g The green value 0-255
     * @param integer $b The blue value 0-255
     */
    public function set_axis_color($r, $g, $b){
        $this->ink['axis'] = imagecolorallocate($this->im, $r, $g, $b);
    }

    public function set_background_color($r, $g, $b){
        $this->ink['background'] = imagecolorallocate($this->im, $r, $g, $b);
        imagefilledrectangle($this->im, 0, 0, $this->width, $this->height, $this->ink['background']);
    }
    
    /**
     * Sets the bar color in rgb format
     *
     * @param integer $r The red value 0-255
     * @param integer $g The green value 0-255
     * @param integer $b The blue value 0-255
     */
    public function set_bar_color($r, $g, $b){
        $this->ink['bar'] = imagecolorallocate($this->im, $r, $g, $b);
    }

    /**
     * Create the background image
     *
     */
    private function create_background(){
        imagefilledrectangle($this->im, 0, 0, $this->width, $this->height, $this->ink['black']);
    }

    /**
     * Allocates the default color used
     *
     */
    private function allocate_colors(){

        $this->ink['red'] = imagecolorallocate($this->im, 138, 0, 22);
        $this->ink['orange'] = imagecolorallocate($this->im, 0xd2, 0x8a, 0x00);
        $this->ink['yellow'] = imagecolorallocate($this->im, 0xff, 0xff, 0x00);
        $this->ink['green'] = imagecolorallocate($this->im, 0x00, 0xff, 0x00);
        $this->ink['blue'] = imagecolorallocate($this->im, 0x00, 0x00, 0xff);

        $this->ink['purple'] = imagecolorallocate($this->im, 0x70, 0x70, 0xf9);
        $this->ink['white'] = imagecolorallocate($this->im, 0xff, 0xff, 0xff);
        $this->ink['black'] = imagecolorallocate($this->im, 0x00, 0x00, 0x00);
        $this->ink['gray'] = imagecolorallocate($this->im, 0xaf, 0xaf, 0xaf);

        $this->ink['axis'] = imagecolorallocate($this->im, 95, 95, 95);

        $this->ink['line'] = imagecolorallocate($this->im, 0xff, 0xff, 0x00);
        $this->ink['background'] = imagecolorallocate($this->im, 0x00, 0x00, 0x00);
        $this->ink['text'] = imagecolorallocate($this->im, 0xff, 0xff, 0xff);
        $this->ink['point'] = imagecolorallocate($this->im, 0xff, 0xff, 0xff);
        $this->ink['bar'] = imagecolorallocate($this->im, 0xff, 0xff, 0xff);
    }

    /**
     * Converts the range from point to pixel value
     *
     * @param integer $value The value to convert
     * @return integer The number as converted into the pixel range on the graph
     */
    private function map_y_value($value) {

        $rangeMapper = new sb_Math_RangeMapper(Array(30, $this->graph_height), Array($this->min, $this->max));
        return $rangeMapper->convert($value);
    }

    /**
     * Converts the values into usable data for the drawing of the graph
     *
     * @param array $values
     */
    private function set_values($values) {

        $numbers = Array();
        foreach ($values as $key => $val) {
            $value = new stdClass();
            $value->label = trim($key);

            if(!is_numeric($val)){
                $val = null;
            }

            $value->value = $val;
            $this->values[] = $value;
            $numbers[] = $val;
        }
        $min_max = Array();
        foreach($numbers as $number){
            if(!is_null($number)){
                array_push($min_max, $number);
            }
        }
        //$this->min = min($min_max);
        $this->min = 0;
        
        if(empty($min_max)){
            $this->max = 100;
        }
        else{
            $this->max = max($min_max);
        }
        

        $this->total_values = count($numbers);

        $separation_dist = (($this->graph_width - 40) / $this->total_values);
        $i = 0;

        $this->points = Array();

        foreach ($this->values as $value) {
            $point = new stdClass();
            $point->x = ($i * $separation_dist) + $this->axis_offset;
            $point->y = $this->plot_value($value->value);
            $point->label = $value->label;
            $point->value = $value->value;
            $this->points[] = $point;
            $i++;
        }

        return $this->points;
    }

    /**
     * Draws the y axis on the graph at each point in a dashed line fashion.  This is totally optional and only happens if $this->draw_y_axis ==1
     *
     */
    protected function draw_y_axis() {

        $min = round($this->min, $this->precision);
        $max = round($this->max, $this->precision);

        if (!isset($this->y_axis_legend_increment)) {
            $increment = round(($max - $min) / $this->total_values, $this->precision);
        } 
        else {
            $increment = $this->y_axis_legend_increment;
        }

        if ($increment == 0) {
            $increment = 1;
        }
        
        for($label = $min; $label <= $max + $increment; $label+=$increment){
            $px_position = $this->plot_value($label);
            if($this->x_axis_hints == 1){
                imageline($this->im, 30, $px_position+1, $this->graph_width, $px_position+1, $this->ink['axis']);
            } 
            imagestring($this->im, 3, 10, $px_position - 5, $label, $this->ink['text']);
        }
    }

    /**
     * Converts points on the graph to pixels
     *
     * @param integer $y
     * @return integer The value in pixels
     */
    private function plot_value($y) {
        $rangeMapper = new sb_Math_RangeMapper(Array($this->axis_offset, $this->graph_height - $this->axis_offset), Array($this->max, $this->min));
        return $rangeMapper->convert($y);
    }

    /**
     * Add a horizontal line
     *
     * @param integer $y The y value
     * @param string $color red, orange, yellow, green, blue, purple
     * @param string $label The line label
     */
    public function add_horizontal_line($y, $color='red', $label='') {

        if (!array_key_exists($color, $this->ink)) {
            throw(new Exception("Ink color must be in " . implode(",", array_keys($this->ink))));
        }

        $y = $this->plot_value($y);
        imageline($this->im, 30, $y, $this->graph_width, $y, $this->ink[$color]);

        imagestring($this->im, 3, $this->graph_width+5 , $y-7, $label, $this->ink[$color]);
    }

    /**
     * Output the graph image resouce for use with imagegif, imagepng, imagejpg
     *
     * @return unknown
     */
    public function output() {
        return $this->im;
    }

}

?>
<?php

/**
 * Used to plot simple point and line graphs.  Requires sb_Math_RangeMapper
 * @author Paul Visco
 * @version 1.6  11/19/07
 * @package sb_Graph
 * 
 */
class sb_Graph_Point extends sb_Graph_Base {

    /**
     * Determines if plotted points are connected by a line
     *
     * @var boolean
     */
    public $connect_points = 1;

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
        parent::__construct($width, $height, $values);
    }

    /**
     * Connect the points on a graph
     *
     */
    private function connect_points() {
        imagesetthickness($this->im, '2');
        foreach ($this->points as $point) {
            if(is_null($point->value)){
                $last_x = $point->x;
                $last_y = $point->y;
                $last_val = $point->value;
                continue;
            }

            if(isset($last_x) && (isset($last_val) && !is_null($last_val))){
                //add axis line
                imageline($this->im, $last_x, $last_y, $point->x, $point->y, $this->ink['line']);
            }
            $last_val = $point->value;
            $last_x = $point->x;
            $last_y = $point->y;
        }
    }

    /**
     * Draw the basic graph and plot the points
     *
     */
    public function draw() {
        $this->draw_y_axis();

        if ($this->connect_points == 1) {
            $this->connect_points();
        }



        foreach ($this->points as $point) {
            imagesetthickness($this->im, '1');
            imageline($this->im, 30, 0, 30, $this->graph_height - 30, $this->ink['axis']);

            if ($this->y_axis_hints == 1) {
                imagedashedline($this->im, $point->x, $this->height, $point->x, 0, $this->ink['axis']);
            } 
            else {
                //add axis line
                imageline($this->im, $point->x + 20, $this->graph_height - 30, $point->x + 20, $this->graph_height - 20, $this->ink['axis']);
            }

            //add axis label
            imagestring($this->im, 3, $point->x, $this->graph_height - 20, $point->label, $this->ink['text']);

            //don't plot actual point if it is null
            if (is_null($point->value)) {
                continue;
            }

            //plot point
            imagefilledellipse($this->im, $point->x, $point->y, 7, 7, $this->ink['point']);

            //add point label
            if ($point->y <= 5) {
                $posy = $point->y + 5;
            } 
            else if ($point->y >= $this->graph_height - 5) {
                $posy = $point->y - 20;
            } 
            else {
                $posy = $point->y - 15;
            }

            imagestring($this->im, 3, $point->x + 10, $posy, $point->value, $this->ink['point']);
        }
    }
}

?>
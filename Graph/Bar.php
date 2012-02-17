<?php

/**
 * Used to draw bar graphs
 * @author Greg Dean
 * @version 1.0 07/06/05
 * @package sb_Graph
 *
 */
/*
  $chunkChart = new sb_Graph_Bar3D(240, 130);
  $chunkChart->title ="comments on e:strip per 24 hrs";
  $chunkChart->padding =5;
  //$chunkChart->setColors("#BBC086", "#AEB55C", "#949D2F", "#FFFFFF", "#757E10");
  $chunkChart->setColors("#c08686", "#b55c5c", "#9d2f2f", "#FFFFFF", "#7e1010");

  $chunkChart->values =Array(8, 17, 5, 4, 9, 15);
  $chunkChart->draw();

  header("Content-type: image/gif");
  // return the image using imagepng or imagejpeg.
  imagegif($chunkChart->graph);
 */
class sb_Graph_Bar {

    public $values = array();
    public $graph; //the graph image
    public $width = 200;
    public $height = 100;
    public $padding = 15;
    public $colors;
    public $title = "";
    public $x_axis_hints = 0;
    public $y_axis_hints = 0;
    public $x_axis_increment = 5;
    public $axis_offset = 20;

    public function __construct($width=200, $height=100, $padding=1) {

        $this->width = $width;
        $this->height = $height;
        $this->padding = 1;
        $this->colors = new stdClass();
        
        $this->graph = imagecreate($width, $height);
    }

    public function hexrgb($hexstr) {
        $int = hexdec($hexstr);

        return array(
            "r" => 0xFF & ($int >> 0x10),
            "g" => 0xFF & ($int >> 0x8),
            "b" => 0xFF & $int
        );
    }

    public function setColors($front, $top, $right, $background, $text="#000000") {


        $front = $this->hexrgb($front);
        $top = $this->hexrgb($top);
        $right = $this->hexrgb($right);
        $background = $this->hexrgb($background);
        $text = $this->hexrgb($text);

        $this->colors->front = imagecolorallocate($this->graph, $front['r'], $front['g'], $front['b']);
        $this->colors->top = imagecolorallocate($this->graph, $top['r'], $top['g'], $top['b']);
        $this->colors->right = imagecolorallocate($this->graph, $right['r'], $right['g'], $right['b']);
        $this->colors->background = imagecolorallocate($this->graph, $background['r'], $background['g'], $background['b']);
        $this->colors->axis = imagecolorallocate ($this->graph,95, 95, 95);

        $this->colors->text = imagecolorallocate($this->graph, $text['r'], $text['g'], $text['b']);
    }

    public function draw_x_axis_hints(){        
        $min = round($this->min);
        $max = round($this->max);

        if(!isset($this->x_axis_increment)){
            $increment = round(($max-$min)/$this->total_values, 2);
        } else {
            $increment = $this->x_axis_increment;
        }

        if($increment == 0){
            $increment = 1;
        }

        for($label=$min;$label<=$max+$increment;$label+=$increment){
            $px_position = $this->plot_value($label);
            if($this->x_axis_hints == 1){
                imageline($this->graph, 30, $px_position, $this->width, $px_position,  $this->colors->text);
            }
            imagestring($this->graph, 3, 10, $px_position-4, $label, $this->colors->text);
        }
    }
    
    /**
     * Converts points on the graph to pixels
     *
     * @param integer $y
     * @return integer The value in pixels
     */
    private function plot_value($y){
            $rangeMapper = new sb_Math_RangeMapper(Array($this->axis_offset, $this->height-$this->axis_offset), Array($this->max, $this->min));
            return $rangeMapper->convert($y);
    }
    /**
     * Converts the values into usable data for the drawing of the graph
     *
     * @param array $values
     */
    private function set_values($values){
        $numbers = Array();
        $min_max = Array();
        $i=0;
        $this->maxv = 0;

        foreach($values as $key=>$val){
            $value = new stdClass();
            $value->label = trim($key);

            if(!is_numeric($val)){$val = null;}

            $value->value = $val;
            $this->values[] = $value;
            $numbers[] = $val;

        }
        
        foreach($numbers as $number){
            if(!is_null($number)){
                array_push($min_max, $number);
            }
        }
        
        $columns = count($this->values);
        $column_width = floor($this->width / $columns);
        $this->min = min($min_max);
        $this->max = max($min_max);
        $this->max_height = $this->height - $column_width;        
        $this->total_values = count($numbers);
        
        foreach($this->values as $value){
            $this->maxv = max($value->value, $this->maxv);
        }
        
        foreach($this->values as $value){
            
            if ($value->value == 0) {
                $column_height = 0;
            } 
            else {
                $column_height = ($this->max_height / 100) * (( $value->value / $this->maxv) * 100);
            }
            $point = new stdClass();
            $point->x1 = $i * $column_width + 4;
            $point->y1 = $this->height - $column_height - 20;
            $point->x2 = (($i + 1) * $column_width) - $this->padding;
            $point->y2 = $this->height - 20;
            $point->label = $value->label;
            $point->value = $value->value;
            $this->points[] = $point;
            $i++;
        }

        return $this->points;
    }
    
    public function draw($values) {
        imagefilledrectangle($this->graph, 0, 0, $this->width, $this->height, $this->colors->background);
        
        //Draw axes
        imageline($this->graph, 0, 0, 0, $this->height-20, $this->colors->axis );
        imageline($this->graph, 0, $this->height-20, $this->width, $this->height-20,  $this->colors->axis );

        // write the title at the top left
        imagestring($this->graph, 2, 0, 0, $this->title, $this->colors->text);
        
        $this->set_values($values);
        $this->draw_x_axis_hints();
        
        foreach($this->points as $point){
            imagefilledrectangle($this->graph, $point->x1, $point->y1, $point->x2, $point->y2, $this->colors->front);
        }
    }

}

?>
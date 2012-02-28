<?php

/**
 * Used to draw bar graphs
 * @author Greg Dean
 * @version 1.0 07/06/05
 * @package sb_Graph
 *
 */

class sb_Graph_Bar extends sb_Graph_Base{

    public function __construct($width=200, $height=100, $values=array()) {
        parent::__construct($width, $height, $values);
    }
    
    public function draw() {
        $this->draw_y_axis();
        imageline($this->im, 30, 0, 30, $this->graph_height-30, $this->ink['axis'] );
        
        foreach($this->points as $point){
            if($this->y_axis_hints == 1){
                imagedashedline($this->im, $point->x, $this->height, $point->x, 0, $this->ink['axis'] );
            } 
            else {			
                imageline($this->im, $point->x+20, $this->graph_height-29, $point->x+20, $this->graph_height-20, $this->ink['axis'] );
            }
            
            imagestring($this->im, 3, $point->x, $this->graph_height-20, $point->label, $this->ink['text']);

            imagefilledrectangle($this->im, $point->x, $point->y, $point->x+10, $this->graph_height-30, $this->ink['bar']);
        }
    }

}

?>
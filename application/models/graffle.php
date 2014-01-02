<?php

class Graffle extends Model {
	
	private $shapes = array();

	public function __construct()
	{
		$this->shapes = array (
			'ellipse' => array(190, 100, 30, 20),
            'rect' => array(290, 80, 60, 40, 10),
            'circle' => array(450, 200, 20, 80)		
        );
	}
	
	public function objects()
	{
		$return ['shapes'] = $this->shapes;		

		$return ['script'] = '
		    r = Raphael("holder", 640, 480), 
		    connections = [],
		    shapes = [  r.ellipse(190, 100, 30, 20),
		                r.rect(290, 80, 60, 40, 10),
		                r.rect(290, 180, 60, 40, 2),
		                r.ellipse(450, 100, 20, 20),
		                r.circle(450, 200, 20, 80),
		                r.circle(190, 300, 40, 80)
		            ];

		    for (var i = 0, ii = shapes.length; i < ii; i++) {
		        var color = Raphael.getColor();
		        shapes[i].attr({fill: color, stroke: color, "fill-opacity": 0, "stroke-width": 2, cursor: "move"});
		        shapes[i].drag(
		        	function (dx, dy) {
				        var att = this.type == "rect" ? {x: this.ox + dx, y: this.oy + dy} : {cx: this.ox + dx, cy: this.oy + dy};
				        this.attr(att);
				        for (var i = connections.length; i--;) {
				            r.connection(connections[i]);
				        }
				        r.safari();
				    }, 
				    function () {
				        this.ox = this.type == "rect" ? this.attr("x") : this.attr("cx");
				        this.oy = this.type == "rect" ? this.attr("y") : this.attr("cy");
				        this.animate({"fill-opacity": .2}, 500);
				    }, 
				    function () {
        				this.animate({"fill-opacity": 0}, 500);
    				}	 
				);
		    }

		    connections.push(r.connection(shapes[0], shapes[1], "#bbb"));
		    connections.push(r.connection(shapes[1], shapes[2], "#bbb", "#bbb|5"));
		    connections.push(r.connection(shapes[1], shapes[3], "#000", "#bbb|6"));
		    connections.push(r.connection(shapes[1], shapes[4], "#000", "#bbb|1"));
		    connections.push(r.connection(shapes[1], shapes[5], "#000", "#bbb|2"));
   		';

		return $return;
	}


	public function create($properties) 
	{	
	    $_shapes = array();
	    $i = 20;
	    foreach ($properties['shapes'] as $value) 
	    {
	    	list($x, $y, $w, $h) = $this->shapes[$value];
	    	
	    	$arr = array( $x + $i, $y + $i, $w + $i, $h + $i);

	    	$_shapes['shapes'][] = $value.'( '. join(' ,', $arr ). ' )';

	    	$i = $i + 20;
	    }
	    
	    $connections = array();
	    $i = 0;
	    foreach ($properties['attach'] as $value) 
	    {
	    	$connections [] = 'connections.push(r.connection(shapes['.$value.'], shapes['.$i.'], "#bbb", "#bbb|"))';
			$i++;
	    }

		$return ['script'] = '
			r = Raphael("holder", 640, 640), 
		    connections = [],
		    shapes = [r.'.join(', r.', $_shapes['shapes']).'];

		    for (var i = 0; i < shapes.length; i++) {
		        var color = Raphael.getColor();
		        shapes[i].attr({fill: color, stroke: color, "fill-opacity": 0, "stroke-width": 2, cursor: "move"});
			    shapes[i].drag(
		        	function (dx, dy) {
				        var att = this.type == "rect" ? {x: this.ox + dx, y: this.oy + dy} : {cx: this.ox + dx, cy: this.oy + dy};
				        this.attr(att);
				        for (var i = connections.length; i--;) {
				            r.connection(connections[i]);
				        }
				        r.safari();
				    }, 
				    function () {
				        this.ox = this.type == "rect" ? this.attr("x") : this.attr("cx");
				        this.oy = this.type == "rect" ? this.attr("y") : this.attr("cy");
				        this.animate({"fill-opacity": .2}, 500);
				    }, 
				    function () {
        				this.animate({"fill-opacity": 0}, 500);
    				}	 
				);
		    }		    
   		'. join (', ', $connections);

   		$return ['shapes'] = $this->shapes;	

   		return $return;
	}
}


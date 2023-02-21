<?php
namespace PerfectImageSizes\Integration;

class BeaverBuilder {

    public function __construct() {
        add_filter( 'fl_builder_pre_render_css_rules' , __CLASS__ . '::background_image_replace' );

    }

    function background_image_replace( $rules ) {
    
        foreach ($rules as &$rule) {
            if (isset($rule['props']['background-image'])) {
                $rule['props']['background-image'] = apply_filters( 'perfect_image_sizes/imageurl' , $rule['props']['background-image'] );
            }
        }
        return $rules;
    }        
}
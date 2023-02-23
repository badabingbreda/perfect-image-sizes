<?php
namespace PerfectImageSizes\Integration;

class BeaverBuilder {

    public function __construct() {
        add_filter( 'fl_builder_pre_render_css_rules' , __CLASS__ . '::css_background_image_replace' );
        add_filter( 'fl_builder_node_attributes' , __CLASS__ . '::node_background_image_replace' , 10 , 2 );

    }
    
    /**
     * css_background_image_replace
     *
     * @param  mixed $rules
     * @return void
     */
    function css_background_image_replace( $rules ) {
    
        foreach ($rules as &$rule) {
            if (isset($rule['props']['background-image'])) {
                $rule['props']['background-image'] = apply_filters( 'perfect_image_sizes/imageurl' , $rule['props']['background-image'] );
            }
        }
        return $rules;
    }  
    
    /**
     * node_background_image_replace
     *
     * @param  mixed $attrs
     * @param  mixed $row
     * @return void
     */
    function node_background_image_replace( $attrs , $row ) {
    
        if (isset($attrs['data-parallax-image'])) {
            $attrs['data-parallax-image'] = apply_filters( 'perfect_image_sizes/imageurl' , $attrs['data-parallax-image'] );
        }
        if (isset($attrs['data-parallax-image-medium'])) {
            $attrs['data-parallax-image-medium'] = apply_filters( 'perfect_image_sizes/imageurl' , $attrs['data-parallax-image-medium'] );
        }
        if (isset($attrs['data-parallax-image-responsive'])) {
            $attrs['data-parallax-image-responsive'] = apply_filters( 'perfect_image_sizes/imageurl' , $attrs['data-parallax-image-responsive'] );
        }

        return $attrs;
    }  


}
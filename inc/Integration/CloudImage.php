<?php
namespace PerfectImageSizes\Integration;

class CloudImage {
    
    /**
     * ratio
     *
     * @param  mixed $ratio
     * @return void
     */
    public static function ratio($w,$h) {
        return "&aspect_ratio=" . round($w/$h,3,PHP_ROUND_HALF_UP);
    }
    
    /**
     * gravity
     *
     * @param  mixed $focal_x_p
     * @param  mixed $focal_y_p
     * @return void
     */
    public static function gravity($focal_x_p, $focal_y_p){
        return "&gravity={$focal_x_p}p,{$focal_y_p}p";
    }
    
    /**
     * calc_crop_func
     *
     * @param  mixed $crop
     * @param  mixed $w
     * @param  mixed $h
     * @param  mixed $gravity
     * @param  mixed $ratio
     * @return void
     */
    public static function calc_crop_func( $crop , $w , $h , $gravity , $ratio ) {

        /* determine the crop setting: 
         * if true, crop both width and height
         * if false or 'width', resize to fit to width (no matter the height)
         * if 'height', resize to max height (no matter the width)
         */
        if ($crop === true) {
            $crop_func = "func=crop&width={$w}&height={$h}{$gravity}{$ratio}";
        } else if ( $crop === 'height' ) {
            $crop_func = "height={$h}{$gravity}{$ratio}";
        } else {
            $crop_func = "width={$w}{$gravity}{$ratio}";
        }

        return $crop_func;
    }
    
    /**
     * breakpoint_image
     *
     * @param  mixed $image_url
     * @param  mixed $crop_func
     * @return void
     */
    public static function breakpoint_image($image_url , $crop_func , $output = 'webp' ) {
        return $image_url . "?{$crop_func}&force_format=" . $output;        
    }

    /**
     * img_attr
     *
     * @param  mixed $html
     * @param  mixed $attr
     * @return void
     */
    public static function img_attr($attr,$attachment_id) {

        $html = '';

		foreach( $attr as $name => $value ){
			$html .= " {$name}=\"{$value}\"";
		}

       return $html; 
    }


}
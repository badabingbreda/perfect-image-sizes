<?php
namespace PerfectImageSizes\Integration;
use PerfectImageSizes\Imager;

class TwicPics {

    public function __construct() {

        add_filter( 'perfect_image_sizes/breakpoint_image' , __CLASS__ . '::image_quality' , 10 , 1 );
    }
        
    /**
     * image_quality
     *
     * @param  mixed $image_url
     * @return void
     */
    public static function image_quality( $image_url )  {
        $image_quality = get_option( 'pis_api_image_quality' , 0 );
        // if 0 or less do not add
        if ( $image_quality <= 0 ) return $image_url;
        // add quality setting
        return $image_url . "/quality={$image_quality}";
    }
    
    /**
     * ratio
     *
     * @param  mixed $ratio
     * @return void
     */
    public static function ratio($w,$h) {
        return "/cover={$w}:{$h}";
    }
    
    /**
     * gravity
     *
     * @param  mixed $focal_x_p
     * @param  mixed $focal_y_p
     * @return void
     */
    public static function gravity($focal_x_p, $focal_y_p){
        return "/focus={$focal_x_p}px{$focal_y_p}p";
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
            $crop_func = "{$gravity}/cover={$w}x{$h}";
        } else if ( $crop === 'height' ) {
            $crop_func = "{$gravity}/resize=-x{$h}{$ratio}";
        } else {
            $crop_func = "{$gravity}/resize={$w}{$ratio}";
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
        $image_url = $image_url . "?twic=v1{$crop_func}/output=" . $output;        
        // apply filters so we can add quality and more if needed
        return apply_filters( 'perfect_image_sizes/breakpoint_image' , $image_url );

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
        // remove the filter temporarily, or we will not get the original url
        remove_filter( 'wp_get_attachment_url' , 'PerfectImageSizes\Imager::attachment_url' , 10, 1 );
        $image_url = \wp_get_attachment_image_url( $attachment_id , 'full', false);
        // readd the filter
        add_filter( 'wp_get_attachment_url' , 'PerfectImageSizes\Imager::attachment_url' , 10, 1 );

        // use the uploads dir
        $source = \wp_get_upload_dir()['baseurl'] . '/';

        $image = apply_filters( 'perfect_image_sizes/imageurl', $image_url );

		foreach( $attr as $name => $value ){
            if( $name === 'lqip' && $value === true && !Imager::get_cache_enabled() ) {
                $html .= " style=\"object-fit:cover;background-size:cover;background-image: url({$image}?twic=v1/resize=100/output=preview);\"";
                $html .= " data-twic-background=\"url(" .str_replace( $source, "" , $image_url ). ")\"";
                continue;
            }
			$html .= " {$name}=\"{$value}\"";
		}

       return $html; 
    }

}
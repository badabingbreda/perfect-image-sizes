<?php
namespace PerfectImageSizes;

use PerfectImageSizes\FocalPoint;

class Imager {

    public function __construct() {
        add_filter( 'perfect_get_attachment_picture' , __CLASS__ . '::get_imager' , 10 , 5 );
    }
    
    /**
     * get_attachment_picture
     *
     * @param  mixed $attachment_id
     * @param  mixed $sizes
     * @param  mixed $attr
     * @return void
     */
    public static function get_attachment_picture( $attachment_id , $breakpoints = null , $attr = array() , $max_full ) {

		// let the hooks handle this
		return apply_filters( 'perfect_get_attachment_picture', $html, $attachment_id, $breakpoints, $attr , $max_full );
    }
    
    /**
     * get_imager
     *
     * @param  mixed $html
     * @param  mixed $attachment_id
     * @param  mixed $sizes
     * @param  mixed $attr
     * @return void
     */
    public static function get_imager( $html , $attachment_id , $breakpoints , $attr , $max_full ) {

        if( $attachment_id < 1 || !is_array( $breakpoints ) || count( $breakpoints ) === 0 ){
			return $html;
		}
        // sort the sizes
		ksort( $breakpoints );


        $html .= "<picture>";

		$last_breakpoint = 0;        

        // get the attachment mimetype
        $mime_type = get_post_mime_type( $attachment_id );

		// loop over the breakpoints of found
        foreach( $breakpoints as $breakpoint => $data ){

			if( intval( $breakpoint ) && count( $data ) >= 2 ){
				
                $breakpoint_image = self::get_image_url( $attachment_id , $data );

                if (!$breakpoint_image) continue;

                $html .= '<source media="(max-width:' . intval( $breakpoint ) . 'px)" srcset="' . $breakpoint_image . '" type="' . $mime_type . '">';
                $last_breakpoint = intval( $breakpoint );
			}
		}

        // get the metadata
        $metadata = wp_get_attachment_metadata( $attachment_id );
        list( $width , $height ) = $metadata;


        // if max full size has been given
        if ( is_array($max_full) && count($max_full) >= 2 ) {

            $max_image = self::get_image_url( $attachment_id , $max_full );

            $attr = array_merge( array( 'width' => $max_full[0] , 'height' => $max_full[1] ) , $attr );
        } else {
            $max_image = $image_url;
            $attr = array_merge( array( 'width' => $width , 'height' => $height ) , $attr );
        }

        $html .= "<source media=\"(min-width:". ($last_breakpoint + 1) . "px)\" srcset=\"{$max_image}\">";

        $full_image = self::get_image_url( $attachment_id);

        $html .= "<img src=\"{$full_image}\"";
		foreach( $attr as $name => $value ){
			$html .= " {$name}=\"{$value}\"";
		}
		$html .= ' />';		

        $html .= "</picture>";

        return $html;

    }

    private static function get_image_url( $attachment_id , $data = [] ) {  
        
        // get full image source url
        $image_url = wp_get_attachment_image_url( $attachment_id, 'full', false );

        if (!$image_url) return false;

        // if data params fail just return the image_url
        if (!$data || !is_array($data) || count($data)<2 ) return apply_filters( 'perfect_image_sizes/imageurl' , $image_url );
        
        $w = $data[0];									// width
        $h = $data[1];									// height
        $crop = isset($data[2]) ? $data[2] : false;		// use crop

        $ratio = (isset( $data[3] ) && $data[3] === true ) ? "&aspect_ratio=" . round($w/$h,3,PHP_ROUND_HALF_UP) : false;
        // get the focal point
        $focal = $crop ? FocalPoint::sanitize_focal_point( get_post_meta( $attachment_id, 'focal_point', true ) ) : false;
        
        if ($focal) {
            $focal_x_p = intval($focal[0] * 100);
            $focal_y_p = intval($focal[1] * 100);
            $crop_func = "func=crop&w={$w}&h={$h}&gravity={$focal_x_p}p,{$focal_y_p}p{$ratio}";
        } else {
            $crop_func = $crop ? "func=crop&width={$w}&height={$h}" : "func=bound&width={$w}{$ratio}";//"func=bound&width={$w}&height={$h}";
        }

        $breakpoint_image = $image_url . "?{$crop_func}";
        $breakpoint_image = apply_filters( 'perfect_image_sizes/imageurl' , $breakpoint_image );   
        
        return $breakpoint_image;
    }
}
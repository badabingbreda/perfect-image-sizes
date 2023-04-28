<?php
namespace PerfectImageSizes;

use PerfectImageSizes\FocalPoint;
use PerfectImageSizes\Integration\CloudImage;
use PerfectImageSizes\Integration\TwicPics;

class Imager {

    private static $imager;

    public function __construct() {
        // maybe change the imager on plugins loaded
        add_action( 'plugins_loaded' , __CLASS__ . '::plugins_loaded' );
        
        add_filter( 'perfect_get_attachment_picture' , __CLASS__ . '::get_imager' , 10 , 6 );

        // replace attachement urls
        add_filter( 'wp_get_attachment_url', __CLASS__ . '::attachment_url' , 10, 1 );
        // Replace srcset paths
        add_filter('wp_calculate_image_srcset', __CLASS__ . '::image_srcset' , 10, 1 );

        // filter to replace any missed images
        // try to make sure this refers to <img src=""> only
        add_filter( 'the_content' , __CLASS__ . '::regex_perfect_image_sizes' , 10 , 1 );

        
    }
        
    /**
     * plugins_loaded
     * 
     * 
     *
     * @return void
     */
    public static function plugins_loaded() {
        
        $use_imager = apply_filters( 'perfect_image_sizes/imager' , 'twicpics' );
    
        switch( $use_imager ) {
            case "twicpics":
                self::$imager = new TwicPics();
            break;
            default:
            case "cloudimage":
                self::$imager = new CloudImage();
            break;
    
        }
    }
    
    /**
     * get_attachment_picture
     *
     * @param  mixed $attachment_id
     * @param  mixed $sizes
     * @param  mixed $attr
     * @return void
     */
    public static function get_attachment_picture( $attachment_id , $breakpoints = null , $attr = array() , $max_full = null , $identifier = null ) {

		// let the hooks handle this
		return apply_filters( 'perfect_get_attachment_picture', $html, $attachment_id, $breakpoints, $attr , $max_full , $identifier );
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
    public static function get_imager( $html , $attachment_id , $breakpoints , $attr , $max_full , $identifier ) {

        $imager = self::$imager;

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
				
                $breakpoint_image = self::get_image_url( $attachment_id , $data , $breakpoint , $identifier );

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

            $max_image = self::get_image_url( $attachment_id , $max_full , 'max_full' , $identifier );
            $attr = array_merge( array( 'width' => $max_full[0] , 'height' => $max_full[1] ) , $attr );
        } else {
            $max_image = self::get_image_url($attachment_id , null , 'original' , $identifier );
            $attr = array_merge( array( 'width' => $width , 'height' => $height ) , $attr );
        }

        $html .= "<source media=\"(min-width:". ($last_breakpoint + 1) . "px)\" srcset=\"{$max_image}\">";

        $full_image = self::get_image_url($attachment_id , null , 'original' , $identifier );

        $html .= "<img src=\"{$full_image}\"";
        $html .= $imager::img_attr($attr,$attachment_id);
		$html .= ' />';		

        $html .= "</picture>";

        return $html;

    }
    
    /**
     * get_image_url
     *
     * @param  mixed $attachment_id
     * @param  mixed $data
     * @return void
     */
    private static function get_image_url( $attachment_id , $data = [] , $size = null , $identifier = null ) { 

        $imager = self::$imager;

        $images = [];
        
        // get full image source url
        $image_url = wp_get_attachment_image_url( $attachment_id, 'full', false );

        if (!$image_url) return false;

        // if data params fail just return the image_url
        if (!$data || !is_array($data) || count($data)<2 ) return apply_filters( 'perfect_image_sizes/imageurl' , $image_url );
        
        $w = $data[0];									// width
        $h = $data[1];									// height
        $crop = isset($data[2]) ? $data[2] : false;		// use crop: true/false/'width'/'height'

        // set ratio to resize ratio
        $ratio = (isset( $data[3] ) && $data[3] === true ) ? $imager::ratio($w,$h) : false;
        // if a fractical ratio has been given, use that
        $ratio = ($ratio === false && isset( $data[3] ) && is_numeric($data[3]) ) ? $imager::ratio($data[3] * 100 , 100) : $ratio;

        $gravity = "";
        // get the focal point if crop is to be used
        $focal = FocalPoint::sanitize_focal_point( get_post_meta( $attachment_id, 'focal_point', true ) );

        // add gravity point if focal has been set
        if ($focal) {
            $focal_x_p = intval($focal[0] * 100);
            $focal_y_p = intval($focal[1] * 100);
            $gravity = $imager::gravity($focal_x_p,$focal_y_p);
        }

        $crop_func = $imager::calc_crop_func( $crop , $w , $h , $gravity , $ratio );
        $breakpoint_image = $imager::breakpoint_image($image_url,$crop_func);
        $breakpoint_image = apply_filters( 'perfect_image_sizes/imageurl' , $breakpoint_image ); 
        $images[] = $breakpoint_image . ' 1x';

        if (isset($data[4]) && is_array($data[4])) {

            // get the metadata
            $metadata = wp_get_attachment_metadata( $attachment_id );

            foreach ($data[4] as $density) {
                switch( $density ) {
                    case '1.5x':
                        if ($w*1.5 > $metadata['width'] || $h*1.5 > $metadata['height']) break;
                        $crop_func = $imager::calc_crop_func( $crop , $w*1.5 , $h*1.5 , $gravity , $ratio );
                        $breakpoint_image = $imager::breakpoint_image($image_url,$crop_func);
                        $breakpoint_image = apply_filters( 'perfect_image_sizes/imageurl' , $breakpoint_image ); 
                        $images[] = $breakpoint_image . ' 1.5x';
                    break;
                    case '2x':
                        if ($w*2 > $metadata['width'] || $h*2 > $metadata['height']) break;
                        $crop_func = $imager::calc_crop_func( $crop , $w*2 , $h*2 , $gravity , $ratio );
                        $breakpoint_image = $imager::breakpoint_image($image_url,$crop_func);
                        $breakpoint_image = apply_filters( 'perfect_image_sizes/imageurl' , $breakpoint_image ); 
                        $images[] = $breakpoint_image . ' 2x';
                    break;
                    case '3x':
                        if ($w*3 > $metadata['width'] || $h*3 > $metadata['height']) break;
                        $crop_func = $imager::calc_crop_func( $crop , $w*3 , $h*3 , $gravity , $ratio );
                        $breakpoint_image = $imager::breakpoint_image($image_url,$crop_func);
                        $breakpoint_image = apply_filters( 'perfect_image_sizes/imageurl' , $breakpoint_image ); 
                        $images[] = $breakpoint_image . ' 3x';
                    break;

                }
            }

        } 

        $images = apply_filters( 'perfect_image_sizes/imageurl/after' , $images , $attachment_id , $data , $size , $identifier );

        return implode( ', ', $images );
    }

    /**
     * attachment_url
     * 
     * replace url with cloudimage.io path
     *
     * @param  mixed $url
     * @return void
     */
    public static function attachment_url($url) {
        if (is_admin()) return $url;
        if (file_exists($url)) {
            return $url;
        }
        return apply_filters( 'perfect_image_sizes/imageurl' , $url );
    }
    
    /**
     * regex_perfect_image_sizes
     *
     * @param  mixed $content
     * @return void
     */
    public static function regex_perfect_image_sizes( $content ) {

        if ( !apply_filters( 'perfect_image_sizes/the_content' , false ) ) return $content;

        // use the uploads dir
        $source = \wp_get_upload_dir()['baseurl'] . '/';
        
        // see if this matches and convert to destination
        $destination = apply_filters( 'perfect_image_sizes/imageurl', $source );

        // no need to regex if source and destination remain unchanged
        if ( $source === $destination ) return $content;

        $regex = [ "/" . preg_quote( $source , "/" ) . "(.*(.png|.gif|.jpg|.jpeg|.webp))/" ];
        $replace = [ $destination . "$1" ];

        $result = preg_replace( $regex , $replace , $content );

        if ($result) return $result;
        return $content;
    }

        
    /**
     * image_srcset
     * 
     * replace srcset for <img> tags
     *
     * @param  mixed $sources
     * @return void
     */
    public static function image_srcset($sources) {
        if (is_admin()) return $sources;
        foreach($sources as &$source) {
            if(!file_exists($source['url'])) 
            {
                $source['url'] = apply_filters( 'perfect_image_sizes/imageurl' ,  $source['url']);
            }
        }
        return $sources;
    }    
}
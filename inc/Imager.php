<?php

// https://rudrastyh.com/wordpress/custom-bulk-actions.html

namespace PerfectImageSizes;

use PerfectImageSizes\FocalPoint;
use PerfectImageSizes\LocalStore;
use PerfectImageSizes\Integration\CloudImage;
use PerfectImageSizes\Integration\TwicPics;

class Imager {

    private static $imager;

    private static $enable_cache = false;

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

        // add to bulk dropdown
        add_filter( 'bulk_actions-upload', __CLASS__ . '::add_bulk_action' );
        // handle our bulk action
        add_filter( 'handle_bulk_actions-upload', __CLASS__ . '::bulk_action_handler', 10, 3 );
        // add admin notice if bulk action has been performed
        add_action( 'admin_notices', __CLASS__ . '::bulk_action_notices' );
        
        
    }
        
    /**
     * add_bulk_actions
     *
     * @param  mixed $bulk_array
     * @return void
     */
    public static function add_bulk_action( $bulk_array ) {

        $bulk_array[ 'regenerate_pis_images' ] = 'Regenerate PIS images';
        return $bulk_array;

    } 
    
    public static function bulk_action_handler( $redirect, $do_action, $object_ids ) {

        // let's remove query args first
        $redirect = remove_query_arg(
            array( 'bulk_pis_images_removed' ),
            $redirect
        );
    
        // do something for "Make Draft" bulk action
        if ( 'regenerate_pis_images' === $do_action ) {
    
            foreach ( $object_ids as $post_id ) {
                LocalStore::delete_attachment_pis_images( $post_id );
            }
    
            // do not forget to add query args to URL because we will show notices later
            $redirect = add_query_arg(
                'bulk_pis_images_removed', // just a parameter for URL
                count( $object_ids ), // how many posts have been selected
                $redirect
            );
    
        }
    
        return $redirect;
    
    } 
    
    public static function bulk_action_notices() {

        // first of all we have to make a message,
        // but you can create an awesome message
        if( ! empty( $_REQUEST[ 'bulk_pis_images_removed' ] ) ) {
    
            $count = (int) $_REQUEST[ 'bulk_pis_images_removed' ];
            // depending on ho much posts were changed, make the message different
            $message = sprintf(
                _n(
                    '%d PIS image has been cleared.',
                    '%d PIS images have been cleared.',
                    $count,
                    'perfect-image-sizes'
                ),
                $count
            );
    
            echo "<div class=\"updated notice is-dismissible\"><p>{$message}</p></div>";
    
        }
    
    }    

    /**
     * plugins_loaded
     * 
     * 
     *
     * @return void
     */
    public static function plugins_loaded() {
        
        // try to get the settings from the options table, fallback to twicpics if none selected
        $imager = get_option( 'pis_imager' , 'twicpics' );

        // legact filter
        $use_imager = apply_filters( 'perfect_image_sizes/imager' , $imager );

        // set the imageurl replacement using our generic setting
        $api_access_path = get_option( 'pis_api_access_path' , '' );

        if ( $api_access_path ) {
            add_filter( 'perfect_image_sizes/imageurl' , 
            function( $name ) use ( $api_access_path ) {
                $uploads_dir = wp_upload_dir();
                return str_replace( $uploads_dir[ 'baseurl' ] , $api_access_path , $name ) ;
            } , 10 , 1 ) ;
        }


        self::$enable_cache = get_option( 'pis_enable_cache' , false );

        if ( self::$enable_cache ) {
            // figure this out later

        }

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

        $html = '';
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
        $width = $metadata['width'];
        $height = $metadata[ 'height' ];


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

        $breakpoint_image = self::get_breakpoint_image( $attachment_id , $w , $h , $crop , $focal_x_p , $focal_y_p , $ratio , $crop_func , false );
        
        $images[] = $breakpoint_image . ' 1x';

        if (isset($data[4]) && is_array($data[4])) {

            // get the metadata
            $metadata = wp_get_attachment_metadata( $attachment_id );

            foreach ($data[4] as $density) {
                switch( $density ) {
                    case '1.5x':
                        if ($w*1.5 > $metadata['width'] || $h*1.5 > $metadata['height']) break;
                        $crop_func = $imager::calc_crop_func( $crop , $w*1.5 , $h*1.5 , $gravity , $ratio );
                        // $breakpoint_image = $imager::breakpoint_image( $image_url,$crop_func );
                        // $breakpoint_image = apply_filters( 'perfect_image_sizes/imageurl' , $breakpoint_image , $crop_func ); 
                        $breakpoint_image = self::get_breakpoint_image( $attachment_id , $w , $h , $crop , $focal_x_p , $focal_y_p , $ratio , $crop_func , '1.5x' );
                        $images[] = $breakpoint_image . ' 1.5x';
                    break;
                    case '2x':
                        if ($w*2 > $metadata['width'] || $h*2 > $metadata['height']) break;
                        $crop_func = $imager::calc_crop_func( $crop , $w*2 , $h*2 , $gravity , $ratio );
                        // $breakpoint_image = $imager::breakpoint_image($image_url,$crop_func);
                        // $breakpoint_image = apply_filters( 'perfect_image_sizes/imageurl' , $breakpoint_image , $crop_func ); 
                        $breakpoint_image = self::get_breakpoint_image( $attachment_id , $w , $h , $crop , $focal_x_p , $focal_y_p , $ratio , $crop_func , '2x' );
                        $images[] = $breakpoint_image . ' 2x';
                    break;
                    case '3x':
                        if ($w*3 > $metadata['width'] || $h*3 > $metadata['height']) break;
                        $crop_func = $imager::calc_crop_func( $crop , $w*3 , $h*3 , $gravity , $ratio );
                        // $breakpoint_image = $imager::breakpoint_image($image_url,$crop_func);
                        // $breakpoint_image = apply_filters( 'perfect_image_sizes/imageurl' , $breakpoint_image , $crop_func ); 
                        $breakpoint_image = self::get_breakpoint_image( $attachment_id , $w , $h , $crop , $focal_x_p , $focal_y_p , $ratio , $crop_func , '3x' );
                        $images[] = $breakpoint_image . ' 3x';
                    break;

                }
            }

        } 

        $images = apply_filters( 'perfect_image_sizes/imageurl/after' , $images , $attachment_id , $data , $size , $identifier );

        return implode( ', ', $images );
    }
    
    /**
     * get_breakpoint_image
     * 
     * get the image url, taking into consideration if cache needs to be used
     *
     * @param  mixed $attachment_id
     * @param  mixed $w
     * @param  mixed $h
     * @param  mixed $crop
     * @param  mixed $focal_x_p
     * @param  mixed $focal_y_p
     * @param  mixed $ratio
     * @param  mixed $crop_func
     * @param  mixed $retina
     * @return void
     */
    private static function get_breakpoint_image( $attachment_id , $w , $h , $crop , $focal_x_p , $focal_y_p , $ratio , $crop_func , $retina = false ) {

        $imager = self::$imager;

        // get full image source url
        $image_url = wp_get_attachment_image_url( $attachment_id, 'full', false );

        // get pis full name
        if ( self::$enable_cache ) {
            // get the image metadata
            $image = wp_get_attachment_metadata( $attachment_id );

            // generate a filename that we will use to store this size
            $file_name = LocalStore::get_pis_full_name( 
                    basename( $image['file'] ) , 
                    [ 
                        'w' => $w , 
                        'h' => $h , 
                        'crop' => $crop , 
                        'gravity' => (isset( $focal_x_p ) ? $focal_x_p . 'x' . $focal_y_p : false) , 
                        'ratio' => $ratio , 
                        'retina' => $retina 
                        ] );

            // now that we have a file_name and our breakpoint image,
            // check to see if it already exists. If so, return the full url
            // if not, download the breakpoint image, store under the $file_name and return the full url
            $generated_file_name = LocalStore::get_generated_path( $attachment_id , $file_name );
            if ( file_exists( $generated_file_name ) ) {
                $breakpoint_image = LocalStore::get_pis_path( $generated_file_name );
            } else {
                // generate the url that will get our optimized image
                $breakpoint_image = $imager::breakpoint_image( $image_url, $crop_func , 'webp' );
                $breakpoint_image = apply_filters( 'perfect_image_sizes/imageurl' , $breakpoint_image , $crop_func );

                // download the optimized image and save to disk
                LocalStore::download_image( $breakpoint_image , $attachment_id , $file_name );

                // return the url
                $breakpoint_image = LocalStore::get_pis_path( $generated_file_name );
    
            }
            
        } else {
            $breakpoint_image = $imager::breakpoint_image($image_url,$crop_func, 'webp');
            $breakpoint_image = apply_filters( 'perfect_image_sizes/imageurl' , $breakpoint_image , $crop_func );
        }

        return $breakpoint_image;

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
                $source['url'] = apply_filters( 'perfect_image_sizes/imageurl' ,  $source['url'] );
            }
        }
        return $sources;
    }    
}
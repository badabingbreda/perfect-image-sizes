<?php
namespace PerfectImageSizes;

use PerfectImageSizes\Integration\CloudImage;
use PerfectImageSizes\Integration\TwicPics;

/**
 * Use a remote resize and optimize but store the returned file as a local path
 */
class LocalStore {

    public static $pis_dir = '';
    private static $agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:109.0) Gecko/20100101 Firefox/114.0';

    public function __construct() {

        self::$pis_dir = apply_filters( 'pis_dir_path' , self::get_pis_dir() );

        self::check_pis_dir();  

        // when switching blogs
        add_action( 'switch_blog', __CLASS__ . '::blog_switched' );
        
        add_filter( 'media_row_actions', __CLASS__ . '::media_row_action' , 10, 2 );
        
        add_filter( 'attachment_fields_to_edit', __CLASS__ . '::attachment_regenerate_button' , 20, 2 );

        // when attachment is removed, delete the pis images as well
        add_action( 'delete_attachment', __CLASS__ . '::delete_attachment_pis_images' );

    }
    

    // make it works for multisite network
    public static function blog_switched(){
        self::$pis_dir = '';
        self::$pis_dir = apply_filters( 'pis_dir_path', self::get_pis_dir() );
    }    
    /**
     * get_pis_dir
     *
     * @param  mixed $path
     * @return void
     */
    public static function get_pis_dir( $path = '' ) {

        if ( empty( self::$pis_dir )) {
            $uploads_dir = wp_upload_dir();
            return $uploads_dir[ 'basedir' ] . DIRECTORY_SEPARATOR . 'pis-images' . ( $path !== '' ? DIRECTORY_SEPARATOR . $path : '' );
        } else {
            return self::$pis_dir . ( $path !== '' ? DIRECTORY_SEPARATOR . $path : '' );
        }

    }
    
    /**
     * check_pis_dir
     *
     * @return void
     */
    public static function check_pis_dir(){
        if( ! is_dir( self::$pis_dir ) ){
            wp_mkdir_p( self::$pis_dir );
        }
    }
        
    /**
     * check_attachment_pis_dir
     * 
     * check if an attachmentdir exists
     *
     * @param  mixed $attachment_id
     * @return void
     */
    public static function check_attachment_pis_dir( $attachment_id = 0 ) {
        if ( $attachment_id <= 0 ) return false;
        if( ! is_dir( self::$pis_dir . DIRECTORY_SEPARATOR . $attachment_id ) ){
            wp_mkdir_p( self::$pis_dir . DIRECTORY_SEPARATOR . $attachment_id );
        }

        return self::$pis_dir . DIRECTORY_SEPARATOR . $attachment_id;
    }
    
    /**
     * get_pis_path
     * 
     * get the file, return as url
     *
     * @param  mixed $absolute_path
     * @return void
     */
    public static function get_pis_path( $absolute_path = '' ){
        $wp_upload_dir = wp_upload_dir();
        $path = $wp_upload_dir['baseurl'] . str_replace( $wp_upload_dir['basedir'], '', $absolute_path );
        return str_replace( DIRECTORY_SEPARATOR, '/', $path );
    }
    
    /**
     * get_generated_path
     * 
     * return a full pathname for use in self::get_pis_path()
     *
     * @param  mixed $attachement_id
     * @param  mixed $file_name
     * @return void
     */
    public static function get_generated_path( $attachment_id , $file_name ) {
        return self::$pis_dir . DIRECTORY_SEPARATOR . $attachment_id . DIRECTORY_SEPARATOR . $file_name;
    }

    
    /**
     * pis_dir_writable
     *
     * @return void
     */
    public static function pis_dir_writable(){
        return is_dir( self::$pis_dir ) && wp_is_writable( self::$pis_dir );
    } 
        
    /**
     * delete_all_pis_images
     * 
     * delete our pis_dir and re-create it
     *
     * @return void
     */
    public static function delete_all_pis_images(){
        if( ! function_exists( 'WP_Filesystem' ) ) return false;
        WP_Filesystem();
        global $wp_filesystem;
        if( $wp_filesystem->rmdir( self::get_pis_dir(), true ) ){
            self::check_pis_dir();
            return true;
        }
        return false;
    }
    
    /**
     * delete_attachment_pis_images
     *
     * @param  mixed $attachment_id
     * @return void
     */
    public static function delete_attachment_pis_images( $attachment_id = 0 ){
        if( ! function_exists( 'WP_Filesystem' ) ) return false;
        WP_Filesystem();
        global $wp_filesystem;
        return $wp_filesystem->rmdir( self::get_pis_dir( $attachment_id ), true );
    } 

    /**
     * get_pis_file_name
     * 
     * generate a filename for our file request, this should include all parameters
     * but not be depending on the platform we're using to get optimize an image
     *
     * @param  mixed $file_name
     * @param  mixed $width
     * @param  mixed $height
     * @param  mixed $crop
     * @return void
     */
    public static function get_pis_file_name( $file_name, $width, $height, $crop ) {
        $file_name_only = pathinfo( $file_name, PATHINFO_FILENAME );
        $file_extension = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );
        $crop_extension = '';
        if( $crop === true || $crop === 1 ){
            $crop_extension = '-c';
        }elseif( is_array( $crop ) ){
            if( is_numeric( $crop[0] ) ){
                $crop_extension = '-f' . round( floatval( $crop[0] ) * 100 ) . '_' . round( floatval( $crop[1] ) * 100 );
            }else{
                $crop_extension = '-' . implode( '', array_map( function( $position ){
                    return $position[0];
                }, $crop ) );
            }
        }
        return $file_name_only . '-' . intval( $width ) . 'x' . intval( $height ) . $crop_extension . '.webp';// . $file_extension;
    }
    
    /**
     * get_pis_full_name
     *
     * @param  mixed $file_name
     * @param  mixed $s
     * @return void
     */
    public static function get_pis_full_name( $file_name , $s ) {

        $s = wp_parse_args( 
            $s, 
            array(
                'width' => 0,
                'height' => 0,
                'ratio' => false,
                'retina' => false,
                'crop' => false,
                'gravity' => false,

            ) 
        );       

        $file_name_only = pathinfo( $file_name, PATHINFO_FILENAME );
        $file_extension = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );
        $crop_extension = '';

        $ratio = $s[ 'ratio' ] ? $s[ 'ratio' ] : 'f';
        $retina = $s[ 'retina' ] ? "@{$s['retina']}" : '';

        return  "{$file_name_only}-{$s['w']}x{$s['h']}-c{$s['crop']}-g{$s['gravity']}-r{$ratio}{$retina}.webp" ;
    }
    
    /**
     * download_image
     *
     * @param  mixed $image_url
     * @param  mixed $dest_file_name
     * @return void
     */
    public static function download_image( $image_url , $attachment_id , $file_name ) {

        
        // check if we need to create the attachment subdirectory
        $path = self::check_attachment_pis_dir( $attachment_id );

        // path fails, return false;
        if (!$path) return false;

        $ch = curl_init( $image_url );
        $fp = fopen( $path . DIRECTORY_SEPARATOR . $file_name , 'wb' );
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_USERAGENT, self::$agent);
        curl_setopt($ch, CURLOPT_HTTPHEADER , [ "Accept: image/webp" ] );
        curl_exec($ch);
        curl_close($ch);
        fclose($fp);  
        
        return true;
    }
    

    
    public static function media_row_action( $actions, $post ){
        if( in_array( $post->post_mime_type, PIS_ALLOWED_MIME_TYPES ) ){
            $url = wp_nonce_url( admin_url( 'options-general.php?page=' . PERFECTIMAGESIZES_BASE . '&delete-pis-image&ids=' . $post->ID ), 'delete_pis_image', 'pis_nonce' );
            $actions['pis-image-delete'] = '<a href="' . esc_url( $url ) . '" title="' . esc_attr( __( 'Delete all cached image sizes for this image', 'perfect-image-sizes' ) ) . '">' . __( 'Regenerate PIS images', 'perfect-image-sizes' ) . '</a>';
        }
        return $actions;
    }

    public static function attachment_regenerate_button( $form_fields, $post ){
        if( in_array( get_post_mime_type( $post->ID ), PIS_ALLOWED_MIME_TYPES ) ){
            $url = wp_nonce_url( admin_url( 'options-general.php?page=' . PERFECTIMAGESIZES_BASE . '&delete-pis-image&ids=' . $post->ID ), 'delete_pis_image', 'pis_nonce' );
            $form_fields['regenerate_pis_images'] = array(
                'value' => 1,
                'label' => __( 'Perfect image sizes', 'perfect-image-sizes' ),
                'input' => 'html',
                'html' => '<a class="button button-small" href="' . esc_url( $url ) . '" title="' . esc_attr( __( 'Delete all cached image sizes for this image', 'perfect-image-sizes' ) ) . '">' . __( 'Regenerate', 'perfect-image-sizes' ) . '</a>'
            );
        }
        return $form_fields;
    }    
}
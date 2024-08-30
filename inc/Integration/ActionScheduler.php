<?php
namespace PerfectImageSizes\Integration;

class ActionScheduler {

    public function __construct() {

        add_action( 'init', __CLASS__ . '::pis_remote_downloads_checker' , 20 );
        
        /**
         * When the daily action scheduler fires, it runs this action hook
         * add callbacks to it to make them run 
         */
        add_action( 'pis_remote_download', __CLASS__ . '::download_cdn_image' , 10 , 1 );
        
    }
    
    /**
     * default_url
     * 
     * return default rest api url
     *
     * @param  mixed $url
     * @return void
     */
    public static function default_url( $url ) {
        return 'https://wellzyperks.com/wp-json/wellzyperks/v1/';
    }
    
    /**
     * get_option_api
     *
     * @param  mixed $api
     * @return void
     */
    public static function get_option_api( $api ) {
        return get_option(  'wellzysync-account-key' , false );
    }

    /**
     * Schedule an action with the hook 'eg_midnight_log' to run at midnight each day
     * so that our callback is run then.
     */
    public static function pis_remote_downloads_checker() {

        if (!function_exists( 'as_has_scheduled_action' )) return;

        // add our scheduled action if it doesn't exist
        if ( false === \as_has_scheduled_action( 'pis_remote_download' ) ) {
            \as_schedule_recurring_action( 
                time(),
                MINUTE_IN_SECONDS, 
                'pis_remote_download',  // the action hook name to run
                array(), 
                '', 
                true 
            );
        }
    }

    /**
     * A callback to run when the 'eg_midnight_log' scheduled action is run.
     */
    public static function download_cdn_image( $image ) {

        if (!function_exists( 'as_has_scheduled_action' )) return;

        // run action, child classes should have registered to this hook
        do_action( 'wellzysync/remote_import' );
        
    }
    


}
<?php
namespace PerfectImageSizes;

use PerfectImageSizes\FocalPoint;
use PerfectImageSizes\Imager;
use PerfectImageSizes\LocalStore;
use PerfectImageSizes\PluginOptions;

use PerfectImageSizes\Integration\BeaverBuilder;
use PerfectImageSizes\Integration\CloudImage;
use PerfectImageSizes\Integration\TwicPics;


class Init {

    public function __construct() {
        new LocalStore();
        new PluginOptions();

        new FocalPoint();
        // need to initialize to add filters
        new Imager();


        // add BeaverBuilder replacements
        new BeaverBuilder();
        // new CloudImage();
        // new TwicPics();
        
        if( ! defined('PIS_ALLOWED_MIME_TYPES') ){
            define( 'PIS_ALLOWED_MIME_TYPES', array( 'image/jpeg', 'image/png', 'image/webp' ) );
        }
    
    }
}
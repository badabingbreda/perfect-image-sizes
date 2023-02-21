<?php
namespace PerfectImageSizes;

use PerfectImageSizes\FocalPoint;
use PerfectImageSizes\Imager;

use PerfectImageSizes\Integration\BeaverBuilder;

class Init {

    public function __construct() {
        new FocalPoint();
        // need to initialize to add filters
        new Imager();

        // add BeaverBuilder replacements
        new BeaverBuilder();

        if( ! defined('PIS_ALLOWED_MIME_TYPES') ){
            define( 'PIS_ALLOWED_MIME_TYPES', array( 'image/jpeg', 'image/png', 'image/webp' ) );
        }
    
    }
}
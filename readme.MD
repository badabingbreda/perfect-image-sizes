# Perfect Image Sizes

Creates perfect image sizes using CloudImage.io resizing, optimization and filtering.


When rendering an image, use the perfect_get_picture method to get a <picture> element with a number of breakpoints:

PHP

    perfect_get_picture( $media_id , $breakpoints = array() , $attr = array() , $max_image = null );

The breakpoints is an array of max-width sizes, with the settings for each breakpoint:

    array(
        '480' => array( 320 , 320 , true , true )
    )

For instance is a breakpoint where a viewport smaller than 480px wide, the image will render as a 320x320 pixel cropped image, which is FORCED to maintain that aspect ratio, no matter the source aspect ratio. Scale if needed.

    {{ tb.perfect_get_picture( 
        TimberImage(item.thumbnail).id , 
        { 
            480: [ 320, 320, true , true ],
            640: [ 640, 320, true , true ],
            960: [ 320, 640, ,true , true ],
            1200: [ 1200 , 500 , true , true ]
        } , [] , [ 2400 , 1700 , false , false ] ) 
    }}
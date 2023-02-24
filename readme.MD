# Perfect Image Sizes

Creates perfect image sizes using CloudImage.io resizing, optimization and filtering.

## Focal Point
When the plugin is activated, a setting is added to the media library item. When opening the image for editing, a focal point can be set that needs to stay within focus whenever an image is cropped. This way, when a landscape image has a focal point to the left and the image is cropped to a square or portrait image, the focal point remains visible.

## `perfect_get_picture`

When rendering an image, use the perfect_get_picture method to get a <picture> element with a number of breakpoints:

PHP

    perfect_get_picture( $media_id , $breakpoints = array() , $attr = array() , $max_image = null );

The breakpoints is an array of max-width sizes, with the settings for each breakpoint:

    
    array(
        width_in_pixels => array( width , height , crop , ratio , density ),
    )

so, for instance: 

    array(
        480 => array( 320 , 320 , true , true , [ '1.5x' , '2x' ] ),
    )

will insert an image when the viewport is 480 pixels wide of less. The original image will be resized to 320x320, cropped, and will make sure to force that ratio (zoom in if needed). If the original image allows it to, it will also use 1.5x and 2x images when the viewport density is set to use it.

Moreover, when crop is set to true, and when the focal point is set on the image, the focal point will be used.


    {{ tb.perfect_get_picture( 
            item.thumbnail.id , 
            { 
                480:    [ 320, 320, true , true ],
                640:    [ 640, 320, true , true ],
                960:    [ 320, 640, ,true , true ],
                1200:   [ 1200 , 500 , true , true ],
            }, 
            {
                width: item.thumbnail.width,
                height: item.thumbnail.height,
                alt: item.thumbnail.alt,
                title: item.thumbnail.alt,
            },
            [ 2400 , 1700 , false , false ] 
        ) 
    }}

Beaver Builder support

- Gallery Module
- Image Module (working because srcset is altered through filter. src attribute remains unchangeable)
- Row Slideshow
- Row Image
- Parallax Background Nodes (Row/Col)
- 
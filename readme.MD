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


## breakpoint settings explained

#### `width_in_pixels`
device breakpoint in pixels

#### `Width`
Image width dimension in pixels

#### `Height`
Image height dimesion in pixels

#### `Crop`
A few different options here:

|||
|--|--|
|true  | use crop function, when set to true, as much as possible of the image will be retained, but create an image of w x h size
| false | resize using the width property, do not use crop |
| width | same as false, but easier to understand. use width property to resize|
| height | use height as the main resize property, makes sure the image is at least this high |

#### `ratio`
false : do not use a cover action
true  : Use the resize property to generate an resized image first, then process that image to cover it across the wxh proportions
ratio/fraction : i.e. 1/3, 16/9, .3333, 1/1 use a ratio or faction to cover the generated image to different proportions

#### `density`
an array of additional density images for the srcset. Calculate if the source image has enough pixels to generate a 1.5x, 2x or 3x pixel version



## Beaver Builder support

- Gallery Module
- Image Module (working because srcset is altered through filter. src attribute remains unchangeable)
- Row Slideshow
- Row Image
- Parallax Background Nodes (Row/Col)
- 
<?php

return array(

	// where to store the image files
	// (you'll need to create this directory)
	'storage_dir'   => path('storage'). 'uploads' . DS . 'thumbnails',


	'path_method'   => 'get_%s_path',


	// Should we keep the original uploaded file?
	'keep_original' => true,


	// If this is true, you can only request thumbnails of the
	// sizes defined below.
	// If this is false, then you can request a thumbnail of any
	// size (this requires that "keep_original" is true.)
	'strict_sizes'  => true,


	// If this is true, the resized images are generated when
	// the model is saved.  If this is false, they are generated
	// only when requested (and then cached).
	'on_save'       => true,


	// How to resize the images:
	// crop - fit the shortest side into the formatted dimension, keep aspect ratio
	// fit - fit the longest side into the formatted dimensions, keep aspect ratio
	// resize - resize image to new dimensions, aspect ratio be damned!
	'resize_method' => 'crop',


	// Image format for thumbnailed images.
	// Can be 'jpg', 'png' or 'gif'
	'thumbnail_format' => 'png',


	// Image quality for thumbnails.
	// 0-100.  Irrelevant if format is "gif"
	'thumbnail_quality' => 80,


	// For thumbnails that including padding, what color should the padded
	// area in the resulting thumbnail be.  If it's not set, or set to null,
	// and the format is "png", then it will be transparent.
	// Should be an array of (r,g,b) values
	'thumbnail_background' => array( 255, 255, 255),


	// Default field
	'default_field' => 'image',


	// The array of model attributes/database fields that define the thumbnailable
	// fields.
	// The key of the array is the attribute name, the value is an array of key-value
	// pairs that define settings for that particular field.
	//
	'fields'        => array(

		'image' => array(

			// sizes
			// - An array where the key is the "nickname" for the format and the value is
			//   a "(height)x(width)" dimension for the thumbnail.  A nickname of "default"
			//   can define the default size to use when requesting a thumbnail
			'sizes' => array(
				'small'   => '50x50',
				'medium'  => '100x100',
				'large'   => '300x300',
			),

			// what size to return if none is given
			'default_size' => 'small',

			// other possible keys can override the base settings for this model, i.e.:
			// - storage_dir
			// - keep_original

		)
	)
);
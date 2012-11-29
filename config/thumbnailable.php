<?php

/**
 * Easy thumbnailing for your Eloquent models.
 *
 * @package Thumbnailable
 * @version 1.3
 * @author  Colin Viebrock <colin@viebrock.ca>
 * @link    http://github.com/cviebrock/thumbnailable
 */


return array(

	/**
	 * Where do we store the image files?
	 * (we'll try and create this directory if it doesn't exist)
	 */
	'storage_dir' => path('storage') . 'uploads' . DS . 'thumbnails',


	/**
	 * What is the path to the images?
	 * (you'll need to define this depending on how you are making the `storage_dir`
	 * publically accessible.  Default assumes that you symlink the `storage_dir` to
	 * your application's /public/img/thumbnails directory.
	 */
	'base_url' => '/img/thumbnails',


	/**
	 * Should we keep the original uploaded file?
	 * This needs to be true if you set `strict_sizes` to be false.
	 */
	'keep_original' => true,


	/**
	 * If this is true, you can only request thumbnails of the
	 * sizes defined below.
	 * If this is false, then you can request a thumbnail of any
	 * size (this requires that `keep_original` above is true.)
	 */
	'strict_sizes' => true,


	/**
	 * If this is true, the resized images are generated when
	 * the model is saved.  If this is false, they are generated
	 * on-demand (and then cached).
	 */
	'on_save' => true,


	/**
	 * How should we resize the images (value passed to the Resizer bundle).
	 * Set to one of the following strings:
	 * "crop"  - resizes the image so that it completely fills the thumbnail,
	 *           but maintains the original image's aspect ratio.
	 * "fit"   - resizes the image so that it fits enitrely inside the thumbnail
	 *           (padding it, if necessary) and maintains the aspect ratio.
	 * "exact" - simply resizes the original image to the dimensions of the
	 *           thumbnail with no regard for aspect ratio.
	 */
	'resize_method' => 'crop',


	/**
	 *  Image format for thumbnailed images: one of "jpg", "png" or "gif".
	 *  Set to "auto" will follow original file's extension.
	 */
	'thumbnail_format' => 'jpg',


	/**
	 *  Image quality for thumbnails: 0-100.  Irrelevant if format is "gif"
	 */
	'thumbnail_quality' => 75,

);

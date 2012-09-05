<?php

use Laravel\Config;
use Laravel\Event;
use Laravel\File;
use Laravel\Str;



trait Thumbnailable {


	public function thumbnailable_init( $config=array() ) {

		// load the default configuration from the bundle
		// and override it with anything set in the model
		$temp = array_merge(
			Config::get('thumbnailable', Config::get('eloquent-thumbnailable::thumbnailable') ),
			$config
		);

		// store it in the config
		Config::set('thumbnailable.' . Str::lower(__CLASS__) , $temp);

		// set up event listeners so we can intercept saves and deletes
		Event::listen('eloquent.saving: ' . __CLASS__ , array($this,'thumbnailable_saving'));
		// Event::listen('eloquent.deleted: ' . __CLASS__ , array($this,'thumbnailable_deleted'));

	}


	/*
	 * The main "getter" function
	 */
	public function thumbnail( $field=null, $size=null )
	{

		// Find default field, if it's not given
		$field = $field ?: $this->thumbnailable_config('default_field');
		if (!$field) {
			throw new Thumbnailable_Exception("No thumbnail field given and no default defined.");
		}

		// is this a thumbnailable field?
		if ( !( $sizes = $this->thumbnailable_config('sizes', null, $field ) ) ) {
			throw new Thumbnailable_Exception("Field {$field} is not thumbnailable.");
		}

		// does the request size exist?
		if ( in_array($size, $sizes) ) {
			return $this->thumbnail_resize_image( $field, $size, true );
		}

		// size does not exist, so can we generate it on the fly?
		if ( $this->thumbnailable_config('strict_sizes') ) {
			throw new Thumbnailable_Exception("Can not get {$size} thumbnail for {$field}: not allowed.");
		}

		// generate it on the fly
		return $this->thumbnail_resize_image( $field, $size, true );

	}



	/*
	 * Method that gets fired when the eloquent model is saved.
	 * Handles the image upload, and pre-generates any needed thumbnails
	 */
	public function thumbnailable_saving()
	{

		// check that the model has fields configured for thumbnailing
		if ( !( $fields = static::thumbnailable_config('fields') ) ) {
			throw new Thumbnailable_Exception('No fields configured for thumbnailing');
		}

		// loop through each field to thumbnail
		foreach( $fields as $field=>$info ) {

			// find the storage directory
			if ( !( $directory = static::thumbnailable_config('storage_dir', null, $field ) ) ) {
				throw new Thumbnailable_Exception('No storage directory specified for '.__CLASS__.'->'.$field);
			}

			// create it if it doesn't exist
			if ( !File::mkdir($directory) ) {
				throw new Thumbnailable_Exception('Can not create directory '.$directory);
			}

			// if the model is new or this field has changed, we thumbnail it
			if ( !$this->exists || $this->changed($field) ) {

				// get the file upload info array
				$array = $this->get_attribute($field);

				// skip it if it's null, empty or not an array
				// (maybe because the field isn't required)
				if (!is_array($array) || empty($array)) {
					continue;
				}

				// make sure it's an uploaded file
				if ( !is_uploaded_file($array['tmp_name'])) {
					throw new Thumbnailable_Exception('File upload hijack attempt!');
				}

				// make sure it's an image file, and get the "proper" file extension
				if ( !( $ext = static::thumbnail_get_image_type( $array['tmp_name'] ) ) ) {
					throw new Thumbnailable_Exception('Uploaded '.__CLASS__.'->'.$field.' is not a valid image file');
				}

				// generate a random file name
				$newfile = static::thumbnail_newfile( $directory, $ext );

				// move uploaded file to new location
				if ( !move_uploaded_file($array['tmp_name'], $newfile) ) {
					throw new Thumbnailable_Exception('Could not move uploaded file to '.$newfile);
				}

				// update the eloquent model with the filename
				$this->set_attribute($field, $newfile);


				// if the thumbs are to be generated on save, do it
				if ( static::thumbnailable_config('on_save', false, $field ) ) {
					$this->thumbnail_generate_all($field);
				}

				// keep original?
				if ( ! (static::thumbnailable_config('keep_original', true, $field ) ) ) {
					File::delete( $newfile );
				}


			}
		}

		return true;

	}





	private function thumbnail_generate_all($field)
	{
		$sizes = static::thumbnailable_config('sizes', array(), $field );

		foreach($sizes as $size) {
			$this->thumbnail_generate($field, $size);
		}

	}


	private function thumbnail_generate($field, $size)
	{
		return $this->thumbnail_resize_image( $field, $size, false );
	}



	private function thumbnail_resize_image( $field, $size, $from_cache = true )
	{

		$format  = static::thumbnailable_config('thumbnail_format', 'png', $field );
		$new_file = $this->thumbnail_filename( $field, $size, $format );

		// if we already have a cached copy, return it
		if ($from_cache && File::exists($new_file)) {
			return $new_file;
		}

		$original_file = $this->get_attribute($field);
		if ( !File::exists($original_file) ) {
			throw new Thumbnailable_Exception("Can not generate {$size} thumbnail for {$field}: no original.");
		}

		$method  = static::thumbnailable_config('resize_method', 'crop', $field );
		$quality = static::thumbnailable_config('thumbnail_quality', '80', $field );
		$bg      = static::thumbnailable_config('thumbnail_background', null, $field );


		list($dst_width,$dst_height) = explode('x', Str::lower($size) );

		$src_image = imagecreatefromstring( File::get($original_file) );
		$dst_image = imagecreatetruecolor($dst_width, $dst_height);



		imagealphablending($src_image, true);
		imagealphablending($dst_image, false);
		imagesavealpha($dst_image, true);

		if ($bg) {
			$color = imagecolorallocate($dst_image, $bg[0], $bg[1], $bg[2] );
			imagefilledrectangle($dst_image, 0, 0, $dst_width, $dst_height, $color );
		} else if ($format=='png') {
			$color = imagecolorallocatealpha($dst_image, 127, 127, 127, 127);
			imagefilledrectangle($dst_image, 0, 0, $dst_width, $dst_height, $color );
		}


		$src_width  = imagesx($src_image);
		$src_height = imagesy($src_image);

		$src_ratio = $src_width / $src_height;
		$dst_ratio = $dst_width / $dst_height;

		// squished images, or ones that match the final aspect ratio
		// are simply resized into the new image
		if ($method=='resize' || $src_ratio==$dst_ratio) {

			imagecopyresampled($dst_image, $src_image, 0, 0, 0, 0, $dst_width, $dst_height, $src_width, $src_height);

		} else if ($method=='fit') {

			// method is 'fit'
			// old image fits entirely inside new size, maintaining ratio

			if ($src_ratio > $dst_ratio) {
				// pad top and bottom
				$height = $dst_width / $src_ratio;
				$offset_y = floor( ($dst_height-$height) / 2 );
				imagecopyresampled($dst_image, $src_image, 0, $offset_y, 0, 0, $dst_width, $height, $src_width, $src_height);
			} else {
				// pad left and right sides
				$width = $dst_height * $src_ratio;
				$offset_x = floor( ($dst_width-$width) / 2 );
				imagecopyresampled($dst_image, $src_image, $offset_x, 0, 0, 0, $width, $dst_height, $src_width, $src_height);
			}

		} else {

			// method is 'crop' or undefined
			// old image covers entire new size, maintaining ratio

			if ($src_ratio > $dst_ratio) {
				// crop left and right sides
				$width = $dst_ratio * $src_height;
				$offset_x = floor( ($src_width - $width) / 2);
				imagecopyresampled($dst_image, $src_image, 0, 0, $offset_x, 0, $dst_width, $dst_height, $width, $src_height);
			} else {
				// crop top and bottom sides
				$height = $src_height / $dst_ratio;
				$offset_y = floor( ($src_height - $height) / 2);
				imagecopyresampled($dst_image, $src_image, 0, 0, 0, $offset_y, $dst_width, $dst_height, $src_width, $height);
			}

		}

		$new_file = $this->thumbnail_filename( $field, $size, $format );

		switch ($format) {
			case 'gif':
				$success = imagegif($dst_image, $new_file);
				break;
			case 'jpg':
				$success = imagejpeg($dst_image, $new_file, $quality);
				break;
			case 'png':
				// map 0-100 quality to 9-0 compression
				$quality = round( 9 * (1 - ($quality/100)) );
				$success = imagepng($dst_image, $new_file, $quality);
				break;
		}

		if (!$success) {
			throw new Thumbnailable_Exception("Could not generate thumbnail {$new_file}");
		}

		return $new_file;

	}



	private function thumbnail_filename( $field, $size, $format=null )
	{
		$original_file = $this->get_attribute($field);

		if (!$format) {
			$format = static::thumbnailable_config('thumbnail_format', 'png', $field );
		}

		return rtrim( $original_file, File::extension( $original_file ) ) .
			Str::lower($size) . '.' . $format;
	}



	/**
	 * Generate random filename (and check it doesn't exist).
	 *
	 * @param  string  $directory
	 * @param  string  $extension
	 * @return string
	 */
	private static function thumbnail_newfile( $directory, $ext=null )
	{

		do {
			$filename = $directory . DS . Str::random(24) . ($ext ? '.' . $ext : '');

		} while ( File::exists( $filename ) );

		return $filename;

	}



	private static function thumbnail_get_image_type( $file )
	{
		foreach( array('jpg','png','gif') as $type ) {
			if (File::is($type, $file)) {
				return $type;
			}
		}
		return null;
	}



	private static function thumbnailable_config($key, $default=null, $field=null)
	{

		$class = Str::lower(__CLASS__);

		if ($field) {

			return Config::get(
				"thumbnailable.{$class}.fields.{$field}.{$key}",
				Config::get("thumbnailable.{$class}.{$key}",
					Config::get("thumbnailable.{$key}",
						$default
					)
				)
			);

		}

		return Config::get(
			"thumbnailable.{$class}.{$key}",
			Config::get("thumbnailable.{$key}",
				$default
			)
		);

	}


}



class Thumbnailable_Exception extends Exception {}


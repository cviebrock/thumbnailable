<?php

use Laravel\Config;
use Laravel\Event;
use Laravel\File;
use Laravel\Str;



trait Thumbnailable {


	public function thumbnailable_init() {

		// load the default configuration from the bundle
		// and override it with anything set in the application
		$config = array_merge(
			Config::get('eloquent-thumbnailable::thumbnailable'),
			Config::get('thumbnailable', array() )
		);

		// store it in the config for easy access
		Config::set('thumbnailable', $config);

		// set up event listeners so we can intercept saves and deletes
		Event::listen('eloquent.saving: ' . __CLASS__ , array($this,'thumbnailable_saving'));
		Event::listen('eloquent.deleted: ' . __CLASS__ , array($this,'thumbnailable_deleted'));

	}


	/*
	 * The main "getter" function
	 */
	public function thumbnail( $field=null, $size=null )
	{

		// Find default field, if it's not given
		$field = $field ?: $this->thumbnailable_config('default_field');
		if (!$field) {
			$fields = $this->thumbnailable_config('fields');
			if (count($fields)==1) {
				$field = head( array_keys($fields) );
			} else {
				throw new Thumbnailable_Exception("No thumbnail field given and no default defined.");
			}
		}

		// is this a thumbnailable field?
		if ( !$this->thumbnailable_config('fields', $field ) ) {
			throw new Thumbnailable_Exception("Field $field is not thumbnailable.");
		}

		// get all sizes
		$sizes = $this->thumbnailable_config('sizes',$field);

		// Find default size, if it's not given
		$size = $size ?: $this->thumbnailable_config('default_size', $field);
		if (!$size) {
			if (count($sizes)==1) {
				$size = head( $sizes );
			} else {
				throw new Thumbnailable_Exception("No thumbnail size given for $field and no default defined.");
			}
		}

		// are we asking for the original?
		if ($size==='original') {
			if ( static::thumbnailable_config('keep_original', $field ) ) {
				return $this->thumbnail_resize_image( $field, 'original' );
			}
			throw new Thumbnailable_Exception("Original image for $filed not kept.");
		}

		// are we asking for a size name instead of WxH dimensions?
		if ( array_key_exists($size, $sizes) ) {
			$size = $sizes[$size];
		}

		// does the requested size exist or can we generate it on the fly?
		if ( in_array($size, $sizes) || $this->thumbnailable_config('strict_sizes',$field) ) {
			return $this->thumbnail_resize_image( $field, $size );
		}

		throw new Thumbnailable_Exception("Can not get $size thumbnail for $field: strict_sizes enabled.");

	}


	/*
	 * Get the full path to a thumbnail image
	 */
	public function thumbnail_path( $field=null, $size=null )
	{
		$directory = static::thumbnailable_config('storage_dir', $field );
		return $directory . DS . $this->thumbnail( $field, $size );
	}


	/*
	 * Method that gets fired when the eloquent model is saved.
	 * Handles the image upload, and pre-generates any needed thumbnails
	 */
	public function thumbnailable_saving($model)
	{

		// check that the model has fields configured for thumbnailing
		if ( !( $fields = static::thumbnailable_config('fields') ) ) {
			throw new Thumbnailable_Exception("No fields configured for thumbnailing.");
		}

		// loop through each field to thumbnail
		foreach( $fields as $field=>$info ) {

			// find the storage directory
			if ( !( $directory = static::thumbnailable_config('storage_dir', $field ) ) ) {
				throw new Thumbnailable_Exception("No storage directory specified for ".__CLASS__."->$field.");
			}

			// create it if it doesn't exist
			if ( !File::mkdir($directory) ) {
				throw new Thumbnailable_Exception("Can not create directory $directory.");
			}

			// if the model is new or this field has changed, we thumbnail it
			if ( !$model->exists || $model->changed($field) ) {

				// get the PHP file upload info array
				$array = $model->get_attribute($field);

				// skip it if it's null, empty or not an array
				// (maybe because the field isn't required)
				if (!is_array($array) || empty($array)) {
					continue;
				}

				// make sure it's an uploaded file
				if ( !is_uploaded_file($array['tmp_name'])) {
					throw new Thumbnailable_Exception("File upload hijack attempt!");
				}

				// make sure it's an image file, and get the "proper" file extension
				if ( !( $ext = static::thumbnail_get_image_type( $array['tmp_name'] ) ) ) {
					throw new Thumbnailable_Exception("Uploaded ".__CLASS__."->$field is not a valid image file.");
				}

				// generate a random file name
				$newfile = static::thumbnail_newfile( $directory, $ext );

				// move uploaded file to new location
				if ( !move_uploaded_file($array['tmp_name'], $directory.DS.$newfile) ) {
					throw new Thumbnailable_Exception("Could not move uploaded file to $directory".DS."$newfile.");
				}

				// update the eloquent model with the filename
				$model->set_attribute($field, $newfile);


				// if the thumbs are to be generated on save, do it
				if ( static::thumbnailable_config('on_save', $field ) ) {
					$model->thumbnail_generate_all($field);
				}

				// keep original?
				if ( ! (static::thumbnailable_config('keep_original', $field ) ) ) {
					File::delete( $newfile );
				}


			}
		}

		return true;

	}


	/*
	 * Method that gets fired when the eloquent model is being deleted.
	 * Erases the original file and any generated thumbnails
	 */
	public function thumbnailable_deleted($model)
	{

		// check that the model has fields configured for thumbnailing
		if ( !( $fields = static::thumbnailable_config('fields') ) ) {
			return true;
		}


		// loop through each field to thumbnail
		foreach( $fields as $field=>$info ) {

			// find the storage directory
			if ( !( $directory = static::thumbnailable_config('storage_dir', $field ) ) ) {
				continue;
			}

			// original file
			$original_file = $model->get_attribute($field);

			// strip the extension
			$ext = File::extension($original_file);
			$basename = rtrim( $original_file, $ext );
			$len = strlen($basename);

			// iterate through the directory, looking for files that start with
			// the basename

			$iterator = new DirectoryIterator($directory);
			foreach ($iterator as $file) {
				if ($file->isFile() && strpos($file->getFilename(), $basename)===0) {

					if ( !File::delete($file->getPathName()) ) {
						throw new Thumbnailable_Exception("Could not delete ".$file->getPathName()."." );

					}
				}
			}

		}

		return true;

	}





	/*
	 * PRIVATE METHODS
	 */



	private function thumbnail_generate_all($field)
	{
		$sizes = static::thumbnailable_config('sizes', $field );

		if (!is_array($sizes)) {
			return;
		}

		foreach($sizes as $size) {
			$this->thumbnail_resize_image( $field, $size, false );
		}

	}



	private function thumbnail_resize_image( $field, $size, $use_cache = true )
	{

		$original_file = $this->get_attribute($field);

		// did we ask for the original?
		if ($size=='original') {
			return $original_file;
		}

		$format  = static::thumbnailable_config('thumbnail_format', $field );
		$new_file = $this->thumbnail_filename( $field, $size, $format );

		// if we already have a cached copy, return it
		if ($use_cache && File::exists($new_file)) {
			return $new_file;
		}

		$directory = static::thumbnailable_config('storage_dir', $field );
		if ( !File::exists($directory.DS.$original_file) ) {
			throw new Thumbnailable_Exception("Can not generate $size thumbnail for $field: no original.");
		}

		$src_image = imagecreatefromstring( File::get($directory.DS.$original_file) );

		list($dst_width,$dst_height) = explode('x', Str::lower($size) );
		$dst_image = imagecreatetruecolor($dst_width, $dst_height);

		imagealphablending($src_image, true);
		imagealphablending($dst_image, false);
		imagesavealpha($dst_image, true);

		$bg = static::thumbnailable_config('thumbnail_background', $field );

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

		$method  = static::thumbnailable_config('resize_method', $field );
		$quality = static::thumbnailable_config('thumbnail_quality', $field );

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
				$height = $src_width / $dst_ratio;
				$offset_y = floor( ($src_height - $height) / 2);
				imagecopyresampled($dst_image, $src_image, 0, 0, 0, $offset_y, $dst_width, $dst_height, $src_width, $height);
			}

		}

		$new_file = $this->thumbnail_filename( $field, $size, $format );

		switch ($format) {
			case 'gif':
				$success = imagegif($dst_image, $directory.DS.$new_file);
				break;
			case 'jpg':
				$success = imagejpeg($dst_image, $directory.DS.$new_file, $quality);
				break;
			case 'png':
				// map 0-100 quality to 9-0 compression
				$quality = round( 9 * (1 - ($quality/100)) );
				$success = imagepng($dst_image, $directory.DS.$new_file, $quality);
				break;
		}

		imagedestroy($src_image);
		imagedestroy($dst_image);

		if (!$success) {
			throw new Thumbnailable_Exception("Could not generate thumbnail $new_file.");
		}

		return $new_file;

	}



	private function thumbnail_filename( $field, $size, $format=null )
	{
		$original_file = $this->get_attribute($field);

		if (!$format) {
			$format = static::thumbnailable_config('thumbnail_format', $field );
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
			$filename = Str::random(24) . ($ext ? '.' . $ext : '');
		} while ( File::exists( $directory . DS . $filename ) );
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



	private static function thumbnailable_config($key, $field=null)
	{

		$class = Str::lower(__CLASS__);

		if ($field) {
			return Config::get("thumbnailable.{$class}.fields.{$field}.{$key}") ?:
				Config::get("thumbnailable.{$class}.{$key}") ?:
				Config::get("thumbnailable.{$key}") ?:
				null;
		}

		return Config::get("thumbnailable.{$class}.{$key}") ?:
			Config::get("thumbnailable.{$key}") ?:
			null;

	}


}



class Thumbnailable_Exception extends Exception {}


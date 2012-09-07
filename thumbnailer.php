<?php

/**
 * Easy thumbnailing for your Eloquent models.
 *
 * @package Thumbnailable
 * @version 1.0.1
 * @author  Colin Viebrock <colin@viebrock.ca>
 * @link    http://github.com/cviebrock/thumbnailable
 */


class Thumbnailer {


	/**
	 * Get a particular configuration value for a given model/field/key.
	 *
	 * In order of preference, we look in
	 *
	 * 1a. model_config.fields.FIELD.KEY
	 * 1b. model_config.KEY
	 * 2. app_config.KEY
	 * 3. default_config.KEY
	 *
	 * @param  Model   &$model
	 * @param  string  $key
	 * @param  string  $field
	 * @param  string  $default
	 * @return mixed
	 */
	private static function config( &$model, $key, $field=null, $default=null )
	{

		// 1. check the model
		if ( isset( $model::$thumbnailable ) ) {

			// model_config.fields.FIELD.KEY
			if ( $field ) {
				$value = array_get( $model::$thumbnailable, "fields.$field.$key", null );
				if ( !is_null( $value ) ) return $value;
			}

			// model_config.KEY

			$value = array_get( $model::$thumbnailable, $key, null );
			if ( !is_null( $value ) ) return $value;

		}


		// 2. app_config.KEY

		$value = Config::get( "thumbnailable.$key", null );
		if ( !is_null( $value ) ) return $value;


		// default_config.KEY

		return Config::get( "thumbnailable::thumbnailable.$key", $default );

	}


	/**
	 * Method that gets fired when the eloquent model is saved.
	 * Handles the image upload, and pre-generates any needed thumbnails
	 *
	 * @param  Model   $model
	 * @return bool
	 */
	public static function saving( $model )
	{

		$class = get_class($model);

		// check that the model has fields configured for thumbnailing
		if ( !( $fields = static::config( $model, 'fields' ) ) ) {
			throw new \Exception("No fields configured for thumbnailing.");
		}

		// loop through each field to thumbnail
		foreach( $fields as $field=>$info ) {

			// find the storage directory
			if ( !( $directory = static::config( $model, 'storage_dir', $field ) ) ) {
				throw new \Exception("No storage directory specified for $class\->$field.");
			}

			// create it if it doesn't exist
			if ( !File::mkdir($directory) ) {
				throw new \Exception("Can not create directory $directory.");
			}

			// if the model is new or this field has changed, we thumbnail it
			if ( !$model->exists || $model->changed($field) ) {

				// get the PHP file upload info array
				$array = $model->get_attribute($field);

				// skip it if it's null, empty or not an array
				// (maybe because the field isn't required)
				if ( !is_array($array) || empty($array) ) {
					continue;
				}

				// make sure it's an uploaded file
				if ( !is_uploaded_file( $array['tmp_name'] ) ) {
					throw new \Exception("File upload hijack attempt!");
				}

				// make sure it's an image file, and get the "proper" file extension
				if ( !( $ext = static::image_type( $array['tmp_name'] ) ) ) {
					throw new \Exception("Uploaded $class\->$field is not a valid image file.");
				}

				// generate a random file name
				$newfile = static::newfile( $directory, $ext );

				// move uploaded file to new location
				if ( !move_uploaded_file( $array['tmp_name'], $directory . DS . $newfile ) ) {
					throw new \Exception("Could not move uploaded file to $directory" . DS . "$newfile.");
				}

				// update the eloquent model with the filename
				$model->set_attribute( $field, $newfile );


				// if the thumbs are to be generated on save, do it
				if ( static::config( $model, 'on_save', $field ) ) {
					static::generate_all( $model, $field );
				}

				// keep original?
				if ( !( static::config( $model, 'keep_original', $field ) ) ) {
					File::delete($newfile);
				}


			}
		}

		return true;

	}


	/**
	 * Method that gets fired when the eloquent model is being deleted.
	 * Erases the original file and any generated thumbnails
	 *
	 * @param  Model   $model
	 * @return bool
	 */
	public static function deleted( $model )
	{

		// check that the model has fields configured for thumbnailing
		if ( !( $fields = static::config( $model, 'fields' ) ) ) {
			return true;
		}


		// loop through each field to thumbnail
		foreach( $fields as $field=>$info ) {

			// find the storage directory
			if ( !( $directory = static::config( $model, 'storage_dir', $field ) ) ) {
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
			foreach( $iterator as $file ) {
				if ($file->isFile() && strpos( $file->getFilename(), $basename )===0 ) {

					if ( !File::delete( $file->getPathName() ) ) {
						throw new \Exception("Could not delete ".$file->getPathName()."." );

					}
				}
			}

		}

		return true;

	}


	/**
	 * Get the filename of a resized image, generating it if required
	 *
	 * @param  Model   &$model
	 * @param  string  $field
	 * @param  string  $size
	 * @return string
	 */
	public static function get( &$model, $field=null, $size=null )
	{

		// Find default field, if it's not given
		$field = $field ?: Thumbnailer::config( $model, 'default_field' );
		if (!$field) {
			$fields = Thumbnailer::config( $model, 'fields' );
			if ( count($fields)==1 ) {
				$field = head( array_keys($fields) );
			} else {
				throw new Exception("No thumbnail field given and no default defined.");
			}
		}

		// is this a thumbnailable field?
		if ( !Thumbnailer::config( $model, 'fields', $field ) ) {
			throw new Exception("Field $field is not thumbnailable.");
		}

		// get all sizes
		$sizes = Thumbnailer::config( $model, 'sizes', $field );

		// Find default size, if it's not given
		$size = $size ?: Thumbnailer::config( $model, 'default_size', $field );
		if ( !$size ) {
			if ( count($sizes)==1 ) {
				$size = head( $sizes );
			} else {
				throw new Exception("No thumbnail size given for $field and no default defined.");
			}
		}

		// are we asking for the original?
		if ( $size==='original' ) {
			if ( static::config( $model, 'keep_original', $field ) ) {
				return static::generate( $model, $field, 'original' );
			}
			throw new Exception("Original image for $filed not kept.");
		}

		// are we asking for a size name instead of WxH dimensions?
		if ( array_key_exists( $size, $sizes ) ) {
			$size = $sizes[$size];
		}

		// does the requested size exist or can we generate it on the fly?
		if ( !in_array( $size, $sizes ) && static::config( $model, 'strict_sizes', $field ) ) {
			throw new Exception("Can not get $size thumbnail for $field: strict_sizes enabled.");
		}

		return static::generate( $model, $field, $size );

	}


	/**
	 * Get the full path to a resized image
	 *
	 * @param  Model   &$model
	 * @param  string  $field
	 * @param  string  $size
	 * @return string
	 */
	public static function get_path( &$model, $field=null, $size=null )
	{
		$directory = static::config( $model, 'storage_dir', $field );
		return $directory . DS . static::get( $model, $field, $size );
	}


	/**
	 * Generate all resized images for a give model and field
	 *
	 * @param  Model   &$model
	 * @param  string  $field
	 * @return bool
	 */
	private static function generate_all( &$model, $field )
	{
		$sizes = static::config( $model, 'sizes', $field );

		if ( !is_array( $sizes ) ) {
			return true;
		}

		foreach( $sizes as $size ) {
			static::generate( $model, $field, $size, false );
		}

		return true;

	}


	/**
	 * Generate a resized image (or pull from cache) and return the file name
	 *
	 * @param  Model   &$model
	 * @param  string  $field
	 * @param  string  $size
	 * @param  bool    $use_cache
	 * @return string
	 */
	private static function generate( &$model, $field, $size, $use_cache = true )
	{

		$original_file = $model->get_attribute($field);

		// did we ask for the original?
		if ( $size=='original' ) {
			return $original_file;
		}

		$directory = static::config( $model, 'storage_dir', $field );
		$format    = static::config( $model, 'thumbnail_format', $field );

		$new_file = rtrim( $original_file, File::extension( $original_file ) ) .
			Str::lower($size) . '.' . $format;


		// if we already have a cached copy, return it
		if ( $use_cache && File::exists( $directory . DS . $new_file ) ) {
			return $new_file;
		}

		if ( !File::exists( $directory . DS . $original_file ) ) {
			throw new \Exception("Can not generate $size thumbnail for $field: no original.");
		}

		list( $dst_width, $dst_height ) = explode('x', Str::lower($size) );

		$method  = static::config( $model, 'resize_method', $field );
		$quality = static::config( $model, 'thumbnail_quality', $field );

		Bundle::start('resizer');

		$success = Resizer::open( $directory . DS . $original_file )
			->resize( $dst_width, $dst_height, $method )
			->save( $directory . DS . $new_file, $quality );

		if ( !$success ) {
			throw new \Exception("Could not generate thumbnail $new_file.");
		}

		return $new_file;

	}


	/**
	 * Generate random filename (and check it doesn't exist).
	 *
	 * @param  string  $directory
	 * @param  string  $extension
	 * @return string
	 */
	private static function newfile( $directory, $ext=null )
	{
		do {
			$filename = Str::random(24) . ( $ext ? '.' . $ext : '' );
		} while ( File::exists( $directory . DS . $filename ) );
		return $filename;
	}


	/**
	 * Check if a file is one of the valid image types.
	 * If so, return which one it is.  If not, return null.
	 *
	 * @param  string  $file
	 * @return mixed
	 */
	private static function image_type( $file )
	{
		foreach( array( 'jpg', 'png', 'gif' ) as $type ) {
			if ( File::is( $type, $file ) ) {
				return $type;
			}
		}
		return null;
	}

}

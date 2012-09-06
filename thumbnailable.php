<?php

/**
 * Easy thumbnailing for your Eloquent models.
 *
 * @package Eloquent-Thumbnailable
 * @version 0.2
 * @author  Colin Viebrock <colin@viebrock.ca>
 * @link    http://github.com/cviebrock/eloquent-thumbnailable
 */


trait Thumbnailable {

	/**
	 * Get the filename of a resized image, generating it if required
	 *
	 * @param  string  $field
	 * @param  string  $size
	 * @return string
	 * @see    Thumbnailer::get
	 */
	public function thumbnail( $field=null, $size=null )
	{
		return Thumbnailer::get( $this, $field, $size );
	}


	/**
	 * Get the full path to a resized image
	 *
	 * @param  string  $field
	 * @param  string  $size
	 * @return string
	 * @see    Thumbnailer::get
	 */
	public function thumbnail_path( $field=null, $size=null )
	{
		return Thumbnailer::get_path( $this, $field, $size );
	}

}

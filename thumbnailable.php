<?php

/**
 * Easy thumbnailing for your Eloquent models.
 *
 * @package Thumbnailable
 * @version 1.0.1
 * @author  Colin Viebrock <colin@viebrock.ca>
 * @link    http://github.com/cviebrock/thumbnailable
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

<?php

/**
 * Easy thumbnailing for your Eloquent models.
 *
 * @package Thumbnailable
 * @version 1.7
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
	 * @see    Thumbnailer::get_path
	 */
	public function thumbnail_path( $field=null, $size=null )
	{
		return Thumbnailer::get_path( $this, $field, $size );
	}

	/**
	 * Get the URL to a resized image
	 *
	 * @param  string  $field
	 * @param  string  $size
	 * @return string
	 * @see    Thumbnailer::get_url
	 */
	public function thumbnail_url( $field=null, $size=null )
	{
		return Thumbnailer::get_url( $this, $field, $size );
	}


	/**
	 * Get the <img> tag for a resized image
	 *
	 * @param  string  $field
	 * @param  string  $size
	 * @param  string  $alt
	 * @param  array   $attributes
	 * @return string
	 * @see    Thumbnailer::get_url
	 */
	public function thumbnail_image( $field=null, $size=null, $alt=null, $attributes=array() )
	{
		if ( $url = Thumbnailer::get_url( $this, $field, $size ) ) {
			return HTML::image( $url, $alt, $attributes );
		}
	}

}

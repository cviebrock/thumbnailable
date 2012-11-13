<?php

/**
 * Easy thumbnailing for your Eloquent models.
 *
 * @package Thumbnailable
 * @version 1.2
 * @author  Colin Viebrock <colin@viebrock.ca>
 * @link    http://github.com/cviebrock/thumbnailable
 */


Autoloader::map(array(
	'Thumbnailable' => __DIR__ . DS . 'thumbnailable.php',
	'Thumbnailer'   => __DIR__ . DS . 'thumbnailer.php'
));


// Listen to the Eloquent save/delete events so we can do our thing:

Event::listen('eloquent.saving',  array('Thumbnailer','saving') );
Event::listen('eloquent.updated', array('Thumbnailer','updated') );
Event::listen('eloquent.deleted', array('Thumbnailer','deleted') );

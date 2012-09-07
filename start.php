<?php

Autoloader::map(array(
	'Thumbnailable' => __DIR__ . DS . 'thumbnailable.php',
	'Thumbnailer'   => __DIR__ . DS . 'thumbnailer.php'
));


Event::listen('eloquent.saving',  array('Thumbnailer','saving') );
Event::listen('eloquent.deleted', array('Thumbnailer','deleted') );

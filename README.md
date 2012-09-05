# Eloquent-Thumbnailable

Easy image handling in your Laravel models!



## Installing the Bundle

Install the bundle using Artisan:

```
php artisan bundle::install eloquent-thumbnailable
```

Update your `application/bundles.php` file with:

```php
'eloquent-thumbnailable' => array( 'auto' => true ),
```

## Updating your Models

You need to include the Thumbnailable trait in your model, and create a constructor
to initialize the thumbnailable configuration.

```php
class User extends Eloquent
{

	use Thumbnailable;

	public function __construct($attributes = array(), $exists = false)
	{
		$this->thumbnailable_init();

		// any other constructor code can go here ...
		// but be sure load the parent constructor
		parent::__construct($attributes, $exists);

	}

}
```

That's it ... your model is now "thumbnailable"!


## Using the Class

Saving a model is easy:

```php
$user = new User(array(
	'name'     => Input::get('name'),
	'headshot' => Input::file('headshot'),
));

$user->save();
```

Retrieving the thumbnails:

```php
// return the path to the default thumbnail for the "headshot" file
$user->thumbnail('headshot');

// return the path to the "small" thumbnail
$user->thumbnail('headshot', 'small');

// return the path to a custom-sized thumbnail
$user->thumbnail('headshot', '75x75');
```


## Configuration

Configuration was designed to be as flexible as possible.  You can set up
defaults for all of your Eloquent models, defaults for all the image fields
within a model, or even override those settings for individual images.

By default, configuration can be set in the `application/config/thumbnailable.php`
file.  Here is an example configuration, with all the settings shown:

```php
return array(

	'storage_dir'          => path('storage'). 'uploads' . DS . 'thumbnails',
	'keep_original'        => true,
	'strict_sizes'         => true,
	'on_save'              => true,
	'resize_method'        => 'crop',
	'thumbnail_format'     => 'png',
	'thumbnail_quality'    => 80,
	'thumbnail_background' => array( 255, 255, 255),

	'user' => array(
		'default_field' => 'headshot',
		'fields' => array(
			'headshot' => array(
				'default_size' => 'small',
				'sizes' => array(
					'small'  => '50x50',
					'medium' => '100x100',
					'large'  => '300x300',
				),
			),
			'alternate' => array(
				'strict_size' => false,
			),
		),
	),

	'dog' => array(
		'keep_original' => false,
		'fields' => array(
			'image' => array(
				'sizes' => array(
					'small'  => '50x50',
				),
			),
		),
	),

);
```

Let's go through these.  The first set of options define some of the global thumbnail settings
(although they can all be overridden as you will see).

`storage_dir` is the directory where the images and generated thumbnails are stored.
The directory needs to be writable by the webserver (but the bundle will do its
best to create any missing directories for you).

`keep_original` is a boolean that determines whether to keep the original (un-thumbnailed)
image file after processing.  Maybe you really only need thumbnails, so save storage space
by discarding the original.

`strict_sizes` is a boolean.  If it's set to true, then you can only generate and access
thumbnails of the pre-determined sizes (defined later on in the configuration).  If it's
set to false, then you can generate thumbnails of any size at any time.

`on_save` is a boolean.  If true, then saving your Eloquent model will trigger
thumbnail generation of all the pre-determined sizes.  This way, you will have instant
access to the resized images.  If false, then thumbnails are only generated when they
are requested (you must set `keep_original` to true in this case).

`resize_method` is one of "crop", "fit" or "resize".  "crop" will resize and crop the image
so that it completely fills the thumbnail, but maintains the original image's aspect
ratio.  "fit" will resize the image so that it fits inside the thumbnail (padding it,
if required), and also maintains aspect ratio.  "resize" simply resizes the original image
to the dimensions of the thumbnail with no regard for aspect ratios.

`thumbnail_format` is one of "png", "jpg" or "gif" and determines what the final image
format of the thumbnails should be.

`thumbnail_quality` is an integer from 0 to 100 and determines (for PNG and JPG images)
the quality or compression of the thumbnail (0 is lowest quality/highest compression,
100 is best quality/no compression).

`thumbnail_background` is an array of (R,G,B) integers.  If `resize_method` is "fit", and
the resulting thumbnail requires padding of the original image, then this is the background
color to use for the padding.  You can also set `thumbnail_background` to a null value
which will give you transparent padding (only if `thumbnail_format` is "png").

The rest of the configuration elements are keys for each of the models in your application
that require thumbnailing.  So the `user` array defines how thumbnailing works for the
User model, `dog` for the Dog model, etc..  Each of these model configurations can
override any of the global settings, if needed.  Looking at the example above, we
have set `keep_original` to true, but for the Dog model thumbnails, we have changed it to false.

Each of the model configurations has a few specific settings as well.

`fields` is an array of which attributes in the model (or fields in the database)
represent the images to be thumbnailed.  Looking at our User model, we have thumbnail images
for "headshot" and "alternate".  When generating your database, these should be string
fields (since they will store the path to the images), e.g.

```php
Schema::create('users', function($table)
{
	$table->increments('id');
	$table->string('name');
	$table->string('headshot');
	$table->string('alternate');
	$table->timestamps();
});
```

Within each element of `fields` array is where we define what thumbnail sizes to generate,
and what friendly names to give them.  Looking at our example again:

```php
	'user' => array(
		'default_field' => 'headshot',
		'fields' => array(
			'headshot' => array(
				'default_size' => 'small',
				'sizes' => array(
					'small'  => '50x50',
					'medium' => '100x100',
					'large'  => '300x300',
				),
			),
			'alternate' => array(
				'strict_size' => false,
			),
		),
	),
```

For the User->headshot field, we are generating 50x50, 100x100 and 300x300 thumbnails.
We can access the small one this way:

```php
$user->thumbnail('headshot','50x50');
```

What happens if you decide that 50x50 is too big and you really need to change those
dimensions?  It would be a pain to search your entire application for instances of that,
so you can (and should!) refer to the image size by name (i.e. the key in that array):

```php
$user->thumbnail('headshot','small');
```
We make it easier as well: you can define a `default_size` for a particular field, so the
following code will also return the small thumbnail image:

```php
$user->thumbnail('headshot');
```

Need it even easier?  There is a `default_field` configuration for the model.  So this
code will also return the small headshot file:

```php
$user->thumbail();
```

Our model has an "alternate" field as well (maybe a secondary photo the users upload).
We have set `strict_size` to false and not given any predetermined sizes.  This means you can
request any size thumbnail for that field, although you need to do it explicitly:

```php
$user->thumbnail('alternate', '320x240');
```

Let's take a quick look at the Dog model's configuration again:

```php
	'dog' => array(
		'keep_original' => false,
		'fields' => array(
			'image' => array(
				'sizes' => array(
					'small'  => '50x50',
				),
			),
		),
	),
```

In this case, we aren't keeping the original image, so any file that is uploaded is
thumbnailed to 50x50 (since the global `on_save` is true).  We also only have one field
with one size, so we don't need to define a `default_field` or `default_size`.
All of the following lines of code will return the same thing:

```php
$dog->thumnail('image','50x50');
$dog->thumnail('image','small');
$dog->thumnail('image');
$dog->thumnail();
```

## Credits

The idea for this bundle came from using Symfony's
[sfDoctrineThumbnailablePlugin](https://github.com/ubermuda/sfDoctrineThumbnailablePlugin) ...
back when I built sites with Symfony.  It was a quick and "automagic" way to get image
thumbnailing built into my Symfony Doctrine models.  I tried to keep the configuration
for the Eloquent-Thumbnailable bundle fairly similar to the SF plugin, but the bundle's code
is from scratch.

Image resizing code is compiled from several places on the internet, but mostly built
from scratch using good ol' math skills.

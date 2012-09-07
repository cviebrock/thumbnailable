# Thumbnailable

Easy thumbnailing for your Eloquent models!



## Installing the Bundle

Install the bundle using Artisan:

```
php artisan bundle::install thumbnailable
```

Update your `application/bundles.php` file with:

```php
'thumbnailable' => array( 'auto' => true ),
```

## Updating your Models

For PHP 5.4 users, you simple need to include the Thumbnailable trait in your
model, and define the configuration for your model in a  public static
property `$thumbnailable`.

```php
class User extends Eloquent
{

	use Thumbnailable;

	public static $thumbnailable = array(
		'default_field' => 'headshot',
		'fields' => array(
			'headshot' => array(
				'default_size' => 'small',
				'sizes' => array(
					'small'  => '50x50',
					'medium' => '100x100',
					'large'  => '200x200',
				)
			),
			'bodyshot' => array(
				'sizes' => array(
					'full' => '240x360'
				)
			)
		)
	);

}
```

That's it ... your model is now "thumbnailable"!

** Please see the [note](#note) if you are using a version of PHP prior to
5.4. **


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

And when you're all done, deleting is a piece of cake too:

```php
$user->delete();
```

The bundle will automatically delete the original uploaded file and all
thumbnails associated with it.


## Global Configuration

Configuration was designed to be as flexible as possible.  You can  set up
defaults for all of your Eloquent models, defaults for all the  image fields
within a model, or even override those settings for  individual images.

By default, global configuration can be set in the
`application/config/thumbnailable.php` file.  If a configuration isn't set,
then the bundle defaults from `bundles/thumbnailable/config/thumbnailable.php`
are used.  Here is an example configuration, with all the settings shown:

```php
return array(

	'storage_dir'       => path('storage'). 'uploads' . DS . 'thumbnails',
	'keep_original'     => true,
	'strict_sizes'      => true,
	'on_save'           => true,
	'resize_method'     => 'crop',
	'thumbnail_format'  => 'png',
	'thumbnail_quality' => 80,

);
```

`storage_dir` is the directory where the images and generated thumbnails are
stored. The directory needs to be writable by the webserver (but the bundle
will do its best to create any missing directories for you).

`keep_original` is a boolean that determines whether to keep the original (un-
thumbnailed) image file after processing.  Maybe you really only need
thumbnails, so save storage space by discarding the original.

`strict_sizes` is a boolean.  If it's set to true, then you can only generate
and access thumbnails of the pre-determined sizes (defined later on in the
configuration).  If it's set to false, then you can generate thumbnails of any
size at any time.

`on_save` is a boolean.  If true, then saving your Eloquent model will trigger
thumbnail generation of all the pre-determined sizes.  This way, you will have
instant access to the resized images.  If false, then thumbnails are only
generated when they are requested (you must set `keep_original` to true in
this case).

`resize_method` is one of the strings "exact", "portrait", "landscape",
"auto", "fit" or "crop". These are passed to the Resizer class.

* "crop" will resize your image so that it completely fills the thumbnail, but
  maintains the original image's aspect ratio.

* "fit" will resize the image so that it fits inside the thumbnail (padding
  it, if necessary)

* "exact" simply resizes the original image to the dimensions of the thumbnail
  with no regard for aspect ratios.

The remaining options of "portrait", "landscape" and "auto" can produce images
larger than the dimensions you give, so their use is discourage.

`thumbnail_format` is one of "png", "jpg" or "gif" and determines what the
final image format of the thumbnails should be.

`thumbnail_quality` is an integer from 0 to 100 and determines (for PNG and
JPG images) the quality or compression of the thumbnail (0 is lowest
quality/highest compression, 100 is best quality/no compression).


## Model Configuration

The configuration for each model that uses the bundle is done in the model
itself.  Let's look at our User class again:

```php
class User extends Eloquent
{

	use Thumbnailable;

	public static $thumbnailable = array(
		'default_field' => 'headshot',
		'fields' => array(
			'headshot' => array(
				'default_size' => 'small',
				'sizes' => array(
					'small'  => '50x50',
					'medium' => '100x100',
					'large'  => '200x200',
				)
			),
			'bodyshot' => array(
				'strict_sizes' => false,
				'sizes' => array(
					'full' => '240x360'
				)
			)
		)
	);

}
```

`fields` is an array of which attributes in the model (or fields in the
database) represent the images to be thumbnailed.  Looking at our User model,
we have thumbnail images for "headshot" and "bodyshot".  When generating your
database, these should be string fields (since they will store the path to the
images), e.g.:

```php
Schema::create('users', function($table)
{
	$table->increments('id');
	$table->string('name');
	$table->string('headshot');
	$table->string('bodyshot');
	$table->timestamps();
});
```

Within each element of `fields` array is where we define what thumbnail sizes
to generate, and what friendly names to give them.  For our User->headshot
field, we are generating 50x50, 100x100 and 200x200 thumbnails.  We can access
the small one this way:

```php
$user->thumbnail('headshot','50x50');
```

But what happens if you decide that 50x50 is too big and you really need to
change those dimensions?  It would be a pain to search your entire application
for instances of that, so you can (and should!) refer to the image size by
name (i.e. the key in that array):

```php
$user->thumbnail('headshot','small');
```

We make it easier as well: you can define a `default_size` for a particular
field, so the following code will also return the small thumbnail image:

```php
$user->thumbnail('headshot');
```

Still too much typing?  There is a `default_field` configuration for the
model.  So this code will also return the small headshot file:

```php
$user->thumbail();
```

Our model has a "bodyshot" field as well (maybe a secondary photo the users
upload). We have set `strict_sizes` to false and not given any predetermined
sizes.  This means you can request any size thumbnail for that field, although
you need to do it explicitly:

```php
$user->thumbnail('bodyshot', '200x100');
```

And all of the global configuration values can be redefined on a model-by-
model -- or even field-by-field -- case.

Let's take a quick look at an other example model:

```php
class Dog extends Eloquent
{

	use Thumbnailable;

	public static $thumbnailable = array(
		'keep_original' => false,
		'fields' => array(
			'image' => array(
				'sizes' => array(
					'small'  => '50x50',
				),
			),
		),
	);

}
```

Here, we aren't keeping the original image, so any file that is uploaded is
thumbnailed to 50x50 (since the global `on_save` is true).  We also only have
one field with one size, so we don't need to define a `default_field` or
`default_size`. All of the following lines of code will return the same thing:

```php
$dog->thumbnail('image','50x50');
$dog->thumbnail('image','small');
$dog->thumbnail('image');
$dog->thumbnail();
```


## Out-of-Model Configuration

Another option for configuring your models is to handle it all in the
`application/config/thumbnailable.php` file:


```php
return array(

	'storage_dir'       => path('storage'). 'uploads' . DS . 'thumbnails',
	'keep_original'     => true,
	'strict_sizes'      => true,
	'on_save'           => true,
	'resize_method'     => 'crop',
	'thumbnail_format'  => 'png',
	'thumbnail_quality' => 80,

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

This puts all the configuration for your application in one place, which might
be your preference.


## Credits

The idea for this bundle came from using Symfony's
[sfDoctrineThumbnailablePlugin](https://github.com/ubermuda/sfDoctrineThumbnailablePlugin)
... back when I built sites with Symfony.  It was a quick and "automagic" way
to get image thumbnailing built into my Symfony Doctrine models.  I tried to
keep the configuration for the Thumbnailable bundle fairly similar to the SF
plugin, but the bundle's code is from scratch.

The bundle depends on having the [Resizer](http://bundles.laravel.com/bundle/resizer)
bundle installed.  Artisan should handle the dependency for you.


## NOTE

Because this bundles uses PHP traits, it will only work if you are running PHP
5.4.

If you are running PHP 5.3, you can still use the bundle, but you will need to
define your models a bit differently: basically, copy the two trait methods
from `thumbnailable.php` into your model, and skip the `use Thumbnailable`
setting.  e.g.:

```php
class User extends Eloquent
{

	public function thumbnail( $field=null, $size=null )
	{
		return Thumbnailer::get( $this, $field, $size );
	}

	public function thumbnail_path( $field=null, $size=null )
	{
		return Thumbnailer::get_path( $this, $field, $size );
	}

	public static $thumbnailable = array(
		// your regular config settings
	);

}
```

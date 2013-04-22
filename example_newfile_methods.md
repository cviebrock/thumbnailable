# Example Filename Methods

With version 1.7.1, Thumbnailable no longer enforces a naming scheme on the uploaded
files.  Previously, an uploaded file was given a random 24-character filename, the
appropriate file extension, and stored in the `storage_directory`, e.g.:

```
/storage/uploads/0123456789abcdefghijklmn.jpg
```

Now, the bundle looks for a static method in your Eloquent model and uses the result of
that to determine the new filename.  See the README.md file for how to configure this
in your model class.

If it's not defined, the default behaviour is the same: a random 24-character filename.

Here is the original code and some other examples that you could drop into your models,
depending on how you'd like to rename your files.  Keep in mind that any class you author
should:

- confirm that the file doesn't already exist, and
- return the full path relative to the `storage_directory` that's passed to the method.

Feel free to contribute more examples with a pull request!



### Random 24-character filename, no sub-directories (default method)

```php
/*
 *  e.g. /storage_dir/0123456789abcdefghijklmn.jpg
 */
private static function newfile( $original_name, $directory, $ext, $field )
{
	do {
		$filename = Str::random(24) . ( $ext ? '.' . $ext : '' );
	} while ( File::exists( $directory . DS . $filename ) );
	return $filename;
}
```


### Random 24-character filename, 4 levels of sub-directories based on filename


```php
/*
 *  e.g. /storage_dir/0/01/012/0123/0123456789abcdefghijklmn.jpg
 */
public static function thumbnailer_newfile( $original, $directory, $ext, $field ) {
	do {
		$filename = Str::random(24) . ( $ext ? '.' . $ext : '' );
		$newdir = substr($filename,0,1) . DS .
			substr($filename,0,2) . DS .
			substr($filename,0,3) . DS .
			substr($filename,0,4);
	} while ( File::exists( $directory . DS . $newdir . DS . $filename ) );
	return $newdir . DS . $filename;
}
```


### Random filename, use classname and field directories


```php
/*
 *  e.g. /storage_dir/users/headshots/0123456789abcdefghijklmn.jpg
 */
public static function thumbnailer_newfile( $original, $directory, $ext, $field ) {
	do {
		$filename = Str::random(24) . ( $ext ? '.' . $ext : '' );
		$newdir = Str::slug( get_called_class() ) . DS . Str::slug( $field );
	} while ( File::exists( $directory . DS . $newdir . DS . $filename ) );
	return $newdir . DS . $filename;
}
```


### Keep existing filename, use classname and random subdirectory


```php
/*
 *  e.g. /storage_dir/users/012345/original.jpg
 */
public static function thumbnailer_newfile( $original, $directory, $ext, $field ) {
	do {
		$newdir = Str::slug( get_called_class() ) . DS . Str::random(6);
	} while ( File::exists( $directory . DS . $newdir ) );
	return $newdir . DS . $original;
}
```

# Changelog


### Version 1.7 -- 17-Apr-2013

- `newfile_method` option, which allows you to define a static method in your model
  that will generate the storage filename for uploaded images -- see [README.md]
  for an example.  This provides a solution for issue #8 (@markwu).
- Better handling for when image fields are empty (i.e. the image isn't a required
	attribute in the model, or is a nullable field in the DB).


### Version 1.6 -- 10-Apr-2013

- Per-size configuration - view the README.md for details
	(suggested in issue #17 by @ehsanquddusi).


### Version 1.5 -- 05-Apr-2013

- Fix issue #15 - File upload hijack attempt error when no upload is made
  (thanks @iwiznia, although I used different code to fix it).
- Fix issue #19 - Use "/" instead of `DS` when building URLs (thanks @ehsanquddusi).


### Version 1.4 -- 17-Dec-2012

- Register Resizer bundle automatically (thanks @sahanz).
- Fix bug where bundle wouldn't delete files when model was deleted (thanks @danielboggs).


### Version 1.3 -- 29-Nov-2012

- Add `thumbnail_format` option "auto", which will reuse the file format/extension
  of the original image (thanks @markwu).
- Added `base_url` config to define base URL route to thumbnail images.
- `thumbnail_url()` method to return full URL to a thumbnail.
- `thumbnail_image()` method to return HTML tag for a thumbnail.


### Version 1.2 -- 13-Nov-2012

- Old thumbnails are now removed when a model is updated with a new image (thanks @markwu).


### Version 1.1 -- 12-Sep-2012

- Fix issue where all models are assumed to be thumbnailable.


### Version 1.0.1 -- 07-Sep-2012

- Fix typo (was too eager to release).
- Change default `thumbnail_format` config to "jpg".


### Version 1.0 -- 07-Sep-2012

- Initial release.
- Name change to Thumbnailable.
- Reworked internals:
	- Use Resizer bundle to do the actual image manipulation.
	- Remove `thumbnail_background` config option.
	- Move logic from trait into static Thumbnailer class.
	- Trait methods can easily be duplicate in model for PHP 5.3 users.
- Global configuration is done in `application/config/thumbnailable.php` and
  model-level configuration is done in model.


### Version 0.2 -- 05-Sep-2012

- Fix logic flaw with `strict_sizes`.
- Add the ability to set thumbnail configuration in the model itself.


### Version 0.1 -- 05-Sep-2012

- Initial (alpha) release.

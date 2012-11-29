# Changelog


### Version 0.1 -- 05-Sep-2012

- initial (alpha) release


### Version 0.2 -- 05-Sep-2012

- fix logic flaw with `strict_sizes`
- add the ability to set thumbnail configuration in the model itself


### Version 1.0 -- 07-Sep-2012

- initial release
- name change to Thumbnailable
- reworked internals
	- use Resizer bundle to do the actual image manipulation
	- remove `thumbnail_background` config option
	- move logic from trait into static Thumbnailer class
	- trait methods can easily be duplicate in model for PHP 5.3 users
- global configuration is done in `application/config/thumbnailable.php` and
  model-level configuration is done in model


### Version 1.0.1 -- 07-Sep-2012

- fix typo (was too eager to release)
- change default `thumbnail_format` config to "jpg"


### Version 1.1 -- 12-Sep-2012

- fix issue where all models are assumed to be thumbnailable


### Version 1.2 -- 13-Nov-2012

- old thumbnails are now removed when a model is updated with a new image (thanks @markwu)

### Version 1.3 -- 29-Nov-2012

- add `thumbnail_format` option "auto", which will reuse the file format/extension
  of the original image (thanks @markwu)
- added `base_url` config to define base URL route to thumbnail images
- thumbnail_url() method to return full URL to a thumbnail
- thumbnail_image() method to return HTML tag for a thumbnail

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

# Changelog

All Notable changes to `laravel-sluggable` will be documented in this file

## 3.6.0 - 2023-03-21
- Added slugifyUseLanguage to SlugOption
- Added slugifyUseDictionary to SlugOption

## 3.6.0 - 2023-03-21
- Added Laravel 10
- Update composer.json.

## 3.5.0 - 2022-04-06
 - Added Laravel 9
 - Update composer.json.

## 3.4.0 - 2021-09-12
- Update composer.json.

## 3.0.0 - 2018-10-23
 - Added Laravel 5.7
 - Update composer.json.
 
## 2.1.0 - 2016-07-09
 - Added Sluggable Scope Helpers


## 2.0.0 - 2016-07-09
**NOTE:**
This package is based on [spatie/laravel-sluggable](https://packagist.org/packages/spatie/laravel-sluggable)
but with some adjustments for me and few  improvements. Here's a major changes:

 - Added the ability to specify a source field through a model relation with dot notation. Ex.: ['category.name'] or ['customer.country.code'] where category, customer and country are model relations.
 - Added the ability to specify multiple fields with priority to look up the first non-empty source field.  Ex.: In the example above, we set the look up to find a non empty source in model for slug in this order: title, first_name and last_name. Note: slug is set if at least one of these fields is not empty:
```php
SlugOptions::create()->generateSlugsFrom([
						                'title',
						                ['first_name', 'last_name'],
							            ])
```           
 - Added option to set the behaviour when the source fields are all empty (thrown an exception or generate a random slug).
 - Remove the abstract function getSlugOptions() and introduce the ability to set the trait with zero configuration with default options. The ability to define getSlugOptions() function in your model remained. 
 - Added option to set slug separator
 - Some other adjustments and fix

## 1.2.0 - 2016-06-13
- Added the ability to generate slugs from a callable

## 1.1.0 - 2016-01-24
- Allow custom slugs

## 1.0.2 - 2016-01-12

- Fix bug when creating slugs from null values

## 1.0.1 - 2015-12-27

- Fix Postgres bug

## 1.0.0 - 2015-12-24

- Initial release

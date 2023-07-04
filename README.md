# Generate slugs when saving Eloquent models

[![Latest Version on Packagist](https://img.shields.io/packagist/v/padosoft/laravel-sluggable.svg?style=flat-square)](https://packagist.org/packages/padosoft/laravel-sluggable)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Build Status](https://img.shields.io/travis/padosoft/laravel-sluggable/master.svg?style=flat-square)](https://travis-ci.org/padosoft/laravel-sluggable)
[![Quality Score](https://img.shields.io/scrutinizer/g/padosoft/laravel-sluggable.svg?style=flat-square)](https://scrutinizer-ci.com/g/padosoft/laravel-sluggable)
[![Total Downloads](https://img.shields.io/packagist/dt/padosoft/laravel-sluggable.svg?style=flat-square)](https://packagist.org/packages/padosoft/laravel-sluggable)
[![SensioLabsInsight](https://img.shields.io/sensiolabs/i/236930cb-61cc-433f-b864-e5660f4533e6.svg?style=flat-square)](https://insight.sensiolabs.com/projects/a56f8c11-331f-4d3c-8724-77f55969f2f7)

This package provides a trait that will generate a unique slug when saving any Eloquent model.

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

##Overview
```php
$model = new EloquentModel();
$model->name = 'activerecord is awesome';
$model->save();

echo $model->slug; // outputs "activerecord-is-awesome"
```

The slugs are generated with Laravels `str_slug` method, whereby spaces are converted to '-'.

##Requires
  
- php: >=7.0.0
- illuminate/database: ^5.0
- illuminate/support: ^5.0
  
## Installation

You can install the package via composer:
``` bash
$ composer require padosoft/laravel-sluggable
```

## Usage

Your Eloquent models should use the `Padosoft\Sluggable\HasSlug` trait and the `Padosoft\Sluggable\SlugOptions` class.

The trait shipping with ZERO Configuration if your model contains the slug attribute and one of the fields specified in getSlugOptionsDefault().
If the zero config not for you, you can define `getSlugOptions()`  method  in your model. 

Here's an example of how to implement the trait with zero configuration:

```php
<?php

namespace App;

use Padosoft\Sluggable\HasSlug;
use Illuminate\Database\Eloquent\Model;

class YourEloquentModel extends Model
{
    use HasSlug;   
}
```

Here's an example of how to implement the trait with implementation of getSlugOptions():

```php
<?php

namespace App;

use Padosoft\Sluggable\HasSlug;
use Padosoft\Sluggable\SlugOptions;
use Illuminate\Database\Eloquent\Model;

class YourEloquentModel extends Model
{
    use HasSlug;
    
    /**
     * Get the options for generating the slug.
     */
    public function getSlugOptions() : SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('slug');
    }
}
```

Want to use multiple field as the basis for a slug? No problem!

```php
public function getSlugOptions() : SlugOptions
{
    return SlugOptions::create()
        ->generateSlugsFrom(['first_name', 'last_name'])
        ->saveSlugsTo('slug');
}
```
Want to use relation field as the basis for a slug? No problem!

```php
public function getSlugOptions() : SlugOptions
{
    return SlugOptions::create()
        ->generateSlugsFrom('category.name')
        ->saveSlugsTo('slug');
}
```
where category is a relation in your model:
```php
public function category()
{
    return $this->belongsTo('\App\Category', 'category_id');
}
```

If you want to use a specific language for the slug creation
```php
public function getSlugOptions() : SlugOptions
{
    return SlugOptions::create()
        ->slugifyUseLanguage('jp')
        ->saveSlugsTo('slug');
}
```
If you want to use a specific convertion dictionary
```php
public function getSlugOptions() : SlugOptions
{
    return SlugOptions::create()
        ->slugifyUseDictionary(['@'=>'at','['=>'quad'])
        ->saveSlugsTo('slug');
}
```

It support chaining for relation so you can also pass a customer..


You can also pass a `callable` to `generateSlugsFrom`.


By default the package will generate unique slugs by appending '-' and a number, to a slug that already exists.
You can disable this behaviour by calling `allowDuplicateSlugs`.

By default the package will generate a random 50char slug if all source fields are empty.
You can disable this behaviour by calling `disallowSlugIfAllSourceFieldsEmpty` 
and set the random string char lenght by calling `randomSlugsShouldBeNoLongerThan`.

```php
public function getSlugOptions() : SlugOptions
{
    return SlugOptions::create()
        ->generateSlugsFrom('name')
        ->saveSlugsTo('url')
        ->allowDuplicateSlugs()
        ->disallowSlugIfAllSourceFieldsEmpty()
        ;
}
```

You can also put a maximum size limit on the created slug and/or the lenght of random slug:

```php
public function getSlugOptions() : SlugOptions
{
    return SlugOptions::create()
        ->generateSlugsFrom('name')
        ->saveSlugsTo('url')
        ->slugsShouldBeNoLongerThan(50)
        ->randomSlugsShouldBeNoLongerThan(20);
}
```

The slug may be slightly longer than the value specified, due to the suffix which is added to make it unique.

You can also override the generated slug just by setting it to another value then the generated slug.
```php
$model = EloquentModel:create(['name' => 'my name']); //url is now "my-name"; 
$model->url = 'my-custom-url';
$model-save();

$model->name = 'changed name';
$model->save(); //url stays "my name"

//if you reset the slug and recall save it will regenerate the slug.
$model->url = '';
$model-save(); //url is now "changed-name";
```

### Custom slug (i.e.: manually set slug url)

If you want a custom slug write by hand, use the `saveCustomSlugsTo()` method to set the custom field: 
```php
  ->saveCustomSlugsTo('url-custom')
```

Then, if you set the `url-custom` attribute in your model, the slug field will be set to same value.
In any case, check for correct url and uniquity will be performed to custom slug value.
Example:
```php
$model = new class extends TestModel
{
    public function getSlugOptions(): SlugOptions
    {
        return parent::getSlugOptions()->generateSlugsFrom('name')
                                       ->saveCustomSlugsTo('url_custom');
    }
};
$model->name = 'hello dad';
$model->url_custom = 'this is a custom test';
$model->save(); //the slug is 'this-is-a-custom-test' and not , 'hello-dad';
```

## SluggableScope Helpers

The package included some helper functions for working with models and their slugs.
You can do things such as:

```php
$post = Post::whereSlug($slugString)->get();

$post = Post::findBySlug($slugString);

$post = Post::findBySlugOrFail($slugString);
```

## Change log

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security

If you discover any security related issues, please email instead of using the issue tracker.

## Credits
- [Lorenzo Padovani](https://github.com/lopadova)
- [Freek Van der Herten](https://github.com/freekmurze)
- [All Contributors](../../contributors)

## About Padosoft
Padosoft (https://www.padosoft.com) is a software house based in Florence, Italy. Specialized in E-commerce and web sites.

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

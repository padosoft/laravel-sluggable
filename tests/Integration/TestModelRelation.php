<?php

namespace Padosoft\Sluggable\Test\Integration;

use Illuminate\Database\Eloquent\Model;
use Padosoft\Sluggable\HasSlug;
use Padosoft\Sluggable\SlugOptions;

class TestModelRelation extends Model
{
    use HasSlug;

    protected $table = 'test_models';

    protected $guarded = [];

    public $timestamps = false;

    /**
     * Get the options for generating the slug.
     */
    public function getSlugOptions() : SlugOptions
    {
        return $this->slugOptions ?? $this->getDefaultSlugOptions();
    }

    /**
     * Set the options for generating the slug.
     */
    public function setSlugOptions(SlugOptions $slugOptions) : TestModel
    {
        $this->slugOptions = $slugOptions;

        return $this;
    }

    /**
     * Get the default slug options used in the tests.
     */
    public function getDefaultSlugOptions() : SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom('name')
            ->saveSlugsTo('url')
            ->allowSlugIfAllSourceFieldsEmpty();
    }
}

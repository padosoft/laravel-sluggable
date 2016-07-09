<?php

namespace Padosoft\Sluggable;

class SlugOptions
{
    /** @var string|array|callable */
    public $generateSlugFrom;

    /** @var string */
    public $slugField;

    /** @var bool */
    public $generateUniqueSlugs = true;

    /** @var bool */
    public $generateSlugIfAllSourceFieldsEmpty = true;

    /** @var int */
    public $maximumLength = 250;

    /** @var string */
    public $separator = '-';

    /** @var int */
    public $randomUrlLen=50;



    public static function create(): SlugOptions
    {
        return new static();
    }

    /**
     * @param string|array|callable $fieldName
     *
     * @return \Padosoft\Sluggable\SlugOptions
     */
    public function generateSlugsFrom($fieldName): SlugOptions
    {
        $this->generateSlugFrom = $fieldName;

        return $this;
    }

    public function saveSlugsTo(string $fieldName): SlugOptions
    {
        $this->slugField = $fieldName;

        return $this;
    }

    public function allowDuplicateSlugs(): SlugOptions
    {
        $this->generateUniqueSlugs = false;

        return $this;
    }

    public function disallowSlugIfAllSourceFieldsEmpty(): SlugOptions
    {
        $this->generateSlugIfAllSourceFieldsEmpty = false;

        return $this;
    }

    public function allowSlugIfAllSourceFieldsEmpty(): SlugOptions
    {
        $this->generateSlugIfAllSourceFieldsEmpty = true;

        return $this;
    }

    public function slugsShouldBeNoLongerThan(int $maximumLength): SlugOptions
    {
        $this->maximumLength = $maximumLength;

        return $this;
    }

    public function randomSlugsShouldBeNoLongerThan(int $maximumLength): SlugOptions
    {
        $this->randomUrlLen = $maximumLength;

        return $this;
    }

    public function slugsSeparator(string $separator): SlugOptions
    {
        $this->separator = $separator ?? '-';

        return $this;
    }

    /**
     * Get the options for generating the slug.
     */
    public function getSlugOptionsDefault(): SlugOptions
    {
        return SlugOptions::create()
            ->generateSlugsFrom([
                'titolo',
                'title',
                ['nome', 'cognome'],
                ['first_name', 'last_name'],
                'nome',
                'name',
                'descr',
                'descrizione',
                'codice',
                'pcode',
                'id',
            ])
            ->saveSlugsTo('slug')
            ->slugsShouldBeNoLongerThan(255);
    }
}

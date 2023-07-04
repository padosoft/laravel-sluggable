<?php

namespace Padosoft\Sluggable;

class SlugOptions
{
    /** @var string|array|callable */
    public $generateSlugFrom;

    /** @var string */
    public $slugField;

    /** @var string */
    public $slugCustomField;

    /** @var bool */
    public $generateUniqueSlugs = true;

    /** @var bool */
    public $generateSlugIfAllSourceFieldsEmpty = true;

    /** @var int */
    public $maximumLength = 251;

    /** @var string */
    public $separator = '-';

    /** @var int */
    public $randomUrlLen = 50;

    /** @var bool */
    public $slugifySlugSourceString = true;//if setted to false don't call Str::slug on the slug generated automatically

    /** @var bool */
    public $slugifyCustomSlug = true;//if setted to false don't call Str::slug on the slug custom field

    /**
     * @var ?string
     */
    public $language_code = 'en';//set the referred language used by Str::slug

    /**
     * @var array<string, string>
     */
    public $dictionary = ['@' => 'at'];//dictionary used to convert characters

    /**
     * @return SlugOptions
     */
    public static function create(): SlugOptions
    {
        return new static();
    }

    /**
     * @param ?string $language_code INFO: https://en.wikipedia.org/wiki/List_of_ISO_639-1_codes
     * @return $this
     */
    public function slugifyUseLanguage($language_code)
    {
        $this->language_code=$language_code;

        return $this;
    }

    /**
     * @param array<string, string> $dictionary
     * @return $this
     */
    public function slugifyUseDictionary($dictionary)
    {
        if (is_array($dictionary))
        {
            $this->dictionary=$dictionary;
        }

        return $this;
    }

    /**
     * @param bool $bool
     * @return $this
     */
    public function slugifySourceString($bool)
    {
        $this->slugifySlugSourceString = $bool ? true : false;

        return $this;
    }

    /**
     * @param bool $bool
     * @return $this
     */
    public function slugifyCustomSlug($bool)
    {
        $this->slugifyCustomSlug = $bool ? true : false;

        return $this;
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

    /**
     * @param string $fieldName
     * @return SlugOptions
     */
    public function saveSlugsTo(string $fieldName): SlugOptions
    {
        $this->slugField = $fieldName;

        return $this;
    }

    /**
     * @param string $fieldName
     * @return SlugOptions
     */
    public function saveCustomSlugsTo(string $fieldName): SlugOptions
    {
        $this->slugCustomField = $fieldName;

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

    /**
     * @param int $maximumLength
     * @return SlugOptions
     */
    public function slugsShouldBeNoLongerThan(int $maximumLength): SlugOptions
    {
        $this->maximumLength = $maximumLength;

        return $this;
    }

    /**
     * @param int $maximumLength
     * @return SlugOptions
     */
    public function randomSlugsShouldBeNoLongerThan(int $maximumLength): SlugOptions
    {
        $this->randomUrlLen = $maximumLength;

        return $this;
    }

    /**
     * @param string $separator
     * @return SlugOptions
     */
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
            ->saveCustomSlugsTo('slug_custom')
            ->slugsShouldBeNoLongerThan(251);
    }
}

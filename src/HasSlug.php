<?php

namespace Padosoft\Sluggable;

use Illuminate\Database\Eloquent\Model;

trait HasSlug
{
    /** @var \Padosoft\Sluggable\SlugOptions */
    protected $slugOptions;

    /**
     * Boot the trait.
     */
    protected static function bootHasSlug()
    {
        static::creating(function (Model $model) {
            $model->addSlug();
        });

        static::updating(function (Model $model) {
            $model->addSlug();
        });
    }

    /**
     * Add the slug to the model.
     */
    protected function addSlug()
    {
        $this->slugOptions = $this->getSlugOptionsOrDefault();

        $this->guardAgainstInvalidSlugOptions();

        $slug = $this->generateNonUniqueSlug();

        if ($this->slugOptions->generateUniqueSlugs) {
            $slug = $this->makeSlugUnique($slug);
        }

        $slugField = $this->slugOptions->slugField;

        $this->$slugField = $slug;
    }

    /**
     * Retrive a specifice SlugOptions for this model, or return default SlugOptions
     */
    protected function getSlugOptionsOrDefault()
    {
        if (method_exists($this, 'getSlugOptions')) {
            return $this->getSlugOptions();
        } else {
            return SlugOptions::create()->getSlugOptionsDefault();
        }
    }

    /**
     * Generate a non unique slug for this record.
     * @return string
     * @throws InvalidOption
     */
    protected function generateNonUniqueSlug(): string
    {
        if ($this->hasCustomSlugBeenUsed()) {
            $slugField = $this->slugOptions->slugField;
            return $this->$slugField ?? '';
        }
        return str_slug($this->getSlugSourceString(), $this->slugOptions->separator);
    }

    /**
     * Determine if a custom slug has been saved.
     * @return bool
     */
    protected function hasCustomSlugBeenUsed(): bool
    {
        $slugField = $this->slugOptions->slugField;

        if(!$this->$slugField || trim($this->$slugField)==''){
            return false;
        }
        return $this->getOriginal($slugField) != $this->$slugField;
    }

    /**
     * Get the string that should be used as base for the slug.
     * @return string
     * @throws InvalidOption
     */
    protected function getSlugSourceString(): string
    {
        if (is_callable($this->slugOptions->generateSlugFrom)) {
            $slugSourceString = call_user_func($this->slugOptions->generateSlugFrom, $this);
            return substr($slugSourceString, 0, $this->slugOptions->maximumLength);
        }

        $slugFrom = $this->getSlugFrom($this->slugOptions->generateSlugFrom);

        if(is_null($slugFrom) || (!is_array($slugFrom) && trim($slugFrom)=='')){
            if(!$this->slugOptions->generateSlugIfAllSourceFieldsEmpty){
                throw InvalidOption::missingFromField();
            }

            return str_random($this->slugOptions->maximumLength > $this->slugOptions->randomUrlLen ? $this->slugOptions->randomUrlLen : $this->slugOptions->maximumLength);
        }

        $slugSourceString = $this->getImplodeSourceString($slugFrom, $this->slugOptions->separator);

        return substr($slugSourceString, 0, $this->slugOptions->maximumLength);
    }

    /**
     * Get the correct field(s) from to generate slug
     * @param string|array|callable $fieldName
     * @return string|array
     */
    protected function getSlugFrom($fieldName)
    {
        if(!is_callable($fieldName) && !is_array($fieldName) && trim($fieldName)==''){
            return '';
        }

        if(!is_callable($fieldName) && !is_array($fieldName) && (!data_get($this, $fieldName))){
            return '';
        }elseif (!is_array($fieldName)){
            return $fieldName;
        }

        $slugSourceString = '';
        $countFieldName = count($fieldName);
        for($i=0;$i<$countFieldName;$i++){

            $currFieldName = $fieldName[$i];
            if(!is_array($currFieldName) && trim($currFieldName)==''){
                continue;
            }
            if (!is_array($currFieldName) && (!data_get($this, $currFieldName))){
                continue;
            }
            if (!is_array($currFieldName) && data_get($this, $currFieldName)){
                $slugSourceString = $currFieldName;
                break;
            }

            $slugSourceString = $this->getImplodeSourceString($currFieldName, '');

            if($slugSourceString!=''){
                $slugSourceString = $currFieldName;
                break;
            }
        }

        return $slugSourceString;
    }

    /**
     * Make the given slug unique.
     * @param string $slug
     * @return string
     */
    protected function makeSlugUnique(string $slug): string
    {
        $originalSlug = $slug;
        $i = 1;

        while ($this->otherRecordExistsWithSlug($slug) || $slug === '') {
            $slug = $originalSlug.$this->slugOptions->separator.$i++;
        }

        return $slug;
    }

    /**
     * Determine if a record exists with the given slug.
     * @param string $slug
     * @return bool
     */
    protected function otherRecordExistsWithSlug(string $slug): bool
    {
        return (bool) static::where($this->slugOptions->slugField, $slug)
            ->where($this->getKeyName(), '!=', $this->getKey() ?? '0')
            ->first();
    }

    /**
     * This function will throw an exception when any of the options is missing or invalid.
     * @throws InvalidOption
     */
    protected function guardAgainstInvalidSlugOptions()
    {
        if (!count($this->slugOptions->generateSlugFrom)) {
            throw InvalidOption::missingFromField();
        }

        if (!strlen($this->slugOptions->slugField)) {
            throw InvalidOption::missingSlugField();
        }

        if ($this->slugOptions->maximumLength <= 0) {
            throw InvalidOption::invalidMaximumLength();
        }
    }

    /**
     * @param $slugFrom
     * @param string $separator
     * @return string
     */
    protected function getImplodeSourceString($slugFrom, string $separator) : string
    {
        $slugSourceString = collect($slugFrom)
            ->map(function (string $fieldName) : string {
                if ($fieldName == '') {
                    return '';
                }
                return data_get($this, $fieldName) ?? '';
            })
            ->implode($separator);
        return $slugSourceString;
    }
}

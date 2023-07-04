<?php

namespace Padosoft\Sluggable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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
            $slugField = $this->slugOptions->slugCustomField;
            if (!$this->$slugField) {
                return '';
            }
            return !$this->slugOptions->slugifyCustomSlug ? $this->$slugField : Str::slug($this->$slugField, $this->slugOptions->separator);
        }
        if ($this->hasSlugBeenUsed()) {
            $slugField = $this->slugOptions->slugField;
            return $this->$slugField ?? '';
        }
        $generatedSlug = $this->getSlugSourceString();
        if (!$this->slugOptions->slugifySlugSourceString) {
            return $generatedSlug;
        }

        return Str::slug($generatedSlug, $this->slugOptions->separator, $this->slugOptions->language_code, $this->slugOptions->dictionary);
    }

    /**
     * Determine if a custom slug has been saved.
     * @return bool
     */
    protected function hasCustomSlugBeenUsed(): bool
    {
        $slugField = $this->slugOptions->slugCustomField;
        if (!$slugField || trim($slugField) == '' || !$this->$slugField || trim($this->$slugField) == '') {
            return false;
        }
        return true;
    }

    /**
     * Determine if a custom slug has been saved.
     * @return bool
     */
    protected function hasSlugBeenUsed(): bool
    {
        $slugField = $this->slugOptions->slugField;

        if (!$slugField || trim($slugField) == '' || !$this->$slugField || trim($this->$slugField) == '') {
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
            return mb_substr($slugSourceString, 0, $this->slugOptions->maximumLength);
        }

        $slugFrom = $this->getSlugFrom($this->slugOptions->generateSlugFrom);

        if (is_null($slugFrom) || (!is_array($slugFrom) && trim($slugFrom) == '')) {
            if (!$this->slugOptions->generateSlugIfAllSourceFieldsEmpty) {
                throw InvalidOption::missingFromField();
            }

            return Str::random($this->slugOptions->maximumLength > $this->slugOptions->randomUrlLen ? $this->slugOptions->randomUrlLen : $this->slugOptions->maximumLength);
        }

        $slugSourceString = $this->getImplodeSourceString($slugFrom, $this->slugOptions->separator);

        return mb_substr($slugSourceString, 0, $this->slugOptions->maximumLength);
    }

    /**
     * Get the correct field(s) from to generate slug
     * @param string|array|callable $fieldName
     * @return string|array
     */
    protected function getSlugFrom($fieldName)
    {
        if (!is_callable($fieldName) && !is_array($fieldName) && trim($fieldName) == '') {
            return '';
        }

        if (!is_callable($fieldName) && !is_array($fieldName) && (!data_get($this, $fieldName))) {
            return '';
        } elseif (!is_array($fieldName)) {
            return $fieldName;
        }

        $slugSourceString = '';
        $countFieldName = count($fieldName);
        for ($i = 0; $i < $countFieldName; $i++) {

            $currFieldName = $fieldName[$i];
            if (!is_array($currFieldName) && trim($currFieldName) == '') {
                continue;
            }
            if (!is_array($currFieldName) && (!data_get($this, $currFieldName))) {
                continue;
            }
            if (!is_array($currFieldName) && data_get($this, $currFieldName)) {
                $slugSourceString = $currFieldName;
                break;
            }

            $slugSourceString = $this->getImplodeSourceString($currFieldName, '');

            if ($slugSourceString != '') {
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
            $slug = $originalSlug . $this->slugOptions->separator . $i++;
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
        return (bool)static::where($this->slugOptions->slugField, $slug)
            ->where($this->getKeyName(), '!=', $this->getKey() ?? '0')
            ->first();
    }

    /**
     * This function will throw an exception when any of the options is missing or invalid.
     * @throws InvalidOption
     */
    protected function guardAgainstInvalidSlugOptions()
    {
        if (is_array($this->slugOptions->generateSlugFrom) && count($this->slugOptions->generateSlugFrom) < 1) {
            throw InvalidOption::missingFromField();
        }

        if (!mb_strlen($this->slugOptions->slugField)) {
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
    protected function getImplodeSourceString($slugFrom, string $separator): string
    {
        $slugSourceString = collect($slugFrom)
            ->map(function (string $fieldName): string {
                if ($fieldName == '') {
                    return '';
                }
                return data_get($this, $fieldName) ?? '';
            })
            ->implode($separator);
        return $slugSourceString;
    }

    /**
     *
     * SCOPE HELPERS
     *
     */

    /**
     * Query scope for finding a model by its slug field.
     *
     * @param \Illuminate\Database\Eloquent\Builder $scope
     * @param string $slug
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWhereSlug(\Illuminate\Database\Eloquent\Builder $scope, $slug)
    {
        $this->slugOptions = $this->getSlugOptionsOrDefault();
        return $scope->where($this->slugOptions->slugField, $slug);
    }

    /**
     * Find a model by its slug field.
     *
     * @param string $slug
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection|static[]|static|null
     */
    public static function findBySlug($slug, array $columns = ['*'])
    {
        return static::whereSlug($slug)->first($columns);
    }

    /**
     * Find a model by its slug field or throw an exception.
     *
     * @param string $slug
     * @param array $columns
     * @return \Illuminate\Database\Eloquent\Model|\Illuminate\Database\Eloquent\Collection
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException
     */
    public static function findBySlugOrFail($slug, array $columns = ['*'])
    {
        return static::whereSlug($slug)->firstOrFail($columns);
    }
}

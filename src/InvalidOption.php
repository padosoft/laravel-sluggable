<?php

namespace Padosoft\Sluggable;

use Exception;

class InvalidOption extends Exception
{
    public static function missingFromField()
    {
        return new static('Could not determinate which fields should be sluggified or these fields are empty');
    }

    public static function missingSlugField()
    {
        return new static('Could not determinate in which field the slug should be saved');
    }

    public static function invalidMaximumLength()
    {
        return new static('Maximum length should be greater than zero');
    }
}

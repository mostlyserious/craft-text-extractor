<?php

namespace mostlyserious\crafttextextractor\models;

use craft\base\Model;

/**
 * Text Extractor settings
 */
class Settings extends Model
{
    public $fieldHandle = 'body';

    public function defineRules(): array
    {
        return [
            [['fieldHandle'], 'required'],
        ];
    }
}

# Text Extractor

A tool to extract text from documents and insert it into Craft CMS Asset Elements.

## Requirements

This plugin requires Craft CMS 5.0.0 or later, and PHP 8.2 or later.

## Features
- Supports PDF (.pdf) and MS Word (.docx) files
    - Password-protected PDF files are [not supported](https://github.com/smalot/pdfparser/blob/master/doc/Usage.md#pdf-encryption).
- Extracts text on Asset creation and when Asset files are replaced
- Includes an Action to extract text from the Assets index view.

## Configuration
Extracted document text is inserted into the custom field handle defined by the plugin. The default field handle is `body`.

You can customize the handle by adding a plugin config file.

```php
<?php

/* @note config/text-extractor.php */

return [
    'fieldHandle' => 'myCustomHandle'
];
```

This must be a Text field or CKEditor field.

## Usage
- Upload supported file extensions and enjoy!

## Thank you to the following packages:
- [smalot/pdfparser](https://github.com/smalot/pdfparser)
- [Label305/DocxExtractor](https://github.com/Label305/DocxExtractor)

## Future Plans and Other Document Parsers

The [PHPWord](https://github.com/PHPOffice/PHPWord) library ([docs](https://phpoffice.github.io/PHPWord/index.html)) and [PHPOffice](https://github.com/PHPOffice) tools like promising, but were more complex than needed for this project at this time.

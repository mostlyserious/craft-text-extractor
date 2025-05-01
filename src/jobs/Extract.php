<?php

namespace mostlyserious\crafttextextractor\jobs;

use Craft;
use craft\elements\Asset;
use craft\queue\BaseJob;
use mostlyserious\crafttextextractor\TextExtractor as Plugin;
use yii\base\Exception;

class Extract extends BaseJob
{
    public int $assetId;
    protected ?Asset $asset;

    public function __construct($assetId)
    {
        $this->assetId = $assetId;

        parent::__construct();
    }

    /*
     * Updates an Asset to include it's extracted text in a custom field.
     */
    public function execute($queue): void
    {
        $this->asset = Asset::find()->id($this->assetId)->one();

        if (!$this->asset) {
            Craft::error('Could not find Asset with Id ' . $this->assetId, __METHOD__);

            return;
        }

        $targetField = Plugin::getInstance()->settings->fieldHandle;
        $fieldLayout = $this->asset->getFieldLayout();
        $field = $fieldLayout
            ? $fieldLayout->getFieldByHandle($targetField)
            : null;

        if (!$field) {
            Craft::error('Could not extract text. Asset "' . $this->asset->title . '" does not have a field layout with the handle "' . $targetField . '". ', __METHOD__);

            return;
        }

        $message = implode(', ', [
            'Extracting text from Asset with title: ' . $this->asset->title,
            'Id: ' . $this->asset->id,
            'Filename: ' . $this->asset->filename,
        ]);

        Craft::info($message, __METHOD__);

        /** Extract text and save the Asset. */
        $contents = Plugin::getInstance()->extractor->extractText($this->asset);

        if (!empty($contents)) {
            $this->asset->setFieldValue($targetField, $contents);

            if (Craft::$app->elements->saveElement($this->asset, true, true, true, true, true)) {
                Craft::info('Successfully extracted text from ' . $this->asset->filename, __METHOD__);
            } else {
                $error_message = 'Could not save Asset. Error extracting text from ' . $this->asset->filename;
                Craft::error($error_message, __METHOD__);

                throw new Exception($error_message);
            }
        }
    }

    protected function defaultDescription(): string
    {
        return 'Extracting text from document.';
    }
}

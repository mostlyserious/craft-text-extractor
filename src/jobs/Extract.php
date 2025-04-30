<?php

namespace mostlyserious\crafttextextractor\jobs;

use Craft;
use craft\elements\Asset;
use craft\queue\BaseJob;
use mostlyserious\crafttextextractor\TextExtractor as Plugin;
use Smalot\PdfParser\Parser;
use yii\base\Exception;

class Extract extends BaseJob
{
    public $assetId;
    protected $asset;

    public function __construct($assetId)
    {
        $this->assetId = $assetId;

        parent::__construct();
    }

    /*
     * Updates an Asset to include it's extracted text in a custom field.
     *
     * @see https://github.com/smalot/pdfparser
     */
    public function execute($queue): void
    {
        $this->asset = Asset::find()->id($this->assetId)->one();

        if (!$this->asset) {
            Craft::error('Could not find asset with Id ' . $this->assetId, __METHOD__);

            return;
        }

        $fieldHandle = Plugin::getInstance()->settings->fieldHandle;

        $url = $this->asset->getUrl();
        $tempFilePath = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $this->asset->filename;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FILE, fopen($tempFilePath, 'w'));
        curl_exec($ch);
        curl_close($ch);

        $parser = new Parser();
        $pdf = $parser->parseFile($tempFilePath);
        $text = $pdf->getText();

        if (!empty($text)) {
            $this->asset->setFieldValue($fieldHandle, $text);

            if (Craft::$app->elements->saveElement($this->asset, true, true, true, true, true)) {
                Craft::info('Successfully extracted text from ' . $this->asset->filename, __METHOD__);
            } else {
                $error_message = 'Could not save entry. Error extracting text from ' . $this->asset->filename;
                Craft::error($error_message, __METHOD__);

                throw new Exception($error_message);
            }
        }

        unlink($tempFilePath);
    }

    protected function defaultDescription(): string
    {
        if (!$this->asset) {
            $message = 'Extracting text from document.';
        } else {
            $message = implode(', ', [
                'Extracting text from Asset with title: ' . $this->asset->title,
                'Id: ' . $this->asset->id,
                'Filename: ' . $this->asset->filename,
            ]);
        }

        return $message;
    }
}

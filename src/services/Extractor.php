<?php

namespace mostlyserious\crafttextextractor\services;

use Craft;
use craft\base\Component;
use craft\elements\Asset;
use mostlyserious\crafttextextractor\TextExtractor as Plugin;
use Smalot\PdfParser\Config as PdfParserConfig;
use Smalot\PdfParser\Parser as PdfParser;

class Extractor extends Component
{
    public function extractText(Asset $asset): string
    {
        if (!$this->isSupportedKind($asset)) {
            throw new \Exception('File type "' . $asset->kind . '" not supported.');
        }

        /** Download a temporary copy of the Asset's file to work with. */
        $url = $asset->getUrl();
        $tempFilePath = Craft::$app->getPath()->getTempPath() . DIRECTORY_SEPARATOR . $asset->filename;

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FILE, fopen($tempFilePath, 'w'));
        curl_exec($ch);
        curl_close($ch);

        if ($asset->kind === 'pdf') {
            $text = $this->_extractFromPdf($tempFilePath);
        } elseif ($asset->kind === 'docx') {
            $text = $this->_extractFromWord($tempFilePath);
        } else {
            throw new \Exception('No extractor available for file kind "' . $asset->kind . '"');
        }

        /** Remove the temp file. */
        unlink($tempFilePath);

        return $text;
    }

    private function _extractFromPdf(string $path): string
    {
        $config = new PdfParserConfig();
        $config->setRetainImageContent(false);
        $parser = new PdfParser([], $config);
        $pdf = $parser->parseFile($path);
        $text = $pdf->getText();

        return $text;
    }

    private function _extractFromWord(string $path): string
    {
        // @todo
        return '';
    }

    public function isSupportedKind(Asset $asset): bool
    {
        return in_array(
            $asset->kind,
            Plugin::getInstance()->getSettings()->supportedExtensions
        );
    }
}

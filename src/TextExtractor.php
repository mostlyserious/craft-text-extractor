<?php

namespace mostlyserious\crafttextextractor;

use Craft;
use craft\base\Element;
use craft\base\Model;
use craft\base\Plugin;
use craft\elements\Asset;
use craft\events\ModelEvent;
use craft\events\RegisterElementActionsEvent;
use craft\events\RegisterElementHtmlAttributesEvent;
use mostlyserious\crafttextextractor\elements\actions\ExtractText as ExtractTextAction;
use mostlyserious\crafttextextractor\jobs\Extract as ExtractJob;
use mostlyserious\crafttextextractor\models\Settings;
use yii\base\Event;

/**
 * Text Extractor plugin
 *
 * @property Settings $settings
 * @method static TextExtractor getInstance()
 * @method Settings getSettings()
 */
class TextExtractor extends Plugin
{
    public string $schemaVersion = '1.0.0';
    public bool $hasCpSettings = false;

    public static function config(): array
    {
        return [
            'components' => [],
        ];
    }

    public function init(): void
    {
        parent::init();

        Craft::$app->onInit(function () {
            $this->attachEventHandlers();
        });
    }

    protected function createSettingsModel(): ?Model
    {
        return Craft::createObject(Settings::class);
    }

    /*
     * Note: Control Panel settings are not used at this time.
     * The field handle to receive the extracted text can be configured here:
     *
     * `./plugins/text-extractor/src/models/Settings.php`
     */
    protected function settingsHtml(): ?string
    {
        return Craft::$app->view->renderTemplate('text-extractor/_settings.twig', [
            'plugin' => $this,
            'settings' => $this->getSettings(),
        ]);
    }

    private function attachEventHandlers(): void
    {
        /**
         * Adds a custom data attribute to Assets to validate the Extract Text action.
         */
        Event::on(
            Asset::class,
            Element::EVENT_REGISTER_HTML_ATTRIBUTES,
            function (RegisterElementHtmlAttributesEvent $event) {
                /** @var Asset $element */
                $element = $event->sender;
                $supportedKinds = self::getInstance()->getSettings()->supportedExtensions;
                if (in_array(
                    $element->kind,
                    $supportedKinds
                )) {
                    $event->htmlAttributes = [
                        'data-extract-text' => 'true',
                    ];
                }
            }
        );

        Event::on(
            Asset::class,
            Asset::EVENT_REGISTER_ACTIONS,
            function (RegisterElementActionsEvent $event) {
                $event->actions[] = ExtractTextAction::class;
            }
        );

        Event::on(
            Asset::class,
            Asset::EVENT_AFTER_SAVE,
            function (ModelEvent $event) {
                /** @var Asset $asset */
                $asset = $event->sender;
                $supportedKinds = self::getInstance()->getSettings()->supportedExtensions;
                $scenario = $asset->getScenario();
                if (
                    in_array($asset->kind, $supportedKinds) &&
                    (
                        $scenario === Asset::SCENARIO_CREATE ||
                        $scenario === Asset::SCENARIO_REPLACE
                    )
                ) {
                    Craft::$app->queue->push(new ExtractJob([
                        'assetId' => $asset->id,
                    ]));
                }
            }
        );
    }
}

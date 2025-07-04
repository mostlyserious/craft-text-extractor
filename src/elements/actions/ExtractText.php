<?php

namespace mostlyserious\crafttextextractor\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\elements\Asset;
use craft\elements\db\ElementQueryInterface;
use mostlyserious\crafttextextractor\jobs\Extract as ExtractJob;
use mostlyserious\crafttextextractor\TextExtractor as Plugin;

/**
 * Extract Text element action
 */
class ExtractText extends ElementAction
{
    public static function displayName(): string
    {
        return Craft::t('text-extractor', 'Extract Text');
    }

    public function getTriggerHtml(): ?string
    {
        Craft::$app->getView()->registerJsWithVars(fn($type) => <<<JS
            (() => {
                new Craft.ElementActionTrigger({
                    type: $type,

                    // Whether this action should be available when multiple elements are selected
                    bulk: true,

                    // Return whether the action should be available depending on which elements are selected
                    validateSelection: (selectedItems) => {
                        return Array.from(selectedItems).every((item) => {
                            const el = item.querySelector('[data-can-extract-text="true"]');
                            if (!el) {
                                return false;
                            }

                            return true;
                        });
                    },

                    // Uncomment if the action should be handled by JavaScript:
                    // activate: () => {
                    //   Craft.elementIndex.setIndexBusy();
                    //   const ids = Craft.elementIndex.getSelectedElementIds();
                    //   // ...
                    //   Craft.elementIndex.setIndexAvailable();
                    // },
                });
            })();
        JS, [static::class]);

        return null;
    }

    public function performAction(ElementQueryInterface $query): bool
    {
        $elements = $query->all();

        foreach ($elements as $element) {
            if (
                $element instanceof Asset &&
                Plugin::getInstance()->extractor->isSupportedExtension($element)
            ) {
                Craft::$app->queue->push(new ExtractJob($element->id));
            }
        }

        return true;
    }
}

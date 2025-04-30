<?php

namespace mostlyserious\crafttextextractor\elements\actions;

use Craft;
use craft\base\ElementAction;
use craft\elements\Asset;
use mostlyserious\crafttextextractor\jobs\Extract as ExtractJob;

/**
 * Extract Text element action
 */
class ExtractText extends ElementAction
{
    public static function displayName(): string
    {
        return Craft::t('_text-extractor', 'Extract Text');
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
                      return true;
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

    public function performAction(Craft\elements\db\ElementQueryInterface $query): bool
    {
        $elements = $query->all();

        foreach ($elements as $element) {
            if ($element instanceof Asset && $element->kind === 'pdf') {
                Craft::$app->queue->push(new ExtractJob([
                    'assetId' => $element->id,
                ]));
            }
        }

        return true;
    }
}

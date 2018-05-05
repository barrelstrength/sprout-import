<?php

namespace barrelstrength\sproutimport\integrations\sproutimport\settings;

use barrelstrength\sproutbase\sproutimport\contracts\BaseSettingsImporter;
use barrelstrength\sproutimport\models\importers\Widget as WidgetModel;
use barrelstrength\sproutimport\SproutImport;
use Craft;
use craft\base\WidgetInterface;

class Widget extends BaseSettingsImporter
{
    /**
     * @return string
     */
    public function getName()
    {
        return Craft::t('sprout-import', 'Widget');
    }

    /**
     * @return string
     */
    public function getModelName()
    {
        return WidgetModel::class;
    }

    /**
     * @return WidgetInterface
     * @throws \Throwable
     */
    public function save()
    {
        unset($this->rows['@model']);

        $dashboardService = Craft::$app->getDashboard();

        /**
         * @var $widget WidgetInterface
         */
        $widget = $dashboardService->saveWidget($dashboardService->createWidget($this->rows));

        if ($widget) {
            $this->model = $widget;
        } else {
            SproutImport::error(Craft::t('sprout-import', 'Cannot save Widget: '.$widget::displayName()));
            SproutImport::info($widget);
        }

        return $widget;
    }

    /**
     * @param $id
     *
     * @return bool
     */
    public function deleteById($id)
    {
        return Craft::$app->getDashboard()->deleteWidgetById($id);
    }
}

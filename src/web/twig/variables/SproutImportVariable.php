<?php

namespace barrelstrength\sproutimport\web\twig\variables;

use barrelstrength\sproutimport\SproutImport;
use Craft;

class SproutImportVariable
{
    /**
     * Confirm if a specific plugin is installed
     *
     * @param string
     *
     * @return bool
     */
    public function isPluginInstalled($plugin)
    {
        if (Craft::$app->getPlugins()->getPlugin($plugin)) {
            return true;
        }

        return false;
    }

    /**
     * @return array
     */
    public function getSproutImportThemes()
    {
        return SproutImport::$app->themes->getSproutImportThemes();
    }

    /**
     * @return array
     */
    public function getSproutImportImporters()
    {
        return SproutImport::$app->importers->getSproutImportImporters();
    }

    /**
     * @return mixed
     */
    public function getSproutImportFieldImporters()
    {
        return SproutImport::$app->importers->getSproutImportFieldImporters();
    }

    /**
     * Confirm if any seeds exist
     *
     * @return int
     */
    public function hasSeeds()
    {
        $seeds = SproutImport::$app->seed->getAllSeeds();

        return count($seeds);
    }
}
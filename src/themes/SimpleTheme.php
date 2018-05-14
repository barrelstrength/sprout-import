<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com/
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   http://sprout.barrelstrengthdesign.com/license
 */

namespace barrelstrength\sproutimport\themes;

use barrelstrength\sproutbase\app\import\base\Theme;
use Craft;

class SimpleTheme extends Theme
{
    /**
     * @return string
     */
    public function getName(): string
    {
        return Craft::t('sprout-import', 'Simple Theme');
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return Craft::t('sprout-import', 'A simple theme that installs some schema and moves some templates.');
    }

    /**
     * The folder where this theme's importable schema files are located
     *
     * @default plugin-handle/src/schema
     *
     * @return string
     */
    public function getSchemaFolder()
    {
        return $this->plugin->getBasePath().DIRECTORY_SEPARATOR.'themes/bundles/simple/schema';
    }

    /**
     * The folder where this theme's template files are located
     *
     * @default plugin-handle/src/templates
     *
     * @return string
     */
    public function getSourceTemplateFolder()
    {
        return $this->plugin->getBasePath().DIRECTORY_SEPARATOR.'themes/bundles/simple/templates';
    }

}



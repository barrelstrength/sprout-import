<?php
/**
 * @link      https://sprout.barrelstrengthdesign.com/
 * @copyright Copyright (c) Barrel Strength Design LLC
 * @license   http://sprout.barrelstrengthdesign.com/license
 */

namespace barrelstrength\sproutimport\bundles;

use barrelstrength\sproutbase\app\import\base\Bundle;
use Craft;

class SimpleBundle extends Bundle
{
    /**
     * @return string
     */
    public function getName(): string
    {
        return Craft::t('sprout-import', 'Simple Bundle');
    }

    /**
     * @return string
     */
    public function getDescription(): string
    {
        return Craft::t('sprout-import', 'A simple bundle that installs some schema and moves some templates.');
    }

    /**
     * The folder where this bundle's importable schema files are located
     *
     * @default plugin-handle/src/schema
     *
     * @return string
     */
    public function getSchemaFolder()
    {
        return $this->plugin->getBasePath().DIRECTORY_SEPARATOR.'bundles/resources/simple/schema';
    }

    /**
     * The folder where this bundle's template files are located
     *
     * @default plugin-handle/src/templates
     *
     * @return string
     */
    public function getSourceTemplateFolder()
    {
        return $this->plugin->getBasePath().DIRECTORY_SEPARATOR.'bundles/resources/simple/templates';
    }

}



<?php

namespace barrelstrength\sproutimport\services;

use barrelstrength\sproutbase\contracts\sproutimport\BaseTheme;
use craft\base\Component;
use craft\events\RegisterComponentTypesEvent;

class Themes extends Component
{
    const EVENT_REGISTER_THEMES = 'registerSproutImportThemes';

    /**
     * @var array
     */
    protected $themes = [];

    public function getSproutImportThemes(): array
    {
        $event = new RegisterComponentTypesEvent([
            'types' => []
        ]);

        $this->trigger(self::EVENT_REGISTER_THEMES, $event);

        $themes = $event->types;

        if ($themes !== null) {
            foreach ($themes as $themeClass) {

                // Create an instance of our Theme object
                $theme = new $themeClass();

                $this->themes[$themeClass] = $theme;
            }
        }

        uasort($this->themes, function($a, $b) {
            /**
             * @var $a BaseTheme
             * @var $b BaseTheme
             */
            return $a->getName() <=> $b->getName();
        });

        return $this->themes;
    }
}
<?php

namespace barrelstrength\sproutimport\services;

use craft\base\Component;
use Faker\Factory;
use Faker\Generator;

class Faker extends Component
{
    /**
     * @return Generator
     */
    public function getGenerator()
    {
        return Factory::create();
    }
}
<?php
namespace barrelstrength\sproutimport\console\controllers;

use yii\console\Controller;

class SeedController extends Controller
{
    public $settings;

    public function options($actionID)
    {
        return ['settings'];
    }

    public function actionIndex()
    {
        $options = $this->getPassedOptionValues();
        \Craft::dd($this->settings, 10, false);
        \Craft::dd(json_decode($this->settings), 10, false);
        \Craft::dd($options, 10, false);
    }
}
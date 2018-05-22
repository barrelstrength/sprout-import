<?php

namespace barrelstrength\sproutimport\importers\fields;

use barrelstrength\sproutbase\app\import\base\FieldImporter;
use craft\fields\PlainText as PlainTextField;
use Craft;

class PlainText extends FieldImporter
{
    /**
     * @return string
     */
    public function getModelName()
    {
        return PlainTextField::class;
    }

    /**
     * @return string
     * @throws \Twig_Error_Loader
     * @throws \yii\base\Exception
     */
    public function getSeedSettingsHtml(): string
    {
        return Craft::$app->getView()->renderTemplate('sprout-base-import/settings/seed-defaults/plaintext/settings', [
            'settings' => $this->seedSettings['fields']['plaintext'] ?? []
        ]);
    }

    /**
     * @return mixed|string
     */
    public function getMockData()
    {
        $settings = $this->model->settings;

        $singlelineLinesMin = 2;
        $singlelineLinesMax = 4;
        $multilineParagraphsMin = 1;
        $multilineParagraphsMax = 3;

        if (isset($this->seedSettings['fields']))
        {
            $singlelineLinesMin = $this->seedSettings['fields']['plaintext']['singleLineLinesMin'] ?: $singlelineLinesMin;
            $singlelineLinesMax = $this->seedSettings['fields']['plaintext']['singleLineLinesMax'] ?: $singlelineLinesMax;
            $multilineParagraphsMin = $this->seedSettings['fields']['plaintext']['multilineParagraphsMin'] ?: $multilineParagraphsMin;
            $multilineParagraphsMax = $this->seedSettings['fields']['plaintext']['multilineParagraphsMax'] ?: $multilineParagraphsMax;
        }

        if ($settings != null && isset($settings['multiline']) && $settings['multiline'] == 1) {
            $lines = random_int($multilineParagraphsMin, $multilineParagraphsMax);
            $paragraphs = $this->fakerService->paragraphs($lines);

            $text = implode("\n\n", $paragraphs);
        } else {
            $lines = random_int($singlelineLinesMin, $singlelineLinesMax);
            $sentences = $this->fakerService->sentences($lines);

            $text = implode("\n ", $sentences);
        }

        $charLimit = $settings['charLimit'] ?? null;

        if ($charLimit) {
            $charLimit = (int) $charLimit;
            $text = trim(substr($text, 0, $charLimit));
        }

        return $text;
    }
}

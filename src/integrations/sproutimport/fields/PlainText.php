<?php

namespace barrelstrength\sproutimport\integrations\sproutimport\fields;

use barrelstrength\sproutbase\contracts\sproutimport\BaseFieldImporter;
use craft\fields\PlainText as PlainTextField;
use Craft;

class PlainText extends BaseFieldImporter
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
        return Craft::$app->getView()->renderTemplate('sprout-import/_seeds/plaintext/settings', [
            'settings' => $this->seedSettings['fields']['plaintext'] ?: []
        ]);
    }

    /**
     * @return mixed|string
     */
    public function getMockData()
    {
        $settings = $this->model->settings;

        $singlelineLinesMin = $this->seedSettings['fields']['plaintext']['singleLineLinesMin'] ?: 2;
        $singlelineLinesMax = $this->seedSettings['fields']['plaintext']['singleLineLinesMax'] ?: 4;
        $multilineParagraphsMin = $this->seedSettings['fields']['plaintext']['multilineParagraphsMin'] ?: 1;
        $multilineParagraphsMax = $this->seedSettings['fields']['plaintext']['multilineParagraphsMax'] ?: 3;

        if ($settings != null && isset($settings['multiline']) && $settings['multiline'] == 1) {
            $lines = random_int($multilineParagraphsMin, $multilineParagraphsMax);
            $paragraphs = $this->fakerService->paragraphs($lines);

            return implode("\n\n", $paragraphs);
        }

        $lines = random_int($singlelineLinesMin, $singlelineLinesMax);
        $sentences = $this->fakerService->sentences($lines);

        return implode("\n ", $sentences);
    }
}

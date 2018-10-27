<?php

namespace barrelstrength\sproutimport\controllers;

use barrelstrength\sproutbase\SproutBase;
use Craft;
use craft\web\Controller;
use yii\web\Response;
use yii\db\Exception;
use yii\web\BadRequestHttpException;

class WeedController extends Controller
{
    /**
     * Render the Weed template
     */
    public function actionWeedIndex(): Response
    {
        $seeds = SproutBase::$app->seed->getSeeds();

        return $this->renderTemplate('sprout-base-import/weed/index', [
            'seeds' => $seeds
        ]);
    }

    /**
     * @return null|Response
     * @throws BadRequestHttpException
     * @throws Exception
     * @throws \craft\errors\MissingComponentException
     */
    public function actionProcessWeed()
    {
        $this->requirePostRequest();

        $submit = Craft::$app->getRequest()->getParam('submit');

        $isKeep = true;

        if ($submit == 'Weed' || $submit == 'Weed All') {
            $isKeep = false;
        }

        $seeds = [];

        $dateCreated = Craft::$app->getRequest()->getParam('dateCreated');

        // @todo - move this logic to the service layer
        if ($dateCreated != null && $dateCreated != '*') {
            $seeds = SproutBase::$app->seed->getSeedsByDateCreated($dateCreated);
        }

        if ($dateCreated == '*') {
            $seeds = SproutBase::$app->seed->getAllSeeds();
        }

        // @todo - update weed method to accept a Weed model so we can validate.
        if (!SproutBase::$app->seed->weed($seeds, $isKeep)) {

            Craft::$app->getSession()->setError(Craft::t('sprout-import', 'Unable to weed data.'));

            return null;
        }

        Craft::$app->getSession()->setNotice(Craft::t('sprout-import', 'The garden is weeded.'));

        return $this->redirectToPostedUrl();
    }
}
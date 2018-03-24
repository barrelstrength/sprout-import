<?php

namespace barrelstrength\sproutimport\models\jobs;

use barrelstrength\sproutimport\queue\jobs\Import;
use craft\base\Model;

class ImportJobs extends Model
{
    /**
     * An array of Import jobs that have been submitted for import
     *
     * @var array
     */
    public $jobs = [];

    /**
     * Add an Import Job to the jobs array
     *
     * @param Import $importData
     */
    public function addJob(Import $importData)
    {
        $this->jobs[] = $importData;
    }
}
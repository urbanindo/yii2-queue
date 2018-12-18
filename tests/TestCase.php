<?php

namespace UrbanIndo\Yii2\QueueTests;

use yii\helpers\ArrayHelper;
use PHPUnit\Framework\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{

    /**
     * Populates Yii::$app with a new application
     * The application will be destroyed on tearDown() automatically.
     * @param array $config The application configuration, if needed
     * @param string $appClass name of the application class to create
     */
    protected function mockApplication($config = [], $appClass = '\yii\console\Application')
    {
        new $appClass(ArrayHelper::merge([
            'id' => 'testapp',
            'basePath' => __DIR__,
            'vendorPath' =>  __DIR__ . '/../vendor',
        ], $config));
    }    
}

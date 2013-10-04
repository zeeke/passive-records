<?php

//$yiit = dirname(__FILE__).'/../yii/framework/yiit.php';
//$config = require_once(dirname(__FILE__).'/config.php');
//require_once($yiit);
//
//Yii::createWebApplication($config);

define('YII_ENABLE_ERROR_HANDLER', false);
define('YII_DEBUG', true);
$_SERVER['SCRIPT_NAME'] = '/' . __DIR__;
$_SERVER['SCRIPT_FILENAME'] = __FILE__;

require_once(__DIR__ . '/../vendor/yiisoft/yii2/yii/Yii.php');

Yii::setAlias('@yiiunit', __DIR__);

//require_once(__DIR__ . '/TestCase.php');

<?php
/**
 * Created by PhpStorm.
 * User: tsingsun
 * Date: 2017/5/12
 * Time: 下午6:42
 */

namespace yiiunit\src;

use tsingsun\log\AccessLog;
use yii\helpers\FileHelper;
use yii\log\Dispatcher;
use yii\log\Logger;
use yiiunit\TestCase;

class FileAccessTargetTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();
        $this->mockWebApplication();
    }

    public function testAccessLog()
    {
        $logFile = \Yii::getAlias('@yiiunit/runtime/logs/filetargettest.log');
        FileHelper::removeDirectory(dirname($logFile));
        mkdir(dirname($logFile), 0777, true);


        $_SERVER['REQUEST_URI'] = '/';
        $al = new AccessLog();
        $al->lg_guid = uniqid();
        $logger = new Logger();
        $dispatcher = new Dispatcher([
            'logger' => $logger,
            'targets' => [
                'access' => [
                    'class' => 'tsingsun\log\FileAccessTarget',
                    'logFile' => $logFile,
                    'maxFileSize' => 1024, // 1 MB
                    'logVars' => [],
                ]
            ]
        ]);
        $logger->log('other',Logger::LEVEL_ERROR);

        $logger->log($al,AccessLog::LEVEL_ACCESS);
        $logger->flush(true);

        clearstatcache();
        $this->assertFileExists($logFile);
    }
}

<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace tsingsun\log;

use Yii;
use yii\log\FileTarget;
use yii\base\InvalidConfigException;
use yii\log\Logger;

/**
 * FileTarget records log messages in a file.
 *
 * The log file is specified via [[logFile]]. If the size of the log file exceeds
 * [[maxFileSize]] (in kilo-bytes), a rotation will be performed, which renames
 * the current log file by suffixing the file name with '.1'. All existing log
 * files are moved backwards by one place, i.e., '.2' to '.3', '.1' to '.2', and so on.
 * The property [[maxLogFiles]] specifies how many history files to keep.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class FileAccessTarget extends FileTarget
{
    public $config;

    /**
     * @var AccessLog
     */
    private static $baseAccessLog;


    /**
     * override
     * @param array $message
     * @return string
     */
    public function formatMessage($message)
    {
        $inLog = $message[0];
        return strval($inLog);
    }

    public function getLevels()
    {
        return AccessLog::LEVEL_ACCESS;
    }


    /**
     * for swoole runkit,export must place self class
     * @throws InvalidConfigException
     */
    public function export()
    {
        $text = implode("\n", array_map([$this, 'formatMessage'], $this->messages)) . "\n";
        if (($fp = @fopen($this->logFile, 'a')) === false) {
            throw new InvalidConfigException("Unable to append to log file: {$this->logFile}");
        }
        @flock($fp, LOCK_EX);
        if ($this->enableRotation) {
            // clear stat cache to ensure getting the real current file size and not a cached one
            // this may result in rotating twice when cached file size is used on subsequent calls
            clearstatcache();
        }
        if ($this->enableRotation && @filesize($this->logFile) > $this->maxFileSize * 1024) {
            $this->rotateFiles();
            @flock($fp, LOCK_UN);
            @fclose($fp);
            @file_put_contents($this->logFile, $text, FILE_APPEND | LOCK_EX);
        } else {
            @fwrite($fp, $text);
            @flock($fp, LOCK_UN);
            @fclose($fp);
        }
        if ($this->fileMode !== null) {
            @chmod($this->logFile, $this->fileMode);
        }
    }

    public function collect($messages, $final)
    {
        self::$baseAccessLog = AccessLog::createBaseAccessLog($this->config);
        parent::collect($messages, $final);
    }


    /**
     * override
     * Filters the given messages according to their categories and levels.
     * @param array $messages messages to be filtered.
     * The message structure follows that in [[Logger::messages]].
     * @param integer $levels the message levels to filter by. This is a bitmap of
     * level values. Value 0 means allowing all levels.
     * @param array $categories the message categories to filter by. If empty, it means all categories are allowed.
     * @param array $except the message categories to exclude. If empty, it means all categories are allowed.
     * @return array the filtered messages.
     */
    public static function filterMessages($messages, $levels = 0, $categories = [], $except = [])
    {
        $hasError = 0;
        foreach ($messages as $i => $message) {
            if ($message[1] == Logger::LEVEL_ERROR) {
                $hasError = 1;
            }
            if ($levels && !($levels & $message[1])) {
                unset($messages[$i]);
                continue;
            }

            $matched = empty($categories);
            foreach ($categories as $category) {
                if ($message[2] === $category || !empty($category) && substr_compare($category, '*', -1, 1) === 0 && strpos($message[2], rtrim($category, '*')) === 0) {
                    $matched = true;
                    break;
                }
            }

            if ($matched) {
                foreach ($except as $category) {
                    $prefix = rtrim($category, '*');
                    if (($message[2] === $category || $prefix !== $category) && strpos($message[2], $prefix) === 0) {
                        $matched = false;
                        break;
                    }
                }
            }

            if (!$matched) {
                unset($messages[$i]);
            }

        }
        foreach ($messages as $message) {
            $aLog = $message[0];
            $aLog->sd_05 = $hasError;
            self::getFinalAccessLog($aLog);
        }
        return $messages;
    }


    /**
     * @param AccessLog $inLog
     * @return AccessLog
     */
    private static function getFinalAccessLog(&$inLog)
    {
        $baseLog = self::$baseAccessLog;
        $baseLog->lg_guid = str_replace('.', '', uniqid('', true));
        $sa = (array)$baseLog;
        $sa = array_filter($sa);
        foreach ($sa as $key => $value) {
            $inLog->$key = $value;
        }
        return $inLog;
    }


}

<?php


namespace tsingsun\log;

use Yii;
use yii\log\Target;
use yii\log\Logger;

/**
 * 本类的实现参考了TP
 * github: https://github.com/luofei614/SocketLog
 * @author tsingsun<21997272@qq.com>
 */
class SocketLogTarget extends Target
{
    public $port = 1116; //SocketLog 服务的http的端口号

    protected $allowForceClientIds = []; //配置强制推送且被授权的client_id

    public $config = [
        // socket服务器地址
        'host' => 'localhost',
        // 是否显示加载的文件列表
        'show_included_files' => false,
        // 日志强制记录到配置的client_id
        'force_client_ids' => [],
        // 限制允许读取日志的client_id
        'allow_client_ids' => [],
    ];

    protected static $css = [
        'sql' => 'color:#009bb4;',
        'sql_warn' => 'color:#009bb4;font-size:14px;',
        'error' => 'color:#f4006b;font-size:14px;',
        'page' => 'color:#40e2ff;background:#171717;',
        'big' => 'font-size:20px;color:red;',
    ];

    /**
     * override
     * @param array $message
     * @param array $traces
     * @return string
     */
    public function formatTraceMessage($message, &$traces)
    {

        $logType = Logger::getLevelName($message[1]);
        if ($message[0] instanceof \Throwable) {
            $msg = $message[0]->getMessage();
        } elseif (!is_string($message[0])) {
            $msg = var_export($message[0], true);
        } else {
            $msg = $message[0];
        }
        $msg = "[{$logType}]" . $msg;
        if ($message[1] == Logger::LEVEL_ERROR || $message[2] == Logger::LEVEL_WARNING) {
            $traces[] = self::createTrace('groupCollapsed', $msg, '');
            if ($message[0] instanceof \Throwable) {
                $finfo = "in {$message[0]->getFile()}:{$message[0]->getLine()}";
                $traces[] = self::createTrace('log', $finfo);
            } else if ($message[4]) {
                foreach ($message[4] as $file) {
                    $finfo = "in {$file['file']}:{$file['line']}";
                    $traces[] = self::createTrace('log', $finfo);
                }
            }
            $traces[] = self::createTrace('groupEnd', '');
        } else {
            $traces[] = self::createTrace('log', $msg);
        }
    }

    private static function createTrace($type, $msg, $css = '')
    {
        return $trace[] = [
            'type' => $type,
            'msg' => $msg,
            'css' => isset(self::$css[$type]) && empty($css) ? self::$css[$type] : '',
        ];
    }

    /**
     * override
     * @return bool
     */
    public function export()
    {
        if (!$this->check()) {
            return false;
        }
        $runtime = number_format(Yii::$app->getLog()->getLogger()->getElapsedTime(), 10);
        $reqs = $runtime > 0 ? number_format(1 / $runtime, 2) : '∞';
        $time_str = ' [elapsed time：' . number_format($runtime, 6) . 's][throughput rate：' . $reqs . 'req/s]';
        $file_load = ' [file load：' . count(get_included_files()) . ']';

        if (isset($_SERVER['HTTP_HOST'])) {
            $current_uri = Yii::$app->getRequest()->getAbsoluteUrl();
        } else {
            $current_uri = 'cmd:' . implode(' ', $_SERVER['argv']);
        }
        // top info
        $traces[] = [
            'type' => 'group',
            'msg' => $current_uri . $time_str . $file_load,
            'css' => self::$css['page'],
        ];
        foreach ($this->messages as $message) {
            $this->formatTraceMessage($message, $traces);
        }
        if ($this->config['show_included_files']) {
            $traces[] = [
                'type' => 'groupCollapsed',
                'msg' => '[ file ]',
                'css' => '',
            ];
            $traces[] = [
                'type' => 'log',
                'msg' => implode("\n", get_included_files()),
                'css' => '',
            ];
            $traces[] = [
                'type' => 'groupEnd',
                'msg' => '',
                'css' => '',
            ];
        }

        $traces[] = [
            'type' => 'groupEnd',
            'msg' => '',
            'css' => '',
        ];

        $tabid = $this->getClientArg('tabid');
        if (!$client_id = $this->getClientArg('client_id')) {
            $client_id = '';
        }

        if (!empty($this->allowForceClientIds)) {
            //强制推送到多个client_id
            foreach ($this->allowForceClientIds as $force_client_id) {
                $client_id = $force_client_id;
                $this->sendToClient($tabid, $client_id, $traces, $force_client_id);
            }
        } else {
            $this->sendToClient($tabid, $client_id, $traces, '');
        }
        return true;
    }

    /**
     * 发送给指定客户端
     * @author Zjmainstay
     * @param $tabid
     * @param $client_id
     * @param $logs
     * @param $force_client_id
     */
    protected function sendToClient($tabid, $client_id, $logs, $force_client_id)
    {
        $msg = [
            'tabid' => $tabid,
            'client_id' => $client_id,
            'logs' => $logs,
            'force_client_id' => $force_client_id,
        ];
        $msg = @json_encode($msg);
        $address = '/' . $client_id; //将client_id作为地址， server端通过地址判断将日志发布给谁
        $this->send($this->config['host'], $msg, $address);
    }

    protected function check()
    {
        $tabid = $this->getClientArg('tabid');
        //是否记录日志的检查
        if (!$tabid && !$this->config['force_client_ids']) {
            return false;
        }
        //用户认证
        $allow_client_ids = $this->config['allow_client_ids'];
        if (!empty($allow_client_ids)) {
            //通过数组交集得出授权强制推送的client_id
            $this->allowForceClientIds = array_intersect($allow_client_ids, $this->config['force_client_ids']);
            if (!$tabid && count($this->allowForceClientIds)) {
                return true;
            }

            $client_id = $this->getClientArg('client_id');
            if (!in_array($client_id, $allow_client_ids)) {
                return false;
            }
        } else {
            $this->allowForceClientIds = $this->config['force_client_ids'];
        }
        return true;
    }

    protected function getClientArg($name)
    {
        static $args = [];

        $key = 'HTTP_USER_AGENT';

        if (isset($_SERVER['HTTP_SOCKETLOG'])) {
            $key = 'HTTP_SOCKETLOG';
        }

        if (!isset($_SERVER[$key])) {
            return null;
        }
        if (empty($args)) {
            if (!preg_match('/SocketLog\((.*?)\)/', $_SERVER[$key], $match)) {
                $args = ['tabid' => null];
                return null;
            }
            parse_str($match[1], $args);
        }
        if (isset($args[$name])) {
            return $args[$name];
        }
        return null;
    }

    /**
     * @param string $host - $host of socket server
     * @param string $message - 发送的消息
     * @param string $address - 地址
     * @return bool
     */
    protected function send($host, $message = '', $address = '/')
    {
        $url = 'http://' . $host . ':' . $this->port . $address;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $message);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $headers = [
            "Content-Type: application/json;charset=UTF-8",
        ];
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); //设置header
        return curl_exec($ch);
    }

}

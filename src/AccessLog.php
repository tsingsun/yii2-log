<?php

namespace tsingsun\log;

use Yii;
/**
 * 定义了访问日志协议
 */
class AccessLog {

    const LEVEL_ACCESS = 0x80;
    //ctrl+A
    //private $splitChar='\001';

    //日志的GUID(32)
    public $lg_guid;
    //本记录的类型id
    public $lg_type;
    //时间日期
    public $t_time;
    //时间戳(秒)
    public $t_ts;
    //服务端时区(中国上海）
    public $t_gmt;
    //客户端时区
    public $t_cgmt;
    //访次跟踪字段
    public $vt_vsi;
    //访客跟踪字段（Cookie)
    public $vt_vid;
    //设备号
    public $vt_did;
    //用户账号(唯一标示)
    public $vt_uid;
    //客户端ip
    public $vt_cip;
    //访次信息
    public $vt_si;
    //访次开始时间
    public $vt_sst;
    //国家
    public $gc_country;
    //城市
    public $gc_city;
    //经度
    public $gc_la;
    //维度
    public $gc_lo;
    //域名
    public $wb_ds;
    //Cookie字符串
    public $wb_cs;
    //是否支持Cookie
    public $wb_sc;
    //url参数串
    public $wb_up;
    //浏览器大小
    public $wb_bs;
    //浏览器类型
    public $wb_bt;
    //屏幕色彩位数
    public $sys_bc;
    //屏幕分辨率
    public $sys_sr;
    //操作系统
    public $sys_os;
    //设备机型
    public $sys_dt;
    //网络类型
    public $sys_nt;
    //语言类型
    public $sys_lt;
    //
    public $at_cid;
    //版本号
    public $at_cvn;
    //应用服务应用id
    public $at_sid;
    //应用服务器ip
    public $at_sip;
    //应用服务器应用版本号
    public $at_svn;
    //检索服务应用id
    public $at_ssid;
    //检索服务器ip
    public $at_ssip;
    //检索服务器应用版本号
    public $at_ssvn;
    //流程名称
    public $si_n;
    //流程步骤名称
    public $si_p;
    //是否转化步骤
    public $si_cs;
    //页面标题
    public $si_ti;
    //事件ID
    public $et_ei;
    //系统保留参数01,是否是请求入口
    public $sd_01;
    //系统保留参数02,//访问的禁止状态,0禁止,1允许,2验证
    public $sd_02;
    //系统保留参数03,渠道号
    public $sd_03;
    //app版本
    public $sd_04;
    //访问是否有异常,0,正常,1异常
    public $sd_05;
    //销售渠道
    public $sd_06;
    //系统保留参数07
    public $sd_07;
    //系统保留参数08
    public $sd_08;
    //系统保留参数09
    public $sd_09;
    //系统保留参数10
    public $sd_10;
    //系统保留参数11
    public $sd_11;
    //系统保留参数12
    public $sd_12;
    //系统保留参数13
    public $sd_13;
    //系统保留参数14
    public $sd_14;
    //系统保留参数15
    public $sd_15;
    //系统保留参数16
    public $sd_16;
    //系统保留参数17
    public $sd_17;
    //系统保留参数18
    public $sd_18;
    //系统保留参数19
    public $sd_19;
    //系统保留参数20
    public $sd_20;
    //关键字
    public $ud_01;
    //城市中文名
    public $ud_02;
    //用户自定义
    public $ud_03;
    //用户自定义
    public $ud_04;
    //用户自定义
    public $ud_05;
    //用户自定义
    public $ud_06;
    //用户自定义
    public $ud_07;
    //用户自定义
    public $ud_08;
    //用户自定义
    public $ud_09;
    //用户自定义
    public $ud_10;
    //用户自定义
    public $ud_11;
    //用户自定义
    public $ud_12;
    //用户自定义
    public $ud_13;
    //用户自定义
    public $ud_14;
    //用户自定义
    public $ud_15;
    //用户自定义
    public $ud_16;
    //用户自定义
    public $ud_17;
    //用户自定义
    public $ud_18;
    //用户自定义
    public $ud_19;
    //用户自定义
    public $ud_20;
    //用户自定义
    public $ud_21;
    //用户自定义
    public $ud_22;
    //用户自定义
    public $ud_23;
    //用户自定义
    public $ud_24;
    //用户自定义
    public $ud_25;
    //用户自定义
    public $ud_26;
    //用户自定义
    public $ud_27;
    //用户自定义
    public $ud_28;
    //用户自定义
    public $ud_29;
    //用户自定义
    public $ud_30;
    //用户自定义
    public $ud_31;
    //用户自定义
    public $ud_32;
    //用户自定义
    public $ud_33;
    //用户自定义
    public $ud_34;
    //用户自定义
    public $ud_35;
    //用户自定义
    public $ud_36;
    //用户自定义
    public $ud_37;
    //用户自定义
    public $ud_38;

    function __toString()
    {
        $splitChar="\001";
        $aObject = (array)$this;
        $so = implode($splitChar,$aObject);
        return $so;
    }

    public static function createBaseAccessLog($config)
    {
        $request = Yii::$app->getRequest();
        $blog = new AccessLog();
        $blog->t_gmt = Yii::$app->getTimeZone();
        $lg_type = self::getRequestId();
        $blog->lg_type = strtolower($lg_type);
        $blog->at_cid = $config['at_cid'];
        $blog->at_cvn = $config['at_cvn'];
        $blog->at_sid = $config['at_sid'];
        $blog->at_sip = self::getServerIP();
        $blog->t_time = date('Y-m-d H:i:s', YII_BEGIN_TIME);
        $blog->t_ts = YII_BEGIN_TIME;
        $blog->vt_cip = $request->getUserIP();
        $params = $request->getBodyParams();
        if ($request->getRawBody()) {
            $params['rawBody'] = $request->getRawBody();
        }
        $abUrl = $request->getAbsoluteUrl();
        $blog->wb_up = $params
            ? $abUrl . ($request->getQueryString() ? '&' : '?') . http_build_query($params)
            : $abUrl;
        $blog->sd_07 = round(Yii::$app->getLog()->getLogger()->getElapsedTime(), 2);
        return $blog;
    }

    private static function getRequestId()
    {
        $controller = Yii::$app->controller;
        if (!$controller) {
            return null;
        }
        $controllerName = $controller->id;
        $moduleName = Yii::$app->controller->module->id == $controller->module->id ? $controller->module->id : Yii::$app->id . '.' . $controller->module->id;
        $actionName = $controller->action ? $controller->action->id : $controller->defaultAction;
        $result = $moduleName . '.' . $controllerName . '.' . $actionName;
        return $result;
    }

    private static function getServerIP()
    {
        if (isset($_SERVER)) {
            if (isset($_SERVER['SERVER_ADDR'])) {
                $server_ip = $_SERVER['SERVER_ADDR'];
            } elseif (isset($_SERVER['LOCAL_ADDR'])) {
                $server_ip = $_SERVER['LOCAL_ADDR'];
            } else
                $server_ip = '0.0.0.0';
        } else {
            $server_ip = getenv('SERVER_ADDR');
        }
        return $server_ip;
    }
}
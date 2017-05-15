# 基于Yii2的Log组件

* 用户访问日志的FileAccessTarget组件，采用的是自定义的协议，应该说能用性不是很多，但可以根据实际应用使用，如果有好的用户行为日志格式，希望你能告诉我。
* 基于socketLog的远程调试日志组件，[socketLog传送门](https://github.com/luofei614/SocketLog)

### 安装
```php
composer require tsingsun/yii2-log
```

### FileAccess

基于文件的用户行为记录，可以配合fluentd + kafka的日志组合来使用

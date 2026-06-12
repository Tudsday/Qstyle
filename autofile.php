<?php
error_reporting(E_ALL ^E_NOTICE);
//引入类库文件;
include('./include/Qstyle.class.php');
define('TIME', microtime(true));
// 我们设置一个目录.
$Qstyle = new Qstyle([QStyle::CNF_UPDATE => true, QStyle::CNF_DEBUG => true]);

// 测试程序启用每次均更新, 假如不设置此项, 将在模板修改之后才更新.
$Qstyle->conf( Qstyle::CNF_UPDATE, true);

// 设置默认搜索目录.多个目录, 直接将方法调用多次.
// 注意看模板中写的代码, 是完全没有路径的. 
$Qstyle->conf(Qstyle::CNF_TPLDIR, ['./Data/default']);
$Qstyle->conf(Qstyle::CNF_AUTODIR, ['./testing']);
$Qstyle->conf(Qstyle::CNF_AUTODIR, ['./include']);


// 引入显示层模板, 模板层也支持自动搜索.
$Qstyle->load('__autofile');
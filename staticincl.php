<?php
error_reporting(E_ALL ^E_NOTICE);
//引入类库文件;
include('./include/Qstyle.class.php');
define('TIME', microtime(true));
// 我们设置一个目录.
$Qstyle = new Qstyle(true,false);

// 测试程序启用每次均更新, 假如不设置此项, 将在模板修改之后才更新.
$Qstyle->conf(Qstyle::CNF_UPDATE,true);

$Qstyle->conf(Qstyle::CNF_TPLDIR,['./Data/default']);

// 设置默认搜索目录,
$Qstyle->conf(Qstyle::CNF_AUTODIR,['./testing/']);

// 引入显示层模板, 模板层也支持自动搜索.
$Qstyle->load('staticincl');
<?php
error_reporting(E_ALL ^E_NOTICE);
//引入类库文件;
include('./include/Qstyle.class.php');
define('TIME', microtime(true));
// 我们设置一个目录.
$Qstyle = new Qstyle();

$dateinfo = date('Y-m-d H:i:s');

// 测试程序启用每次均更新, 假如不设置此项, 将在模板修改之后才更新.
$Qstyle->Conf( Qstyle::CNF_UPDATE, true);
$Qstyle->Conf(Qstyle::CNF_DEBUG, true);

$Qstyle->Conf(Qstyle::CNF_TPLDIR, './Data/default');

// 设置默认搜索目录.
$Qstyle->Conf(Qstyle::CNF_AUTODIR, ['./testing']);

// 注意这儿, 是ajax的模板层
if( isset($_GET['ajax']) ){
    // 第二个参数为模板里面的block段
    $Qstyle->load('./block/demo','ajax_info'); 
    exit();
}

define('minfo2','11111111111');

// 同时load方法还支持这种写法, 直接数据字符当模板.
//  $Qstyle->load('<span>{$a}</span><span>{$b}</span><span>{$c}</span><span>{$d}</span>');

// 引入显示层模板
$Qstyle->load('block/demo');
<?php
error_reporting(E_ALL ^E_NOTICE);
//引入类库文件;
include('./include/Qstyle.class.php');
define('TIME', microtime(true));

// 我们设置一个目录.
$Qstyle = new Qstyle([QStyle::CNF_UPDATE => true, QStyle::CNF_DEBUG => true]);

$Qstyle->conf(Qstyle::CNF_TPLDIR, ['./Data/default']);

// 设置默认搜索目录,
$Qstyle->conf(Qstyle::CNF_AUTODIR, ['./testing']);

$Qstyle->assign('header', 'header');

$string = array();
$string['a']['b']['c']['d'] = '数组100分';
$a = 'a';
$b = 'b';
$c = 'c';
$d = 'd';

$newarr = range('a','d');
$newarr[] = 0;  // null, false, 0, '' 看显示效果.

$Qstyle->setlang('msg','语言描述');

// 引入显示层模板, 模板层也支持自动搜索.
$Qstyle->load('outer');
<?php
error_reporting(E_ALL ^E_NOTICE);
//引入类库文件;
include('./include/Qstyle.class.php');
define('TIME', microtime(true));
// 我们设置一个目录.
// 支持多个目录. 如 new Qstyle('./Data/default/','aa/bb/cc/', 'new/img/');  当然也是支持数组的 new Qstyle( array('./Data/default/','aa/bb/cc/', 'new/img/'));
// 需要注意的是, 数组后面的目录搜索时优化使用, 比如三个目录中都有a.html模板, 那么它会先选择 new/img/ 当中的.
$Qstyle = new Qstyle(true,true);

// 设置默认的模板后缀, 这步可以省略, 程序默认为.html. 第二个参数为缓存后缀, 一般不需要设置.
$Qstyle->conf(Qstyle::CNF_SUFFIX, '.html');

$Qstyle->conf(Qstyle::CNF_TPLDIR, ['./Data/default']);
$Qstyle->conf(Qstyle::CNF_AUTODIR, ['./testing']);

// 引入显示层模板
// 注意看路径是无后缀的.
// 同时模板引入支持在原模板目录路径下继续定位目录, 如下面所示. 
$Qstyle->load('./loadtpl/loadtpl');

// 同样的道理, 当路径找不到时, 它仍然会去其它目录寻找文件. 
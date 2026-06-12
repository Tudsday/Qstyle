<?php declare(strict_types=1);
/*
# @category: 分享工作室;
# @var; index PHPnew;
# @version: Qstyle 8.5.0;
*/

$get_usage = memory_get_usage();
$get_peak_usage = memory_get_peak_usage();

define('PHPnew','index');
header('Content-Type: text/html; Charset=utf-8');
define('TIME', microtime(true));
error_reporting(E_ALL ^E_NOTICE);

//引入类库文件;
include('./include/Qstyle.class.php');
$Qstyle = new Qstyle();

$Qstyle->conf(Qstyle::CNF_TPLDIR, './Data/default');
$Qstyle->conf(Qstyle::CNF_AUTODIR, ['./testing'] );

$Qstyle->replace(['行业'=>'+行业'],'<');

############################# 变量开始产生 ###########################################################3
# 第一步
$instalnew = highlight_string("
    增加{ABC[var][\$sex]} 常量数组的支持. 
    支持 PHP8.3+ 
",true);

# 第一步
$install1 = highlight_string("
	<?php 
    # 开始前， 我们先了解一下几个概念：
    
 	//引入类库文件, 类库文件可换成任意路径。
	include('./include/Qstyle.class.php');
	//模板引擎实例;
    // 也可带入参数 new Qstyle(true,true); 第一个参数保持更新, 第二个参数开启调试日志. 这两个参数都可在实例化后用属性来设置.
	\$Qstyle = new Qstyle(true,true);
 
	// load方法第一个参数是模板文件名，模板文件将从你设置的模板路径中寻找.
    // 参数同时也支持绝对路径。即读取指定的模板文件。类似： \$Qstyle->load('./dir/phpnew.tpl');
	\$Qstyle->load('phpnew');
",true);

# 第二步
$install2 = highlight_string("
	<?php 
    // php执行代码
    \$new_var = '我是新的变量';
    
 	//引入类库文件, 等php执行完成后再引入模板类库即可。以下三行,可自行封装成函数或者类方法.
	include('./include/Qstyle.class.php');
	//模板引擎实例;
	\$Qstyle = new Qstyle();
    
	//最后输出页面. phpnew.html 模板中写代码{\$new_var}即可显示变量内容。非常方便。
	\$Qstyle->load('phpnew');
",true);

# 第三步
$install3 = highlight_string("
	<?php 
	include('./include/Qstyle.class.php');
	\$Qstyle = new Qstyle();
    
    // 普通赋值.
    \$new_var = '我是新的变量';
    // 兼容smarty的模式， 如果你以前学过, 仍然可以使用.
    \$Qstyle->assign('user_pay',15.26);
    
    # 两种模式可以同时使用, 互不影响.
	//最后输出页面. phpnew.html 模板中写代码{\$new_var},{\$user_pay} 即可显示两个变量内容。
    
	\$Qstyle->load('phpnew'); 
",true);

# 第四步
$install4 = highlight_string("
	<?php 
	include('./include/Qstyle.class.php');
	\$Qstyle = new Qstyle();
    \$new_var = '我是php变量';
    \$Qstyle->assign('user_pay',15.26);
    
    # 事实上模板引擎还支持php原生态. 这对于许多喜欢原生态的朋友来说, 也是很好地兼容.
    
	//phpnew.html 模板中写代码{\$new_var},{\$user_pay} {eval echo strlen(52356345234)} 均可以.
	\$Qstyle->load('phpnew'); 
",true);

# 第五步
$install5 = highlight_string("
	<?php 
	include('./include/Qstyle.class.php');
	\$Qstyle = new Qstyle();
    // 定义一个数组.
    \$new_arr = array('yuan'=>'1200','tom'=>'1300','lisa'=>'1500')
    
	//phpnew.html 模板代码:
    <!--{loop \$new_arr \$key \$val}-->
        {\$key} 的工资是:{\$val}
    <!--{/loop}-->
    
	\$Qstyle->load('phpnew'); 
    # 然后访问看看, 是否已经循环出数组在模板中了?
",true);

# 第六步
$install6 = highlight_string("
	<?php 
	include('./include/Qstyle.class.php');
	\$Qstyle = new Qstyle();
    // 定义一个数组.
    \$new_arr = array('yuan'=>'1200','tom'=>'1300','lisa'=>'1500')
    
	//phpnew.html 模板代码:
    <!--{if is_array(\$new_arr) === true}-->
        \$new_arr是数组.
    <!--{else}-->
        \$new_arr不是数组.
    <!--{/if}-->
    
	\$Qstyle->load('phpnew'); 
",true);

# 第七步
$install7 = highlight_string('
	<?php
          # 以下代码规则是针对html模板文件而讲解. 
	      "{eval echo 111}"	 智能模式.简单运行php代码;
	      "{block name}{/block}" block代码运行块, 当你写完一块后, 后续可以多次调用;
	      "{LF}" 为提示产生换行符, 即&#10;
	      "{lang zn}" 语言模板语法, 可通过方法 setlang($key, $val) 实现传入语言包.
          "{load header}" 普通常用的模板文件相互引入;
	      "{load $footer}" 模板文件相互引入的过程支持变量;
	      "{loads header}" 模板文件相互引入支持静态引入;
          "//TODO: 需要标注的信息" 新版本支持todo写法.分号为结束符号
          "#BUG: 需要标注的bug信息" 新版本支持bug写法. 分号为结束符号
          "{__my.jpg}" 支持这样引入文件, 不需要关心路径.
	?>
	',true);
# 第八步
$install8 = highlight_string("
	<?php
	include('./include/Qstyle.class.php');

	# 实例化, 可传入配置数组
	\$Qstyle = new Qstyle([
	    QStyle::CNF_UPDATE => true,   // 是否每次请求都重编译模板
	    QStyle::CNF_DEBUG  => true,   // 开启调试模式
	]);

	# 使用 conf() 方法配置, 支持常量名或属性名
	\$Qstyle->conf(QStyle::CNF_TPLDIR,   './Data/default');  // 模板目录
	\$Qstyle->conf(QStyle::CNF_AUTODIR,  ['./testing']);     // 自动搜索目录
	\$Qstyle->conf(QStyle::CNF_CACHEDIR, './cache');         // 缓存目录
	\$Qstyle->conf(QStyle::CNF_SUFFIX,   '.html');            // 模板后缀
	\$Qstyle->conf(QStyle::CNF_UPDATE,   true);               // 强制每次重编译
	\$Qstyle->conf(QStyle::CNF_DEBUG,    true);               // 开启调试
	\$Qstyle->conf(QStyle::CNF_ENKEY,    'my_secret_key');    // 安全密钥, 影响缓存文件名

	# 变量赋值
	\$Qstyle->assign('title', 'Hello');           // 单个赋值
	\$Qstyle->assign(['name' => 'World']);        // 批量赋值

	# 语言包
	\$Qstyle->setlang('msg', 'Welcome');
	\$Qstyle->setlang(['greeting' => ['hello' => 'Hi']]);

	# 模板内容替换
	\$Qstyle->replace(['old_text' => 'new_text'], '<');  // 解析前替换
	\$Qstyle->replace(['old_text' => 'new_text'], '>');  // 解析后替换

	# 加载并渲染模板
	\$Qstyle->load('phpnew');

	?>
	",true);

# 第九步
$install9 = highlight_string("
	<?php 
	include('./include/Qstyle.class.php');
	\$Qstyle = new Qstyle();
	\$var = 'Qstyle 模板引擎';
	\$Qstyle->load('phpnew');
    
    # 如果你需要全局查看, 可以在load调用后一行进行实例变量打印,
    print_r(\$Qstyle);
    
",true);



# 第十步
$install10 = highlight_string("
	<?php 
	include('./include/Qstyle.class.php');
	\$Qstyle = new Qstyle();

	#建议使用assign方法设置变量. 会让架构整体更明了, 知道哪些变量是释放到模板的.
    \$Qstyle->assign('vars', 100); // 设置\$vars为100;
    
    // 当然, 你可以进行数组释放变量
    \$arr = array('vars'=>100,   'vardemo'=> 200);
    \$Qstyle->assign(\$arr);
 
    # 即可释放出两个变量. 在模板{\$vardemo} 即可显示200; 方法可以使用多次.
    
    \$Qstyle->load('phpnew');
",true);

# 第十一步
$install11 = highlight_string("
	<?php 
	include('./include/Qstyle.class.php');
	\$Qstyle = new Qstyle();
	# 静态文件自动匹配功能. 为了解决图片, js, css文件路径问题而生.

    // 首先你需要告诉引擎, 图片, js, css文件放在哪个目录, 即静态文件.
    \$Qstyle->conf(QStyle::CNF_AUTODIR, './Static/');
    # 以站点根目录为起点寻找Static目录, 方法会自动搜寻目录中的所有文件, 无论里面放了多少层目录, 它都会找到. 
    # 方法可以调用多次, 以支持多个目录搜索. 优先权跟模板目录一样以倒序为标准. 引擎以这样来适应叠加目录.
    
    // 当多个目录里面保存了相同的文件时, 系统以找到之后即停止搜索的原理解析搜索.
    // 自动搜索功能仅在模板解析时处理一次, 压力小, 速度快.
    // 接着在模板中就可以用以下两种方法使用此功能. {__demo.jpg} 即可以匹配到demo的相对路径. html, css, js中均可以这样使用, 以下是一些示例.
    
    '<style>{__comon.css}</style>'   # 引入comon.css 缓存文件, 是缓存文件.
    '<img src=\"{__1.jpg}\" />'        # html中引入图片的快速写法,
    '<img src=\"{__{\$str}.jpg}\" />'   # html中引入图片支持变量写法,
    '<div style=\"background: url({__bg.jpg});\">div string</div>' # 背景图片引入.

    # 在引擎环境中, 用{__file.js} 引入文件将大大改善混乱问题, 路径问题交给程序解决吧.
    
    # !{load } 由于模板语法load, 通常大家都不会写后缀名, 所以模板语法不能使用自动匹配路径语法!
    \$Qstyle->load('phpnew');
",true);


# 第十二步
$install12 = highlight_string("
	<?php 
	include('./include/Qstyle.class.php');
	\$Qstyle = new Qstyle();
    
    # 为了解决模板与css, js相独立的问题, 模板引擎增加了静态文件解析功能. 所有的引入文件js, css都将具有语法解析功能.
	# 静态文件除了可解析自动路径外, 也支持简单的变量解析了.
    # 为什么不像模板一样, 全部解析语法? 因为css, js文件解析后, 需要保存为相同的后缀, 它是不经过php的, 所以无法释放php代码. 全部解析成静态的值.
    # 所以, assign 设置变量的方法由此而产生, 建议项目中用这个方法设置的值,一直保持不变.理解为静态变量. css, js里面默认支持常量,也不失为一种方法.
    
    # 建议手工释放静态文件中的变量. 此步也可省略, 它会自动继承全局assign. 全局assign的可变性将对结果有所影响.
    \$Qstyle->assign('color','red');
    \$Qstyle->assign(array('keys'=>'red'));  // {\$keys}
    \$Qstyle->assign('color',array('keys'=>'red'));  // \$color['keys'];
        
    # 解析后的静态文件被写入在缓存目录中.
    \$Qstyle->load('phpnew');
",true);

# 第十三步
$install13 = highlight_string("
	<?php 
    # 公共方法如下: 
	/*
    [1] => load                 php, tpl代码加载方法, 支持字符串解析, block块解析, 选择器解析.
    [2] => assign               赋值变量.
    [3] => print                打印源码. 
    [4] => setlang              设置语言值数据
    [5] => conf                 设置类属性值, 基本上均由此设置.
    [6] => replace              设置html替换字符 
    [20] => clear               删除缓存, 默认全部删除.
    */
",true);

$install4 = highlight_string("
    <?php 
    
    # 非常简洁变通的调用方法. 一个方法实现所有功能.
    # 同时支持字符串解析.
    
    # 变通调用方式
    \$Qstyle->load('phpnew');
    
    # 假如需要对整个模板目录深层搜索, 可以用到 __header的方式.
      // \$Qstyle->load('__phpnew');
    
    # 也支持目录引用, 以模板目录为基点再寻找目录文件.
      // \$Qstyle->load('./music/index');
    
    //可以选择用字符方式来加载, 字符串的写法跟普通模板完全一样.
      // \$Qstyle->load(file_get_contents('./Data/default/phpnew.html'));
    
    # 字符串加载, 支持多次调用.(无解析工作的纯字符串尽量别用load解析, 没任何意义)
     // \$usercpu = 'my name';
     // \$Qstyle->load('<br />我是纯字符的解析 ={\$usercpu}');
    
    # 支持打印解析后的代码.
      \$Qstyle->print('<br />我是纯字符的解析 ={\$usercpu}');
    
    # 第二个参数字符串将调用block, .class, #id 的html内容, 类似jquery
      \$Qstyle->load('phpnew', 'blockname');  
",true);

$install5 = highlight_string("
    <?php 
    
    # load方法 字符串 & block 加载
    
    # 变通调用方式可能是以下两种, 第二个参数true时, 返回路径.
    \$Qstyle->load('phpnew');
    \$Qstyle->load('d:\xx');  //绝对路径 
    \$Qstyle->load('./music/phpnew'); // 相对路径 (这个相对是模板的根目录开始计算)
    \$Qstyle->load('__phpnew');  // 假如你不知道文件放在多模板目录的位置, 可以用 __文件名 来实现自动寻找.
    
    # 为了解决ajax的简单返回值, 比如一串字符, 里面又套上几个变量, 假如json返回到前端, 处理起来就复杂得多. 
    \$PHPnew->load('<span>{\$a}</span><span>{\$b}</span><span>{\$c}</span><span>{\$d}</span>');
      // 这样是否会感觉舒服很多? 当第二个参数传入true时, 将返回php源码.
      
    # 不过, 有时ajax需要返回一大块div,或者table串, 那和字符方式也比较难控制, 平时我们就需要创建一个文件, 然后用load来加载, 现在不需要这么复杂了.
    \$Qstyle->load('phpnew','_ajax'); // ('模板名','bolck名'),
      // 这一步骤将使得平时少用的block标签疯狂起来. 你可以在模板中定义多个block块, 既然平时不用, 也没关系, 它默认不会显示. 当有特殊需求时, 即可以用load block方式调用起来. 
      // 同时支持.class #id 选择器的方式调用. 这对于一些复杂的html结构, 需要返回特定的html块时, 是非常方便的.
    
    # 既然说了block, 那也详细讲讲. 以下是简单的一段block.
    {block abc}<span>{\$val}</span>{/block}  
      // 如果没人调用, 那它不会有所显示. 调用的方法很简单, 把block的名字括号起来即可. 如下
    <!--{loop \$arr \$val}-->
        {block \$abc}
    <!--{/loop}-->
      // 循环体中的代码会被block继承起来. 这样多块相同的模板, 便可以用block来处理.
    
    # 同样的道理, 假如在ajax php中只想调用模板html中的id=mytable, 那就
        \$Qstyle->load('phpnew','#mytable');
    
",true);
################################ 变量生成完成 ################################
$Qstyle->load('phpnew');

# 以下为内存监控.
function convert(int|float $size): string{
    if ($size <= 0) {
        return '0 b';
    }

    $unit = array('b','kb','mb','gb','tb','pb');
    $i = (int) floor(log((float) $size, 1024));
    $i = max(0, min($i, count($unit) - 1));

    return round((float) $size / pow(1024, $i), 2) . ' ' . $unit[$i];
}

$usermemory = convert(memory_get_usage() - $get_usage); // 123 kb
$peakmemory = convert(memory_get_peak_usage() - $get_peak_usage); // 123 kb

echo "<div style=\"color:#FFFFFF\">页面内存: $usermemory; 最高占用: $peakmemory</div>";
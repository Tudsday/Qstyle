# Qstyle 模板引擎 — 完整说明文档

> **版本：** 8.5.0（当前）/ 8.1.0（兼容版）
> **作者：** 分享工作室 Yuan
> **要求：** PHP 8.0+（v8.5.0）/ PHP 5.x+（v8.1.0）
> **许可：** 自由使用

---

## 一、概述

Qstyle 是一款轻量、高效的 PHP 模板引擎。它将自定义模板语法编译为 PHP 缓存文件执行，兼具 **Smarty 兼容性** 与 **原生 PHP 灵活性**。核心设计理念：

| 特性 | 说明 |
|------|------|
| **编译缓存** | 模板首次解析后生成 `.php` 缓存，后续直接 include，极速输出 |
| **自动更新** | 检测模板文件变更后自动重编译，无需手动清理缓存 |
| **安全防护** | 禁止模板中 `<?` 标签、XSS 转义（`enhtml`）、安全表达式校验 |
| **路径无关** | `{__file.ext}` 自动搜索静态文件，彻底消除路径混乱 |
| **CSS/JS 模板化** | 静态文件支持模板变量与常量解析 |
| **Block + AJAX** | `{block}` 块定义 + `load('tpl','block')` 局部渲染，天然适配 AJAX |
| **选择器提取** | `load('tpl','#id')` / `load('tpl','.class')` 类 jQuery 精准提取 |

---

## 二、目录结构

```
Qstyle/
├── include/
│   ├── Qstyle.class.php        ← 当前引擎 v8.5.0（PHP 8.3+）
│   └── Qstyle.class.v5.php     ← 兼容引擎 v8.1.0（旧版 PHP）
├── Data/
│   ├── index.html              ← 目录保护文件
│   └── default/                ← 默认模板目录
│       ├── header.html         ← 公共头部模板
│       ├── footer.html         ← 公共底部模板
│       ├── phpnew.html         ← 主文档/教程展示模板
│       ├── test.html           ← 综合语法测试模板
│       ├── outer.html          ← 变量模板/语言包演示
│       ├── cssjs.html          ← CSS/JS 模板化演示
│       ├── staticincl.html     ← 静态资源嵌入演示
│       ├── autofile.html       ← 自动路径搜索演示
│       ├── block/
│       │   └── demo.html       ← Block + AJAX 演示模板
│       └── loadtpl/
│           └── loadtpl.html    ← 子目录模板加载演示
├── testing/                    ← 静态资源目录（自动搜索用）
│   ├── jquery-1.11.2.min.js
│   ├── in.css                  ← 含模板语法的 CSS
│   ├── a.css                   ← 纯 CSS 样式
│   ├── phpnew.gif
│   ├── BACODC2500AO0001.jpg
│   └── subdir/
│       └── xampps.jpg
├── index.php                   ← 主入口（13 步教程演示）
├── block.php                   ← Block + AJAX 演示
├── outer.php                   ← 变量/语言包/默认值演示
├── cssjs.php                   ← CSS/JS 模板化演示
├── autofile.php                ← 自动路径搜索演示
├── staticincl.php              ← 静态资源 Base64 嵌入演示
├── loadtpl.php                 ← 子目录模板加载演示
└── 说明.txt                    ← 本说明文件
```

---

## 三、快速开始

### 3.1 最简调用

```php
<?php
include('./include/Qstyle.class.php');

$Qstyle = new Qstyle(true, true);   // 参数1=自动更新, 参数2=调试模式
$Qstyle->conf(Qstyle::CNF_TPLDIR, './Data/default');  // 设置模板目录
$Qstyle->conf(Qstyle::CNF_AUTODIR, ['./testing']);    // 设置静态搜索目录

$title = 'Hello Qstyle';
$Qstyle->load('phpnew');            // 加载并渲染 phpnew.html
```

### 3.2 使用 assign 赋值

```php
<?php
include('./include/Qstyle.class.php');
$Qstyle = new Qstyle();
$Qstyle->conf(Qstyle::CNF_TPLDIR, './Data/default');

$Qstyle->assign('title', 'Hello');           // 单个赋值
$Qstyle->assign(['name' => 'World']);        // 批量赋值

$Qstyle->load('phpnew');
```

### 3.3 模板文件示例（phpnew.html）

```html
<!DOCTYPE html>
<html>
<head><title>{$title}</title></head>
<body>
    <h1>{$title}</h1>
    <p>当前时间：{eval echo date('Y-m-d H:i:s');}</p>
</body>
</html>
```

---

## 四、配置详解

### 4.1 当前版本（v8.5.0）— 常量配置体系

使用 `conf()` 方法 + 类常量进行统一配置：

```php
$Qstyle->conf(Qstyle::CNF_TPLDIR,   './Data/default');  // 模板目录
$Qstyle->conf(Qstyle::CNF_AUTODIR,  ['./testing']);     // 自动搜索目录
$Qstyle->conf(Qstyle::CNF_CACHEDIR, './cache');         // 缓存目录
$Qstyle->conf(Qstyle::CNF_SUFFIX,   '.html');            // 模板后缀
$Qstyle->conf(Qstyle::CNF_UPDATE,   true);               // 强制每次重编译
$Qstyle->conf(Qstyle::CNF_DEBUG,    true);               // 开启调试
```

| 常量 | 属性 | 默认值 | 说明 |
|------|------|--------|------|
| `CNF_TPLDIR` | `$Cnf_tplDir` | `[]` | 模板目录路径数组，**后设置的优先搜索** |
| `CNF_SUFFIX` | `$Cnf_suffix` | `.html` | 模板文件后缀 |
| `CNF_UPDATE` | `$Cnf_update` | `false` | 是否每次请求都重编译模板 |
| `CNF_CACHEDIR` | `$Cnf_cacheDir` | 系统临时目录 | 编译缓存存放目录 |
| `CNF_AUTODIR` | `$Cnf_autoDir` | `[]` | `{__file}` 自动搜索目录数组 |
| `CNF_DEBUG` | `$Cnf_debug` | `false` | 调试模式（页面底部输出调试信息） |

**配置合并规则：**
- 数组类型：新值与旧值合并，**新值优先**（即后设置的目录先搜索）
- 布尔类型：强制转换
- 整型类型：强制转换
- 其他类型：若传入数组则取首个元素

### 4.2 兼容版本（v8.1.0）— 属性/方法配置体系

```php
$Qstyle->set_templates_path('./Data/default/');   // 添加模板目录
$Qstyle->set_cache_path('./Data/cache_tpl/');     // 设置缓存目录
$Qstyle->set_templates_suffix('.html', '.php');   // 模板后缀, 缓存后缀
$Qstyle->set_templates_auto(true);                // 自动更新
$Qstyle->set_templates_type('All');               // 变量模式: All|ASSIGN
$Qstyle->set_templates_oncenew(true);             // 强制当次更新
$Qstyle->set_templates_space(false);              // 清除无意义空白
$Qstyle->set_templates_isdebug(true);             // 调试模式
$Qstyle->set_templates_ankey('mykey');            // 安全码
$Qstyle->set_auto_path('./Static/');              // 自动搜索目录
$Qstyle->set_templates_replace(['旧词'=>'新词']); // 全局替换
$Qstyle->set_static_assign('color', 'red');       // CSS/JS 静态变量
$Qstyle->set_language('msg', '翻译文本');         // 语言包
```

| 属性 | 方法 | 默认值 | 说明 |
|------|------|--------|------|
| `$templates_dir` | `set_templates_path()` | `['./Data/default/']` | 模板目录数组 |
| `$templates_cache` | `set_cache_path()` | `'./Data/cache_tpl/'` | 缓存目录 |
| `$templates_postfix` | `set_templates_suffix()` | `.html` | 模板后缀 |
| `$templates_caching` | 同上（第2参数） | `.php` | 缓存后缀 |
| `$templates_var` | `set_templates_type()` | `All` | 变量模式（All=全局, ASSIGN=仅assign） |
| `$templates_auto` | `set_templates_auto()` | `true` | 自动更新 |
| `$templates_new` | `set_templates_oncenew()` | `false` | 强制当次更新 |
| `$templates_space` | `set_templates_space()` | `false` | 清除无意义空白 |
| `$templates_ankey` | `set_templates_ankey()` | `''` | 安全码（影响缓存文件名） |
| `$templates_isdebug` | `set_templates_isdebug()` | `false` | 调试模式 |
| `$templates_replace` | `set_templates_replace()` | `[]` | 全局替换规则 |

---

## 五、模板语法完整参考

### 5.1 变量输出

| 语法 | 示例 | 编译结果 | 说明 |
|------|------|----------|------|
| `{$var}` | `{$title}` | `<?php echo ($title ?? '');?>` | 输出变量 |
| `{$var[key]}` | `{$arr['name']}` | `<?php echo ($arr['name'] ?? '');?>` | 数组访问 |
| `{$var[a][b]}` | `{$user['info']['age']}` | 嵌套数组 | 多维数组 |
| `{$obj->prop}` | `{$Qstyle->Cnf_suffix}` | 对象属性 | 属性访问 |
| `{$$var}` | `{$$varname}` | 变量变量 | PHP `$$var` 风格 |
| `${$var}` | `${$varname}` | 变量变量 | 等价写法 |

### 5.2 常量输出

| 语法 | 示例 | 说明 |
|------|------|------|
| `{CONST}` | `{TIME}` | 输出 PHP 常量 |
| `{CONST[key]}` | `{ABC[var][$sex]}` | 常量数组访问（**v8.5.0 新增**） |
| `{Class::CONST}` | `{Qstyle::CNF_VERSION}` | 静态类常量 |
| `{Class::$prop}` | `{Qstyle::$prop}` | 静态类属性 |

### 5.3 默认值与二元判断

| 语法 | 示例 | 说明 |
|------|------|------|
| `{$var\|default}` | `{$title\|'无标题'}` | 变量为空时显示默认值 |
| `{$var\|true_val\|false_val}` | `{$status\|'启用'\|'禁用'}` | 二元（三元）条件表达式 |
| `{$arr[key]\|1\|0}` | `{$flag['active']\|1\|0}` | 数组 + 二元判断 |

### 5.4 流程控制

**条件判断：**

```html
<!--{if is_array($arr)}-->
    $arr 是数组
<!--{elseif count($arr) > 0}-->
    $arr 非空
<!--{else}-->
    $arr 为空
<!--{/if}-->
```

> 流程控制标签必须包裹在 HTML 注释 `<!-- -->` 中，避免被浏览器直接渲染。

**循环：**

```html
<!--{loop $arr $key $val}-->
    {$key} => {$val}
<!--{/loop}-->

<!--{loop $arr $val}-->
    {$val}
<!--{/loop}-->
```

- 第一种：`{loop 数组 键 变量}` — 同时获取键和值
- 第二种：`{loop 数组 变量}` — 仅获取值
- 内部自动使用安全数组包装器 `_A()`，非数组值不会报错

### 5.5 模板引入

| 语法 | 示例 | 说明 |
|------|------|------|
| `{load name}` | `{load header}` | 动态引入模板文件 |
| `{load $var}` | `{load $header}` | 变量模板名引入 |
| `{load __name}` | `{load __footer}` | 自动搜索模板文件 |
| `{loads name}` | `{loads header}` | 静态引入（编译时展开源码） |

**`{load}` 与 `{loads}` 的区别：**
- `{load}` — 运行时动态 include，每次执行都会走一遍
- `{loads}` — 编译时直接将源码展开到当前位置，后续不再动态加载，性能更优

### 5.6 Block 区块

**定义块：**

```html
{block userinfo}
    <div class="user">{$name} - {$email}</div>
{/block}
```

**调用块：**

```html
{block $userinfo}
```

> 未被调用的 block 不会显示任何内容。block 特别适合 AJAX 局部渲染场景。

### 5.7 自动路径搜索

| 语法 | 示例 | 说明 |
|------|------|------|
| `{__file.ext}` | `{__logo.jpg}` | 在 AUTODIR 中递归搜索文件，返回完整路径 |
| `{__file.css}` | `{__common.css}` | CSS 文件自动定位 |
| `{__file.js}` | `{__jquery.min.js}` | JS 文件自动定位 |

**使用场景：**

```html
<img src="{__photo.jpg}" />
<link rel="stylesheet" href="{__style.css}" />
<script src="{__app.js}"></script>
<div style="background: url({__bg.jpg});"></div>
```

> 自动搜索仅在模板解析时执行一次，搜索结果缓存在内存中，不影响运行时性能。

### 5.8 静态资源 Base64 嵌入

| 语法 | 示例 | 说明 |
|------|------|------|
| `{#file.ext}` | `{#icon.jpg}` | 将文件内容 Base64 编码为 data URI 嵌入 |

支持格式：jpg/jpeg, gif, png, ico, js, css, html

```html
<img src="{#logo.jpg}" />
<!-- 编译为: <img src="data:image/jpeg;base64,..." /> -->
```

### 5.9 执行 PHP 代码

| 语法 | 示例 | 说明 |
|------|------|------|
| `{eval statement}` | `{eval echo date('Y-m-d')}` | 执行 PHP 语句 |
| `{eval statement;}` | `{eval $x = 1;}` | 带分号也支持 |

> v8.5.0 安全策略：模板中禁止 `<?php ?>` 原生标签，统一转义为文本。请使用 `{eval}` 替代。

### 5.10 语言包

| 语法 | 示例 | 说明 |
|------|------|------|
| `{lang key}` | `{lang msg}` | 输出语言包值 |
| `{lang key/subkey}` | `{lang greeting/hello}` | 多层嵌套访问 |

**PHP 端设置：**

```php
$Qstyle->setlang('msg', '欢迎访问');
$Qstyle->setlang(['greeting' => ['hello' => '你好']]);
```

### 5.11 HTML 实体编码（XSS 防护）

| 语法 | 示例 | 说明 |
|------|------|------|
| `{enhtml expr}` | `{enhtml $user_input}` | HTML 实体编码输出，防止 XSS |

编译为：`<?php echo ($this->enhtml($user_input));?>`

使用 `ENT_QUOTES | ENT_SUBSTITUTE` 标志，单引号、双引号和无效编码均被正确转义。

### 5.12 换行符

| 语法 | 说明 |
|------|------|
| `{LF}` | 输出 `&#10;`（HTML 属性中可用的换行实体） |

### 5.13 注释标注

以下标注在编译时自动移除，不会出现在输出中：

| 语法 | 示例 | 说明 |
|------|------|------|
| `// TODO: 描述` | `// TODO: 需要优化此处` | 待办标注 |
| `// BUG: 描述` | `// BUG: 空值导致崩溃` | 缺陷标注 |
| `// info: 描述` | `// info: 此处逻辑说明` | 信息标注 |
| `# TODO: 描述` | `# TODO: 重构` | 同上，# 风格 |
| `# BUG: 描述` | `# BUG: 修复前` | 同上 |
| `# info: 描述` | `# info: 参考` | 同上 |

---

## 六、API 方法参考

### 6.1 当前版本（v8.5.0）

#### `__construct(bool $update = false, bool $debug = false)`

构造函数，初始化引擎。

```php
$Qstyle = new Qstyle(true, true);
```

#### `conf(string $key, mixed $value): bool`

设置配置项。`$key` 为 `Qstyle::CNF_*` 常量。

```php
$Qstyle->conf(Qstyle::CNF_TPLDIR, './Data/default');
$Qstyle->conf(Qstyle::CNF_AUTODIR, ['./testing', './assets']);
```

#### `assign(string|array $key, mixed $value = null): bool`

向模板分配变量。

```php
$Qstyle->assign('title', 'Hello');              // 单个
$Qstyle->assign(['title' => 'Hello', 'x' => 1]); // 批量
```

#### `load(string $tpl, string $fkey = ''): string`

加载并渲染模板。支持多种调用方式：

```php
$Qstyle->load('phpnew');                        // 普通模板名
$Qstyle->load('__phpnew');                      // 自动搜索模板
$Qstyle->load('./block/demo');                  // 相对子目录
$Qstyle->load('<span>{$var}</span>');           // 字符串模板
$Qstyle->load('phpnew', 'blockname');           // 仅渲染指定 block
$Qstyle->load('phpnew', '#mytable');            // 仅渲染 #id 元素
$Qstyle->load('phpnew', '.classname');          // 仅渲染 .class 元素
```

#### `print(string $tpl, string $fkey = ''): string`

打印编译后的 PHP 源码（高亮显示），用于调试。

```php
$Qstyle->print('phpnew');
```

也可通过 URL 参数 `?print=1` 触发（需开启调试模式）。

#### `replace(array $key, string $idx = '<>'): void`

设置模板内容替换规则。

```php
// '<' = 解析前替换, '>' = 解析后替换, '<>' = 解析前替换
$Qstyle->replace(['行业' => '+行业'], '<');
```

#### `setlang(array|string $key, mixed $val = null): string`

设置语言包。

```php
$Qstyle->setlang('msg', '欢迎');
$Qstyle->setlang(['msg' => '欢迎', 'bye' => '再见']);
```

#### `clear(): void`

清除所有编译缓存文件。

```php
$Qstyle->clear();
```

#### `enhtml(string $html): string`（protected）

HTML 实体编码，用于 XSS 防护。

### 6.2 兼容版本（v8.1.0）额外方法

| 方法 | 说明 |
|------|------|
| `display($name, $returnpath)` | 主渲染方法（等价于 v8.5.0 的 `load`） |
| `load(...)` | `display` 的别名 |
| `assign($key, $val)` | 赋值变量 |
| `set_templates_path($path)` | 添加模板目录 |
| `set_cache_path($dir)` | 设置缓存目录 |
| `set_templates_suffix($tpl, $cache)` | 设置模板/缓存后缀 |
| `set_templates_auto($bool)` | 自动更新开关 |
| `set_templates_type($mode)` | 变量模式（All/ASSIGN） |
| `set_templates_oncenew($bool)` | 强制当次更新 |
| `set_templates_space($bool)` | 清除无意义空白 |
| `set_templates_isdebug($bool)` | 调试模式 |
| `set_templates_ankey($key)` | 安全码 |
| `set_templates_replace($arr)` | 全局替换 |
| `set_auto_path($path)` | 自动搜索目录 |
| `set_static_assign($key, $val)` | CSS/JS 静态变量 |
| `set_language($key, $val)` | 语言包 |
| `cache_dele($path)` | 清除缓存 |

---

## 七、高级功能

### 7.1 多模板目录

支持多个模板目录，**后设置的目录优先搜索**（倒序优先）：

```php
$Qstyle->conf(Qstyle::CNF_TPLDIR, './Data/default');
$Qstyle->conf(Qstyle::CNF_TPLDIR, './Data/override');   // 优先搜索此目录
```

当多个目录存在同名模板时，优先使用后设置的目录中的版本。建议不超过 3 个目录，避免路径混乱。

### 7.2 子目录模板

以模板目录为基点，使用 `./` 前缀加载子目录中的模板：

```php
$Qstyle->load('./block/demo');     // 加载 Data/default/block/demo.html
$Qstyle->load('./music/index');    // 加载 Data/default/music/index.html
```

### 7.3 自动搜索模板

使用 `__` 前缀在所有模板目录中自动搜索：

```php
$Qstyle->load('__phpnew');         // 自动搜索 phpnew.html
```

### 7.4 字符串模板

直接将字符串作为模板解析：

```php
$Qstyle->load('<span>{$a}</span><span>{$b}</span>');
```

适合 AJAX 返回简单 HTML 片段，避免为小块内容创建模板文件。

### 7.5 Block + AJAX 局部渲染

**模板中定义 block：**

```html
{block ajax_info}
    <div id="info">{$dateinfo}</div>
{/block}

{block minfo}
    <p>普通内容</p>
{/block}
```

**PHP 端根据请求类型渲染：**

```php
if (isset($_GET['ajax'])) {
    $Qstyle->load('block/demo', 'ajax_info');   // 仅渲染 ajax_info 块
    exit();
}

$Qstyle->load('block/demo');                    // 渲染完整模板
```

### 7.6 CSS/HTML 选择器提取

使用 DOMDocument + XPath 精准提取 HTML 元素：

```php
$Qstyle->load('template', '#mytable');      // 提取 id="mytable" 的元素
$Qstyle->load('template', '.content');      // 提取 class="content" 的首个元素
```

> 适合 AJAX 需要返回特定 HTML 块的场景，无需额外创建模板文件。

### 7.7 CSS/JS 模板化

CSS/JS 文件中可以使用模板语法：

**CSS 文件（in.css）：**

```css
body {
    background: url({__bg.jpg});
    color: {$color};
}
```

**PHP 端：**

```php
$color = 'red';
$Qstyle->load('cssjs');
```

> 解析后的静态文件被写入缓存目录，保持原后缀。CSS/JS 中仅支持变量和常量解析，不支持完整模板语法（因为不经过 PHP 执行）。

### 7.8 全局替换

在模板解析前/后进行字符串替换：

```php
// 解析前替换（影响模板源码）
$Qstyle->replace(['行业' => '+行业'], '<');

// 解析后替换（影响编译结果）
$Qstyle->replace(['old_text' => 'new_text'], '>');
```

### 7.9 语言包与国际化

```php
// 设置语言包
$Qstyle->setlang('welcome', '欢迎访问');
$Qstyle->setlang(['greeting' => ['hello' => '你好', 'bye' => '再见']]);

// 模板中使用
// {lang welcome}           → 欢迎访问
// {lang greeting/hello}    → 你好
```

### 7.10 调试模式

开启调试后，页面底部自动输出调试信息面板：

```php
$Qstyle = new Qstyle(true, true);   // 第二个参数开启调试
```

调试信息包含：
- 所有配置项值
- 模板/缓存文件路径
- 变量列表
- 文件读写记录
- PHP 错误信息

也可通过 URL 参数 `?print=1` 查看编译后的 PHP 源码高亮。

---

## 八、安全机制

### 8.1 模板安全

| 策略 | 说明 |
|------|------|
| PHP 标签禁止 | 模板中 `<?` 和 `?>` 被转义为 `&lt;?` 和 `?&gt;`，防止注入 |
| 安全表达式校验 | `{$...}` 仅允许读取路径（变量、属性、数组下标），禁止函数调用和赋值 |
| 缓存文件保护 | 编译缓存头部加入 `if(is_object($this) === false){exit('Hacking!');}` |
| 文件锁 | 读写操作使用 `flock(LOCK_EX/LOCK_SH | LOCK_NB)` 防止并发冲突 |

### 8.2 XSS 防护

使用 `{enhtml $var}` 输出用户输入，自动进行 HTML 实体编码：

```html
<p>用户名：{enhtml $username}</p>
<textarea>{enhtml $content}</textarea>
```

### 8.3 缓存文件命名

缓存文件名基于 SHA256(文件内容SHA256 + 主机密钥) 生成，不可预测：

```
phpnew_a1b2c3d4e5f6...64chars.php
```

---

## 九、缓存机制

### 9.1 工作流程

```
模板请求 → 检查缓存文件是否存在
  ├── 存在 + 未修改 → 直接 include 缓存（最快）
  ├── 存在 + 已修改 → 重新编译 → 写入缓存 → include
  └── 不存在 → 编译 → 写入缓存 → include
```

### 9.2 缓存目录

- 默认使用系统临时目录（`sys_get_temp_dir()`）
- 可通过 `CNF_CACHEDIR` 自定义
- 目录必须存在且可写，否则引擎报错退出

### 9.3 强制更新

```php
// 每次请求都重编译（开发环境推荐）
$Qstyle->conf(Qstyle::CNF_UPDATE, true);
```

### 9.4 清除缓存

```php
$Qstyle->clear();   // 删除缓存目录中所有 .php 文件
```

---

## 十、两版本对比

| 特性 | v8.5.0（当前） | v8.1.0（兼容） |
|------|----------------|----------------|
| PHP 版本要求 | **PHP 8.3+** | PHP 5.x+ |
| 严格类型 | `declare(strict_types=1)` | 无 |
| 类型系统 | 全部 typed properties | 无类型声明 |
| 配置方式 | `conf()` + 常量 | 公共属性 + setter 方法 |
| 模板安全 | 禁止 `<?` 标签 + 表达式校验 | Base64 编码保护 PHP 块 |
| XSS 防护 | `{enhtml}` 内置 | 无 |
| 选择器提取 | DOMDocument + XPath | 无 |
| 常量数组 | `{ABC[key][sub]}` | 无 |
| 文件锁 | `LOCK_EX/LOCK_SH` | `LOCK_EX/LOCK_SH` |
| 变量提取 | 首次 `load` 时提取全局变量 | `display` 时提取 |
| CSS/JS 解析 | 通过 `{__file.css}` 引入 | 独立 `__preg_source_parse` 管线 |
| 调试输出 | textarea 统一格式 | 逐行 div 格式 |
| 注释标注 | `// TODO:` `// BUG:` `# info:` | 同 |
| 缓存文件名 | SHA256(文件SHA256 + enkey) | MD5(文件 + ankey + host) |

---

## 十一、完整示例

### 示例 1：基础页面

```php
<?php
include('./include/Qstyle.class.php');

$Qstyle = new Qstyle(true, true);
$Qstyle->conf(Qstyle::CNF_TPLDIR, './Data/default');
$Qstyle->conf(Qstyle::CNF_AUTODIR, ['./testing']);

$title = 'Qstyle 演示';
$items = ['苹果', '香蕉', '橙子'];

$Qstyle->load('mypage');
```

**模板 mypage.html：**

```html
{loads header}

<h1>{$title}</h1>

<ul>
<!--{loop $items $item}-->
    <li>{$item}</li>
<!--{/loop}-->
</ul>

{load footer}
```

### 示例 2：AJAX 局部渲染

```php
<?php
include('./include/Qstyle.class.php');

$Qstyle = new Qstyle(true, false);
$Qstyle->conf(Qstyle::CNF_TPLDIR, './Data/default');

$dateinfo = date('Y-m-d H:i:s');

if (isset($_GET['ajax'])) {
    $Qstyle->load('demo', 'ajax_info');
    exit();
}

$Qstyle->load('demo');
```

**模板 demo.html：**

```html
<div class="container">
    <h2>演示页面</h2>
    <div id="info">{$dateinfo}</div>
    <button onclick="loadAjax()">刷新</button>
</div>

{block ajax_info}
    <div id="info">{$dateinfo}</div>
{/block}

<script>
function loadAjax() {
    fetch('?ajax=1').then(r => r.text()).then(t => {
        document.getElementById('info').outerHTML = t;
    });
}
</script>
```

### 示例 3：多语言 + 默认值

```php
<?php
include('./include/Qstyle.class.php');

$Qstyle = new Qstyle();
$Qstyle->conf(Qstyle::CNF_TPLDIR, './Data/default');

$Qstyle->setlang('welcome', '欢迎访问');
$Qstyle->setlang('goodbye', '再见');

$username = '';  // 未登录

$Qstyle->load('i18n');
```

**模板 i18n.html：**

```html
<p>{lang welcome}，{$username|'游客'}！</p>
<p>状态：{$is_login|'已登录'|'未登录'}</p>
```

### 示例 4：静态资源管理

```php
<?php
include('./include/Qstyle.class.php');

$Qstyle = new Qstyle(true, true);
$Qstyle->conf(Qstyle::CNF_TPLDIR, ['./Data/default']);
$Qstyle->conf(Qstyle::CNF_AUTODIR, ['./testing', './assets']);

$Qstyle->load('resource');
```

**模板 resource.html：**

```html
<!DOCTYPE html>
<html>
<head>
    <link rel="stylesheet" href="{__style.css}" />
    <script src="{__jquery.min.js}"></script>
</head>
<body>
    <img src="{__logo.jpg}" alt="Logo" />
    <img src="{#icon.jpg}" alt="Inline Icon" />
</body>
</html>
```

---

## 十二、性能说明

| 指标 | 说明 |
|------|------|
| **编译缓存** | 首次访问编译为 PHP 文件，后续直接 include，接近原生 PHP 速度 |
| **自动搜索** | 目录结构仅在首次使用时扫描并缓存到内存，不重复 I/O |
| **文件锁** | 非阻塞锁（LOCK_NB），并发写入不卡顿 |
| **变量提取** | 全局变量仅在首次 `load` 时提取一次（`EXTR_SKIP` 避免覆盖） |
| **静态引入** | `{loads}` 编译时展开，零运行时开销 |
| **安全校验** | 表达式正则校验在编译阶段完成，不影响缓存执行速度 |

---

## 十三、常见问题

### Q: 模板修改后页面没更新？
A: 确保 `CNF_UPDATE` 设为 `true`（开发环境），或手动调用 `$Qstyle->clear()` 清除缓存。

### Q: 提示"无法指定缓存目录"？
A: 缓存目录不存在或不可写。请通过 `CNF_CACHEDIR` 指定一个有写权限的目录。

### Q: 提示"务必指定模板目录"？
A: 未设置 `CNF_TPLDIR`。至少需要指定一个模板目录。

### Q: `{$var}` 不输出？
A: 检查变量是否已定义。Qstyle 默认提取全局变量，但如果使用 `assign` 模式则需手动赋值。v8.5.0 使用 `??` 空合并，未定义变量输出空字符串而非报错。

### Q: `{__file.jpg}` 找不到文件？
A: 确保已通过 `CNF_AUTODIR` 设置搜索目录，且文件确实存在于该目录或子目录中。

### Q: 如何在模板中写原生 PHP？
A: v8.5.0 禁止 `<?php ?>` 标签，请使用 `{eval echo ...;}` 语法。

### Q: 如何选择 v8.5.0 还是 v8.1.0？
A: PHP 8.3+ 环境务必使用 v8.5.0（`Qstyle.class.php`），更安全、更现代。仅在旧版 PHP 环境下使用 v8.1.0（`Qstyle.class.v5.php`）。

---

## 十四、更新日志

### v8.5.0 (2024-06-11)
- 全面适配 PHP 8.3+，启用 `declare(strict_types=1)`
- 所有属性改为 typed properties
- 配置体系重构为 `conf()` + 常量模式
- 新增常量数组语法 `{ABC[key][sub]}`
- 新增 `{enhtml}` XSS 防护语法
- 新增 DOMDocument + XPath 选择器提取（`#id` / `.class`）
- 模板安全升级：禁止 `<?` 标签、安全表达式校验
- 缓存文件名升级为 SHA256 算法
- 文件读写增加非阻塞锁

### v8.1.0
- Smarty 兼容模式
- 公共属性 + setter 方法配置
- Base64 编码保护 PHP 代码块
- CSS/JS 独立解析管线
- Block + AJAX 局部渲染
- 自动路径搜索
- 语言包支持
- TODO/BUG/INFO 注释标注

<?php declare(strict_types=1);
/**
 * Qtpl 模板引擎 仅支持 PHP 8.0 及以上版本。
 * @category: 分享工作室;
 * @var; Qstyle.class.php;
 * @version: Qstyle 8.5.0 2024-06-11;
 */

class QStyle{
	
    protected string $Cnf_suffix = '.html';
	protected string $Cnf_cacheDir = '';
	protected array  $Cnf_tplDir = [];
    protected array  $Cnf_autoDir = [];

	protected array  $lang  = [];
	protected array  $var_assign = [];
	protected array  $html_replace = [];
	protected int    $rwcount = 0;

    private  array		$rwtplfile = [];
	private  array  	$debugs = [];
	private  array		$find_Dirs = [];
	private  bool		$Cnf_update = false;
	private  bool		$Cnf_debug = false;
	private  string		$print_html = '';
	private  string     $Cnf_enkey = '';

	const CNF_VERSION  = '8.5.0';
	const CNF_TPLDIR   = 'Cnf_tplDir';
	const CNF_SUFFIX   = 'Cnf_suffix';
	const CNF_UPDATE   = 'Cnf_update';
	const CNF_CACHEDIR = 'Cnf_cacheDir';
	const CNF_AUTODIR  = 'Cnf_autoDir';
	const CNF_DEBUG    = 'Cnf_debug';
	const CNF_ENKEY    = 'Cnf_enkey';
	/**
	 * 构造函数，初始化模板引擎。
	 *
	 * 设置模板目录、缓存目录，并生成基于主机名和文件内容的加密密钥，
	 * 同时规范化模板文件后缀（确保以点号开头，去除多余的分隔符）。
	 *
	 * @param array $args 可选的配置项数组，支持通过常量名或属性名设置配置项，如 [QStyle::CNF_UPDATE => true] 或 ['Cnf_update' => true]。
	 */
	public function __construct(array $args = []){
		foreach($args as $k => $v){
			$this->conf($k, $v);
		}
        $this->Cnf_suffix  = '.' . ltrim($this->Cnf_suffix,'.\//');
		$this->debug('construct', ($_SERVER['HTTP_HOST'] ?? ''));
	}

	/**
	 * 清理编译缓存文件。
	 */
	public function clear(){
		if($this->Cnf_cacheDir === ''){
			return;
		}

		$files = glob($this->Cnf_cacheDir . '/*');
		foreach ($files as $file) {
			if (strlen($file) >= 64 && str_ends_with($file, '.php')) {
				@unlink($file);
			}
		}
	}

	private function debug(string $key, mixed $val): array{
		$this->debugs[$key] = $val;
		return $this->debugs;
	}

	/**
	 * 设置或合并配置项。
	 *
	 * 通过配置常量名或属性名动态设置对象属性。当传入值为数组且目标属性也是数组时，
	 * 执行合并操作（新值优先）；否则直接赋值。配置优先级：常量 > 传入值 > 默认值。
	 *
	 * @param  string $key   配置键名（支持 CNf_* 常量或属性名）
	 * @param  mixed  $value 配置值，数组会与已有数组合并
	 * @return bool   设置成功返回 true，键名无效返回 false
	 */
	public function conf(string $key, mixed $value): bool{
		// 从常量中处理配置项，优先级：常量 > 传入值 > 默认值。
		$resolvedKey = defined($key) ? (string) constant($key) : $key;
		if($resolvedKey === '' || property_exists($this, $resolvedKey) === false){
			return false;
		}

		if(is_array($this->$resolvedKey ?? null)){
			if(!is_array($value))
				$value = [$value];
			$value = array_merge(array_reverse($value), $this->$resolvedKey);
		}elseif(is_bool($this->$resolvedKey ?? null)){
			$value = (bool) $value;
		}elseif(is_int($this->$resolvedKey ?? null)){
			$value = (int) $value;
		}else{
			if(is_array($value)){
				$value = ($value ? $value[array_key_first($value)] : '');
			}
		}
		
		$this->$resolvedKey = $value;
		return true;
	}

	/**
	 * 向模板分配变量。
	 *
	 * 将键值对注册到模板变量池中，供模板渲染时使用。
	 * 支持两种调用方式：
	 *   - 数组方式：assign(['title' => 'Hello', 'name' => 'World'])
	 *   - 键值方式：assign('title', 'Hello')
	 *
	 * @param  string|array $key   变量名或键值对数组
	 * @param  mixed        $value 当 $key 为字符串时的变量值
	 * @return bool   始终返回 true
	 */
	public function assign(string|array $key, mixed $value = null): bool{
		if(is_array($key)){
			foreach($key as $k => $v){
				$this->var_assign[$k] = $v;
			}
		}else{
			$this->var_assign[$key] = $value;
		}
		return true;
	}
	/**
	 * 设置模板内容替换规则。
	 * < 表示在模板解析前进行替换，> 表示在模板解析后进行替换。
	 * @param  array  $key 替换规则数组，键为待替换内容，值为替换后的内容
	 * @param  string $idx 替换时机标识，'<' 表示解析前替换，'>' 表示解析后替换，默认为 '<> 前面替换'
	 */
	public function replace(array $key, string $idx = '<>'): void{
		if(is_array($key)){
			foreach($key as $k => $v){
				if($idx === '<' || $idx === '<>'){
				   $this->html_replace['<'][$k] = $v;
				}else{
					$this->html_replace['>'][$k] = $v;
				}
			}
		}
	}

	/**
	 * 将字符串转换为 HTML 实体。
	 * 使用 ENT_QUOTES | ENT_SUBSTITUTE 标志确保单引号、双引号和无效编码都被正确转义，防止 XSS 攻击。
	 * @param  string $html 待转换的字符串
	 * @return string 转换后的字符串，HTML 特殊字符已被转义
	 */
	protected function enhtml(string $html): string{
		return str_replace(' ', '&nbsp;', htmlspecialchars($html, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
	}

	/**
	 * 将模板内容编译为 PHP 代码。
	 *
	 * 安全策略：模板中禁止任何 PHP 标签，统一转义为普通文本输出。
	 * @param  string $html 原始模板内容
	 * @return string 编译后的 PHP代码，包含安全头部
	 */
	protected function parse_tpl(string $html): string{
		$html = trim($html);
		if ($html === '')
			return '';

		$normalizeExpression = static function (string $expression): string {
			$expression = trim($expression);
			return preg_replace_callback(
				'/\[(\s*[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*\s*)\]/u',
				static function (array $matches): string {
					$key = trim($matches[1]);
					if(is_numeric($key) || $key[0] === '$'){
						return '[' . $key . ']';
					}
					return "['" . $key . "']";
				},
				$expression
			) ?? $expression;
		};

		$normalizeConstantArrayExpression = static function (string $expression): string {
			$expression = trim($expression);
			return preg_replace_callback(
				'/\[(\s*[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*\s*)\]/u',
				static function (array $matches): string {
					$key = trim($matches[1]);
					if(is_numeric($key) || $key[0] === '$'){
						return '[' . $key . ']';
					}
					if(preg_match('/^[A-Z_][A-Z0-9_]*$/', $key) === 1){
						return "[(defined('{$key}') ? constant('{$key}') : '{$key}')]";
					}
					return "['" . $key . "']";
				},
				$expression
			) ?? $expression;
		};

		$compileValue = static function (string $value) use ($normalizeExpression): string {
			$value = trim($value);
			if($value === ''){
				return "''";
			}

			if(defined($value)){
				return "constant('" . addslashes($value) . "')";
			}
			if($value[0] === '$'){
				return $normalizeExpression($value);
			}
			if(is_numeric($value)){
				return $value;
			}

			if(($value[0] === '\'' && str_ends_with($value, '\'')) || ($value[0] === '"' && str_ends_with($value, '"'))){
				return $value;
			}
			return var_export($value, true);
		};

		$compileEcho = static function (string $expression) use ($normalizeExpression, $normalizeConstantArrayExpression, $compileValue): string {
			$expression = trim($expression);
			$parts = array_map('trim', explode('|', $expression));
			$baseExpression = array_shift($parts) ?? '';
			if(preg_match('/^[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*(?:\[[^\]]+\])+$/u', $baseExpression) === 1 && $baseExpression[0] !== '$'){
				$baseExpression = $normalizeConstantArrayExpression($baseExpression);
			}else{
				$baseExpression = $normalizeExpression($baseExpression);
			}

			if(count($parts) >= 2){
				$trueValue = $compileValue($parts[0]);
				$falseValue = $compileValue($parts[1]);
				return "<?php echo ((({$baseExpression}??'' )? {$trueValue} : {$falseValue}));?>";
			}

			if(count($parts) === 1){
				$defaultValue = $compileValue($parts[0]);
				return "<?php echo ({$baseExpression} ?? {$defaultValue});?>";
			}

			if(preg_match('/^([A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*)(?:\[.*\])?$/u', $baseExpression, $matches) === 1 && str_contains($baseExpression, '->') === false){
				$constant = $matches[1];
				return "<?php echo (defined('{$constant}') ? ({$baseExpression} ?? '') : '');?>";
			}

			return "<?php echo ({$baseExpression} ?? '');?>";
		};

		$compileStaticAccess = static function (string $expression): string {
			$expression = trim($expression);
			if($expression === ''){
				return '';
			}

			// 支持 Class::CONST 与 Class::$prop 两类静态访问。
			if(str_contains($expression, '::$')){
				return "<?php echo ({$expression} ?? '');?>";
			}

			$constantExpr = addslashes($expression);
			return "<?php echo (defined('{$constantExpr}') ? constant('{$constantExpr}') : '');?>";
		};

		$compileLoad = static function (string $expression) use ($normalizeExpression): string {
			$expression = trim($expression);
			if($expression === ''){
				return '';
			}
			if(preg_match('/^(__[A-Za-z0-9_\-\/]+)$/u', $expression, $matches) === 1){
				return "<?php \$this->load('{$matches[1]}');?>";
			}
			$loadTarget = $expression[0] === '$' ? ($normalizeExpression($expression) . " ?? ''") : var_export($expression, true);
			$label = addslashes($expression);
			return "<?php \$this->load({$loadTarget});?>";
		};

		$compileLang = static function (string $expression) use ($compileValue): string {
			$expression = trim($expression);
			if($expression === ''){
				return '';
			}

			$parts = preg_split('/\s*\/\s*/u', $expression) ?: [];
			$compiledParts = [];
			foreach($parts as $part){
				$part = trim($part);
				if($part === ''){
					continue;
				}
				$compiledParts[] = $compileValue($part);
			}

			if($compiledParts === []){
				$compiledParts[] = $compileValue($expression);
			}

			return "<?php echo (\$this->getlang([" . implode(', ', $compiledParts) . "]));?>";
		};

		$compileLoopReceiver = static function (string $receiver) use ($normalizeExpression): string {
			$receiver = trim($receiver);
			if($receiver === '' || $receiver[0] !== '$'){
				return '$__invalidLoopVar';
			}
			return $normalizeExpression($receiver);
		};

		$resolveLoadsTarget = function (string $expression): string {
			$expression = trim($expression);
			if($expression === ''){
				return '';
			}

			if(($expression[0] === '\'' && str_ends_with($expression, '\'')) || ($expression[0] === '"' && str_ends_with($expression, '"'))){
				return stripcslashes(substr($expression, 1, -1));
			}

			if($expression[0] === '$' && preg_match('/^\$+([A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*)$/u', $expression, $matches) === 1){
				$key = $matches[1]??'';
				$value = ($this->var_assign[$key] ?? ($GLOBALS[$key] ?? null));
				if(is_string($value) || is_numeric($value)){
					return (string) $value;
				}

				// 未显式 assign 时，回退为变量名本身，支持 {block $block1} -> block1。
				return $key;
			}

			return $expression;
		};

		$resolveBlockName = function (string $expression): string {
			$expression = trim($expression);
			if($expression === ''){
				return '';
			}

			if($expression[0] === '$' && preg_match('/^\$+([A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*)$/u', $expression, $matches) === 1){
				$key = $matches[1]??'';
				$value = ($this->var_assign[$key] ?? ($GLOBALS[$key] ?? null));
				if(is_string($value) || is_numeric($value)){
					return (string) $value;
				}

				// 未显式 assign 时，回退变量名本身，支持 {block $block1} 调用 block1。
				return $key;
			}

			return $expression;
		};

		$expandLoads = function (string $source, int $depth = 0) use (&$expandLoads, $resolveLoadsTarget): string {
			$source = strtr($source, ['<!--{' => '{', '}-->' => '}']);
			if($depth >= 30 || preg_match('/\{loads\s+[^{}]+\}/iu', $source) !== 1){
				return $source;
			}

			return preg_replace_callback(
				'/\{loads\s+([^{}]+)\}/iu',
				function (array $matches) use (&$expandLoads, $resolveLoadsTarget, $depth): string {
					$target = $resolveLoadsTarget($matches[1]);
					if($target === ''){
						return '';
					}

					$source = $this->loads($target);
					if($source === ''){
						return '';
					}

					return $expandLoads($source, $depth + 1);
				},
				$source
			) ?? $source;
		};

		$normalizeVariableVariableSyntax = static function (string $expression): string {
			// 兼容 ${$var} 形式，归一化为 $$var，便于后续统一校验与编译。
			return preg_replace_callback(
				'/\$\{\s*(\$+[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*)\s*\}/u',
				static fn(array $matches): string => '$' . $matches[1],
				$expression
			) ?? $expression;
		};

		$isSafeReadExpression = static function (string $expression): bool {
			$expression = trim($expression);
			if($expression === '' || $expression[0] !== '$'){
				return false;
			}

			// 严格模式下只允许读取路径：变量、对象属性、静态成员、数组下标。
			$pattern = '/^\$+[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*(?:\s*(?:->\s*[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*|::\s*[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*|\[\s*(?:-?\d+|\$+[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*|[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*|\'[^\']*\'|"[^"]*")\s*\]))*$/u';
			if(preg_match($pattern, $expression) !== 1){
				return false;
			}

			// 双保险：禁止调用、赋值与语句拼接。
			if(strpbrk($expression, '();`') !== false){
				return false;
			}
			if(preg_match('/(^|[^!<>=])=(?!=)/', $expression) === 1){
				return false;
			}

			return true;
		};

		// 从这开始.
		$php = $html;

		$blockTemplates = [];

		// 将 {loads ...} 先展开为模板源码，并递归展开其中的 {loads ...}。
		$php = $expandLoads($php);

		if($this->html_replace['<'] ?? null){
			$php = strtr($php, $this->html_replace['<']);
		}

		// 让<!-- --> 注释里的标签不被解析, 先转成[base64]编码, 等编译结束再转回来.
		$php = preg_replace_callback(
			'/<!--(.*?)-->/isu',
			static function (array $matches): string {
				$content = $matches[1] ?? '';
				return '[base64]' . base64_encode($content) . '[base64]';
			},
			$php
		) ?? $php;
		
		$php = str_replace(["\r\n", "\r"], "\n", $php);
		
		// 安全策略：模板内容里禁止任何 PHP 标签，统一转义成普通文本输出。
		$php = str_replace(['<?', '?>'], ['&lt;?', '?&gt;'], $php);

		// 兼容{enhtml reg}写法 reg为字符,变量,常量之类，转成echo $this->enhtml($var)，功能显示实体
		preg_match_all('/\{enhtml\s+([^{}]+?)\s*\}/iu', $php, $matches);
		foreach($matches[1] ?? [] as $index => $expression){
			$expression = $compileValue($expression);
			$compiled = "<?php echo (\$this->enhtml({$expression}));?>";
			$php = str_replace($matches[0][$index], $compiled, $php);
		}

		// 只解开包在 HTML 注释中的流程控制标签，避免把说明性文本误判为模板语法。
		$php = preg_replace(
			'/<!--\{\s*((?:if|elseif|else\s+if|else|\/if|loop|\/loop)\b.*?)\s*\}-->/isu',
			'{$1}',
			$php
		) ?? $php;

		// 删除模板里的 #info / #todo / #bug 私有注释行，并连同行尾换行一起移除，避免留下空白占位行。
		$php = preg_replace('/^[\t ]*\/\/(?:info|todo|bug)\s*:(.*)(?:\n|$)/imu', '', $php) ?? $php;
		$php = preg_replace('/^[\t ]*#(?:info|todo|bug)\s*:.*(?:\n|$)/imu', '', $php) ?? $php;

		// 将 {load ...} 编译为模板加载代码，支持字面量模板名和变量模板名。
		$php = preg_replace_callback(
			'/\{load\s+([^{}]+)\}/iu',
			static fn(array $matches): string => $compileLoad($matches[1]),
			$php
		) ?? $php;

		// 将 {eval ...} 直接编译成 PHP 代码块；模板里已带分号时不重复补分号。
		$php = preg_replace_callback(
			'/\{eval\s+(.+?)\s*\}/isu',
			static function (array $matches): string {
				$statement = rtrim(trim($matches[1]));
				if($statement !== '' && str_ends_with($statement, ';') === false){
					$statement .= ';';
				}
				return "<?php {$statement}?>";
			},
			$php
		) ?? $php;

		// 将 {lang ...} 编译成 lang 调用，支持常量key/变量/数组下标/对象属性等表达式。
		$php = preg_replace_callback(
			'/\{lang\s+(.+?)\s*\}/isu',
			static fn(array $matches): string => $compileLang($matches[1]),
			$php
		) ?? $php;

		// 将 {LF} 换成 HTML 属性里可用的换行实体。
		$php = preg_replace('/\{LF\}/iu', '&#10;', $php) ?? $php;

		// 将 {__file.ext} 自动定位语法编译成 autofile 调用，并保留模板里传入的后缀。
		$php = preg_replace_callback(
			'/\{\s*(__[A-Za-z0-9_\.\-\/]+)\s*\}/u',
			static function (array $matches): string {
				$autoName = $matches[1];
				return "<?php echo (\$this->autofile('{$autoName}'));?>";
			},
			$php
		) ?? $php;

		// 将独占一行的 {block blockName}...{/block} 收集为原始模板片段，并连同标签所在空白行一起移除。
		// 为避免误吞调用标签，区块体中若再次出现 {block ...} 行，则该起始标签不视为定义。
		$php = preg_replace_callback(
			'/^[\t ]*\{block\s+([A-Za-z_][A-Za-z0-9_]*)\}[\t ]*\n(?![\t ]*\{block\b)(.*?)^[\t ]*\{\/block\}[\t ]*(?:\n|$)/imsu',
			static function (array $matches) use (&$blockTemplates): string {
				$name = $matches[1] ?? '';
				if($name !== ''){
					$blockTemplates[$name] = trim($matches[2]);
				}
				return '';
			},
			$php
		) ?? $php;

		// 调用仅支持变量模式：{block $xxx}。
		if($blockTemplates !== []){
			$php = preg_replace_callback(
				'/\{block\s+(\$+[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*)\}/iu',
				function (array $matches) use ($blockTemplates, $resolveBlockName): string {
					$name = $resolveBlockName($matches[1] ?? '');
					return $name !== '' ? ($blockTemplates[$name] ?? '') : '';
				},
				$php
			) ?? $php;
		}

		// 支持 {Class::CONST} / {Class::$prop} 静态访问写法。
		$php = preg_replace_callback(
			'/\{\s*([A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*(?:\\\\[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*)*::(?:\$+[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*|[A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*))\s*\}/u',
			static fn(array $matches): string => $compileStaticAccess($matches[1]),
			$php
		) ?? $php;

		$php = preg_replace_callback(
			'/\{\s*([A-Za-z_][A-Za-z0-9_]*)\s*\}/u',
			static function (array $matches) use ($compileEcho): string {
				$name = $matches[1];
				// 保留流程关键字，不在这里转成常量输出。
				if(in_array(strtolower($name), ['else'], true)){
					return $matches[0];
				}

				// 常量输出编译（block 调用已由 {block ...} 专用语法处理）。
				return $compileEcho($name);
			},
			$php
		) ?? $php;

		// 将 {if ...} / {elseif ...} / {else} / {/if} 编译成 PHP 条件语句，并统一裸数组下标写法。
		$php = preg_replace_callback(
			'/\{if\s+(.+?)\}/isu',
			static fn(array $matches): string => '<?php if(' . $normalizeExpression($matches[1]) . '){ ?>',
			$php
		) ?? $php;
		$php = preg_replace_callback(
			'/\{(?:elseif|else\s+if)\s+(.+?)\}/isu',
			static fn(array $matches): string => '<?php }elseif(' . $normalizeExpression($matches[1]) . '){ ?>',
			$php
		) ?? $php;
		$php = preg_replace('/\{else\}/iu', '<?php }else{ ?>', $php) ?? $php;
		$php = preg_replace('/\{\/if\}/iu', '<?php } ?>', $php) ?? $php;

		$php = preg_replace_callback(
			'/\{loop\s+([^\{\}\n]+?)\s+(\$[^\s\}]+)\s+(\$[^\s\}]+)\}/iu',
			static function (array $matches) use ($normalizeExpression, $compileLoopReceiver): string {
				$keyVar = $compileLoopReceiver($matches[2]);
				$valVar = $compileLoopReceiver($matches[3]);
				return '<?php foreach($this->_A(' . $normalizeExpression($matches[1]) . ' ?? []) as ' . $keyVar . ' => ' . $valVar . '){ ?>';
			},
			$php
		) ?? $php;
		$php = preg_replace_callback(
			'/\{loop\s+([^\{\}\n]+?)\s+(\$[^\s\}]+)\}/iu',
			static function (array $matches) use ($normalizeExpression, $compileLoopReceiver): string {
				$valVar = $compileLoopReceiver($matches[2]);
				return '<?php foreach($this->_A(' . $normalizeExpression($matches[1]) . ' ?? []) as ' . $valVar . '){ ?>';
			},
			$php
		) ?? $php;
		$php = preg_replace('/\{\/loop\}/iu', '<?php } ?>', $php) ?? $php;

		// 将 {$...} 输出语法编译成 echo，但只接受读取类表达式，不吞掉赋值或语句块。
		$php = preg_replace_callback(
			'/\{\s*(\$(?:(?:\{[^{}]+\})|[^{}])+?)\s*\}/isu',
			function (array $matches) use ($compileEcho, $isSafeReadExpression, $normalizeVariableVariableSyntax): string {
				$expression = $normalizeVariableVariableSyntax(trim($matches[1]));
				if(str_contains($expression, ';') || preg_match('/(^|[^!<>=])=(?!=)/', $expression) === 1){
					return $matches[0];
				}

				if(true){
					$parts = array_map('trim', explode('|', $expression));
					$baseExpression = $parts[0] ?? '';
					if($isSafeReadExpression($baseExpression) === false){
						return $matches[0];
					}
				}

				return $compileEcho($expression);
			},
			$php
		) ?? $php;

		// 将 {ABC[var][$sex]} 这类常量数组语法编译成带 defined 判断的安全输出。
		$php = preg_replace_callback(
			'/\{\s*([A-Za-z_\x80-\xff][A-Za-z0-9_\x80-\xff]*(?:\[[^{}]+\])+?)\s*\}/isu',
			static fn(array $matches): string => $compileEcho($matches[1]),
			$php
		) ?? $php;

		$php = preg_replace_callback(
			'/\[base64\](.*?)\[base64\]/isu',
			static function (array $matches): string {
				$content = $matches[1] ?? '';
				return '<!--' . base64_decode($content) . '-->';	
			},
			$php
		) ?? $php;

		if($this->rwtplfile['cache']){
			$phphtml = "<?php /* {$this->rwtplfile['tpl']} */ if(is_object(\$this) === false){exit('Hacking!');}?>" . $php;
		}else{
			$phphtml = $php;
		}
		
		if($this->html_replace['>'] ?? null){
			$phphtml = strtr($phphtml, $this->html_replace['>']);
		}

		return $phphtml;
	}

	/**
	 * 加载并输出模板内容。
	 * 
	 * 根据模板标识定位模板文件，若编译缓存不存在或已过期则重新编译，
	 * 然后包含执行编译后的缓存文件，并将原始模板内容以高亮字符串形式输出，便于调试和查看模板源码。
	 */
	public function print(string $tpl, string $fkey = ''): string{
		$this->print_html = '1';
		$filecode = $this->load($tpl, $fkey);
		// 判断.php结尾
		if(str_ends_with($filecode, '.php')){
			$filecode = $this->preg__file($filecode);
		}

		// php代码高亮显示样式
		$filecode = highlight_string($filecode, true);

		print $filecode;

		$this->print_html = '';
		return $filecode;
	}

	/**
	 * 加载并渲染模板文件。
	 *
	 * 根据模板标识定位模板文件，若编译缓存不存在或已过期则重新编译，
	 * 然后包含执行编译后的缓存文件。支持以下模板标识格式：
	 *   - 普通模板名：'header' → 在模板目录中搜索
	 *   - 自动定位：'__filename' → 在自动搜索目录中查找文件
	 *   - 相对路径：'./block/demo' → 基于当前模板目录的相对路径
	 *   - 变量模板名：$dynamicName → 运行时确定模板
	 *
	 * 当指定 $blockName 时，仅提取并渲染模板中对应 block 块的内容。
	 * 首次调用时会将全局变量提取到模板作用域中（仅执行一次）。
	 *
	 * @param  string $tpl       模板标识、文件路径或内联模板内容
	 * @param  string $fkey      可选，指定只渲染模板中某个 block 块、#id 或 .class
	 * @return string 编译后的模板内容，或错误提示信息
	 */
	public function load(string $tpl, string $fkey = ''): string{
		if($this->Cnf_debug && ($_GET['print'] ??'')){
			$_GET['print'] = '';
			return $this->print($tpl, $fkey);
		}

		if(!($this->debugs['Qextract'] ?? '')){
            foreach($GLOBALS as $key => $value){
                // 将用户定义的变量释放
                if(!in_array($key, ['GLOBALS','_SERVER','_GET','_POST','_FILES','_COOKIE','_SESSION','_REQUEST','_ENV','argv','argc'], true)){
                    if(!isset($this->var_assign[$key])){
                        $this->var_assign[$key] = $value;
                    }
                }
            }

            extract($this->var_assign, EXTR_SKIP);
            $this->debugs['Qextract'] = '1 (variables extracted)';

			// 修正tpldir, autodir, cacheDir路径末尾斜杠，确保统一格式，便于后续处理。
			$this->Cnf_tplDir = array_map(function($dir){
				return str_replace('\\', '/', rtrim($dir, '/\\'));
			}, $this->Cnf_tplDir);
			$this->Cnf_autoDir = array_map(function($dir){
				return str_replace('\\', '/', rtrim($dir, '/\\'));
			}, $this->Cnf_autoDir);

			$this->Cnf_cacheDir = str_replace('\\', '/', rtrim($this->Cnf_cacheDir, '/\\'));
			if(!$this->Cnf_cacheDir){
				$basedir = ini_get('open_basedir');
				$temdir = sys_get_temp_dir();
				if($basedir !== '' && str_contains($basedir, $temdir)){
					$temdir = '';
				}
				$this->Cnf_cacheDir = str_replace('\\', '/', rtrim($temdir, '/\\'));
			}

			if(!$this->Cnf_cacheDir || is_dir($this->Cnf_cacheDir) === false || is_writable($this->Cnf_cacheDir) === false){
				$this->conf( self::CNF_DEBUG,false);
				exit('无法指定缓存目录'.$temdir);
			}

			if(!$this->Cnf_tplDir){
				$this->conf(self::CNF_DEBUG,false);
				exit('务必指定模板目录'.var_export($this->Cnf_tplDir, true));
			}
			$this->Cnf_enkey = hash('sha256',($this->Cnf_enkey??($_SERVER['HTTP_HOST'] ?? '')).md5_file(__FILE__));
        }

        $this->rwtplfile = $this->autofile($tpl,'tpl');
		$evalcode = '';
		if($fkey){
			// 当$fkey有值时, 可取三类字符[block,#id,.class].
			if($this->rwtplfile['tpl']){
				$tplContent = $this->preg__file($this->rwtplfile['tpl']);
			}else{
				$tplContent = $tpl;
				// 如果不是文件, 就是字符串本身.
			}

			$tplcode = '';
			if($tplContent){
				if( str_starts_with($fkey, '#') || str_starts_with($fkey, '.') ){
					$tplcode = $this->extractHtmlContentBySelector($tplContent, $fkey);
				}else{
					$blockPattern = '/^[\t ]*\{block\s+' . preg_quote($fkey, '/') . '\}[\t ]*\n?(.*?)^[\t ]*\{\/block\}[\t ]*(?:\n|$)/imsu';
					if(preg_match($blockPattern, $tplContent, $matches) === 1){
						$tplcode = trim($matches[1]);
					}
				}
			}
			
			$this->debug('load3key_'.$fkey, strlen($tplcode).' > 0 ok');
			$evalcode = $this->parse_tpl($tplcode);
		}

		// 把它理解成解析html字符.
		if(!$evalcode)
		if($tpl && !$this->rwtplfile['tpl']){
			$this->debug('parse_string', strlen($tpl));
			$evalcode = $this->parse_tpl($tpl);
		}

		$errint =0;
		if($evalcode){
			if($this->print_html){
				return $evalcode;
			}

			$prevErrorReporting = error_reporting($errint);
			eval('?>'.$evalcode);
			error_reporting($prevErrorReporting);
			return $tpl;
		}

        // cache 一般都有, isupdate返回true时, 一定更新.
        $compiled = $tpl;
        if($this->rwtplfile['isupdate'] && $this->rwtplfile['tpl']){
            $compiled = $this->parse_tpl($this->preg__file($this->rwtplfile['tpl']));
            if($compiled){
                $this->preg__file($this->rwtplfile['cache'], $compiled);
            }
        }

		if($this->print_html){
			return $this->rwtplfile['cache'];
		}

        if($this->rwtplfile['cache'] && $this->rwtplfile['tpl']){
			$this->debug('load_'.$tpl, $this->rwtplfile['cache'].'('. var_export($this->rwtplfile['isupdate'], true) .')');

			$prevErrorReporting = error_reporting($errint);
            include $this->rwtplfile['cache'];
			error_reporting($prevErrorReporting);
		}else{
			$this->debug('load_failed_tpl', $tpl);
		}

		return $compiled;
	}

	public function __destruct(){
		if(!$this->debugs || !$this->Cnf_debug){
			return;
		}

		// 判断非ajax请求
		if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest'){
			return;
		}

		// 补充debug输出
		echo "\n<textarea readonly style=\"width:70%;height:500px; color:slategray; background-color:#fff; padding:10px;\">";
		echo "Qstyle " . self::CNF_VERSION . " Debug Information:\n";
		$cntarr = (new ReflectionClass(QStyle::class))->getConstants();
		$contval = array();
		foreach($cntarr as $key => $value){
			$contval[$value] = $this->{$value} ?? $value;
		}
		$this->debugs = array_merge($this->debugs, $contval);
		$this->debugs['phperr'] = error_get_last();
		$this->debugs['jsonerr'] = json_last_error_msg();
		ksort($this->debugs);
		foreach($this->debugs as $key => $value){
			$value = is_scalar($value) ? (string) $value : json_encode($value, JSON_UNESCAPED_UNICODE);
			echo '  '. htmlspecialchars($key) . ": " . htmlspecialchars($value)."\n";
		}
		echo "</textarea>";
	}

	/**
	 * 使用 DOMDocument + XPath 提取首个 #id 或 .class 命中标签的 HTML。
	 *
	 * 注意：DOMDocument 会将模板语法中的 > 等字符转义为 HTML 实体（如 &gt;），
	 * 此方法会在输出后自动还原这些转义，确保模板语法原样保留。
	 *
	 * @param string $html     源 HTML 内容
	 * @param string $selector CSS 选择器，支持 #id 或 .class
	 */
	protected function extractHtmlContentBySelector(string $html, string $selector): string{
		$selector = trim($selector);
		if($html === '' || $selector === '' || class_exists('DOMDocument') === false){
			return '';
		}

		$prevUseErrors = libxml_use_internal_errors(true);
		$doc = new DOMDocument('1.0', 'UTF-8');
		$loaded = $doc->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
		libxml_clear_errors();
		libxml_use_internal_errors($prevUseErrors);

		if($loaded === false){
			return '';
		}

		$query = '';
		if(str_starts_with($selector, '#')){
			$id = substr($selector, 1);
			if($id === ''){
				return '';
			}
			$query = "//*[@id=" . $this->xpathLiteral($id) . "]";
		}else if(str_starts_with($selector, '.')){
			$className = substr($selector, 1);
			if($className === ''){
				return '';
			}
			$query = "//*[contains(concat(' ', normalize-space(@class), ' '), " . $this->xpathLiteral(' ' . $className . ' ') . ")]";
		}else{
			return '';
		}
      
		$xpath = new DOMXPath($doc);
		$nodes = $xpath->query($query);
		if($nodes === false || $nodes->length === 0){
			return '';
		}

		$node = $nodes->item(0);
		if($node === null){
			return '';
		}

		$result = trim(htmlspecialchars_decode($doc->saveHTML($node), ENT_QUOTES | ENT_HTML5));
		return $result;
	}

	/**
	 * 构造 XPath 字符串字面量，兼容单双引号。
	 */
	protected function xpathLiteral(string $value): string{
		if(str_contains($value, "'")){
			if(str_contains($value, '"')){
				$parts = explode("'", $value);
				$quotedParts = array_map(static fn(string $part): string => "'" . $part . "'", $parts);
				return 'concat(' . implode(', "\'", ', $quotedParts) . ')';
			}
			return '"' . $value . '"';
		}

		return "'" . $value . "'";
	}

	protected function loads(string $tpl):string{
		// {loads file} {loads $var} 直接将源码返回.
		if(str_starts_with($tpl, '__') && str_contains($tpl, "\n") === false){
			$tpl = trim($tpl, '_');
		}

		if(str_starts_with($tpl, './') && str_contains($tpl, "\n") === false){
			$tpl = trim($tpl, './');
		}

        $tem = $this->autofile($tpl,'tpl');
		$this->debug('loads_'.$tpl, $tem['tpl'] ?? '');
		if($tem['tpl']){
			return $this->preg__file($tem['tpl']);
		}else{
			return '';
		}	
	}

	/**
	 * 设置语言包条目。
	 * 支持单条设置（setlang('key', 'value')）和批量设置（setlang(['key1' => 'value1', 'key2' => 'value2']）两种用法。
	 * @param  array|string $key 键名或键值对数组
	 * @param  mixed $val      可选，单条设置时的值
	 */
	public function setlang(array|string $key, mixed $val = null): string{
		if(is_string($key)){
			$key = [$key => $val];
		}

		$this->lang = array_merge($key,$this->lang);
		return '';
    }

	/**
	 * 获取语言包条目。
	 * 支持单条获取（getlang('key')）和批量获取（getlang(['key1', 'key2']）两种用法。
	 * 获取时会根据传入的键名路径在语言包数组中查找对应的值，支持多层嵌套访问（如 getlang('greeting/hello') 对应 $lang['greeting']['hello']）。
	 * 若找到的值为字符串或数字，则直接返回其字符串形式；若为数组或其他类型，则返回其 JSON	编码字符串，确保在模板中输出时能正确显示复杂结构。
	 * @param  array|string $key 键名或键路径数组
	 * @return string           对应的语言包值或其 JSON 编码字符串
	 */
	protected function getlang(array|string $key): string{
		if(is_string($key))
			$key = [$key];

		$value = $this->lang;
		foreach($key as $k){
			if(is_array($value) && array_key_exists($k, $value)){
				$value = $value[$k];
			}
		}
		
		if(is_string($value) || is_numeric($value)){
			return (string) $value;
		}else{
			return json_encode($value, JSON_UNESCAPED_UNICODE);
		}
    }

    /**
     * 安全数组包装器。
     *
     * 确保传入的值在 foreach 中可安全迭代：
     * 若值为可迭代类型（数组或 Traversable）则原样返回，否则返回空数组。
     * 用于模板循环语句中防止对非迭代值执行 foreach 导致报错。
     *
     * @param  mixed $value 待检查的值
     * @return mixed 可迭代的值或空数组
     */
    protected function _A(mixed $value): mixed{
        return is_iterable($value) ? $value : [];
    }

    /**
     * 自动定位文件路径。
     *
     * 根据文件名在配置的搜索目录中查找文件，有两种工作模式：
     *   - 模板模式（$GetType != 'auto'）：在模板目录中搜索模板文件，
     *     同时生成对应的编译缓存路径，返回包含 tpl/cache/isupdate 信息的数组。
     *     支持带后缀和不带后缀的文件名（不带后缀时自动补上配置的模板后缀）。
     *   - 自动搜索模式（$GetType = 'auto'）：在自动搜索目录及其子目录中递归查找文件，
     *     找到后返回文件的完整路径字符串，未找到返回空字符串。
     *
     * @param  string $filename 文件名或模板标识
     * @param  string $GetType  搜索模式，'auto' 为纯文件搜索，其他值为模板搜索
     * @return array|string 模板模式返回 ['tpl'=>路径, 'cache'=>缓存路径, 'isupdate'=>是否需更新]；
     *                      自动搜索模式返回文件完整路径或空字符串
     */
    protected function autofile(string $filename, string $GetType ='auto'): array|string{
		// 凡是有空格,换行, 都不当成文件名, 直接返回原字符串. 只有纯文件名才进行搜索.
		if(preg_match('/\s/', $filename) === 1){
			$this->debug('autostr-'.microtime(true), strlen($filename));
			return $GetType === 'auto' ? '' : ['tpl' => '', 'cache' => '', 'isupdate' => false];
		}

		// $filename = __name 时,并且没有换行, 先进行autofile
		if(str_starts_with($filename, '__')){
			$filename = trim($filename, '_');
		}

		// 前后打掉所有./
		$filename = trim(str_replace('\\', '/', $filename), './');

		// 模板与文件是独立的搜索.
		if ($GetType !== 'auto') {
            $cachefile = '';
			$filepath = '';

			if(str_contains($filename, '/')){
				$filepath = $filename;
				if(!is_file($filepath)){
					$filenameHead = explode('.', ($filename), 2)[0] ?? ($filename);
					$filepath = './' . ltrim($filenameHead, './') . $this->Cnf_suffix;
				}
			}

			if(!is_file($filepath))
			foreach ($this->Cnf_tplDir as $dir) {
				$filepath = $dir. '/' .$filename;
				if(!is_file($filepath)){
					$filenameHead = explode('.', ($filename), 2)[0] ?? ($filename);
					$filepath = $dir. '/' . $filenameHead . $this->Cnf_suffix;
					if(is_file($filepath)){
						break;
					}else{
						$filepath = '';
					}
				}
			}

            $isupdate = false;
            if($filepath){
                $fmd5     = hash('sha256',(hash_file('sha256', $filepath).$this->Cnf_enkey));
                $filenameHead = explode('.', basename($filename), 2)[0] ?? basename($filename);
                $cachefile = $this->Cnf_cacheDir . '/' .$filenameHead.'_'.$fmd5. '.php';
                if(!is_file($cachefile) || $this->Cnf_update)
                   $isupdate = true;
            }
			
            $ret = ['tpl' => $filepath, 'cache' => $cachefile, 'isupdate' => $isupdate];
			$this->debug('auto['.$filename.']', $ret['tpl'].'('.$GetType.')');
			return $ret;
        }

        // 有一种纯搜索文件. 只会返回string
        $autoDir = $this->Cnf_autoDir;
		$searchDirs = [];
		$keydir = md5(implode('|', $autoDir));
		// find_Dirs
		if(isset($this->find_Dirs[$keydir])){
			$searchDirs = $this->find_Dirs[$keydir];
		}else{
			foreach($autoDir as $dir){
				if($dir === '' || !is_dir($dir)){
					continue;
				}

				// 先放入当前目录，再把其子目录依次加入，保证目录相对顺序稳定。
				$searchDirs[] = $dir;
				try{
					$iterator = new RecursiveIteratorIterator(
						new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
						RecursiveIteratorIterator::SELF_FIRST
					);
					foreach($iterator as $item){
						if($item instanceof SplFileInfo && $item->isDir()){
							$searchDirs[] = str_replace('\\', '/',$item->getPathname());
						}
					}
				}catch(Throwable){
					// 忽略无权限目录，继续搜索其他目录。
				}
			}
			$this->find_Dirs[$keydir] = $searchDirs;
			$this->debug('find_dirs', $searchDirs);
		}
		
		$full = '';
		$searchDirs = array_values(array_unique($searchDirs));
		foreach($searchDirs as $dir){
			$dir = str_replace('\\', '/', rtrim($dir, '/\\'));
			$full = $dir . '/' . ltrim($filename, '_');
			if(is_file($full)){
				break;
			}

			$full = $dir . '/' . ltrim($filename, '_') . $this->Cnf_suffix;
			if(is_file($full)){
				break;
			}
		}

		$this->debug('auto['.$filename.']',  $full.'('.$GetType.')');
        return $full;
    }

    /**
     * 带文件锁的安全文件读写操作。
     *
     * 根据第二个参数决定读写模式：
     *   - 读取模式（$lock 为空）：以共享锁读取文件全部内容，返回字符串。
     *   - 写入模式（$lock 非空）：以排他锁将 $lock 内容写入文件，返回写入字节数。
     *
     * 使用非阻塞锁（LOCK_NB）避免长时间等待，获取锁失败时不执行操作。
     *
     * @param  string $path 文件路径
     * @param  string $strData 可选，写入内容；默认为特殊字符串表示读取模式
     * @return int|string|false 读取模式返回文件内容字符串；写入模式返回写入字节数或 0；
     *                          文件打开失败返回 false
     */
    protected function preg__file(string $path, string $strData='*********'){
        $mode = $strData !== '*********' ?'wb+':'rb';
        if(!@$fp = fopen($path, $mode))
            return false;

        $ints = 0;
        if($mode === 'wb+'){
            if(flock($fp, LOCK_EX | LOCK_NB)){
                if(!$ints = fwrite($fp, $strData))
                    return 0;
                $this->debug('write:' . $path, $ints.' bytes');
                $this->rwcount ++;
                flock($fp, LOCK_UN);
                fclose($fp);
            }
        }else{
            $ints = '';
            if(flock($fp, LOCK_SH | LOCK_NB)){
                while(!feof($fp)){
                    $ints .= fread($fp, 4096);
                }
				$this->debug('read:' . $path,strlen($ints).' bytes');
                flock($fp, LOCK_UN);
                fclose($fp);
            }
        }
         return $ints;
    }
}

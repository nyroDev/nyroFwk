<?php
/**
 * @author Cedric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
 */
/**
 *
 */
final class debug {

	/**
	 * Debug configuration
	 *
	 * @var config
	 */
	private static $cfg;

	/**
	 * Array of timer for debug
	 *
	 * @var array
	 */
	private static $timer = array();

	/**
	 * retrieve the function where the call come from
	 *
	 * @param int $nb Recursion number
	 * @param null|string $sep Seperator for the return between the className and the function name If null, only the className is returned
	 * @return string CLASS.$sep.FUNCTION
	 */
	public static function callFrom($nb=1, $sep='-') {
		$nb++;
		$dbt = debug_backtrace();
		if ($sep === null)
			return $dbt[$nb]['class'];
		else
			return $dbt[$nb]['class'].$sep.$dbt[$nb]['function'];
	}

	/**
	 * Return a string for debug an element/object to debug
	 *
	 * @param mixed $obj: The object to show
	 * @param bool $printExit: true or 1 to print the result, 2 for print and exit
	 * @return string
	 */
	public static function trace($obj, $printExit=false) {
		$ret = '<pre>'.htmlentities(print_r($obj, true)).'</pre>';
		if ($printExit > 1)
			response::getInstance()->sendText($ret);
		else if ($printExit)
			echo $ret;
		else
			return $ret;
	}

	private static function initCfg() {
		if (!self::$cfg)
			self::$cfg = new config(factory::loadCfg(__CLASS__));
	}

	public static function errorHandler($code, $message, $file, $line) {
		self::initCfg();
		$msg = self::$cfg->getInArray('errors', $code).': '.$message.' at '.$file.':'.$line;

		$stopCfg = self::$cfg->stop;
		$stop = is_array($stopCfg)? in_array($code, $stopCfg) : $stopCfg;

		if ($stop) {
		    $e = new nException($msg, $code);
		    $e->line = $line;
		    $e->file = $file;
			throw $e;
			return true;
		} else {
			// Maybe log something somewhere?
		}
	}

	/**
	 * Get the HTML for the debugger, and add the CSS and JS to the response
	 *
	 * @param array $elts
	 * @return string
	 */
	public static function debugger(array $elts=null) {
		if (is_null($elts)) {
			debug::timer('nyro');
			debug::timer('nyroRender');
			return debug::debugger(array(
				'timing'=>array('Timing', debug::timer(), 'time'),
				'included'=>array('Included Files', get_included_files(), array('name'=>'code_red', 'type'=>'script')),
				'session'=>array('Session vars', $_SESSION, 'shield'),
				'db_queries'=>array('DB Queries', db::log(), 'database'),
				'consts'=>array('Constants', array_reverse(get_defined_constants(true), true), array('name'=>'gear', 'type'=>'script')),
				'request'=>array('Request', request::get(), array('name'=>'right', 'type'=>'arrow')),
				'cookies'=>array('Cookies', $_COOKIE, array('name'=>'gray', 'type'=>'user')),
				'get'=>array('Get', $_GET, array('name'=>'show', 'type'=>'tag')),
				'post'=>array('Post', $_POST, array('name'=>'green', 'type'=>'tag')),
				'files'=>array('Files', $_FILES, array('name'=>'orange', 'type'=>'tag')),
				'response'=>array('Response', array('Headers'=>response::getInstance()->getHeader(), 'Included Files'=>response::getInstance()->getIncFiles()), array('name'=>'right', 'type'=>'arrow')),
			));
		}
		if (request::get('out') != 'html')
			return;

		$menu = array();
		$content = array();
		$close = utils::getIcon(array('name'=>'cross', 'type'=>'default', 'attr'=>array('class'=>'close', 'alt'=>'Close')));
		foreach($elts as $k=>$v) {
			$icon = array_key_exists(2, $v)
				? (utils::getIcon(is_array($v[2])? $v[2] : array('name'=>'show', 'type'=>$v[2])))
				: null;
			$menu[] = '<a rel="'.$k.'">'.$icon.$v[0].'</a>';
			$tmp = '<div class="debugElt" id="'.$k.'" style="display: none;">'.$close.'<h2>'.$icon.$v[0].'</h2>';
			if (is_array($v[1])) {
				if (is_numeric(key($v[1])))
					$tmp.= '<ol><li>'.implode('</li><li>', $v[1]).'</li></ol>';
				else
					$tmp.= debug::trace($v[1]);
			} else
				$tmp.= $v[1];
			$tmp.= '</div>';
			$content[] = $tmp;
		}

		$resp = response::getInstance();
		return '<div id="nyroDebugger">'
			.$resp->getIncludeTagFile('js', 'debug')
			.$resp->getIncludeTagFile('css', 'debug')
			.'<ul><li id="close">'.$close.'</li><li>'.implode('</li><li>', $menu).'</li></ul>'
			.implode("\n", $content)
			.'</div>';
	}

	/**
	 * Enter description here...
	 *
	 * @param string $name Timer name
	 * @return unknown
	 */
	public static function timer($name=null) {
		if (is_null($name)) {
			$tmp = array();
			foreach(self::$timer as $k=>$v)
				if (array_key_exists(1, $v))
					$tmp[$k] = $v[1];
			return $tmp;
		}

		if (!array_key_exists($name, self::$timer))
			self::$timer[$name] = array(microtime(true)*1000);
		else {
			self::$timer[$name][1] = (microtime(true)*1000 - self::$timer[$name][0]).' ms';
			return self::$timer[$name][1];
		}
	}
}

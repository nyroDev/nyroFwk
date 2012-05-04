<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * functions utils
 */
class utils {

	/**
	 * Split an array with
	 *
	 * @param array $arr Array to split
	 * @param int $nb Count element for the first part
	 * @param bool $presKey True if the key must be preserved
	 * @return array First element will be the first splitted part, Second the rest
	 */
	public static function cutArray(array $arr, $nb, $presKey = true) {
		return array(
			array_slice($arr, 0, $nb, $presKey),
			array_slice($arr, $nb, count($arr), $presKey)
		);
	}

	/**
	 * Transform HTML to text, using html2text library
	 *
	 * @param string $html The HTML string to transform
	 * @return string
	 */
	public static function html2Text($html) {
		lib::load('html2text');
		return html2text($html);
	}

	/**
	 * Create a HTML tag
	 *
	 * @param string $tag Tag name
	 * @param array $attributes attributes for the tag used by htmlAttribute
	 * @param null|string $content Eventual tag content
	 * @return string
	 */
	public static function htmlTag($tag, array $attributes, $content = null) {
		$ret = '<'.$tag.' '.self::htmlAttribute($attributes);
		if (!is_null($content))
			$ret.= '>'.$content.'</'.$tag.'>';
		else
			$ret.= ' />';
		return $ret;
	}

	/**
	 * Create a mailto HTML tag to send an email, with obfuscating
	 *
	 * @param string $email The email
	 * @param string|null $name The name to show as text. If null, $email will be used
	 * @param array $attributes Attributes to add to the html tag
	 * @return string
	 */
	public static function mailTo($email, $name = null, array $attributes = array()) {
		$emailObfs = self::htmlObfuscate($email);
		if (is_null($name))
			$name = $emailObfs;
		$emailObfs = self::htmlObfuscate('mailto:').$emailObfs;
		return self::htmlTag('a', array_merge(array('href'=>$emailObfs), $attributes), $name);
	}

	/**
	 * Create the HTML string to obfuscate a text (usually an email)
	 *
	 * @param string $text
	 * @return string
	 */
	public static function htmlObfuscate($text) {
		$ret = null;
		for ($i=0; $i<strlen($text); $i++)
			$ret.= '&#'.ord($text[$i]).';';
		return $ret;
	}

	/**
	 * Create a string for HTML attribute
	 *
	 * @param array $prm Array for create the attribue
	 * @return string
	 */
	public static function htmlAttribute(array $prm) {
		$tmp = array();
		foreach($prm as $k=>$v)
			if (!empty($v) || $v === 0 || $v === '0')
				$tmp[] = $k.'="'.$v.'"';
		return implode(' ', $tmp);
	}

	/**
	 * Convert html entities. If array provided, all the data will be converted
	 *
	 * @param array|string $val
	 * @param bool $key In case of an array, indicate if the keys should also be processed
	 * @return array|string
	 */
	public static function htmlOut($val, $key = false) {
		if (is_array($val)) {
			if ($key) {
				$tmp = $val;
				$val = array();
				foreach($tmp as $k=>$t)
					$val[self::htmlOut($k)] = self::htmlOut($t);
			} else
				array_walk_recursive($val, create_function('&$v', '$v = utils::htmlOut($v);'));
		} else {
			$tmp = self::htmlChars();
			$val = str_replace(array_keys($tmp), $tmp, $val);
		}
		return $val;
	}

	/**
	 * Opposite of htmlOut
	 *
	 * @param array|string $val
	 * @param bool $key In case of an array, indicate if the keys should also be processed
	 * @return array|string
	 */
	public static function htmlDeOut($val, $key = false) {
		if (is_null($val))
			return $val;
		if (is_array($val)) {
			if ($key) {
				$tmp = $val;
				$val = array();
				foreach($tmp as $k=>$t)
					$val[self::htmlDeOut($k)] = self::htmlDeOut($t);
			} else
				array_walk_recursive($val, create_function('&$v', '$v = utils::htmlDeOut($v);'));
		} else {
			$tmp = self::htmlChars();
			$val = str_replace($tmp, array_keys($tmp), $val);
		}
		return $val;
	}

	/**
	 * HTML translation table
	 *
	 * @var array
	 */
	private static $htmlChars = null;

	/**
	 * Get the HTML translation table
	 *
	 * @return array
	 */
	public static function htmlChars() {
		if (is_null(self::$htmlChars)) {
			$tmp = array();
			foreach(get_html_translation_table(HTML_ENTITIES) as $k=>$v) {
				$tmp[utf8_encode($k)]= utf8_encode($v);
			}
			unset($tmp['&']); // Unset here to place it at the very top of the array
			self::$htmlChars = array_merge(array(
				'&'=>'&amp;',
				'Œ'=>'&OElig;',
				'œ'=>'&oelig;',
				'Š'=>'&Scaron;',
				'š'=>'&scaron;',
				'Ÿ'=>'&Yuml;',
				'^'=>'&circ;',
				'˜'=>'&tilde;',
				'–'=>'&ndash;',
				'—'=>'&mdash;',
				'‘'=>'&lsquo;',
				'’'=>'&rsquo;',
				'‚'=>'&sbquo;',
				'“'=>'&ldquo;',
				'”'=>'&rdquo;',
				'„'=>'&bdquo;',
				'†'=>'&dagger;',
				'‡'=>'&Dagger;',
				'‰'=>'&permil;',
				'‹'=>'&lsaquo;',
				'›'=>'&rsaquo;',
				'€'=>'&euro;',
			), $tmp);
		}
		return self::$htmlChars;
	}

	/**
	 * Used to retrieve the data from the request
	 *
	 * @param array|string $val
	 * @return array|string
	 */
	public static function htmlIn($val) {
		return $val;
		if (is_array($val))
			array_walk_recursive($val, create_function('&$v', '$v = utf8_decode($v);'));
		else
			$val = $val;
		return $val;
	}

	/**
	 * Get the module name from the class name
	 *
	 * @param string $className
	 * @return string
	 */
	public static function getModuleName($className) {
		$tmp = explode('_', $className);
		array_shift($tmp);
		array_pop($tmp);
		return implode('_', $tmp);
	}

	/**
	 * Transform a numeric pair array to a string key array
	 *
	 * @param array $vars Variable array to transfer
	 * @param string $finalName Name for the last key, if needed
	 */
	public static function initTabNumPair(array &$vars, $finalName = 'final') {
		$ret = array();
		if (!empty($vars)) {
			for($i = 1; $i<count($vars); $i+=2) {
				$ret[$vars[$i-1]] = $vars[$i];
			}
			if ($i == count($vars))
				$ret[$finalName] = $vars[$i-1];
		}
		return $ret;
	}

	/**
	 * Get an icon
	 *
	 * @param array $prm Icon configuration. Available key:
	 *  - string name: action name (required)
	 *  - string type: icon type
	 *  - bool imgTag: true if the return should be a valid html img tag. if false, will return he url
	 *  - string alt: alt text for the image, used only if imgTag = true
	 *  - array attr: attributes added to the img tag
	 * @return unknown
	 */
	public static function getIcon(array $prm) {
		$ret = null;

		static $cfg;
		if (!$cfg)
			$cfg = factory::loadCfg('icons', false);

		if (config::initTab($prm, array(
			'name'=>null,
			'type'=>$cfg['default'],
			'imgTag'=>true,
			'alt'=>'',
			'attr'=>array(),
		))) {
			if (array_key_exists($prm['type'], $cfg['icons'])
				&& is_array($cfg['icons'][$prm['type']])
				&& in_array($prm['name'], $cfg['icons'][$prm['type']])) {
					$ret = request::get('path').$cfg['dir'].'/'.$prm['type'].request::getCfg('sepParam').$prm['name'].$cfg['ext'];
			} else if ($prm['type'] != $cfg['default']) {
				$ret = self::getIcon(array('name'=>$prm['name'], 'imgTag'=>false));
			}

			if ($ret && $prm['imgTag']) {
				$alt = $prm['alt']? $prm['alt'] : ucFirst($prm['name']);
				$ret = self::htmlTag('img', array_merge(array(
					'src'=>$ret,
					'alt'=>$alt
				), $prm['attr']));
			}
		}
		return $ret;
	}

	/**
	 *
	 * @var helper_date
	 */
	protected static $date;

	/**
	 * Format a date, using helper_date
	 *
	 * @param int|string $date Int to directly set a timestamp or strong for a date to format (compatible with strtotime)
	 * @param string $type Format needed
	 * @param string $len Format length needed
	 * @return string The date formatted
	 * @see helper_date::format
	 */
	public static function formatDate($date, $type = 'date', $len = 'short2', $htmlOut = true) {
		if (is_null(self::$date)) {
			self::$date = factory::getHelper('date');
		}
		self::$date->getCfg()->setA(array(
			'defaultFormat'=>array(
				'type'=>$type,
				'len'=>$type == 'datetime' && $len == 'short2' ? 'short' : $len
			),
			'htmlOut'=>$htmlOut
		));
		self::$date->set($date, is_int($date) ? 'timestamp' : 'date');
		return self::$date->format();
	}

	/**
	 * Create the image tag for an image (in the img directory)
	 *
	 * @param string|array $prm Src string or array of attribute for the img tag
	 * @return string The HTML img tag
	 */
	public static function img($prm, $absolute = false) {
		if (!is_array($prm))
			$prm = array('src'=>$prm, 'size'=>true);
		$alt = $prm['src'];
		if (isset($prm['size'])) {
			unset($prm['size']);
			if (!isset($prm['width']) && !isset($prm['height'])) {
				$size = getimagesize(WEBROOT.'img/'.$prm['src']);
				if (!isset($prm['width']))
					$prm['width'] = $size[0];
				if (!isset($prm['height']))
					$prm['height'] = $size[1];
			}
		}
		$prm['src'] = request::get('path').'img/'.$prm['src'];
		if ($absolute)
			$prm['src'] = request::get('domain').$prm['src'];
		return self::htmlTag('img', array_merge(array(
			'alt'=>$alt
		), $prm));
	}

	/**
	 * Render a tpl element
	 *
	 * @param array $prm Array configuration, with at least the module and action keys
	 * @return string The rendered element
	 */
	public static function render(array $prm) {
		return factory::get('tpl', $prm)->render($prm);
	}

	/**
	 * Get a value in an array, by specifing the path to it
	 *
	 * @param array $source The array source
	 * @param array $keys The path in the $source array, numercally indexed
	 * @return mixed
	 */
	public static function getValInArray(array $source, array $keys) {
		$ret = null;
		if (array_key_exists($keys[0], $source)) {
			if (is_array($source[$keys[0]]) && count($keys) > 1)
				$ret = self::getValInArray($source[$keys[0]], array_slice($keys, 1));
			else
				$ret = $source[$keys[0]];
		}
		return $ret;
	}

	/**
	 * Clean a string to be used in an URL
	 *
	 * @param string $text
	 * @param string $ignore Ignore charater list
	 * @return string
	 */
	public static function urlify($text, $ignore = null) {
		$text = str_replace(
			array('ß' , 'æ',  'Æ',  'Œ', 'œ', '¼',   '½',   '¾',   '‰',   '™'),
			array('ss', 'ae', 'AE', 'OE', 'oe', '1/4', '1/2', '3/4', '0/00', 'TM'),
			$text);
		$from = "ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøðÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüŠšÝŸÿÑñÐÞþ()[]~¤$&%*@ç§¶!¡†‡?¿;,.:/\\^¨€¢£¥{}|¦+÷×±<>«»“”„\"‘’' ˜–—…©®¹²³°";
		$to   = 'AAAAAAaaaaaaOOOOOOoooooooEEEEeeeeCcIIIIiiiiUUUUuuuuSsYYyNnDPp           cS        --     EcPY        __________------CR123-';
		if (!is_null($ignore)) {
			$len = strlen($ignore);
			for($i = 0; $i < $len; $i++) {
				$pos = strpos($from, $ignore{$i});
				if ($pos !== false) {
					$from = substr($from, 0, $pos).substr($from, $pos+1);
					$to = substr($to, 0, $pos).substr($to, $pos+1);
				}
			}
		}
		return trim(str_replace(
			array(' ', '-----', '----', '---', '--'),
			URLSEPARATOR,
			strtr(utf8_decode($text), utf8_decode($from), utf8_decode($to))), URLSEPARATOR);
	}

	/**
	 * Encodes an array to be used as a Json object. Keeps functions declaration
	 * 
	 * @param array $vars
	 * @return string
	 */
	public static function jsEncode($vars) {
		$func = array();
		if (is_array($vars)) {
			foreach($vars as $k=>$v) {
				if (is_string($v) && strpos($v, 'function(') === 0) {
					$func['"'.$k.'Func"'] = $v;
					$vars[$k] = $k.'Func';
				}
			}
		}
		
		$encoded = json_encode($vars);
		if (!empty($func))
			$encoded = str_replace(array_keys($func), $func, $encoded);

		
		return $encoded;
	}

	/**
	 * Create a random string
	 *
	 * @param int $len Length of the returned string
	 * @param null|string $ignore Character to exclude from the random string
	 * @return string
	 */
	public static function randomStr($len = 10, $ignore = null) {
		$source = 'abcdefghikjlmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
		if (!is_null($ignore)) {
			$tmp = array();
			for($i=0;$i<strlen($ignore);$i++)
				$tmp[] = $ignore[$i];
			$source = str_replace($tmp, '', $source);
		}
		$len = abs(intval($len));
		$n = strlen($source)-1;
		$r = '';
		for($i = 0; $i < $len; $i++)
			$r.= $source{rand(0, $n)};
		return $r;
	}

	/**
	 * Indicate if a configuration array is contained in the url
	 *
	 * @param array $url
	 * @param array $checks
	 * @return bool True if a line in $checks matches the $url
	 */
	public static function isContained(array $url, array $checks) {
		foreach($checks as $c) {
			$tmp = array_intersect_key($url, $c);
			$nbM = 0;
			foreach($tmp as $k=>$v)
				if (!is_array($v))
					$nbM += (preg_match('/'.$c[$k].'/', $v)? 1 : 0);

			if ($nbM == count($tmp))
				return true;
		}
		return false;
	}

}
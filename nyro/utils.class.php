<?php
/**
 * @author Cedric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
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
	public static function cutArray(array $arr, $nb, $presKey=true) {
		return array(
			array_slice($arr, 0, $nb, $presKey),
			array_slice($arr, $nb, count($arr), $presKey)
		);
	}

	public static function html2Text($html) {
		lib::load('markdownify');
		$md = new Markdownify_Extra();
		preg_match('@<body[^>]*>(.*)</body>@siU', $html, $matches);
		if (!empty($matches))
			$html = $matches[1];
		return $md->parseString($html);
	}

	/**
	 * Create a HTML tag
	 *
	 * @param string $tag Tag name
	 * @param array $attributes attributes for the tag used by htmlAttribute
	 * @param null|string $content Eventual tag content
	 * @return string
	 */
	public static function htmlTag($tag, array $attributes, $content=null) {
		$ret = '<'.$tag.' '.self::htmlAttribute($attributes);
		if (!is_null($content))
			$ret.= '>'.$content.'</'.$tag.'>';
		else
			$ret.= ' />';
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
			if (!empty($v))
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
	public static function htmlOut($val, $key=false) {
		if (is_array($val)) {
			if ($key) {
				$tmp = $val;
				$val = array();
				foreach($tmp as $k=>$t)
					$val[htmlentities($k)] = htmlentities($t);
			} else
				array_walk_recursive($val, create_function('&$v', '$v = htmlentities($v);'));
		} else
			$val = htmlentities($val);
		return $val;
	}

	/**
	 * Opposite of htmlOut
	 *
	 * @param array|string $val
	 * @param bool $key In case of an array, indicate if the keys should also be processed
	 * @return array|string
	 */
	public static function htmlDeOut($val, $key=false) {
		if (is_array($val)) {
			if ($key) {
				$tmp = $val;
				$val = array();
				foreach($tmp as $k=>$t)
					$val[html_entity_decode($k)] = html_entity_decode($t);
			} else
				array_walk_recursive($val, create_function('&$v', '$v = html_entity_decode($v);'));
		} else
			$val = html_entity_decode($val);
		return $val;
	}

	/**
	 * Used to retrieve the data from the request
	 *
	 * @param array|string $val
	 * @return array|string
	 */
	public static function htmlIn($val) {
		if (is_array($val))
			array_walk_recursive($val, create_function('&$v', '$v = utf8_decode($v);'));
		else
			$val = utf8_decode($val);
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
	public static function initTabNumPair(array &$vars, $finalName='final') {
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

	public static function formatDate($date, $type='date', $len='short2') {
		$d = factory::getHelper('date', array(
			'timestamp'=>strtotime($date),
			'defaultFormat'=>array(
				'type'=>$type,
				'len'=>$len
			)
		));
		return $d->format();
	}

	public static function img($prm) {
		if (!is_array($prm))
			$prm = array('src'=>$prm);
		$alt = $prm['src'];
		$prm['src'] = request::get('path').'img/'.$prm['src'];
		return self::htmlTag('img', array_merge(array(
			'alt'=>$alt
		), $prm));
	}

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
	
	public static function urlify($text) {
		$from = "ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ()[]~$&%*@ç!?;,:/\\^¨€{}|+<>\"' ’–«»…®";
		$to  =  'AAAAAAaaaaaaOOOOOOooooooEEEEeeeeCcIIIIiiiiUUUUuuuuyNn          c   _     E      _________';
		return str_replace(
			array(' ', '_____', '____', '___', '__'),
			'_',
			strtr($text, $from, $to));
	}

}
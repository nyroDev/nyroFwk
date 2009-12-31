<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * abstract class for multiple value form element
 */
abstract class form_mulValue extends form_abstract {

	/**
	 * Initialize the list and group with a sql request if provided
	 */
	protected function afterInit() {
		parent::afterInit();

		if (!$this->cfg->uniqValue)
			$this->cfg->name.= '[]';

		$dbList = $this->cfg->dbList;
		if (is_array($dbList) && $dbList['table']) {
			$list = $this->cfg->list;
			if (!$list)
				$list = array();

			$group = $this->cfg->group;
			if (!$group)
				$group = array();

			$db = db::getInstance();

			$values = $db->select(array_merge($dbList, array('result'=>MYSQL_NUM)));

			$tmp = null;
			foreach($values as $v) {
				$key = array_shift($v);
				if ($dbList['nbFieldGr'] > 0) {
					$arr = utils::cutArray($v, $dbList['nbFieldGr']);
					$tmp2 = implode($dbList['sepGr'], $arr[0]);
					if ($tmp != $tmp2) {
						$group[$key] = $tmp2;
						$tmp = $tmp2;
					}
					$v = $arr[1];
				}
				if (empty($v))
					$v = array($key);
				$list[$key] = implode($dbList['sep'], $v);
			}
			$this->cfg->group = utils::htmlOut($group);
			$this->cfg->list = utils::htmlOut($list);
		} else if ($this->cfg->needOut)
			$this->cfg->list = utils::htmlOut($this->cfg->list, true);

		if (is_array($this->cfg->list))
			$this->addRule('in', array_keys($this->cfg->list));
	}

	/**
	 * Get the actual value
	 *
	 * @param bool $outside Indicate if it's coming from outside (ie if it should be htmlDeOut)
	 * @return mixed
	 */
	public function getValue($outside=true) {
		$val = parent::getValue();
		if (!is_null($this->cfg->valueNone) && $val == $this->cfg->valueNone)
			return null;
		if ($this->cfg->needOut) {
			if ($outside)
				$val = utils::htmlDeOut($val, true);
			else
				$val = utils::htmlOut($val, true);
		}
		return $val;
	}

	public function setValue($value, $refill=false) {
		if (is_array($value) && $this->cfg->uniqValue) {
			parent::setValue(array_shift($value));
		} else {
			parent::setValue($value);
		}
	}

	public function to($type) {
		if ($this->cfg->mode == 'view') {
			if ($this->cfg->uniqValue)
				return $this->cfg->getInArray('list', $this->getValue(false));
			else {
				$tmp = array();
				foreach($this->getValue(false) as $v)
					$tmp[] = $this->cfg->getInArray('list', $v);
				return implode(', ', $tmp);
			}
		}

		$prm = $this->cfg->get($type);
		$inline = $this->cfg->inline? 'Inline' : null;
		$ret = null;

		$tmpGr = null;
		$tmpVal = null;
		if (is_array($this->cfg->list)) {
			foreach($this->cfg->list as $k=>$v) {
				if (is_array($this->cfg->group) && array_key_exists($k, $this->cfg->group) && $tmpGr != $k) {
					$tmpGr = $k;
					$ret.= str_replace(
						array('[label]', '[group]'),
						array($this->cfg->group[$k], $tmpVal),
						$prm['group']);
					$tmpVal = null;
				}
				$selected = $this->isInValue($k)? $prm['selected'] : null;

				$tmpVal.= str_replace(
					array('[plus]', '[value]', '[label]'),
					array($selected, $k, $v),
					$prm['value']);
			}
		}

		if (!empty($tmpGr)) {
			$ret.= str_replace(
				array('[label]', '[group]'),
				array($this->cfg->group[$k], $tmpVal),
				$prm['group']);
		} else
			$ret.= $tmpVal;

		$this->id = (!$this->cfg->uniqValue)? substr($this->cfg->name, 0, -2) : $this->cfg->name;
		return str_replace(
			array('[values]', '[plus]', '[name]', '[id]'),
			array($ret, $prm['plus'], $this->cfg->name, $this->id),
			$prm['global'.$inline]);
	}

	public function toHtml() {
		return $this->to('html');
	}

	public function toXul() {
		return $this->to('xul');
	}

	/**
	 * Check if a value is in the current value
	 *
	 * @param mixed $val
	 * @return bool
	 */
	public function isInValue($val) {
		if (is_array($this->cfg->value))
			return (in_array($val, $this->cfg->value));
		else
			return ($val == $this->cfg->value);
	}

}

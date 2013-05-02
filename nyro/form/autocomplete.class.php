<?php
class form_autocomplete extends form_checkbox {

	public function setValue($value, $refill = false) {
		if (is_array($value) && $this->cfg->uniqValue) {
			parent::setValue(array_shift($value));
		} else {
			if (is_array($value))
				$value = $this->addNew($value);
			parent::setValue($value);
		}
	}

	/**
	 * Parse values send through POST data and eventually add the new ones
	 *
	 * @param array $values Valeus to parse
	 * @return array Values to use
	 */
	protected function addNew(array $values) {
		$new = isset($values['new']) && is_array($values['new']) ? array_filter($values['new']) : null;
		unset($values['new']);
		if ($this->cfg->allowAdd && count($new) && is_array($new)) {
			$dbList = $this->cfg->dbList;
			if (is_array($dbList) && $dbList['table'] && $dbList['db']) {
				$tbl = $dbList['db']->getTable($dbList['table']);
				$list = utils::htmlDeOut($this->cfg->list);

				$i18n = isset($dbList['i18nFields']) && $dbList['i18nFields'];
				if (!$i18n)
					$field = substr($dbList['fields'], strlen($dbList['ident'])+1);

				foreach($new as $v) {
					$exists = array_search($v, $list);
					if ($exists === false) {
						$row = $tbl->getRow();
						if ($i18n) {
							$row->setI18n(array($dbList['i18nFields']=>$v));
						} else {
							$row->set($field, $v);
						}
						$id = $row->save();
						$values[] = $id;
						$list[$id] = $v;
					} else
						$values[] = $exists;
				}
				$this->cfg->list = utils::htmlOut($list);
				if (is_array($this->cfg->list))
					$this->addRule('in', array_keys($this->cfg->list));
			}
		}
		return $values;
	}

	public function to($type) {
		$ret = parent::to($type);

		if ($type == 'html' && $this->cfg->mode == 'edit') {
			$ret = '<span id="'.$this->id.'Container">'.$ret.'</span>';
			$resp = response::getInstance();
			$resp->addJs('jqueryui');
			$resp->addJs('formAutocomplete');
			$prm = $this->cfg->jsPrm;
			$prm['name'] = $this->id.'[]';
			$prm['nameNew'] = $this->id.'[new]';
			$resp->blockJquery('$("#'.$this->id.'Container").formAutocomplete('.json_encode($prm).');');
		}

		return $ret;
	}
}
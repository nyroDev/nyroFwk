<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * class for create form directly from the db fields
 */
class form_db extends form {

	/**
	 * Add a form element regarding the field information
	 *
	 * @param array $field Field information
	 * @param bool $isI18n
	 * @return form_abstract Reference to the new element
	 */
	public function addFromField(array $field, $isI18n=false) {
		$prm = $this->getFromFieldPrm($field);
		return $this->add($prm['type'], $prm, $isI18n);
	}

	/**
	 * Add a form element for a related filter
	 *
	 * @param array $related
	 */
	public function addFromRelated(array $related) {
		$prm = array(
			'dbList'=>$related['fk2']['link'],
			'name'=>$related['name'],
			'label'=>$related['label'],
			'valid'=>array_key_exists('valid', $related)? $related['valid'] : false
		);
		$prm['dbList']['fields'] = $related['fk2']['link']['ident'].
				($prm['dbList']['fields']? ','.$prm['dbList']['fields'] : null);
		$type = 'checkbox';
		if (isset($related['fields']) && count($related['fields'])) {
			$type = 'checkbox_fields';
			$prm['table'] = $related['tableObj'];
			$prm['fields'] = $related['fields'];
		}
		if (count($prm['dbList']['fields'])) {
			$tmp = array();
			foreach(explode(',', $prm['dbList']['fields']) as $f) {
				if ($f != $prm['dbList']['ident']) {
					$tmp[] = $f;
				}
			}
			if (count($tmp))
				$prm['dbList']['order'] = implode(' ASC, ', $tmp).' ASC';
		}
		if (array_key_exists('formType', $related) && $related['formType'])
			$type = $related['formType'];
		return $this->add($type, $prm);
	}

	/**
	 * Add a form element regarding the field information, especially for a filter
	 *
	 * @param array $field Field information
	 * @return form_abstract Reference to the new element
	 */
	public function addFromFieldFilter(array $field) {
		$saveAutoValidRule = $this->cfg->autoValidRule;
		$this->cfg->autoValidRule = array();
		if (isset($field['formTypeFilter'])) {
			$field['formType'] = $field['formTypeFilter'];
		} else if (isset($field['formType'])) {
			unset($field['formType']);
		}
		$prm = $this->getFromFieldPrm($field);
		$this->cfg->autoValidRule = $saveAutoValidRule;
		unset($prm['value']);
		switch($prm['type']) {
			case 'multiline':
			case 'richtext':
				$prm['type'] = 'text';
				break;
			case 'date':
			case 'datetime':
				$prm['type'] = 'range_date';
				break;
			case 'radio':
				if ($field['type'] == 'tinyint')
					$prm['list'] += array('-1'=>$this->cfg->all);
				else
					$prm['type'] = 'checkbox';
				$prm['valueNone'] = -1;
				$prm['value'] = -1;
				break;
			case 'list':
				$prm['list'] += array('-1'=>$this->cfg->all);
				$prm['valueNone'] = -1;
				$prm['value'] = -1;
				break;
			case 'file':
				$prm['type'] = 'checkbox';
				$prm['list'] = array('1'=>$this->cfg->getInArray('listBool', 1));
				$prm['uniqValue'] = true;
				break;
			case 'numeric':
				$prm['type'] = 'range_numeric';
				$prm['valid']['validEltArray'] = true;
				$prm['allowedRange'] = $this->cfg->table->getRange($prm['name']);
				break;
		}
		$prm['valid']['required'] = false;
		$prm['valid']['dbUnique'] = false;
		return $this->add($prm['type'], $prm);
	}

	/**
	 * Add a form element for a related filter, especially for a filter
	 *
	 * @param array $related
	 */
	public function addFromRelatedFilter(array $related) {
		$prm = array(
			'dbList'=>$related['fk2']['link'],
			'name'=>$related['name'],
			'label'=>$related['label'],
			'valid'=>array_key_exists('valid', $related)? $related['valid'] : false
		);
		$prm['dbList']['fields'] = $related['fk2']['link']['ident'].
				($prm['dbList']['fields']? ','.$prm['dbList']['fields'] : null);
		$type = 'checkbox';
		if (count($prm['dbList']['fields'])) {
			$tmp = array();
			foreach(explode(',', $prm['dbList']['fields']) as $f) {
				if ($f != $prm['dbList']['ident']) {
					$tmp[] = $f;
				}
			}
			if (count($tmp))
				$prm['dbList']['order'] = implode(' ASC, ', $tmp).' ASC';
		}
		if (array_key_exists('formTypeFilter', $related) && $related['formTypeFilter'])
			$type = $related['formTypeFilter'];
		return $this->add($type, $prm);
	}

	/**
	 * Get a form element array parameter regarding the field information
	 *
	 * @param array $field Field information
	 * @return form_abstract Reference to the new element
	 */
	public function getFromFieldPrm(array $field) {
		$ret = array();
		$prm = array_merge(array(
				'name'=>$field['name'],
				'label'=>$field['label'],
				'value'=>array_key_exists('value', $field)? $field['value'] : $field['default'],
				'link'=>array_key_exists('link', $field)? $field['link'] : null,
				'valid'=>array('required'=>$field['required'])
			), $field['comment']);

		if ($field['unique'] && ! array_key_exists('dbUnique', $prm['valid'])) {
			$prm['valid']['dbUnique'] = array(
				'table'=>$this->cfg->table,
				'field'=>$field['name'],
				'value'=>$prm['value']
			);
		}
		if (array_search($field['name'], $this->cfg->autoValidRule) !== false && !array_key_exists($field['name'], $prm['valid']))
			$prm['valid'][$field['name']] = true;

		if (array_search('hidden', $field['comment'], true) !== false)
			$ret = array_merge($prm, array('type'=>'hidden'));
		else {
			if (!empty($field['link'])) {
				$checkType = false;
				$type = 'list';
				$prm['list'] = $field['link']['list'];
				$prm['valueNone'] = 0;
				$fields = null;
				$order = null;
				$join = null;
				if ($field['link']['fields']) {
					$linkedTable = db::get('table', $field['link']['table'], array(
						'db'=>$this->cfg->table->getDb()
					));
					$tmp = array();
					foreach(explode(',', $field['link']['fields']) as $t) {
						if ($linkedInfo = $linkedTable->getLinkedTableName($t)) {
							$alias = $field['name'].'_'.$linkedInfo['table'];
							$join[] = array(
								'table'=>$linkedInfo['table'],
								'alias'=>$alias,
								'dir'=>'left outer',
								'on'=>$field['link']['table'].'.'.$linkedInfo['field'].'='.$alias.'.'.$linkedInfo['ident']
							);
							$ttmp = array();
							foreach(explode(',', $linkedInfo['fields']) as $tt) {
								$ttmp[] = $alias.'.'.$tt;
								$ttmp[] = '"'.$linkedInfo['sep'].'"';
							}
							array_pop($ttmp);
							$tmp[] = 'CONCAT('.implode(',', $ttmp).')';
						} else
							$tmp[] = $field['link']['table'].'.'.$t;
					}
					$fields.= ','.implode(',', $tmp);
					$order = implode(' ASC, ', $tmp).' ASC';
				}
				$prm['dbList'] = array(
					'fields'=>$field['link']['table'].'.'.$field['link']['ident'].$fields,
					'i18nFields'=>$field['link']['i18nFields'],
					'ident'=>$field['link']['ident'],
					'table'=>$field['link']['table'],
					'join'=>$join,
					'sep'=>$field['link']['sep'],
					'where'=>$field['link']['where'],
					'order'=>$order,
					'sepGr'=>$field['link']['sepGr'],
					'nbFieldGr'=>$field['link']['nbFieldGr']
				);
				$ret = array_merge($prm, array('type'=>$type));
			} else {
				switch($field['type']) {
					case 'set':
					case 'enum':
						$type = 'radio';
						$prm['list'] = $field['precision'];
						$prm['needOut'] = true;
						break;
					case 'date':
						$type = 'date';
						$prm['valid']['different'] = '0000-00-00';
						break;
					case 'datetime':
						$type = 'datetime';
						$prm['valid']['different'] = '0000-00-00 00:00:00';
						break;
					case 'text':
					case 'blob':
					case 'tinytext':
					case 'tinyblob':
					case 'mediumtext':
					case 'mediumblob':
					case 'longtext':
					case 'longblob':
						$key = array_search('richtext', $field['comment']);
						if ($key !== false) {
							$type = 'richtext';
							unset($field['comment'][$key]);
							$prm['html'] = $field['comment'];
							if (array_key_exists('tinyMce', $field))
								$prm['tinyMce'] = $field['tinyMce'];
							unset($prm['html']['tinyMce']);
						} else {
							$type = 'multiline';
							$prm['maxlength'] = $field['length'];
						}
						break;
					case 'bool':
					case 'boolean':
					case 'tinyint':
						$type = 'radio';
						$prm['list'] = $this->cfg->listBool;
						$prm['inline'] = true;
						if ($field['type'] != 'tinyint' || ($field['type'] == 'tinyint' && $field['length'] == 1)) {
							$prm['valid']['required'] = false;
							break;
						}
					case 'year':
					case 'bigint':
					case 'mediumint':
					case 'smallint':
					case 'int':
						$type = 'numeric';
						$prm['maxlength'] = $field['length'];
						$prm['valid']['int'] = true;
						break;
					case 'decimal':
					case 'float':
					case 'double':
					case 'decimal':
					case 'dec':
					case 'numeric':
					case 'fixed':
						$type = 'numeric';
						$prm['maxlength'] = $field['precision']? $field['length']+1 : $field['length'];
						$prm['valid']['numeric'] = true;
						break;
					case 'file':
						$type = 'file';
						if (count($field['comment']) == 1 && array_key_exists(0, $field['comment']))
							$prm['helper'] = $field['comment'][0];
						else
							$prm = array_merge($prm, $field['comment']);
						break;
					default:
						$type = array_key_exists(0, $field['comment']) && !empty($field['comment'][0])? $field['comment'][0] : 'text';
						$prm['maxlength'] = $field['length'];
				}

				$ret = array_merge($prm, array('type'=>$type));
			}
		}
		if (array_key_exists('formType', $field) && $field['formType'])
			$ret['type'] = $field['formType'];
		return $ret;
	}

}

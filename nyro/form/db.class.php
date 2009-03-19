<?php
/**
 * @author Cedric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyro
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
		return $this->add('checkbox', $prm);
	}

	/**
	 * Add a form element regarding the field information, especially for a filter
	 *
	 * @param array $field Field information
	 * @return form_abstract Reference to the new element
	 */
	public function addFromFieldFilter(array $field) {
		$prm = $this->getFromFieldPrm($field);
		unset($prm['value']);
		switch($prm['type']) {
			case 'multiline':
			case 'richtext':
				$prm['type'] = 'text';
				break;
			case 'date':
				$prm['type'] = 'range_date';
				break;
			case 'datetime':
				$prm['type'] = 'range_date';
				break;
			case 'radio':
				if (!empty($prm['list'])) {
					$prm['list'][-1] = 'all';
					$prm['valueNone'] = -1;
					$prm['value'] = -1;
				}
				break;
			case 'file':
				$prm['type'] = 'checkbox';
				$prm['list'] = array('1'=>'Yes');
				$prm['uniqValue'] = true;
				break;
			case 'numeric':
				$prm['type'] = 'range_numeric';
		}
		return $this->add($prm['type'], $prm);
	}

	/**
	 * Get a form element array parameter regarding the field information
	 *
	 * @param array $field Field information
	 * @return form_abstract Reference to the new element
	 */
	public function getFromFieldPrm(array $field) {
		$prm = array(
			'name'=>$field['name'],
			'label'=>$field['label'],
			'value'=>array_key_exists('value', $field)? $field['value'] : $field['default'],
			'link'=>$field['link'],
			'valid'=>array('required'=>$field['required'])
		);

		if (!empty($field['link'])) {
			$checkType = false;
			$type = 'list';
			$prm['list'] = $field['link']['list'];
			$prm['valueNone'] = 0;
			$prm['dbList'] = array(
				'fields'=>$field['link']['ident'].($field['link']['fields']? ','.$field['link']['fields'] : null),
				'i18nFields'=>$field['link']['i18nFields'],
				'ident'=>$field['link']['ident'],
				'table'=>$field['link']['table'],
				'sep'=>$field['link']['sep'],
				'sepGr'=>$field['link']['sepGr'],
				'nbFieldGr'=>$field['link']['nbFieldGr']
			);
			return array_merge($prm, array('type'=>$type));
		}

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
				if (in_array('richtext', $field['comment'])) {
					$type = array_shift($field['comment']);
					$prm['html'] = utils::initTabNumPair($field['comment']);
					if (array_key_exists('tinyMce', $field))
						$prm['tinyMce'] = $field['tinyMce'];
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
				if (count($field['comment']) == 1)
					$prm['helper'] = $field['comment'][0];
				else
					$prm = array_merge($prm, utils::initTabNumPair($field['comment']));
				break;
			default:
				$type = array_key_exists(0, $field['comment']) && !empty($field['comment'][0])? $field['comment'][0] : 'text';
				$prm['maxlength'] = $field['length'];
		}

		return array_merge($prm, array('type'=>$type));
	}
}

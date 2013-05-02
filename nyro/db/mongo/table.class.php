<?php
class db_mongo_table extends db_table {

	protected function _initIdent() {
		
	}

	public function count(array $prm) {
		
	}

	public function findText($text, $filter = null) {
		
	}

	public function getI18nFields() {
		
	}

	public function getI18nLabel($field = null) {
		
	}

	public function getI18nLinked($field) {
		
	}

	public function getI18nWhereClause($field, $val) {
		
	}
	
	/**
	 * Get the configured fields for this table
	 *
	 * @return array
	 */
	protected function getConfiguredFields() {
		$fields = $this->cfg->getInArray('configuration', 'fields');
		$default = $this->cfg->getInArray('configuration', 'defaultField');
		foreach ($fields as $name=>&$field) {
			$field['name'] = $name;
			config::initTab($field, $default);
		}
		return $fields;
	}

	public function getRange($field = null) {
		
	}

	public function getSortBy($sortBy, $query) {
		
	}

	public function geti18nField($field = null, $keyVal = null) {
		
	}

	public function hasI18n() {
		
	}

	public function select(array $prm = array()) {
		
	}

}
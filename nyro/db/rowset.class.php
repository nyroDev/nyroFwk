<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * rowset object for fetching select results
 */
class db_rowset extends object implements Iterator, Countable, ArrayAccess {

	/**
	* Iterator pointer
	*
	* @var int
	*/
	protected $_pointer = 0;

	/**
	* How many data rows there are
	*
	* @var int
	*/
	protected $_count;

	/**
	 * Instancied rows
	 *
	 * @var array
	 */
	protected $_rows = array();

	/**
	 * Fields Names
	 *
	 * @var array
	 */
	protected $fields;

	protected function afterInit() {
		$this->_count = count($this->cfg->data);
	}

	/**
	 * Return the db object
	 *
	 * @return db_abstract
	 */
	public function getDb() {
		return $this->cfg->db;
	}

	/**
	 * Return the table object
	 *
	 * @return db_table
	 */
	public function getTable() {
		return $this->cfg->table;
	}

	/**
	 * Get a where object
	 *
	 * @param array $prm The configuration for the where object
	 * @return db_where
	 */
	public function getWhere(array $prm = array()) {
		return $this->getDb()->getWhere($prm);
	}

	/**
	 * Get the fields
	 *
	 * @return array
	 */
	public function getFields($mode='flat') {
		if (!$this->fields[$mode])
			$this->fields[$mode] = array_keys($this->get(0)->getValues($mode));
		return $this->fields[$mode];
	}

	/**
	 * Get a specific item of the rowset
	 *
	 * @param int $number Index of the ement
	 * @return db_row Element from the collection
	 */
	public function get($number) {
		if (!array_key_exists($number, $this->_rows)) {
			$this->_rows[$number] = db::get('row', $this->getTable(), array(
				'db'=>$this->getDb(),
				'table'=>$this->getTable(),
				'data'=>$this->cfg->getInArray('data', $number),
			));
		}
		return $this->_rows[$number];
	}

	/**
	 * Add a row with a db_row or an array
	 *
	 * @param db_row|array $row The row to add
	 */
	public function add($row) {
		if ($row instanceof db_row) {
			$this->_rows[$this->_count] = $row;
			$array = $row->getData();
		} else {
			$array = $row;
		}
		$this->cfg->setInArray('data', $this->_count, $array);
		$this->_count++;
	}

	/**
	 * Get the raw data
	 *
	 * @return array
	 */
	public function getData() {
		return $this->cfg->data;
	}

	/**
	* Rewind the Iterator to the first element.
	* Required by interface Iterator.
	*/
	public function rewind() {
		$this->_pointer = 0;
	}

	/**
	* Return the current element.
	* Required by interface Iterator.
	*
	* @return db_row current element from the collection
	*/
	public function current() {
		return $this->get($this->_pointer);
	}

	/**
	* Return the identifying key of the current element.
	* Required by interface Iterator.
	*
	* @return int
	*/
	public function key() {
		return $this->_pointer;
	}

	/**
	* Move forward to next element.
	* Required by interface Iterator.
	*/
	public function next() {
		$this->_pointer++;
	}

	/**
	* Check if there is a current element after calls to rewind() or next().
	* Used to check if we've iterated to the end of the collection.
	* Required by interface Iterator.
	*
	* @return bool False if there's nothing more to iterate over
	*/
	public function valid() {
		return $this->_pointer < $this->_count;
	}

	/**
	* Returns the number of elements in the collection.
	* Required by interface Countable
	*
	* @return int
	*/
	public function count() {
		return $this->_count;
	}

	/**
	 * Check if an index exists.
	 * Required by interface ArrayAccess
	 *
	 * @param int $offset
	 * @return bool
	 */
	public function offsetExists($offset) {
		$this->get($offset);
		return array_key_exists($offset, $this->_rows);
	}

	/**
	 * Get a value.
	 * Required by interface ArrayAccess
	 *
	 * @param int $offset
	 * @return db_row
	 */
	public function offsetGet($offset) {
		return $this->get($offset);
	}

	/**
	 * Set a value.
	 * Required by interface ArrayAccess
	 *
	 * @param int $offset
	 * @param db_row $value
	 * @return db_row
	 */
	public function offsetSet($offset, $value) {
		return $this->_rows[$offset] = $value;
	}

	/**
	 * Remove an element.
	 * Required by interface ArrayAccess
	 *
	 * @param int $offset
	 */
	public function offsetUnset($offset) {
		unset($this->_rows[$offset]);
	}

}
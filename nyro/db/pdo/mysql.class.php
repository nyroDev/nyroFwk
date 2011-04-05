<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 * @version 0.2
 * @package nyroFwk
 */
/**
 * Interface for db classes
 */
class db_pdo_mysql extends db_pdo_abstract {

	/**
	 * Cached tables of the DB
	 *
	 * @var array
	 */
	protected $tables;

	/**
	 * Returns a list of the tables in the database.
	 *
	 * @return array
	 */
	public function getTables() {
		$cache = $this->getCache();
		if (!$cache->get($this->tables, array('id'=>'tables'))) {
			if (is_null($this->tables)) {
				$stmt = $this->query('SHOW TABLES');
				$this->tables = $stmt->fetchAll(db::FETCH_COLUMN);
			}
			$cache->save();
		}
		return $this->tables;
	}

	/**
	 * Returns the properties
	 *
	 * @param string $table TableName
	 * @return array
	 */
	public function fields($table) {
		$cache = $this->getCache();
		$ret = array();
		if (!$cache->get($ret, array('id'=>'fields-'.$table))) {
			$stmt = $this->query('SHOW FULL FIELDS FROM '.$this->quoteIdentifier($table));

			$fField		= 'Field';
			$fType		= 'Type';
			$fNull		= 'Null';
			$fKey		= 'Key';
			$fDefault	= 'Default';
			$fExtra		= 'Extra';
			$fComment	= 'Comment';

			$i = 0;
			$p = 0;
			while($row = $stmt->fetch(db::FETCH_ASSOC)) {
				$length = null;
				$precision = null;
				$unsigned = preg_match('/unsigned/', $row[$fType]);
				$text = false;
				$auto = false;
				$htmlOut = true;
				$tmp = explode($this->cfg->sepCom, $row[$fComment]);
				$comment = array();
				$unique = false;
				foreach($tmp as $t) {
					$tt = explode($this->cfg->sepComVal, $t);
					if (count($tt) == 2)
						$comment[$tt[0]] = $tt[1];
					else
						$comment[] = $t;
				}
				if (preg_match('/^(set|enum)\((.+)\)/', $row[$fType], $matches)) {
					$row[$fType] = $matches[1];
					$tmp = explode(',',$matches[2]);
					array_walk($tmp, create_function('&$t','$t = substr(substr($t, 0, strlen($t)-1), 1);'));
					$precision = array();
					foreach($tmp as $v)
						$precision[$v] = $v;
				} else if (preg_match('/^((?:var)?char)\((\d+)\)/', $row[$fType], $matches)) {
					if (substr($row[$fField], -5) == '_file') {
						$row[$fType] = 'file';
						$htmlOut = false;
					}else {
						$row[$fType] = $matches[1];
						$length = $matches[2];
						$text = true;
					}
				} else if (preg_match('/^(decimal|float|double|decimal|dec|numeric|fixed)\((\d+),(\d+)\)/', $row[$fType], $matches)) {
					$row[$fType] = $matches[1];
					$length = $matches[2];
					$precision = $matches[3];
				} else if (preg_match('/^((?:big|medium|small|tiny)?int)\((\d+)\)/', $row[$fType], $matches)) {
					$row[$fType] = $matches[1];
					$length = $matches[2];
				} else if (preg_match('/^((?:tiny|medium|long)?(text|blob))/', $row[$fType], $matches)) {
					$text = true;
					$htmlOut = !in_array('richtext', $comment);
				}
				if (strtoupper($row[$fKey]) == 'PRI') {
					$primary = true;
					$primaryPos = $p++;
					$identity = ($row[$fExtra] == 'auto_increment');
				} else if (strtoupper($row[$fKey]) == 'UNI') {
					$primary = false;
					$primaryPos = null;
					$identity = false;
					$unique = true;
				} else {
					$primary = false;
					$primaryPos = null;
					$identity = false;
				}
				if (strtoupper($row[$fDefault]) == 'CURRENT_TIMESTAMP') {
					$cf = create_function('', 'return time();');
					$row[$fDefault] = $cf();
					$auto = true;
				}
				$ret[$row[$fField]] = array(
					'name'			=> $row[$fField],
					'pos'			=> $i++,
					'type'			=> $row[$fType],
					'default'		=> $row[$fDefault],
					'required'		=> ($row[$fNull] == 'NO'),
					'length'		=> $length,
					'precision'		=> $precision,
					'unsigned'		=> $unsigned,
					'primary'		=> $primary,
					'primaryPos'	=> $primaryPos,
					'identity'		=> $identity,
					'unique'		=> $unique,
					'text'			=> $text,
					'auto'			=> $auto,
					'htmlOut'		=> $htmlOut,
					'comment'		=> $comment,
					'rawComment'	=> $row[$fComment],
				);
			}
			$cache->save();
		}
		return $ret;
	}

}

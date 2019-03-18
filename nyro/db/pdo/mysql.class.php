<?php
/**
 * @author Cédric Nirousset <cedric@nyrodev.com>
 *
 * @version 0.2
 */
/**
 * Interface for db classes.
 */
class db_pdo_mysql extends db_pdo_abstract
{
    /**
     * Cached tables of the DB.
     *
     * @var array
     */
    protected $tables;

    /**
     * Returns a list of the tables in the database.
     *
     * @param bool $unPrefix Indicate if the table name shold remove paramettred prefix
     *
     * @return array
     */
    public function getTables($unPrefix = true)
    {
        $cache = $this->getCache();
        if (!$cache->get($this->tables, array('id' => 'tables'))) {
            if (is_null($this->tables)) {
                $this->tables = array();
                $stmt = $this->query('SHOW TABLES');
                $this->tables['raw'] = $stmt->fetchAll(db::FETCH_COLUMN);
                $this->tables['unprefix'] = array();
                foreach ($this->tables['raw'] as $r) {
                    $this->tables['unprefix'][$r] = $this->cfg->prefix ? str_replace($this->cfg->prefix, '', $r) : $r;
                }
            }
            $cache->save();
        }

        return $this->tables[$unPrefix ? 'raw' : 'unprefix'];
    }

    /**
     * Add a prefix that was previsouly removed for a table name.
     *
     * @param string $table Table name
     *
     * @return string Table name with it's prefix, if existing
     */
    public function prefixTable($table)
    {
        if ($this->cfg->prefix) {
            $tables = $this->getTables(false);
            $index = array_search($table, $tables);
            if ($index) {
                $table = $index;
            }
        }

        return $table;
    }

    /**
     * Returns the properties.
     *
     * @param string $table TableName
     *
     * @return array
     */
    public function fields($table)
    {
        $cache = $this->getCache();
        $ret = array();
        if (!$cache->get($ret, array('id' => 'fields-'.$table))) {
            $stmt = $this->query('SHOW FULL FIELDS FROM '.$this->quoteIdentifier($this->prefixTable($table)));

            $fField = 'Field';
            $fType = 'Type';
            $fNull = 'Null';
            $fKey = 'Key';
            $fDefault = 'Default';
            $fExtra = 'Extra';
            $fComment = 'Comment';

            $i = 0;
            $p = 0;
            while ($row = $stmt->fetch(db::FETCH_ASSOC)) {
                $length = null;
                $precision = null;
                $unsigned = preg_match('/unsigned/', $row[$fType]);
                $text = false;
                $auto = false;
                $htmlOut = true;
                $tmp = explode($this->cfg->sepCom, $row[$fComment]);
                $comment = array();
                $unique = false;
                foreach ($tmp as $t) {
                    $tt = explode($this->cfg->sepComVal, $t);
                    if (2 == count($tt)) {
                        $comment[$tt[0]] = $tt[1];
                    } else {
                        $comment[] = $t;
                    }
                }
                if (preg_match('/^(set|enum)\((.+)\)/', $row[$fType], $matches)) {
                    $row[$fType] = $matches[1];
                    $tmp = explode(',', $matches[2]);
                    array_walk($tmp, create_function('&$t', '$t = substr(substr($t, 0, strlen($t)-1), 1);'));
                    $precision = array();
                    foreach ($tmp as $v) {
                        $precision[$v] = $v;
                    }
                } elseif (preg_match('/^((?:var)?char)\((\d+)\)/', $row[$fType], $matches)) {
                    if ('_file' == substr($row[$fField], -5)) {
                        $row[$fType] = 'file';
                        $htmlOut = false;
                    } else {
                        $row[$fType] = $matches[1];
                        $length = $matches[2];
                        $text = true;
                    }
                } elseif (preg_match('/^(decimal|float|double|decimal|dec|numeric|fixed)\((\d+),(\d+)\)/', $row[$fType], $matches)) {
                    $row[$fType] = $matches[1];
                    $length = $matches[2];
                    $precision = $matches[3];
                } elseif (preg_match('/^((?:big|medium|small|tiny)?int)\((\d+)\)/', $row[$fType], $matches)) {
                    $row[$fType] = $matches[1];
                    $length = $matches[2];
                } elseif (preg_match('/^((?:tiny|medium|long)?(text|blob))/', $row[$fType], $matches)) {
                    $text = true;
                    $htmlOut = !in_array('richtext', $comment);
                }
                if ('PRI' == strtoupper($row[$fKey])) {
                    $primary = true;
                    $primaryPos = $p++;
                    $identity = ('auto_increment' == $row[$fExtra]);
                } elseif ('UNI' == strtoupper($row[$fKey])) {
                    $primary = false;
                    $primaryPos = null;
                    $identity = false;
                    $unique = true;
                } else {
                    $primary = false;
                    $primaryPos = null;
                    $identity = false;
                }
                if ('CURRENT_TIMESTAMP' == strtoupper($row[$fDefault]) || 'CURRENT_TIMESTAMP()' == strtoupper($row[$fDefault])) {
                    $cf = create_function('', 'return time();');
                    $row[$fDefault] = $cf();
                    $auto = true;
                }
                $ret[$row[$fField]] = array(
                    'name' => $row[$fField],
                    'pos' => $i++,
                    'type' => $row[$fType],
                    'default' => $row[$fDefault],
                    'required' => ('NO' == $row[$fNull]),
                    'length' => $length,
                    'precision' => $precision,
                    'unsigned' => $unsigned,
                    'primary' => $primary,
                    'primaryPos' => $primaryPos,
                    'identity' => $identity,
                    'unique' => $unique,
                    'text' => $text,
                    'auto' => $auto,
                    'htmlOut' => $htmlOut,
                    'comment' => $comment,
                    'rawComment' => $row[$fComment],
                );
            }
            $cache->save();
        }

        return $ret;
    }
}

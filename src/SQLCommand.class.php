<?php

/**
 * SQLCommand.class.php
 *
 * (c) Andreas Mueller <webmaster@am-wd.de>
 */

namespace AMWD\SQL;
require_once __DIR__.'/SQLDataReader.class.php';

/**
 * Represents a SQL Statement/Command to execute with a DataReader
 *
 * @package    SQL
 * @author     Andreas Mueller <webmaster@am-wd.de>
 * @copyright  (c) 2015 Andreas Mueller
 * @license    MIT - http://am-wd.de/index.php?p=about#license
 * @link       https://bitbucket.org/BlackyPanther/sql-class
 * @version    v1.2-20150828 | stable
 */
class SQLCommand {

	// --- properties
	// ===========================================================================

	/**
	 * object of database connection
	 * @var SQL
	 */
	private $conn;

	/**
	 * Query with placeholders
	 * @var string
	 */
	private $query;
	
	/**
	 * Query with replaced placeholders
	 * @var string
	 */
	private $queryParsed;
	
	/**
	 * associative array with parameter names and their values
	 * @var mixed[]
	 */
	private $params;
	
	/**
	 * associative array with parameter names and thier datatype
	 * @var string[]
	 */
	private $paramTypes;

	// --- basic stuff
	// ===========================================================================

		/**
	 * initialize new base object with property array
	 *
	 * @return SQL
	 */
	function __construct($query, $conn) {
		$this->query = trim($query);
		$this->queryParsed = '';
		$this->conn = $conn;

		$this->params = array();
		$this->paramTypes = array();
	}

	/**
	 * do your rest before object is destroyed
	 * @return void
	 */
	function __destruct() {
		// nothing to do yet
	}

	// --- adding parameter
	// ===========================================================================
	/**
	 * add a parameter
	 * 
	 * names of parameters have to start with an @ in the query
	 * e.g. INSERT INTO table (id, name) VALUES(@obj_id, @obj_name);
	 * 
	 * The function call will be:
	 * $cmd->add_parameter('obj_id', 1);
	 * $cmd->add_parameter('obj_name', 'Test');
	 * 
	 * arrays and objects will saved json encoded.
	 * except for DateTime, this will be parsed and stored correctly.
	 * 
	 * @param string $name name of parameter
	 * @param mixed $value value of parameter
	 * 
	 * @return void
	 */
	public function add_parameter($name, $value) {
		// i = integer, d = double, s = string, b = blob
		$type = gettype($value);
		
		switch ($type) {
			case 'boolean':
			case 'integer':
				$this->paramTypes[$name] = 'i';
				$this->params[$name] = intval($value);
				break;
			case 'double':
			case 'float':
				$this->paramTypes[$name] = 'd';
				$this->params[$name] = floatval($value);
				break;
			case 'string':
				$this->paramTypes[$name] = 's';
				$this->params[$name] = "'".str_replace("'", "\'", strval($value))."'";
				break;
			case 'array':
				$this->add_parameter($name, strval(json_encode($value)));
				break;
			case 'object':
				if ($value instanceof \DateTime) {
					$this->add_parameter($name, strval($value->format('Y-m-d H:i:s')));
				} else {
					$this->add_parameter($name, strval(json_encode($value)));
				}
				break;
			case 'NULL':
				$this->paramTypes[$name] = 'b';
				$this->params[$name] = 'NULL';
				break;
			default:
				// ignore
		}
		
		$this->queryParsed = '';
	}

	// --- removing parameter
	// ===========================================================================
	/**
	 * delete perviously added parameter
	 * 
	 * @param string $name name of parameter
	 * @return bool
	 */
	public function delete_parameter($name) {
		$this->queryParsed = '';
		
		if (array_key_exists($name, $this->params)) {
			unset($this->params[$name]);
			unset($this->paramTypes[$name]);
			
			return !isset($this->params[$name]);
		}
		
		$trace = debug_backtrace();
		trigger_error('Undefined key for delete_parameter(): '
									.$name.' in '
									.$trace[0]['file'].' at row '
									.$trace[0]['line']
				, E_USER_NOTICE);
		
		return false;
	}

	/**
	 * clear all parameters
	 * @return void
	 */
	public function clear_parameters() {
		$this->params = array();
		$this->paramTypes = array();
		$this->queryParsed = '';
	}

	/**
	 * execute query without response
	 *
	 * @return bool
	 */
	public function execute_non_query() {
		$this->parse_query();
		if (empty($this->queryParsed))
			return true;
		
		$close = $this->conn->status() == 'closed';
		
		$this->conn->open();
		$this->conn->query($this->query);
		
		$ret = empty($this->conn->error());
		
		if ($close)
			$this->conn->close();

		return $ret;
	}

	/**
	 * execute query and return all data in a reader
	 * 
	 * @return SQLDataReader
	 */
	public function execute_reader() {
		$this->parse_query();
		if (empty($this->queryParsed))
			return new SQLDataReader();
		
		$close = $this->conn->status() == 'closed';
		
		$this->conn->open();
		$res = $this->conn->query($this->query);
		
		$data = array();
		while ($row = $this->conn->fetch_array($res)) {
			$data[] = $row;
		}
		
		$ret = empty($this->conn->error());
		
		if ($close)
			$this->conn->close();

		return $ret ? new SQLDataReader($data) : new SQLDataReader();
	}

	// --- helpers
	// ===========================================================================
	
	/**
	 * parse query and replace placeholders
	 * 
	 * @return void
	 */
	private function parse_query() {
		if (empty($this->query))
			return;

		foreach ($this->params as $key => $value) {
			$this->queryParsed = str_replace('@'.$key, $value, $this->query);
		}
	}

}

?>
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
 * @copyright  (c) 2015-2016 Andreas Mueller
 * @license    MIT - http://am-wd.de/index.php?p=about#license
 * @link       https://bitbucket.org/BlackyPanther/sql-class
 * @version    v1.1-20160309 | stable
 */
class SQLCommand
{

	// --- properties
	// ===========================================================================

	/**
	 * The database connection.
	 * @var SQL
	 */
	private $conn;

	/**
	 * The statement to handle with.
	 * @var \PDOStatement
	 */
	private $statement;

	/**
	 * The parameters to bind to the statement.
	 * @var mixed[]
	 */
	private $params;

	/**
	 * The datatypes of the parameters to bind to the statement.
	 * @var int[]
	 */
	private $paramTypes;

	// --- basic stuff
	// ===========================================================================

	/**
	 * Initializes a new instance of SQLCommand.
	 *
	 * If you want to bind parameters, make sure the parameters look like
	 * in this example statement:
	 * "INSERT INTO table (lastname, firstname, birthday) VALUES(:last_name, :first_name, :birth_day);"
	 *
	 * @param string $query
	 *   The query to execute.
	 * @param SQL $conn
	 *   The Sql connection.
	 */
	function __construct($query, $conn)
	{
		$query = trim($query);

		$this->conn       = $conn;
		$this->statement  = $conn->prepare($query);
		$this->params     = array();
		$this->paramTypes = array();
	}

	// --- adding parameter
	// ===========================================================================

	/**
	 * Adds a parameter to the internal list.
	 *
	 * The name of the parameter is set _without_ the colon at the beginning.
	 * Example to the statement at the constructor:
	 * add_parameter('last_name', 'Smith');
	 * add_parameter('first_name', 'John');
	 *
	 * A DateTime object is parsed to yyyy-mm-dd hh:mm:ss.
	 * All other arrays and objects are encoded via json_encode() to a string.
	 * The methods SQLDataReader::get_* decode the string correctly.
	 *
	 * @param string $name
	 *   The name of the parameter.
	 * @param mixed $value
	 *   The value of the parameter.
	 *
	 * @return void
	 */
	public function add_parameter($name, $value)
	{
		switch (gettype($value))
		{
			case 'boolean':
				$this->paramTypes[$name] = \PDO::PARAM_BOOL;
				$this->params[$name] = boolval($value);
				break;
			case 'NULL':
				// in some cases NULL breaks => PARAM_INT will succeed
				$this->paramTypes[$name] = \PDO::PARAM_NULL;
				//$this->paramTypes[$name] = \PDO::PARAM_INT;
				$this->params[$name] = null;
				break;
			case 'integer':
				$this->paramTypes[$name] = \PDO::PARAM_INT;
				$this->params[$name] = intval($value);
				break;
			case 'double':
			case 'float':
				$this->paramTypes[$name] = \PDO::PARAM_STR;
				$this->params[$name] = floatval($value);
				break;
			case 'string':
				$this->paramTypes[$name] = \PDO::PARA;_STR;
				//$this->params[$name] = $this->conn->escape(strval($value));
				$this->params[$name] = strval($value);
				break;
			case 'array':
				$this->add_parameter($name, strval(json_encode($value)));
				break;
			case 'object':
				if ($value instanceof \DateTime)
					$this->add_parameter($name, strval($value->format('Y-m-d H:i:s')));
				else
					$this->add_parameter($name, strval(json_encode($value)));
				break;
			default:
				// ignore
		}
	}

	// --- removing parameter
	// ===========================================================================

	/**
	 * Deletes a previously added parameter from the list.
	 *
	 * @param string $name
	 *   The name of the parameter.
	 *
	 * @return bool
	 *   true on success otherwise false and a notice is triggered.
	 */
	public function delete_parameter($name)
	{
		if (array_key_exists($name, $this->params))
		{
			unset($this->params[$name]);
			unset($this->paramTypes[$name]);

			return !isset($this->params[$name]);
		}

		$trace = debug_backtrace();
		trigger_error('Undefined key for delete_parameter(): '
		              .$name.' in '
		              .$trace[0]['file'].' at line '
		              .$trace[0]['line']
			, E_USER_NOTICE);

		return false;
	}

	/**
	 * Clears all parameters previously set.
	 *
	 * @return void
	 */
	public function clear_parameters()
	{
		$this->params = array();
		$this->paramTypes = array();
	}

	/**
	 * Executes the statement with bound parameters.
	 *
	 * @return bool
	 *   true on a successful execution, otherwise false.
	 */
	public function execute_non_query()
	{
		$this->bind_params();

		$close = $this->conn->status() == 'closed';
		$this->conn->open();

		$ret = $this->statement->execute();

		if ($close)
			$this->conn->close();

		return $ret;
	}

	/**
	 * Executes the statement with bound parameters for a SQLDataReader.
	 *
	 * @return SQLDataReader|bool
	 *   On success a SQLDataReader is returned otherwise false.
	 */
	public function execute_reader()
	{
		$this->bind_params();

		$close = $this->conn->status() == 'closed';
		$this->conn->open();

		if (!$this->statement->execute())
			return false;

		return new SQLDataReader($this->statement->fetchAll(\PDO::FETCH_ASSOC));
	}

	// --- helpers
	// ===========================================================================

	/**
	 * Binds all proviously set parameters to the statement.
	 *
	 * @return void
	 */
	private function bind_params()
	{
		if (count($this->params) != count($this->paramTypes))
			throw new \ArgumentException("Something went wrong while adding parameters");

		foreach ($this->params as $key => $value)
			$this->statement->bindValue(':'.$key, $value, $this->paramTypes[$key]);
	}

}

?>

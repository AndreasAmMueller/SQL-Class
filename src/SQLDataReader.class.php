<?php

/**
 * SQLDataReader.class.php
 *
 * (c) Andreas Mueller <webmaster@am-wd.de>
 */

namespace AMWD\SQL;

/**
 * Represents a DataReader for an executed SQLCommand
 *
 * @package    SQL
 * @author     Andreas Mueller <webmaster@am-wd.de>
 * @copyright  (c) 2015-2016 Andreas Mueller
 * @license    MIT - http://am-wd.de/index.php?p=about#license
 * @link       https://bitbucket.org/BlackyPanther/sql-class
 * @version    v1.0-20160309 | stable
 */
class SQLDataReader
{

	/**
	 * An Array with all fetched data.
	 * @var mixed[]
	 */
	private $data;

	/**
	 * The current position of the DataReader.
	 * @var int
	 */
	private $pos;

	/**
	 * Initializes a new instance of SQLDataReader.
	 *
	 * @param mixed[] $data
	 *   An array with the fetched data from an executed statement.
	 */
	function __construct($data = null)
	{
		$this->data = $data;
		$this->pos = -1;
	}

	/**
	 * Performs a iterator step.
	 *
	 * @return bool
	 *   true if new data have been loaded, otherwise false (end of data).
	 */
	public function read()
	{
		if ($this->data == null)
			return false;

		$this->pos++;
		return $this->pos < count($this->data);
	}

	/**
	 * Gets the value of a key. of the current data record.
	 *
	 * If the name of the key is null (not set), the whole record (assoc. array) is returned.
	 *
	 * @param string $name
	 *   The name of the key.
	 *
	 * @return mixed
	 *   The value of the key or null (not found).
	 *
	 * @throws \OutOfBoundsException
	 *   If the parameter is requested without a valid read_data() action.
	 */
	public function get($name = null)
	{
		if ($this->pos == -1)
		{
			$trace = debug_backtrace();
			trigger_error('Reader not executed at SQLDataReader::get(): '
			              .$name.' in '
			              .$trace[0]['file'].' at line '
			              .$trace[0]['line']
				, E_USER_ERROR);

			return null;
		}
		else if ($this->pos < count($this->data))
		{
			if ($name == null)
				return $this->data[$this->pos];
			else if (array_key_exists($name, $this->data[$this->pos]))
				return $this->data[$this->pos][$name];

			$trace = debug_backtrace();
			trigger_error('Undefined key for SQLDataReader::get(): '
			              .$name.' in '
			              .$trace[0]['file'].' at line '
			              .$trace[0]['line']
				, E_USER_WARNING);

			return null;
		}
		else
		{
			throw new \OutOfBoundsException("No data available");
		}
	}

	/**
	 * Gets the key of the current record as DateTime object.
	 *
	 * @param string $name
	 *   The name of the paramerter.
	 *
	 * @return \DateTime
	 */
	public function get_DateTime($name) {
		$value = $this->get($name);

		if ($value != null)
		{
			$date = date_parse($value);

			if ($date['error_count'] > 0 || $date['warning_count'] > 0)
			{
				$trace = debug_backtrace();
				trigger_error('Parsing Error on SQLDataReader::get_DateTime(): '
				              .$name.' in '
				              .$trace[0]['file'].' at row '
				              .$trace[0]['line']
					, E_USER_WARNING);

				return null;
			}

			$format = array();
			$format[] = $date['year'];
			$format[] = '-';
			$format[] = (($date['month'] < 10) ? '0' : '').$date['month'];
			$format[] = '-';
			$format[] = (($date['day'] < 10) ? '0' : '').$date['day'];
			$format[] = ' ';
			$format[] = (($date['hour'] < 10) ? '0' : '').$date['hour'];
			$format[] = ':';
			$format[] = (($date['minute'] < 10) ? '0' : '').$date['minute'];
			$format[] = ':';
			$format[] = (($date['second'] < 10) ? '0' : '').$date['second'];

			$str = implode('', $format);
			$value = \DateTime::createFromFormat('Y-m-d H:i:s', $str);
		}

		return $value;
	}

	/**
	 * Gets the parameter of current record as string.
	 *
	 * @param string $name
	 *   The name of the parameter.
	 *
	 * @return string
	 */
	public function get_String($name)
	{
		return strval($this->get($name));
	}

	/**
	 * Gets the parameter of the current record as integer.
	 *
	 * @param string $name
	 *   The name of the parameter.
	 *
	 * @return integer
	 */
	public function get_Integer($name)
	{
		return intval($this->get($name));
	}

	/**
	 * Gets the parameter of the current record as integer.
	 *
	 * @param string $name
	 *   The name of the parameter.
	 *
	 * @return integer
	 */
	public function get_Int($name)
	{
		return $this->get_Integer($name);
	}

	/**
	 * Gets the parameter of the current record as float.
	 *
	 * @param string $name
	 *   The name of the parameter.
	 *
	 * @return float
	 */
	public function get_Float($name)
	{
		return floatval($this->get($name));
	}

	/**
	 * Gets the parameter of the current record as float.
	 *
	 * The data type double is not present at Php.
	 *
	 * @param string $name
	 *   The name of the parameter.
	 *
	 * @return float
	 */
	public function get_Double($name)
	{
		return $this->get_Float($name);
	}

	/**
	 * Gets the parameter of the current record as boolean.
	 *
	 * @param string $name
	 *   The name of the parameter.
	 *
	 * @return bool
	 */
	public function get_Boolean($name)
	{
		return $this->get_Int($name) > 0;
	}

	/**
	 * Gets the parameter of the current record as array.
	 *
	 * @param string $name
	 *   The name of the parameter.
	 *
	 * @return array
	 */
	public function get_Array($name)
	{
		return json_decode($this->get_String($name));
	}

	/**
	 * Gets the parameter of the current record as object.
	 *
	 * @param string $name
	 *   The name of the parameter.
	 *
	 * @return object
	 */
	public function get_Object($name)
	{
		return json_decode($this->get_String($name));
	}

	/**
	 * Gets the current record as associative array.
	 *
	 * @return mixed[]
	 */
	public function get_sql_array()
	{
		return $this->get();
	}

	/**
	 * Gets the current record as object.
	 *
	 * @return object
	 */
	public function get_sql_object()
	{
		$obj = new stdClass();

		foreach ($this->get() as $key => $value)
			$obj->$key = $value;

		return $obj;
	}
}

?>
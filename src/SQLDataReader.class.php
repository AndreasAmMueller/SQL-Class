<?php

/**
 * SQLDataReader.class.php
 *
 * (c) Andreas Mueller <webmaster@am-wd.de>
 */

namespace AMWD\SQL;

/**
 * Represents a DataReader to perform a SQL Request
 *
 * @package    SQL
 * @author     Andreas Mueller <webmaster@am-wd.de>
 * @copyright  (c) 2015 Andreas Mueller
 * @license    MIT - http://am-wd.de/index.php?p=about#license
 * @link       https://bitbucket.org/BlackyPanther/sql-class
 * @version    v1.2-20150828 | stable
 */
class SQLDataReader {

	/**
	 * array with all data
	 * @var mixed[]
	 */
	private $data;
	
	/**
	 * current position of DataReader
	 * @var int
	 */
	private $pos;
	
	/**
	 * initalize new instance of DataReader
	 * @return DataReader
	 */
	function __construct($data = null) {
		$this->data = $data;
		$this->pos = -1;
	}
	
	/**
	 * performs a iterator step
	 * 
	 * returns true if reader has new data loaded, else false (end of data)
	 * 
	 * @return bool
	 */
	public function read() {
		if ($this->data == null)
			return false;
		
		$this->pos++;
		return $this->pos < count($this->data);
	}
	
	/**
	 * get parameter of current data block
	 * 
	 * @param string $name name of parameter
	 * 
	 * @return mixed
	 * @throws \OutOfBoundsException if parameter is requested without valid read_data()
	 */
	public function get($name) {
		if ($this->pos == -1) {
			$trace = debug_backtrace();
			trigger_error('Reader not executed at DataReader.get(): '
										.$name.' in '
										.$trace[0]['file'].' at row '
										.$trace[0]['line']
					, E_USER_ERROR);
			
			return null;
		} else if ($this->pos < count($this->data)) {
			if (array_key_exists($name, $this->data[$this->pos])) {
				return $this->data[$this->pos][$name];
			}
			
			$trace = debug_backtrace();
			trigger_error('Undefined key for DataReader.get(): '
										.$name.' in '
										.$trace[0]['file'].' at row '
										.$trace[0]['line']
					, E_USER_WARNING);
			
			return null;
		} else {
			throw new \OutOfBoundsException("No data available");
		}
	}
	
	/**
	 * get paramerter of current data block as DateTime
	 * 
	 * @param string $name name of paramerter
	 * 
	 * @return \DateTime
	 */
	public function get_DateTime($name) {
		$value = $this->get($name);
		
		if ($value != null) {
			$date = date_parse($value);
			
			if ($date['error_count'] > 0 || $date['warning_count'] > 0) {
				$trace = debug_backtrace();
				trigger_error('Parsing Error on DataReader.get_DateTime(): '
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
	 * get parameter of current data block as string.
	 * 
	 * @param string $name name of parameter
	 * 
	 * @return string
	 */
	public function get_String($name) {
		return strval($this->get($name));
	}
	
	/**
	 * get parameter of current data block as integer.
	 * 
	 * @param string $name name of parameter
	 * 
	 * @return integer
	 */
	public function get_Integer($name) {
		return intval($this->get($name));
	}
	
	/**
	 * get parameter of current data block as float.
	 * 
	 * @param string $name name of parameter
	 * 
	 * @return float
	 */
	public function get_Float($name) {
		return floatval($this->get($name));
	}
	
	/**
	 * get parameter of current data block as double.
	 * 
	 * @param string $name name of parameter
	 * 
	 * @return double
	 */
	public function get_Double($name) {
		return $this->get_Float($name);
	}
	
	/**
	 * get parameter of current data block as boolean.
	 * 
	 * @param string $name name of parameter
	 * 
	 * @return boolean
	 */
	public function get_Boolean($name) {
		return $this->get_Int($name) > 0;
	}
	
	/**
	 * get parameter of current data block as array.
	 * 
	 * @param string $name name of parameter
	 * 
	 * @return mixed[]
	 */
	public function get_Array($name) {
		return json_decode($this->get_String($name));
	}
	
	/**
	 * get parameter of current data block as object (what ever its content is).
	 * 
	 * @param string $name name of parameter
	 * 
	 * @return mixed
	 */
	public function get_Object($name) {
		return json_decode($this->get_String($name));
	}
}

?>
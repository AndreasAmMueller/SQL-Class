<?php

/**
 * SQLTest.php
 * 
 * (c) Andreas Mueller <webmaster@am-wd.de>
 */
namespace AMWD\SQL;
require_once __DIR__.'/../src/SQL.class.php';

/**
 * Basic tests for SQL.class.php
 *
 * @package    SQL
 * @author     Andreas Mueller <webmaster@am-wd.de>
 * @copyright  (c) 2015 Andreas Mueller
 * @license    MIT - http://am-wd.de/index.php?p=about#license
 * @link       https://bitbucket.org/BlackyPanther/sql-class
 * @version    v1.0-20150829 | in developement
 */
class SQLiteTest extends \PHPUnit_Framework_TestCase {
	
	public function testConstructor() {
		@unlink(__DIR__.'/test.sqlite');
		$this->assertFalse(file_exists(__DIR__.'/test.sqlite'), "File still exists.");
		
		$sql = new SQLite(__DIR__.'/test.sqlite');
		$sql->open();
		$sql->close();
		$this->assertTrue(file_exists(__DIR__.'/test.sqlite'), "File not created.");
	}
	
	public function testCreateTable() {
		$this->assertTrue(true);
	}
}


?>
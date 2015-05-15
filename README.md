# SQL Class

-----

With this class I'll try to provide a basic compatibillity between MySQL and SQLite3.

If your programming for an WebApplication or simple Website (e.g. CMS), you may have problems with your customers Webspace (e.g. no MySQL Server available).   
So you can use this class programming the App and while shipping you can set the correct Database type in a config.

## Requirements
- PHP 5.5 or later
- correct SQL Syntax; (`[...] LIMIT 0, 10;` is common MySQL syntax)

## Notice
Dumps of SQLite and MySQL are different! So be careful with commands in your Dump file.

## Example
```php
<?php
// load Class
require_once __DIR__."/sql.class.php";

// Create my Object, here I want a MySQL Connection
// We will use our static Function to create one
// User:     TestUser
// Password: fromErlangenToMunich
// Database: exampleBase
// Port:     3307
// Host:     123.321.123.321
$sql = SQL::MySQL("TestUser", "fromErlangenToMunich", "exampleBase", 3307, "123.321.123.321");

// Tell MySQL to use german locales
$sql->setLocales("de_DE");

// open the connection
$sql->open();

// Example Query
// I just want to get all information from all users with firstname George
$res = $sql->query("SELECT * FROM users WHERE firstname = 'George'");

// tell me the number of results (lines)
$count = $sql->num_rows($res);

// print out all results
echo "We have $count Results to Present".PHP_EOL;
echo "---------------------------------".PHP_EOL;
while ($row = $sql->fetch_object($res)) {
	echo $row->firstname . " " . $row->lastname.PHP_EOL;
}

// close connection
$sql->close();

?>
```

## Bugs / Issues
Please report bugs to [Bitbucket | Issues](https://bitbucket.org/BlackyPanther/sql-class/issues)

-----

### LICENSE
My scripts are published under [MIT License](https://am-wd.de/?p=about#license).

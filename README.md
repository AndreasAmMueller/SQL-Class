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
// Load classes
require_once __DIR__.'/src/SQL.class.php';
require_once __DIR__.'/src/SQLCommand.class.php';

// Set timezone for cli
date_default_timezone_set('Europe/Berlin');

// Set credentials for MySQL
$user     = 'root';
$password = 'root';
$database = 'test';
$host     = '127.0.0.1';
$port     = 3306;

// Create new connection
$sql = new AMWD\SQL\MySQL($user, $password, $database, $port, $host);
// Define locals for e.g. DateTime
$sql->locales = 'de_DE';

// Perform a request with parameter
$query = "SELECT
	movie_id    AS id,
	movie_title AS title,
	movie_year  AS year,
	genre_name  AS genre
FROM
	movies
JOIN
	genres ON movie_genre = genre_id
WHERE
	movie_year = @year
;";

// Create command
$cmd = new AMWD\SQL\SQLCommand($query, $sql);
// Add parameter
$cmd->add_parameter('year', 2012);

// Execute command -> returns SQLDataReader
$dr = $cmd->execute_reader();

// Read all data with this reader
while ($dr->read()) {
	// and return them in a clean way
	echo '#'.$dr->get_Integer('id').' | '.$dr->get_String('title').' ('.$dr->get_Int('year').') - '.$dr->get_String('genre').PHP_EOL;
}

?>
```

## Bugs / Issues
Please report bugs to [Bitbucket | Issues](https://bitbucket.org/BlackyPanther/sql-class/issues)

-----

### LICENSE
My scripts are published under [MIT License](https://am-wd.de/?p=about#license).

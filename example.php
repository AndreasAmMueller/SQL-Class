<?php

date_default_timezone_set('Europe/Berlin');

require_once __DIR__.'/src/SQL.class.php';
require_once __DIR__.'/src/SQLCommand.class.php';

$user     = '';
$password = '';
$database = '';
$host     = 'localhost';
$port     = 3306;

$sql = new AMWD\SQL\MySQL($user, $password, $database, $port, $host);
$sql->locales = 'de_DE';

$query = "SELECT
	movie_id    AS id,
	movie_title AS title,
	movie_year  AS year,
	genre_name  AS genre
FROM
	movies
JOIN
	genres ON movie_genre = genre_id
;";

$cmd = new AMWD\SQL\SQLCommand($query, $sql);

$dr = $cmd->execute_reader();

while ($dr->read()) {
	echo '#'.$dr->get_Integer('id').' | '.$dr->get_String('title').' ('.$dr->get_Int('year').') - '.$dr->get_String('genre').PHP_EOL;
}

echo PHP_EOL;

$dump = $sql->get_dump();
echo $dump;

echo PHP_EOL;
echo 'Restore Success: '.$sql->restore_dump($dump, true, true);
echo PHP_EOL;
echo PHP_EOL;

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

$cmd = new AMWD\SQL\SQLCommand($query, $sql);
$cmd->add_parameter('year', 2012);

$dr = $cmd->execute_reader();
while ($dr->read()) {
	echo '#'.$dr->get_Integer('id').' | '.$dr->get_String('title').' ('.$dr->get_Int('year').') - '.$dr->get_String('genre').PHP_EOL;
}

echo PHP_EOL;

?>
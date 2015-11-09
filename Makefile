.PHONY: all docs test-mysql test-sqlite

all: test-mysql test-sqlite

test-mysql:
	php tools/phpunit.phar --verbose tests/MySQLTest.php

test-sqlite:
	php tools/phpunit.phar --verbose tests/SQLiteTest.php

docs:
	php tools/phpDocumentor.phar -p -d src/ -t docs/ --template="clean"

CREATE TABLE genres (
	genre_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	genre_name VARCHAR(32) NOT NULL,
	PRIMARY KEY(genre_id),
	UNIQUE KEY genre_name_uq (genre_name)
);

CREATE TABLE movies (
	movie_id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
	movie_title VARCHAR(50) NOT NULL,
	movie_year INT(4),
	movie_genre INT(11) UNSIGNED,
	PRIMARY KEY(movie_id),
	CONSTRAINT movie_genre_fk FOREIGN KEY(movie_genre) REFERENCES genres(genre_id) ON UPDATE CASCADE ON DELETE SET NULL
);

INSERT INTO genres VALUES (1, 'Action');
INSERT INTO genres VALUES (2, 'Abenteuer');
INSERT INTO genres VALUES (3, 'Animation');
INSERT INTO genres VALUES (4, 'Komödie');
INSERT INTO genres VALUES (5, 'Krimi');

INSERT INTO movies VALUES (1, 'Dark Shadows', 2012, 4);
INSERT INTO movies VALUES (2, '7 Sekunden', 2005, 1);
INSERT INTO movies VALUES (3, 'Himmel und Huhn', 2005, 3);
INSERT INTO movies VALUES (4, 'Honig im Kopf', 2014, 4);
INSERT INTO movies VALUES (5, 'Act of Valor', 2012, 1);
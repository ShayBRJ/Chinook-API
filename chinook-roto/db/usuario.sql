-- Usuario de la API, solo lectura
CREATE USER 'api'@'%' IDENTIFIED BY 'api123';
GRANT SELECT ON Chinook_AutoIncrement.* TO 'api'@'%';

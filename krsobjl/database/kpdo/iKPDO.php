<?php
interface iKPDO extends iDriverDB {
	
	const FETCH_NUM = "FETCH_NUM";
	const FETCH_ASSOC = "FETCH_ASSOC";
	const FETCH_BOTH = "FETCH_BOTH";
	const FETCH_OBJECT = "FETCH_OBJECT";
	const FETCH_COLUMN = "FETCH_COLUMN";
	const FETCH_UNIQUE = "FETCH_UNIQUE";
	const FETCH_GROUP = "FETCH_GROUP";
	const FETCH_CLASS = "FETCH_CLASS";
	const FETCH_FUNC = "FETCH_FUNC";
	
	const TYPE_SMALLINT = "SMALLINT";
	const TYPE_INTEGER = "INTEGER";
	const TYPE_BIGINT = "BIGINT";
	const TYPE_REAL = "REAL";
	const TYPE_FLOAT = "FLOAT";
	const TYPE_DOUBLE = "DOUBLE";
	const TYPE_DECIMAL = "DECIMAL";
	const TYPE_CHAR = "CHAR";
	const TYPE_VARCHAR = "VARCHAR";
	const TYPE_BLOB = "BLOB";
	const TYPE_CLOB = "CLOB";
	const TYPE_DATE = "DATE";
	const TYPE_TIME = "TIME";
	const TYPE_DATETIME = "DATETIME";
	const TYPE_TIMESTAMP = "TIMESTAMP";
	const TYPE_BOOLEAN = "BOOLEAN";
	const TYPE_TEXT = "TEXT";
	
	const NOT_NULL = 1;
	const AUTO_INCREMENT = 2;
	const PRIMARY_KEY = 4;
	const UNIQUE = 8;
	const INDEX = 16;
	
}
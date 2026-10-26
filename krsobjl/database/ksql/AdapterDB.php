<?php
class AdapterDB
{  
	static function createIstance($type) {
		switch (strtoupper($type)) {
			case "MYSQL":
				return new MysqlKsql();
		}
	}
}
?>

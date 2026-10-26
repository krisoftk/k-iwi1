<?php
class UtilityConfiguration {
	static private $parse = NULL;
	
	static function getConfiguration() {
		if(self::$parse == null) {   
       		$ini = DIR_CONFIG . "config.ini";	
       		self::$parse = @parse_ini_file ( $ini , false ) or die("Errore nel config.ini");
      	}
      	return self::$parse;
	}
}
?>

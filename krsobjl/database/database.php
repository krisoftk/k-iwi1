<?php
require_once 'utility_db.php';

/*
 * KSQL LIBRARY
 */
require_once 'ksql/AdapterDB.php';
require_once 'ksql/iKsql.php';
require_once 'ksql/Ksql.php';
require_once 'ksql/imp/MysqlKsql.php';

/*
 * KPDO LIBRARY
 */
require_once 'kpdo/iDriverDB.php';
require_once 'kpdo/iStatement.php';
require_once 'kpdo/iKPDO.php';
require_once 'kpdo/KPDO.php';
require_once 'kpdo/KPODException.php';
require_once 'kpdo/imp/MysqlDriver.php';
require_once 'kpdo/imp/MysqlStatement.php';
require_once 'kpdo/imp/ODBCDriver.php';
require_once 'kpdo/imp/ODBCStatement.php';
?>
<?php
/* LIBRARY KIMPORT */
require_once 'kimport/iImport.php';
require_once 'kimport/KImport.php';

/* LIBRARY KDOWNLOAD */
require_once 'kdownload/iDownload.php';
require_once 'kdownload/KDownload.php';

/* LIBRARY KREADER AND KWRITER */
// KReader depends on the third-party PHPExcel library (class
// PHPExcel_Reader_IReadFilter), which isn't included in this distribution:
// the require is disabled so it doesn't stop the whole framework from
// starting. To enable it again, add PHPExcel and uncomment the line below.
// require_once 'kreader/KReader.php';
require_once 'kwriter/KWriter.php';

/* LIBRARY KREPORT */
require_once 'kreport/KReport.php';

/* HELPER */
require_once 'kout.php';
require_once 'utf8.php';
require_once 'pagination.php';

/* LIBRARY KPARSER */
require_once 'kparser/KParser.php';
?>

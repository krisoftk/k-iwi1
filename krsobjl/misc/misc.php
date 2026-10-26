<?php
/* LIBRARY KIMPORT */
require_once 'kimport/iImport.php';
require_once 'kimport/KImport.php';

/* LIBRARY KDOWNLOAD */
require_once 'kdownload/iDownload.php';
require_once 'kdownload/KDownload.php';

/* LIBRARY KREADER AND KWRITER */
// KReader dipende dalla libreria di terze parti PHPExcel (classe
// PHPExcel_Reader_IReadFilter), che non e' inclusa in questa distribuzione:
// il require e' disattivato per non bloccare l'avvio dell'intero framework.
// Per riabilitarlo, aggiungere PHPExcel e poi de-commentare la riga sotto.
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

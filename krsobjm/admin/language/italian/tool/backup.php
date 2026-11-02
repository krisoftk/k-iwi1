<?php
// Heading
$_['heading_title']          = 'Backup / Ripristino';

// Text
$_['text_help']              = 'Backup del database del framework (database.* in config.ini) in un file .sql. I backup restano nella cartella backup/ del progetto, fuori dalla cartella pubblica: contengono tutto il database (utenti, hash delle password, dati personali), quindi scarica quelli che ti servono e cancella gli altri.';
$_['text_backups']           = 'Backup disponibili';
$_['text_no_backups']        = 'Nessun backup.';
$_['text_create']            = 'Nuovo backup';
$_['text_create_help']       = 'Scegli le tabelle da salvare. Le sessioni (krs_session) sono escluse di default: sono temporanee e contengono i dati di sessione di chi è collegato.';
$_['text_upload']            = 'Carica un backup';
$_['text_upload_help']       = 'Solo file creati da questa pagina e non modificati: il file viene controllato e poi mostrato in una pagina di conferma, non viene ripristinato subito.';
$_['text_upload_limit']      = 'Dimensione massima: %s (upload_max_filesize / post_max_size di PHP).';
$_['text_confirm_delete']    = 'Cancellare definitivamente questo backup?';
$_['text_kind_backup']       = 'Manuale';
$_['text_kind_prerestore']   = 'Automatico, prima di un ripristino';
$_['text_kind_upload']       = 'Caricato';
$_['text_created']           = 'Backup creato: %s';
$_['text_deleted']           = 'Backup %s cancellato.';
$_['text_restored']          = 'Ripristino di %s completato. Lo stato precedente del database è nel backup %s.';
$_['text_confirm_title']     = 'Conferma il ripristino';
$_['text_target']            = 'Il ripristino sovrascrive le tabelle elencate qui sotto nel database %s.';
$_['text_confirm_help']      = 'Prima di iniziare viene fatto in automatico un backup completo dello stato attuale. Se una tabella del backup contiene gli utenti, al termine valgono gli utenti e le password del backup.';
$_['text_other_database']    = 'Il backup viene dal database %s, quello attuale è %s.';
$_['text_file']              = 'File';
$_['text_created_on']        = 'Creato il';
$_['text_from_database']     = 'Database di origine';
$_['text_server']            = 'server';
$_['text_replaced']          = 'Tabelle sostituite';
$_['text_untouched']         = 'Tabelle che restano come sono';
$_['text_none']              = 'nessuna';
$_['text_agree']             = 'Ho capito che queste tabelle verranno sostituite con il contenuto del backup.';

// Column
$_['column_date']            = 'Data';
$_['column_kind']            = 'Tipo';
$_['column_tables']          = 'Tabelle';
$_['column_table']           = 'Tabella';
$_['column_rows']            = 'Righe (circa)';
$_['column_size']            = 'Dimensione';
$_['column_action']          = 'Azione';

// Button
$_['button_create']          = 'Crea il backup';
$_['button_upload']          = 'Carica';
$_['button_download']        = 'Scarica';
$_['button_restore']         = 'Ripristina';
$_['button_delete']          = 'Cancella';
$_['button_cancel']          = 'Annulla';

// Error
$_['error_driver']           = 'Backup e ripristino funzionano solo con MySQL/MariaDB (database.driver = "mysql").';
$_['error_writable']         = 'Il web server non può scrivere nella cartella %s: creala e rendila scrivibile per fare backup e ripristini.';
$_['error_no_tables']        = 'Scegli almeno una tabella.';
$_['error_create']           = 'Backup non riuscito: %s';
$_['error_safety']           = 'Il backup automatico dello stato attuale non è riuscito, quindi il ripristino non è partito: %s';
$_['error_restore']          = 'Il ripristino si è fermato alla riga %d: %s. Il database può essere rimasto a metà: ripristina il backup automatico %s per tornare allo stato precedente.';
$_['error_agree']            = 'Spunta la conferma per avviare il ripristino.';
$_['error_upload_missing']   = 'Scegli un file da caricare.';
$_['error_upload']           = 'Caricamento non riuscito.';
$_['error_upload_size']      = 'Il file supera la dimensione massima consentita (%s).';
$_['error_file_read']        = 'Il file non si può leggere.';
$_['error_file_format']      = 'Non è un backup di K-iwi.';
$_['error_file_integrity']   = 'Il file è incompleto o è stato modificato (il controllo SHA-256 non corrisponde).';
$_['error_file_statement']   = 'La riga %d contiene un\'istruzione che un backup di K-iwi non contiene.';
$_['error_file_store']       = 'Il file non si può salvare nella cartella dei backup.';
?>

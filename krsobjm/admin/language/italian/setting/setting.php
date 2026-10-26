<?php
// Heading
$_['heading_title']         = 'Impostazioni';

// Text
$_['text_success']          = 'Impostazioni salvate. La copia precedente di config.ini è tra i backup.';
$_['text_success_editor']   = 'config.ini salvato. La copia precedente è tra i backup.';
$_['text_readonly']         = 'Il web server non può scrivere %s (o la sua cartella): le impostazioni sono in sola lettura. Rendi il file e la cartella scrivibili per modificarle da qui.';
$_['text_editor_help']      = 'Il file config.ini così com\'è. Prima di salvare viene controllato che sia un file ini valido e, se cambiano i dati del database, che la connessione funzioni. La versione precedente finisce tra i backup.';
$_['text_backup_help']      = 'Prima di ogni salvataggio viene tenuta una copia di config.ini (le ultime 10). Aprila nell\'editor e salvala per ripristinarla.';
$_['text_no_backups']       = 'Nessun backup: verrà creato al primo salvataggio.';
$_['text_backup_loaded']    = 'Nell\'editor c\'è il backup %s, non ancora salvato: salva per ripristinarlo.';
$_['text_password_keep']    = 'Lascia vuoto per non cambiarla';
$_['text_default_value']    = 'Predefinito';

// Tab
$_['tab_database']          = 'Database';
$_['tab_framework']         = 'Aspetto';
$_['tab_setting']           = 'Sistema';
$_['tab_report']            = 'Report';
$_['tab_editor']            = 'Editor config.ini';
$_['tab_backup']            = 'Backup';

// Column
$_['column_backup']         = 'Backup del';
$_['column_size']           = 'Dimensione';
$_['column_action']         = 'Azioni';

// Entry
$_['entry_database_driver']               = 'Driver';
$_['entry_database_host']                 = 'Server';
$_['entry_database_port']                 = 'Porta';
$_['entry_database_user']                 = 'Utente';
$_['entry_database_password']             = 'Password';
$_['entry_database_name']                 = 'Nome del database';
$_['entry_framework_theme']               = 'Tema del sito';
$_['entry_framework_theme_admin']         = 'Tema dell\'admin';
$_['entry_framework_title_page']          = 'Titolo della pagina';
$_['entry_framework_title_application']   = 'Nome dell\'applicazione';
$_['entry_framework_author']              = 'Autore';
$_['entry_framework_copyright']           = 'Copyright';
$_['entry_framework_meta_description']    = 'Descrizione (meta)';
$_['entry_setting_use_ssl']               = 'Usa HTTPS';
$_['entry_setting_use_session_db']        = 'Sessioni nel database';
$_['entry_setting_error_display']         = 'Mostra gli errori PHP';
$_['entry_setting_error_log']             = 'Scrivi gli errori nel log';
$_['entry_setting_file_log']              = 'File di log';
$_['entry_setting_compression']           = 'Compressione';
$_['entry_setting_language']              = 'Lingua predefinita';
$_['entry_setting_timeout']               = 'Scadenza della sessione';
$_['entry_setting_decimals']              = 'Decimali';
$_['entry_setting_dec_point']             = 'Separatore decimale';
$_['entry_setting_thousands_sep']         = 'Separatore delle migliaia';
$_['entry_setting_login_max_attempts']    = 'Tentativi di login';
$_['entry_setting_login_lockout_minutes'] = 'Blocco dopo i tentativi';
$_['entry_setting_scheduler_ssl_verify']  = 'Scheduler: verifica il certificato';
$_['entry_report_time_expire']            = 'Validità dei link ai report';
$_['entry_report_base_url']               = 'Indirizzo di BIRT';

// Help
$_['help_database_port']                  = 'Vuota per la porta standard.';
$_['help_database_password']              = 'Non viene mai mostrata.';
$_['help_setting_use_session_db']         = 'Cambiandola tutti gli utenti, tu compreso, dovranno rifare il login.';
$_['help_setting_error_display']          = 'Solo su una macchina di sviluppo: mostra ai visitatori gli errori con i percorsi del server.';
$_['help_setting_file_log']               = 'Nome del file nella cartella dei log.';
$_['help_setting_compression']            = 'Livello gzip da 0 (spenta) a 9.';
$_['help_setting_language']               = 'Per chi non è loggato o non ha scelto una lingua.';
$_['help_setting_timeout']                = 'Secondi di inattività prima del logout.';
$_['help_setting_login_max_attempts']     = 'Password sbagliate (stesso utente e IP) prima del blocco. Predefinito: 5.';
$_['help_setting_login_lockout_minutes']  = 'Minuti di blocco. Predefinito: 15.';
$_['help_setting_scheduler_ssl_verify']   = 'No solo con un certificato autofirmato. Predefinito: Sì.';
$_['help_report_time_expire']             = 'Secondi.';

// Button
$_['button_save_editor']    = 'Salva config.ini';
$_['button_load_backup']    = 'Apri nell\'editor';

// Error
$_['error_form']            = 'Controlla i campi evidenziati.';
$_['error_required']        = 'Campo obbligatorio.';
$_['error_chars']           = 'Non sono ammessi virgolette ("), a capo e ${.';
$_['error_int']             = 'Inserisci un numero intero tra %d e %d.';
$_['error_select']          = 'Scegli uno dei valori proposti.';
$_['error_filename']        = 'Solo un nome di file: lettere, cifre e _ . - (niente cartelle).';
$_['error_url']             = 'Inserisci un indirizzo che inizia con http:// o https://.';
$_['error_parse']           = 'Il file non è un ini valido e non è stato salvato: %s';
$_['error_missing']         = 'Manca la chiave %s (obbligatoria): il file non è stato salvato.';
$_['error_database']        = 'Connessione al database non riuscita con i nuovi dati, niente è stato salvato: %s';
$_['error_editor_size']     = 'Il testo supera %d KB.';
$_['error_save']            = 'Scrittura di config.ini non riuscita.';
?>

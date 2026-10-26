<?php
// Heading
$_['heading_title']          = 'Lingue';

// Text
$_['text_success']           = 'Lingua salvata.';
$_['text_upload_success']    = 'Pacchetto di lingua caricato.';
$_['text_default']           = ' <b>(Predefinita)</b>';
$_['text_files']             = '%d di %d file';
$_['text_help_list']         = 'La lingua di ogni utente si sceglie in Utenti. La lingua indicata come predefinita (setting.language in config.ini) vale per chi non ne ha scelta una e copre i testi che le altre lingue non hanno. La colonna File confronta i file di testo di ogni lingua con quelli della predefinita.';
$_['text_pack']              = 'Testi della lingua';
$_['text_pack_help']         = 'Tutti i testi della lingua, di tutti i moduli, stanno in un solo file JSON. Per tradurre: scarica il pacchetto della lingua predefinita, traduci i testi (non le chiavi) e caricalo qui. Vengono sostituiti solo i file contenuti nel pacchetto; gli altri restano come sono.';
$_['text_pack_new']          = 'Salva la lingua: subito dopo potrai caricarne i testi.';

// Column
$_['column_name']            = 'Nome';
$_['column_code']            = 'Codice';
$_['column_directory']       = 'Cartella';
$_['column_files']           = 'File';
$_['column_status']          = 'Stato';
$_['column_action']          = 'Azioni';

// Entry
$_['entry_name']             = 'Nome:';
$_['entry_code']             = 'Codice:';
$_['entry_locale']           = 'Locale:';
$_['entry_directory']        = 'Cartella:';
$_['entry_sort_order']       = 'Ordinamento:';
$_['entry_status']           = 'Stato:';
$_['entry_pack']             = 'Pacchetto JSON:';

// Help
$_['help_code']              = 'Es. en_GB, de_DE, pt_BR. Si usa in setting.language di config.ini.';
$_['help_locale']            = 'Facoltativo. Es. en_GB.UTF-8,en_GB';
$_['help_directory']         = 'Nome della cartella dei file di lingua, es. english. Non si cambia dopo la creazione.';
$_['help_directory_fixed']   = 'Fissata alla creazione.';

// Button
$_['button_download']        = 'Scarica';
$_['button_upload']          = 'Carica';

// Error
$_['error_form']             = 'Controlla i campi evidenziati.';
$_['error_name']             = 'Il nome deve essere tra 1 e 64 caratteri.';
$_['error_code']             = 'Codice non valido: due o tre lettere minuscole, eventualmente seguite da _ e due maiuscole (es. en_GB).';
$_['error_code_exists']      = 'Esiste già una lingua con questo codice.';
$_['error_locale']           = 'Locale non valido: solo lettere, cifre e i caratteri _ . , @ -';
$_['error_directory']        = 'Cartella non valida: da 2 a 32 lettere minuscole, cifre o _.';
$_['error_directory_exists'] = 'Esiste già una lingua con questa cartella.';
$_['error_default_status']   = 'La lingua predefinita (config.ini) non si può disattivare.';
$_['error_default_code']     = 'Il codice della lingua predefinita è quello di setting.language in config.ini: cambialo prima lì.';
$_['error_default_delete']   = 'La lingua predefinita (config.ini) non si può eliminare.';
$_['error_upload_missing']   = 'Scegli il file JSON da caricare.';
$_['error_upload']           = 'Caricamento del file non riuscito.';
$_['error_upload_size']      = 'Il file supera il limite di %d MB.';
$_['error_pack']             = 'Il pacchetto non è stato caricato:';
$_['error_pack_format']      = 'il file non è un pacchetto di lingua K-iwi (JSON con "format": "k-iwi-language" e "files").';
$_['error_pack_empty']       = 'il pacchetto non contiene testi.';
$_['error_pack_file']        = 'file "%s": nome non valido o modulo inesistente.';
$_['error_pack_strings']     = 'file "%s": deve contenere almeno un testo.';
$_['error_pack_string']      = 'testo "%s": la chiave deve essere un testo di al massimo 128 caratteri senza caratteri di controllo e il valore deve essere un testo.';
$_['error_writable']         = 'Il web server non può scrivere in: %s. Rendi scrivibili le cartelle language dei moduli e riprova.';
?>

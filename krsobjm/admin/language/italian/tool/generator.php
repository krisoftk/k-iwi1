<?php
// Heading
$_['heading_title']           = 'Generatore';

// Text
$_['text_database']           			= 'Database: ';
$_['text_table']             			= 'Tabella: ';
$_['text_module']             			= 'Modulo: ';
$_['text_delete_table']       			= 'Cancellare la tabella al termine della disinstallazione: ';
$_['text_search_check']       			= 'Visualizza il pannello di ricerca: ';
$_['text_insert_check']       			= 'Inserire ed aggiornare i record: ';
$_['text_delete_check']       			= 'Cancellare i record: ';
$_['text_pagination_check']   			= 'Inserire la paginazione della tabella: ';
$_['text_success']            			= 'Modulo %s creato correttamente.';
$_['text_search_description'] 			= 'Configurazione pannello di ricerca.';
$_['text_insert_description'] 			= 'Configurazione modulo di inserimento.';
$_['text_relationship_description'] 	= 'Configurazione delle relazioni dei campi.';
$_['text_relationship_one_to_many'] 	= 'Uno a Molti';
$_['text_relationship_many_to_one'] 	= 'Molti a Uno';
$_['text_relationship_many_to_many'] 	= 'Molti a Molti';

//Tabs
$_['tab_general']             = "Generale";
$_['tab_insert']              = "Inserimento";
$_['tab_delete']              = "Cancellazione";
$_['tab_search']              = "Ricerca";
$_['tab_relationship']        = "Relazioni";

//Buttons
$_['button_generator']        = "Genera";

//Errors
$_['error_database_id']       = "Seleziona il database";
$_['error_table_id']          = "Devi selezionare una tabella per poter generare il programma";
$_['error_module']            = "Il nome del modulo deve iniziare con una lettera e contenere solo lettere, cifre e underscore (massimo 32 caratteri).";
$_['error_module_reserved']   = "Questo nome è usato da un modulo del framework (admin, standard, common, install, error): scegline un altro.";
$_['error_writable']          = 'Il web server non può scrivere in %s: il generatore ha bisogno dei permessi di scrittura su krsobjm/ (vedi readme).';
$_['error_fields']            = "Devi selezionare almeno un campo della tabella.";
$_['error_fields_key_insert'] = "Hai selezionato l'inserimento e l'aggiornamento, per questa opzione è obbligatorio selezionare almeno un campo da usare come chiave";
$_['error_fields_key_delete'] = "Hai selezionato la cancellazione, per questa opzione è obbligatorio selezionare almeno un campo da usare come chiave";
$_['error_fields_insert']     = "Hai selezionato l'inserimento e l'aggiornamento, per questa opzione è obbligatorio selezionare almeno un campo da usare come input";
$_['error_fields_search']     = "Hai selezionato la visualizzazione del pannello di ricerca, per questa opzione è obbligatorio selezionare almeno un campo da usare come input";

// Column
$_['column_view'] = 'Visualizza';
$_['column_field'] = 'Campo';
$_['column_type'] = 'Tipo';
$_['column_null'] = 'Null';
$_['column_key'] = 'Chiave';
$_['column_default'] = 'Predefinito';
$_['column_extra'] = 'Extra';
$_['button_add_relationship'] = 'Aggiungi';
$_['button_remove_relationship'] = 'Rimuovi';
?>
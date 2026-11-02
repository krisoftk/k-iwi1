<?php
// Heading
$_['heading_title']          = 'Backup / Restore';

// Text
$_['text_help']              = 'Backup of the framework database (database.* in config.ini) to a .sql file. Backups stay in the backup/ folder of the project, outside the public folder: they hold the whole database (users, password hashes, personal data), so download the ones you need and delete the others.';
$_['text_backups']           = 'Available backups';
$_['text_no_backups']        = 'No backups.';
$_['text_create']            = 'New backup';
$_['text_create_help']       = 'Choose the tables to save. Sessions (krs_session) are left out by default: they are temporary and hold the session data of whoever is logged in.';
$_['text_upload']            = 'Upload a backup';
$_['text_upload_help']       = 'Only files made by this page and not changed: the file is checked and then shown in a confirmation page, it isn\'t restored right away.';
$_['text_upload_limit']      = 'Maximum size: %s (PHP\'s upload_max_filesize / post_max_size).';
$_['text_confirm_delete']    = 'Delete this backup for good?';
$_['text_kind_backup']       = 'Manual';
$_['text_kind_prerestore']   = 'Automatic, before a restore';
$_['text_kind_upload']       = 'Uploaded';
$_['text_created']           = 'Backup created: %s';
$_['text_deleted']           = 'Backup %s deleted.';
$_['text_restored']          = '%s restored. The previous state of the database is in the backup %s.';
$_['text_confirm_title']     = 'Confirm the restore';
$_['text_target']            = 'The restore overwrites the tables listed below in the database %s.';
$_['text_confirm_help']      = 'Before it starts, a full backup of the current state is made automatically. If a table of the backup holds the users, afterwards the users and passwords of the backup apply.';
$_['text_other_database']    = 'The backup comes from the database %s, the current one is %s.';
$_['text_file']              = 'File';
$_['text_created_on']        = 'Created on';
$_['text_from_database']     = 'Source database';
$_['text_server']            = 'server';
$_['text_replaced']          = 'Tables replaced';
$_['text_untouched']         = 'Tables left as they are';
$_['text_none']              = 'none';
$_['text_agree']             = 'I understand that these tables will be replaced with the content of the backup.';

// Column
$_['column_date']            = 'Date';
$_['column_kind']            = 'Type';
$_['column_tables']          = 'Tables';
$_['column_table']           = 'Table';
$_['column_rows']            = 'Rows (about)';
$_['column_size']            = 'Size';
$_['column_action']          = 'Action';

// Button
$_['button_create']          = 'Create the backup';
$_['button_upload']          = 'Upload';
$_['button_download']        = 'Download';
$_['button_restore']         = 'Restore';
$_['button_delete']          = 'Delete';
$_['button_cancel']          = 'Cancel';

// Error
$_['error_driver']           = 'Backup and restore only work with MySQL/MariaDB (database.driver = "mysql").';
$_['error_writable']         = 'The web server can\'t write to the folder %s: create it and make it writable to make backups and restores.';
$_['error_no_tables']        = 'Choose at least one table.';
$_['error_create']           = 'Backup failed: %s';
$_['error_safety']           = 'The automatic backup of the current state failed, so the restore didn\'t start: %s';
$_['error_restore']          = 'The restore stopped at line %d: %s. The database may be left halfway: restore the automatic backup %s to go back to the previous state.';
$_['error_agree']            = 'Tick the confirmation to start the restore.';
$_['error_upload_missing']   = 'Choose a file to upload.';
$_['error_upload']           = 'Upload failed.';
$_['error_upload_size']      = 'The file is larger than the maximum allowed size (%s).';
$_['error_file_read']        = 'The file can\'t be read.';
$_['error_file_format']      = 'This isn\'t a K-iwi backup.';
$_['error_file_integrity']   = 'The file is incomplete or has been changed (the SHA-256 check doesn\'t match).';
$_['error_file_statement']   = 'Line %d holds a statement that a K-iwi backup doesn\'t contain.';
$_['error_file_store']       = 'The file can\'t be saved in the backup folder.';
?>

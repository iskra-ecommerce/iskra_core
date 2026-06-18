<?php
// Heading
$_['heading_title'] = 'Iskra: Core';

// Text
$_['text_success']           = 'Success: You have modified Iskra Core settings!';
$_['text_settings']          = 'Settings';
$_['text_logs']              = 'Logs';
$_['text_event_bus']         = 'Event Bus';
$_['text_queue']             = 'Task Queue';
$_['text_backups']           = 'Backups';
$_['text_migrations']        = 'Migrations';
$_['text_integrity']         = 'Integrity';
$_['text_enabled']           = 'Enabled';
$_['text_disabled']          = 'Disabled';
$_['text_yes']               = 'Yes';
$_['text_no']                = 'No';
$_['text_filter']            = 'Filter';
$_['text_search']            = 'Search';
$_['text_reset']             = 'Reset';
$_['text_rotate_now']        = 'Rotate Now';
$_['text_auto_rotate']       = 'Auto-rotate';
$_['text_backup']            = 'Backup';
$_['text_restore']           = 'Restore';
$_['text_download']          = 'Download';
$_['text_delete']            = 'Delete';
$_['text_delete_backup']     = 'Delete Backup';
$_['text_create_backup']     = 'Create Backup';
$_['text_no_backups']        = 'No backups found';
$_['text_no_logs']           = 'No logs found';
$_['text_no_migrations']     = 'No migrations found';
$_['text_showing']           = 'Showing %d to %d of %d (%d Pages)';
$_['text_pagination']        = 'Pagination';
$_['text_all']               = 'All';
$_['text_pending']           = 'Pending';
$_['text_success_status']    = 'Success';
$_['text_failed']            = 'Failed';
$_['text_rolled_back']       = 'Rolled Back';
$_['text_applied']           = 'Applied';
$_['text_not_applied']       = 'Not Applied';
$_['text_run_migrations']    = 'Run Pending Migrations';
$_['text_rollback_last']     = 'Rollback Last Migration';
$_['text_check_integrity']   = 'Check Integrity Now';
$_['text_table']             = 'Table';
$_['text_rows']              = 'Rows';
$_['text_size']              = 'Size';
$_['text_status']            = 'Status';
$_['text_ok']                = 'OK';
$_['text_error']             = 'Error';
$_['text_warning']           = 'Warning';
$_['text_details']           = 'Details';
$_['text_log_level']         = 'Log Level';
$_['text_category']          = 'Category';
$_['text_user']              = 'User';
$_['text_message']           = 'Message';
$_['text_ip']                = 'IP Address';
$_['text_date']              = 'Date';
$_['text_action']            = 'Action';
$_['text_select_all']        = 'Select All';
$_['text_unselect_all']      = 'Unselect All';
$_['text_confirm_delete']    = 'Are you sure you want to delete the selected items?';
$_['text_confirm_rollback']  = 'Rollback will delete data. Are you sure?';
$_['text_confirm_uninstall'] = 'This will remove all iskra_core data. Backup recommended!';
$_['text_cyrillic_test']     = 'Cyrillic Test';

// Entry
$_['entry_logging_enabled']      = 'Enable Logging';
$_['entry_log_level']            = 'Minimum Log Level';
$_['entry_log_retention_days']   = 'Keep Logs For (days)';
$_['entry_log_max_size_mb']      = 'Max Log Table Size (MB)';
$_['entry_log_limit_strategy']   = 'Log Limit Strategy';
$_['entry_log_max_rows']         = 'Max Log Rows';
$_['entry_log_min_disk_mb']      = 'Min Free Disk Space (MB)';
$_['entry_event_bus_enabled']    = 'Enable Event Bus';
$_['entry_queue_cron_enabled']   = 'Process Queue Via Cron';
$_['entry_backup_retention_days']= 'Backup Retention (days)';
$_['entry_date_from']            = 'Date From';
$_['entry_date_to']              = 'Date To';
$_['entry_level']                = 'Level';
$_['entry_category']             = 'Category';

// Help
$_['help_logging_enabled']       = 'Completely enable or disable log writing. Existing logs remain viewable.';
$_['help_log_level']             = 'Log only this level and higher. Recommended: info for production, debug for development.';
$_['help_log_retention_days']    = 'Logs older than this number of days will be automatically deleted.';
$_['help_log_max_size_mb']       = 'When table size exceeds this, oldest logs are deleted. Default: 5 GB.';
$_['help_log_limit_strategy']    = 'rows = count rows; table_size = measure DB table; disk_space = check free disk space.';
$_['help_log_max_rows']          = 'Soft limit on number of log rows. Oldest deleted when exceeded.';
$_['help_log_min_disk_mb']       = 'If free disk space drops below this, oldest logs are deleted.';
$_['help_event_bus']             = 'Event Bus allows extensions to interact with each other.';
$_['help_queue_cron']            = 'Task queue is processed via cron. Recommended every minute.';
$_['help_backup_retention']      = 'Backups older than this are automatically deleted.';

// Levels
$_['level_debug']    = 'Debug';
$_['level_info']     = 'Info';
$_['level_warning']  = 'Warning';
$_['level_error']    = 'Error';
$_['level_critical'] = 'Critical';

// Column
$_['column_level']     = 'Level';
$_['column_category']  = 'Category';
$_['column_user']      = 'User';
$_['column_message']   = 'Message';
$_['column_ip']        = 'IP Address';
$_['column_date']      = 'Date';
$_['column_version']   = 'Version';
$_['column_name']      = 'Name';
$_['column_status']    = 'Status';
$_['column_checksum']  = 'Checksum';
$_['column_applied']   = 'Applied At';
$_['column_rolled_back']= 'Rolled Back';
$_['column_error']     = 'Error';
$_['column_table']     = 'Table';
$_['column_rows']      = 'Rows';
$_['column_size']      = 'Size';
$_['column_backup_name'] = 'Backup Name';
$_['column_backup_size'] = 'Size';
$_['column_backup_date'] = 'Date';

// Tab
$_['tab_settings']    = 'Settings';
$_['tab_logs']        = 'Logs';
$_['tab_backups']     = 'Backups';
$_['tab_migrations']  = 'Migrations';
$_['tab_integrity']   = 'Integrity';

// Button
$_['button_save']         = 'Save';
$_['button_cancel']       = 'Cancel';
$_['button_filter']       = 'Filter';
$_['button_clear']        = 'Clear';
$_['button_delete']       = 'Delete Selected';
$_['button_rotate']       = 'Rotate Now';
$_['button_backup']       = 'Create Backup';
$_['button_restore']      = 'Restore';
$_['button_download']     = 'Download';
$_['button_run']          = 'Run';
$_['button_rollback']     = 'Rollback';
$_['button_check']        = 'Check';
$_['button_install']      = 'Install';
$_['button_uninstall']    = 'Uninstall';

// Error
$_['error_permission']       = 'Warning: You do not have permission to modify Iskra Core settings!';
$_['error_log_level']          = 'Invalid log level!';
$_['error_retention']          = 'Retention period must be between 1 and 365 days!';
$_['error_max_size']           = 'Max size must be between 100 MB and 100 GB!';
$_['error_max_rows']           = 'Max rows must be between 1000 and 10 000 000!';
$_['error_disk_space']         = 'Min disk space must be between 100 and 50 000 MB!';
$_['error_backup_failed']      = 'Backup creation failed!';
$_['error_restore_failed']     = 'Restore failed!';
$_['error_integrity_failed']   = 'Integrity check failed for one or more tables!';
$_['error_no_migration']       = 'No migrations to run or rollback!';
$_['error_delete_failed']      = 'Failed to delete selected items!';

// Success
$_['success_settings_saved']   = 'Success: Settings saved!';
$_['success_backup_created']   = 'Success: Backup created!';
$_['success_backup_restored']  = 'Success: Backup restored!';
$_['success_backup_deleted']   = 'Success: Backup deleted!';
$_['success_logs_deleted']     = 'Success: Selected logs deleted!';
$_['success_logs_rotated']     = 'Success: Logs rotated! %d entries removed.';
$_['success_migrations_run']   = 'Success: Migrations applied!';
$_['success_rollback']         = 'Success: Migration rolled back!';
$_['success_integrity_ok']     = 'Success: All tables pass integrity check!';

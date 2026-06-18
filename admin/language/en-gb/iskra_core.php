<?php
// Heading
$_['heading_title'] = 'Iskra: Core';

// Text
$_['text_success']     = 'Success: You have modified Iskra core settings!';
$_['text_settings']    = 'Settings';
$_['text_log']         = 'Logs';
$_['text_event_bus']   = 'Event Bus';
$_['text_queue']       = 'Task Queue';
$_['text_enabled']     = 'Enabled';
$_['text_disabled']    = 'Disabled';
$_['text_rotated']     = 'Logs rotated';

// Entry
$_['entry_log_level']            = 'Minimum log level';
$_['entry_log_retention_days']   = 'Keep logs for (days)';
$_['entry_log_max_size_mb']      = 'Max log size (MB)';
$_['entry_event_bus_enabled']    = 'Enable Event Bus';
$_['entry_queue_cron_enabled']   = 'Process queue via cron';

// Column
$_['column_level']     = 'Level';
$_['column_category']  = 'Category';
$_['column_user']      = 'User';
$_['column_message']   = 'Message';
$_['column_ip']        = 'IP address';
$_['column_date']      = 'Date';

// Levels
$_['level_debug']    = 'Debug';
$_['level_info']     = 'Info';
$_['level_warning']  = 'Warning';
$_['level_error']    = 'Error';
$_['level_critical'] = 'Critical';

// Help
$_['help_log_level']          = 'Log only this level and higher. Recommended: info for production, debug for development';
$_['help_log_retention_days'] = 'Logs older than this number of days will be automatically deleted';
$_['help_log_max_size_mb']    = 'When this size is exceeded, the oldest logs will be deleted. Default: 5 GB';
$_['help_event_bus']          = 'Event Bus allows extensions to interact with each other';
$_['help_queue_cron']         = 'Task queue is processed via cron. Recommended every minute';

// Error
$_['error_permission'] = 'Warning: You do not have permission to modify Iskra core settings!';
$_['error_log_level']  = 'Invalid log level!';
$_['error_retention']  = 'Retention period must be between 1 and 365 days!';
$_['error_max_size']   = 'Max size must be between 100 MB and 100 GB!';

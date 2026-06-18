<?php
// Heading
$_['heading_title'] = 'Искра: Ядро';

// Text
$_['text_success']           = 'Успешно: вы изменили настройки ядра Искры!';
$_['text_settings']          = 'Настройки';
$_['text_logs']              = 'Логи';
$_['text_event_bus']         = 'Шина событий';
$_['text_queue']             = 'Очередь задач';
$_['text_backups']           = 'Бэкапы';
$_['text_migrations']        = 'Миграции';
$_['text_integrity']         = 'Целостность';
$_['text_enabled']           = 'Включено';
$_['text_disabled']          = 'Отключено';
$_['text_yes']               = 'Да';
$_['text_no']                = 'Нет';
$_['text_filter']            = 'Фильтр';
$_['text_search']            = 'Поиск';
$_['text_reset']             = 'Сбросить';
$_['text_rotate_now']        = 'Ротировать сейчас';
$_['text_auto_rotate']       = 'Авто-ротация';
$_['text_backup']            = 'Бэкап';
$_['text_restore']           = 'Восстановить';
$_['text_download']          = 'Скачать';
$_['text_delete']            = 'Удалить';
$_['text_delete_backup']     = 'Удалить бэкап';
$_['text_create_backup']     = 'Создать бэкап';
$_['text_no_backups']        = 'Бэкапы не найдены';
$_['text_no_logs']           = 'Логи не найдены';
$_['text_no_migrations']     = 'Миграции не найдены';
$_['text_showing']           = 'Показано %d — %d из %d (%d страниц)';
$_['text_pagination']        = 'Пагинация';
$_['text_all']               = 'Все';
$_['text_pending']           = 'В ожидании';
$_['text_success_status']    = 'Успешно';
$_['text_failed']            = 'Ошибка';
$_['text_rolled_back']       = 'Откат';
$_['text_applied']           = 'Применена';
$_['text_not_applied']       = 'Не применена';
$_['text_run_migrations']    = 'Запустить ожидающие миграции';
$_['text_rollback_last']     = 'Откатить последнюю миграцию';
$_['text_check_integrity']   = 'Проверить целостность';
$_['text_table']             = 'Таблица';
$_['text_rows']              = 'Строк';
$_['text_size']              = 'Размер';
$_['text_status']            = 'Статус';
$_['text_ok']                = 'OK';
$_['text_error']             = 'Ошибка';
$_['text_warning']           = 'Предупреждение';
$_['text_details']           = 'Подробности';
$_['text_log_level']         = 'Уровень лога';
$_['text_category']          = 'Категория';
$_['text_user']              = 'Пользователь';
$_['text_message']           = 'Сообщение';
$_['text_ip']                = 'IP-адрес';
$_['text_date']              = 'Дата';
$_['text_action']            = 'Действие';
$_['text_select_all']        = 'Выбрать все';
$_['text_unselect_all']      = 'Снять выделение';
$_['text_confirm_delete']    = 'Вы уверены, что хотите удалить выбранные элементы?';
$_['text_confirm_rollback']  = 'Откат удалит данные. Вы уверены?';
$_['text_confirm_uninstall'] = 'Это удалит все данные iskra_core. Рекомендуется сделать бэкап!';
$_['text_cyrillic_test']     = 'Тест кириллицы';

// Entry
$_['entry_logging_enabled']      = 'Включить логирование';
$_['entry_log_level']            = 'Минимальный уровень логирования';
$_['entry_log_retention_days']   = 'Хранить логи (дней)';
$_['entry_log_max_size_mb']      = 'Макс. размер таблицы логов (МБ)';
$_['entry_log_limit_strategy']   = 'Стратегия лимита логов';
$_['entry_log_max_rows']         = 'Макс. количество строк логов';
$_['entry_log_min_disk_mb']      = 'Мин. свободное место на диске (МБ)';
$_['entry_event_bus_enabled']    = 'Включить Event Bus';
$_['entry_queue_cron_enabled']   = 'Обрабатывать очередь через cron';
$_['entry_backup_retention_days']= 'Хранение бэкапов (дней)';
$_['entry_date_from']            = 'Дата с';
$_['entry_date_to']              = 'Дата по';
$_['entry_level']                = 'Уровень';
$_['entry_category']             = 'Категория';

// Help
$_['help_logging_enabled']       = 'Полностью включить или отключить запись логов. Существующие логи остаются доступны для просмотра.';
$_['help_log_level']             = 'Записывать логи только этого уровня и выше. Рекомендуется: info для production, debug для разработки.';
$_['help_log_retention_days']    = 'Логи старше указанного количества дней будут автоматически удаляться.';
$_['help_log_max_size_mb']       = 'При превышении этого размера самые старые логи удаляются. По умолчанию: 5 ГБ.';
$_['help_log_limit_strategy']    = 'rows = считать строки; table_size = измерять размер таблицы; disk_space = проверять свободное место на диске.';
$_['help_log_max_rows']          = 'Мягкий лимит на количество строк логов. При превышении удаляются самые старые.';
$_['help_log_min_disk_mb']       = 'Если свободное место на диске меньше этого значения, удаляются самые старые логи.';
$_['help_event_bus']             = 'Шина событий позволяет расширениям взаимодействовать друг с другом.';
$_['help_queue_cron']            = 'Очередь задач обрабатывается через cron. Рекомендуется каждую минуту.';
$_['help_backup_retention']      = 'Бэкапы старше этого срока автоматически удаляются.';

// Levels
$_['level_debug']    = 'Отладка';
$_['level_info']     = 'Информация';
$_['level_warning']  = 'Предупреждение';
$_['level_error']    = 'Ошибка';
$_['level_critical'] = 'Критическая';

// Column
$_['column_level']     = 'Уровень';
$_['column_category']  = 'Категория';
$_['column_user']      = 'Пользователь';
$_['column_message']   = 'Сообщение';
$_['column_ip']        = 'IP-адрес';
$_['column_date']      = 'Дата';
$_['column_version']   = 'Версия';
$_['column_name']      = 'Имя';
$_['column_status']    = 'Статус';
$_['column_checksum']  = 'Контрольная сумма';
$_['column_applied']   = 'Применена';
$_['column_rolled_back']= 'Откат';
$_['column_error']     = 'Ошибка';
$_['column_table']     = 'Таблица';
$_['column_rows']      = 'Строк';
$_['column_size']      = 'Размер';
$_['column_backup_name'] = 'Имя бэкапа';
$_['column_backup_size'] = 'Размер';
$_['column_backup_date'] = 'Дата';

// Tab
$_['tab_settings']    = 'Настройки';
$_['tab_logs']        = 'Логи';
$_['tab_backups']     = 'Бэкапы';
$_['tab_migrations']  = 'Миграции';
$_['tab_integrity']   = 'Целостность';

// Button
$_['button_save']         = 'Сохранить';
$_['button_cancel']       = 'Отмена';
$_['button_filter']       = 'Фильтр';
$_['button_clear']        = 'Очистить';
$_['button_delete']       = 'Удалить выбранные';
$_['button_rotate']       = 'Ротировать';
$_['button_backup']       = 'Создать бэкап';
$_['button_restore']      = 'Восстановить';
$_['button_download']     = 'Скачать';
$_['button_run']          = 'Запустить';
$_['button_rollback']     = 'Откатить';
$_['button_check']        = 'Проверить';
$_['button_install']      = 'Установить';
$_['button_uninstall']    = 'Удалить';

// Error
$_['error_permission']       = 'Предупреждение: у вас нет прав для изменения настроек ядра Искры!';
$_['error_log_level']          = 'Неверный уровень логирования!';
$_['error_retention']          = 'Срок хранения должен быть от 1 до 365 дней!';
$_['error_max_size']           = 'Максимальный размер должен быть от 100 МБ до 100 ГБ!';
$_['error_max_rows']           = 'Максимальное количество строк должно быть от 1000 до 10 000 000!';
$_['error_disk_space']         = 'Минимальное свободное место должно быть от 100 до 50 000 МБ!';
$_['error_backup_failed']      = 'Не удалось создать бэкап!';
$_['error_restore_failed']     = 'Восстановление не удалось!';
$_['error_integrity_failed']   = 'Проверка целостности не пройдена для одной или нескольких таблиц!';
$_['error_no_migration']       = 'Нет миграций для запуска или отката!';
$_['error_delete_failed']      = 'Не удалось удалить выбранные элементы!';

// Success
$_['success_settings_saved']   = 'Успешно: Настройки сохранены!';
$_['success_backup_created']   = 'Успешно: Бэкап создан!';
$_['success_backup_restored']  = 'Успешно: Бэкап восстановлен!';
$_['success_backup_deleted']   = 'Успешно: Бэкап удалён!';
$_['success_logs_deleted']     = 'Успешно: Выбранные логи удалены!';
$_['success_logs_rotated']     = 'Успешно: Логи ротированы! Удалено записей: %d.';
$_['success_migrations_run']   = 'Успешно: Миграции применены!';
$_['success_rollback']         = 'Успешно: Миграция откачена!';
$_['success_integrity_ok']     = 'Успешно: Все таблицы прошли проверку целостности!';

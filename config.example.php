<?php
/**
 * This file is part of Totara Enterprise Extensions.
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * Totara Enterprise Extensions is provided only to Totara
 * Learning Solutions LTD’s customers and partners, pursuant to
 * the terms and conditions of a separate agreement with Totara
 * Learning Solutions LTD or its affiliate.
 *
 * If you do not have an agreement with Totara Learning Solutions
 * LTD, you may not access, use, modify, or distribute this software.
 * Please contact [licensing@totaralearning.com] for more information.
 *
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package core
 * @var core_config $CFG
 */

/***********************************************************************************************************************
Getting started
***********************************************************************************************************************/
//
// Copy this file to "config.php" and edit it, providing the settings for your site.
// All settings already uncommented are required. All others are optional
//

/***********************************************************************************************************************
Totara Registration
***********************************************************************************************************************/
//
// When installing or upgrading Totara you will be asked to register your site if you have not already.
// To save having to provide the information through the web interface you can set the following configuration variables.
// Site type can be one of: production, trial, qa, demo, or development.
// $CFG->sitetype = 'production';
// $CFG->registrationcode = 'xxxxxxxxxxxxxxxx'; // Your unique code provided by the registration system
//
// All sites are required to send registration data to Totara unless an exemption has been granted.
// Exemptions are granted on a case-by-case basis and need to be organised with your Totara partner.
// If you are granted an exemption you will be provided with a unique code that needs to be added
// to your config.php as shown below.
//
// $CFG->registrationexemptioncode = 'yyyyyyyyyy'; // Optional registration exemption code, 10 characters.
//

/***********************************************************************************************************************
Web Address
***********************************************************************************************************************/
//
// Set the full web address that your Totara installation will be access on.
// Important information
//  * Totara can only be accessed via a single URL.
//  * It must be either https:// or http:// Totara cannot be accessed on both. https:// is strongly recommended.
//  * Do not add a trailing slash.
//
$CFG->wwwroot   = 'https://example.com/totara';

/***********************************************************************************************************************
Data directory
***********************************************************************************************************************/
//
// The following is the path to the directory that Totara will use to store files.
// This includes user uploaded files, temporary and cache files, and other miscellaneous files created by Totara.
// The directory MUST be both readable and writable by the web server user.
// It should NOT be accessible via the web.
//
$CFG->dataroot  = '/var/totara/sitedata';
//
// Sets the permissions applied to new directories careted by Totara within the dataroot set above.
// The default is usually acceptable. The value must be an octal (the leading 0 is important, don't put it in quotes)
//
$CFG->directorypermissions = 02777;
//
// Set the directory Totara will use to store temporary files. If not set a temp directory will be created within the
// dataroot directory. The directory used must be accessible to all cluster nodes.
// $CFG->tempdir = '/var/totara/temp';
//
// Set the directory Totara will use to store cache files. If not set a cache directory will be created within the
// dataroot directory. The directory used must be accessible to all cluster nodes, and locking must be supported.
// $CFG->cachedir = '/var/totara/cache';
//
// Set a locally available cache directory. This is intended for server clusters and does NOT need to be shared between
// nodes. Only data that is safe to cache like this will be stored in this directory.
// $CFG->localcachedir = '/var/totara/localcache/';
//
// Set the lifetime a served file will remain in caches (in seconds)
// This setting only has an effect in areas where stale files are not expected to be a problem. Areas where it is vital
// the correct file is served will not make use of caching.
// If you are concerned about users receiving stale files you can lower this lifetime.
// $CFG->filelifetime = 60*60*6;
//


/***********************************************************************************************************************
 Database configuration
**********************************************************************************************************************/
//
// The following settings are common to all databases.
// Database specific options are detailed in following sections.
//
$CFG->dbtype = 'pgsql';      // One of pgsql, mariadb, mysqli, sqlsrv
$CFG->dblibrary = 'native';  // Always 'native'
$CFG->dbhost = 'localhost';  // Host, URL or IP address
$CFG->dbname = 'totara';     // Database name
$CFG->dbuser = 'username';   // Username for your database username
$CFG->dbpass = 'password';   // Password for your database user
$CFG->prefix = 'ttr_';       // Prefix to use for all table names
$CFG->dboptions = array(
    // Used to enable persistent database connections.
    'dbpersist' => false,
    // Set to true or to the socket path.
    'dbsocket'  => false,
    // Port to use to connect to the database. When empty the default port for your chosen DB will be used.
    'dbport'    => '',
);
//
// Totara used to periodically ask the database management system to analyse the context and context_map tables,
// ensuring that statistics for the tables are up to date.
// Having accurate statistics can have a significant performance impact on a site, particularly if courses, activities
// and users are being created, moved or deleted often. Unfortunately, there are known issues with some database systems
// when analysing tables for a heavily loaded database.
// Totara tries to ensure that table statistics are up to date in a safe way for your database.
//
// PostgreSQL:
// Analyze table is called after every change to context as well as updates to the context_map table.
// There are no known issues with this approach in this database system, and it will result in
// the best possible outcome as statistics for the tables are always up to date.
//
// MySQL, MariaDB and MSSQL:
// There is a known performance problem within these database systems which can have significant impact
// when the system is heavily loaded.
// As such a scheduled task that runs only once per day has been introduced.
// Sites using these database systems may want to tweak when table analysis happens to best suit
// how those sites are used.
// It is possible to control when to analyse tables through the analyze_context_table_after_build setting.
//
// Setting it to true will execute analyze table command after changes to the context table as well as
// updates to the context_map table.
// Setting it to false will prevent this and it will be done late at night by totara_core\task\analyze_table_task
// to mitigate performance degradation.
// It is better to revisit the scheduled tasks setting to let the task run at off-peak times on your site.
//
// The default for this setting is true for PostgreSQL, and false for MySQL, MariaDB and MSSQL.
//
// $CFG->analyze_context_table_after_build = true;
//
// The context_map table is exceptionally large, and changes to a single context record can lead to
// a significant number of updates for the context_map table.
// The following setting controls how often table statistics are updated when updating the context_map table.
// By default it will update context_map table statistics when more than 1000 updates have occurred.
// If set to a positive integer, it will update statistics after the number of updates is more than the value.
// Setting it to 0 will cause it to update statistics after every update.
// Note: table statistics will not be updated if $CFG->analyze_context_table_after_build has been set to false.
//
// $CFG->analyze_context_table_inserted_count_threshold = 1000;     // this is "analyze", not "analyse"
//

/***********************************************************************************************************************
Database configuration: PostgreSQL
***********************************************************************************************************************/
//
// To use a custom schema uncomment the following and set it to the name of your schema.
// You will need to create the schema within the database manually.
//
// $CFG->dboptions['dbschema'] = 'totara';
//
// PgSQL connection poolers like pgbouncer don't support advanced options on connection. When set to true advanced
// options will not be sent in the connection string, instead you will need to set them in the database.
//    ALTER DATABASE moodle SET client_encoding = UTF8;
//    ALTER DATABASE moodle SET standard_conforming_strings = on;
//    ALTER DATABASE moodle SET search_path = 'totara,public';  -- Optional, if you wish to use a custom schema.
//
// $CFG->dboptions['dbhandlesoptions'] = false';
//
// Sets the maximum number of records that can be inserted in a single operation.
// Sets larger than this will be chunked and inserted in batches.
//
// $CFG->dboptions['bulkinsertsize'] = 500;
//
// Set Totara to connect using SSL.
//  * https://www.php.net/manual/en/function.pg-connect.php
//  * https://www.postgresql.org/docs/current/libpq-ssl.html#LIBPQ-SSL-PROTECTION
//  * https://www.postgresql.org/docs/current/runtime-config-connection.html#RUNTIME-CONFIG-CONNECTION-SSL
//  * https://www.postgresql.org/docs/current/ssl-tcp.html
//
// $CFG->dboptions['sslmode'] = true;
//
// Totara Full Text Search supports one language only, you need to configure it here before installation or upgrade to
// Totara 12 otherwise the default value will be used. If you change it later you need to run following CLI script to
// rebuild all full text search indexes: admin/cli/fts_rebuild_indexes.php
// It is recommended that the language selected here is compatible with $CFG->lang.
// PostgreSQL is using 'english' configuration for full text search by default,
// for list of available options see result of "SELECT cfgname FROM pg_ts_config;". For example:
//
// $CFG->dboptions['ftslanguage'] = 'english';
// $CFG->dboptions['ftslanguage'] = 'simple';
// $CFG->dboptions['ftslanguage'] = 'german';
//
// PostgreSQL does not support Japanese and other languages with very short words without spaces in between, enable the
// following setting to get a basic experimental support of these languages.
// If the value changes then you need to run: admin/cli/fts_repopulate_tables.php
//
// $CFG->dboptions['fts3bworkaround'] = true;
//
// PostgreSQL has built in support for accent sensitive full text searches.
// PostgreSQL provides this by means of an extension called unaccent which is not created by default.
// To change accent sensitive fulltext searches for you can set the following setting according to your requirement:
//
// $CFG->dboptions['ftsaccentsensitivity'] = true;
// $CFG->dboptions['ftsaccentsensitivity'] = false;
// $CFG->dboptions['ftsaccentsensitivity'] = 'dbdefault';
//
// After changing the accent sensitivity setting you need to run the following scripts in the listed order:
//    1. admin/cli/fts_rebuild_indexes.php
//    2. admin/cli/fts_repopulate_tables.php
//

/***********************************************************************************************************************
Database configuration: MySQL + MariaDB
***********************************************************************************************************************/
//
// Set the database collation.
// It is preferred to configure the database default collation prior to installation.
//
// $CFG->dboptions['dbcollation'] = 'utf8mb4_unicode_ci';
//
// Set the Database engine.
// It is preferable to configure the database default engine prior to installation.
// InnoDB or XtraDB are recommended.
//
// $CFG->dboptions['dbengine'] = 'InnoDB';
//
// Sets the maximum number of records that can be inserted in a single operation.
// Sets larger than this will be chunked and inserted in batches.
//
// $CFG->dboptions['bulkinsertsize'] = 500;
//
// Set Totara to connect using SSL.
//  * https://www.php.net/manual/en/mysqli.ssl-set.php
//  * https://www.php.net/manual/en/mysqli.real-connect.php
//  * https://dev.mysql.com/doc/refman/8.0/en/using-encrypted-connections.html
//
// $CFG->dboptions['client_ssl'] = true;
// $CFG->dboptions['client_dont_verify_server_cert'] = false;
// $CFG->dboptions['ssl_key'] = NULL;
// $CFG->dboptions['ssl_cert'] = NULL;
// $CFG->dboptions['ssl_ca'] = NULL;
// $CFG->dboptions['ssl_capath'] = NULL;
// $CFG->dboptions['ssl_cipher'] = NULL;
// $CFG->dboptions['ssl_verify_server_cert'] = false;
//
// Totara Full Text Search supports one language only, you need to configure it here before installation or upgrade to
// Totara 12 otherwise the default value will be used. If you change it later you need to run following CLI script to
// rebuild all full text search indexes: admin/cli/fts_rebuild_indexes.php
// It is recommended that the language selected here is compatible with $CFG->lang.
//
// MySQL is using case and accent insensitive collation for full text search by default, you can specify a different
// collation here, for example:
//
// $CFG->dboptions['ftslanguage'] = 'utf8_unicode_ci';
// $CFG->dboptions['ftslanguage'] = 'utf8mb4_0900_as_ci';
// $CFG->dboptions['ftslanguage'] = 'utf8mb4_de_pb_0900_ai_ci';
//
// MySQL does not support Japanese and other languages with very short words without spaces in between, enable the
// following setting to get a basic experimental support of these languages.
// If the value changes then you need to run: admin/cli/fts_repopulate_tables.php
//
// $CFG->dboptions['fts3bworkaround'] = true;
//
// MySQL also has the full-text parser plugin ngram, which make the search easier for Chinese, Japanese, and Korean
// languages. It is also useful for tokenising concatenated words. More information about ngram parser can be found in
// MySQL documentation. To use ngram support, enable the following setting:
//
// $CFG->dboptions['ftsngram'] = true;
//
// After changing the configuration of ngram support after Totara had been installed, you will need to run the following
// scripts in the listed order:
//    1. admin/cli/fts_rebuild_indexes.php
//    2. admin/cli/fts_repopulate_tables.php
//
// When using MySQL GTID based clustering/replication (only Mysql 8.0.21 or later supported) the following setting must be used
// to disable all features that create database tables from select statements (such as Report builder caching).
//
// $CFG->dboptions['create_table_from_select_supported'] = false;

/***********************************************************************************************************************
Database configuration: MSSQL
***********************************************************************************************************************/
//
// Set Totara to connect using SSL.
// * https://docs.microsoft.com/en-gb/sql/connect/php/connection-options?view=sql-server-2017
//
// $CFG->dboptions['encrypt'] = true;
// $CFG->dboptions['trustservercertificate'] = true;
//
// Totara Full Text Search supports one language only, you need to configure it here before installation or upgrade to
// Totara 12 otherwise the default value will be used. If you change it later you need to run following CLI script to
// rebuild all full text search indexes: admin/cli/fts_rebuild_indexes.php
// It is recommended that the language selected here is compatible with $CFG->lang.
//
// MS SQL Server is using 'English' language by default, list of options is at
// https://docs.microsoft.com/en-us/sql/relational-databases/system-catalog-views/sys-fulltext-languages-transact-sql?view=sql-server-2017
//
// $CFG->dboptions['ftslanguage'] = 'English';
// $CFG->dboptions['ftslanguage'] = 'German';
// $CFG->dboptions['ftslanguage'] = 'Japanese';
// $CFG->dboptions['ftslanguage'] = 1028; // Traditional Chinese
// $CFG->dboptions['ftslanguage'] = 2052; // Simplified Chinese
//
// MS SQL has built in support for accent sensitive full text searches.
// Accent sensitivity is on by default and to turn it off the fulltext catalog will need to be rebuilt.
// To change accent sensitive fulltext searches for MS SQL you can set the following setting according to your requirement:
//
// $CFG->dboptions['ftsaccentsensitivity'] = true;
// $CFG->dboptions['ftsaccentsensitivity'] = false;
// $CFG->dboptions['ftsaccentsensitivity'] = 'dbdefault';
//
// After changing the accent sensitivity setting you need to run the following scripts in the listed order:
//    1. admin/cli/fts_rebuild_indexes.php
//    2. admin/cli/fts_repopulate_tables.php
//

/***********************************************************************************************************************
PHP configuration
***********************************************************************************************************************/
//
// Set the default timezone for PHP.
// It is preferable to configure this in your php.ini file, however if you don't have access to modify that then this
// can be set in config.php as follows:
//
// date_default_timezone_set('Pacific/Auckland');

/***********************************************************************************************************************
Web server configuration
***********************************************************************************************************************/
//
// Offloading file serving
// Some web servers allow file serving to be offloaded from PHP directly to the web server using special headers.
// This can be beneficial for your web server performance.
//
// Apache https://tn123.org/mod_xsendfile/
// $CFG->xsendfile = 'X-Sendfile';
//
// Lighttpd http://redmine.lighttpd.net/projects/lighttpd/wiki/X-LIGHTTPD-send-file
// $CFG->xsendfile = 'X-LIGHTTPD-send-file';
//
// Nginx http://wiki.nginx.org/XSendfile
// $CFG->xsendfile = 'X-Accel-Redirect';
//
// Directory aliases for web servers like Nginx that require them you can specify them as following:
//
// $CFG->xsendfilealiases = [];
// $CFG->xsendfilealiases['/dataroot/'] = $CFG->dataroot;
// $CFG->xsendfilealiases['/cachedir/'] = '/var/totara/cache',     // for custom $CFG->cachedir locations
// $CFG->xsendfilealiases['/localcachedir/'] = '/var/local/cache', // for custom $CFG->localcachedir locations
// $CFG->xsendfilealiases['/tempdir/']  = '/var/totara/temp',      // for custom $CFG->tempdir locations
// $CFG->xsendfilealiases['/filedir']   = '/var/totara/filedir',   // for custom $CFG->filedir locations
//
// Inform Totara that it is behind a reverse proxy load balancing configuration.
// This may also be required when using port forwarding.
// $CFG->reverseproxy = true;
//
// Inform Totara that the site is being a server that is offloading SSL.
// Please note that a site may be available on https:// or http:// but not both.
// $CFG->sslproxy = true;
//

/***********************************************************************************************************************
Session handling
***********************************************************************************************************************/
//
// Totara supports several session storage methods.
// Use the following setting to set the storage method you want to use.
//
// $CFG->session_handler_class = '\core\session\database';
//
// Each requires its own configuration. The following blocks explain how to configure each to the storage methods
// Totara supports.
//
// ------------------------------------------------------------------------
// Database
//
// $CFG->session_handler_class = '\core\session\database';
// $CFG->session_database_acquire_lock_timeout = 120;
//
// ------------------------------------------------------------------------
// File
// Requires file system locking
//
// $CFG->session_handler_class = '\core\session\file';
// $CFG->session_file_save_path = $CFG->dataroot.'/sessions';
//
// ------------------------------------------------------------------------
// Memcached
// Requires a Memcached server and for the Memcached PHP extension to be available.
// It is important that you use a dedicated Memcached server for session storage. Do not configure anything else, either
// inside Totara or outside to use the same server. Memcached purges affect all stored data, and other systems using
// the same memcached server may unintentionally purge all session data.
//
// $CFG->session_handler_class = '\core\session\memcached';
// $CFG->session_memcached_save_path = '127.0.0.1:11211';
// $CFG->session_memcached_prefix = 'memc.sess.key.';
// $CFG->session_memcached_acquire_lock_timeout = 120;
// $CFG->session_memcached_lock_expire = 7200;
// $CFG->session_memcached_lock_retry_sleep = 150;   // ms
//
// ------------------------------------------------------------------------
// Native Redis
// Requires a Redis server, and for the Redis PHP extension to be available (version >= 5.0.0)
// The Redis server SET command must support EX and NX options.
// See https://github.com/phpredis/phpredis#php-session-handler for more information.
//
// $CFG->session_handler_class = '\core\session\redis5';
// $CFG->session_redis5_host = '127.0.0.1';              // Optional.
// $CFG->session_redis5_port = 6379;                     // Optional.
// $CFG->session_redis5_timeout = 5;                     // Optional (seconds).
// $CFG->session_redis5_database = 0;                    // Optional, database number.
// $CFG->session_redis5_auth = '';                       // Optional, password.
// $CFG->session_redis5_prefix = 'PHPREDIS_SESSION';     // Optional.
// $CFG->session_redis5_lock_expire = 7200;              // Optional, default is $CFG->sessiontimeout (seconds).
// $CFG->session_redis5_lock_wait_time = 200000;         // Optional, default is 0.2s (microseconds).
// $CFG->session_redis5_lock_retries = 100;              // Optional, default is 100 times.
// $CFG->session_redis5_serializer_use_igbinary = false; // Optional, default is PHP built-in serializer.
// $CFG->session_redis5_sentinel_hosts = '127.0.0.1,[::1]:26793'; // Optional, list of comma separated Sentinel hosts optionally with port number.
// $CFG->session_redis5_sentinel_master = 'mymaster';    // Name of Redis master, required if Sentinel hosts specified.
// $CFG->session_redis5_sentinel_auth = '';              // Optional, Sentinel password.
//
// Single master setup is required; RedisArray and RedisCluster environments are not currently supported.
//
// ------------------------------------------------------------------------
// Redis (legacy)
// Requires a Redis server, and for the Redis PHP Extension to be available (version >= 2.0.0)
// The Redis server SET command must support EX.
//
// $CFG->session_handler_class = '\core\session\redis';
// $CFG->session_redis_host = '127.0.0.1';
// $CFG->session_redis_port = 6379;  // Optional.
// $CFG->session_redis_database = 0;  // Optional, default is db 0.
// $CFG->session_redis_auth = ''; // Optional, default is don't set one.
// $CFG->session_redis_prefix = ''; // Optional, default is don't set one.
// $CFG->session_redis_acquire_lock_timeout = 120;
// $CFG->session_redis_lock_expire = 7200;
// $CFG->session_redis_lock_retry = 100; // ms
//
// The igbinary serialised can be used instead of the default serialiser provided by PHP if you want.
// If you change the serialiser setting you must flush the database.
//
// $CFG->session_redis_serializer_use_igbinary = true;
//
// ------------------------------------------------------------------------
// Memcache
// Requires a memcached server and the memcache PHP extension.
// It is important that you use a dedicated Memcached server for session storage. Do not configure anything else, either
// inside Totara or outside to use the same server. Memcached purges affect all stored data, and other systems using
// the same memcached server may unintentionally purge all session data.
// This session handler has been deprecated and will be removed in Totara 14. We recommend you use the memcached session
// handler instead.
//
// $CFG->session_handler_class = '\core\session\memcache';
// $CFG->session_memcache_save_path = '127.0.0.1:11211';
// $CFG->session_memcache_acquire_lock_timeout = 120;
//
// ------------------------------------------------------------------------
// Other session settings
//
// Totara stores the time a session was modified in the database. How frequently this happens can be configured.
// $CFG->session_update_timemodified_frequency = 20; // In seconds.
//
// For performance reasons some scripts (such as file serving) are by default allowed to acquire
// read-only sessions without locking. You can disable this feature by uncommenting following line:
// $CFG->allow_lockless_readonly_sessions = false;
//
// Track the current users IP and ensure it does not change during a session.
// This helps prevent the possibility of sessions being hijacked by XSS, but may cause problems for users coming through
// proxies that change the IP address regularly.
// $CFG->tracksessionip = true;
//

/***********************************************************************************************************************
Mail
***********************************************************************************************************************/
//
// The following settings alter the rules applied when handling email bounces
// $CFG->handlebounces = true;
// $CFG->minbounces = 10;
// $CFG->bounceratio = 0.20;
//
// Some mail systems require prefixes be used in outgoing mail in order to handle bounces. The prefix required will
// depend upon the system that you use.
// $CFG->mailprefix = 'ttr+'; // Exim and Postfix
// $CFG->mailprefix = 'ttr-'; // Qmail
// $CFG->maildomain = 'example.com';
//
// Notify of database connection errors by email to the following address.
// $CFG->emailconnectionerrorsto = 'errors@example.com';
//
// Force Totara to use a real user account as the noreply user when sending mail. By default Totara will create a dummy
// user for this purpose and use the configured noreply address.
// $CFG->noreplyuserid = -10;
//
// Force Totara to use a real user account as the support user when sending mail. By default Totara will create a dummy
// user for this purpose and use the configured support email address.
// $CFG->supportuserid = -20;


/***********************************************************************************************************************
JavaScript and CSS
***********************************************************************************************************************/
//
// Enable slash arguments when serving YUI JavaScript.
// This may improve caching and performance in some environments, although may require special rewrite rules in order to
// work around path length limitations in some systems. RewriteRule (^.*/theme/yui_combo\.php)(/.*) $1?file=$2
// $CFG->yuislasharguments = 1;
//

/***********************************************************************************************************************
Locking
***********************************************************************************************************************/
//
// Critical tasks often acquire locks to ensure that processed do not collide.
// Out of the box Totara will use the database to manager locking if you are using PostgreSQL, and the file system to
// manage locking for all other sites. The file system is often a poor choice to manage locking for large scale sites
// and we recommend an alternative lock manager is chosen.
// Locking should be tested using lib/tests/other/lockingtestpage.php page.
// The following are lock managers are available:
//
// $CFG->lock_factory = "auto";
// $CFG->lock_factory = "\\core\\lock\\file_lock_factory";
// $CFG->lock_factory = "\\core\\lock\\postgres_lock_factory"; // DB locking based on postgres advisory locks.
// $CFG->lock_factory = "\\core\\lock\\mysql_lock_factory"; // DB locking based on MySQL/MariaDB locks.
// $CFG->lock_factory = "\\core\\lock\\mssql_lock_factory"; // DB locking based on MS SQL Server application locks.
//
// The following factory has been deprecated in Totara 13. We strongly recommend you use the correct locking factory for
// your database from the options above.
// $CFG->lock_factory = "\\core\\lock\\db_record_lock_factory";
//
// File system locking
// Configure the directory in which locks are created. This must exist and be shared across all web servers if your site
// is scaled horizontally.
// $CFG->lock_file_root = $CFG->dataroot . '/lock';
//

/***********************************************************************************************************************
Caching
***********************************************************************************************************************/
//
// Custom cache configuration file path
// Totara stores cache configuration information within a file in the site data directory by default.
// The location of this file can be changed using the following setting.
// $CFG->altcacheconfigpath = '/var/common/shared.cache.config.php
//

/***********************************************************************************************************************
Themes
***********************************************************************************************************************/
//
// Set an additional directory in which themes can exist. This can be outside of the source directory root, but must be
// readable by the web server user. It does not need to be accesible via the web.
// $CFG->themedir = '/location/of/extra/themes';
//
// Set the order in which theme selection occurs.
// Priority is set from highest to lowest. Any theme selection point not included in this list is essentially disabled.
// Individual selection points such as user, and course still need to be enabled in the product.
// $CFG->themeorder = ['course', 'category', 'session', 'user', 'site'];
//
// Enable theme designer mode
// Styles will no longer be cached. This will greatly slow down your site performance, but will make theme development
// much easier when working with older technologies.
// $CFG->themedesignermode = true;
//
// Enable TUI designer mode
// TUI designer mode operates similarly to theme designer mode but only impacts code using the new TUI framework.
// Enabling TUI designer mode but not theme designer mode should be sufficient to develop with the new framework,
// but will be significantly faster than enabling the full theme designer mode.
// $CFG->tuidesignermode = true;
//
// Disable all caching for TUI development
// Again this should only be used when developing for TUI, but can help prevent caching of changes during development.
// $CFG->forced_plugin_settings['totara_tui'] = ['cache_js' => false, 'cache_scss' => false, 'development_mode' => true];
//

/***********************************************************************************************************************
Backup and restore
 ***********************************************************************************************************************/
//
// Forcibly prevent the creation of user accounts when restoring backups.
// This setting overrides any capabilities and permissions otherwise granted to the user. Attempting to restore a course
// containing users that would require creation will lead to an error.
// $CFG->disableusercreationonrestore = true;
//
// Keep temporary directories used to facilitate backup and restore after the process has been completed.
// These directories will be kept on disk and cleaned up periodically by cron.
// This setting is useful when debugging backup and restore problems.
// $CFG->keeptempdirectoriesonbackup = true;
//
// Produce zip files rather than gzipped tar files (.tgz)
// Changes the default archiving format for backups. Does not affect restore.
// $CFG->usezipbackups = true;
//

/***********************************************************************************************************************
Block and page settings
***********************************************************************************************************************/
//
// Set the default blocks that get added to all newly created courses.
// This forces the default blocks for all course formats. We recommend you set the default blocks per course
// format using the settings below this one.
// $CFG->defaultblocks_override = 'participants,activity_modules,search_forums,course_list:news_items,calendar_upcoming,recent_activity';
//
// Set the default blocks that get added to all newly created courses of a certain format.
// $CFG->defaultblocks_site = 'site_main_menu,course_list:course_summary,calendar_month';
// $CFG->defaultblocks_social = 'participants,search_forums,calendar_month,calendar_upcoming,social_activities,recent_activity,course_list';
// $CFG->defaultblocks_topics = 'participants,activity_modules,search_forums,course_list:news_items,calendar_upcoming,recent_activity';
// $CFG->defaultblocks_weeks = 'participants,activity_modules,search_forums,course_list:news_items,calendar_upcoming,recent_activity';
//
// Set the default blocks that get added to a newly created course of any other format.
// $CFG->defaultblocks = 'participants,activity_modules,search_forums,course_list:news_items,calendar_upcoming,recent_activity';
//

/***********************************************************************************************************************
Report Builder
***********************************************************************************************************************/
//
// Configure a read only database for Report Builder to use
// These settings enable you to configure a second database connection that will be used by Report Builder when generating
// report. This can improve performance of report builder and lower the load on the main database server.
// $CFG->clone_dbname = 'totara_clone';
// $CFG->clone_dbhost = $CFG->dbhost;
// $CFG->clone_dbuser = $CFG->dbuser;
// $CFG->clone_dbpass = $CFG->dbpass;
// $CFG->clone_dboptions = $CFG->dboptions;
//

/***********************************************************************************************************************
Grid catalog settings
***********************************************************************************************************************/
//
// Override the full text search relevance weighting applied to relevance columns when searching.
// $CFG->catalogrelevanceweight = ['high' => 16, 'medium' => 4 , 'low' => 1];
//
// Enable cascading content when storing data about learning items that will be indexed and searched up.
// When enabled the high value content column will contain high value, the medium will contain high and medium, and the
// low value content will include high, medium and low value content.
// In databases that apply implicit "AND" behaviour across columns during a full text search this will ensure that if
// the user searches for two words, which only appear across two buckets, that results will still be returned.
// At the time of writing this, this setting is applicable to PgSQL and MSSQL only.
// After changing this setting you will need to run:
// php server/totara/catalog/cli/populate_catalog_data.php --purge_catalog_first
// $CFG->catalog_use_and_compatible_buckets = true;
//
// Enable multi-linguage alphabetical sorting.
// When viewing the grid catalog on a site with only a single language installed the user will be able to order the
// results alphabetically. If multiple languages are installed however this option is removed as sorting multi-language
// content is unreliable and will not provide the desired results in most cases.
// The following setting will force the alphabetical option to appear regardless of the number of languages installed.
// $CFG->catalog_enable_alpha_sorting_with_multiple_languages = true;
//

/***********************************************************************************************************************
General settings
***********************************************************************************************************************/
//
// Set the default language for the site. This can be set through the admin user interface.
// $CFG->lang = 'en';
//
// When undertaking an operation that is expected to be resource intensive Totara will raise the memory limit available
// to PHP. This setting allows you to control how much memory is made available.
// The value must be a valid PHP memory value.
// $CFG->extramemorylimit = '1024M';
//
// Configure a key which must be provided in order to upgrade a site via the web interfaces after the code has been
// upgraded. This ensures only users who know the key can trigger a site upgrade through the web interfaces.
// We strongly recommend that you put your site into maintenance mode, and then upgrade via the command line interfaces.
// $CFG->upgradekey = 'ASecretOnlyYourSysAdminKnows';
//
// Configure the default quick access menu
// The quick access menu in Totara allows administrators to quickly access important links. They can customise their
// menu by adding and removing the items there.
// The following setting overrides the default menu that administrators will see before they begin customising it.
// Item keys are required and can currently only be found by inspecting the menu items in the browser.
// Group can be one of: platform, learn, engage, perform, configuration.
// $CFG->defaultquickaccessmenu = [
//    [
//        'key'    => 'item_key_1',
//        'group'  => 'platform', // Optional, defaults to 'learn'
//        'label'  => 'sometext', // Optional
//        'weight' => 1000        // Optional
//    ],
//    ['key' => 'item_key_2', 'group' => 'platform', 'label' => 'sometext', 'weight' => 2000],
//    ['key' => 'item_key_3', 'group' => 'learn', 'label' => 'sometext', 'weight' => 3000],
// ];
//
// Force a particular flavour of Totara
// $CFG->forceflavour = 'flavourname';
//
// Configure which flavours get shown on the feature overview screen
// $CFG->showflavours = 'flavourname,learn,learn_perform';
//

// Totara checks that cron is running frequently. If the time between cron runs
// is greater than this value (in seconds), you get a warning on the admin page. (This
// setting only controls whether or not the warning appears, it has no other effect.)
//
//      $CFG->expectedcronfrequency = 200;
//


/***********************************************************************************************************************
Advanced settings
***********************************************************************************************************************/
//
// Force plugin settings
// Individual plugin settings can be forced to a specific value within your config.php by using the following setting.
// $CFG->forced_plugin_settings = [
//     'plugin_name' => [
//         'setting_name_1' => 'value',
//         'setting_name_2' => 'value',
//     ],
//     'mod_facetoface' => [
//         'facetoface_approvaloptions' => 'approval_manager,approval_admin'
//     ];
// ];
//
// Prevent modifications to scheduled tasks through the user interfaces.
// $CFG->preventscheduledtaskchanges = true;
//
// Define new, or redefine existing supported file types.
// Extension, icon and type must be specified. All other fields are optional.
// $CFG->customfiletypes = [
//     (object)[
//         'extension' => 'frog',
//         'icon' => 'archive',
//         'type' => 'application/frog',
//         'customdescription' => 'Amphibian-related file archive'
//     ]
// ];
//
//
// Force firstname and lastname of users when displaying them
// This will anonymise all user names for all users who do not hold the capability to view user details.
// $CFG->forcefirstname = 'John';
// $CFG->forcelastname  = 'Smith';
//
// Limit the number of courses shown in the calendar when the Calendar Admin sees all setting has been turned on and
// the admin sets the filter for the calendar to "All courses". The default is 50.
// $CFG->calendar_adminallcourseslimit = 50;
//
// Fast hashing can be enabled to more quickly hash users' passwords when being
// imported via HR Import at the cost of security. Due to the nature of this setting
// we strongly recommend against enabling it.
// $CFG->tool_totara_sync_enable_fasthash = true;
//
// Path to Ghostsrcipt
// Used to generated images of pages for use in PDF's. Is required by the Annotate PDF functionality.
// $CFG->pathtogs = '/usr/bin/gs';
//
// Path to du (Disk Usage)
// Providing the path to du will enable its use. Performance of operations that look at the file system will likely
// improve if du is configured and used.
// $CFG->pathtodu = '/usr/bin/du';
//
// Path to dot
// Enables the generation of images from DOT files. Currently only required by the profiling tool.
// $CFG->pathtodot = '/usr/bin/dot';
//
// Prevent stats processing and remove all admin options from the user interfaces.
// $CFG->disablestatsprocessing = true;
//
// Disable fixing of numeric day values printed by the userdate() function.
// PHP will print a single digit day string prefixed with a 0, e.g. 7 => 07
// Totara removes the leading 0 when this happens in order to make the userdate more readable.
// If you want to disable this automatic fixing, the following setting can be used.
// $CFG->nofixday = true;
//
// Force the log report to use line graphs instead of bar graphs
// $CFG->preferlinegraphs = true;
//
// Path to the PHP binary to be used when executing commands using the PCNTL extension
// The PCNTL extension is available in unix based systems only and enables a more secure means of executing external
// programs from within Totara.
// The \core\copmmand\executable API, used consistently throughout Totara will make use of PCNTL when both the extension
// is enabled, and the following setting is provided.
// $CFG->pcntl_phpclipath = '/usr/bin/php';
//
// Whitelist third party executables
// Third party plugins that execute external programs from within Totara using the \core\copmmand\executable API require
// the executables they use be whitelisted, and Totara informed as to whether the program is executed via the client
// requests or via cli scripts. Third party plugins will inform of this in their instructions when required.
// True informs Totara the program will be executed from client requests, false indicates from cli scripts.
// $CFG->thirdpartyexeclist = array('/path/to/bin' => true, '/path/to/script.sh' => false);
//
// Set the path in which completion upload files exist on the server
// When users will be selecting completion import files to upload from the server itself this setting will need to be
// set to the directory that the completion files exist within. Sub directories can be used to further organise.
// $CFG->completionimportdir = '';
//
// The following two settings can be used to explicitly allow or block urls
// for our link parser. The link parser is used to get meta information about links
// added to the Weka editor and which can be shown either as card or embedded version (Youtube, etc.).
// The hostnames / IP addresses must be specified as a comma delimited string, i.e. 'www.example.com,123.34.12.1'
// $CFG->link_parser_allowed_hosts = '';
// $CFG->link_parser_blocked_hosts = '';
//
// This setting can be used to place a hard limit the number of records that can be returned from GraphQL pagination
// cursor. This will prevent excessively huge numbers of records being returned from a query which reduces load on the
// server. If this setting is not specified there will be no records limits applied.
// $CFG->graphqlcursorlimitmax = 1000

/***********************************************************************************************************************
Experimental settings
***********************************************************************************************************************/
//
// Introduce a URL rewriter class that can be used to in conjunction with an Apache or Nginx handler to rewrite outgoing
// urls as "clean" urls. The introduced class must implement \core\output\url_rewriter.
// The setting should be considered experimental as it may require other hacks in order to function as expected and may
// not be compatible with some features.
// $CFG->urlrewriteclass = '\local_cleanurls\url_rewriter';
//

/***********************************************************************************************************************
Deprecated settings
***********************************************************************************************************************/
//
// Disable the use of SVG images. Totara by default prefers SVG images. If you want to however disable the use of svg
// images then the following setting will allow you to achieve that. This setting is deprecated as some images are now
// only available in SVG. Deprecated in Totara 13.
// $CFG->svgicons = false;
//
// Totara 12 introduced a new site administration menu and removed the site administration branch in the administration
// block. If you want it back the following setting can be used. This setting has been deprecated as we do not intent to
// keep the old behaviour indefinitely. Deprecated in Totara 13.
// $CFG->legacyadminsettingsmenu = true;
//
// Restore course completion behaviour to that in Totara 2.7 for completed but failed activities. In Totara 2.7 and
// below a completed but failed activity completion does not count towards course completion. This behaviour chaned in
// Totara 2.9, and any activity completion (passed, failed, or not-specified) now counts towards course completion.
// This setting must be set BEFORE upgrading to Totara 2.9.0 or above. Deprecated in Totara 13.
// $CFG->completionexcludefailures = 1;
//
// Path to aspell
// Required in order to use spell check with old editors. This setting is no longer used by any core editors and
// was deprecated in Totara 13.
// $CFG->aspellpath = '';
//
// Export hierarchies in legacy format
// The export format of hierarchies changed in Totara 12. If you require the original format enabling this setting will
// ensure your export stays as it was.
// This setting has been deprecated and will be removed in a future verison.
// $CFG->hierarchylegacyexport = 1;
//
// Disable static maps when calculating visibility
// These maps are used to improve the performance of queries resolving visibility checks solely against the database.
// While tested extensively we theorised that there may be database configurations in which the databases query
// optimiser may not resolve to an optimal strategy when using these maps. Whenthe following setting is turned on the
// static maps will not be used, and instead the original resolvers will be used.
// This setting was deprecated in Totara 13 and will be removed in a future version.
//  $CFG->disable_visibility_maps = 0;
//

/***********************************************************************************************************************
Dangerous advanced settings
***********************************************************************************************************************/
//
// Theses settings are not recommended. Do not set these unless there is no other solution.
//
// Allow usernames to contain an extended set of characters.
// $CFG->extendedusernamechars = true;
//
// Export user passwords when backing up courses including users and user data.
// This setting is extremely dangerous.
// $CFG->includeuserpasswordsinbackup = true;
//
// Force restore to perform lighter user matching when restoring a backup produced in Totara 1.1 or below.
// Internally this causes Totara to treat the backup as though it is coming from a different site, meaning that user
// matching will be lighter, and more likely to match despite site differences.
// This is only useful for backups produced in very old versions of Totara 1.1 and below.
// $CFG->forcedifferentsitecheckingusersonrestore = true;
//
// Override the moodle_page class that is used to produce pages, and is made available as $PAGE.
// $CFG->moodlepageclass = 'moodle_page';
// $CFG->moodlepageclassfile = "$CFG->dirroot/local/myplugin/mypageclass.php";
//
// Override the block manager class that is used by the page to view and manage blocks.
// $CFG->blockmanagerclass = 'block_manager';
// $CFG->blockmanagerclassfile = "$CFG->dirroot/local/myplugin/myblockamanagerclass.php";
//
// Expose user information to Apache logs
// When enabled the configured user information will be exposed to Apache as "TOTARAUSER" and can be included in logs.
// This may expose otherwise secure information about your users. Use with caution.
// $CFG->apacheloguser = 0; // Off (default)
// $CFG->apacheloguser = 1; // Log user id.
// $CFG->apacheloguser = 2; // Log full name in cleaned format. ie, John Smith will be displayed as john_smith.
// $CFG->apacheloguser = 3; // Log username.
// In addition to setting this in your config.php you will also need to configure Apache to include the information in
// your log output, for example:
//   LogFormat "%h %l %{TOTARAUSER}n %t \"%r\" %s %b \"%{Referer}i\" \"%{User-Agent}i\"" totara_log_format
//   CustomLog "/your/path/to/log" totara_log_format
//
// Expose user information in HTTP header
// When enabled a header [X-TOTARAUSER] will be added to each page that includes the configured user information.
// This header is ideally stripped from the request by the web server and used in log output so that it never makes it
// to the end user. This may expose otherwise secure information about your users. Use with caution.
// $CFG->headerloguser = 0; // Off (default)
// $CFG->headerloguser = 1; // Log user id.
// $CFG->headerloguser = 2; // Log full name in cleaned format. ie, John Smith will be displayed as john_smith.
// $CFG->headerloguser = 3; // Log username.
//
// Disable file locking.
// Not all file systems support file locking. Some variations of NFS for example do not support it. Totara relies upon
// file locking to avoid race conditions and file state quality, particularly when under load. This setting should not
// be used on production sites.
// $CFG->preventfilelocking = false;
//
// Prevent executable paths from being changed in the user interface. When enabled this introduces a security risk.
// By default Totara requires paths to executable files required by the application to be specified in the config.php.
// When enabled this setting allows them to be changed by an administrator through the user interface.
// $CFG->preventexecpath = true;
//
// Disable CSRF protection on the login page.
// This is strongly discouraged due to its security implications. It may be useful when trying to get third party SSO
// authentication plugins working or if the deprecated alternative login URL setting is being used.
// $CFG->allowlogincsrf = 1;
//
// Enable custom script replacement of existing Totara scripts.
// If set, must be set to a directory that can be read by the web server. When set and a request is made to a script in
// Totara that requires config.php, the code will check if a script exists within the configured directory, in the same
// path as the script being served. If found the custom script will be used instead of the Totara script.
// This only works for files that include config.php, and for which the script filename is used in the request.
// For example https://examples.com/course/index.php will be resolved by /var/customscripts/course/index.php if it
// exists.
// The use of custom scripts like this is highly discouraged. It may introduce security vulnerabilities and may not be
// compatible with upgrades. Additionally you will not encounter conflicts if changes are made to the original script.
// $CFG->customscripts = '/home/example/customscripts';
//

/***********************************************************************************************************************
Developer settings
***********************************************************************************************************************/
//
// The following settings should not be used on production sites on a permanent basis. They are intended for debugging
// and diagnostics only. Enabling them lessens the security profile of a site.
//
// Enable debugging for the whole site:
// $CFG->debug = (E_ALL | E_STRICT);
// $CFG->debugdisplay = 1;
//
// Enable debugging just for a series of users.
// Comma separated user ids from the database. Does not require debugging be enabled for the whole site.
// $CFG->debugusers = '2,15';
//
// Force PHP To report and display errors.
// This should not be needed in normal circumstances; these values can also be set in your php.ini file.
// @error_reporting(E_ALL | E_STRICT);
// @ini_set('display_errors', '1');
//
// Enable debugging just for cron.
// There is no need to set this if you have set any of the above.
// $CFG->showcrondebugging = true;
//
// Disable JS caching
// Totara by default sets headers to ensure that JS is cached. During development or when debugging problems you may
// want to disable JS caching in order to ensure up-to-date JS files are used.
// $CFG->cachejs = false;
//
// Disable GraphQL schema caching
// Totara builds a complete schema file given the individual schema files for all components and plugins. During
// development you may want to disable this caching in order to ensure your schema changes are reflected in real time.
// $CFG->cache_graphql_schema = false;
//
// Disable lang string caching
// Totara caches compiled language string resolutions. During development you may want to disable this caching in order
// to see live changes to language strings.
// $CFG->langstringcache = false;
//
// Enable scheduled tasks to be changed to next run via the user interface
// When enabled a new action appears on the scheduled task interface to override the schedule of a scheduled task
// causing it to run when cron next runs.
// $CFG->debugallowscheduledtaskoverride = true;
//
// Enable GraphQL development mode
// This enables a development GraphQL endpoint which can be used to run arbitrary GraphQL queries from development tools.
// define('GRAPHQL_DEVELOPMENT_MODE', true);
//
// Capture performance information and display it in the page footer of every page (theme must support this)
// $CFG->perfdebug = 15;
//    OR
// define('MDL_PERF'  , true);
//
// Write performance information into the web server error log
// define('MDL_PERFTOLOG'  , true);
//
// DEPRECATED: Used to signal additional DB performance logging. This is no longer required setting perfdebug = 15 OR
// defining MDL_PERF will cause DB performance logging to occur.
// define('MDL_PERFDB'  , true);
//
// DEPRECATED: Causes performance information to be written to the footer of the page. This is no longer required
// setting perfdebug = 15 OR defining MDL_PERF will cause performance information to be printed in the footer.
// define('MDL_PERFTOFOOT', true);
//
// Totara also ships with a profiling tool. This tool requires the XHProf extension to be installed and available.
// Once enabled a new "profiling" node will be shown under development, in the site administration menu.
// $CFG->profilingenabled = true;
//
// Additionally early profiling can be enabled. This will cause further code to be profiling, including the setup
// of essential constructs such as the database connection, and loading configuration data.
// $CFG->earlyprofilingenabled = true;
//
// Early profiling requires the following configuration:
// Allow the current page to be profiled by added "PROFILEME" to the GET params.
// $CFG->profilingallowme = 1;
// When enabled you can start and stop profiling of all pages requested by the current user by adding one of the
// following keys to the GET params for a page: PROFILEALL or PROFILEALLSTOP
// $CFG->profilingallowall = 1;
// Used to enable random profiling at a set frequency, 100 (one in 100 pages will be profiled).
// $CFG->profilingautofrec = 100;
// Set to a pattern, if the current file path matches the pattern the request will be profiled.
// $CFG->profilingincluded = 'forum';
// Set to a pattern, if the current file path matches the pattern the request will NOT be profiled.
// $CFG->profilingexcluded = 'forum/view.php';
// When set profiling run information in the database will only be kept for X minutes
// $CFG->profilinglifetime = 10;
//
// Disable the sending of email.
// This is particularly useful when testing production data in a test environment.
// $CFG->noemailever = true;
//
// Force Totara to divert all email to a specified address.
// It is recommended to use a mail catcher as that will enable you to confirm the intended receiptient however in lieu
// of that this setting can be useful when debugging email.
// $CFG->divertallemailsto = 'developer@example.com';
//
// Divert all email as per $CFG->divertallemailsto unless it matches the set addresses
// Should be set to a comma separated list of addresses, or regular expressions to match the desired email addresses.
// $CFG->divertallemailsexcept = 'tester@example.com, developer(\+.*)?@example.com';
//
// Enable imap fetch debugging
// Causes verbose debug information to be printed when fetching email messages from an IMAP server.
// $CFG->debugimap = true;
//
// During upgrade print SQL statements when executing them
// $CFG->upgradeshowsql = true;
//
// During cron print SQL statements when executing them
// $CFG->showcronsql = true;
//
// Enable YUI JavaScript logging
// $CFG->yuiloglevel = 'debug';
//
// Configure which YUI modules log and which do not.
// $CFG->yuiloginclude = array(
//     'moodle-core-dock-loader' => true,
//     'moodle-course-categoryexpander' => true,
// );
// $CFG->yuilogexclude = array(
//     'moodle-core-dock' => true,
//     'moodle-core-notification' => true,
// );
//
// Set the password used when generating users through the generator tool
// $CFG->tool_generator_users_password = 'passw0rd!';
//
// Disable GraphQL schema caching on PHPUnit instantiation
// When instantiating PHPUnit Totara builds and caches a complete GraphQL schema file for all components and plugins. During
// development when running individual unit tests you may want to disable this caching in order to insstantiate the testing suite faste.
// define('PHPUNIT_NO_CACHE_GRAPHQL_SCHEMA', true);
//
// Determines the amount of information returned by an API response when an error occurs.
// Acceptable options are fixed and are made available from the global_api_config.php file:
// - ERROR_RESPONSE_LEVEL_NONE (0)
// - ERROR_RESPONSE_LEVEL_NORMAL (1)
// - ERROR_RESPONSE_LEVEL_DEVELOPER (2)
// Note that if an invalid number is set, it will be ignored and the default value (1) will be used instead.
// $CFG->forced_plugin_settings['totara_api'] = array(
//     'response_debug' =>  1
// );
//
// Determines whether to do Oauth2 authentication for access token for external API by pre-request watcher. This setting
// is only for totara Oauth2 specifically.
// $CFG->forced_plugin_settings['totara_api'] = array(
//     'disable_oauth2_authentication' => true
// );
//

// All done!
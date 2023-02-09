<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2020 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package core_config
 */

/**
 * Main Totara configuration.
 *
 * @property int $debug PHP debugging level
 * @property bool $debugdeveloper tru if developer mode is active
 * @property bool $debugdisplay must be disabled on production servers for security reasons
 * @property string $extramemorylimit memory size for pages that are known to require more memory
 * @property string $lang default site language
 * @property bool $preventexecpath prevent admins from setting program paths web wenb interface
 * @property bool $reverseproxy enable when reverse proxying or port forwarding
 * @property string $session_handler_class class name of session handler
 * @property bool $sslproxy enable when using external SSL proxy
 * @property string $xsendfile setting for offloading of file serving to web server
 * @property bool $program_message_prevent_resend_on_schedule_change Prevent resending legacy program messages on schedule change
 */
final class core_config extends stdClass {

    // NOTE: only add real properties for stuff that cannot be overridden from database,
    //       regular settings should be documented in main PHPDoc block above only.

    /** @var string main site web URL */
    public $wwwroot;

    /** @var string new top level code directory, contains all Totara code */
    public $srcroot;

    /** @var string old /server/ root for access to file from web */
    public $dirroot;

    /** @var string old library directory */
    public $libdir;

    /** @var string new top level libraries directory */
    public $libraries;

    /** @var string shared data files directory */
    public $dataroot;

    /** @var string shared temporary directory */
    public $tempdir;

    /** @var string shared cache directory */
    public $cachedir;

    /** @var string local cache directory */
    public $localcachedir;

    /** @var int permissions for new directories in dataroot */
    public $directorypermissions;

    /** @var int permissions for new directories in dataroot */
    public $filepermissions;

    /** @var int umask to be used when creating file and directories */
    public $umaskpermissions;

    /** @var array original main from config.php */
    public $config_php_settings;

    /** @var array force configuration for plugins */
    public $forced_plugin_settings;

    /** @var string available options are 'mysqli', 'mariadb', 'postgresql' or 'sqlsrv' */
    public $dbtype;

    /** @var string optional, defaults to 'native' */
    public $dblibrary;

    /** @var string database host */
    public $dbhost;

    /** @var string database name */
    public $dbname;

    /** @var string database user */
    public $dbuser;

    /** @var string database password */
    public $dbpass;

    /** @var string database table name prefix */
    public $prefix;

    /** @var array database options */
    public $dboptions;

    /**
     * @internal do not call directly
     * @param stdClass $cfg
     */
    public function __construct(stdClass $cfg) {
        foreach ((array)$cfg as $k => $v) {
            $this->{$k} = $v;
        }
    }
}

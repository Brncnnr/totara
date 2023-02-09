<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * * This file defines settingpages and externalpages under the "server" section
 * @package   core
 * @copyright 2006 Petr Skoda {@link http://skodak.org}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/** @var admin_root $ADMIN */
/** @var context_system $systemcontext */
/** @var bool $hassiteconfig */

// This file defines settingpages and externalpages under the "server" category
if ($hassiteconfig || has_capability('totara/oauth2:manageproviders', $systemcontext)) {
    // speedup for non-admins, add all caps used on this page
    // "System paths" settingpage
    $temp = new admin_settingpage('systempaths', new lang_string('systempaths', 'admin'));
    // "Path to du" setting
    $temp->add(
        new admin_setting_configexecutable(
            'pathtodu',
            new lang_string('pathtodu', 'admin'),
            new lang_string('configpathtodu', 'admin'),
            ''
        )
    );
    // 'Path to aspell' setting
    $temp->add(
        new admin_setting_configexecutable(
            'aspellpath',
            new lang_string('aspellpath', 'admin'),
            new lang_string('edhelpaspellpath'),
            ''
        )
    );
    // 'Path to dot' setting
    $temp->add(
        new admin_setting_configexecutable(
            'pathtodot',
            new lang_string('pathtodot', 'admin'),
            new lang_string('pathtodot_help', 'admin'),
            ''
        )
    );
    // 'Path to ghostscript' setting
    $temp->add(
        new admin_setting_configexecutable(
            'pathtogs',
            new lang_string('pathtogs', 'admin'),
            new lang_string('pathtogs_help', 'admin'),
            '/usr/bin/gs'
        )
    );
    // 'Path to wkhtmltopdf' setting
    $temp->add(
        new admin_setting_configexecutable(
            'pathtowkhtmltopdf',
            new lang_string('pathtowkhtmltopdf', 'totara_core'),
            new lang_string('pathtowkhtmltopdf_help', 'totara_core'),
            ''
        )
    );
    // Totara: it is not secure to use unoconv on servers!
    // $temp->add(
    //     new admin_setting_configexecutable(
    //         'pathtounoconv',
    //         new lang_string('pathtounoconv', 'admin'),
    //         new lang_string('pathtounoconv_help', 'admin'),
    //         '/usr/bin/unoconv'
    //     )
    // );
    $ADMIN->add('server', $temp);

    // "Support contact" settingpage
    $temp = new admin_settingpage('supportcontact', new lang_string('supportcontact','admin'));
    // 'Support name' setting
    $primaryadmin = get_admin();
    if ($primaryadmin) {
        $primaryadminemail = $primaryadmin->email;
        $primaryadminname  = fullname($primaryadmin, true);
    } else {
        // no defaults during installation - admin user must be created first
        $primaryadminemail = NULL;
        $primaryadminname  = NULL;
    }
    $temp->add(
        new admin_setting_configtext(
            'supportname',
            new lang_string('supportname', 'admin'),
            new lang_string('configsupportname', 'admin'),
            $primaryadminname,
            PARAM_NOTAGS
        )
    );
    // 'Support email' setting
    $setting = new admin_setting_configtext(
        'supportemail',
        new lang_string('supportemail', 'admin'),
        new lang_string('configsupportemail', 'admin'),
        $primaryadminemail,
        PARAM_EMAIL
    );
    $setting->set_force_ltr(true);
    // 'Support page' setting
    $temp->add(
        new admin_setting_configtext(
            'supportpage',
            new lang_string('supportpage', 'admin'),
            new lang_string('configsupportpage', 'admin'),
            '',
            PARAM_URL
        )
    );
    // 'Organisation name' setting
    $temp->add(
        new admin_setting_configtext(
            'orgname',
            new lang_string('orgname', 'admin'),
            new lang_string('orgnamehelp', 'admin'),
            '',
            PARAM_NOTAGS
        )
    );
    // 'Tech support email' setting
    $temp->add(
        new admin_setting_configtext(
            'techsupportemail',
            new lang_string('techsupportemail', 'admin'),
            new lang_string('techsupportemailhelp', 'admin'),
            '',
            PARAM_NOTAGS
        )
    );
    // 'Tech support phone number' setting
    $temp->add(
        new admin_setting_configtext(
            'techsupportphone',
            new lang_string('techsupportphone', 'admin'),
            new lang_string('techsupportphonehelp', 'admin'),
            '',
            PARAM_NOTAGS
        )
    );
    $temp->add($setting);
    // 'Support page' setting
    $temp->add(
        new admin_setting_configtext(
            'supportpage',
            new lang_string('supportpage', 'admin'),
            new lang_string('configsupportpage', 'admin'),
            '',
            PARAM_URL
        )
    );
    $ADMIN->add('systeminformation', $temp);

    // "Session handling" settingpage
    $temp = new admin_settingpage('sessionhandling', new lang_string('sessionhandling', 'admin'));
    /** @var moodle_database $DB */
    if (empty($CFG->session_handler_class) and $DB->session_lock_supported()) {
        // 'Use database for session information' setting
        $temp->add(
            new admin_setting_configcheckbox(
                'dbsessions',
                new lang_string('dbsessions', 'admin'),
                new lang_string('configdbsessions', 'admin'),
                0
            )
        );
    }
    // 'Timeout' setting
    $temp->add(
        new admin_setting_configselect(
            'sessiontimeout',
            new lang_string('sessiontimeout', 'admin'),
            new lang_string('configsessiontimeout', 'admin'),
            7200,
            array(
                28800 => new lang_string('numhours', 'moodle', 8),
                14400 => new lang_string('numhours', 'moodle', 4),
                10800 => new lang_string('numhours', 'moodle', 3),
                7200 => new lang_string('numhours', 'moodle', 2),
                5400 => new lang_string('numhours', 'moodle', '1.5'),
                3600 => new lang_string('numminutes', 'moodle', 60),
                2700 => new lang_string('numminutes', 'moodle', 45),
                1800 => new lang_string('numminutes', 'moodle', 30),
                900 => new lang_string('numminutes', 'moodle', 15),
                300 => new lang_string('numminutes', 'moodle', 5)
            )
        )
    );
    // 'Cookie prefix' setting
    $temp->add(
        new admin_setting_configtext(
            'sessioncookie',
            new lang_string('sessioncookie', 'admin'),
            new lang_string('configsessioncookie', 'admin'),
            '',
            PARAM_ALPHANUM
        )
    );
    // 'Cookie path' setting
    $temp->add(
        new admin_setting_configtext(
            'sessioncookiepath',
            new lang_string('sessioncookiepath', 'admin'),
            new lang_string('configsessioncookiepath', 'admin'),
            '',
            PARAM_RAW
        )
    );
    // 'Cookie domain' setting
    $temp->add(
        new admin_setting_configtext(
            'sessioncookiedomain',
            new lang_string('sessioncookiedomain', 'admin'),
            new lang_string('configsessioncookiedomain', 'admin'),
            '',
            PARAM_RAW,
            50
        )
    );
    $ADMIN->add('server', $temp);

    // "Stats" settingpage
    $temp = new admin_settingpage('stats', new lang_string('stats'), 'moodle/site:config', empty($CFG->enablestats));
    // 'Maximum processing interval' setting
    $temp->add(
        new admin_setting_configselect(
            'statsfirstrun',
            new lang_string('statsfirstrun', 'admin'),
            new lang_string('configstatsfirstrun', 'admin'),
            'none',
            array(
                'none' => new lang_string('none'),
                60*60*24*7 => new lang_string('numweeks', 'moodle', 1),
                60*60*24*14 => new lang_string('numweeks', 'moodle', 2),
                60*60*24*21 => new lang_string('numweeks', 'moodle', 3),
                60*60*24*28 => new lang_string('nummonths', 'moodle', 1),
                60*60*24*56 => new lang_string('nummonths', 'moodle', 2),
                60*60*24*84 => new lang_string('nummonths', 'moodle', 3),
                60*60*24*112 => new lang_string('nummonths', 'moodle', 4),
                60*60*24*140 => new lang_string('nummonths', 'moodle', 5),
                60*60*24*168 => new lang_string('nummonths', 'moodle', 6),
                'all' => new lang_string('all')
            )
        )
    );
    // 'Maximum runtime' setting
    $temp->add(
        new admin_setting_configselect(
            'statsmaxruntime',
            new lang_string('statsmaxruntime', 'admin'),
            new lang_string('configstatsmaxruntime3', 'admin'),
            0,
            array(
                0 => new lang_string('untilcomplete'),
                60*30 => '10 '.new lang_string('minutes'),
                60*30 => '30 '.new lang_string('minutes'),
                60*60 => '1 '.new lang_string('hour'),
                60*60*2 => '2 '.new lang_string('hours'),
                60*60*3 => '3 '.new lang_string('hours'),
                60*60*4 => '4 '.new lang_string('hours'),
                60*60*5 => '5 '.new lang_string('hours'),
                60*60*6 => '6 '.new lang_string('hours'),
                60*60*7 => '7 '.new lang_string('hours'),
                60*60*8 => '8 '.new lang_string('hours')
            )
        )
    );
    // 'Days to process' setting
    $temp->add(
        new admin_setting_configtext(
            'statsruntimedays',
            new lang_string('statsruntimedays', 'admin'),
            new lang_string('configstatsruntimedays', 'admin'),
            31,
            PARAM_INT
        )
    );
    // 'User threshold' setting
    $temp->add(
        new admin_setting_configtext(
            'statsuserthreshold',
            new lang_string('statsuserthreshold', 'admin'),
            new lang_string('configstatsuserthreshold', 'admin'),
            0,
            PARAM_INT
        )
    );
    $ADMIN->add('server', $temp);

    // "HTTP" settingpage
    $temp = new admin_settingpage('http', new lang_string('http', 'admin'));
    // 'Reverse proxy' setting
    $temp->add(
        new admin_setting_heading(
            'reverseproxy',
            new lang_string('reverseproxy', 'admin'),
            '',
            ''
        )
    );
    // 'Logged IP address source' setting
    $options = array(
        0 => 'HTTP_CLIENT_IP, HTTP_X_FORWARDED_FOR, REMOTE_ADDR',
        GETREMOTEADDR_SKIP_HTTP_CLIENT_IP => 'HTTP_X_FORWARDED_FOR, REMOTE_ADDR',
        GETREMOTEADDR_SKIP_HTTP_X_FORWARDED_FOR => 'HTTP_CLIENT, REMOTE_ADDR',
        GETREMOTEADDR_SKIP_HTTP_X_FORWARDED_FOR|GETREMOTEADDR_SKIP_HTTP_CLIENT_IP => 'REMOTE_ADDR'
    );
    $temp->add(
        new admin_setting_configselect(
            'getremoteaddrconf',
            new lang_string('getremoteaddrconf', 'admin'),
            new lang_string('configgetremoteaddrconf', 'admin'),
            GETREMOTEADDR_SKIP_HTTP_X_FORWARDED_FOR|GETREMOTEADDR_SKIP_HTTP_CLIENT_IP,
            $options
        )
    );
    // 'Ignore reverse proxies' setting
    $temp->add(
        new admin_setting_configtext(
            'reverseproxyignore',
            new lang_string('reverseproxyignore', 'admin'),
            new lang_string('configreverseproxyignore', 'admin'),
            ''
        )
    );
    // 'Web proxy' setting
    $temp->add(
        new admin_setting_heading(
            'webproxy',
            new lang_string('webproxy', 'admin'),
            new lang_string('webproxyinfo', 'admin')
        )
    );
    // 'Proxy host' setting
    $temp->add(
        new admin_setting_configtext(
            'proxyhost',
            new lang_string('proxyhost', 'admin'),
            new lang_string('configproxyhost', 'admin'),
            '',
            PARAM_HOST
        )
    );
    // 'Proxy port' setting
    $temp->add(
        new admin_setting_configtext(
            'proxyport',
            new lang_string('proxyport', 'admin'),
            new lang_string('configproxyport', 'admin'),
            0,
            PARAM_INT
        )
    );
    // 'Proxy type' setting
    $options = array('HTTP' => 'HTTP');
    if (defined('CURLPROXY_SOCKS5')) {
        $options['SOCKS5'] = 'SOCKS5';
    }
    $temp->add(
        new admin_setting_configselect(
            'proxytype',
            new lang_string('proxytype', 'admin'),
            new lang_string('configproxytype','admin'),
            'HTTP',
            $options
        )
    );
    // 'Proxy username' setting
    $temp->add(
        new admin_setting_configtext(
            'proxyuser',
            new lang_string('proxyuser', 'admin'),
            new lang_string('configproxyuser', 'admin'),
            ''
        )
    );
    // 'Proxy password' setting
    $temp->add(
        new admin_setting_configpasswordunmask(
            'proxypassword',
            new lang_string('proxypassword', 'admin'),
            new lang_string('configproxypassword', 'admin'),
            ''
        )
    );
    // 'Proxy bypass hosts' setting
    $temp->add(
        new admin_setting_configtext(
            'proxybypass',
            new lang_string('proxybypass', 'admin'),
            new lang_string('configproxybypass', 'admin'),
            'localhost, 127.0.0.1'
        )
    );
    $ADMIN->add('server', $temp);

    // 'Maintenance mode' settingpage
    $temp = new admin_settingpage('maintenancemode', new lang_string('sitemaintenancemode', 'admin'));
    // 'Maintenance mode' setting
    $options = array(
        0 => new lang_string('disable'),
        1 => new lang_string('enable')
    );
    $temp->add(
        new admin_setting_configselect(
            'maintenance_enabled',
            new lang_string('sitemaintenancemode', 'admin'),
            new lang_string('helpsitemaintenance', 'admin'),
            0,
            $options
        )
    );
    // 'Optional maintenance message' setting
    $temp->add(
        new admin_setting_confightmleditor(
            'maintenance_message',
            new lang_string('optionalmaintenancemessage', 'admin'),
            '',
            ''
        )
    );
    $ADMIN->add('server', $temp);

    // 'Cleanup' settingpage.
    $temp = new admin_settingpage('cleanup', new lang_string('cleanup', 'admin'));
    // 'Delete not fully setup users after' setting
    $temp->add(
        new admin_setting_configselect(
            'deleteunconfirmed',
            new lang_string('deleteunconfirmed', 'admin'),
            new lang_string('configdeleteunconfirmed', 'admin'),
            168,
            array(
                0 => new lang_string('never'),
                168 => new lang_string('numdays', 'moodle', 7),
                144 => new lang_string('numdays', 'moodle', 6),
                120 => new lang_string('numdays', 'moodle', 5),
                96 => new lang_string('numdays', 'moodle', 4),
                72 => new lang_string('numdays', 'moodle', 3),
                48 => new lang_string('numdays', 'moodle', 2),
                24 => new lang_string('numdays', 'moodle', 1),
                12 => new lang_string('numhours', 'moodle', 12),
                6 => new lang_string('numhours', 'moodle', 6),
                1 => new lang_string('numhours', 'moodle', 1)
            )
        )
    );
    // 'Delete incomplete users after' setting
    $temp->add(
        new admin_setting_configselect(
            'deleteincompleteusers',
            new lang_string('deleteincompleteusers', 'admin'),
            new lang_string('configdeleteincompleteusers', 'admin'),
            0,
            array(
                0 => new lang_string('never'),
                168 => new lang_string('numdays', 'moodle', 7),
                144 => new lang_string('numdays', 'moodle', 6),
                120 => new lang_string('numdays', 'moodle', 5),
                96 => new lang_string('numdays', 'moodle', 4),
                72 => new lang_string('numdays', 'moodle', 3),
                48 => new lang_string('numdays', 'moodle', 2),
                24 => new lang_string('numdays', 'moodle', 1)
            )
        )
    );
    // 'Delete course completion logs after' setting
    $temp->add(
        new admin_setting_configselect(
            'deletecompletionlogs',
            new lang_string('deletecompletionlogs', 'totara_core'),
            new lang_string('deletecompletionlogs_help', 'totara_core'),
            0,
            [
                0 => new lang_string('never'),
                365 * 7 => new lang_string('numyears', 'moodle', 7),
                365 * 5 => new lang_string('numyears', 'moodle', 5),
                365 * 3 => new lang_string('numyears', 'moodle', 3),
                365 * 1 => new lang_string('numyear', 'moodle', 1), // keep "1" for formatting and easy reading :)
            ]
        )
    );
    // 'Disable grade history' setting
    $temp->add(
        new admin_setting_configcheckbox(
            'disablegradehistory',
            new lang_string('disablegradehistory', 'grades'),
            new lang_string('disablegradehistory_help', 'grades'),
            0
        )
    );
    // 'Grade history lifetime' setting
    $temp->add(
        new admin_setting_configselect(
            'gradehistorylifetime',
            new lang_string('gradehistorylifetime', 'grades'),
            new lang_string('gradehistorylifetime_help', 'grades'),
            0,
            array(
                0 => new lang_string('neverdeletehistory', 'grades'),
                1000 => new lang_string('numdays', 'moodle', 1000),
                365 => new lang_string('numdays', 'moodle', 365),
                180 => new lang_string('numdays', 'moodle', 180),
                150 => new lang_string('numdays', 'moodle', 150),
                120 => new lang_string('numdays', 'moodle', 120),
                90 => new lang_string('numdays', 'moodle', 90),
                60 => new lang_string('numdays', 'moodle', 60),
                30 => new lang_string('numdays', 'moodle', 30)
            )
        )
    );
    // 'Clean up temporary data files older than' setting
    $temp->add(
        new admin_setting_configselect(
            'tempdatafoldercleanup',
            new lang_string('tempdatafoldercleanup', 'admin'),
            new lang_string('configtempdatafoldercleanup', 'admin'),
            168,
            array(
                1 => new lang_string('numhours', 'moodle', 1),
                3 => new lang_string('numhours', 'moodle', 3),
                6 => new lang_string('numhours', 'moodle', 6),
                9 => new lang_string('numhours', 'moodle', 9),
                12 => new lang_string('numhours', 'moodle', 12),
                18 => new lang_string('numhours', 'moodle', 18),
                24 => new lang_string('numhours', 'moodle', 24),
                48 => new lang_string('numdays', 'moodle', 2),
                168 => new lang_string('numdays', 'moodle', 7),
            )
        )
    );
    $ADMIN->add('server', $temp);

    // 'Environment' page
    $ADMIN->add(
        'server',
        new admin_externalpage(
            'environment',
            new lang_string('environment', 'admin'),
            "$CFG->wwwroot/$CFG->admin/environment.php"
        )
    );
    // 'PHP Info' page
    $ADMIN->add(
        'server',
        new admin_externalpage(
            'phpinfo',
            new lang_string('phpinfo'),
            "$CFG->wwwroot/$CFG->admin/phpinfo.php"
        )
    );

    // "Performance" settingpage
    $temp = new admin_settingpage('performance', new lang_string('performance', 'admin'));
    // 'Extra PHP memory limit' setting
    // Memory limit options for large administration tasks.
    $memoryoptions = array(
        '64M' => '64M',
        '128M' => '128M',
        '256M' => '256M',
        '512M' => '512M',
        '1024M' => '1024M',
        '2048M' => '2048M'
    );
    // Allow larger memory usage for 64-bit sites only.
    if (PHP_INT_SIZE === 8) {
        $memoryoptions['3072M'] = '3072M';
        $memoryoptions['4096M'] = '4096M';
    }
    $temp->add(
        new admin_setting_configselect(
            'extramemorylimit',
            new lang_string('extramemorylimit', 'admin'),
            new lang_string('configextramemorylimit', 'admin'),
            '512M',
            $memoryoptions
        )
    );
    // 'Maximum time limit' setting
    $temp->add(
        new admin_setting_configtext(
            'maxtimelimit',
            new lang_string('maxtimelimit', 'admin'),
            new lang_string('maxtimelimit_desc', 'admin'),
            0,
            PARAM_INT
        )
    );
    // 'cURL cache TTL' setting
    $temp->add(
        new admin_setting_configtext(
            'curlcache',
            new lang_string('curlcache', 'admin'),
            new lang_string('configcurlcache', 'admin'),
            120,
            PARAM_INT
        )
    );
    // 'Bitrate to use when calculating cURL timeouts (Kbps)' setting
    $temp->add(
        new admin_setting_configtext(
            'curltimeoutkbitrate',
            new lang_string('curltimeoutkbitrate', 'admin'),
            new lang_string('curltimeoutkbitrate_help', 'admin'),
            56,
            PARAM_INT
        )
    );
    // Totara performance settings.
    // 'Main menu cache life time' setting
    $options = array(
        0 => new lang_string('no'),
        1800 => new lang_string('numminutes', 'moodle', 30),
        1200 => new lang_string('numminutes', 'moodle', 20),
        600 => new lang_string('numminutes', 'moodle', 10),
        300 => new lang_string('numminutes', 'moodle', 5),
    );
    $temp->add(
        new admin_setting_configselect(
            'menulifetime',
            new lang_string('menulifetime', 'totara_core'),
            new lang_string('menulifetime_desc', 'totara_core'),
            '600',
            $options
        )
    );
    $ADMIN->add('server', $temp);

    // Totara: removed Moodle hubs registration.
    // E-mail settings.
    $ADMIN->add('server', new admin_category('email', new lang_string('categoryemail', 'admin')));
    // 'Outgoing mail configuration' settingpage
    $temp = new admin_settingpage('outgoingmailconfig', new lang_string('outgoingmailconfig', 'admin'));
    // 'SMTP' setting
    $temp->add(
        new admin_setting_heading(
            'smtpheading',
            new lang_string('smtp', 'admin'),
            new lang_string('smtpdetail', 'admin')
        )
    );
    // 'SMTP hosts' setting
    $temp->add(
        new admin_setting_configtext(
            'smtphosts',
            new lang_string('smtphosts', 'admin'),
            new lang_string('configsmtphosts', 'admin'),
            '',
            PARAM_RAW
        )
    );
    // 'SMTP security' setting
    $options = array(
        '' => new lang_string('none', 'admin'),
        'ssl' => 'SSL',
        'tls' => 'TLS'
    );
    $temp->add(
        new admin_setting_configselect(
            'smtpsecure',
            new lang_string('smtpsecure', 'admin'),
            new lang_string('configsmtpsecure', 'admin'),
            '',
            $options
        )
    );
    // 'SMTP Auth Type' setting
    $authtypeoptions = array(
        'LOGIN' => 'LOGIN',
        'PLAIN' => 'PLAIN',
        'NTLM' => 'NTLM',
        'CRAM-MD5' => 'CRAM-MD5',
        'XOAUTH2' => 'XOAUTH2'
    );
    $temp->add(
        new admin_setting_configselect(
            'smtpauthtype',
            new lang_string('smtpauthtype', 'admin'),
            new lang_string('configsmtpauthtype', 'admin'),
            'LOGIN',
            $authtypeoptions
        )
    );
    // 'Outgoing mail configuration' setting
    $temp->add(
        \core\xoauth2\helper::service_providers_configselect(
            'smtpoauth2issuer',
            new lang_string('outgoingmailconfig', 'admin')
        )
    );
    // 'SMTP username' setting
    $temp->add(
        new admin_setting_configtext(
            'smtpuser',
            new lang_string('smtpuser', 'admin'),
            new lang_string('configsmtpuser', 'admin'),
            '',
            PARAM_NOTAGS
        )
    );
    // 'SMTP password' setting
    $temp->add(
        new admin_setting_configpasswordunmask(
            'smtppass',
            new lang_string('smtppass', 'admin'),
            new lang_string('configsmtpuser', 'admin'),
            ''
        )
    );
    // 'SMTP session limit' setting
    $temp->add(
        new admin_setting_configtext(
            'smtpmaxbulk',
            new lang_string('smtpmaxbulk', 'admin'),
            new lang_string('configsmtpmaxbulk', 'admin'),
            1,
            PARAM_INT
        )
    );
    // 'No-reply and domain' setting
    $temp->add(
        new admin_setting_heading(
            'noreplydomainheading',
            new lang_string('noreplydomain', 'admin'),
            new lang_string('noreplydomaindetail', 'admin')
        )
    );
    // 'No-reply and address' setting
    $temp->add(
        new admin_setting_configtext(
            'noreplyaddress',
            new lang_string('noreplyaddress', 'admin'),
            new lang_string('confignoreplyaddress', 'admin'),
            'noreply@' . get_host_from_url($CFG->wwwroot),
            PARAM_EMAIL
        )
    );
    // 'Email display settings' setting
    $temp->add(
        new admin_setting_heading(
            'emaildoesnotfit',
            new lang_string('doesnotfit', 'admin'),
            new lang_string('doesnotfitdetail', 'admin')
        )
    );
    // 'Character set' setting
    $charsets = get_list_of_charsets();
    unset($charsets['UTF-8']); // Not needed here.
    $options = array();
    $options['0'] = 'UTF-8';
    $options = array_merge($options, $charsets);
    $temp->add(
        new admin_setting_configselect(
            'sitemailcharset',
            new lang_string('sitemailcharset', 'admin'),
            new lang_string('configsitemailcharset','admin'),
            '0',
            $options
        )
    );
    // 'Allow user to select character set' setting
    $temp->add(
        new admin_setting_configcheckbox(
            'allowusermailcharset',
            new lang_string('allowusermailcharset', 'admin'),
            new lang_string('configallowusermailcharset', 'admin'),
            0
        )
    );
    // 'Allow attachments' setting
    $temp->add(
        new admin_setting_configcheckbox(
            'allowattachments',
            new lang_string('allowattachments', 'admin'),
            new lang_string('configallowattachments', 'admin'),
            1
        )
    );
    // 'Newline characters in mail' setting
    $options = array('LF' => 'LF', 'CRLF' => 'CRLF');
    $temp->add(
        new admin_setting_configselect(
            'mailnewline',
            new lang_string('mailnewline', 'admin'),
            new lang_string('configmailnewline', 'admin'),
            'LF',
            $options
        )
    );
    // 'Email via information' setting
    $temp->add(
        new admin_setting_configcheckbox(
            'emailfromvia',
            new lang_string('emailfromvia', 'admin'),
            new lang_string('configemailfromvia', 'admin'),
            0
        )
    );
    $ADMIN->add('email', $temp);
    // Add new oauth2 category under the "server" category
    $ADMIN->add('server', new admin_category('oauth2services', new lang_string('oauth2services', 'admin')));
} // end of speedup

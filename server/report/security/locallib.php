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
 * Lib functions
 *
 * @package    report
 * @subpackage security
 * @copyright  2008 petr Skoda
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;


define('REPORT_SECURITY_OK', 'ok');
define('REPORT_SECURITY_INFO', 'info');
define('REPORT_SECURITY_WARNING', 'warning');
define('REPORT_SECURITY_SERIOUS', 'serious');
define('REPORT_SECURITY_CRITICAL', 'critical');

function report_security_hide_timearning() {
     global $PAGE;
     $PAGE->requires->js_init_code("Y.one('#timewarning').addClass('timewarninghidden')");
}

function report_security_get_issue_list() {
    global $CFG;

    $result = array(
        'report_security_check_unsecuredataroot',
        'report_security_check_displayerrors',
        'report_security_check_nodemodules',
        'report_security_check_noauth',
        'report_security_check_openprofiles',
        'report_security_check_google',
        'report_security_check_passwordpolicy',
        'report_security_check_emailchangeconfirmation',
        'report_security_check_usernameenumeration',
        'report_security_check_https',
        'report_security_check_cookiesecure',
        'report_security_check_cookiehttponly',
        'report_security_check_persistentlogin',
        'report_security_check_scormsessionkeepalive',
        'report_security_check_configrw',
        'report_security_check_riskallowxss',
        'report_security_check_resourcesallowxss',
        'report_security_check_resourcesallowpdfembedding',
        'report_security_check_riskxss',
        'report_security_check_logincsrf',
        'report_security_check_riskadmin',
        'report_security_check_riskbackup',
        'report_security_check_defaultuserrole',
        'report_security_check_guestrole',
        'report_security_check_frontpagerole',
        'report_security_check_webcron',
        'report_security_check_guest',
        'report_security_check_repositoryurl',
        'report_security_check_xxe_risk',
        'report_security_check_preventexecpath',
        'report_security_check_disableconsistentcleaning',
        'report_security_check_devgraphql',
        'report_security_check_oauth2verify',
    );

    $result = array_flip($result);
    if (empty($CFG->disableconsistentcleaning)) {
        unset($result['report_security_check_riskxss']);
        if (get_config('resource', 'allowxss')) {
            unset($result['report_security_check_resourcesallowpdfembedding']);
        }
    } else {
        unset($result['report_security_check_riskallowxss']);
        unset($result['report_security_check_resourcesallowxss']);
        unset($result['report_security_check_resourcesallowpdfembedding']);
    }
    $result = array_flip($result);

    return $result;
}

function report_security_doc_link($issue, $name) {
    global $CFG, $OUTPUT;

    if (empty($CFG->docroot)) {
        return $name;
    }

    return $OUTPUT->doc_link('report/security/'.$issue, $name);
}

///=============================================
///               Issue checks
///=============================================


/**
 * Verifies unsupported noauth setting
 * @param bool $detailed
 * @return object result
 */
function report_security_check_noauth($detailed=false) {
    global $CFG;

    $result = new stdClass();
    $result->issue   = 'report_security_check_noauth';
    $result->name    = get_string('check_noauth_name', 'report_security');
    $result->info    = null;
    $result->details = null;
    $result->status  = null;
    $result->link    = null;
    $result->link    = "<a href=\"$CFG->wwwroot/$CFG->admin/settings.php?section=manageauths\">".get_string('authsettings', 'admin').'</a>';

    if (is_enabled_auth('none')) {
        $result->status = REPORT_SECURITY_CRITICAL;
        $result->info   = get_string('check_noauth_error', 'report_security');
    } else {
        $result->status = REPORT_SECURITY_OK;
        $result->info   = get_string('check_noauth_ok', 'report_security');
    }

    if ($detailed) {
        $result->details = get_string('check_noauth_details', 'report_security');
    }

    return $result;
}

/**
 * Verifies if password policy set
 * @param bool $detailed
 * @return object result
 */
function report_security_check_passwordpolicy($detailed=false) {
    global $CFG;

    $result = new stdClass();
    $result->issue   = 'report_security_check_passwordpolicy';
    $result->name    = get_string('check_passwordpolicy_name', 'report_security');
    $result->info    = null;
    $result->details = null;
    $result->status  = null;
    $result->link    = "<a href=\"$CFG->wwwroot/$CFG->admin/settings.php?section=sitepolicies\">".get_string('sitepolicies', 'admin').'</a>';

    if (empty($CFG->passwordpolicy)) {
        $result->status = REPORT_SECURITY_WARNING;
        $result->info   = get_string('check_passwordpolicy_error', 'report_security');
    } else {
        $result->status = REPORT_SECURITY_OK;
        $result->info   = get_string('check_passwordpolicy_ok', 'report_security');
    }

    if ($detailed) {
        $result->details = get_string('check_passwordpolicy_details', 'report_security');
    }

    return $result;
}

/**
 * Test if registerauth has been enabled or if protect usernames has been turned off and warn about possible user enumeration.
 *
 * @param bool $detailed
 * @return stdClass
 */
function report_security_check_usernameenumeration($detailed = false) {
    global $CFG;

    $result = new stdClass();
    $result->issue   = __FUNCTION__;
    $result->name    = get_string('check_usernameenumeration_name', 'report_security');
    $result->info    = null;
    $result->details = null;
    $result->status  = null;
    $result->link    = "<a href=\"$CFG->wwwroot/$CFG->admin/settings.php?section=manageauths\">".get_string('authsettings', 'admin').'</a>';

    // We only check registerauth, we don't check that if its set the plugin actually facilitates it.
    // This is because the plugin may have hidden logic, really if registerauth is set we can presume that "someone" can signup.
    // We also check $CFG->protectusernames because that allows username enumeration as well.
    if (empty($CFG->registerauth) && !empty($CFG->protectusernames)) {
        $result->status = REPORT_SECURITY_OK;
        $result->info   = get_string('check_usernameenumeration_ok', 'report_security');
    } else {
        $result->status = REPORT_SECURITY_WARNING;
        $result->info   = get_string('check_usernameenumeration_warning', 'report_security');
    }

    if ($detailed) {
        $result->details = get_string('check_usernameenumeration_details', 'report_security');
    }

    return $result;
}

/**
 * Verifies fatal misconfiguration of dataroot
 * @param bool $detailed
 * @return object result
 */
function report_security_check_unsecuredataroot($detailed=false) {
    global $CFG;

    $result = new stdClass();
    $result->issue   = 'report_security_check_unsecuredataroot';
    $result->name    = get_string('check_unsecuredataroot_name', 'report_security');
    $result->info    = null;
    $result->details = null;
    $result->status  = null;
    $result->link    = null;

    $insecuredataroot = is_dataroot_insecure(true);

    if ($insecuredataroot == INSECURE_DATAROOT_WARNING) {
        $result->status = REPORT_SECURITY_SERIOUS;
        $result->info   = get_string('check_unsecuredataroot_warning', 'report_security', $CFG->dataroot);

    } else if ($insecuredataroot == INSECURE_DATAROOT_ERROR) {
        $result->status = REPORT_SECURITY_CRITICAL;
        $result->info   = get_string('check_unsecuredataroot_error', 'report_security', $CFG->dataroot);

    } else {
        $result->status = REPORT_SECURITY_OK;
        $result->info   = get_string('check_unsecuredataroot_ok', 'report_security');
    }

    if ($detailed) {
        $result->details = get_string('check_unsecuredataroot_details', 'report_security');
    }

    return $result;
}

/**
 * Verifies displaying of errors - problem for lib files and 3rd party code
 * because we can not disable debugging in these scripts (they do not include config.php)
 * @param bool $detailed
 * @return object result
 */
function report_security_check_displayerrors($detailed=false) {
    $result = new stdClass();
    $result->issue   = 'report_security_check_displayerrors';
    $result->name    = get_string('check_displayerrors_name', 'report_security');
    $result->info    = null;
    $result->details = null;
    $result->status  = null;
    $result->link    = null;

    if (defined('WARN_DISPLAY_ERRORS_ENABLED')) {
        $result->status = REPORT_SECURITY_WARNING;
        $result->info   = get_string('check_displayerrors_error', 'report_security');
    } else {
        $result->status = REPORT_SECURITY_OK;
        $result->info   = get_string('check_displayerrors_ok', 'report_security');
    }

    if ($detailed) {
        $result->details = get_string('check_displayerrors_details', 'report_security');
    }

    return $result;
}

/**
 * Verifies open profiles - originally open by default, not anymore because spammer abused it a lot
 * @param bool $detailed
 * @return object result
 */
function report_security_check_openprofiles($detailed=false) {
    global $CFG;

    $result = new stdClass();
    $result->issue   = 'report_security_check_openprofiles';
    $result->name    = get_string('check_openprofiles_name', 'report_security');
    $result->info    = null;
    $result->details = null;
    $result->status  = null;
    $result->link    = "<a href=\"$CFG->wwwroot/$CFG->admin/settings.php?section=sitepolicies\">".get_string('sitepolicies', 'admin').'</a>';

    if (empty($CFG->forcelogin) and empty($CFG->forceloginforprofiles)) {
        $result->status = REPORT_SECURITY_WARNING;
        $result->info   = get_string('check_openprofiles_error', 'report_security');
    } else {
        $result->status = REPORT_SECURITY_OK;
        $result->info   = get_string('check_openprofiles_ok', 'report_security');
    }

    if ($detailed) {
        $result->details = get_string('check_openprofiles_details', 'report_security');
    }

    return $result;
}

/**
 * Verifies google access not combined with disabled guest access
 * because attackers might gain guest access by modifying browser signature.
 * @param bool $detailed
 * @return object result
 */
function report_security_check_google($detailed=false) {
    global $CFG;

    $result = new stdClass();
    $result->issue   = 'report_security_check_google';
    $result->name    = get_string('check_google_name', 'report_security');
    $result->info    = null;
    $result->details = null;
    $result->status  = null;
    $result->link    = "<a href=\"$CFG->wwwroot/$CFG->admin/settings.php?section=sitepolicies\">".get_string('sitepolicies', 'admin').'</a>';

    if (empty($CFG->opentogoogle)) {
        $result->status = REPORT_SECURITY_OK;
        $result->info   = get_string('check_google_ok', 'report_security');
    } else if (!empty($CFG->guestloginbutton)) {
        $result->status = REPORT_SECURITY_INFO;
        $result->info   = get_string('check_google_info', 'report_security');
    } else {
        $result->status = REPORT_SECURITY_SERIOUS;
        $result->info   = get_string('check_google_error', 'report_security');
    }

    if ($detailed) {
        $result->details = get_string('check_google_details', 'report_security');
    }

    return $result;
}

/**
 * Verifies email confirmation - spammers were changing mails very often
 * @param bool $detailed
 * @return object result
 */
function report_security_check_emailchangeconfirmation($detailed=false) {
    global $CFG;

    $result = new stdClass();
    $result->issue   = 'report_security_check_emailchangeconfirmation';
    $result->name    = get_string('check_emailchangeconfirmation_name', 'report_security');
    $result->info    = null;
    $result->details = null;
    $result->status  = null;
    $result->link    = "<a href=\"$CFG->wwwroot/$CFG->admin/settings.php?section=sitepolicies\">".get_string('sitepolicies', 'admin').'</a>';

    if (empty($CFG->emailchangeconfirmation)) {
        if (empty($CFG->allowemailaddresses)) {
            $result->status = REPORT_SECURITY_WARNING;
            $result->info   = get_string('check_emailchangeconfirmation_error', 'report_security');
        } else {
            $result->status = REPORT_SECURITY_INFO;
            $result->info   = get_string('check_emailchangeconfirmation_info', 'report_security');
        }
    } else {
        $result->status = REPORT_SECURITY_OK;
        $result->info   = get_string('check_emailchangeconfirmation_ok', 'report_security');
    }

    if ($detailed) {
        $result->details = get_string('check_emailchangeconfirmation_details', 'report_security');
    }

    return $result;
}

/**
 * Verifies if https enabled only secure cookies allowed,
 * this prevents redirections and sending of cookies to unsecure port.
 * @param bool $detailed
 * @return object result
 */
function report_security_check_cookiesecure($detailed=false) {
    global $CFG;

    // Totara: show this always, not just on HTTPS sites.

    $result = new stdClass();
    $result->issue   = 'report_security_check_cookiesecure';
    $result->name    = get_string('check_cookiesecure_name', 'report_security');
    $result->info    = null;
    $result->details = null;
    $result->status  = null;
    $result->link    = "<a href=\"$CFG->wwwroot/$CFG->admin/settings.php?section=httpsecurity\">".get_string('httpsecurity', 'admin').'</a>';

    if (!is_moodle_cookie_secure()) {
        $result->status = REPORT_SECURITY_SERIOUS;
        $result->info   = get_string('check_cookiesecure_error', 'report_security');
    } else {
        $result->status = REPORT_SECURITY_OK;
        $result->info   = get_string('check_cookiesecure_ok', 'report_security');
    }

    if ($detailed) {
        $result->details = get_string('check_cookiesecure_details', 'report_security');
    }

    return $result;
}

/**
 * Verifies config.php is not writable anymore after installation,
 * config files were changed on several outdated server.
 * @param bool $detailed
 * @return object result
 */
function report_security_check_configrw($detailed=false) {
    global $CFG;

    $result = new stdClass();
    $result->issue   = 'report_security_check_configrw';
    $result->name    = get_string('check_configrw_name', 'report_security');
    $result->info    = null;
    $result->details = null;
    $result->status  = null;
    $result->link    = null;

    if (is_writable($CFG->dirroot.'/config.php')) {
        $result->status = REPORT_SECURITY_WARNING;
        $result->info   = get_string('check_configrw_warning', 'report_security');
    } else {
        $result->status = REPORT_SECURITY_OK;
        $result->info   = get_string('check_configrw_ok', 'report_security');
    }

    if ($detailed) {
        $result->details = get_string('check_configrw_details', 'report_security');
    }

    return $result;
}


/**
 * Lists all users with XSS risk, it would be great to combine this with risk trusts in user table,
 * unfortunately nobody implemented user trust UI yet :-(
 *
 * @since Totara 13.0
 *
 * @param bool $detailed
 * @return object result
 */
function report_security_check_riskallowxss($detailed=false) {
    global $DB;

    $result = new stdClass();
    $result->issue   = 'report_security_check_riskallowxss';
    $result->name    = get_string('check_riskallowxss_name', 'report_security');
    $result->info    = null;
    $result->details = null;
    $result->status  = REPORT_SECURITY_WARNING;
    $result->link    = null;

    $params = array('capallow'=>CAP_ALLOW);

    $sqlfrom = "FROM (SELECT rcx.*
                       FROM {role_capabilities} rcx
                       JOIN {capabilities} cap ON (cap.name = rcx.capability AND ".$DB->sql_bitand('cap.riskbitmask', RISK_ALLOWXSS)." <> 0)
                       WHERE rcx.permission = :capallow) rc,
                     {context} c,
                     {context} sc,
                     {role_assignments} ra,
                     {user} u
               WHERE c.id = rc.contextid
                     AND (sc.path = c.path OR sc.path LIKE ".$DB->sql_concat('c.path', "'/%'")." OR c.path LIKE ".$DB->sql_concat('sc.path', "'/%'").")
                     AND u.id = ra.userid AND u.deleted = 0
                     AND ra.contextid = sc.id AND ra.roleid = rc.roleid";

    $count = $DB->count_records_sql("SELECT COUNT(DISTINCT u.id) $sqlfrom", $params);

    $result->info = get_string('check_riskallowxss_warning', 'report_security', $count);

    if ($count === 0) {
        // Totara: no users means no warning, this is good for new installs.
        $result->status = REPORT_SECURITY_OK;
    }

    if ($detailed) {
        $userfields = user_picture::fields('u');
        $users = $DB->get_records_sql("SELECT DISTINCT $userfields $sqlfrom", $params);
        foreach ($users as $uid=>$user) {
            $users[$uid] = fullname($user);
        }
        $users = implode(', ', $users);
        $result->details = get_string('check_riskallowxss_details', 'report_security', $users);
    }

    return $result;
}

/**
 * Lists resources that have allowxss setting enabled.
 *
 * @since Totara 13.0
 *
 * @param bool $detailed
 * @return stdClass result
 */
function report_security_check_resourcesallowxss($detailed=false) {
    $result = new stdClass();
    $result->issue   = 'report_security_check_resourcesallowxss';
    $result->name    = get_string('check_resourcesallowxss_name', 'report_security');
    $result->info    = null;
    $result->details = null;
    $result->status  = REPORT_SECURITY_SERIOUS;
    $result->link    = null;

    $allresources = ['label', 'page', 'resource'];
    $dangerous = [];
    foreach ($allresources as $resource) {
        if (get_config($resource, 'allowxss')) {
            $dangerous[] = get_string('pluginname', $resource);
        }
    }

    if (!$dangerous) {
        $result->status = REPORT_SECURITY_OK;
        $result->info = get_string('check_resourcesallowxss_ok', 'report_security');
    } else {
        $result->info = get_string('check_resourcesallowxss_warning', 'report_security');
        $result->details = get_string('check_resourcesallowxss_details', 'report_security', implode(', ', $dangerous));
    }

    return $result;
}

/**
 * Warn if PDF embedding is enabled in mod_resourse.
 *
 * @since Totara 13.1
 *
 * @param bool $detailed
 * @return stdClass result
 */
function report_security_check_resourcesallowpdfembedding($detailed = false) {
    global $CFG;

    $result = new stdClass();
    $result->issue   = 'report_security_check_resourcesallowpdfembedding';
    $result->name    = get_string('check_resourcesallowpdfembedding_name', 'report_security');
    $result->info    = null;
    $result->details = null;
    $result->status  = REPORT_SECURITY_WARNING;
    $result->link    = "<a href=\"$CFG->wwwroot/$CFG->admin/settings.php?section=modsettingresource\">".get_string('pluginname', 'mod_resource').'</a>';

    if (get_config('resource', 'allowpdfembedding')) {
        $result->info = get_string('check_resourcesallowpdfembedding_warning', 'report_security');
        $result->details = get_string('check_resourcesallowpdfembedding_details', 'report_security');
    } else {
        $result->status = REPORT_SECURITY_OK;
        $result->info = get_string('check_resourcesallowpdfembedding_ok', 'report_security');
    }

    return $result;
}

/**
 * Lists all users with XSS risk, it would be great to combine this with risk trusts in user table,
 * unfortunately nobody implemented user trust UI yet :-(
 * @param bool $detailed
 * @return object result
 */
function report_security_check_riskxss($detailed=false) {
    global $DB;

    $result = new stdClass();
    $result->issue   = 'report_security_check_riskxss';
    $result->name    = get_string('check_riskxss_name', 'report_security');
    $result->info    = null;
    $result->details = null;
    $result->status  = REPORT_SECURITY_WARNING;
    $result->link    = null;

    $params = array('capallow'=>CAP_ALLOW);

    $sqlfrom = "FROM (SELECT rcx.*
                       FROM {role_capabilities} rcx
                       JOIN {capabilities} cap ON (cap.name = rcx.capability AND ".$DB->sql_bitand('cap.riskbitmask', RISK_XSS)." <> 0)
                       WHERE rcx.permission = :capallow) rc,
                     {context} c,
                     {context} sc,
                     {role_assignments} ra,
                     {user} u
               WHERE c.id = rc.contextid
                     AND (sc.path = c.path OR sc.path LIKE ".$DB->sql_concat('c.path', "'/%'")." OR c.path LIKE ".$DB->sql_concat('sc.path', "'/%'").")
                     AND u.id = ra.userid AND u.deleted = 0
                     AND ra.contextid = sc.id AND ra.roleid = rc.roleid";

    $count = $DB->count_records_sql("SELECT COUNT(DISTINCT u.id) $sqlfrom", $params);

    $result->info = get_string('check_riskxss_warning', 'report_security', $count);

    if ($count === 0) {
        // Totara: no users means no warning, this is good for new installs.
        $result->status = REPORT_SECURITY_OK;
    }

    if ($detailed) {
        $userfields = user_picture::fields('u');
        $users = $DB->get_records_sql("SELECT DISTINCT $userfields $sqlfrom", $params);
        foreach ($users as $uid=>$user) {
            $users[$uid] = fullname($user);
        }
        $users = implode(', ', $users);
        $result->details = get_string('check_riskxss_details', 'report_security', $users);
    }

    return $result;
}

/**
 * Makes sure that $CFG->allowlogincsrf is disabled.
 *
 * @param bool $detailed
 * @return object result
 */
function report_security_check_logincsrf($detailed=false) {
    global $CFG;

    $result = new stdClass();
    $result->issue   = 'report_security_check_logincsrf';
    $result->name    = get_string('check_logincsrf_name', 'report_security');
    $result->info    = null;
    $result->details = null;
    $result->link    = null;

    if (!empty($CFG->allowlogincsrf)) {
        $result->status = REPORT_SECURITY_SERIOUS;
        $result->info   = get_string('check_logincsrf_error', 'report_security');
    } else {
        $result->status = REPORT_SECURITY_OK;
        $result->info   = get_string('check_logincsrf_ok', 'report_security');
    }

    if ($detailed) {
        $result->details = get_string('check_logincsrf_details', 'report_security');
    }

    return $result;
}

/**
 * Verifies sanity of default user role.
 * @param bool $detailed
 * @return object result
 */
function report_security_check_defaultuserrole($detailed=false) {
    global $DB, $CFG;

    $result = new stdClass();
    $result->issue   = 'report_security_check_defaultuserrole';
    $result->name    = get_string('check_defaultuserrole_name', 'report_security');
    $result->info    = null;
    $result->details = null;
    $result->status  = null;
    $result->link    = "<a href=\"$CFG->wwwroot/$CFG->admin/settings.php?section=userpolicies\">".get_string('userpolicies', 'admin').'</a>';

    if (!$default_role = $DB->get_record('role', array('id'=>$CFG->defaultuserroleid))) {
        $result->status  = REPORT_SECURITY_WARNING;
        $result->info    = get_string('check_defaultuserrole_notset', 'report_security');
        $result->details = $result->info;

        return $result;
    }

    // risky caps - usually very dangerous
    $params = array('capallow'=>CAP_ALLOW, 'roleid'=>$default_role->id);
    $sql = "SELECT COUNT(DISTINCT rc.contextid)
              FROM {role_capabilities} rc
              JOIN {capabilities} cap ON cap.name = rc.capability
             WHERE ".$DB->sql_bitand('cap.riskbitmask', (RISK_XSS | RISK_CONFIG | RISK_DATALOSS))." <> 0
                   AND rc.permission = :capallow
                   AND rc.roleid = :roleid";

    $riskycount = $DB->count_records_sql($sql, $params);

    // it may have either none or 'user' archetype - nothing else, or else it would break during upgrades badly
    if ($default_role->archetype === '' or $default_role->archetype === 'user') {
        $legacyok = true;
    } else {
        $legacyok = false;
    }

    if ($riskycount or !$legacyok) {
        $result->status  = REPORT_SECURITY_CRITICAL;
        $result->info    = get_string('check_defaultuserrole_error', 'report_security', role_get_name($default_role));

    } else {
        $result->status  = REPORT_SECURITY_OK;
        $result->info    = get_string('check_defaultuserrole_ok', 'report_security');
    }

    if ($detailed) {
        $result->details = get_string('check_defaultuserrole_details', 'report_security');
    }

    return $result;
}

/**
 * Verifies sanity of guest role
 * @param bool $detailed
 * @return object result
 */
function report_security_check_guestrole($detailed=false) {
    global $DB, $CFG;

    $result = new stdClass();
    $result->issue   = 'report_security_check_guestrole';
    $result->name    = get_string('check_guestrole_name', 'report_security');
    $result->info    = null;
    $result->details = null;
    $result->status  = null;
    $result->link    = "<a href=\"$CFG->wwwroot/$CFG->admin/settings.php?section=userpolicies\">".get_string('userpolicies', 'admin').'</a>';

    if (!$guest_role = $DB->get_record('role', array('id'=>$CFG->guestroleid))) {
        $result->status  = REPORT_SECURITY_WARNING;
        $result->info    = get_string('check_guestrole_notset', 'report_security');
        $result->details = $result->info;

        return $result;
    }

    // risky caps - usually very dangerous
    $params = array('capallow'=>CAP_ALLOW, 'roleid'=>$guest_role->id);
    $sql = "SELECT COUNT(DISTINCT rc.contextid)
              FROM {role_capabilities} rc
              JOIN {capabilities} cap ON cap.name = rc.capability
             WHERE ".$DB->sql_bitand('cap.riskbitmask', (RISK_XSS | RISK_CONFIG | RISK_DATALOSS))." <> 0
                   AND rc.permission = :capallow
                   AND rc.roleid = :roleid";

    $riskycount = $DB->count_records_sql($sql, $params);

    // it may have either no or 'guest' archetype - nothing else, or else it would break during upgrades badly
    if ($guest_role->archetype === '' or $guest_role->archetype === 'guest') {
        $legacyok = true;
    } else {
        $legacyok = false;
    }

    if ($riskycount or !$legacyok) {
        $result->status  = REPORT_SECURITY_CRITICAL;
        $result->info    = get_string('check_guestrole_error', 'report_security', format_string($guest_role->name));

    } else {
        $result->status  = REPORT_SECURITY_OK;
        $result->info    = get_string('check_guestrole_ok', 'report_security');
    }

    if ($detailed) {
        $result->details = get_string('check_guestrole_details', 'report_security');
    }

    return $result;
}

/**
 * Verifies sanity of frontpage role
 * @param bool $detailed
 * @return object result
 */
function report_security_check_frontpagerole($detailed=false) {
    global $DB, $CFG;

    $result = new stdClass();
    $result->issue   = 'report_security_check_frontpagerole';
    $result->name    = get_string('check_frontpagerole_name', 'report_security');
    $result->info    = null;
    $result->details = null;
    $result->status  = null;
    $result->link    = "<a href=\"$CFG->wwwroot/$CFG->admin/settings.php?section=frontpagesettings\">".get_string('frontpagesettings','admin').'</a>';

    if (!$frontpage_role = $DB->get_record('role', array('id'=>$CFG->defaultfrontpageroleid))) {
        $result->status  = REPORT_SECURITY_INFO;
        $result->info    = get_string('check_frontpagerole_notset', 'report_security');
        $result->details = get_string('check_frontpagerole_details', 'report_security');

        return $result;
    }

    // risky caps - usually very dangerous
    $params = array('capallow'=>CAP_ALLOW, 'roleid'=>$frontpage_role->id);
    $sql = "SELECT COUNT(DISTINCT rc.contextid)
              FROM {role_capabilities} rc
              JOIN {capabilities} cap ON cap.name = rc.capability
             WHERE ".$DB->sql_bitand('cap.riskbitmask', (RISK_XSS | RISK_CONFIG | RISK_DATALOSS))." <> 0
                   AND rc.permission = :capallow
                   AND rc.roleid = :roleid";

    $riskycount = $DB->count_records_sql($sql, $params);

    // there is no legacy role type for frontpage yet - anyway we can not allow teachers or admins there!
    if ($frontpage_role->archetype === 'teacher' or $frontpage_role->archetype === 'editingteacher'
      or $frontpage_role->archetype === 'coursecreator' or $frontpage_role->archetype === 'manager') {
        $legacyok = false;
    } else {
        $legacyok = true;
    }

    if ($riskycount or !$legacyok) {
        $result->status  = REPORT_SECURITY_CRITICAL;
        $result->info    = get_string('check_frontpagerole_error', 'report_security', role_get_name($frontpage_role));

    } else {
        $result->status  = REPORT_SECURITY_OK;
        $result->info    = get_string('check_frontpagerole_ok', 'report_security');
    }

    if ($detailed) {
        $result->details = get_string('check_frontpagerole_details', 'report_security');
    }

    return $result;
}

/**
 * Lists all admins.
 * @param bool $detailed
 * @return object result
 */
function report_security_check_riskadmin($detailed=false) {
    global $DB, $CFG;

    $result = new stdClass();
    $result->issue   = 'report_security_check_riskadmin';
    $result->name    = get_string('check_riskadmin_name', 'report_security');
    $result->info    = null;
    $result->details = null;
    $result->status  = null;
    $result->link    = null;

    $userfields = user_picture::fields('u');
    $sql = "SELECT $userfields
              FROM {user} u
             WHERE u.id IN ($CFG->siteadmins)";

    $admins = $DB->get_records_sql($sql);
    $admincount = count($admins);

    if ($detailed) {
        foreach ($admins as $uid=>$user) {
            $url = "$CFG->wwwroot/user/profile.php?id=$user->id";
            // TOTARA - Escape potential XSS in user email.
            $admins[$uid] = '<li><a href="'.$url.'">'.fullname($user).' ('.clean_string($user->email).')</a></li>';
        }
        $admins = '<ul>'.implode('', $admins).'</ul>';
    }

    $result->status  = REPORT_SECURITY_OK;
    $result->info = get_string('check_riskadmin_ok', 'report_security', $admincount);

    if ($detailed) {
        $result->details = get_string('check_riskadmin_detailsok', 'report_security', $admins);
    }

    return $result;
}

/**
 * Lists all roles that have the ability to backup user data, as well as users
 * @param bool $detailed
 * @return object result
 */
function report_security_check_riskbackup($detailed=false) {
    global $CFG, $DB;

    $result = new stdClass();
    $result->issue   = 'report_security_check_riskbackup';
    $result->name    = get_string('check_riskbackup_name', 'report_security');
    $result->info    = null;
    $result->details = null;
    $result->status  = null;
    $result->link    = null;

    $syscontext = context_system::instance();

    $params = array('capability'=>'moodle/backup:userinfo', 'permission'=>CAP_ALLOW, 'contextid'=>$syscontext->id);
    $sql = "SELECT DISTINCT r.id, r.name, r.shortname, r.sortorder, r.archetype
              FROM {role} r
              JOIN {role_capabilities} rc ON rc.roleid = r.id
             WHERE rc.capability = :capability
               AND rc.contextid  = :contextid
               AND rc.permission = :permission";
    $systemroles = $DB->get_records_sql($sql, $params);

    $params = array('capability'=>'moodle/backup:userinfo', 'permission'=>CAP_ALLOW, 'contextid'=>$syscontext->id);
    $sql = "SELECT DISTINCT r.id, r.name, r.shortname, r.sortorder, r.archetype, rc.contextid
              FROM {role} r
              JOIN {role_capabilities} rc ON rc.roleid = r.id
             WHERE rc.capability = :capability
               AND rc.contextid <> :contextid
               AND rc.permission = :permission";
    $overriddenroles = $DB->get_records_sql($sql, $params);

    // list of users that are able to backup personal info
    // note: "sc" is context where is role assigned,
    //       "c" is context where is role overridden or system context if in role definition
    $params = array('capability'=>'moodle/backup:userinfo', 'permission'=>CAP_ALLOW, 'context1'=>CONTEXT_COURSE, 'context2'=>CONTEXT_COURSE);

    $sqluserinfo = "
        FROM (SELECT rcx.*
                FROM {role_capabilities} rcx
               WHERE rcx.permission = :permission AND rcx.capability = :capability) rc,
             {context} c,
             {context} sc,
             {role_assignments} ra,
             {user} u
       WHERE c.id = rc.contextid
             AND (sc.path = c.path OR sc.path LIKE ".$DB->sql_concat('c.path', "'/%'")." OR c.path LIKE ".$DB->sql_concat('sc.path', "'/%'").")
             AND u.id = ra.userid AND u.deleted = 0
             AND ra.contextid = sc.id AND ra.roleid = rc.roleid
             AND sc.contextlevel <= :context1 AND c.contextlevel <= :context2";

    $usercount = $DB->count_records_sql("SELECT COUNT('x') FROM (SELECT DISTINCT u.id $sqluserinfo) userinfo", $params);
    $systemrolecount = empty($systemroles) ? 0 : count($systemroles);
    $overriddenrolecount = empty($overriddenroles) ? 0 : count($overriddenroles);

    if (max($usercount, $systemrolecount, $overriddenrolecount) > 0) {
        $result->status = REPORT_SECURITY_WARNING;
    } else {
        $result->status = REPORT_SECURITY_OK;
    }

    $a = (object)array('rolecount'=>$systemrolecount,'overridecount'=>$overriddenrolecount,'usercount'=>$usercount);
    $result->info = get_string('check_riskbackup_warning', 'report_security', $a);

    if ($detailed) {

        $result->details = '';  // Will be added to later

        // Make a list of roles
        if ($systemroles) {
            $links = array();
            foreach ($systemroles as $role) {
                $role->name = role_get_name($role);
                $role->url = "$CFG->wwwroot/$CFG->admin/roles/manage.php?action=edit&amp;roleid=$role->id";
                $links[] = '<li>'.get_string('check_riskbackup_editrole', 'report_security', $role).'</li>';
            }
            $links = '<ul>'.implode($links).'</ul>';
            $result->details .= get_string('check_riskbackup_details_systemroles', 'report_security', $links);
        }

        // Make a list of overrides to roles
        $rolelinks2 = array();
        if ($overriddenroles) {
            $links = array();
            foreach ($overriddenroles as $role) {
                $role->name = $role->localname;
                $context = context::instance_by_id($role->contextid);
                $role->name = role_get_name($role, $context, ROLENAME_BOTH);
                $role->contextname = $context->get_context_name();
                $role->url = "$CFG->wwwroot/$CFG->admin/roles/override.php?contextid=$role->contextid&amp;roleid=$role->id";
                $links[] = '<li>'.get_string('check_riskbackup_editoverride', 'report_security', $role).'</li>';
            }
            $links = '<ul>'.implode('', $links).'</ul>';
            $result->details .= get_string('check_riskbackup_details_overriddenroles', 'report_security', $links);
        }

        // Get a list of affected users as well
        $users = array();

        list($sort, $sortparams) = users_order_by_sql('u');
        $userfields = user_picture::fields('u');
        $rs = $DB->get_recordset_sql("SELECT DISTINCT $userfields, ra.contextid, ra.roleid
            $sqluserinfo ORDER BY $sort", array_merge($params, $sortparams));

        foreach ($rs as $user) {
            $context = context::instance_by_id($user->contextid);
            $url = "$CFG->wwwroot/$CFG->admin/roles/assign.php?contextid=$user->contextid&amp;roleid=$user->roleid";
            // TOTARA - Escape potential XSS in user email.
            $a = (object)array('fullname'=>fullname($user), 'url'=>$url, 'email'=>clean_string($user->email),
                               'contextname'=>$context->get_context_name());
            $users[] = '<li>'.get_string('check_riskbackup_unassign', 'report_security', $a).'</li>';
        }
        if (!empty($users)) {
            $users = '<ul>'.implode('', $users).'</ul>';
            $result->details .= get_string('check_riskbackup_details_users', 'report_security', $users);
        }
    }

    return $result;
}

/**
 * Verifies the status of web cron
 *
 * @param bool $detailed
 * @return object result
 */
function report_security_check_webcron($detailed = false) {
    global $CFG;

    $croncli = $CFG->cronclionly;
    $cronremotepassword = $CFG->cronremotepassword;

    $result = new stdClass();
    $result->issue   = 'report_security_check_webcron';
    $result->name    = get_string('check_webcron_name', 'report_security');
    $result->details = null;
    $result->link    = "<a href=\"$CFG->wwwroot/$CFG->admin/settings.php?section=sitepolicies\">"
            .get_string('sitepolicies', 'admin').'</a>';

    if (empty($croncli) && empty($cronremotepassword)) {
        $result->status = REPORT_SECURITY_WARNING;
        $result->info   = get_string('check_webcron_warning', 'report_security');
    } else {
        $result->status = REPORT_SECURITY_OK;
        $result->info   = get_string('check_webcron_ok', 'report_security');
    }

    if ($detailed) {
        $result->details = get_string('check_webcron_details', 'report_security');
    }

    return $result;
}

/**
 * Verifies if https used in Totara.
 *
 * @param bool $detailed
 * @return stdClass result
 */
function report_security_check_https($detailed = false) {
    $result = new stdClass();
    $result->issue   = 'report_security_check_https';
    $result->name    = get_string('check_https_name', 'report_security');
    $result->details = null;
    $result->link    = '';

    if (!is_https()) {
        $result->status = REPORT_SECURITY_SERIOUS;
        $result->info   = get_string('check_https_warning', 'report_security');
    } else {
        $result->status = REPORT_SECURITY_OK;
        $result->info   = get_string('check_https_ok', 'report_security');
    }

    if ($detailed) {
        $result->details = get_string('check_https_details', 'report_security');
    }

    return $result;
}

/**
 * Verifies if real login required.
 *
 * @param bool $detailed
 * @return stdClass result
 */
function report_security_check_guest($detailed = false) {
    global $CFG;

    $result = new stdClass();
    $result->issue   = 'report_security_check_guest';
    $result->name    = get_string('check_guest_name', 'report_security');
    $result->details = null;
    $result->link    = "<a href=\"$CFG->wwwroot/$CFG->admin/settings.php?section=manageauths\">".get_string('authsettings', 'core_admin').'</a>';

    if (!empty($CFG->guestloginbutton)) {
        $result->status = REPORT_SECURITY_SERIOUS;
        $result->info   = get_string('check_guest_warning', 'report_security');
    } else {
        $result->status = REPORT_SECURITY_OK;
        $result->info   = get_string('check_guest_ok', 'report_security');
    }

    if ($detailed) {
        $result->details = get_string('check_guest_details', 'report_security');
    }

    return $result;
}

/**
 * Verifies if URL downloader repository enabled in Totara.
 *
 * @param bool $detailed
 * @return stdClass result
 */
function report_security_check_repositoryurl($detailed = false) {
    global $CFG;
    require_once($CFG->dirroot . '/repository/lib.php');

    $result = new stdClass();
    $result->issue = 'report_security_check_repositoryurl';
    $result->name = get_string('check_repositoryurl_name', 'report_security');
    $result->details = null;
    $result->link = '';

    $repositorytype = repository::get_type_by_typename('url');

    if ($repositorytype) {
        $result->status = REPORT_SECURITY_WARNING;
        $result->info = get_string('check_repositoryurl_warning', 'report_security');
    } else {
        $result->status = REPORT_SECURITY_OK;
        $result->info = get_string('check_repositoryurl_ok', 'report_security');
    }

    if ($detailed) {
        $result->details = get_string('check_repositoryurl_details', 'report_security');
    }

    return $result;
}

/**
 * Verifies if the httponly setting is enable for a site.
 *
 * @param bool $detailed
 * @return stdClass result
 */
function report_security_check_cookiehttponly($detailed = false) {
    global $CFG;

    $result = new stdClass();
    $result->issue   = 'report_security_check_cookiehttponly';
    $result->name    = get_string('check_cookiehttponly_name', 'report_security');
    $result->details = null;
    $result->link    = "<a href=\"$CFG->wwwroot/$CFG->admin/settings.php?section=httpsecurity\">".get_string('httpsecurity', 'admin').'</a>';

    if ($CFG->cookiehttponly == true) {
        $result->status = REPORT_SECURITY_OK;
        $result->info = get_string('check_cookiehttponly_ok', 'report_security');
    } else {
        $result->status = REPORT_SECURITY_SERIOUS;
        $result->info = get_string('check_cookiehttponly_error', 'report_security');
    }

    if ($detailed) {
        $result->details = get_string('check_cookiehttponly_details', 'report_security');
    }

    return $result;
}

/**
 * Verifies if the persistentloginenabled setting is enabled on this site.
 *
 * @param bool $detailed
 * @return stdClass result
 */
function report_security_check_persistentlogin($detailed = false) {
    global $CFG;

    $result = new stdClass();
    $result->issue   = 'report_security_check_persistentlogin';
    $result->name    = get_string('check_persistentlogin_name', 'report_security');
    $result->details = null;
    $result->link    = "<a href=\"$CFG->wwwroot/$CFG->admin/settings.php?section=sitepolicies\">".get_string('sitepolicies', 'admin').'</a>';

    if (empty($CFG->persistentloginenable)) {
        $result->status = REPORT_SECURITY_OK;
        $result->info = get_string('check_persistentlogin_ok', 'report_security');
    } else {
        $result->status = REPORT_SECURITY_WARNING;
        $result->info = get_string('check_persistentlogin_warning', 'report_security');
    }

    if ($detailed) {
        $result->details = get_string('check_persistentlogin_details', 'report_security');
    }

    return $result;
}

/**
 * Verifies if the sessionkeepalive setting in scorm is enabled on this site.
 *
 * @param bool $detailed
 * @return stdClass result
 */
function report_security_check_scormsessionkeepalive($detailed = false) {
    global $CFG;

    $result = new stdClass();
    $result->issue   = 'report_security_check_scormsessionkeepalive';
    $result->name    = get_string('check_scormsessionkeepalive_name', 'report_security');
    $result->details = null;
    $result->link    = "<a href=\"$CFG->wwwroot/$CFG->admin/settings.php?section=modsettingscorm\">".get_string('modulename', 'scorm').'</a>';

    if (!get_config('scorm', 'sessionkeepalive')) {
        $result->status = REPORT_SECURITY_OK;
        $result->info = get_string('check_scormsessionkeepalive_ok', 'report_security');
    } else {
        $result->status = REPORT_SECURITY_WARNING;
        $result->info = get_string('check_scormsessionkeepalive_warning', 'report_security');
    }

    if ($detailed) {
        $result->details = get_string('check_scormsessionkeepalive_details', 'report_security');
    }

    return $result;
}

/**
 * Checks whether a DOM object will load contents of an external file by default when it loads XML.
 *
 * @param bool $detailed
 * @return stdClass
 */
function report_security_check_xxe_risk($detailed = false) {
    global $CFG;

    require_once($CFG->dirroot . '/totara/core/environmentlib.php');

    $result = new stdClass();
    $result->issue = 'report_security_check_xxe_risk';
    $result->name = get_string('check_xxe_risk_name', 'report_security');
    $result->details = null;
    $result->link = null;

    $dom = new DOMDocument();
    $dom->load($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml");
    if (totara_core_xml_external_entities_check_searchdom($dom, 'filetext')) {
        // This environment is vulnerable.
        $result->status = REPORT_SECURITY_CRITICAL;
        $result->info = get_string('check_xxe_risk_critical', 'report_security');
    } else if (LIBXML_VERSION < 20900) {
        // Ancient libxml library, they should really have at least 2.9.0 now that we require PHP 7.3
        $result->status = REPORT_SECURITY_CRITICAL;
        $result->info = get_string('check_xxe_risk_critical', 'report_security');
    } else {
        $result->status = REPORT_SECURITY_OK;
        $result->info = get_string('check_xxe_risk_ok', 'report_security');
    }

    if ($detailed) {
        $result->details = get_string('check_xxe_risk_details', 'report_security');
    }

    return $result;
}

/**
 * Check the presence of the node_modules directory.
 *
 * @param bool $detailed Return detailed info.
 * @return object Result data.
 */
function report_security_check_nodemodules($detailed = false) {
    global $CFG;

    $result = (object)[
        'issue' => 'report_security_check_nodemodules',
        'name' => get_string('check_nodemodules_name', 'report_security'),
        'info' => get_string('check_nodemodules_info', 'report_security'),
        'details' => null,
        'status' => null,
        'link' => null,
    ];

    if (is_dir($CFG->dirroot.'/node_modules')) {
        $result->status = REPORT_SECURITY_WARNING;
    } else {
        $result->status = REPORT_SECURITY_OK;
    }

    if ($detailed) {
        $result->details = get_string('check_nodemodules_details', 'report_security', ['path' => $CFG->dirroot.'/node_modules']);
    }

    return $result;
}


/**
 * Verifies the status of preventexecpath
 *
 * @param bool $detailed
 * @return object result
 */
function report_security_check_preventexecpath($detailed = false) {
    global $CFG;

    $result = new stdClass();
    $result->issue   = 'report_security_check_preventexecpath';
    $result->name    = get_string('check_preventexecpath_name', 'report_security');
    $result->details = null;
    $result->link    = null;

    if (empty($CFG->preventexecpath)) {
        $result->status = REPORT_SECURITY_WARNING;
        $result->info   = get_string('check_preventexecpath_warning', 'report_security');
        if ($detailed) {
            $result->details = get_string('check_preventexecpath_details', 'report_security');
        }
    } else {
        $result->status = REPORT_SECURITY_OK;
        $result->info   = get_string('check_preventexecpath_ok', 'report_security');
    }

    return $result;
}

/**
 * Verifies the status of the legacy noclean handling for format_text.
 *
 * @since Totara 13.0
 * @param bool $detailed If set to true explain the consequences of the state.
 * @return stdClass
 */
function report_security_check_disableconsistentcleaning($detailed = false): stdClass {
    global $CFG;

    $result = new stdClass();
    $result->issue   = 'report_security_check_disableconsistentcleaning';
    $result->name    = get_string('check_disableconsistentcleaning_name', 'report_security');
    $result->details = null;
    $result->link    = null;

    if (!empty($CFG->disableconsistentcleaning)) {
        $result->status = REPORT_SECURITY_SERIOUS;
        $result->info = get_string('check_disableconsistentcleaning_serious', 'report_security');
        if ($detailed) {
            $result->details = get_string('check_disableconsistentcleaning_details', 'report_security');
        }
    } else {
        $result->status = REPORT_SECURITY_OK;
        $result->info = get_string('check_disableconsistentcleaning_ok', 'report_security');
    }

    return $result;
}

/**
 * Verifies GraphQL dev mode is disabled.
 *
 * @param bool $detailed
 * @return object result
 */
function report_security_check_devgraphql($detailed = false) {
    $result = new stdClass();
    $result->issue   = 'report_security_check_devgraphql';
    $result->name    = get_string('check_devgraphql_name', 'report_security');
    $result->details = null;
    $result->link    = null;

    if (defined('GRAPHQL_DEVELOPMENT_MODE') and GRAPHQL_DEVELOPMENT_MODE) {
        $result->status = REPORT_SECURITY_SERIOUS;
        $result->info = get_string('check_devgraphql_error', 'report_security');
        if ($detailed) {
            $result->details = get_string('check_devgraphql_details', 'report_security');
        }
    } else {
        $result->status = REPORT_SECURITY_OK;
        $result->info   = get_string('check_devgraphql_ok', 'report_security');
    }

    return $result;
}

/**
 * Check that:
 *       i) Configured OAuth2 issuers will verify email address;
 *      ii) Totara users not permitted to share an email address.
 *
 * It is possible for a user to compromise another user account when shared email addresses are
 * permitted either in Totara, or by a third party OAuth 2 issuer (e.g. see MDL-66598).
 *
 * @param bool $detailed Return detailed info.
 * @return object Result data.
 */
function report_security_check_oauth2verify($detailed = false) {
    global $DB;

    $result = new stdClass();
    $result->issue = 'report_security_check_oauth2verify';
    $result->name = get_string('check_oauth2verify_name', 'report_security');
    $result->info = null;
    $result->details = null;
    $result->status = null;
    $result->link = null;

    if (get_config('auth_oauth2', 'allowautolinkingexisting')) {
        $badconf = $DB->count_records('oauth2_issuer', ['enabled' => 1, 'showonloginpage' => 1,  'requireconfirmation' => 0]);
        if ($badconf == 0) {
            // There are no OAUTH 2 issuers that would allow linking of logins without email confirmation.
            $result->status = REPORT_SECURITY_OK;
            $result->info = get_string('check_oauth2verify_info_confirmationrequired', 'report_security');
        } else {
            // There is at least one OAUTH 2 issuers that allows linking of logins without email confirmation.
            $result->status = REPORT_SECURITY_SERIOUS;
            $result->info = get_string('check_oauth2verify_info_serious', 'report_security');
        }
    } else {
        // Accounts cannot be linked, other external auths do not require email verification either when adding new accounts
        // or check email duplicity, so this cannot be considered a security issue.
        $result->status = REPORT_SECURITY_OK;
        $result->info = get_string('check_oauth2verify_info_nolinking', 'report_security');
    }

    if ($detailed) {
        $result->details = get_string('check_oauth2verify_details', 'report_security');
    }

    return $result;
}

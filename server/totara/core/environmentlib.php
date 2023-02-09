<?php
/*
 * This file is part of Totara LMS
 *
 * Copyright (C) 2015 onwards Totara Learning Solutions LTD
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
 * @author Petr Skoda <petr.skoda@totaralms.com>
 * @package totara_code
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Check that database user has enough permission for database upgrade
 * @param environment_results $result
 * @return environment_results|null
 */
function totara_core_mysql_environment_check(environment_results $result) {
    global $DB;
    $result->info = 'mysql_configuration';

    if ($DB->get_dbfamily() === 'mysql') {
        // No matter what anybody says InnoDB and XtraDB are the only supported and tested engines.
        $engine = $DB->get_dbengine();
        if (!in_array($engine, array('InnoDB', 'XtraDB'))) {
            $result->setRestrictStr(array('mysqlneedsinnodb', 'totara_core', $engine));
            $result->setStatus(false);
            return $result;
        }
        // Do not show this entry unless we have a problem.
    }

    // Do not show anything for other databases.
    return null;
}

/**
 * Check that mariadb engine innodb_read_only_compressed configuration is set to OFF
 *
 * @param environment_results $result
 * @return environment_results|null
 */
function totara_core_mariadb_environment_check(environment_results $result): ?environment_results {
    global $DB;

    // Do not show anything for other databases.
    if ($DB->get_dbvendor() !== 'mariadb') {
        return null;
    }
    $result->setInfo(get_string('mariadb_compressed_rows_support', 'totara_core'));
    $version = $DB->get_server_info()['version'];
    $requires_setting_off = version_compare($version, '10.6', '>=');
    $innodb_read_only_compressed = $DB->get_record_sql("SHOW VARIABLES LIKE 'innodb_read_only_compressed'");

    if ($requires_setting_off && $innodb_read_only_compressed->value === 'ON') {
        $result->setRestrictStr(array('mariadb_needs_innodb_read_only_compressed_off', 'totara_core'));
        $result->setStatus(false);
    } else {
        $result->setStatus(true);
    }

    return $result;
}

/**
 * Check that the Totara build date always goes up.
 * @param environment_results $result
 * @return environment_results
 */
function totara_core_linear_upgrade_check(environment_results $result) {
    global $CFG;
    if (empty($CFG->totara_build)) {
        // This is a new install or upgrade from Moodle.
        return null;
    }

    $result->info = 'linear_upgrade';

    $TOTARA = new stdClass();
    $TOTARA->build = 0;
    require("$CFG->dirroot/version.php");

    if ($TOTARA->build < $CFG->totara_build) {
        $result->setRestrictStr(array('upgradenonlinear', 'totara_core', $CFG->totara_build));
        $result->setStatus(false);
        return $result;
    }

    // Everything is fine, no need for any info.
    return null;
}

/**
 * Used to recursively check a DOMDocument for a given string.
 *
 * @param DOMDocument|DOMElement $dom
 * @param string $text
 * @return bool true if string found, false if not.
 */
function totara_core_xml_external_entities_check_searchdom($dom, $text) {
    $found = false;
    /** @var DOMElement $childNode */
    foreach($dom->childNodes as $childNode) {
        if ($childNode->nodeValue !== null && strpos($childNode->nodeValue, $text) !== false) {
            $found = true;
            break;
        }
        if ($childNode->hasChildNodes()) {
            if ($found = totara_core_xml_external_entities_check_searchdom($childNode, $text)) {
                break;
            }
        }
    }

    return $found;
}

/**
 * Checks whether xml loaded with one of the libraries that uses libxml, we've chosen DOMDocument here,
 * are loading external entities by default. If they are, this means parts of the site could be
 * vulnerable to local file inclusion. Recent versions of PHP and libxml should not have this vulnerability.
 *
 * @param environment_results $result
 * @return environment_results|null - null is returned if check finds nothing wrong.
 */
function totara_core_xml_external_entities_check(environment_results $result) {
    global $CFG;

    if (!class_exists('DOMDocument')) {
        // They should have libxml installed to have loaded the environment.xml, but perhaps this particular class
        // is not enabled somehow. It's unlikely and this is the class referenced in security discussions
        // so is the best to test against.
        $result->setInfo(get_string('domdocumentnotfound', 'admin'));
        $result->setStatus(false);
        return $result;
    }

    $dom = new DOMDocument();
    @$dom->load($CFG->dirroot . "/totara/core/tests/fixtures/extentities.xml");

    if (totara_core_xml_external_entities_check_searchdom($dom, 'filetext')) {
        $result->setInfo(get_string('xmllibraryentitycheckerror', 'admin'));
        $result->setStatus(false);
        return $result;
    }

    // The test passed, no text from the external file was found.
    return null;
}

/**
 * NGRAM is a parser plugin for full-text index. Which helps to optimize diacritics search and compound word search,
 * only for the full-text indexed columns. At this point, only MySQL supports this plugin, but not MariaDB.
 *
 * @param environment_results $result
 * @return environment_results
 */
function totara_core_check_for_ngram(environment_results $result) {
    global $DB;
    if ($DB->get_dbvendor() !== "mysql") {
        // Nothing to check, so lets keep it out of the list of results
        return null;
    }

    $ngram = $DB->record_exists_sql(
        "SELECT 1 FROM information_schema.PLUGINS WHERE PLUGIN_NAME = 'ngram' AND PLUGIN_STATUS = 'ACTIVE'"
    );

    $result->setStatus($ngram);
    $result->setInfo(get_string('ngramcheckinfo', 'totara_core'));

    if (!$ngram) {
        $result->setFeedbackStr(['ngramenvironmentmsg', 'totara_core']);
    }

    return $result;
}

/**
 * @param environment_results $result
 * @return environment_results
 */
function totara_core_mnet_removal_check(environment_results $result) {
    global $DB;

    if (!$DB->get_manager()->table_exists('mnet_application')) {
        return null;
    }

    $sql = 'SELECT COUNT(DISTINCT mnethostid)
              FROM "ttr_user"
             WHERE deleted = 0';
    $hostcount = $DB->get_field_sql($sql);

    $result->setInfo(get_string('mnetremoval', 'totara_core'));
    if ($hostcount > 1) {
        $result->setStatus(false);
        $result->setFeedbackStr(['mnetremovalblocked', 'totara_core']);
    } else {
        $result->setStatus(true);
    }

    return $result;
}

/**
 * Make sure top level directory is not shared via web server.
 *
 * @param environment_results $result
 * @return environment_results
 */
function totara_shared_src_directory(environment_results $result) {
    global $CFG;

    $result->setInfo(get_string('sharedsrcinfo', 'totara_core'));

    if (substr($CFG->wwwroot, - strlen('/server')) === '/server') {
        $result->setStatus(false);
        $result->setFeedbackStr(['sharedsrcwarning', 'totara_core']);
    } else {
        $result->setStatus(true);
    }

    return $result;
}


/**
 * Check for four-byte compatible characterset
 *
 * @param environment_results $result
 * @return environment_results
 */
function totara_core_four_byte_character_set(environment_results $result) {
    global $DB;

    $info_string = get_string('four_byte_characterset_info', 'totara_core');
    if (method_exists($DB, 'get_charset')) {
        $info_string = get_string('four_byte_characterset_info_with_current', 'totara_core', $DB->get_charset());
    }
    $result->setInfo($info_string);

    if ($DB->setup_supports_four_byte_character_set()) {
        $result->setStatus(true);
    } else {
        $result->setStatus(false);
        $result->setFeedbackStr(['four_byte_characterset_warning', 'totara_core']);
    }

    return $result;
}

/**
 * Check that the database has memoization disabled.
 *
 * @param environment_results $result
 * @return environment_results|null
 */
function totara_core_database_memoize_environment_check(environment_results $result): ?environment_results {
    global $DB;

    $result->setInfo(get_string('dbmemoizeinfo', 'totara_core'));
    if ($DB->get_dbfamily() === 'postgres') {
        // For postgres >= 14, we need to confirm enable_memoize is off
        $info = $DB->get_server_info();
        $pg_version = normalize_version($info['version']);
        if (version_compare($pg_version, '14.0', '<')) {
            // Don't report any status for older postgres versions
            return null;
        }

        $setting = $DB->get_field_sql("SELECT current_setting('enable_memoize')");
        if ($setting !== 'off') {
            $result->setRestrictStr(['dbmemoizerestrictedpg', 'totara_core']);
            $result->setStatus(false);
        } else {
            $result->setStatus(true);
        }

        return $result;
    }

    // Do not show anything for other databases.
    return null;
}
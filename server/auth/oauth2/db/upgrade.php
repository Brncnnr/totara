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
 * OAuth2 authentication plugin upgrade code
 *
 * @package    auth_oauth2
 * @copyright  2017 Damyon Wiese
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade function
 *
 * @param int $oldversion the version we are upgrading from
 * @return bool result
 */
function xmldb_auth_oauth2_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // Totara 13.0 release line.

    if ($oldversion < 2020110800) {
        // Delete all unconfirmed OAuth 2 users, this will be moved to core upgrade in TL-28050.
        $userids = $DB->get_fieldset_select('user', 'id', "auth = 'oauth2' AND deleted = 0 AND confirmed = 0");
        foreach ($userids as $userid) {
            try {
                $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0]);
                if ($user) {
                    delete_user($user);
                }
            } catch (Throwable $e) {
                debugging("Exception encountered when deleting unconfirmed OAuth 2 user accounts: " . $e->getMessage(), DEBUG_NORMAL);
                $DB->set_field('user', 'deleted', 1, ['id' => $userid]);
            }
        }
        unset($userids);

        // Remove invalid and unconfirmed linked logins.
        $llids = $DB->get_fieldset_sql(
            'SELECT ll.id
               FROM "ttr_auth_oauth2_linked_login" ll
          LEFT JOIN "ttr_user" u ON u.id = ll.userid
          LEFT JOIN "ttr_oauth2_issuer" i ON i.id = ll.issuerid
              WHERE u.id IS NULL OR i.id IS NULL OR ll.confirmtoken <> \'\'');
        foreach ($llids as $llid) {
            $DB->delete_records('auth_oauth2_linked_login', ['id' => $llid]);
        }
        unset($llids);

        // Define field confirmed to be added to auth_oauth2_linked_login.
        $table = new xmldb_table('auth_oauth2_linked_login');
        $field = new xmldb_field('confirmed', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'email', ['0', '1']);
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            // We have removed all unconfirmed links in previous step, so mark all as confirmed.
            $DB->set_field('auth_oauth2_linked_login', 'confirmed', 1, ['confirmed' => 0]);
        }

        // Drop onl and new keys before changing nullability.
        $table = new xmldb_table('auth_oauth2_linked_login');
        $key = new xmldb_key('userid_key', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'), 'restrict');
        $dbman->drop_key($table, $key);
        $key = new xmldb_key('userid_key', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'));
        $dbman->drop_key($table, $key);

        // Drop the invalid unique index that would interfere with nullable userid.
        $table = new xmldb_table('auth_oauth2_linked_login');
        $key = new xmldb_key('uniq_key', XMLDB_KEY_UNIQUE, array('userid', 'issuerid', 'username'), null, null);
        $dbman->drop_key($table, $key);

        // Changing nullability of field userid on table auth_oauth2_linked_login to null.
        $table = new xmldb_table('auth_oauth2_linked_login');
        $index = new xmldb_index('unique_issuer_userid', XMLDB_INDEX_UNIQUE, array('issuerid', 'userid'));
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }
        $table = new xmldb_table('auth_oauth2_linked_login');
        $field = new xmldb_field('userid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'usermodified');
        $dbman->change_field_notnull($table, $field);

        // Define key userid_key (foreign) to be added to auth_oauth2_linked_login.
        $table = new xmldb_table('auth_oauth2_linked_login');
        $key = new xmldb_key('userid_key', XMLDB_KEY_FOREIGN, array('userid'), 'user', array('id'), 'restrict');
        $dbman->add_key($table, $key);

        // Define key issuerid_key (foreign) to be added to auth_oauth2_linked_login.
        $table = new xmldb_table('auth_oauth2_linked_login');
        $key = new xmldb_key('issuerid_key', XMLDB_KEY_FOREIGN, array('issuerid'), 'oauth2_issuer', array('id'), 'cascade');
        $dbman->drop_key($table, $key);
        $key = new xmldb_key('issuerid_key', XMLDB_KEY_FOREIGN, array('issuerid'), 'oauth2_issuer', array('id'));
        $dbman->drop_key($table, $key);
        $key = new xmldb_key('issuerid_key', XMLDB_KEY_FOREIGN, array('issuerid'), 'oauth2_issuer', array('id'), 'cascade');
        $dbman->add_key($table, $key);

        // Define index unique_issuer_userid (non-unique) to be dropped form auth_oauth2_linked_login.
        $table = new xmldb_table('auth_oauth2_linked_login');
        $index = new xmldb_index('search_index', XMLDB_INDEX_NOTUNIQUE, array('issuerid', 'username'));
        if ($dbman->index_exists($table, $index)) {
            $dbman->drop_index($table, $index);
        }

        // Define index unique_issuer_username (unique) to be added to auth_oauth2_linked_login.
        $table = new xmldb_table('auth_oauth2_linked_login');
        $index = new xmldb_index('unique_issuer_username', XMLDB_INDEX_UNIQUE, array('issuerid', 'username'));
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        // Define index unique_issuer_userid (unique) to be added to auth_oauth2_linked_login.
        $table = new xmldb_table('auth_oauth2_linked_login');
        $index = new xmldb_index('unique_issuer_userid', XMLDB_INDEX_UNIQUE, array('issuerid', 'userid'));
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }

        upgrade_plugin_savepoint(true, 2020110800, 'auth', 'oauth2');
    }

    if ($oldversion < 2020110801) {
        if (!empty($CFG->authpreventaccountcreation)) {
            // NOTE: to be replaced by core upgrade in TL-28394
            set_config('allowaccountcreation', 0, 'auth_oauth2');
        }
        upgrade_plugin_savepoint(true, 2020110801, 'auth', 'oauth2');
    }

    if ($oldversion < 2020110802) {
        // Changing type of field email on table auth_oauth2_linked_login to char.
        $table = new xmldb_table('auth_oauth2_linked_login');
        $field = new xmldb_field('email', XMLDB_TYPE_CHAR, '100', null, XMLDB_NOTNULL, null, null, 'username');
        $dbman->change_field_type($table, $field);
        upgrade_plugin_savepoint(true, 2020110802, 'auth', 'oauth2');
    }

    return true;
}

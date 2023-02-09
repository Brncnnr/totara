<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @package totara_tenant
 */

namespace totara_tenant\local;

use context_helper;
use core\event\tenant_deleted;
use core\event\tenant_updated;
use core\event\user_tenant_membership_changed;
use core\exception\unresolved_record_reference;
use core\record\tenant;
use core\reference\tenant_record_reference;
use core_user\exception\create_user_exception;

defined('MOODLE_INTERNAL') || die();

/**
 * Internal tenant API methods.
 *
 * NOTE: This is not a public API - do not use in plugins or 3rd party code!
 */
final class util {
    /** @var string delete users when deleting tenant */
    const DELETE_TENANT_USER_DELETE = 'delete';
    /** @var string suspend users when deleting tenant */
    const DELETE_TENANT_USER_SUSPEND = 'suspend';
    /** @var string migrate users when deleting tenant */
    const DELETE_TENANT_USER_MIGRATE = 'migrate';

    /**
     * Is this a valid tenant name?
     *
     * @param string $name
     * @param int|null $id id of tenant, null if not created yet
     * @return bool|string true means valid, string is error message
     */
    public static function is_valid_name(string $name, ?int $id) {
        global $DB;
        if (trim($name) === '') {
            return get_string('required');
        }
        if ($name !== trim($name)) {
            return get_string('errornameinvalid', 'totara_tenant');
        }
        if ($name !== clean_param($name, PARAM_TEXT)) {
            return get_string('errornameinvalid', 'totara_tenant');
        }
        if (\core_text::strlen($name) > 1333) {
            return get_string('errornameinvalid', 'totara_tenant');
        }
        if ($id) {
            $tenant = $DB->get_record('tenant', ['id' => $id], 'id, name');
            if ($tenant and $tenant->name === $name) {
                // Existing value is always allowed.
                return true;
            }
            $select = "LOWER(name) = LOWER(:name) AND id <> :id";
            $params = ['name' => $name, 'id' => $id];
            if ($DB->record_exists_select('tenant', $select, $params)) {
                return get_string('errornameexists', 'totara_tenant');
            }
            return true;
        }
        $select = "LOWER(name) = LOWER(:name)";
        $params = ['name' => $name];
        if ($DB->record_exists_select('tenant', $select, $params)) {
            return get_string('errornameexists', 'totara_tenant');
        }
        return true;
    }

    /**
     * Is this a valid tenant ID number?
     *
     * @param string $idnumber
     * @param int|null $id id of tenant, null if not created yet
     * @return bool|string true means valid, string is error message
     */
    public static function is_valid_idnumber(string $idnumber, ?int $id) {
        global $DB;
        if (trim($idnumber) === '') {
            return get_string('required');
        }
        // Tenant identifier is required and needs to be safe to use in any context including CSS,
        // no special characters or uppercase is allowed. Also we need to be able to distinguish
        // it from tenant->id, so it cannot start with a number.
        if (!preg_match('/^[a-z][a-z0-9]*$/D', $idnumber)) {
            return get_string('erroridnumberinvalid', 'totara_tenant');
        }
        $length = \core_text::strlen($idnumber);
        if ($length > 100 or $length < 2) {
            return get_string('erroridnumberinvalid', 'totara_tenant');
        }
        if ($id) {
            $tenant = $DB->get_record('tenant', ['id' => $id], 'id, idnumber');
            if ($tenant and $tenant->idnumber === $idnumber) {
                // Existing value is always allowed.
                return true;
            }
            $select = "idnumber = :idnumber AND id <> :id";
            $params = ['idnumber' => $idnumber, 'id' => $id];
            if ($DB->record_exists_select('tenant', $select, $params)) {
                return get_string('erroridnumberexists', 'totara_tenant');
            }
            return true;
        }
        if ($DB->record_exists('tenant', ['idnumber' => $idnumber])) {
            return get_string('erroridnumberexists', 'totara_tenant');
        }
        return true;
    }

    /**
     * Provision a new tenant instance.
     *
     * @param array $data
     * @return tenant record from {tenant] db table
     */
    public static function create_tenant(array $data) {
        global $DB, $USER, $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');
        require_once($CFG->dirroot . '/totara/dashboard/lib.php');

        if (empty($CFG->tenantsenabled)) {
            throw new \coding_exception('Tenants are not enabled, cannot create new tenant instance.');
        }

        if (self::is_valid_name($data['name'], null) !== true) {
            throw new \invalid_parameter_exception('invalid tenant name');
        }
        if (self::is_valid_idnumber($data['idnumber'], null) !== true) {
            throw new \invalid_parameter_exception('invalid tenant idnumber');
        }

        $tenant = new \stdClass();
        $tenant->name = $data['name'];
        $tenant->idnumber = $data['idnumber'];
        $tenant->description = $data['description'] ?? '';
        $tenant->descriptionformat = $data['descriptionformat'] ?? FORMAT_HTML;
        $tenant->suspended = !empty($data['suspended']);
        $tenant->timecreated = time();
        $tenant->usercreated = $USER->id;

        $trans = $DB->start_delegated_transaction();

        // Create top course category.
        $coursecat = new \stdClass();
        $coursecat->name = empty($data['categoryname']) ? $tenant->name : $data['categoryname'];
        $coursecat->name = \core_text::substr($coursecat->name, 0, 1333);
        $coursecat->idnumber = $data['categoryidnumber'] ?? null; // NOTE: add validation if added to UI
        $coursecat->description = '';
        $coursecat->parent = 0;
        $coursecat->visible = ($tenant->suspended ? 0 : 1);
        $coursecat = \coursecat::create($coursecat);
        $ccontext = \context_coursecat::instance($coursecat->id);
        $tenant->categoryid = $coursecat->id;

        // Create cohort for tenant participants.
        $cohort = new \stdClass();
        $cohort->cohorttype = 1; // Static type for now, we might add new cohort type later if necessary.
        $cohort->name = empty($data['cohortname']) ? $tenant->name : $data['cohortname'];
        $cohort->name = \core_text::substr($cohort->name, 0, 254);
        $cohort->idnumber = $data['cohortidnumber'] ?? null; // NOTE: add validation if added to UI
        $cohort->description = '';
        $cohort->visible = ($tenant->suspended ? 0 : 1); // Not used yet.
        $cohort->active = 1;
        $cohort->component = 'totara_tenant';
        $cohort->contextid = $ccontext->id;
        $tenant->cohortid = cohort_add_cohort($cohort, false);

        $tenant->id = $DB->insert_record('tenant', $tenant);

        // Hack category context and make the hackery worked.
        $ccontext = \context_coursecat::instance($coursecat->id);
        $DB->set_field('tenant', 'categoryid', $coursecat->id, ['id' => $tenant->id]);
        // Very nasty hack: change tenant id of category context and reset all caches.
        $DB->set_field('context', 'tenantid', $tenant->id, ['id' => $ccontext->id]);
        $DB->set_field('context', 'tenantid', $tenant->id, ['parentid' => $ccontext->id]);
        $ccontext->mark_dirty();
        context_helper::reset_caches();
        $ccontext = \context_coursecat::instance($coursecat->id);
        if ($ccontext->tenantid != $tenant->id) {
            throw new \coding_exception('Cannot create tenant course category');
        }

        // Clone dashboard.
        if (!empty($data['clonedashboard'])) {
            $dashboardname = empty($data['dashboardname']) ? \core_text::substr($tenant->name, 0, 255) : $data['dashboardname'];
            $dashboard = new \totara_dashboard($data['clonedashboard']);
            $dashboard->clone_dashboard($dashboardname, $tenant->id);
        }

        $trans->allow_commit();

        $event = \core\event\tenant_created::create(
            ['objectid' => $tenant->id, 'context' => \context_tenant::instance($tenant->id)]
        );
        $event->add_record_snapshot('tenant', $tenant);

        tenant::reset_caches($tenant->id);
        $tenant = tenant::fetch($tenant->id);

        $event->trigger();

        return $tenant;
    }

    /**
     * Update tenant definition.
     *
     * @param array $data
     * @return tenant
     */
    public static function update_tenant(array $data) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');

        $oldtenant = $DB->get_record('tenant', ['id' => $data['id']], '*', MUST_EXIST);

        $tenant = new \stdClass();
        $tenant->id = $oldtenant->id;
        if (isset($data['name'])) {
            if (self::is_valid_name($data['name'], $oldtenant->id) !== true) {
                throw new \invalid_parameter_exception('invalid tenant name');
            }
            $tenant->name = $data['name'];
        }
        if (isset($data['idnumber'])) {
            if (self::is_valid_idnumber($data['idnumber'], $oldtenant->id) !== true) {
                throw new \invalid_parameter_exception('invalid tenant idnumber');
            }
            $tenant->idnumber = $data['idnumber'];
        }
        if (isset($data['description'])) {
            $tenant->description = $data['description'];
        }
        if (isset($data['descriptionformat'])) {
            $tenant->descriptionformat = $data['descriptionformat'];
        }
        if (isset($data['suspended'])) {
            $tenant->suspended = !empty($data['suspended']);
        }
        $trans = $DB->start_delegated_transaction();
        $DB->update_record('tenant', $tenant);

        tenant::reset_caches($tenant->id);
        $tenant = tenant::fetch($tenant->id);

        // Update category if necessary.
        $category = \coursecat::get($tenant->categoryid, MUST_EXIST, true);
        $update = [];
        if (isset($data['categoryname']) and $category->name !== $data['categoryname']) {
            if (trim($data['categoryname']) === '') {
                throw new \invalid_parameter_exception('invalid category name');
            }
            $update['name'] = $data['categoryname'];
        }
        if ($tenant->suspended == $category->visible) {
            $update['visible'] = ($tenant->suspended ? 0 : 1);
        }
        if ($update) {
            $update['id'] = $category->id;
            $category->update($update);
        }
        $catcontext = \context_coursecat::instance($category->id);

        $cohort = $DB->get_record('cohort', ['id' => $tenant->cohortid], '*', MUST_EXIST);
        $update = [];
        if (isset($data['cohortname']) and $cohort->name !== $data['cohortname']) {
            if (trim($data['cohortname']) === '') {
                throw new \invalid_parameter_exception('invalid audience name');
            }
            $update['name'] = $data['cohortname'];
        }
        if ($tenant->suspended == $cohort->visible) {
            $update['visible'] = ($tenant->suspended ? 0 : 1); // Not used yet.
        }
        // Make sure nobody hijacked the cohort in the meantime.
        if ($cohort->component !== 'totara_tenant') {
            $update['component'] = 'totara_tenant';
        }
        if ($cohort->contextid != $catcontext->id) {
            $update['contextid'] = $catcontext->id;
        }
        if ($update) {
            $update['id'] = $cohort->id;
            cohort_update_cohort((object)$update);
        }

        $trans->allow_commit();

        $event = tenant_updated::create(['objectid' => $tenant->id, 'context' => \context_tenant::instance($tenant->id)]);
        $event->add_record_snapshot(\core\entity\tenant::TABLE, $oldtenant);
        $event->trigger();

        // Kill sessions of tenant members if suspending tenant.
        if (!$oldtenant->suspended and $tenant->suspended) {
            $users = $DB->get_records('user', ['tenantid' => $tenant->id, 'deleted' => 0, 'suspended' => 0], 'id ASC', 'id');
            foreach ($users as $user) {
                \core\session\manager::kill_user_sessions($user->id);
            }
        }

        return $tenant;
    }

    /**
     * Delete tenant.
     *
     * @param int $id
     * @param string $useraction one of DELETE_TENANT_USER_XXX constants
     * @return bool success
     */
    public static function delete_tenant(int $id, string $useraction) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/user/lib.php');
        require_once($CFG->dirroot . '/cohort/lib.php');
        require_once($CFG->dirroot . '/totara/dashboard/lib.php');

        $tenant = $DB->get_record('tenant', ['id' => $id]);
        if (!$tenant) {
            return true;
        }
        $context = \context_tenant::instance($tenant->id);

        // Migrate users away, do not use transactions, this may take a long time, interruption is not fatal.
        // Unfortunately if we get interrupted only part of the users will get migrated/deleted.
        \core_php_time_limit::raise(60*60);
        raise_memory_limit(MEMORY_EXTRA);

        if ($useraction === self::DELETE_TENANT_USER_DELETE) {
            $users = $DB->get_records('user', ['tenantid' => $tenant->id, 'deleted' => 0], 'id ASC', 'id, username');
            foreach ($users as $user) {
                delete_user($user);
            }

        } else if ($useraction === self::DELETE_TENANT_USER_MIGRATE) {
            $users = $DB->get_records('user', ['tenantid' => $tenant->id, 'deleted' => 0], 'id ASC', 'id, suspended');
            foreach ($users as $user) {
                self::set_user_participation($user->id, []);
            }

        } else if ($useraction === self::DELETE_TENANT_USER_SUSPEND) {
            $users = $DB->get_records('user', ['tenantid' => $tenant->id, 'deleted' => 0], 'id ASC');
            foreach ($users as $user) {
                $logout = false;
                if (!$user->suspended) {
                    $logout = true;
                    $updateuser = new \stdClass();
                    $updateuser->id   = $user->id;
                    $updateuser->suspended = 1;
                    user_update_user($updateuser, false, true);
                    \totara_core\event\user_suspended::create_from_user($user)->trigger();
                }
                self::set_user_participation($user->id, []);
                if ($logout) {
                    \core\session\manager::kill_user_sessions($user->id);
                }
            }

        } else {
            throw new \coding_exception('Invalid $useraction parameter');
        }
        $DB->set_field('user', 'tenantid', null, ['tenantid' => $tenant->id, 'deleted' => 1]);

        // Prevent partial deletion of tenant, this will error out if any users are added in the meantime.
        $prevignore = ignore_user_abort(true);
        $trans = $DB->start_delegated_transaction();

        // For the same of consistency delete all participants from the tenant audience,
        // otherwise only the tenant members would be removed from the audience when migrating them to regular accounts.
        $participants = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', [$tenant->cohortid]);
        foreach ($participants as $userid) {
            cohort_remove_member($tenant->cohortid, $userid);
        }

        // Delete dashboards and related blocks.
        $dashboards = $DB->get_records('totara_dashboard', ['tenantid' => $tenant->id]);
        foreach ($dashboards as $dashboard) {
            $d = new \totara_dashboard($dashboard->id);
            $d->delete();
        }

        $DB->set_field('course_categories', 'visible', '0', ['id' => $tenant->categoryid]);
        $DB->set_field('cohort', 'component', '', ['id' => $tenant->cohortid]);

        $context->delete();

        $DB->set_field('context', 'tenantid', null, ['tenantid' => $tenant->id]);
        $DB->delete_records('tenant', ['id' => $tenant->id]);

        $trans->allow_commit();

        tenant::reset_caches($tenant->id);

        \context_helper::build_all_paths(true, false);

        /** @var tenant_deleted $event */
        $event = tenant_deleted::create(['objectid' => $tenant->id, 'context' => $context]);
        $event->add_record_snapshot('tenant', $tenant);
        $event->trigger();

        ignore_user_abort($prevignore);
        return true;
    }

    /**
     * Adds existing global user as other tenant participant.
     *
     * @param int $tenantid
     * @param int $userid
     */
    public static function add_other_participant(int $tenantid, int $userid) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');

        if (empty($CFG->tenantsenabled)) {
            throw new \coding_exception('Tenants are not enabled, cannot add participants.');
        }

        $tenant = $DB->get_record('tenant', ['id' => $tenantid], '*', MUST_EXIST);
        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);

        if ($user->tenantid !== null) {
            throw new \coding_exception('Only non-tenant users may be tenant participants');
        }

        if (!$DB->record_exists('cohort_members', ['cohortid' => $tenant->cohortid, 'userid' => $user->id])) {
            cohort_add_member($tenant->cohortid, $user->id);
        }
    }

    /**
     * Remove existing global user from list of other tenant participants.
     *
     * @param int $tenantid
     * @param int $userid
     */
    public static function remove_other_participant(int $tenantid, int $userid) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');

        $tenant = $DB->get_record('tenant', ['id' => $tenantid], '*', MUST_EXIST);
        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);

        if ($user->tenantid !== null) {
            throw new \coding_exception('Only non-tenant users may be tenant participants');
        }

        if ($DB->record_exists('cohort_members', ['cohortid' => $tenant->cohortid, 'userid' => $user->id])) {
            cohort_remove_member($tenant->cohortid, $user->id);
        }
    }

    /**
     * Returns list of tenants user is participating in.
     *
     * @param int $userid
     * @return array array of tenant ids
     */
    public static function get_user_participation(int $userid) {
        global $DB;

        $sql = 'SELECT t.id, t.id AS tid
                  FROM "ttr_cohort_members" cm
                  JOIN "ttr_tenant" t ON t.cohortid = cm.cohortid
                  WHERE cm.userid = :userid
               ORDER BY t.id ASC';
        return $DB->get_records_sql_menu($sql, ['userid' => $userid]);
    }

    /**
     * Adding users to multiple tenants, and removing the current tenants only if the list of
     * tenant ids are not providing any id(s) of the current ones.
     *
     * Use empty tenant ids array to make migrate tenant member to regular user.
     *
     * @param int $userid
     * @param int[] $tenantids
     */
    public static function set_user_participation(int $userid, array $tenantids) {
        global $DB, $CFG;

        if (empty($CFG->tenantsenabled)) {
            throw new \coding_exception('Tenants are not enabled, cannot change participants.');
        }

        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);

        $usercontext = \context_user::instance($user->id);
        $syscontext = \context_system::instance();

        $trans = $DB->start_delegated_transaction();

        $moved = false;
        if ($user->tenantid) {
            $moved = true;
            $DB->set_field('user', 'tenantid', null, ['id' => $user->id]);
            $usercontext->update_moved($syscontext);
        }

        $current = self::get_user_participation($user->id);
        foreach ($tenantids as $id) {
            if (isset($current[$id])) {
                unset($current[$id]);
                continue;
            }
            self::add_other_participant($id, $user->id);
        }
        foreach ($current as $id) {
            self::remove_other_participant($id, $user->id);
        }

        $trans->allow_commit();

        if ($moved) {
            $event = user_tenant_membership_changed::create([
                'objectid' => $user->id,
                'context' => $usercontext,
                'relateduserid' => $user->id,
                'other' => [
                    'oldtenantid' => $user->tenantid,
                    'newtenantid' => null
                ]
            ]);
            $event->trigger();
        }

        if ($moved) {
            \core\session\manager::kill_user_sessions($user->id);
        }
    }

    /**
     * Migrate tenant or non-tenant user to tenant user.
     *
     * @param int $userid
     * @param int $tenantid
     */
    public static function migrate_user_to_tenant(int $userid, int $tenantid) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');

        if (empty($CFG->tenantsenabled)) {
            throw new \coding_exception('Tenants are not enabled, cannot migrate user to tenant.');
        }

        // Look out for an invalid tenant_id being passed for the api_client API user.
        try {
            $tenant_record_reference = new tenant_record_reference();
            $tenant = $tenant_record_reference->get_record(['id' => $tenantid]);
        } catch (unresolved_record_reference $exception) {
            throw new create_user_exception('Tenant reference for API user must identify exactly one tenant.');
        }

        $user = $DB->get_record('user', ['id' => $userid, 'deleted' => 0], '*', MUST_EXIST);

        if (is_siteadmin($user)) {
            throw new \coding_exception('Admins cannot be migrated to tenant members');
        }

        if ($user->tenantid and $tenantid == $user->tenantid) {
            // Nothing to do.
            return;
        }

        $tenantcontext = \context_tenant::instance($tenant->id);
        $usercontext = \context_user::instance($user->id);

        $trans = $DB->start_delegated_transaction();

        $DB->set_field('user', 'tenantid', $tenant->id, ['id' => $user->id]);
        $usercontext->update_moved($tenantcontext);

        $alreadyparticipant = false;
        $sql = 'SELECT t.id, cm.cohortid
                  FROM "ttr_cohort_members" cm
                  JOIN "ttr_tenant" t ON t.cohortid = cm.cohortid
                  WHERE cm.userid = :userid';
        $cohorts = $DB->get_records_sql_menu($sql, ['userid' => $userid]);
        foreach ($cohorts as $tid => $cohortid) {
            if ($tenant->cohortid == $cohortid) {
                $alreadyparticipant = true;
                continue;
            }
            cohort_remove_member($cohortid, $user->id);
        }
        if (!$alreadyparticipant) {
            cohort_add_member($tenant->cohortid, $user->id);
        }

        $trans->allow_commit();

        $event = user_tenant_membership_changed::create([
            'objectid' => $user->id,
            'context' => $usercontext,
            'relateduserid' => $user->id,
            'other' => [
                'oldtenantid' => $user->tenantid,
                'newtenantid' => $tenantid
            ]
        ]);
        $event->trigger();

        \core\session\manager::kill_user_sessions($user->id);
    }

    /**
     * If tenants enabled then makes sure that at least one role for each following archetype exists:
     *  - tenantdomainmanager - intended for users that mange courses and categories of a tenant,
     *    it allows them to browse list of tenant users/participants
     *  - tenantusermanager - intended for delegation of management of tenant member accounts
     */
    public static function check_roles_exist(): void {
        global $DB, $CFG;

        if (empty($CFG->tenantsenabled)) {
            return;
        }

        $systemcontext = \context_system::instance();

        $shortnames = ['tenantusermanager', 'tenantdomainmanager'];
        foreach ($shortnames as $shortname) {
            if ($DB->record_exists('role', ['shortname' => $shortname])) {
                continue;
            }
            $newroleid = create_role('', $shortname, '', $shortname);

            $role = $DB->get_record('role', ['id' => $newroleid], '*', MUST_EXIST);
            foreach (array('assign', 'override', 'switch') as $type) {
                $function = 'allow_' . $type;
                $allows = get_default_role_archetype_allows($type, $role->archetype);
                foreach ($allows as $allowid) {
                    $function($role->id, $allowid);
                }
                set_role_contextlevels($role->id, get_default_contextlevels($role->archetype));
            }
            $defaultcaps = get_default_capabilities($role->archetype);
            foreach($defaultcaps as $cap => $permission) {
                assign_capability($cap, $permission, $role->id, $systemcontext->id);
            }

            // Add allow_* defaults related to the new role.
            foreach ($DB->get_records('role') as $role) {
                if ($role->id == $newroleid) {
                    continue;
                }
                foreach (array('assign', 'override', 'switch') as $type) {
                    $function = 'allow_'.$type;
                    $allows = get_default_role_archetype_allows($type, $role->archetype);
                    foreach ($allows as $allowid) {
                        if ($allowid == $newroleid) {
                            $function($role->id, $allowid);
                        }
                    }
                }
            }
        }
    }

    /**
     * Does a user with given username exist?
     *
     * NOTE: this is more strict than other areas to prevent problems in multi-tenant sites.
     *
     * @param string $username
     * @return bool
     */
    public static function user_username_exists(string $username): bool {
        global $DB;
        $params = ['username' => $username];
        return $DB->record_exists_select('user', "LOWER(username) = LOWER(:username) AND deleted = 0", $params);
    }

    /**
     * Is username valid for new tenant user?
     *
     * NOTE: this is more strict than other areas to prevent problems in multi-tenant sites.
     *
     * @param string $username
     * @return string|null null means value ok, anything else is error message
     */
    public static function validate_user_username(string $username): ?string {
        if (trim($username) === '' || empty($username)) {
            return get_string('missingusername');
        }
        if (self::user_username_exists($username)) {
            return get_string('usernameexists');
        }
        if ($username !== \core_user::clean_field($username, 'username')) {
            if ($username !== \core_text::strtolower($username)) {
                return get_string('usernamelowercase');
            } else {
                return get_string('invalidusername');
            }
        }
        return null;
    }

    /**
     * Does a user with given email exist?
     *
     * NOTE: this is more strict than other areas to prevent problems in multi-tenant sites.
     *
     * @param string $email
     * @return bool
     */
    public static function user_email_exists(string $email): bool {
        global $DB;
        $params = ['email' => $email];
        return $DB->record_exists_select('user', "LOWER(email) = LOWER(:email) AND deleted = 0", $params);
    }

    /**
     * Is email valid for new tenant user?
     *
     * NOTE: this is more strict than other areas to prevent problems in multi-tenant sites.
     *
     * @param string $email
     * @return string|null null means value ok, anything else is error message
     */
    public static function validate_user_email(string $email): ?string {
        global $CFG;
        if (trim($email) === '' || empty($email)) {
            return get_string('missingemail');
        }
        if (empty($CFG->allowaccountssameemail) && self::user_email_exists($email)) {
            return get_string('emailexists');
        }
        if (!validate_email($email)) {
            return get_string('invalidemail');
        }
        return null;
    }

    /**
     * Does a user with given idnumber exist?
     *
     * NOTE: this is more strict than other areas to prevent problems in multi-tenant sites.
     *
     * @param string $idnumber
     * @return bool
     */
    public static function user_idnumber_exists(string $idnumber): bool {
        global $DB;
        if ($idnumber === '') {
            return false;
        }
        $params = ['idnumber' => $idnumber];
        return $DB->record_exists_select('user', "LOWER(idnumber) = LOWER(:idnumber) AND deleted = 0", $params);
    }

    /**
     * Is idnumber valid for new tenant user?
     *
     * NOTE: this is more strict than other areas to prevent problems in multi-tenant sites.
     *
     * @param string $idnumber
     * @return string|null null means value ok, anything else is error message
     */
    public static function validate_user_idnumber(string $idnumber): ?string {
        if ($idnumber === '') {
            return null;
        }
        if (trim($idnumber) !== $idnumber || empty($idnumber)) {
            return get_string('idnumberinvalid', 'error');
        }
        if (self::user_idnumber_exists($idnumber)) {
            return get_string('idnumbertaken', 'error');
        }
        return null;
    }

    /**
     * Returns list of required CSV fields.
     *
     * @param bool $requirepasswords
     * @return string[]
     */
    public static function get_csv_required_columns(bool $requirepasswords): array {
        $result = [
            'username',
            'email',
            'firstname',
            'lastname',
        ];
        if ($requirepasswords) {
            $result[] = 'password';
        }
        return $result;
    }

    /**
     * Returns list of optional CSV fields.
     *
     * @param bool $requirepasswords
     * @return string[]
     */
    public static function get_csv_optional_columns(bool $requirepasswords): array {
        global $DB;

        // Do not use the overcomplicated fullname logic from Moodle here, just allow all possible name fields.
        $result = [
            'middlename', 'alternatename', 'firstnamephonetic', 'lastnamephonetic',
            'city', 'country', 'deleted', 'suspended',
            'idnumber', 'lang', 'timezone', 'phone1', 'phone2', 'address', 'maildisplay',
            'mailformat', 'emaildigest', 'autosubscribe', 'description', 'webpage',
            'institution', 'department',
        ];
        if (!$requirepasswords) {
            array_unshift($result, 'password');
        }

        // Custom profile fields, ignore the required flag here, users will be asked later.
        $fields = $DB->get_records('user_info_field', [], 'shortname ASC');
        foreach ($fields as $field) {
            if ($field->visible == PROFILE_VISIBLE_NONE) {
                continue;
            }
            $result[] = 'profile_field_' . $field->shortname;
        }
        return $result;
    }

    /**
     * Validate the structure of CSV file using the first line
     * is compatible with tenant member upload.
     *
     * @param string $content
     * @param string $encoding
     * @param bool $requirepasswords
     * @return array info with keys 'delimiter', 'delimitername', 'columns' and 'error'
     */
    public static function validate_users_csv_structure(string $content, string $encoding, bool $requirepasswords): array {
        global $CFG;
        require_once($CFG->dirroot . '/lib/csvlib.class.php');

        $result = ['delimiter' => null, 'delimitername' => null, 'columns' => null, 'error' => null];

        $content = \core_text::convert($content, $encoding, 'utf-8');
        $content = \core_text::trim_utf8_bom($content);
        $content = preg_replace('!\r\n?!', "\n", $content);

        $content = trim($content);
        if ($content === '') {
            $result['error'] = get_string('csvemptyfile', 'error');
            return $result;
        }

        $requiredcolumns = self::get_csv_required_columns($requirepasswords);
        $optionalcolumns = self::get_csv_optional_columns($requirepasswords);

        $newlinepos = strpos($content, "\n");
        if ($newlinepos) {
            $header = substr($content, 0, $newlinepos);
        } else {
            $header = $content;
        }
        $header = trim($header);

        foreach (\csv_import_reader::get_delimiter_list() as $delimitername => $delimiter) {
            $columns = str_getcsv($header, $delimiter, '"');
            if (count($columns) > 1) {
                $columns = array_map('trim', $columns);
                $unique = array_unique($columns);
                if (count($columns) !== count($unique)) {
                    $result['error'] = get_string('csvcolumnduplicates', 'error');
                    return $result;
                }
                $missing = [];
                foreach ($requiredcolumns as $column) {
                    if (!in_array($column, $columns)) {
                        $missing[$column] = $column;
                    }
                }
                if ($missing) {
                    $result['error'] = get_string('errorcsvcolumnsmissing', 'totara_tenant', implode(', ', $missing));
                } else {
                    $extra = $columns;
                    foreach ($extra as $k => $column) {
                        if (in_array($column, $requiredcolumns)) {
                            unset($extra[$k]);
                        }
                        if (in_array($column, $optionalcolumns)) {
                            unset($extra[$k]);
                        }
                    }
                    if ($extra) {
                        $result['error'] = get_string('errorcsvcolumnsextra', 'totara_tenant', s(implode(', ', $extra)));
                    }
                }

                $result['delimiter'] = $delimiter;
                $result['delimitername'] = $delimitername;
                $result['columns'] = $columns;
                return $result;
            }
        }

        $result['error'] = get_string('namecolumnmissing', 'cohort');
        return $result;
    }

    /**
     * Is the CSV row data valid for tenant member upload?
     *
     * @param array $row array with column names as keys
     * @param bool $requirepasswords
     * @param array $profilefieldvalues Used to track previous profile field values to ensure uniqueness is observed
     * @return array errors, empty array means data is ok
     */
    public static function validate_users_csv_row(array $row, bool $requirepasswords, array &$profilefieldvalues = []): array {
        global $CFG;

        $errors = array();

        $errmsg = self::validate_user_username($row['username']);
        if ($errmsg !== null) {
            $errors[] = $errmsg;
        }

        $errmsg = self::validate_user_email($row['email']);
        if ($errmsg !== null) {
            $errors[] = $errmsg;
        }

        if (isset($row['idnumber'])) {
            $errmsg = self::validate_user_idnumber($row['idnumber']);
            if ($errmsg !== null) {
                $errors[] = $errmsg;
            }
        }
        if (!isset($row['firstname']) || trim($row['firstname']) === '') {
            $errors[] = get_string('missingfield', 'error', 'firstname');
        }

        if (!isset($row['password']) || strlen($row['password']) === 0) {
            if ($requirepasswords) {
                $errors[] = get_string('missingfield', 'error', 'password');
            }
        } else {
            $errmsg = null;
            if (!check_password_policy($row['password'], $errmsg)) {
                $errors[] = $errmsg;
            }
        }

        if (isset($row['lang']) && strlen($row['lang']) > 0) {
            if (clean_param($row['lang'], PARAM_LANG) === '') {
                $errors[] = get_string('cannotfindlang', 'error', s($row['lang']));
            }
        }

        // Validate custom fields.
        require_once($CFG->dirroot . '/admin/tool/uploaduser/locallib.php');
        $row['status'] = [];
        uu_check_custom_profile_data($row, $profilefieldvalues);
        if ($row['status']) {
            $errors = array_merge($errors, $row['status']);
        }
        unset($row['status']);

        return $errors;
    }
}

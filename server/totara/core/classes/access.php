<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
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
 * @author  Simon Coggins <simon.coggins@totaralearning.com>
 * @author  Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_core
 */

namespace totara_core;

defined('MOODLE_INTERNAL') || die();

/**
 * SQL implementation of access control methods.
 *
 * NOTE: this is not public API, use get_has_capability_sql() function instead.
 */
final class access {

    /**
     * This function allows you to restrict rows in an existing SQL statement by including the return value as
     * a WHERE clause. You must provide the capability and user you want to check, and a sql field referencing
     * context id. This allows you to check multiple contexts in one SQL query
     * instead of having to call {@link has_capability()} inside a loop.
     *
     * NOTE: role switching is not implemented here
     *
     * @param string        $capability     The name of the capability to check. For example mod/forum:view
     * @param string        $contextidfield An SQL snippet which represents the link to context id in the parent SQL statement.
     * @param int|\stdClass $user           A user id or user object, null means current user
     * @param boolean       $doanything     If false, only real roles of administrators are considered
     *
     * @return array Array of the form array($sql, $params) which can be included in the WHERE clause of an SQL statement.
     */
    public static function get_has_capability_sql($capability, $contextidfield, $user = null, $doanything = true) {
        global $USER, $CFG, $DB;

        // First, validate that we can work with the $contextidfield supplied.
        self::validate_contextidfield($contextidfield);

        $alias = $DB->get_unique_param('ctx_alias');

        // Make sure there is a user id specified.
        if ($user === null) {
            $userid = $USER->id;
        } else {
            $userid = is_object($user) ? $user->id : intval($user);
        }

        $tenantwhere = '';

        // Capability must exist.
        if (!$capinfo = get_capability_info($capability)) {
            debugging('Capability "'.$capability.'" was not found! This has to be fixed in code.', DEBUG_DEVELOPER);
            return array("1=0", array());
        }

        if (isguestuser($userid) or $userid == 0) {
            // Make sure the guest account and not-logged-in users never get any risky caps no matter what the actual settings are.
            if (($capinfo->captype === 'write') or ($capinfo->riskbitmask & (RISK_XSS | RISK_CONFIG | RISK_DATALOSS))) {
                return array("1=0", array());
            }
            // Make sure forcelogin cuts off not-logged-in users if enabled.
            if (!empty($CFG->forcelogin) and $userid == 0) {
                return array("1=0", array());
            }

            if (!empty($CFG->tenantsenabled)) {
                $tenantwhere = "AND {$alias}.tenantid IS NULL";
            }

        } else {
            // Make sure that the user exists and is not deleted.
            $usercontext = \context_user::instance($userid, IGNORE_MISSING);
            if (!$usercontext) {
                return array("1=0", array());
            }

            if (!empty($CFG->tenantsenabled)) {
                if ($usercontext->tenantid) {
                    // NOTE: ignore top level block contexts exceptions here.
                    if (!empty($CFG->tenantsisolated)) {
                        $tenantwhere = "AND {$alias}.tenantid = " . $usercontext->tenantid;
                    } else {
                        $tenantwhere = "AND ({$alias}.tenantid = " . $usercontext->tenantid . " OR {$alias}.tenantid IS NULL)";
                    }
                }
            }
        }

        // Site admin can do anything, unless otherwise specified.
        if (is_siteadmin($userid) && $doanything) {
            return array("1=1", array());
        }

        if (self::has_capability_definition_past_system($capability, $userid)) {
            // The capability has been assigned at sub contexts, we're going to need to traverse.
            // This is known to be an expensive query on some databases (namely mariadb).
            list($permissionsql, $permissionparams) = self::get_permission_sql_complete($capability, $userid, "{$alias}.id");
        } else {
            // The capability has only ever been assigned at the system context.
            // YAY! This is going to be much faster.
            list($permissionsql, $permissionparams) = self::get_permission_sql_system_level_only($capability, $userid);
        }

        // They must have ALLOW in at least one role, and no prohibits in any role.
        $hascapsql = "
EXISTS (
    SELECT 'x'
      FROM {context} {$alias}
     WHERE {$alias}.id = {$contextidfield} $tenantwhere

       AND EXISTS (
{$permissionsql}
                  )
       )
";

        return array($hascapsql, $permissionparams);
    }

    /**
     * Validates contextidfield parameter to make sure there are no SQL injections or SQL errors
     * in the generic queries and in the queries for the cached reports.
     *
     * @param string $contextidfield
     *
     * @return void
     */
    private static function validate_contextidfield($contextidfield) {
        if (!preg_match('/^(\{?[a-z][a-z0-9_]*\}?\.)?[a-z][a-z0-9_]*$/', $contextidfield, $matches)) {
            throw new \coding_exception('Invalid context id field specified');
        }
        if (isset($matches[1])) {
            if ($matches[1] === 'hascapabilitycontext.' or $matches[1] === '{context}.') {
                throw new \coding_exception('Invalid context id field specified, table name used internally');
            }
        }
    }

    /**
     * Use get_has_capability_sql() to emulate has_capability(),
     * this is intended mainly for testing purposes.
     *
     * Note: role switching is completely ignored.
     *
     * @param string        $capability
     * @param \context      $context
     * @param int|\stdClass $user
     * @param bool          $doanything
     *
     * @return bool
     */
    public static function has_capability($capability, \context $context, $user = null, $doanything = true) {
        global $DB;

        list($hascapsql, $hascapparams) = self::get_has_capability_sql($capability, 'c.id', $user, $doanything);
        if ($hascapsql === "1=1") {
            return true;
        }
        if ($hascapsql === "1=0") {
            return false;
        }

        $sql = "SELECT 'x' FROM {context} c WHERE c.id = {$context->id} AND {$hascapsql}";
        $params = array_merge(array(), $hascapparams);

        return $DB->record_exists_sql($sql, $params);
    }

    /**
     * Returns the SQL for a subquery to obtain role assignments for a specific user.
     *
     * Most of these will come from the role_assignments table but we also need to take
     * into account automatically assigned roles e.g.:
     * - $CFG->defaultuserroleid
     * - $CFG->notloggedinroleid
     * - $CFG->guestroleid
     * - $CFG->defaultfrontpageroleid
     *
     * @param int $userid ID of the user to check permissions for, 0 means not-logger-in user
     *
     * @return string sql fragment with embedded parameters
     */
    public static function get_role_assignments_subquery($userid) {
        global $CFG, $DB;

        $systemcontext = \context_system::instance();
        $userid = intval($userid);

        $queries = array();

        if ($userid == 0) {
            // Zero means a non-logged in user.
            if (!empty($CFG->notloggedinroleid)) {
                // Append the "not logged in role" in the system context.
                $notloggedinroleid = intval($CFG->notloggedinroleid);
                $queries[] = "                             SELECT {$notloggedinroleid} as roleid, {$systemcontext->id} AS contextid";
            }
        } else if (isguestuser($userid)) {
            // Guest account is login as guest allowed.
            if (!empty($CFG->guestroleid)) {
                // Append the "guest role" in the system context.
                $guestroleid = intval($CFG->guestroleid);
                $queries[] = "                             SELECT {$guestroleid} AS roleid, {$systemcontext->id} AS contextid";
            }
        } else {
            // Normal user.
            // Start with authenticated user role.
            if (!empty($CFG->defaultuserroleid)) {
                $defaultuserroleid = intval($CFG->defaultuserroleid);
                $queries[] = "                             SELECT {$defaultuserroleid} AS roleid, {$systemcontext->id} AS contextid";
            }

            // Authenticated user on front page role.
            if (!empty($CFG->defaultfrontpageroleid)) {
                $frontpagecontext = \context_course::instance(get_site()->id);
                $frontpageroleid = intval($CFG->defaultfrontpageroleid);
                $queries[] = "                             SELECT {$frontpageroleid} AS roleid, {$frontpagecontext->id} AS contextid";
            }

            $alias = $DB->get_unique_param('role_ass_alias');
            // Add all real role assignments.
            $queries[] = "                             SELECT roleid, contextid FROM {role_assignments} {$alias} WHERE {$alias}.userid = {$userid}";
        }

        if ($queries) {
            // Join the SQL together.
            $sql = implode("\n                        UNION\n", $queries);
            return $sql;
        }

        // Return select with no results.
        return "SELECT NULL AS roleid, NULL AS contextid WHERE 1=0";
    }

    /**
     * Returns true if the given capability has been prohibited at least once, anywhere throughout the system.
     *
     * This method could be further optimised by providing a userid and then only returning true if the user
     * holds a role that has the prohibit flag for the given capability.
     *
     * @param string $capability
     * @return bool
     */
    private static function has_prohibit_flag_being_used_anywhere(string $capability): bool {
        global $DB;
        return $DB->record_exists('role_capabilities', ['capability' => $capability, 'permission' => CAP_PROHIBIT]);
    }

    /**
     * Returns true if the given capability is defined for the at least one of the roles held by the given user and any context
     * level other than the system.
     *
     * @param string $capability
     * @param int $userid
     * @return bool
     */
    private static function has_capability_definition_past_system(string $capability, int $userid): bool {
        global $DB;
        $roleassignmentssql = self::get_role_assignments_subquery($userid);
        $rc = $DB->get_unique_param('rc');
        $ra = $DB->get_unique_param('ra');
        $c1 = $DB->get_unique_param('ctx_alias');
        $c2 = $DB->get_unique_param('ctx_alias');
        $sql = "SELECT {$rc}.id
                  FROM {role_capabilities} {$rc}
                  JOIN {context} {$c1} ON {$c1}.id = {$rc}.contextid
                  JOIN (
{$roleassignmentssql}
                       ) {$ra} ON {$ra}.roleid = {$rc}.roleid
                  JOIN {context} {$c2} ON {$c2}.id = {$ra}.contextid
                 WHERE {$rc}.capability = :capability AND ({$c2}.depth > 1 OR {$c1}.depth > 1)";
        $params = [
            'capability' => $capability,
        ];
        return $DB->record_exists_sql($sql, $params);
    }

    /**
     * Returns SQL that allows you to filter results to just those where the user holds the given capability at the system level.
     *
     * This method should only be called if you know for sure that the capability is overridden at any context for any of the
     * roles held by the user, including the assumed roles such as the authenticated user role.
     * If you are unsure, or already know that the capability has been overridden then you should call
     * {@link self::get_permission_sql_complete()}
     *
     * @param string $capability
     * @param int $userid
     * @return array
     */
    private static function get_permission_sql_system_level_only(string $capability, int $userid): array {
        global $DB;

        $roleassignmentssql = self::get_role_assignments_subquery($userid);

        // Generate prefix and parameter names in order to avoid collisions.
        $rc = $DB->get_unique_param('rc');
        $ra = $DB->get_unique_param('ra');
        $param_capability = $DB->get_unique_param('capability');
        $param_systemcontextid = $DB->get_unique_param('context');
        $param_allow = $DB->get_unique_param('permission');
        $allowpreventsql = "
            SELECT 'x'
              FROM {role_capabilities} {$rc}
              JOIN ({$roleassignmentssql}) {$ra} ON {$ra}.roleid = {$rc}.roleid
             WHERE {$rc}.contextid = :{$param_systemcontextid} AND
                   {$rc}.capability = :{$param_capability} AND
                   {$rc}.permission = :{$param_allow}
            ";
        $params = [
            $param_capability => $capability,
            $param_systemcontextid => SYSCONTEXTID,
            $param_allow => CAP_ALLOW,
        ];

        if (self::has_prohibit_flag_being_used_anywhere($capability)) {
            // Generate new prefix and parameter names in order to avoid collisions.
            $rc = $DB->get_unique_param('rc');
            $ra = $DB->get_unique_param('ra');
            $param_capability = $DB->get_unique_param('capability');
            $param_systemcontextid = $DB->get_unique_param('context');
            $param_allow = $DB->get_unique_param('permission');
            $param_prohibit = $DB->get_unique_param('permission');

            $allowpreventsql .= " AND
                   NOT EXISTS (
                        SELECT 'x'
                          FROM {role_capabilities} {$rc}
                          JOIN ({$roleassignmentssql}) {$ra} ON {$ra}.roleid = {$rc}.roleid
                         WHERE {$rc}.contextid = :{$param_systemcontextid} AND
                               {$rc}.capability = :{$param_capability} AND
                               {$rc}.permission = :{$param_prohibit}
                   )";
            $params[$param_capability] = $capability;
            $params[$param_systemcontextid] = SYSCONTEXTID;
            $params[$param_allow] = CAP_PROHIBIT;
            $params[$param_prohibit] = CAP_PROHIBIT;
        }

        return [$allowpreventsql, $params];
    }

    /**
     * Given an SQL field containing a context id, return an SQL snippet that returns
     * non-zero number of rows if the specified user is assigned any roles in that context which
     * grant them ALLOW permission on the specified capability. This takes into account
     * overrides by considering the most specific ALLOW or PREVENT permission.
     *
     * @param string $capability     A capability to check for.
     * @param int    $userid         ID of the user to check permissions for.
     * @param string $contextidfield Field linking to the context id in the original query.
     *
     * @return array Array of SQL and parameters that generate the query.
     */
    private static function get_permission_sql_complete($capability, $userid, $contextidfield) {
        global $DB;

        $max_depth_alias = $DB->get_unique_param('max_depth_alias');
        $lineage_alias = $DB->get_unique_param('lineage_alias');

        $capallow = CAP_ALLOW;
        $capprevent = CAP_PREVENT;

        // Build role assignment subquery.
        $roleassignmentssql = self::get_role_assignments_subquery($userid);

        $paramcapability = $DB->get_unique_param('cap');
        if ($DB->get_dbfamily() === 'mysql') {
            $dlineage_alias = $DB->get_unique_param('dlineage_alias');
            $ctx_map_alias = $DB->get_unique_param('dctxmap_alias');
            $dra_alias = $DB->get_unique_param('dra_alias');
            $drc_alias = $DB->get_unique_param('drc_alias');
            $ctx_alias = $DB->get_unique_param('dctx_alias');
            // MySQL seems to be unable to do the aggregation with outside references.
            $mysqlhack = "AND {$max_depth_alias}.childid = {$lineage_alias}.childid";
            $maxdepthsql = "
                  SELECT {$dlineage_alias}.parentid, {$dra_alias}.roleid, MAX({$ctx_alias}.depth) AS depth, {$dlineage_alias}.childid
                    FROM {context_map} {$dlineage_alias}
                    JOIN (
{$roleassignmentssql}
                         ) {$dra_alias} ON {$dra_alias}.contextid = {$dlineage_alias}.parentid
                    JOIN {context_map} {$ctx_map_alias} ON {$ctx_map_alias}.childid = {$dlineage_alias}.childid
                    JOIN {role_capabilities} {$drc_alias} ON {$dra_alias}.roleid = {$drc_alias}.roleid AND {$drc_alias}.contextid = {$ctx_map_alias}.parentid
                         AND {$drc_alias}.capability = :{$paramcapability} AND ({$drc_alias}.permission = {$capallow} OR {$drc_alias}.permission = {$capprevent})
                    JOIN {context} {$ctx_alias} ON {$drc_alias}.contextid = {$ctx_alias}.id
                GROUP BY {$dlineage_alias}.parentid, {$dra_alias}.roleid, {$dlineage_alias}.childid
";
        } else {
            $dlineage_alias = $DB->get_unique_param('dlineage_alias');
            $ctx_map_alias = $DB->get_unique_param('dctxmap_alias');
            $dra_alias = $DB->get_unique_param('dra_alias');
            $drc_alias = $DB->get_unique_param('drc_alias');
            $ctx_alias = $DB->get_unique_param('dctx_alias');
            // This is probably the heaviest subquery, it might be worth exploring optimisation options later.
            $mysqlhack = "";
            $maxdepthsql = "
                  SELECT {$dlineage_alias}.parentid, {$dra_alias}.roleid, MAX({$ctx_alias}.depth) AS depth
                    FROM {context_map} {$dlineage_alias}
                    JOIN (
{$roleassignmentssql}
                         ) {$dra_alias} ON {$dra_alias}.contextid = {$dlineage_alias}.parentid
                    JOIN {context_map} {$ctx_map_alias} ON {$ctx_map_alias}.childid = {$dlineage_alias}.childid
                    JOIN {role_capabilities} {$drc_alias} ON {$dra_alias}.roleid = {$drc_alias}.roleid AND {$drc_alias}.contextid = {$ctx_map_alias}.parentid
                         AND {$drc_alias}.capability = :{$paramcapability} AND ({$drc_alias}.permission = {$capallow} OR {$drc_alias}.permission = {$capprevent})
                    JOIN {context} {$ctx_alias} ON {$drc_alias}.contextid = {$ctx_alias}.id
                   WHERE {$dlineage_alias}.childid = {$contextidfield}
                GROUP BY {$dlineage_alias}.parentid, {$dra_alias}.roleid
";
        }
        $params = array($paramcapability => $capability);

        // Now wrap it all up in one query:
        // - expand lineage
        // - filter out less specific permissions
        // - remove prevents, leaving only most specific allows
        // - filter out permissions assigned below the level we are checking

        $ctx_alias = $DB->get_unique_param('ctx_alias');
        $role_cap_alias = $DB->get_unique_param('role_cap_alias');
        $role_ass_alias = $DB->get_unique_param('role_ass_alias');
        $ctx_map_alias = $DB->get_unique_param('ctx_alias_map');
        $paramcapability = $DB->get_unique_param('cap');
        $allowpreventsql = "
          SELECT 'x'
            FROM {context_map} {$lineage_alias}
            JOIN (
{$roleassignmentssql}
                 ) {$role_ass_alias} ON {$role_ass_alias}.contextid = {$lineage_alias}.parentid
            JOIN {context_map} {$ctx_map_alias} ON {$ctx_map_alias}.childid = {$lineage_alias}.childid
            JOIN {role_capabilities} {$role_cap_alias} ON {$role_ass_alias}.roleid = {$role_cap_alias}.roleid AND {$role_cap_alias}.contextid = {$ctx_map_alias}.parentid
                 AND {$role_cap_alias}.capability = :$paramcapability AND {$role_cap_alias}.permission = {$capallow}
            JOIN {context} {$ctx_alias} ON {$role_cap_alias}.contextid = {$ctx_alias}.id
            JOIN (
{$maxdepthsql}
                 ) {$max_depth_alias} ON {$max_depth_alias}.roleid = {$role_ass_alias}.roleid AND {$ctx_alias}.depth = {$max_depth_alias}.depth AND {$max_depth_alias}.parentid = {$lineage_alias}.parentid $mysqlhack
           WHERE {$lineage_alias}.childid = {$contextidfield}
";
        $params = array_merge($params, array($paramcapability => $capability));

        if (self::has_prohibit_flag_being_used_anywhere($capability)) {
            // The prohibit permission has been used at least once for the capability, ensure we allow for it.
            list($prohibitsql, $prohibitparams) = self::get_prohibit_check_sql($capability, $userid, $contextidfield);
            $allowpreventsql .= " AND NOT EXISTS (\n{$prohibitsql}\n                )";
            $params = array_merge($params, $prohibitparams);
        }

        return array($allowpreventsql, $params);
    }

    /**
     * Given an SQL field containing a context id, return an SQL snippet that returns
     * non-zero number of rows if the specified user is assigned any roles in that context which
     * specifies the PROHIBIT permission on the specified capability.
     *
     * @param string $capability     A capability to check for.
     * @param int    $userid         ID of the user to check permissions for.
     * @param string $contextidfield Field linking to the context id in the original query.
     *
     * @return array Array of SQL and parameters that generate the query.
     */
    private static function get_prohibit_check_sql($capability, $userid, $contextidfield) {
        global $DB;

        // Build role assignment subquery.
        $roleassignmentssql = self::get_role_assignments_subquery($userid);

        $prohibit = CAP_PROHIBIT;

        $ctx_map_alias = $DB->get_unique_param('ctx_alias_map');
        $lineage_alias = $DB->get_unique_param('lineage_alias');
        $ra_alias = $DB->get_unique_param('ra_alias');
        $rc_alias = $DB->get_unique_param('rc_alias');
        $paramcapability = $DB->get_unique_param('cap');
        $prohibitsql = "
            SELECT 'x'
              FROM {context_map} {$lineage_alias}
              JOIN (
{$roleassignmentssql}
                   ) {$ra_alias} ON {$ra_alias}.contextid = {$lineage_alias}.parentid
              JOIN {context_map} {$ctx_map_alias} ON {$ctx_map_alias}.childid = {$lineage_alias}.childid
              JOIN {role_capabilities} {$rc_alias} ON {$ra_alias}.roleid = {$rc_alias}.roleid AND {$rc_alias}.contextid = {$ctx_map_alias}.parentid
                   AND {$rc_alias}.capability = :$paramcapability AND {$rc_alias}.permission = {$prohibit}
             WHERE {$lineage_alias}.childid = {$contextidfield}
";
        $params = array($paramcapability => $capability);
        return array($prohibitsql, $params);
    }

    /**
     * Populate the context map table with the latest data from context table.
     *
     * Note this function includes a direct mapping between the item and itself in addition
     * to each parent child relation. If you want parents only you can exclude this but in
     * most cases you want the full context path.
     *
     * NOTE: this may be extremely slow on large installations
     *
     * @param bool $verbose print perf info to output
     */
    public static function build_context_map($verbose = false) {
        global $DB;

        list($afterbuild, $countthreshold) = self::get_analyze_context_table_configs();

        // NOTE: it is very unlikely there are any extra entries,
        //       so performance for fast detection only, deleting itself can be slow.

        if ($afterbuild) {
            // We want context stats to be in top shape because it will be used heavily in joins.
            self::analyze_table('context');
        }

        // Make sure only existing contexts are referenced,
        // this may happen if context deletion is interrupted.
        if ($verbose) {
            echo str_pad(userdate(time(), '%H:%M:%S'), 10) . 'Deleting entries for non-existent contexts' . "\n";
        }
        $sql = "SELECT map.id
                  FROM {context_map} map
             LEFT JOIN {context} parent ON parent.id = map.parentid
             LEFT JOIN {context} child ON child.id = map.childid
                 WHERE child.id IS NULL OR parent.id IS NULL";
        $start = time();
        $entries = $DB->get_records_sql($sql);
        if ($entries) {
            $entries = array_keys($entries);
            list($select, $params) = $DB->get_in_or_equal($entries);
            $DB->delete_records_select('context_map', "id $select", $params);
        }
        if ($verbose) {
            $duration = time()  - $start;
            $seconds = $duration % 60;
            $minutes = (int)floor($duration / 60);
            echo str_pad(userdate(time(), '%H:%M:%S'), 10) . '... done, ' . count($entries) . " deleted, duration $minutes'$seconds\"\n";
        }

        // Now remove invalid entries using current context paths,
        // these are most likely result of somebody hacking database tables directly,
        // so anything found is highly suspicious.
        if ($verbose) {
            echo str_pad(userdate(time(), '%H:%M:%S'), 10) . 'Deleting invalid context map entries' . "\n";
        }
        $sql = "SELECT map.id
                  FROM {context_map} map
                  JOIN {context} child ON child.id = map.childid AND child.path IS NOT NULL
                  JOIN {context} parent ON parent.id = map.parentid AND parent.path IS NOT NULL
                 WHERE parent.id <> child.id AND child.path NOT LIKE " . $DB->sql_concat('parent.path', "'/%'");
        $start = time();
        $entries = $DB->get_records_sql($sql);
        if ($entries) {
            debugging('Incorrect entries detected in context_map table, this is likely a result of unsupported changes in context table.', DEBUG_DEVELOPER);
            $entries = array_keys($entries);
            list($select, $params) = $DB->get_in_or_equal($entries);
            $DB->delete_records_select('context_map', "id $select", $params);
        }
        if ($verbose) {
            $duration = time()  - $start;
            $seconds = $duration % 60;
            $minutes = (int)floor($duration / 60);
            echo str_pad(userdate(time(), '%H:%M:%S'), 10) . '... done, ' . count($entries) . " deleted, duration $minutes'$seconds\"\n";
        }

        // Add missing map entries.
        self::add_missing_map_entries($verbose);
    }

    /**
     * Add missing map entries.
     *
     * @param bool $verbose print perf info to output
     */
    public static function add_missing_map_entries($verbose = false) {
        global $DB;

        list($afterbuild, $countthreshold) = self::get_analyze_context_table_configs();

        // Make sure context_map is in the best shape to get lots of additions.
        if ($afterbuild) {
            self::analyze_table('context_map');
        }

        $syscontextid = SYSCONTEXTID;
        $maxdepth = (int)$DB->get_field_sql("SELECT MAX(depth) FROM {context}");
        if ($maxdepth < 2) {
            // Missing depths and paths, we cannot build the map yet.
            return;
        }

        $sqls = array();

        // Deal with system context.
        $sqls[1] = "INSERT INTO {context_map_temp} (parentid, childid)

                   SELECT {$syscontextid}, child.id
                     FROM {context} child
                LEFT JOIN {context_map} map ON map.parentid = {$syscontextid} AND map.childid = child.id
                    WHERE map.id IS NULL";

        // Add self link.
        $sqls[2] = "INSERT INTO {context_map_temp} (parentid, childid)

                   SELECT child.id, child.id
                     FROM {context} child
                LEFT JOIN {context_map} map ON map.parentid = child.id AND map.childid = child.id
                    WHERE map.id IS NULL";

        // Now fill each level map for remaining levels top to down.
        for ($depth = 3; $depth <= $maxdepth; $depth++) {
            $sqls[$depth] = "INSERT INTO {context_map_temp} (parentid, childid)

                       SELECT parents.parentid, child.id
                         FROM {context} child
                         JOIN {context} parent ON parent.id = child.parentid
                         JOIN {context_map} parents ON parents.childid = parent.id
                    LEFT JOIN {context_map} map ON map.parentid = parents.parentid AND map.childid = child.id
                        WHERE child.depth = {$depth} AND map.id IS NULL";
        }

        $mergesql = "INSERT INTO {context_map} (parentid, childid)

                     SELECT parentid, childid
                       FROM {context_map_temp}";

        // Create temporary table for insert speedups.
        $dbman = $DB->get_manager();
        $temptable = new \xmldb_table('context_map_temp');
        $temptable->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $temptable->add_field('parentid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $temptable->add_field('childid', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, null);
        $temptable->add_key('primary', XMLDB_KEY_PRIMARY, array('id'));
        $dbman->create_temp_table($temptable);

        $lastupdate = false;
        foreach ($sqls as $depth => $sql) {
            $lastupdate = false;
            $depthstart = time();
            if ($verbose) {
                echo str_pad(userdate(time(), '%H:%M:%S'), 10) . "Checking context depth $depth\n";
            }
            $trans = $DB->start_delegated_transaction();
            if ($verbose) {
                echo str_pad(userdate(time(), '%H:%M:%S'), 10) . "  finding missing entries\n";
            }
            $DB->execute($sql);
            $insertedcount = $DB->count_records('context_map_temp');
            if ($insertedcount > 0) {
                if ($verbose) {
                    echo str_pad(userdate(time(), '%H:%M:%S'), 10) . "  inserting $insertedcount missing entries\n";
                }
                $DB->execute($mergesql);
                $DB->execute("TRUNCATE TABLE {context_map_temp}");
            }
            if ($verbose) {
                $duration = time()  - $depthstart;
                $seconds = $duration % 60;
                $minutes = (int)floor($duration / 60);
                echo str_pad(userdate(time(), '%H:%M:%S'), 10) . "...done, duration $minutes'$seconds\"\n";
            }
            $trans->allow_commit();

            // Force stats update after any large number of inserts.
            if ($afterbuild && $insertedcount > $countthreshold) {
                self::analyze_table('context_map');
                $lastupdate = true;
            }
        }

        // Force stats update if it is stale.
        if ($afterbuild && !$lastupdate) {
            self::analyze_table('context_map');
        }
        $dbman->drop_table($temptable);
    }

    /**
     * Get the configutation values of $CFG->analyze_context_table_xxx.
     * Refer to config.example.php for more information.
     *
     * @return array consisting of [ after_build, inserted_count_threshold ]
     */
    public static function get_analyze_context_table_configs() {
        global $CFG;
        $afterbuild = $CFG->analyze_context_table_after_build ?? self::get_default_analyze_context_table_after_build();
        $countthreshold = 1000; // The default threshold was introduced in TL-6630.
        if ($afterbuild) {
            $countthreshold = $CFG->analyze_context_table_inserted_count_threshold ?? $countthreshold;
            if ($countthreshold < 0) {
                $countthreshold = 0;    // Fix up wrong configuration.
            }
        }
        return array($afterbuild, $countthreshold);
    }

    /**
     * Get the default value to be $CFG->analyze_context_table_after_build.
     *
     * @return bool
     */
    private static function get_default_analyze_context_table_after_build() {
        global $DB;

        $dbfamily = $DB->get_dbfamily();

        if ($dbfamily === 'postgres') {
            // PostgreSQL
            return true;
        } else if ($dbfamily === 'mysql') {
            // MySQL & MariaDB
            return false;
        } else if ($dbfamily === 'mssql') {
            // MSSQL
            return false;
        } else {
            // Just return false for unsupported database families; analyze_table() will throw an exception
            return false;
        }
    }

    /**
     * Force update of db table statistics to make sure
     * we get the best performance in complex selects.
     *
     * @param string $tablename
     */
    public static function analyze_table($tablename) {
        global $DB;

        $dbfamily = $DB->get_dbfamily();

        if ($dbfamily === 'postgres') {
            $DB->execute("ANALYZE {{$tablename}}");

        } else if ($dbfamily === 'mysql') {
            $DB->execute("ANALYZE TABLE {{$tablename}}");

        } else if ($dbfamily === 'mssql') {
            $DB->execute("UPDATE STATISTICS {{$tablename}}");

        } else {
            throw new \coding_exception('Unsupported database family: ' . $dbfamily);
        }
    }

    /**
     * To be called from \context::update_moved() only.
     *
     * @internal
     * @param \stdClass $record
     */
    public static function context_moved(\stdClass $record) {
        global $DB;

        $syscontext = \context_system::instance();
        if ($record->id == $syscontext->id) {
            return;
        }

        if (!trim($record->path, '/')) {
            // This should not happen, context cleanup task will fix it later.
            debugging('Context path not present, cannot rebuild map after context moved', DEBUG_DEVELOPER);
            $DB->delete_records('context_map', ['childid' => $record->id]);
            $DB->delete_records('context_map', ['parentid' => $record->id]);
            return;
        }

        $currentparentpmap = $DB->get_records_menu('context_map', ['childid' => $record->id], '', 'parentid, childid');

        $parents = explode('/', trim($record->path, '/'));
        $children = $DB->get_fieldset_select('context', 'id', "path LIKE ?", [$record->path . '/%']);
        array_unshift($children, $record->id);

        try {
            // Add missing.
            foreach ($parents as $pid) {
                if (isset($currentparentpmap[$pid])) {
                    unset($currentparentpmap[$pid]);
                    continue;
                }
                foreach ($children as $chid) {
                    $DB->insert_record('context_map', ['parentid' => $pid, 'childid' => $chid]);
                }
            }
            // Delete extras.
            foreach ($currentparentpmap as $pid => $unused) {
                list($select, $params) = $DB->get_in_or_equal($children, SQL_PARAMS_NAMED);
                $params['parentid'] = $pid;
                $DB->delete_records_select('context_map', "parentid = :parentid and childid $select", $params);
            }

        } catch (\moodle_exception $e) {
            // This should not happen, context cleanup task will fix it later.
            debugging('Error rebuilding context map after context moved', DEBUG_DEVELOPER);
            $DB->delete_records('context_map', ['childid' => $record->id]);
            $DB->delete_records('context_map', ['parentid' => $record->id]);
            return;
        }
    }

    /**
     * To be called only from \context::insert_context_record() only.
     *
     * @internal
     * @param \stdClass $record
     */
    public static function context_created(\stdClass $record) {
        global $DB;

        // There should not be any map entries, but make sure we do not create duplicates by deleting first.
        $DB->delete_records('context_map', array('childid' => $record->id));

        $parents = trim($record->path ?? '', '/');
        if (!$parents) {
            // This should not happen, admin will have to do a full rebuild from CLI later.
            return;
        }

        $parents = explode('/', $parents);

        $records = array();
        foreach ($parents as $parent) {
            $records[] = array('parentid' => $parent, 'childid' => $record->id);
        }
        $DB->insert_records('context_map', $records);

        // Add parent id if missing.
        if (!isset($record->parentid)) {
            $selfid = array_pop($parents);
            if ($selfid == SYSCONTEXTID) {
                return;
            }
            if ($selfid != $record->id) {
                debugging('Invalid context record supplied to \totara_core\access::context_created(), invalid path', DEBUG_DEVELOPER);
                return;
            }
            $parentid = array_pop($parents);
            if (!$parentid) {
                debugging('Invalid context record supplied to \totara_core\access::context_created(), malformed path', DEBUG_DEVELOPER);
                return;
            }
            $record->parentid = $parentid;
            $DB->set_field('context', 'parentid', $parentid, array('id' => $record->id));
        }
    }

    /**
     * To be called only from \context::delete() only.
     *
     * @internal
     * @param int $contextid
     */
    public static function context_deleted($contextid) {
        global $DB;

        // NOTE: all the children of this context should have been already deleted,
        //       so no need to delete_records via parentid.

        $DB->delete_records('context_map', array('childid' => $contextid));
    }
}

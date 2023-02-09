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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\rb;

use context_user;
use core\entity\tenant;
use mod_perform\controllers\reporting\performance\export;
use totara_core\access;

class util {

    /**
     * Returns SQL to filter activities by activities the user is allowed to see
     *
     * @param int $user_id
     * @param string $activity_id_column
     * @return array containing: [sql, params]
     */
    public static function get_report_on_subjects_activities_sql(int $user_id, string $activity_id_column) {
        global $DB, $CFG;

        $user_context = context_user::instance($user_id);

        $sql = "
            SELECT a.id 
            FROM {perform} a
            JOIN {perform_track} t ON a.id = t.activity_id
            JOIN {perform_track_user_assignment} tua ON t.id = tua.track_id
            JOIN {perform_subject_instance} si ON tua.id = si.track_user_assignment_id
            JOIN {context} c ON si.subject_user_id = c.instanceid AND c.contextlevel = ".CONTEXT_USER."
        ";
        $params = [];

        if (has_capability('mod/perform:report_on_all_subjects_responses', $user_context, $user_id)) {
            // If multi tenancy is enabled and the current user is a tenant member then
            // we make sure that the user can report on all other subjects which are in the same tenant.
            // If the user is not in an tenant and isolation is enabled we make sure he can only report on users who are NOT in a tenant.
            // If the user is not in an tenant and isolation is disabled than he can report on all users.
            if (!empty($CFG->tenantsenabled)) {
                if ($user_context->tenantid) {
                    $tenant = tenant::repository()->find($user_context->tenantid);
                    $sql .= " JOIN {cohort_members} tp ON si.subject_user_id = tp.userid AND tp.cohortid = :mt_tenant_id";
                    $params = ['mt_tenant_id' => $tenant->cohortid];
                } else if (!empty($CFG->tenantsisolated)) {
                    $sql .= " JOIN {user} tpu ON si.subject_user_id = tpu.id AND tpu.tenantid IS NULL";
                } else {
                    return ['1 = 1', []];
                }

                return ["{$activity_id_column} IN ({$sql})", $params];
            }

            return ['1 = 1', []];
        }

        // Early exit if they can not even potentially report on any participants
        if (!has_role_with_capability(
            'mod/perform:report_on_subject_responses',
            [CONTEXT_USER, CONTEXT_SYSTEM, CONTEXT_TENANT],
            $user_id
        )) {
            return ['1 = 0', []];
        }

        [$cap_sql, $cap_params] = access::get_has_capability_sql('mod/perform:report_on_subject_responses', 'c.id', $user_id);

        $params = array_merge($params, $cap_params);
        $sql .= " WHERE {$cap_sql}";

        $sql = "{$activity_id_column} IN ({$sql})";

        return [$sql, $params];
    }

    /**
     * Return SQL and params to apply to a report SQL query in order to filter to only users where the viewing
     * user can manage those user's participation.
     *
     * @param int $report_for User ID of user who is viewing
     * @param string $user_id_field String referencing database column containing user ids to filter.
     * @return array Array containing SQL string and array of params
     */
    public static function get_manage_participation_sql(int $report_for, string $user_id_field) {
        // If user can manage participation across all users don't do the per-row restriction at all.
        $user_context = context_user::instance($report_for);
        if (has_capability('mod/perform:manage_all_participation', $user_context, $report_for)) {
            return self::get_tenant_user_sql($user_context, $user_id_field);
        }

        $capability = 'mod/perform:manage_subject_user_participation';

        // Early exit if they can not even potentially manage any participants
        if (!has_role_with_capability(
            $capability,
            [CONTEXT_USER, CONTEXT_SYSTEM, CONTEXT_TENANT],
            $report_for
        )) {
            return ['1 = 0', []];
        }

        return access::get_has_capability_sql($capability, 'ctx.id', $report_for);
    }

    /**
     * Return SQL and params to apply to an SQL query in order to filter to only users where the viewing
     * user can see performance data belonging to the subject user.
     *
     * @param int $report_for User ID of user who is viewing
     * @param string $user_id_field String referencing database column containing user ids to filter.
     * @param string $context_join
     * @return array Array containing SQL string and array of params
     */
    public static function get_report_on_subjects_sql(int $report_for, string $user_id_field, string $context_join = 'ctx') {
        global $DB;

        // If user can manage participation across all users don't do the per-row restriction at all.
        $user_context = context_user::instance($report_for);
        if (has_capability('mod/perform:report_on_all_subjects_responses', $user_context, $report_for)) {
            return self::get_tenant_user_sql($user_context, $user_id_field);
        }

        $capability = 'mod/perform:report_on_subject_responses';
        // Early exit if they can not even potentially report on any participants
        if (!has_role_with_capability(
            $capability,
            [CONTEXT_USER, CONTEXT_SYSTEM, CONTEXT_TENANT],
            $report_for
        )) {
            return ['1 = 0', []];
        }

        return access::get_has_capability_sql($capability, $context_join.'.id', $report_for);
    }

    /**
     * Creates the sql part to restrict a report to the users the given context
     * can see (if multi tenancy is enabled)
     *
     * @param context_user $user_context
     * @param string $user_id_field
     * @return array returns the sql and the param part as second value
     */
    public static function get_tenant_user_sql(context_user $user_context, string $user_id_field): array {
        global $CFG;

        if (!empty($CFG->tenantsenabled)) {
            if ($user_context->tenantid) {
                $tenant = tenant::repository()->find_or_fail($user_context->tenantid);
                $tenant_sql = "
                        SELECT id
                        FROM {cohort_members} tp
                        WHERE tp.userid = {$user_id_field} AND tp.cohortid = :tp_cohort_id
                    ";
                $params['tp_cohort_id'] = $tenant->cohortid;

                return ["EXISTS ({$tenant_sql})", $params];
            } else if ($CFG->tenantsisolated) {
                $tenant_sql = "
                    SELECT id
                    FROM {user} tp
                    WHERE tp.id = {$user_id_field}
                        AND tp.tenantid IS NULL
                ";
                return ["EXISTS ({$tenant_sql})", []];
            }
        }

        return ['1=1', []];
    }

    /**
     * Filter export available and build the component prop value
     *
     * @param \reportbuilder $report
     * @return object[]
     */
    public static function export_for_props(\reportbuilder $report): array {
        $current_export_options = $report->get_report_export_options();
        $formats = array_intersect(
            array_keys($current_export_options),
            export::ALLOWED_EXPORT
        );
        return array_map(function ($export) use ($current_export_options) {
            $string = "export_as_{$export}";
            return (object)[
                'type' => $current_export_options[$export]->out(),
                'trigger_string' =>  get_string($string, 'mod_perform')
            ];
        }, $formats);
    }
}

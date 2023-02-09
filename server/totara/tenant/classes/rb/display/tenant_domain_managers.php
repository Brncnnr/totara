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

namespace totara_tenant\rb\display;

use \totara_reportbuilder\rb\display\base;
use \core\output\flex_icon;

/**
 * Users with roles in top tenant category
 */
final class tenant_domain_managers extends base {
    /** @var array static cache */
    protected static $used_roles;

    /**
     * All roles assigned at top tenant category contexts.
     *
     * @param bool $usecache
     * @return array
     */
    public static function get_used_roles(bool $usecache = true): array {
        global $DB;

        if (!PHPUNIT_TEST and $usecache) {
             if (isset(self::$used_roles)) {
                 return self::$used_roles;
             }
        }

        $sql = 'SELECT DISTINCT r.*
                  FROM "ttr_role" r
                  JOIN "ttr_role_assignments" ra ON ra.roleid = r.id
                  JOIN "ttr_context" ctx ON ctx.id = ra.contextid
                  JOIN "ttr_course_categories" cc ON cc.id = ctx.instanceid AND ctx.contextlevel = :catlevel
                  JOIN "ttr_tenant" t ON t.categoryid = cc.id
              ORDER BY r.sortorder';
        $params = ['catlevel' => CONTEXT_COURSECAT];

        $roles = $DB->get_records_sql($sql, $params);
        self::$used_roles = [];
        foreach ($roles as $role) {
            self::$used_roles[$role->id] = role_get_name($role);
        }

        return self::$used_roles;
    }

    /**
     * Display data.
     *
     * @param string $value
     * @param string $format
     * @param \stdClass $row
     * @param \rb_column $column
     * @param \reportbuilder $report
     * @return string
     */
    public static function display($value, $format, \stdClass $row, \rb_column $column, \reportbuilder $report) {
        global $OUTPUT, $DB;

        $tenant = \core\record\tenant::fetch($value);
        $category = \coursecat::get($tenant->categoryid, MUST_EXIST, true);
        $categorycontext = \context_coursecat::instance($category->id);

        $usedroles = self::get_used_roles();
        $printnames = (count($usedroles) > 1);

        $sql = 'SELECT u.*, ra.roleid
                  FROM "ttr_user" u
                  JOIN "ttr_role_assignments" ra ON ra.userid = u.id
                  JOIN "ttr_context" ctx ON ctx.id = ra.contextid
                  JOIN "ttr_role" r ON r.id = ra.roleid
                 WHERE u.deleted = 0 AND ctx.id = :contextid
              ORDER BY r.sortorder ASC, u.lastname ASC, u.firstname ASC';
        $params = ['contextid' => $categorycontext->id];
        $users = $DB->get_recordset_sql($sql, $params);

        $perrole = [];
        foreach ($users as $user) {
            $fullname = fullname($user);
            if ($format === 'html') {
                $ac = \core_user\access_controller::for($user);
                $url = $ac->get_profile_url();
                if ($url) {
                    $fullname = \html_writer::link($url, $fullname);
                }
            }
            $perrole[$user->roleid][] = $fullname;
        }

        $lines = [];
        foreach ($perrole as $roleid => $fullnames) {
            $line = '';
            if ($printnames) {
                $line .= $usedroles[$roleid] . ': ';
            }
            $line .= implode(', ', $fullnames);
            $lines[] = $line;
        }

        if ($format === 'html') {
            if (has_capability('moodle/role:assign', $categorycontext)) {
                if ($perrole or $DB->record_exists('cohort_members', ['cohortid' => $tenant->cohortid])) {
                    $url = new \moodle_url('/admin/roles/assign.php', array('contextid' => $categorycontext->id));
                    $lines[] = $OUTPUT->action_icon($url, new flex_icon('user-add', array('alt' => get_string('assignroles', 'role'))));
                }
            }
        }

        $result = '';
        if ($format === 'html') {
            $result .= implode('<br />', $lines);
        } else {
            $result .= implode("\n", $lines);
        }

        return $result;
    }
}

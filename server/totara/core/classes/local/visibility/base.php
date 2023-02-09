<?php
/*
 * This file is part of Totara LMS
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
 * @author Sam Hemelryk <sam.hemelryk@totaralearning.com>
 * @package totara_core
 */

namespace totara_core\local\visibility;

defined('MOODLE_INTERNAL') || die();

use core\dml\sql;

/**
 * Base visibility resolver implementation.
 *
 * @internal
 */
abstract class base implements resolver {

    /**
     * The SQL separator.
     * This is used by report builder report caching.
     * It should not be used by anything else. Ever!
     * @var string
     */
    private $separator = '.';

    /**
     * Should we skip admin checks?
     * @var bool
     */
    private $skip_admin_checks = true;

    /**
     * Returns an SQL snippet that resolves whether the user has the required capability to view the item given its ID field.
     *
     * @param int $userid
     * @param string $field_id
     * @param \context_user|false $usercontext
     * @return sql
     */
    protected function sql_view_hidden(int $userid, string $field_id, $usercontext): sql {
        return (new sql('EXISTS ('))
            ->append('SELECT 1 FROM (')
            ->append($this->map()->sql_view_hidden_roles($userid, $usercontext))
            ->append('HAVING COUNT(map.roleid) > 0')
            ->append(") vh_r WHERE vh_r.id = {$field_id}")
            ->append(')');
    }

    /**
     * Returns an SQL snippet that resolves whether the item is currently available or not.
     *
     * @param int $userid
     * @param string $tablealias
     * @return sql
     */
    abstract protected function get_availability_sql(int $userid, string $tablealias): sql;

    /**
     * Returns the context level relevant to this item.
     *
     * @return int
     */
    abstract protected function get_context_level(): int;

    /**
     * Returns an SQL snippet restrict visibility.
     *
     * @param int $userid
     * @param string $field_id
     * @param string $field_visible
     * @return sql
     */
    abstract protected function get_visibility_sql(int $userid, string $field_id, string $field_visible): sql;

    /**
     * @inheritDoc
     * @param int $userid
     * @param string $tablealias The item table alias, this is normally one of course, c, prog, p
     * @return sql
     */
    final public function sql_where_visible(int $userid, string $tablealias) : sql {
        global $CFG;

        $usercontext = false;
        if ($userid) {
            $usercontext = \context_user::instance($userid, IGNORE_MISSING);
            if (!$usercontext) {
                // Most likely deleted users - they cannot access anything!
                return new sql('1=0');
            }
        }

        if ($this->can_see_all($userid)) {
            if ($usercontext && !empty($CFG->tenantsenabled)) {
                return $this->get_multitenancy_sql($usercontext, $tablealias);
            }
            return new sql('');
        }

        $separator = $this->sql_separator();
        $visibilitytype = $this->sql_field_visible();

        $field_id = $tablealias . $separator . 'id';
        $field_visible = $tablealias . $separator . $visibilitytype;

        $visibility = $this->get_visibility_sql($userid, $field_id, $field_visible);
        $availability = $this->get_availability_sql($userid, $tablealias);

        if ($usercontext && !empty($CFG->tenantsenabled)) {
            $multitenancy = $this->get_multitenancy_sql($usercontext, $tablealias);
        } else {
            $multitenancy = new sql('');
        }

        // For audience visibility, don't include multitenancy in capability sql.
        if ($visibilitytype == 'audiencevisible') {
            $capability = $this->sql_view_hidden($userid, $field_id, false);
        } else {
            // For traditional visibility, multitenancy should be included in capability sql.
            $capability = $this->sql_view_hidden($userid, $field_id, $usercontext);
        }

        $sql = sql::wrap([$visibility, $availability], ' AND ');
        $sql = sql::wrap([$capability, $sql], ' OR ');
        $sql = sql::wrap([$sql, $multitenancy], ' AND ');
        return $sql;
    }

    /**
     * Returns an SQL snippet to restrict the viewable items by tenancy if required.
     *
     * @param \context_user $context
     * @param string $tablealias
     * @return sql The SQL snippet to use to restrict or null if none is required.
     */
    final public function get_multitenancy_sql(\context_user $context, string $tablealias): sql {
        $separator = $this->sql_separator();
        if ($separator !== '.') {
            // NOTE: rb caching is force turned off when multitenancy is enabled,
            //       we do not have to care about missing ctx_tenantid here, yay!
            throw new \coding_exception('RB caching is supposed to be force disabled when multitenancy is enabled');
        }

        $sql = self::tenant_id_sql($context, 'ctx');

        $param_level = \moodle_database::get_unique_param('level');
        return (new sql("EXISTS (
                SELECT 1
                  FROM {context} ctx
                 WHERE {$tablealias}.id = ctx.instanceid
                   AND ctx.contextlevel = :{$param_level}", [$param_level => $this->get_context_level()]))
            ->append($sql, ' AND ')
            ->append(')');
    }

    /**
     * @inheritDoc
     */
    final public function sql_separator(): string {
        return $this->separator;
    }

    /**
     * @inheritDoc
     */
    final public function set_sql_separator(string $separator) {
        $this->separator = $separator;
    }

    /**
     * @inheritDoc
     */
    final public function set_skip_checks_for_admin(bool $value) {
        $this->skip_admin_checks = $value;
    }

    /**
     * @inheritDoc
     */
    final public function skip_checks_for_admin(): bool {
        return $this->skip_admin_checks;
    }

    /**
     * Returns true if the given user can see all items, regardless of visibility.
     *
     * @param int $userid
     * @return bool
     */
    protected function can_see_all(int $userid) {
        if (is_siteadmin($userid) && $this->skip_checks_for_admin()) {
            return true;
        }
        return false;
    }

    /**
     * Provides the tenantid(s) SQL snippet required for context lookup under multitenancy.
     *
     * @param \context_user $context
     * @param string $ctx_tablealias
     * @return sql
     */
    public static function tenant_id_sql(\context_user $context, string $ctx_tablealias): sql {
        global $CFG;

        if (empty($CFG->tenantsenabled)) {
            return new sql('');
        }

        if (empty($context->tenantid)) {
            return new sql('');
        }

        $param_tenant_id = \moodle_database::get_unique_param('tenant_id');
        if (isguestuser($context->instanceid) or !$context->instanceid) {
            $sql = $ctx_tablealias . '.tenantid IS NULL';
        } else if (!empty($CFG->tenantsisolated)) {
            $sql = new sql($ctx_tablealias . ".tenantid = :{$param_tenant_id}", [$param_tenant_id => $context->tenantid]);
        } else {
            $sql = new sql('( ' . $ctx_tablealias . ".tenantid = :{$param_tenant_id} OR " . $ctx_tablealias . '.tenantid IS NULL )', [$param_tenant_id => $context->tenantid]);
        }

        return $sql;
    }

}
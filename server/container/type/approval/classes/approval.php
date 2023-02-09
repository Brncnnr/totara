<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Chris Snyder <chris.snyder@totaralearning.com>
 * @package container_approval
 */

namespace container_approval;

use context_coursecat;
use core\orm\query\builder;
use core\uuid;
use core_container\container;
use core_container\facade\category_name_provider;
use mod_approval\controllers\workflow\edit;
use mod_approval\event\workflow_deleted;
use mod_approval\model\workflow\workflow;
use moodle_url;

/**
 * Container for approval workflows
 * @method static self from_id(int $id)
 */
final class approval extends container implements category_name_provider {

    /*
     * @const string Default category name string.
     */
    const DEFAULT_CATEGORY_NAME = 'mod-approval-workflow-category';

    /**
     * @param int $tenant_category_id
     * @return int|false
     */
    public static function get_category_id_from_tenant_category(int $tenant_category_id) {
        global $DB;

        $params = [
            'name' => self::DEFAULT_CATEGORY_NAME,
            'parent' => $tenant_category_id
        ];

        return $DB->get_field('course_categories', 'id', $params, IGNORE_MISSING);
    }

    /**
     * @inheritDoc
     */
    public function get_view_url(): moodle_url {
        return new moodle_url(edit::get_url([
            'workflow_id' => workflow::load_by_course_id($this->id)->id,
        ]));
    }

    /**
     * Calculate a new shortname that has not yet been used by any container.
     *
     * @param string $name Name of activity, used to help differentiate potential name.
     * @return string Shortname to use.
     */
    protected static function get_unique_shortname(string $name): string {
        return 'workflow-' . uuid::generate();
    }

    /**
     * @inheritDoc
     */
    protected static function pre_create(\stdClass $data): void {
        // Shortname is not relevant to approval containers, just generate a unique one.
        $name = $data->name ?? '';
        $data->shortname = self::get_unique_shortname($name);

        // TODO will be able to remove this once it's been implemented in parent method.
        $data->containertype = self::get_type();

        parent::pre_create($data);
    }

    /**
     * @param container|static $container
     * @param \stdClass $data
     */
    protected static function post_create(container $container, \stdClass $data): void {
        // Create the container_approval enrolment plugin for this workflow instance.
        approval_enrolment::create_container_instance($container);

        parent::post_create($container, $data);
    }

    /**
     * @inheritDoc
     */
    public static function normalise_data_on_create(\stdClass $data): \stdClass {
        $data = parent::normalise_data_on_create($data);

        // approval containers do not have formats.
        $data->format = 'none';

        return $data;
    }

    /**
     * Delete this workflow alongside its course container.
     * @param boolean $force delete the workflow even if it is in use
     *                       this is *true by default* as the function is called from the core system
     */
    public function delete(bool $force = true): void {
        builder::get_db()->transaction(function () use ($force) {
            $workflow = workflow::load_by_course_id($this->get_id());

            // Trigger event
            workflow_deleted::execute($workflow);
            $workflow->delete_internal($force);

            foreach ($this->get_sections() as $section) {
                $section->delete();
            }
            parent::delete();
        });
    }

    /**
     * @inheritDoc
     */
    public static function get_container_category_name(): string {
        return self::DEFAULT_CATEGORY_NAME;
    }

    /**
     * Get the default category context instance for approval activities.
     * If multi tenancy is turned on and the current user is part of a tenant
     * it will get the category of the tenant.
     *
     * If the category does not exist yet it will automatically create it.
     *
     * @return context_coursecat
     */
    public static function get_default_category_context(): context_coursecat {
        $category_id = self::get_default_category_id();
        return context_coursecat::instance($category_id);
    }
}

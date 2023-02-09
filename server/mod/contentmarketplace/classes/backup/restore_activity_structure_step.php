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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author  Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package mod_contentmarketplace
 */

namespace mod_contentmarketplace\backup;

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

use backup;
use container_course\module\course_module;
use core_plugin_manager;
use mod_contentmarketplace\entity\content_marketplace;
use restore_path_element;
use totara_contentmarketplace\learning_object\factory;
use totara_contentmarketplace\local;
use totara_contentmarketplace\model\course_module_source;

class restore_activity_structure_step extends \restore_activity_structure_step {

    /**
     * @inheritDoc
     */
    protected function define_structure() {
        return $this->prepare_activity_structure([
            new restore_path_element(
                'contentmarketplace',
                '/activity/contentmarketplace'
            ),
        ]);
    }

    /**
     * @inheritDoc
     */
    protected function process_contentmarketplace($data): void {
        $data = (object) $data;
        $old_id = $data->id;
        $learning_object_external_id = $data->learning_object_external_id;
        unset($data->id, $data->learning_object_external_id);

        // Require the relevant marketplace plugin to be enabled.
        $plugininfo = core_plugin_manager::instance()->get_plugin_info($data->learning_object_marketplace_component);
        if (!local::is_enabled() || !$plugininfo || !$plugininfo->is_enabled()) {
            $this->log(
                "Can not restore the content marketplace module with ID $old_id, because " .
                "the $plugininfo->displayname content marketplace is not enabled on this site.",
                backup::LOG_WARNING
            );
            return;
        }

        // Require that the learning object actually exists and has been synced.
        $learning_object = factory::get_resolver($data->learning_object_marketplace_component)
            ->find_by_external_identifier($learning_object_external_id);
        if ($learning_object === null) {
            $this->log(
                "Can not restore the content marketplace module with ID $old_id, because " .
                "$plugininfo->displayname is missing a course learning object with the identifier '$learning_object_external_id'."
                . " It may no longer exist within $plugininfo->displayname, or it may not have been synced into Totara yet.",
                backup::LOG_WARNING
            );
            return;
        }

        $activity = new content_marketplace($data, false);
        $activity->course = $this->get_courseid();
        $activity->time_modified = $this->apply_date_offset($activity->time_modified);
        $activity->learning_object_id = $learning_object->get_id();
        $activity->save();

        $this->apply_activity_instance($activity->id);
        $this->set_mapping('contentmarketplace', $old_id, $activity->id);

        $course_module = course_module::from_entity($activity->course_module);
        course_module_source::create($course_module, $learning_object);
    }

    /**
     * @inheritDoc
     */
    protected function after_execute() {
        $this->add_related_files('mod_contentmarketplace', 'intro', null);
    }

}

<?php
/**
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
 * @author Kunle Odusan <kunle.odusan@totaralearning.com>
 */

namespace degeneration\items\container_perform;

use core\orm\query\builder;
use degeneration\App;
use degeneration\items\item;
use mod_perform\constants;
use mod_perform\entity\activity\activity_setting as activity_setting_entity;
use mod_perform\entity\activity\element as element_entity;
use mod_perform\entity\activity\manual_relationship_selection;
use mod_perform\entity\activity\section as section_entity;
use mod_perform\entity\activity\section_element as section_element_entity;
use mod_perform\entity\activity\section_relationship as section_relationship_entity;
use mod_perform\entity\activity\track as track_entity;
use mod_perform\entity\activity\track_assignment as track_assignment_entity;
use mod_perform\models\activity\activity as activity_model;
use mod_perform\models\activity\activity_setting;
use mod_perform\models\activity\section;
use mod_perform\models\activity\track_assignment_type;
use mod_perform\models\activity\track_status;
use mod_perform\user_groups\grouping;

class activity extends item {

    /**
     * @var $data activity_model
    */
    protected $data;

    private $properties;

    /**
     * @return \mod_perform\testing\generator
     * @throws \coding_exception
     */
    public function get_perform_generator(): \mod_perform\testing\generator {
        $generator = App::generator();

        /** @var \mod_perform\testing\generator $perform_generator */
        $perform_generator = $generator->get_plugin_generator('mod_perform');

        return $perform_generator;
    }

    /**
     * @inheritDoc
     */
    public function get_properties(): array {
        return $this->properties;
    }

    /**
     * @param array $properties
     */
    private function set_properties(array $properties): void {
        $this->properties = $properties;
    }

    private function get_core_relationships(): array {
        return $this->get_properties()['options']['core_relationships'];
    }

    private function get_element_data_generator(): element_data_generator {
        return $this->get_properties()['options']['element_data_generator'];
    }

    public function create_activity(array $configuration) {
        $perform_generator = $this->get_perform_generator();
        $this->set_properties($configuration);
        $fake_name = App::faker()->catchPhrase;
        $configuration['information']['activity_name'] = empty($configuration['information']['activity_name'])
            ? $fake_name
            : sprintf('%s:: %s',$fake_name, $configuration['information']['activity_name']);
        $this->data = $perform_generator->create_activity_in_container($configuration['information']);

        $this->create_default_configs($configuration);

        if (!empty($configuration['settings'])) {
            $this->fill_settings($configuration['settings']);
        }

        return $this;
    }

    /**
     * Sets default configurations to ensure the activity is valid to be activated.
     */
    private function create_default_configs(array $config) {
        $this->create_default_section_details();
        $this->create_default_track($config['track']);
    }

    private function create_default_track(array $track_config) {
        $track_entity = new track_entity();
        $track_entity->activity_id = $this->data->id;
        $track_entity->description = '';
        $track_entity->status = track_status::ACTIVE;
        $track_entity->subject_instance_generation = empty($track_config['subject_instance_generation'])
            ? track_entity::SUBJECT_INSTANCE_GENERATION_ONE_PER_SUBJECT
            : $track_config['subject_instance_generation'];
        $track_entity->schedule_is_open = true;
        $track_entity->schedule_is_fixed = true;
        $track_entity->schedule_fixed_from = time();
        $track_entity->schedule_fixed_to = null;
        $track_entity->schedule_dynamic_from = null;
        $track_entity->schedule_dynamic_to = null;
        $track_entity->schedule_dynamic_source = null;
        $track_entity->due_date_is_enabled = false;
        $track_entity->due_date_is_fixed = null;
        $track_entity->due_date_fixed = null;
        $track_entity->due_date_offset = null;
        $track_entity->repeating_is_enabled = false;
        $track_entity->repeating_type = null;
        $track_entity->repeating_offset = null;
        $track_entity->repeating_is_limited = null;
        $track_entity->repeating_limit = null;
        $track_entity->save();

        foreach ($track_config['audiences'] as $audience_id) {
            $track_assignment_entity = new track_assignment_entity();
            $track_assignment_entity->track_id = $track_entity->id;
            $track_assignment_entity->type = track_assignment_type::ADMIN;
            $track_assignment_entity->user_group_type = grouping::COHORT;
            $track_assignment_entity->user_group_id = $audience_id;
            $track_assignment_entity->expand = true;
            $track_assignment_entity->created_by = 0;
            $track_assignment_entity->save();
        }
    }

    /**
     * Creates subject as relationship to default section and an answerable section element.
     */
    private function create_default_section_details() {
        /** @var section $default_section*/
        $default_section = $this->data->sections->first();
        $this->create_default_section_relationship($default_section->id);
        $this->create_default_section_element($default_section->id);
    }

    private function create_default_section_relationship(int $default_section_id) {
        $section_relationship_entity = new section_relationship_entity();
        $section_relationship_entity->section_id = $default_section_id;
        $section_relationship_entity->core_relationship_id = $this->get_core_relationships()[constants::RELATIONSHIP_SUBJECT]->id;
        $section_relationship_entity->can_view = true;
        $section_relationship_entity->can_answer = true;
        $section_relationship_entity->save();
    }

    private function create_default_section_element(int $section_id) {
        $element_data = $this->get_element_data_generator()->generate_data(
            [
                'plugin_name' => 'short_text'
            ]
        );
        $element_id = $this->create_element($element_data);
        $this->create_section_element($section_id, $element_id);
    }

    private function create_element(array $element_data) {
        $entity = new element_entity();
        $entity->context_id = $this->data->get_context()->id;
        $entity->plugin_name = $element_data['plugin_name'];
        $entity->title = $element_data['title'];
        $entity->identifier_id = $element_data['identifier'];
        $entity->data = $element_data['data'];
        $entity->is_required = $element_data['required'];
        $entity->save();

        return $entity->id;
    }

    private function create_section_element($section_id, $element_id, $sort_order = 1) {
        $entity = new section_element_entity();
        $entity->section_id = $section_id;
        $entity->element_id = $element_id;
        $entity->sort_order = $sort_order;
        $entity->save();
    }

    private function fill_settings(array $settings_config) {
        foreach ($settings_config as $group_name => $group_settings) {
            $settings_processor = "set_{$group_name}_settings";
            if (!empty($settings_config[$group_name])
                && method_exists($this, $settings_processor)
            ) {
                $this->{$settings_processor}($group_settings);
            }
        }
    }

    private function set_general_settings(array $config) {
        if (!empty($config['visibility_condition'])) {
            $activity_setting = new activity_setting_entity();
            $activity_setting->activity_id = $this->data->id;
            $activity_setting->name = 'visibility_condition';
            $activity_setting->value = $config['visibility_condition'];
            $activity_setting->save();
        }

        if (!empty($config['multisection'])) {
            $activity_setting = new activity_setting_entity();
            $activity_setting->activity_id = $this->data->id;
            $activity_setting->name = 'multisection';
            $activity_setting->value = $config['multisection'];
            $activity_setting->save();
        }

        if (!empty($config['manual_relationships'])) {
            foreach ($config['manual_relationships'] as $manual_relationship => $selector_relationship) {
                manual_relationship_selection::repository()
                    ->where('activity_id', $this->data->id)
                    ->where('manual_relationship_id', $this->get_core_relationships()[$manual_relationship]->id)
                    ->update(
                        [
                            'selector_relationship_id' => $this->get_core_relationships()[$selector_relationship]->id,
                        ]
                    );
            }
        }
    }

    private function set_content_settings(array $config) {
        if (!empty($config[activity_setting::CLOSE_ON_COMPLETION])) {
            $activity_setting = new activity_setting_entity();
            $activity_setting->activity_id = $this->data->id;
            $activity_setting->name = activity_setting::CLOSE_ON_COMPLETION;
            $activity_setting->value = $config[activity_setting::CLOSE_ON_COMPLETION];
            $activity_setting->save();
        }

        if (!empty($config['sections'])) {
            $this->set_general_settings(['multisection' => 1]);
            $this->create_section_details($config['sections']);
        }
    }

    private function create_section_details(array $sections_config) {
        $sort_order = 1;
        foreach ($sections_config as $section_config) {
            $number_of_sections = $section_config['count'] ?? 1;

            for ($i = 0; $i < $number_of_sections; $i++ ) {
                $section_entity = new section_entity();
                $section_entity->activity_id = $this->data->id;
                $section_entity->title = App::faker()->catchPhrase;
                $section_entity->sort_order = $sort_order;
                $section_entity->save();

                empty($section_config['relationships'])
                    ? $this->create_default_section_relationship($section_entity->id)
                    : $this->create_section_relationships($section_entity->id, $section_config['relationships']);

                $this->create_default_section_element($section_entity->id);

                if (!empty($section_config['other_elements'])) {
                    $this->create_section_elements($section_entity->id, $section_config['other_elements']);
                }
                $sort_order++;
            }
        }
    }

    private function create_section_relationships(int $section_id, array $relationship_data) {
        $relationships = [];

        foreach ($relationship_data as $rel) {
            $relationships[] = (object)[
                'section_id' => $section_id,
                'core_relationship_id' => $this->get_core_relationships()[$rel['relationship']]->id,
                'can_view' => $rel['can_view'] ?? true,
                'can_answer' => $rel['can_answer'] ?? true,
                'created_at' => time(),
            ];
        }
        builder::get_db()->insert_records('perform_section_relationship', $relationships);
    }

    private function create_section_elements(int $section_id, array $other_elements) {
        $sort_order = 10;
        foreach ($other_elements as $other_element) {
            $element_data = $this->get_element_data_generator()->generate_data($other_element);
            $element_id = $this->create_element($element_data);
            $this->create_section_element($section_id, $element_id, $sort_order);
            $sort_order += 10;
        }
    }
}
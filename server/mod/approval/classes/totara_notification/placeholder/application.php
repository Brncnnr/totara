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
 * @author  Nathan Lewis <nathan.lewis@totaralearning.com>
 * @package mod_approval
 */
namespace mod_approval\totara_notification\placeholder;

use coding_exception;
use html_writer;
use mod_approval\controllers\application\view as view_controller;
use mod_approval\model\application\application as application_model;
use moodle_url;
use totara_notification\placeholder\abstraction\placeholder_instance_cache;
use totara_notification\placeholder\abstraction\single_emptiable_placeholder;
use totara_notification\placeholder\option;

class application extends single_emptiable_placeholder {

    use placeholder_instance_cache;

    /**
     * @var ?application_model
     */
    private $model;

    /**
     * application constructor.
     * @param application_model|null $model
     */
    public function __construct(?application_model $model) {
        $this->model = $model;
    }

    /**
     * @param int $id
     *
     * @return self
     */
    public static function from_id(int $id): self {
        $instance = self::get_cached_instance($id);
        if (!$instance) {
            $model = application_model::load_by_id($id);
            $instance = new static($model);
            self::add_instance_to_cache($id, $instance);
        }
        return $instance;
    }

    /**
     * @param application_model $model
     *
     * @return self
     */
    public static function from_model(application_model $model): self {
        // Use the new model, ignoring cache - the model might have been modified, and it costs almost nothing to create a new one.
        $instance = new static($model);
        self::add_instance_to_cache($model->id, $instance);
        return $instance;
    }

    /**
     * @return option[]
     */
    public static function get_options(): array {
        return [
            option::create(
                'title',
                get_string('notification_placeholder:application_title', 'mod_approval')
            ),
            option::create(
                'title_linked',
                get_string('notification_placeholder:application_title_linked', 'mod_approval')
            ),
            option::create(
                'id_number',
                get_string('notification_placeholder:application_id_number', 'mod_approval')
            ),
            option::create(
                'current_approval_level',
                get_string('notification_placeholder:application_current_approval_level', 'mod_approval')
            ),
            option::create(
                'current_stage_name',
                get_string('notification_placeholder:application_current_stage_name', 'mod_approval')
            ),
            option::create(
                'type',
                get_string('notification_placeholder:application_type', 'mod_approval')
            ),
        ];
    }

    /**
     * @param string $key
     * @return bool
     */
    protected function is_available(string $key): bool {
        return null !== $this->model;
    }

    /**
     * @param string $key
     * @return string
     */
    public function do_get(string $key): string {
        if (null === $this->model) {
            throw new coding_exception("The application model is empty");
        }

        switch ($key) {
            case 'title':
                return $this->model->title;
            case 'title_linked':
                $url = view_controller::get_url_for($this->model->id);
                return html_writer::link($url, format_string($this->model->title));
            case 'id_number':
                return $this->model->id_number;
            case 'current_approval_level':
                if (empty($this->model->current_approval_level)) {
                    return '';
                } else {
                    return $this->model->current_approval_level->name;
                }
            case 'current_stage_name':
                if (empty($this->model->current_stage)) {
                    return '';
                } else {
                    return $this->model->current_stage->name;
                }
            case 'type':
                return $this->model->workflow_type->name;
        }

        throw new coding_exception("Invalid key '{$key}'");
    }

    /**
     * @param string $key
     * @return bool
     */
    public static function is_safe_html(string $key): bool {
        if ('title_linked' === $key) {
            return true;
        }

        return parent::is_safe_html($key);
    }
}
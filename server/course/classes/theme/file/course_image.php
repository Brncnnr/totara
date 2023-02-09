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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Johannes Cilliers <johannes.cilliers@totaralearning.com>
 * @package core_course
 */

namespace core_course\theme\file;

use context;
use context_system;
use core\files\type\file_type;
use core\files\type\web_image;
use core\theme\file\theme_file;
use core\theme\settings;
use moodle_url;
use theme_config;

/**
 * Class course_image
 *
 * This file handler is used to get a different version of a file belonging to
 * the theme and within specific context that we have access to, otherwise the
 * default file will be be fetched.
 *
 * This file handler is also used by theme settings to generate a dynamic list
 * of files that can be customised by a user.
 * @see settings
 * @see theme_file
 *
 * @package core_course\theme\file
 */
class course_image extends theme_file {

    /**
     * course_image constructor.
     *
     * @param theme_config|null $theme_config
     */
    public function __construct(?theme_config $theme_config = null) {
        parent::__construct($theme_config);
        $this->type = new web_image();
    }

    /**
     * @inheritDoc
     */
    public static function get_id(): string {
        return 'course/defaultimage';
    }

    /**
     * @inheritDoc
     */
    public function get_component(): string {
        return 'course';
    }

    /**
     * @inheritDoc
     */
    public function get_area(): string {
        return 'defaultcourseimage';
    }

    /**
     * @inheritDoc
     */
    public function get_ui_key(): string {
        return 'learncourse';
    }

    /**
     * @inheritDoc
     */
    public function get_ui_category(): string {
        return 'images';
    }

    /**
     * @inheritDoc
     */
    public function get_type(): file_type {
        return $this->type;
    }

    /**
     * @inheritDoc
     */
    public function get_default_url(): moodle_url {
        global $OUTPUT;

        $system_context = context_system::instance();
        $fs = get_file_storage();

        // First check whether there's a default image on the system level
        $files = $fs->get_area_files(
            $system_context->id,
            'course',
            'defaultimage',
            0,
            'timemodified DESC',
            false
        );

        if ($files) {
            $file = reset($files);
            return moodle_url::make_pluginfile_url(
                $system_context->id,
                'course',
                'defaultimage',
                theme_get_revision(),
                '/',
                $file->get_filename(),
                false
            );
        }

        return $OUTPUT->image_url(
            'course_defaultimage',
            'core',
            false
        );
    }

}
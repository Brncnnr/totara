<?php
/**
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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package totara_core
 */
namespace totara_core\hook;

use core_container\hook\base_redirect;
use stdClass;

/**
 * A hook to allow any plugins to redirect away from adding module to course page.
 * Page where it is being used: /course/modedit.php
 */
final class mod_add extends base_redirect {

    /** @var stdClass */
    private $course;

    /** @var string|null */
    private $module_name;

    /** @var int|null */
    private $section_id;

    /**
     * mod_add constructor.
     *
     * @param $course
     *
     * @param int|null $section_id
     */
    public function __construct($course, ?string $module_name = null, ?int $section_id = null) {
        parent::__construct($course);
        $this->course = $course;
        $this->module_name = $module_name;
        $this->section_id = $section_id;
    }

    /**
     * @return stdClass
     */
    public function get_course(): stdClass {
        return $this->course;
    }

    /**
     * @return string|null
     */
    public function get_module_name(): ?string {
        return $this->module_name;
    }

    /**
     * @return int|null
     */
    public function get_section_id(): ?int {
        return $this->section_id;
    }

}
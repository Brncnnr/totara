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
 * @author Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package container_site
 */
namespace container_site;

use core_container\container;
use core_container\module\module;
use container_course\course;
use container_site\module\site_module;

/**
 * Site is just another course
 */
final class site extends course {
    /**
     * This is a site, so we should use SITE for the record.
     *
     * @param \stdClass $record
     * @return void
     */
    protected function map_record(\stdClass $record): void {
        $site = get_site();

        if ($site->id != $record->id) {
            // Allowing comparing numeric string with numeric value, otherwise it will never yield true result.
            debugging("Constructing a site container from a record that is not a site", DEBUG_DEVELOPER);
        }

        parent::map_record($site);
    }

    /**
     * @param int $id
     * @return container
     */
    public static function from_id(int $id): container {
        if (0 != $id && $id != SITEID) {
            debugging("The id is not one of the SITEID: '{$id}'", DEBUG_DEVELOPER);
        }

        // It is always a SITEID
        $id = SITEID;
        return parent::from_id($id);
    }

    /**
     * Create a module of its own kind.
     *
     * @param \stdClass $newcm
     * @return site_module
     */
    protected function create_module(\stdClass $newcm): module {
        return site_module::create($newcm);
    }

    /**
     * @return \context
     */
    public function get_context(): \context {
        if (null == $this->context) {
            $this->context = \context_system::instance();
        }

        return $this->context;
    }

    /**
     * Everyone can see a site
     * @param int|null $userid
     * @return bool
     */
    public function is_visible(int $userid = null): bool {
        return true;
    }

    /**
     * @param \stdClass  $data
     * @param int|null   $actorid
     *
     * @return container
     */
    public static function create(\stdClass $data, ?int $actorid = null): container {
        throw new \coding_exception("Site cannot be created");
    }

    /**
     * @param \stdClass  $data
     * @return bool
     */
    public function update(\stdClass $data): bool {
        throw new \moodle_exception('invalidcourse', 'error');
    }

    /**
     * @return bool
     */
    public static function is_site(): bool {
        return true;
    }

    /**
     * Default category id of site is zero.
     *
     * @return int
     */
    public static function get_default_category_id(): int {
        return 0;
    }

    /**
     * @throws \coding_exception
     */
    public function delete(): void {
        throw new \coding_exception("Site cannot be deleted");
    }
}
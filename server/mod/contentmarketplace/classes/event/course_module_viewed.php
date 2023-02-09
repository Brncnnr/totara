<?php
/**
 * This file is part of Totara Core
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
 * @author  Kian Nguyen <kian.nguyen@totaralearning.com>
 * @package mod_contentmarketplace
 */
namespace mod_contentmarketplace\event;

use core\event\course_module_viewed as base;
use mod_contentmarketplace\model\content_marketplace;

/**
 * Event for viewing course module.
 *
 * @method static course_module_viewed create(array $data)
 */
class course_module_viewed extends base {
    /**
     * @return void
     */
    protected function init(): void {
        $this->data['objecttable'] = 'contentmarketplace';
        parent::init();
    }

    /**
     * @param content_marketplace $content_marketplace
     * @param int|null $user_id
     *
     * @return course_module_viewed
     */
    public static function from_model(content_marketplace $content_marketplace, ?int $user_id = null): course_module_viewed {
        return static::create([
            'objectid' => $content_marketplace->course_module->id,
            'userid' => $user_id,
            'courseid' => $content_marketplace->course_id,
            'context' => $content_marketplace->context,
        ])
            ->add_entity_snapshot($content_marketplace->course)
            ->add_entity_snapshot($content_marketplace->get_entity_copy());
    }
}
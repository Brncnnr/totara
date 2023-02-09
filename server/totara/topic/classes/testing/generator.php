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
 * @package totara_topic
 */

namespace totara_topic\testing;

use totara_topic\topic;
use totara_topic\provider\topic_provider;
use core_tag\entity\tag_area;
use core_tag\repository\tag_area_repository;

final class generator extends \core\testing\component_generator {
    /**
     * @var string[]
     */
    private static $names;

    /**
     * @param string|null   $name
     * @param string|null   $description
     * @return topic
     */
    public function create_topic(?string $name = null, ?string $description = null): topic {
        if (null == $name) {
            $try = 0;

            while (true) {
                $name = $this->generate_name();
                $topic = topic_provider::find_by_name($name);

                if (null === $topic) {
                    // We got a name.
                    break;
                } else if (100 === $try) {
                    // Or give it a hundred trials before we tear it down.
                    throw new \coding_exception("Tried 100 topic's name, and it failed.");
                }

                $try += 1;
            }
        }

        return topic::create($name, null, $description);
    }

    /**
     * @return tag_area
     */
    public function add_default_area(): tag_area {
        global $CFG;

        /** @var tag_area_repository $repo */
        $repo = tag_area::repository();
        $area = $repo->find_for_component('totara_topic', 'topic');

        if (null === $area) {
            $area = new tag_area();
            $area->component = 'totara_topic';
            $area->itemtype = 'topic';
            $area->tagcollid = $CFG->topic_collection_id;
            $area->showstandard = 1;

            $area->save();
        }

        return $area;
    }

    /**
     * @return string
     */
    protected function generate_name(): string {
        global $CFG;

        if (null === static::$names) {
            static::$names = require("{$CFG->dirroot}/totara/topic/tests/fixtures/names.php");
        }

        $index = rand(0, (count(static::$names) - 1));
        return static::$names[$index];
    }

    /**
     * This is being used in behat generator
     *
     * @param array $element_data
     * @return void
     */
    public function create_topic_from_params(array $element_data): void {
        if (!isset($element_data['name'])) {
            throw new \coding_exception("Element data does not have property name");
        }

        $this->create_topic($element_data['name']);
    }
}
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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 */

namespace degeneration\items\totara_playlist;

use degeneration\items\item;

final class rating extends item {
    /**
     * @var int
     */
    protected $playlist_id;

    /**
     * @var int
     */
    protected $user_id;

    /**
     * rating constructor.
     *
     * @param int $playlist_id
     * @param int $user_id
     */
    public function __construct(int $playlist_id, int $user_id) {
        $this->playlist_id = $playlist_id;
        $this->user_id = $user_id;
    }

    /**
     * @return array
     */
    public function get_properties(): array {
        return [
            'component' => 'totara_playlist',
            'area' => 'playlist',
            'instanceid' => $this->playlist_id,
            'rating' => rand(0, 5),
            'userid' => $this->user_id,
            'timecreated' => time(),
        ];
    }

    public function get_entity_class(): ?string {
        return \totara_engage\entity\rating::class;
    }

    /**
     * @return array
     */
    public function create_for_bulk(): array {
        $properties = [];

        foreach ($this->get_properties() as $key => $property) {
            $properties[$key] = $this->evaluate_property($property);
        }

        return $properties;
    }
}
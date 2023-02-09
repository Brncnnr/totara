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

namespace degeneration\items\totara_engage;

use degeneration\items\item;

final class share_recipient extends item {
    /**
     * @var int
     */
    protected $share_id;

    /**
     * @var int
     */
    protected $sharer_id;

    /**
     * @var int
     */
    protected $instance_id;

    /**
     * @var string
     */
    protected $component;

    /**
     * @var string|null
     */
    protected $area;

    /**
     * share_recipient constructor.
     *
     * @param int $share_id
     * @param int $sharer_id
     * @param int $instance_id
     * @param string $component
     * @param string|null $area
     */
    public function __construct(int $share_id, int $sharer_id, int $instance_id, string $component, ?string $area) {
        $this->share_id = $share_id;
        $this->sharer_id = $sharer_id;
        $this->instance_id = $instance_id;
        $this->component = $component;
        $this->area = $area;
    }

    /**
     * @return array
     */
    public function get_properties(): array {
        return [
            'shareid' => $this->share_id,
            'sharerid' => $this->sharer_id,
            'component' => $this->component,
            'area' => $this->area,
            'instanceid' => $this->instance_id,
            'visibility' => \totara_engage\share\share::VISIBILITY_VISIBLE,
            'notified' => \totara_engage\share\share::NOT_NOTIFIED,
            'timecreated' => time(),
        ];
    }

    /**
     * @return string|null
     */
    public function get_entity_class(): ?string {
        return \totara_engage\entity\share_recipient::class;
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
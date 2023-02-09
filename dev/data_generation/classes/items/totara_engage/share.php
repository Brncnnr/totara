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
use totara_engage\entity\share as share_entity;

final class share extends item {
    /**
     * @var int
     */
    protected $owner_id;

    /**
     * @var int
     */
    protected $item_id;

    /**
     * @var string
     */
    protected $component;

    /**
     * @var int
     */
    protected $context_id;

    /**
     * share constructor.
     *
     * @param int $owner_id
     * @param int $item_id
     * @param string $component
     * @param int $context_id
     */
    public function __construct(int $owner_id, int $item_id, string $component, int $context_id) {
        $this->owner_id = $owner_id;
        $this->item_id = $item_id;
        $this->component = $component;
        $this->context_id = $context_id;
    }

    /**
     * @return array
     */
    public function get_properties(): array {
        return [
            'itemid' => $this->item_id,
            'ownerid' => $this->owner_id,
            'component' => $this->component,
            'contextid' => $this->context_id,
        ];
    }

    /**
     * @return string|null
     */
    public function get_entity_class(): ?string {
        return share_entity::class;
    }

    /**
     * Add a share recipient
     *
     * @param int $recipient_id
     * @param string $component
     * @param string|null $area
     * @param int $sharer_id
     * @return array
     */
    public function add_recipient(int $recipient_id, string $component, ?string $area, int $sharer_id) {
        return (new share_recipient(
            $this->get_data('id'),
            $sharer_id,
            $recipient_id,
            $component,
            $area
        ))->create_for_bulk();
    }
}
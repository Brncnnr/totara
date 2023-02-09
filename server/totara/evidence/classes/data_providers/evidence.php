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
 * @author Marco Song <marco.song@totaralearning.com>
 * @package totara_evidence
 */

namespace totara_evidence\data_providers;

use totara_evidence\entity\evidence_item as evidence_entity;

class evidence extends provider {

    // Mapping of sort field display names to physical entity _columns_.
    public const SORT_FIELDS = [
        'evidence_id' => 'id',
        'evidence_name' => 'name'
    ];

    /**
     * Creates an instance of the data provider.
     *
     * @return provider the dataprovider.
     */
    public static function create(): provider {
        return new self(
            evidence_entity::class,
            self::SORT_FIELDS,
            'totara_evidence\entity\filters\evidence_item_filters::for'
        );
    }

}

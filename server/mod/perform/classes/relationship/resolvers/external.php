<?php
/*
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
 * @author Marco Song <marco.song@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\relationship\resolvers;

use context;
use mod_perform\entity\activity\subject_instance_manual_participant;
use totara_core\relationship\relationship_resolver;
use totara_core\relationship\relationship_resolver_dto;

/**
 * Relationship resolver for external participants
 *
 * @package mod_perform\relationship\resolvers
 */
class external extends relationship_resolver {

    public const SOURCE = 'external';

    /**
     * @inheritDoc
     */
    public static function get_accepted_fields(): array {
        return [
            ['subject_instance_id'],
            ['user_id'], // Unused, but required to be compatible with other relationships used in mod_perform.
        ];
    }

    /**
     * Returns simple objects that have a name and email attribute.
     *
     * @param array $data
     * @param context $context
     * @return relationship_resolver_dto[]
     */
    protected function get_data(array $data, context $context): array {
        return subject_instance_manual_participant::repository()
            ->select(['name', 'email'])
            ->where('subject_instance_id', $data['subject_instance_id'])
            ->where('core_relationship_id', $this->parent_relationship->id)
            ->get()
            ->map(
                function ($external_participant) {
                    return new relationship_resolver_dto(
                        0,
                        self::SOURCE,
                        [
                            'name'  => $external_participant->name,
                            'email' => $external_participant->email,
                        ]
                    );
                }
            )
            ->all();
    }
}

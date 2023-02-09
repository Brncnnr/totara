<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTDvs
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
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
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package totara_competency
 */

namespace totara_competency\event;

use core\collection;
use core\event\base;
use totara_hierarchy\entity\competency;

class pathways_copied_bulk extends base {
    /**
     * Create instance of event.
     *
     * @param int $copy_op_id unique id identifying this copy operation.
     * @param competency $source source competency.
     * @param collection<competency> $targets target competencies.
     *
     * @return self
     */
    public static function create_for_operation(
        int $copy_op_id,
        competency $source,
        collection $targets
    ): self {
        $data = [
            'objectid' => $copy_op_id,
            'userid' => \core\session\manager::get_realuser()->id,
            'other' => [
                'source_id' => $source->id,
                'target_ids' => $targets->pluck('id')
            ],
            'context' => \context_system::instance()
        ];

        return static::create($data);
    }

    /**
     * {@inheritdoc}
     */
    public static function get_name() {
        return get_string('event_pathways_copied', 'totara_competency');
    }

    /**
     * {@inheritdoc}
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'totara_competency_pathway';
    }

    /**
     * @inheritDoc
     */
    public function get_description() {
        $details = (object)$this->other;
        $target_ids = $details->target_ids;

        return sprintf(
            "[copy id: %d, user: %d] Competency %d pathways were copied to competenc%s %s",
            (int)$this->objectid,
            (int)$this->userid,
            (int)$details->source_id,
            count($target_ids) === 1 ? 'y' : 'ies',
            implode(',', $target_ids)
        );
    }
}
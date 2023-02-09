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

namespace totara_competency\task;

use core\collection;
use core\task\adhoc_task;
use totara_competency\helpers\error;
use totara_competency\helpers\result;
use totara_competency\models\copy_pathway;
use totara_hierarchy\entity\competency;

/**
 * Adhoc task to copy pathways from a reference competency to others.
 */
class copy_pathway_task extends adhoc_task {
    /**
     * @var callable $log sink if any.
     */
    private $write_to_log = null;

    /**
     * Create the task.
     *
     * @param copy_pathway $copier operation details.
     * @param int $user_id the user to use when running this task.
     * @param ?int $test_copy_op_id explicit copy op id. For use in tests only.
     *
     * @return self the created task.
     */
    public static function create(
        copy_pathway $copier,
        int $user_id,
        ?int $test_copy_op_id=null
    ): self {
        $copy_op_id = is_null($test_copy_op_id)
            ? self::generate_copy_op_id()
            : $test_copy_op_id;

        // Note: some competencies may have already been set up as targets by an
        // earlier but yet to run bulk pathway copying task. However, they will
        // be updated as targets for *this* task instead; they will no longer be
        // targets of the earlier task. This switching will work out in the end
        // because the pathways copied to the targets by the earlier task would
        // have been erased by this task anyway.
        competency::repository()
            ->where('id', $copier->targets->pluck('id'))
            ->update(['copy_op_id' => $copy_op_id]);

        $stored = [
            'source_id' => $copier->source->id,
            'copy_op_id' => $copy_op_id,
            'fw_ids' => $copier->frameworks->pluck('id')
        ];

        $task = new self();
        $task->set_component('totara_competency');
        $task->set_custom_data($stored);
        $task->set_userid($user_id);

        return $task;
    }

    /**
     * Generates a copy operation id.
     *
     * @return int the generated copy id.
     */
    private static function generate_copy_op_id(): int {
        $raw_copy_op_id = competency::repository()
            ->select('max(copy_op_id) as max')
            ->one()
            ->max;

        return intval($raw_copy_op_id) + mt_rand(1, 100000);
    }

    /**
     * @inheritDoc
     */
    public function execute() {
        [
            'source_id' => $source_id,
            'copy_op_id' => $copy_op_id,
            'fw_ids' => $fw_ids
        ] = (array)$this->get_custom_data();

        $copy = function () use ($source_id, $copy_op_id, $fw_ids): result {
            $target_ids = competency::repository()
                ->select('id as id')
                ->where('copy_op_id', $copy_op_id)
                ->get()
                ->pluck('id');

            return copy_pathway::create_by_ids($source_id, $target_ids, $fw_ids)
                ->copy($copy_op_id);
        };

        result::try($copy)
            ->map(
                function (collection $copied): void {
                    $count = $copied->count();

                    $this->log(
                        'copied pathways to %s target%s',
                        $count,
                        $count === 1 ? '' : 's'
                    );
                }
            )
            ->or_else(
                function (error $error): void {
                    $this->log('copy failed: %s', $error->message);
                }
            );

        competency::repository()
            ->where('copy_op_id', $copy_op_id)
            ->update(['copy_op_id' => 0]);
    }

    /**
     * Declares the logging function to use. The adhoc task interface is not
     * very test friendly so need a way to check on its execution.
     *
     * @param callable $write_to_log string->void function to log a message.
     *
     * @return self this object.
     */
    public function set_logger(callable $write_to_log): self {
        $this->write_to_log = $write_to_log;
        return $this;
    }

    /**
     * Logs a message.
     *
     * @param string $message the message to log.
     */
    private function log(
        string $format,
        ...$args
    ): void {
        $message = sprintf($format, ...$args);
        $is_test = defined('PHPUNIT_TEST') && PHPUNIT_TEST;

        $write_to_log = $this->write_to_log;
        if ($write_to_log) {
            $write_to_log($message);
        } else if (!$is_test) {
            mtrace($message);
        }
    }
}
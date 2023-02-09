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

namespace totara_competency\webapi\resolver\mutation;

use Closure;
use core\entity\user;
use core\task\manager;
use core\webapi\execution_context;
use core\webapi\middleware\require_advanced_feature;
use core\webapi\middleware\require_authenticated_user;
use core\webapi\middleware\require_site_admin;
use core\webapi\mutation_resolver;
use totara_competency\helpers\error;
use totara_competency\helpers\result;
use totara_competency\helpers\copy_pathway\result as copy_pathway_result;
use totara_competency\models\copy_pathway as copy_pathway_model;
use totara_competency\task\copy_pathway_task;

/**
 * Queues an adhoc task to copy pathway(s) from a reference competency to others.
 */
class copy_pathway extends mutation_resolver {
    /**
     * {@inheritdoc}
     */
    public static function resolve(
        array $args,
        execution_context $ec
    ) {
        return result::try(
            Closure::fromCallable([self::class, 'queue_task']), $args
        );
    }

    /**
     * {@inheritdoc}
     */
    public static function get_middleware(): array {
        return [
            new require_authenticated_user(),
            new require_advanced_feature('competency_assignment'),
            new require_site_admin()
        ];
    }

    /**
     * Sets up an adhoc task to do the copying.
     *
     * @param array<string,mixed> key value pairs from graphql runtime.
     *
     * @return result<collection|error> the result embedding:
     *         - if adhoc task was queued: the target competencies
     *         - if the queueing failed: the error object
     */
    private static function queue_task(array $args): result {
        $run_as = user::logged_in()->id;

        [
            'source_competency_id' => $source_id,
            'target_competency_ids' => $target_ids,
            'allowed_competency_frameworks' => $fw_ids
        ] = $args['input'];

        return copy_pathway_model::create_by_ids($source_id, $target_ids, $fw_ids)
            ->validated()
            ->flat_map(
                function (copy_pathway_model $copier) use ($run_as): result {
                    $task = copy_pathway_task::create($copier, $run_as);

                    return manager::queue_adhoc_task($task, true)
                        ? copy_pathway_result::create($copier)
                        : copy_pathway_result::create(
                            error::cannot_queue_task(copy_pathway_task::class)
                        );
                }
            );
    }
}
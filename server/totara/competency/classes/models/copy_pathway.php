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

namespace totara_competency\models;

use Closure;
use coding_exception;
use core\collection;
use core\orm\entity\repository;
use core\orm\query\builder;
use totara_competency\achievement_configuration;
use totara_competency\entity\pathway as pathway_entity;
use totara_competency\event\pathways_copied_bulk;
use totara_competency\helpers\error;
use totara_competency\helpers\result;
use totara_competency\helpers\validator;
use totara_competency\helpers\copy_pathway\errors;
use totara_competency\helpers\copy_pathway\validator as copy_pathway_validator;
use totara_competency\pathway;
use totara_hierarchy\entity\competency;
use totara_hierarchy\entity\competency_framework;

/**
 * Copies pathway(s) from a reference competency to others.
 *
 * @property-read competency $source
 * @property-read collection<competency> $targets
 * @property-read collection<competency_framework> $frameworks
 */
class copy_pathway {
    /**
     * @var competency competency whose pathways are to be copied.
     */
    private competency $source;

    /**
     * @var collection<competency> target competencies.
     */
    private collection $targets;

    /**
     * @var collection<competency_framework> target competency frameworks.
     */
    private collection $frameworks;

    /**
     * Creates an instance of this object.
     *
     * @param competency $source competency whose pathways are to be copied.
     * @param competency[] $targets target competencies.
     * @param competency_framework[] $frameworks target competency frameworks.
     *
     * @return self the object.
     */
    public static function create(
        competency $source,
        array $targets,
        array $frameworks
    ): self {
        $fw = collection::new(array_unique($frameworks, SORT_REGULAR));

        $competencies = collection::new(array_unique($targets, SORT_REGULAR))
            ->filter(
                function (competency $competency) use ($source): bool {
                    // Yes, this can happen!
                    return (int)$competency->id !== (int)$source->id;
                }
            );

        return new self($source, $competencies, $fw);
    }

    /**
     * Creates an instance of this object.
     *
     * @param int $source_id id of competency whose pathways are to be copied.
     * @param int[] $target_ids target competency ids.
     * @param int[] $framework_ids target competency framework ids.
     *
     * @return self the object.
     */
    public static function create_by_ids(
        int $source_id,
        array $target_ids,
        array $framework_ids
    ): self {
        $source = self
            ::load_entities(
                competency::repository()->with('active_pathways'),
                [errors::class, 'missing_source'],
                $source_id
            )
            ->or_else(
                function (error $error): void {
                    throw $error->exception;
                }
            )
            ->value
            ->first();

        return self::create_by_source_and_target_ids(
            $source, $target_ids, $framework_ids
        );
    }

    /**
     * Creates an instance of this object.
     *
     * @param competency $source competency whose pathways are to be copied.
     * @param int[] $target_ids target competency ids.
     * @param int[] $framework_ids target competency framework ids.
     *
     * @return self the object.
     */
    public static function create_by_source_and_target_ids(
        competency $source,
        array $target_ids,
        array $framework_ids
    ): self {
        $targets = self
            ::load_entities(
                competency::repository()->with('active_pathways'),
                [error::class, 'missing_competencies'],
                ...$target_ids
            )
            ->or_else(
                function (error $error): void {
                    throw $error->exception;
                }
            )
            ->value
            ->all();

        $frameworks = self
            ::load_entities(
                competency_framework::repository(),
                [error::class, 'missing_frameworks'],
                ...$framework_ids,
            )
            ->or_else(
                function (error $error): void {
                    throw $error->exception;
                }
            )
            ->value
            ->all();

        return self::create($source, $targets, $frameworks);
    }

    /**
     * Retrieves entities from the given ids.
     *
     * @param repository $respository repository from which to get entities.
     * @param callable $missing_entity_error int->error method taking a count of
     *        missing entities and giving the error to return to the caller.
     * @param int[] $ids entity ids to retrieve.
     *
     * @return result<collection<entity>|error> the result embedding:
     *         - if the entity ids are valid: the loaded entities
     *         - if the loading failed: the error object
     */
    private static function load_entities(
        repository $repository,
        callable $missing_entity_error,
        int ...$ids
    ): result {
        $unique = array_unique($ids);
        $expected_id_count = count($unique);

        // Since this is done in bulk, it is possible for this SQL statement to
        // exceed the database's allowed packet size. Hence the retrieving of
        // entities in batches.
        $entities = $expected_id_count > 0
            ? collection::new(array_chunk($unique, 200))->reduce(
                function (array $entities, array $ids) use ($repository): array {
                    $retrieved = $repository
                        ->where('id', 'in', $ids)
                        ->get()
                        ->all();

                    return array_merge($entities, $retrieved);
                },
                []
            )
            : [];

        $missing_count = $expected_id_count - count($entities);
        $value = $missing_count > 0
            ? $missing_entity_error($missing_count)
            : collection::new($entities);

        return result::create($value);
    }

    /**
     * Default constructor.
     *
     * @param competency $source competency whose pathways are to be copied.
     * @param collection<competency> $targets target competencies.
     * @param collection<competency_framework> $frameworks target competency
     *        frameworks.
     */
    private function __construct(
        competency $source,
        collection $targets,
        collection $frameworks
    ) {
        $this->source = $source;
        $this->targets = $targets;
        $this->frameworks = $frameworks;
    }

    /**
     * Magic attribute getter
     *
     * @param string $field field whose value is to returned.
     *
     * @return mixed the field value.
     *
     * @throws coding_exception if the field name is unknown.
     */
    public function __get(string $field) {
        $fields = ['source', 'targets', 'frameworks'];
        if (in_array($field, $fields)) {
            return $this->$field;
        }

        throw new coding_exception(
            'Unknown ' . self::class . " attribute: $field"
        );
    }

    /**
     * Copies the pathways from the source to the specified targets. Note: this
     * runs self::validate() before doing the copy.
     *
     * @param int $copy_op_id a unique identifier for this copy operation. Used
     *        to identify log entries pertaining to a specific copy operation.
     *
     * @return result<collection|error> the result embedding:
     *         - if the copying passed: the updated targets
     *         - if the copying failed: the error object
     */
    public function copy(int $copy_op_id): result {
        $result = $this->validated();

        return $result->is_successful()
            ? result::try(
                function () use ($copy_op_id): collection {
                    $copied = $this->run_in_transaction();

                    pathways_copied_bulk::create_for_operation(
                        $copy_op_id, $this->source, $copied
                    )->trigger();

                    return $copied;
                }
            )
            : $result;
    }

    /**
     * Runs the copy operation within a transaction.
     *
     * Depending on the database isolation mode, other code running concurrently
     * with this method may or may not see new pathways in target competencies.
     * Fortunately, the Totara transaction infrastructure uses 'read committed'
     * isolation for a transaction; therefore other code should only see the old
     * pathways until the copy is done.
     *
     * @return collection<collection> the updated targets.
     */
    private function run_in_transaction(): collection {
        $fn = Closure::fromCallable([$this, 'run']);
        return builder::get_db()->transaction($fn);
    }

    /**
     * Copies pathways from the source competency to targets.
     *
     * @return collection<collection> the updated targets.
     */
    private function run(): collection {
        $source = new achievement_configuration($this->source);
        $aggregation = $source->get_aggregation_type();

        $pathways = $this->pathways_to_copy();

        return $this->targets->map(
            function (
                competency $target
            ) use ($aggregation, $pathways): competency {
                $reset = $this->reset_target($target, $aggregation);
                return $this->copy_to_target($reset, $pathways);
            }
        );
    }

    /**
     * Formulates a set of source competency pathways to be copied to targets.
     *
     * @return collection<pathway> the pathways to be copied.
     */
    private function pathways_to_copy(): collection {
        $pathways = $this->source->active_pathways->map(
            [pathway::class, 'from_entity']
        );

        // A single use pathway type should appear at most once in an _active_
        // pathway list. However, the only place where this rule is enforced is
        // in the UI and that is by allowing/disallowing pathway type selection
        // options! In the backend, the pathway itself and its parent competency
        // do not enforce this. Which is why it has to be done here to prevent
        // problems from occurring far away from the copy process.
        return pathway::remove_extra_single_use_pathways($pathways)->sort(
            function (pathway $left, pathway $right): int {
                return $left->get_sortorder() <=> $right->get_sortorder();
            }
        );
    }

    /**
     * 'Resets' a target competency by archiving its active pathways and setting
     * its aggregation method to the source competency aggregation value.
     *
     * @param competency $target competency to reset.
     * @param string $aggregation new aggregation method.
     *
     * @return competency the updated competency.
     */
    private function reset_target(
        competency $target,
        string $aggregation
    ): competency {
        $now = time();
        $config = new achievement_configuration($target);
        $config->set_aggregation_type($aggregation)
            ->save_aggregation($now);

        $pathways_to_archive = $target->active_pathways->map(
            function (pathway_entity $pathway): array {
                return [
                    'id' => $pathway->id, 'type' => $pathway->path_type
                ];
            }
        );

        if ($pathways_to_archive) {
            // The easiest way to remove pathways from targets in bulk is to run
            // a "DELETE FROM ..." directly on pathway and other related tables.
            // It is certainly more performant but is too risky.
            //
            // Since pathways are pluggable and extensible, this method will not
            // know which custom tables need to be updated as well. This method
            // also cannot think tables always use ON DELETE CASCADE constraints
            // to enforce referential integrity.
            //
            // And then there is always the problem of reaggregating competency
            // assignment scores for users when pathways change.
            //
            // Hence the use of achievement_configuration::delete_pathways(); it
            // should safely 'delete' the pathways and associated data in all
            // cases.
            $config->delete_pathways($pathways_to_archive->all(), $now);
        }

        return $target;
    }

    /**
     * Copy pathways to a single target.
     *
     * @param competency $target competency to copy to.
     * @param collection $pathways pathways to copy to target.
     *
     * @return competency the updated competency.
     */
    private function copy_to_target(
        competency $target,
        collection $pathways
    ): competency {
        $competency = $pathways->reduce(
            function (competency $competency, pathway $pathway): competency {
                return $pathway->copy_to_competency($competency);
            },
            $target
        );

        $competency->pathways(); // Forces a reload of changed pathways.
        return $competency;
    }

    /**
     * Does a sanity check on the values to be used during the copy pathways
     * operation.
     *
     * @return result<self|error> the result embedding:
     *         - if all data for the copy operation is valid: this object
     *         - if the copying failed: the error object
     */
    public function validated(): result {
        $source = $this->source;
        $error = copy_pathway_validator::source_exists($source);
        $error = $error ?? copy_pathway_validator::source_has_active_pathways(
            $source
        );

        $targets = $this->targets;
        $error = $error ?? validator::competencies_not_empty($targets);
        $error = $error ?? validator::competencies_exist($targets);

        $frameworks = $this->frameworks;
        if ($frameworks->count() > 0) {
            $error = $error ?? validator::frameworks_exist($frameworks);
            $error = $error ?? validator::competencies_are_in_frameworks(
                $frameworks, $targets
            );
        }

        $value = $error ? $error : $this;
        return result::create($value);
    }
}
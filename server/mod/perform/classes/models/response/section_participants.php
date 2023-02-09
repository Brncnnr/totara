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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Murali Nair <murali.nair@totaralearning.com>
 * @package mod_perform
 */

namespace mod_perform\models\response;

use Closure;
use core\collection;
use mod_perform\models\activity\participant_instance;
use mod_perform\models\activity\participant_source;
use mod_perform\models\activity\section;
use mod_perform\models\activity\subject_instance;
use stdClass;

/**
 * Holds participants for a specific section in an activity.
 */
class section_participants {

    /**
     * @var section parent section.
     */
    private $section;

    /**
     * @var collection|participant_section[] participant sections.
     */
    private $participant_sections;

    /**
     *  Default constructor.
     *
     * @param section $section parent section.
     * @param collection|participant_section[] $participant_sections set of participant instances.
     */
    public function __construct(section $section, collection $participant_sections) {
        $this->section = $section;

        // Sort by relationship and then by name
        $participant_sections->sort(function (participant_section $sect1, participant_section $sect2) {
            $a = $sect1->participant_instance;
            $b = $sect2->participant_instance;
            if ($a->core_relationship->name === $b->core_relationship->name) {
                return $a->participant->fullname <=> $b->participant->fullname;
            }
            return $a->core_relationship->sort_order <=> $b->core_relationship->sort_order;
        });

        $this->participant_sections = $participant_sections;
    }

    /**
     * Returns the parent section.
     *
     * @return section the parent section.
     */
    public function get_section(): section {
        return $this->section;
    }

    /**
     * Returns the associated participant sections.
     *
     * @return collection|participant_section[] participant sections.
     */
    public function get_participant_sections(): collection {
        return $this->participant_sections;
    }

    /**
     * Returns true if the current (or given) user can participate in
     *
     * @param int|null $user_id if omitted will check for the current user
     * @return bool
     */
    public function can_participate(int $user_id = null): bool {
        if (!$user_id) {
            global $USER;
            $user_id = (int) $USER->id;
        }

        return $this->participant_sections->find(function (participant_section $item) use ($user_id) {
            return (int) $item->participant_instance->participant_source === participant_source::INTERNAL
                && (int) $item->participant_instance->participant_id === $user_id;
        }) !== null;
    }

    /**
     * Formulates sections and their participants for a target activity subject
     * instance.
     *
     * @param subject_instance $subject_instance target subject instance.
     *
     * @return collection|section_participants[] section participant objects.
     */
    public static function create_from_subject_instance(subject_instance $subject_instance): collection {
        return $subject_instance
            ->participant_instances
            ->reduce(
                Closure::fromCallable([self::class, 'create_from_participant_instance']),
                collection::new([])
            )
            ->sort(function ($a, $b) {
                return $a->section->sort_order <=> $b->section->sort_order;
            })
            ->map(
                function (stdClass $raw): section_participants {
                    return new section_participants($raw->section, $raw->participant_sections);
                }
            );
    }

    /**
     * Formulates sections and their participants for a target activity participant
     * instance.
     *
     * @param collection|stdClass[] $by_sections mapping of section ids to section
     *        and participant details.
     * @param participant_instance $participant_instance
     * @return collection|stdClass[] the updated section mappings.
     */
    private static function create_from_participant_instance(
        collection $by_sections,
        participant_instance $participant_instance
    ): collection {
        if ($participant_instance->should_be_hidden()) {
            return $by_sections;
        }

        foreach ($participant_instance->participant_sections as $participant_section) {
            $section = $participant_section->section;

            $raw = $by_sections->item($section->id) ?? (object) [
                'section' => $section,
                'participant_sections' => collection::new([])
            ];
            $raw->participant_sections->append($participant_section);

            $by_sections->set($raw, $section->id);
        }

        return $by_sections;
    }

}

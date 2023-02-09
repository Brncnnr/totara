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
 * @category test
 */

use core\format;
use core_phpunit\testcase;
use mod_perform\constants;
use mod_perform\dates\date_offset;
use mod_perform\entity\activity\track as track_entity;
use mod_perform\models\activity\track as track_model;
use mod_perform\models\activity\trigger\repeating\after_closure;
use mod_perform\testing\generator;

use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_perform\webapi\resolver\type\track
 *
 * @group perform
 */
class mod_perform_webapi_type_track_testcase extends testcase {

    use webapi_phpunit_helper;

    private const TYPE = 'mod_perform_track';

    /**
     * @covers ::resolve
     */
    public function test_invalid_input(): void {
        $this->create_track();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches("/track/");

        $this->resolve_graphql_type(self::TYPE, 'id', new \stdClass());
    }

    /**
     * @covers ::resolve
     */
    public function test_invalid_field(): void {
        [$track, $context] = $this->create_track();
        $field = 'unknown';

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessageMatches("/$field/");

        $this->resolve_graphql_type(self::TYPE, $field, $track, [], $context);
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve(): void {
        // Note: cannot use dataproviders here because PHPUnit runs these before
        // everything else. Incredibly, if a dataprovider in a random testsuite
        // creates database records or sends messages, etc, those will also be
        // visible to _all_ tests. In other words, with dataproviders, current
        // and yet unborn tests do not start in a clean state!
        [$source, $context] = $this->create_track(
            '<h1>This is a <strong>test</strong> description</h1>'
        );
        $plain_desc = format_string($source->description, true, ['context' => $context]);

        $testcases = [
            'id' => ['id', null, $source->id],
            'default desc' => ['description', null, $plain_desc],
            'plain desc' => ['description', format::FORMAT_PLAIN, $plain_desc],
            'status' => ['status', null, $source->status],
            'repeating is enabled' => ['repeating_is_enabled', null, $source->repeating_is_enabled],
            'repeating trigger type' => ['repeating_trigger_type', null, $source->repeating_trigger->get_name()],
            'repeating trigger interval' => ['repeating_trigger_interval', null, $source->repeating_trigger->get_interval()],
            'repeating offset' => ['repeating_offset', null, $source->repeating_offset],
            'repeating is limited' => ['repeating_is_limited', null, $source->repeating_is_limited],
            'repeating limit' => ['repeating_limit', null, $source->repeating_limit],
            'repeating type' => ['repeating_type', null, constants::SCHEDULE_REPEATING_UNSET]
        ];

        foreach ($testcases as $id => $testcase) {
            [$field, $format, $expected] = $testcase;
            $args = $format ? ['format' => $format] : [];

            $value = $this->resolve_graphql_type(self::TYPE, $field, $source, $args, $context);
            $this->assertEquals($expected, $value, "[$id] wrong value");
        }
    }

    /**
     * Generates a test track.
     *
     * @param string $description track description.
     *
     * @return array (generated track, context) tuple.
     */
    private function create_track(string $description=''): array {
        $this->setAdminUser();
        $activity = generator::instance()->create_activity_in_container();
        $context = $activity->get_context();

        $track = track_model::create($activity, $description)
            ->set_repeating_enabled(
                track_entity::SCHEDULE_REPEATING_TYPE_UNSET,
                new date_offset(1, date_offset::UNIT_WEEK),
                2,
                new after_closure()
            )
            ->update();

        return [$track, $context];
    }

}

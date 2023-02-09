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

use mod_perform\models\activity\track as track_model;
use totara_core\advanced_feature;
use totara_core\dates\date_time_setting;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_perform\webapi\resolver\query\tracks
 *
 * @group perform
 */
class mod_perform_webapi_query_tracks_testcase extends advanced_testcase {
    private const QUERY = 'mod_perform_tracks';

    use webapi_phpunit_helper;

    /**
     * @covers ::resolve
     */
    public function test_find(): void {
        [$activity_id, $tracks_by_id, $args, ] = $this->setup_env();
        $actual_tracks = $this->resolve_graphql_query('mod_perform_tracks', $args);
        $this->assertCount(count($tracks_by_id), $actual_tracks, 'wrong retrieve count');

        foreach ($actual_tracks as $track) {
            $expected = $tracks_by_id[$track->id] ?? null;

            $this->assertNotNull($expected, 'unknown track');
            $this->assertEquals($activity_id, $expected->activity_id, 'wrong activity');
            $this->assertEquals($expected->status, $track->status, 'wrong status');
            $this->assertEmpty($track->assignments->all(), 'wrong assignments');
        }
    }

    /**
     * @covers ::resolve
     */
    public function test_successful_ajax_call(): void {
        [, $tracks_by_id, $args, $context] = $this->setup_env();

        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_successful($result);

        $actual_tracks = $this->get_webapi_operation_data($result);
        $this->assertCount(count($tracks_by_id), $actual_tracks, 'wrong retrieve count');

        foreach ($actual_tracks as $actual) {
            $track_id = $actual['id'] ?? null;
            $this->assertNotNull($track_id, 'no retrieved track id');

            $track = $tracks_by_id[$track_id] ?? null;
            $this->assertNotNull($track, 'unknown track');

            $expected = $this->graphql_return($track, $context);
            $this->assertEquals($expected, $actual, 'wrong graphql return');
        }
    }

    /**
     * @covers ::resolve
     */
    public function test_failed_ajax_query(): void {
        [, , $args, ] = $this->setup_env(1);

        $feature = 'performance_activities';
        advanced_feature::disable($feature);
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed(
            $result,
            'Feature performance_activities is not available.'
        );
        advanced_feature::enable($feature);

        $result = $this->parsed_graphql_operation(self::QUERY, []);
        $this->assert_webapi_operation_failed(
            $result,
            'Variable "$activity_id" of required type "param_integer!" was not provided.'
        );

        $result = $this->parsed_graphql_operation(self::QUERY, ['activity_id' => 0]);
        $this->assert_webapi_operation_failed(
            $result,
            'Invalid parameter value detected (invalid activity id)'
        );

        $id = 1293;
        $result = $this->parsed_graphql_operation(self::QUERY, ['activity_id' => $id]);
        $this->assert_webapi_operation_failed(
            $result,
            "Invalid activity"
        );

        self::setGuestUser();
        $result = $this->parsed_graphql_operation(self::QUERY, $args);
        $this->assert_webapi_operation_failed(
            $result,
            'Invalid activity'
        );
    }

    /**
     * Generates test data.
     *
     * @param int $no_of_tracks no of tracks to generate.
     *
     * @return array (activity id, the generated tracks by ids, graphql query
     *         arguments, context) tuple.
     */
    private function setup_env(int $no_of_tracks=10): array {
        $this->setAdminUser();
        $generator = \mod_perform\testing\generator::instance();
        $activity = $generator->create_activity_in_container();

        $tracks_by_id = $generator
            ->create_activity_tracks($activity, $no_of_tracks)
            ->reduce(
                function (array $map, track_model $track): array {
                    $map[$track->id] = $track;
                    return $map;
                },
                []
            );

        $activity_id = $activity->get_id();
        $args = ['activity_id' => $activity_id];

        return [$activity_id, $tracks_by_id, $args, $activity->get_context()];
    }

    /**
     * Given the input track, returns data the graphql call is supposed to
     * return.
     *
     * @param track_model $track source track.
     * @param context $context
     *
     * @return array the expected graphql data values.
     */
    private function graphql_return(track_model $track, context $context): array {
        $resolve = function (string $field) use ($track, $context) {
            return $this->resolve_graphql_type('mod_perform_track', $field, $track, [], $context);
        };

        /** @var date_time_setting $from */
        $from = $resolve('schedule_fixed_from');

        return [
            'id' => $resolve('id'),
            'description' => $resolve('description'),
            'status' => $resolve('status'),
        ];
    }
}

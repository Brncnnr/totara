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

use core\collection;
use core\format;
use core_phpunit\testcase;
use core_user\profile\card_display;
use core_user\profile\card_display_field;
use core_user\profile\user_field_resolver;
use mod_perform\user_groups\grouping;
use totara_job\job_assignment;
use totara_webapi\phpunit\webapi_phpunit_helper;

/**
 * @coversDefaultClass \mod_perform\webapi\resolver\type\user_grouping
 *
 * @group perform
 */
class mod_perform_webapi_type_user_grouping_testcase extends testcase {

    use webapi_phpunit_helper;

    private const TYPE = 'mod_perform_user_grouping';

    /**
     * @covers ::resolve
     */
    public function test_invalid_input(): void {
        $this->create_grouping();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches("/user_grouping/");

        $this->resolve_graphql_type(self::TYPE, 'id', new stdClass());
    }

    /**
     * @covers ::resolve
     */
    public function test_invalid_field(): void {
        [$grouping, $context] = $this->create_grouping();
        $field = 'unknown';

        $this->expectException(moodle_exception::class);
        $this->expectExceptionMessageMatches("/$field/");

        $this->resolve_graphql_type(self::TYPE, $field, $grouping, [], $context);
    }

    /**
     * Test data provider for test_resolve_non_user_grouping.
     */
    public function resolve_non_user_grouping(): array {
        return [
            'org' => [grouping::ORG],
            'cohort' => [grouping::COHORT],
            'position' => [grouping::POS]
        ];
    }

    /**
     * @covers ::resolve
     * @dataProvider resolve_non_user_grouping
     */
    public function test_resolve_non_user_grouping(int $group_type): void {
        [$source, $context] = $this->create_grouping($group_type);
        $plain_name = format_string($source->get_name(), true, ['context' => $context]);

        $testcases = [
            'id' => ['id', null, $source->get_id()],
            'type' => ['type', null, $source->get_type()],
            'type_label' => ['type_label', null, $source->get_type_label()],
            'default name' => ['name', null, $plain_name],
            'html name' => ['name', format::FORMAT_PLAIN, $plain_name],
            'size' => ['size', null, $source->get_size()],
            'extra' => ['extra', null, null]
        ];

        foreach ($testcases as $id => $testcase) {
            [$field, $format, $expected] = $testcase;
            $args = $format ? ['format' => $format] : [];

            $value = $this->resolve_graphql_type(self::TYPE, $field, $source, $args, $context);
            $this->assertEquals($expected, $value, "[$id] wrong value");
        }
    }

    /**
     * @covers ::resolve
     */
    public function test_resolve_user(): void {
        [$source, $context, $user] = $this->create_grouping(grouping::USER);
        $plain_name = format_string($source->get_name(), true, ['context' => $context]);

        $testcases = [
            'id' => ['id', null, $source->get_id()],
            'type' => ['type', null, $source->get_type()],
            'type_label' => ['type_label', null, $source->get_type_label()],
            'default name' => ['name', null, $plain_name],
            'html name' => ['name', format::FORMAT_PLAIN, $plain_name],
            'size' => ['size', null, $source->get_size()]
        ];

        foreach ($testcases as $id => $testcase) {
            [$field, $format, $expected] = $testcase;
            $args = $format ? ['format' => $format] : [];

            $value = $this->resolve_graphql_type(self::TYPE, $field, $source, $args, $context);
            $this->assertEquals($expected, $value, "[$id] wrong value");
        }

        $user_field_resolver = user_field_resolver::from_record($user);
        $card_display_fields = card_display::create($user_field_resolver)
            ->get_card_display_fields();

        $display_fields = collection::new($card_display_fields)
            ->map_to(
                function (card_display_field $field): array {
                    return [
                        'value' => $field->get_field_value(),
                        'label' => $field->get_field_label(),
                        'associate_url' => $field->get_field_url(),
                        'is_custom' => $field->is_custom_field()
                    ];
                }
            )
            ->filter(
                function (array $field): bool {
                    return !empty($field['label']);
                }
            );

        $expected = [
            'display_fields' => $display_fields->all(),
            'profile_picture_url' => $user_field_resolver->get_field_value('profileimageurl'),
            'profile_picture_alt' => $user_field_resolver->get_field_value('profileimagealt'),
            'profile_url' => $user_field_resolver->get_field_value('profileurl')
        ];

        $resolved = $this->resolve_graphql_type(self::TYPE, 'extra', $source, [], $context);
        $extra = json_decode($resolved, true);
        $this->assertEquals($expected, $extra, 'wrong extra value');
    }

    /**
     * Generates a test grouping.
     *
     * @param string|null $type grouping type.
     *
     * @return array (test grouping, context, target object eg cohort) tuple.
     */
    private function create_grouping(?string $type=null): array {
        global $USER;
        $this->setAdminUser();
        $context = context_user::instance($USER->id);

        $generator = $this->getDataGenerator();
        $hierarchies = $generator->get_plugin_generator('totara_hierarchy');

        $group_users = [];
        for ($i = 0; $i < 3; $i++) {
            $group_users[] = $generator->create_user()->id;
        }

        $grouping = null;
        $target = null;
        switch ($type) {
            case grouping::COHORT:
                $target = $generator->create_cohort(['name' => 'My testing cohort']);
                foreach ($group_users as $user) {
                    cohort_add_member($target->id, $user);
                }

                $grouping = grouping::cohort($target->id);
                break;

            case grouping::ORG:
                $target = $hierarchies->create_org([
                    'frameworkid' => $hierarchies->create_org_frame([])->id,
                    'shortname' => 'My short org name',
                    'fullname' => 'My really long org name'
                ]);

                foreach ($group_users as $user) {
                    job_assignment::create([
                        'userid' => $user,
                        'idnumber' => "$user",
                        'organisationid' => $target->id
                    ]);
                }

                $grouping = grouping::org($target->id);
                break;

            case grouping::POS:
                $target = $hierarchies->create_pos([
                    'frameworkid' => $hierarchies->create_pos_frame([])->id,
                    'shortname' => 'My short pos name',
                    'fullname' => 'My really long pos name'
                ]);

                foreach ($group_users as $user) {
                    job_assignment::create([
                        'userid' => $user,
                        'idnumber' => "$user",
                        'positionid' => $target->id
                    ]);
                }

                $grouping = grouping::pos($target->id);
                break;

            default:
                $target = $generator->create_user([
                    'firstname' => 'Tester',
                    'middlename' => 'Number',
                    'lastname' => 'Two'
                ]);

                $grouping = grouping::user($target->id);
        }

        return [$grouping, $context, $target];
    }

}

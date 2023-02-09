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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Marco Song <marco.song@totaralearning.com>
 * @package mod_perform
 */

use mod_perform\models\activity\section_element;
use totara_webapi\phpunit\webapi_phpunit_helper;

require_once(__DIR__ . '/section_element_reference_test.php');

/**
 * @group perform
 * @group perform_element
 */
class mod_perform_reference_element_section_element_deletion_testcase extends section_element_reference_testcase {
    public const QUERY = 'mod_perform_element_deletion_validation';

    use webapi_phpunit_helper;

    public function test_delete_watcher(): void {
        $this->create_test_data();

        $this->expectException(coding_exception::class);
        $this->expectExceptionMessageMatches("/This question cannot be deleted/");

        // delete section element
        section_element::load_by_id($this->source_section_element->id)->delete();
    }

    public function test_query_validation_with_problems(): void {
        $this->create_test_data();

        $args = ['input' => ['section_element_id' => $this->source_section_element->id]];
        $result = $this->resolve_graphql_query(self::QUERY, $args);

        self::assertEquals('Cannot delete question element', $result['title']);
        self::assertFalse($result['can_delete']);

        $description = $result['reason']['description'];
        $result_data = $result['reason']['data'];

        self::assertEquals('This question cannot be deleted, because it is being referenced by other elements:', $description);

        // Check data with correct order.
        self::assertCount(2, $result_data);

        self::assertEquals('referencing_redisplay_activity : referencing_redisplay_section (Response redisplay)', $result_data[0]);
        self::assertEquals('source_activity : referencing_aggregation_section (Response aggregation)', $result_data[1]);
    }
}
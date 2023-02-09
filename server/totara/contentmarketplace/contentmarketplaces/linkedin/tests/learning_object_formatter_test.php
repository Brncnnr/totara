<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2021 onwards Totara Learning Solutions LTD
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
 * @author Mark Metcalfe <mark.metcalfe@totaralearning.com>
 * @package contentmarketplace_linkedin
 */

use contentmarketplace_linkedin\entity\learning_object as learning_object_entity;
use contentmarketplace_linkedin\formatter\learning_object as learning_object_formatter;
use contentmarketplace_linkedin\formatter\timespan_field_formatter;
use contentmarketplace_linkedin\model\learning_object as learning_object_model;
use core\date_format;
use core\format;
use core_phpunit\testcase;

/**
 * @covers contentmarketplace_linkedin\formatter\learning_object
 * @group totara_contentmarketplace
 */
class contentmarketplace_linkedin_learning_object_formatter_testcase extends testcase {

    /**
     * @return void
     */
    public function test_formatter(): void {
        $entity = new learning_object_entity();
        $entity->urn = 'urn:12345';
        $entity->title = 'This is a <p>title</p>';
        $entity->description = 'This is a <p>description</p>';
        $entity->description_include_html = 'This is a <script>console.log("bad")</script><p>HTML description</p>';
        $entity->short_description = 'This is a <p>short description</p>';
        $entity->locale_language = 'en';
        $entity->locale_country = 'US';
        $entity->last_updated_at = time();
        $entity->published_at = time();
        $entity->retired_at = time();
        $entity->level = "BEGINNER";
        $entity->asset_type = "COURSE";
        $entity->primary_image_url = 'https://example.com/image.jpg?cached=1&time=123';
        $entity->time_to_complete = 120;
        $entity->web_launch_url = 'https://example.com/?cached=1&time=123';
        $entity->sso_launch_url = 'https://example.com/sso.php?cached=1&time=123';
        $entity->save();
        $model = learning_object_model::load_by_entity($entity);

        $formatter = new learning_object_formatter($model, context_system::instance());

        // Unformatted fields
        $this->assertEquals($entity->asset_type, $formatter->format('asset_type'));
        $this->assertEquals($entity->level, $formatter->format('level'));
        $this->assertEquals('Beginner', $formatter->format('display_level'));
        $this->assertEquals($entity->primary_image_url, $formatter->format('image_url'));

        // String fields
        $this->assertNotEquals($entity->title, $formatter->format('name', format::FORMAT_PLAIN));
        $this->assertEquals('This is a title', $formatter->format('name', format::FORMAT_PLAIN));
        $this->assertNotEquals($entity->description, $formatter->format('description', format::FORMAT_PLAIN));
        $this->assertEquals('This is a description', $formatter->format('description', format::FORMAT_PLAIN));
        $this->assertNotEquals($entity->description_include_html, $formatter->format('description_include_html', format::FORMAT_PLAIN));
        $this->assertEquals('This is a console.log("bad")HTML description', $formatter->format('description_include_html', format::FORMAT_PLAIN));
        $this->assertStringNotContainsString('<script>', $formatter->format('description_include_html', format::FORMAT_HTML));
        $this->assertNotEquals($entity->short_description, $formatter->format('short_description', format::FORMAT_PLAIN));
        $this->assertEquals('This is a short description', $formatter->format('short_description', format::FORMAT_PLAIN));

        // Date fields
        $this->assertIsNotInt($formatter->format('last_updated_at', date_format::FORMAT_DATE));
        $this->assertIsNotInt($formatter->format('published_at', date_format::FORMAT_DATE));

        // Timespan fields
        $this->assertEquals("2m", $formatter->format('time_to_complete', timespan_field_formatter::FORMAT_HUMAN));
        $this->assertEquals(120, $formatter->format('time_to_complete', timespan_field_formatter::FORMAT_SECONDS));
    }

}

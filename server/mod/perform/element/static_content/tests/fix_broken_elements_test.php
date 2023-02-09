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
 * @author Fabian Derschatta <fabian.derschatta@totaralearning.com>
 * @package performelement_static_element
 */

defined('MOODLE_INTERNAL') || die();

use mod_perform\entity\activity\element;

class performelement_static_content_fix_broken_elements_testcase extends advanced_testcase {

    public function test_fix_elements(): void {
        global $CFG;
        require_once $CFG->dirroot . '/mod/perform/element/static_content/db/upgradelib.php';

        $working_json = '{"docFormat":"FORMAT_JSON_EDITOR","draftId":762632001,"format":"HTML","wekaDoc":"{\"type\":\"doc\",\"content\":[{\"type\":\"attachments\",\"content\":[{\"type\":\"attachment\",\"attrs\":{\"filename\":\"image with spaces.png\",\"size\":56116,\"option\":{\"alttext\":\"\"},\"url\":\"@@PLUGINFILE@@\/image%20with%20spaces.png?forcedownload=1\"}}]},{\"type\":\"attachments\",\"content\":[{\"type\":\"attachment\",\"attrs\":{\"filename\":\"image2.png\",\"size\":433680,\"option\":{\"alttext\":\"\"},\"url\":\"@@PLUGINFILE@@\/image2.png?forcedownload=1\"}}]},{\"type\":\"bullet_list\",\"content\":[{\"type\":\"list_item\",\"content\":[{\"type\":\"paragraph\",\"content\":[{\"type\":\"text\",\"text\":\"Bullet point 1\"}]}]},{\"type\":\"list_item\",\"content\":[{\"type\":\"paragraph\",\"content\":[{\"type\":\"text\",\"text\":\"Bullet point 2\"}]}]},{\"type\":\"list_item\",\"content\":[{\"type\":\"paragraph\"},{\"type\":\"attachments\",\"content\":[{\"type\":\"attachment\",\"attrs\":{\"filename\":\"image2.png\",\"size\":274719,\"option\":{\"alttext\":\"\"},\"url\":\"@@PLUGINFILE@@\/image2.png?forcedownload=1\"}}]}]},{\"type\":\"list_item\",\"content\":[{\"type\":\"paragraph\",\"content\":[{\"type\":\"text\",\"text\":\"Bullet point 3\"}]}]},{\"type\":\"list_item\",\"content\":[{\"type\":\"paragraph\"},{\"type\":\"image\",\"attrs\":{\"filename\":\"image4.png\",\"url\":\"@@PLUGINFILE@@\/image4.png\",\"alttext\":\"\"}}]},{\"type\":\"list_item\",\"content\":[{\"type\":\"paragraph\",\"content\":[{\"type\":\"text\",\"text\":\"Bullet point 4\"}]}]}]}]}","element_id":31}';
        $broken_json1 = '{"docFormat":"FORMAT_JSON_EDITOR","draftId":762632002,"format":"HTML","wekaDoc":"{\"type\":\"doc\",\"content\":[{\"type\":\"attachments\",\"content\":[{\"type\":\"attachment\",\"attrs\":{\"filename\":\"image with spaces.png\",\"size\":56116,\"option\":{\"alttext\":\"\"},\"url\":null}}]},{\"type\":\"attachments\",\"content\":[{\"type\":\"attachment\",\"attrs\":{\"filename\":\"image2.png\",\"size\":433680,\"option\":{\"alttext\":\"\"},\"url\":null}}]},{\"type\":\"bullet_list\",\"content\":[{\"type\":\"list_item\",\"content\":[{\"type\":\"paragraph\",\"content\":[{\"type\":\"text\",\"text\":\"Bullet point 1\"}]}]},{\"type\":\"list_item\",\"content\":[{\"type\":\"paragraph\",\"content\":[{\"type\":\"text\",\"text\":\"Bullet point 2\"}]}]},{\"type\":\"list_item\",\"content\":[{\"type\":\"paragraph\"},{\"type\":\"attachments\",\"content\":[{\"type\":\"attachment\",\"attrs\":{\"filename\":\"image2.png\",\"size\":274719,\"option\":{\"alttext\":\"\"},\"url\":null}}]}]},{\"type\":\"list_item\",\"content\":[{\"type\":\"paragraph\",\"content\":[{\"type\":\"text\",\"text\":\"Bullet point 3\"}]}]},{\"type\":\"list_item\",\"content\":[{\"type\":\"paragraph\"},{\"type\":\"image\",\"attrs\":{\"filename\":\"image4.png\",\"url\":\"@@PLUGINFILE@@\/image4.png\",\"alttext\":\"\"}}]},{\"type\":\"list_item\",\"content\":[{\"type\":\"paragraph\",\"content\":[{\"type\":\"text\",\"text\":\"Bullet point 4\"}]}]}]}]}","element_id":31}';
        $fixed_json1 = '{"docFormat":"FORMAT_JSON_EDITOR","draftId":762632002,"format":"HTML","wekaDoc":"{\"type\":\"doc\",\"content\":[{\"type\":\"attachments\",\"content\":[{\"type\":\"attachment\",\"attrs\":{\"filename\":\"image with spaces.png\",\"size\":56116,\"option\":{\"alttext\":\"\"},\"url\":\"@@PLUGINFILE@@\/image%20with%20spaces.png?forcedownload=1\"}}]},{\"type\":\"attachments\",\"content\":[{\"type\":\"attachment\",\"attrs\":{\"filename\":\"image2.png\",\"size\":433680,\"option\":{\"alttext\":\"\"},\"url\":\"@@PLUGINFILE@@\/image2.png?forcedownload=1\"}}]},{\"type\":\"bullet_list\",\"content\":[{\"type\":\"list_item\",\"content\":[{\"type\":\"paragraph\",\"content\":[{\"type\":\"text\",\"text\":\"Bullet point 1\"}]}]},{\"type\":\"list_item\",\"content\":[{\"type\":\"paragraph\",\"content\":[{\"type\":\"text\",\"text\":\"Bullet point 2\"}]}]},{\"type\":\"list_item\",\"content\":[{\"type\":\"paragraph\"},{\"type\":\"attachments\",\"content\":[{\"type\":\"attachment\",\"attrs\":{\"filename\":\"image2.png\",\"size\":274719,\"option\":{\"alttext\":\"\"},\"url\":\"@@PLUGINFILE@@\/image2.png?forcedownload=1\"}}]}]},{\"type\":\"list_item\",\"content\":[{\"type\":\"paragraph\",\"content\":[{\"type\":\"text\",\"text\":\"Bullet point 3\"}]}]},{\"type\":\"list_item\",\"content\":[{\"type\":\"paragraph\"},{\"type\":\"image\",\"attrs\":{\"filename\":\"image4.png\",\"url\":\"@@PLUGINFILE@@\/image4.png\",\"alttext\":\"\"}}]},{\"type\":\"list_item\",\"content\":[{\"type\":\"paragraph\",\"content\":[{\"type\":\"text\",\"text\":\"Bullet point 4\"}]}]}]}]}","element_id":31}';
        $broken_json2 = '{"docFormat":"FORMAT_JSON_EDITOR","draftId":762632003,"format":"HTML","wekaDoc":"{\"type\":\"doc\",\"content\":[{\"type\":\"attachments\",\"content\":[{\"type\":\"attachment\",\"attrs\":{\"filename\":\"image with spaces.png\",\"size\":56116,\"option\":{\"alttext\":\"\"},\"url\":null}}]},{\"type\":\"attachments\",\"content\":[{\"type\":\"attachment\",\"attrs\":{\"filename\":\"image2.png\",\"size\":433680,\"option\":{\"alttext\":\"\"},\"url\":null}}]},{\"type\":\"bullet_list\",\"content\":[{\"type\":\"list_item\",\"content\":[{\"type\":\"paragraph\",\"content\":[{\"type\":\"text\",\"text\":\"Bullet point 1\"}]}]},{\"type\":\"list_item\",\"content\":[{\"type\":\"paragraph\",\"content\":[{\"type\":\"text\",\"text\":\"Bullet point 2\"}]}]},{\"type\":\"list_item\",\"content\":[{\"type\":\"paragraph\"},{\"type\":\"attachments\",\"content\":[{\"type\":\"attachment\",\"attrs\":{\"filename\":\"image2.png\",\"size\":274719,\"option\":{\"alttext\":\"\"},\"url\":null}}]}]},{\"type\":\"list_item\",\"content\":[{\"type\":\"paragraph\",\"content\":[{\"type\":\"text\",\"text\":\"Bullet point 3\"}]}]},{\"type\":\"list_item\",\"content\":[{\"type\":\"paragraph\"},{\"type\":\"image\",\"attrs\":{\"filename\":\"image4.png\",\"url\":\"@@PLUGINFILE@@\/image4.png\",\"alttext\":\"\"}}]},{\"type\":\"list_item\",\"content\":[{\"type\":\"paragraph\",\"content\":[{\"type\":\"text\",\"text\":\"Bullet point 4\"}]}]}]}]}","element_id":31}';
        $fixed_json2 = '{"docFormat":"FORMAT_JSON_EDITOR","draftId":762632003,"format":"HTML","wekaDoc":"{\"type\":\"doc\",\"content\":[{\"type\":\"attachments\",\"content\":[{\"type\":\"attachment\",\"attrs\":{\"filename\":\"image with spaces.png\",\"size\":56116,\"option\":{\"alttext\":\"\"},\"url\":\"@@PLUGINFILE@@\/image%20with%20spaces.png?forcedownload=1\"}}]},{\"type\":\"attachments\",\"content\":[{\"type\":\"attachment\",\"attrs\":{\"filename\":\"image2.png\",\"size\":433680,\"option\":{\"alttext\":\"\"},\"url\":\"@@PLUGINFILE@@\/image2.png?forcedownload=1\"}}]},{\"type\":\"bullet_list\",\"content\":[{\"type\":\"list_item\",\"content\":[{\"type\":\"paragraph\",\"content\":[{\"type\":\"text\",\"text\":\"Bullet point 1\"}]}]},{\"type\":\"list_item\",\"content\":[{\"type\":\"paragraph\",\"content\":[{\"type\":\"text\",\"text\":\"Bullet point 2\"}]}]},{\"type\":\"list_item\",\"content\":[{\"type\":\"paragraph\"},{\"type\":\"attachments\",\"content\":[{\"type\":\"attachment\",\"attrs\":{\"filename\":\"image2.png\",\"size\":274719,\"option\":{\"alttext\":\"\"},\"url\":\"@@PLUGINFILE@@\/image2.png?forcedownload=1\"}}]}]},{\"type\":\"list_item\",\"content\":[{\"type\":\"paragraph\",\"content\":[{\"type\":\"text\",\"text\":\"Bullet point 3\"}]}]},{\"type\":\"list_item\",\"content\":[{\"type\":\"paragraph\"},{\"type\":\"image\",\"attrs\":{\"filename\":\"image4.png\",\"url\":\"@@PLUGINFILE@@\/image4.png\",\"alttext\":\"\"}}]},{\"type\":\"list_item\",\"content\":[{\"type\":\"paragraph\",\"content\":[{\"type\":\"text\",\"text\":\"Bullet point 4\"}]}]}]}]}","element_id":31}';

        $working_element = new element();
        $working_element->context_id = 1;
        $working_element->plugin_name = 'static_content';
        $working_element->title = 'Working element 1';
        $working_element->is_required = 0;
        $working_element->data = $working_json;
        $working_element->save();

        $broken_element1 = new element();
        $broken_element1->context_id = 1;
        $broken_element1->plugin_name = 'static_content';
        $broken_element1->title = 'Broken element 1';
        $broken_element1->is_required = 0;
        $broken_element1->data = $broken_json1;
        $broken_element1->save();

        $broken_element2 = new element();
        $broken_element2->context_id = 1;
        $broken_element2->plugin_name = 'static_content';
        $broken_element2->title = 'Broken element 2';
        $broken_element2->is_required = 0;
        $broken_element2->data = $broken_json2;
        $broken_element2->save();

        $other_element = new element();
        $other_element->context_id = 1;
        $other_element->plugin_name = 'long_text';
        $other_element->title = 'Other element';
        $other_element->is_required = 0;
        $other_element->data = "{}";
        $other_element->save();

        performelement_static_content_fix_broken_elements();

        $working_element = element::repository()->find($working_element->id);
        $this->assertEquals($working_element->data, $working_json);

        $broken_element1 = element::repository()->find($broken_element1->id);
        $this->assertEquals($broken_element1->data, $fixed_json1);

        $broken_element2 = element::repository()->find($broken_element2->id);
        $this->assertEquals($broken_element2->data, $fixed_json2);

        $other_element_reloaded = element::repository()->find($other_element->id);
        $this->assertEquals($other_element->data, $other_element_reloaded->data);
    }

    /**
     * @return void
     */
    public function test_fix_broken_structure_test(): void {
        global $CFG;
        require_once("{$CFG->dirroot}/mod/perform/element/static_content/db/upgradelib.php");

        $not_so_broken_content = json_encode([
            'docFormat' => "FORMAT_JSON_EDITOR",
            'draftId' => 42,
            'format' => "HTML",
            'wekaDoc' => null
        ]);

        $element_one = new element();
        $element_one->context_id = 1;
        $element_one->plugin_name = 'static_content';
        $element_one->title = 'Kaboom!';
        $element_one->is_required = 0;
        $element_one->data = $not_so_broken_content;
        $element_one->save();


        $element_two = new element();
        $element_two->context_id = 1;
        $element_two->plugin_name = 'static_content';
        $element_two->title = 'Kaboom!';
        $element_two->is_required = 0;
        $element_two->data = '{}';
        $element_two->save();

        performelement_static_content_fix_broken_elements();

        $element_one->refresh();
        $element_two->refresh();

        self::assertEquals($not_so_broken_content, $element_one->data);
        self::assertEquals('{}', $element_two->data);
    }
}
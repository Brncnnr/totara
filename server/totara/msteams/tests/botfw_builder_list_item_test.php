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
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package totara_msteams
 */

use totara_msteams\botfw\builder;

class totara_msteams_botfw_builder_list_item_testcase extends advanced_testcase {
    public function test_build() {
        $item = builder::list_item()
            ->type('section')
            ->title('Test item')
            ->subtitle('Lorem ipsum')
            ->icon('http://example.com/icon.png')
            ->tap(builder::action()
                ->url('Click me', 'http://example.com/test')
                ->build())
            ->build();
        $this->assertEquals('section', $item->type);
        $this->assertEquals('Test item', $item->title);
        $this->assertEquals('Lorem ipsum', $item->subtitle);
        $this->assertEquals('http://example.com/icon.png', $item->icon);
        $this->assertEquals('openUrl', $item->tap->type);
        $this->assertEquals('Click me', $item->tap->title);
        $this->assertEquals('Click me', $item->tap->text);
        $this->assertEquals('http://example.com/test', $item->tap->value);

        $item = builder::list_item()
            ->type('file')
            ->id('https://example.com/teams/new/Shared%20Documents/Report.xlsx')
            ->title('Report')
            ->subtitle('teams > new > design')
            ->tap(builder::action()
                ->im_back('editOnline https://example.com/teams/new/Shared%20Documents/Report.xlsx')
                ->build())
            ->build();
        $this->assertEquals('file', $item->type);
        $this->assertEquals('https://example.com/teams/new/Shared%20Documents/Report.xlsx', $item->id);
        $this->assertEquals('Report', $item->title);
        $this->assertEquals('teams > new > design', $item->subtitle);
        $this->assertEquals('imBack', $item->tap->type);
        $this->assertEquals('editOnline https://example.com/teams/new/Shared%20Documents/Report.xlsx', $item->tap->value);

        $item = builder::list_item()
            ->type('resultItem')
            ->title('Test result')
            ->subtitle('Lorem ipsum dolor sit amet')
            ->icon('http://example.com/lorem.png')
            ->tap(builder::action()
                ->url('Tap me', 'http://example.com/tap')
                ->build())
            ->build();
        $this->assertEquals('resultItem', $item->type);
        $this->assertEquals('Test result', $item->title);
        $this->assertEquals('Lorem ipsum dolor sit amet', $item->subtitle);
        $this->assertEquals('http://example.com/lorem.png', $item->icon);
        $this->assertEquals('openUrl', $item->tap->type);
        $this->assertEquals('Tap me', $item->tap->title);
        $this->assertEquals('Tap me', $item->tap->text);
        $this->assertEquals('http://example.com/tap', $item->tap->value);

        $item = builder::list_item()
            ->type('section')
            ->title('Manager')
            ->build();
        $this->assertEquals('section', $item->type);
        $this->assertEquals('Manager', $item->title);

        $item = builder::list_item()
            ->type('person')
            ->id('JohnDoe@example.com')
            ->title('John Doe')
            ->subtitle('Manager')
            ->tap(builder::action()
                ->im_back('whois JohnDoe@example.com')
                ->build())
            ->build();
        $this->assertEquals('person', $item->type);
        $this->assertEquals('JohnDoe@example.com', $item->id);
        $this->assertEquals('John Doe', $item->title);
        $this->assertEquals('Manager', $item->subtitle);
        $this->assertEquals('imBack', $item->tap->type);
        $this->assertEquals('whois JohnDoe@example.com', $item->tap->value);
    }
}

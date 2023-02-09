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

class totara_msteams_botfw_builder_action_testcase extends advanced_testcase {
    public function test_build_url_easy() {
        $action = builder::action()
            ->url('Test action', 'https://example.com/test')
            ->build();
        $this->assertEquals('openUrl', $action->type);
        $this->assertEquals('Test action', $action->title);
        $this->assertEquals('Test action', $action->text);
        $this->assertEquals('https://example.com/test', $action->value);
    }

    public function test_build_url_hard() {
        $action = builder::action()
            ->type('openUrl')
            ->title('Test action')
            ->value('https://example.com/test')
            ->build();
        $this->assertEquals('openUrl', $action->type);
        $this->assertEquals('Test action', $action->title);
        $this->assertEquals('https://example.com/test', $action->value);
    }

    public function test_build_message_back_easy_string() {
        $action = builder::action()
            ->message_back('Click me', 'kiaora')
            ->build();
        $this->assertEquals('messageBack', $action->type);
        $this->assertEquals('Click me', $action->title);
        $this->assertEquals('kiaora', $action->text);
        $this->assertEquals('kiaora', $action->displayText);
    }

    public function test_build_message_back_easy_object() {
        $action = builder::action()
            ->message_back('Click me', (object)['kia' => 'ora'])
            ->build();
        $this->assertEquals('messageBack', $action->type);
        $this->assertEquals('Click me', $action->title);
        $this->assertEquals('{"kia":"ora"}', $action->value);
    }

    public function test_build_message_back_hard() {
        $action = builder::action()
            ->type('messageBack')
            ->title('Click me')
            ->display_text('I clicked the button')
            ->text('User just clicked the button')
            ->value((object)['kia' => 'ora'])
            ->build();
        $this->assertEquals('messageBack', $action->type);
        $this->assertEquals('Click me', $action->title);
        $this->assertEquals('I clicked the button', $action->displayText);
        $this->assertEquals('User just clicked the button', $action->text);
        $this->assertEquals('{"kia":"ora"}', $action->value);
    }

    public function test_build_signin_easy() {
        $action = builder::action()
            ->signin('Click me', 'https://example.com/test')
            ->build();
        $this->assertEquals('signin', $action->type);
        $this->assertEquals('Click me', $action->title);
        $this->assertEquals('Click me', $action->text);
        $this->assertEquals('https://example.com/test', $action->value);
    }

    public function test_build_signin_hard() {
        $action = builder::action()
            ->type('signin')
            ->title('Click me')
            ->text('Click the button')
            ->value('https://example.com/test')
            ->build();
        $this->assertEquals('signin', $action->type);
        $this->assertEquals('Click me', $action->title);
        $this->assertEquals('Click the button', $action->text);
        $this->assertEquals('https://example.com/test', $action->value);
    }
}

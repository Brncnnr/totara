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

use totara_msteams\botfw\activity;
use totara_msteams\botfw\bot;
use totara_msteams\botfw\dispatchable;
use totara_msteams\botfw\router\dynamic_router;
use totara_msteams\botfw\router\route;

class totara_msteams_botfw_router_dynamic_testcase extends advanced_testcase {
    /** @var dynamic_router */
    private $router;

    public function setUp(): void {
        $this->router = new dynamic_router();
    }

    public function tearDown(): void {
        $this->router = null;
    }

    public function test_get_routes() {
        $this->assertCount(0, $this->router->get_routes());

        $this->router->add([], new test1_dispatcher());
        $this->assertCount(1, $this->router->get_routes());
    }

    public function test_find_best_match_basic() {
        set_config('bot_feature_enabled', 1, 'totara_msteams');
        set_config('messaging_extension_enabled', 1, 'totara_msteams');

        $activity = new activity();
        $activity->type = 'kiaora';
        $this->assertNull($this->router->find_best_match($activity));

        $this->router->add(['type' => 'kiaorakoutou'], new test1_dispatcher());
        $this->assertNull($this->router->find_best_match($activity));

        $activity->type = 'kiaorakoutou';
        $route = $this->router->find_best_match($activity);
        $this->assertNotNull($route);
        $this->assertInstanceOf(test1_dispatcher::class, $route->get_dispatcher());

        $selector = function (activity $activity) {
            return strpos($activity->text, 'kia ora') === 0;
        };
        $this->router->add($selector, new test2_dispatcher());
        $activity->type = 'message';
        $activity->text = 'hello';
        $this->assertNull($this->router->find_best_match($activity));
        $activity->text = 'kia ora koutou';
        $route = $this->router->find_best_match($activity);
        $this->assertNotNull($route);
        $this->assertInstanceOf(test2_dispatcher::class, $route->get_dispatcher());
    }

    public function data_find_best_match_features(): array {
        return [
            [false, false, false, false],
            [false, true, false, true],
            [true, false, true, false],
            [true, true, true, true],
        ];
    }

    /**
     * Test the find_best_match function with features enabled/disabled.
     *
     * @param boolean $bot_enabled
     * @param boolean $mex_enabled
     * @param boolean $bot_available
     * @param boolean $mex_available
     * @dataProvider data_find_best_match_features
     */
    public function test_find_best_match_features(bool $bot_enabled, bool $mex_enabled, bool $bot_available, bool $mex_available) {
        set_config('bot_feature_enabled', $bot_enabled, 'totara_msteams');
        set_config('messaging_extension_enabled', $mex_enabled, 'totara_msteams');

        $this->router->add(['type' => 'bot'], new test1_dispatcher());
        $this->router->add(['type' => 'mex'], new test2_dispatcher(), route::EXTENSION);

        $bot_activity = activity::from_object((object)['type' => 'bot']);
        $mex_activity = activity::from_object((object)['type' => 'mex']);
        $find_dispatcher = function (activity $activity): ?dispatchable {
            $route = $this->router->find_best_match($activity);
            return $route !== null ? $route->get_dispatcher() : null;
        };
        $disp1 = $find_dispatcher($bot_activity);
        $disp2 = $find_dispatcher($mex_activity);
        $this->assertSame($bot_available, $disp1 instanceof test1_dispatcher, 'Assertion failed: '. ($disp1 ? get_class($disp1) : 'null'));
        $this->assertSame($mex_available, $disp2 instanceof test2_dispatcher, 'Assertion failed: '. ($disp2 ? get_class($disp2) : 'null'));
    }
}

class test1_dispatcher implements dispatchable {
    public function dispatch(bot $bot, activity $activity): void {
        // do nothing
    }
}

class test2_dispatcher implements dispatchable {
    public function dispatch(bot $bot, activity $activity): void {
        // do nothing
    }
}

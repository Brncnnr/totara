<?php
/**
 * This file is part of Totara Talent Experience Platform
 *
 * Copyright (C) 2022 onwards Totara Learning Solutions LTD
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
 * @author Cody Finegan <cody.finegan@totaralearning.com>
 * @package block_totara_recommendations
 */

namespace block_totara_recommendations\testing;

use block_totara_recommendations\repository\recommendations_repository;
use ml_recommender\recommendations;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionProperty;

/**
 * Helper functions to testing recommendations, switch between the two data sources.
 */
trait recommendations_service_mock_trait {
    /**
     * @var bool
     */
    private $is_legacy_service;

    /**
     * @var array
     */
    private $recommendations;

    /**
     * @var string
     */
    private $recommendation_component;

    /**
     * @param $component
     * @return void
     */
    private function start_mock_service($component): void {
        $this->is_legacy_service = false;
        $this->recommendations = [];
        $this->recommendation_component = $component;
    }

    /**
     * @return void
     */
    private function clean_mock_service(): void {
        $this->is_legacy_service = null;
        $this->recommendations = null;
        $this->recommendation_component = null;

        // Reset the mock reflection
        $reflection = new ReflectionProperty(recommendations_repository::class, 'recommendations_helper');
        $reflection->setAccessible(true);
        $reflection->setValue(null);
        $reflection->setAccessible(false);
    }

    /**
     * Switch between recommending from the mock service or the legacy tables.
     *
     * @param bool $legacy
     * @return void
     * @deprecated since Totara 17
     */
    private function toggle_legacy_service(bool $legacy): void {
        set_config('ml_service_url', $legacy ? null : 'http://localhost:5000');
        set_config('ml_service_key', $legacy ? '' : 'testing');
        $this->is_legacy_service = $legacy;
    }

    /**
     * @param array $item_ids
     * @param int $user_id
     * @return void
     */
    private function recommend(array $item_ids, int $user_id): void {
        $this->recommendations[$user_id] = $item_ids;
    }

    /**
     * Must be called before querying recommendations for any one user, this will insert their specific records.
     *
     * @param int $user_id
     * @return void
     */
    private function commit_recommendations(int $user_id): void {
        global $DB;
        $recommendations = $this->recommendations[$user_id] ?? [];

        if ($this->is_legacy_service) {
            // We only want to insert these records once
            $this->recommendations[$user_id] = null;
            foreach ($recommendations as $item_id) {
                $DB->insert_record('ml_recommender_users', [
                    'user_id' => $user_id,
                    'unique_id' => "{$this->recommendation_component}{$item_id}_user{$user_id}",
                    'item_id' => $item_id,
                    'component' => $this->recommendation_component,
                    'time_created' => time(),
                    'score' => 1,
                    'seen' => 0
                ]);
            }
        } else {
            $mock_helper = null;
            if (null !== $this->recommendations) {
                /** @var MockObject $mock_helper */
                $mock_helper = $this->createMock(recommendations::class);
                $mock_helper
                    ->method('get_user_recommendations')
                    ->willReturn($recommendations ?? []);
            }
            $reflection = new ReflectionProperty(recommendations_repository::class, 'recommendations_helper');
            $reflection->setAccessible(true);
            $reflection->setValue($mock_helper);
            $reflection->setAccessible(false);
        }
    }
}

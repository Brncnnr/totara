<?php
/*
 * This file is part of Totara Learn
 *
 * Copyright (C) 2019 onwards Totara Learning Solutions LTD
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
 * @author Mark Metcalfe <mark.metcalfe>@totaralearning.com>
 * @package totara_competency
 */

use core\format;
use core_phpunit\testcase;
use core\webapi\execution_context;
use totara_competency\webapi\resolver\type\scale_value as scale_value_type;
use totara_hierarchy\entity\scale_value;

/**
 * @group totara_competency
 */
class totara_competency_webapi_resolver_type_scale_value_testcase extends testcase {

    /** @var scale_value $scale_value */
    protected $scale_value;

    protected function setUp(): void {
        parent::setUp();

        $this->scale_value = new scale_value([
            'proficient' => 0,
            'numericscore' => 0,
            'idnumber' => 0,
            'scaleid' => 0,
            'sortorder' => 0,
            'timemodified' => 0,
            'usermodified' => 0,
        ]);
    }

    protected function tearDown(): void {
        parent::tearDown();

        $this->scale_value = null;
    }

    /**
     * Resolve the type.
     *
     * @param string $field
     * @param array $format
     * @return mixed
     * @throws coding_exception
     */
    private function resolve_field(string $field, array $format) {
        return scale_value_type::resolve(
            $field,
            $this->scale_value,
            $format,
            execution_context::create('dev', null)
        );
    }

    /**
     * Test that the scale value mutlilang names and descriptions are resolved.
     */
    public function test_resolve_scale_value_field_multilang() {
        // Enable multi-language filter
        filter_manager::reset_caches();
        filter_set_global_state('multilang', TEXTFILTER_ON);
        filter_set_applies_to_strings('multilang', true);

        $multilang_string = '<span lang="en" class="multilang">English</span><span lang="es" class="multilang">Spanish</span>';

        $this->scale_value->name = $multilang_string;
        $this->scale_value->description = $multilang_string;
        $this->scale_value->save();

        $user = $this->getDataGenerator()->create_user();
        $this->assign_cap('totara/hierarchy:viewcompetencyscale', $user->id);

        // resolve name
        $this->assertEquals('English', $this->resolve_field('name', ['format' => format::FORMAT_HTML]));
        $this->assertEquals('English', $this->resolve_field('name', ['format' => format::FORMAT_PLAIN]));
        // without capability
        $this->assertEquals(null, $this->resolve_field('name', ['format' => format::FORMAT_RAW]));
        // with capability
        $this->setUser($user);
        $this->assertEquals(
            '<span lang="en" class="multilang">English</span><span lang="es" class="multilang">Spanish</span>',
            $this->resolve_field('name', ['format' => format::FORMAT_RAW])
        );

        // resolve description
        $this->assertEquals('English', $this->resolve_field('description', ['format' => format::FORMAT_HTML]));
        $this->assertEquals('English', $this->resolve_field('description', ['format' => format::FORMAT_PLAIN]));
        // without capability
        $this->setUser(null);
        $this->assertEquals(null, $this->resolve_field('description', ['format' => format::FORMAT_RAW]));
        // with capability
        $this->setUser($user);
        $this->assertEquals(
            '<span lang="en" class="multilang">English</span><span lang="es" class="multilang">Spanish</span>',
            $this->resolve_field('description', ['format' => format::FORMAT_RAW])
        );
    }

    /**
     * Test that the scale value descriptions that have images in them are resolved.
     */
    public function test_resolve_scale_value_description_image() {
        $this->scale_value->name = 'Value';
        $this->scale_value->save();

        $xss_string = '<script>alert("Bad")</script>';

        get_file_storage()->create_file_from_string([
            'contextid' => context_system::instance()->id,
            'component' => 'totara_hierarchy',
            'filearea' => scale_value::TABLE,
            'filepath' => '/',
            'filename' => 'value.png',
            'itemid' => $this->scale_value->id,
        ], 'File');
        $this->scale_value->description = '<img src="@@PLUGINFILE@@/value.png"/>' . $xss_string;
        $this->scale_value->save();

        $raw_description = $this->resolve_field('description', ['format' => format::FORMAT_PLAIN]);
        $this->assertStringNotContainsString('<img src=', $raw_description);
        $this->assertStringNotContainsString($xss_string, $raw_description);

        $resolved_description = $this->resolve_field('description', ['format' => format::FORMAT_HTML]);
        $this->assertStringContainsString('pluginfile.php', $resolved_description);
        $this->assertStringContainsString('totara_hierarchy', $resolved_description);
        $this->assertStringContainsString(scale_value::TABLE, $resolved_description);
        $this->assertStringContainsString('value.png', $resolved_description);
        $this->assertStringNotContainsString($xss_string, $resolved_description);
    }

    /**
     * assign capability to user
     *
     * @param string $capability
     * @param int $user_id
     * @param bool|null $unassign
     * @throws coding_exception
     * @throws dml_exception
     */
    private function assign_cap(string $capability, int $user_id, ?bool $unassign = false) {
        $roleid = $this->getDataGenerator()->create_role();
        $syscontext = context_system::instance();
        if ($unassign) {
            unassign_capability($capability, $roleid, $syscontext);
        } else {
            assign_capability($capability, CAP_ALLOW, $roleid, $syscontext);
        }
        role_assign($roleid, $user_id, $syscontext);
    }
}

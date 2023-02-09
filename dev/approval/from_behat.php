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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Tatsuhiro Kirihara <tatsuhiro.kirihara@totaralearning.com>
 * @package mod_approval
 */

use Behat\Gherkin\Keywords\CucumberKeywords;
use Behat\Gherkin\Lexer;
use Behat\Gherkin\Parser;
use Behat\Gherkin\Loader\GherkinFileLoader;
use Behat\Gherkin\Node\OutlineNode;
use Behat\Gherkin\Node\FeatureNode;
use Behat\Gherkin\Node\ScenarioInterface;

define('CLI_SCRIPT', 'yes!');

require(__DIR__ . '/../../server/config.php');
global $CFG;
/** @var core_config $CFG */

require_once($CFG->dirroot . '/lib/clilib.php');

[$options, $files] = cli_get_params(
    [
        'help' => false,
        'name' => false,
        'background' => false,
        'until' => false,
        'outline' => false,
        'dry-run' => false,
        'ignore-failure' => false,
        'list-steps' => false,
    ],
    [
        'h' => 'help',
        'b' => 'background',
        'o' => 'outline',
        'n' => 'dry-run',
    ]
);

if (!empty($options['help']) || (empty($options['list-steps']) && empty($files))) {
    $basename = basename(__FILE__);
    cli_writeln("Generate site using the generator steps in a Behat scenario.

Usage: php {$basename} [-b|--background] [--name=NAME] [--until=TEXT] [-n|--dry-run] [-o=N|--outline=N] file.feature

Options:
    -h, --help              Print out this help
    -b, --background        Process background only
    -o=N,--outline=N        Example index of a scenario outline (N = 1, 2, 3, ...)
    -n, --dry-run           Do not actually process steps
    --name=NAME             Name of a scenario in the file
    --until=TEXT            Stop processing when TEXT appears in a step
    --ignore-failure        Swallow failure and continue processing
    --list-steps            List all supported generator steps

Warnings:
    Fresh installation is required.
    Non-generator steps are ignored.
    If an exception is thrown, the site will likely be corrupted.

Examples:
    $ php dev/approval/from_behat.php -b path/to/file.feature
    $ php dev/approval/from_behat.php --name=mod_approval_314 -o=2 --until=\"I log in\" path/to/file.feature
");
    exit(2);
}

if (!file_exists($CFG->srcroot . '/test/behat/vendor/autoload.php')) {
    cli_error("Behat framework is not set up. Run `php test/behat/behat.php init` or `php composer.phar -dtest/behat/ install` before starting.");
}
require_once($CFG->srcroot . '/test/behat/vendor/autoload.php');

require_once($CFG->dirroot . '/lib/testing/classes/util.php');
require_once($CFG->dirroot . '/lib/behat/behat_base.php');
require_once($CFG->dirroot . '/lib/tests/behat/behat_hooks.php');
require_once($CFG->dirroot . '/lib/tests/behat/behat_data_generators.php');
require_once($CFG->dirroot . '/totara/core/tests/behat/behat_totara_core.php');
require_once($CFG->dirroot . '/totara/core/tests/behat/behat_totara_data_generators.php');
require_once($CFG->dirroot . '/totara/hierarchy/tests/behat/behat_totara_hierarchy.php');

$behat_totara_hierarchy = new behat_totara_hierarchy();
$behat_data_generators = new behat_data_generators();
$behat_totara_data_generators = new behat_totara_data_generators();
$behat_totara_core = new behat_totara_core();

$known_steps = [
    '/^the following job assignments exist:$/' => [$behat_totara_hierarchy, 'the_following_job_assignments_exist'],
    '/^the following "((?:[^"]|\\")*)" frameworks exist:$/' => [$behat_totara_hierarchy, 'the_following_frameworks_exist'],
    '/^the following "((?:[^"]|\\")*)" hierarchy exists:$/' => [$behat_totara_hierarchy, 'the_following_hierarchy_exists'],
    '/^the following hierarchy types exist:$/' => [$behat_totara_hierarchy, 'the_following_hierarchy_types_exist'],
    '/^the following hierarchy type custom fields exist:$/' => [$behat_totara_hierarchy, 'the_following_hierarchy_type_custom_fields_exist'],
    '/^the following "((?:[^"]|\\")*)" exist:$/' => [$behat_data_generators, 'the_following_exist'],
    '/^the following "((?:[^"]|\\")*)" exist in "([a-z0-9_]*)" plugin:$/' => [$behat_totara_data_generators, 'the_following_exist_in_plugin'],
    '/^I wait for the next second$/' => [$behat_totara_core, 'i_wait_for_next_second'],
    '/^the multi-language content filter is enabled/' => [$behat_totara_core, 'the_multi_language_content_filter_is_enabled'],
    '/^the multi-language content filter is disabled/' => [$behat_totara_core, 'the_multi_language_content_filter_is_disabled'],
];

// Conditionally add SF-182 generator.
$behat_sf182_path = $CFG->dirroot . '/mod/approval/form/sf182/tests/behat/behat_sf182.php';
if (file_exists($behat_sf182_path)) {
    require_once($behat_sf182_path);
    $behat_sf182 = new behat_sf182();
    $known_steps['/^I create an SF-182 workflow named "((?:[^"]|\\")*)" for the organisation with shortname "((?:[^"]|\\")*)"$/'] = [$behat_sf182, 'install_demo_workflow'];
    $known_steps['/^I install the SF-182 completion evidence type$/'] = [$behat_sf182, 'install_evidence_type'];
}

if (!empty($options['list-steps'])) {
    echo implode("\n", array_keys($known_steps)) . "\n";
    exit(0);
}

$file = $files[0];
if (!file_exists($file)) {
    cli_error("File not found: {$file}");
}

$scenario_name = $options['name'];
$step_until = $options['until'];

$yaml = <<<EOF
"en":
  name: English
  native: English
  feature: Feature
  background: Background
  scenario: Scenario
  scenario_outline: Scenario Outline|Scenario Template
  examples: Examples|Scenarios
  given: "*|Given"
  when: "*|When"
  then: "*|Then"
  and: "*|And"
  but: "*|But"
EOF;

$keywords = new CucumberKeywords($yaml);
$lexer = new Lexer($keywords);
$parser = new Parser($lexer);
$loader = new GherkinFileLoader($parser);
$features = $loader->load($file);

$scenarios_found = [];

foreach ($features as $feature) {
    foreach ($feature->getScenarios() as $scenario) {
        if ((string)$scenario_name === '') {
            $scenarios_found[] = [$feature, $scenario];
        } else if (strpos($scenario->getTitle(), $scenario_name) !== false) {
            $scenarios_found[] = [$feature, $scenario];
        }
    }
}

if (empty($scenarios_found)) {
    if ((string)$scenario_name === '') {
        cli_error("No scenarios found in {$file}");
    } else {
        cli_error("No scenarios matching '{$scenario_name}' found in {$file}, please review the `--name` option");
    }
}
if (count($scenarios_found) > 1) {
    if (empty($options['background']) || count($features) > 1) {
        if ((string)$scenario_name === '') {
            cli_error("Multiple scenarios found in {$file}, please use the `--name` option");
        } else {
            cli_error("Multiple scenarios matching '{$scenario_name}' found in {$file}, please refine the `--name` option");
        }
    }
}

[$feature, $scenario] = $scenarios_found[0];
/** @var FeatureNode $feature */
/** @var ScenarioInterface $scenario */
$containers = [$feature->getBackground()];
if (empty($options['background'])) {
    if ($scenario instanceof OutlineNode) {
        $examples = $scenario->getExamples();
        if (!isset($options['outline']) || (string)$options['outline'] === '') {
            cli_error("'{$scenario->getTitle()}' is a scenario outline, please use the `--outline` option");
        }
        $outline_index = (int)($options['outline']) - 1;
        if ($outline_index < 0 || $outline_index >= count($examples)) {
            cli_error("The outline #{$options['outline']} is out of range for scenario '{$scenario->getTitle()}'");
        }
        $scenario = $examples[$outline_index];
    }
    $containers[] = $scenario;
}

$steps = [];
foreach ($containers as $container) {
    if ($container === null) {
        continue;
    }
    foreach ($container->getSteps() as $step) {
        $text = $step->getText();
        if ((string)$step_until !== '' && strpos($text, $step_until) !== false) {
            break;
        }
        foreach ($known_steps as $known_step => $data) {
            if (preg_match($known_step, $text, $matches)) {
                array_shift($matches);
                if (!empty($step->getArguments())) {
                    $matches[] = $step->getArguments()[0];
                }
                array_unshift($data, $step->getText());
                $data[] = $matches;
                $steps[] = $data;
                break;
            }
        }
    }
}

if (empty($steps)) {
    if (!empty($options['background'])) {
        cli_error("No background steps found for feature '{$feature->getTitle()}'");
    } else {
        cli_error("No steps found for scenario '{$scenario->getTitle()}'");
    }
}

if (!empty($options['dry-run'])) {
    $processor = function () {
        // do nothing
    };
} else if (!empty($options['ignore-failure'])) {
    $processor = function ($gen, $method, $args) {
        global $DB;
        $transaction = $DB->start_delegated_transaction();
        try {
            $gen->{$method}(...$args);
            $transaction->allow_commit();
            echo "OK";
        } catch (Throwable $ex) {
            $transaction->rollback();
            echo "FAIL\n";
            echo implode("\n", array_map(function ($text) { return "E> {$text}"; }, explode("\n", $ex->getMessage())));
        }
    };
} else {
    $processor = function ($gen, $method, $args) {
        try {
            $gen->{$method}(...$args);
        } catch (Throwable $ex) {
            echo "FAIL: database is now corrupted :(\n";
            throw $ex;
        }
        echo "OK";
    };
}

foreach ($steps as [$text, $gen, $method, $args]) {
    echo "Processing {$text} ... ";
    $processor($gen, $method, $args);
    echo "\n";
}

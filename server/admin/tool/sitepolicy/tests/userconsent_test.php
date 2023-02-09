<?php
/**
 * This file is part of Totara Learn
 *
 * Copyright (C) 2018 onwards Totara Learning Solutions LTD
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Riana Rossouw <riana.rossouw@totaralearning.com>
 * @package tool_sitepolicy
 */

namespace tool_sitepolicy;

defined('MOODLE_INTERNAL') || die();

/**
 * Sitepolicy tests
 */
class tool_sitepolicy_userconsent_test extends \advanced_testcase {

    /**
     * Test save with and without consentoption
     */
    public function test_save_with_exception_no_consentoptionid() {
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Expected consentoptionid and language not set');

        $userconsent = new userconsent();
        $userconsent->save();
    }

    /**
     * Test save
     */
    public function test_save_exception_no_language() {
        $this->expectException('coding_exception');
        $this->expectExceptionMessage('Expected consentoptionid and language not set');

        $userconsent = new userconsent();
        $userconsent->set_consentoptionid(1);
        $userconsent->save();
    }

    /**
     * Test save
     */
    public function test_save() {
        global $DB, $USER;


        $userconsent = new userconsent();
        $userconsent->set_timeconsented(1523249171);
        $userconsent->set_consentoptionid(1);
        $userconsent->set_language('en');
        $userconsent->save();
        $rows = $DB->get_records('tool_sitepolicy_user_consent');
        $this->assertEquals(1, count($rows));
        $row = array_shift($rows);
        $this->assertEquals($row->id, $userconsent->get_id());
        $this->assertEquals(0, $row->hasconsented);
        $this->assertEquals(1, $row->consentoptionid);
        $this->assertEquals('en', $row->language);

        // Force it to load from the database.
        $userconsent = new userconsent($userconsent->get_id());
        self::assertEquals($USER->id, $userconsent->get_userid());
        self::assertEquals(1523249171, $userconsent->get_timeconsented());
        self::assertEquals(0, $userconsent->get_hasconsented());
        self::assertEquals('en', $userconsent->get_language());
        self::assertEquals(1, $userconsent->get_consentoptionid());
    }

    /**
     * Test get_unansweredpolicies when there is only a draft version
     */
    public function test_get_unansweredpolicies_draft() {

        /** @var \tool_sitepolicy\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $options = [
            'hasdraft' => true,
            'numpublished' => 0,
            'allarchived' => false,
            'authorid' => 2,
            'languages' => 'es,en,nl',
            'langprefix' => ',en,nl',
            'title' => 'Test policy',
            'statement' => 'Policy statement',
            'numoptions' => 2,
            'consentstatement' => 'Consent statement',
            'providetext' => 'yes',
            'withheldtext' => 'no',
            'mandatory' => 'first'
        ];

        $generator->create_multiversion_policy($options);

        $consentpolicies = userconsent::get_unansweredpolicies(2);
        $this->assertEquals(0, count($consentpolicies));
    }

    /**
     * Test get_unansweredpolicies when there are only archived versions
     */
    public function test_get_unansweredpolicies_archived() {

        /** @var \tool_sitepolicy\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $options = [
            'hasdraft' => false,
            'numpublished' => 1,
            'allarchived' => true,
            'authorid' => 2,
            'languages' => 'es,en,nl',
            'langprefix' => ',en,nl',
            'title' => 'Test policy',
            'statement' => 'Policy statement',
            'numoptions' => 2,
            'consentstatement' => 'Consent statement',
            'providetext' => 'yes',
            'withheldtext' => 'no',
            'mandatory' => 'first'
        ];

        $generator->create_multiversion_policy($options);

        $consentpolicies = userconsent::get_unansweredpolicies(2);
        $this->assertEquals(0, count($consentpolicies));
    }

    /**
     * Test get_unansweredpolicies when there is only a published version
     */
    public function test_get_unansweredpolicies_published() {
        global $DB;


        /** @var \tool_sitepolicy\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $options = [
            'hasdraft' => false,
            'numpublished' => 1,
            'allarchived' => false,
            'authorid' => 2,
            'languages' => 'es,en,nl',
            'langprefix' => ',en,nl',
            'title' => 'Test policy',
            'statement' => 'Policy statement',
            'numoptions' => 2,
            'consentstatement' => 'Consent statement',
            'providetext' => 'yes',
            'withheldtext' => 'no',
            'mandatory' => 'first'
        ];

        $sitepolicy = $generator->create_multiversion_policy($options);
        $activeversion = policyversion::from_policy_latest($sitepolicy, policyversion::STATUS_PUBLISHED);

        $existingoptions = $DB->get_records('tool_sitepolicy_consent_options', ['policyversionid' => $activeversion->get_id()]);
        $mandatoryoptions = array_filter($existingoptions, function($val) {
            return $val->mandatory;
        });
        $mandatoryoption = array_shift($mandatoryoptions);
        $optionaloptions = array_filter($existingoptions, function($val) {
            return !$val->mandatory;
        });
        $optionaloption = array_shift($optionaloptions);

        $this->assertEquals(2, count($existingoptions));

        // No consent given yet
        $consentpolicies = userconsent::get_unansweredpolicies(2);
        $this->assertEquals(1, count($consentpolicies));

        // User doesn't agree with mandatory option
        $userconsent = new userconsent();
        $userconsent->set_userid(2);
        $userconsent->set_consentoptionid($mandatoryoption->id);
        $userconsent->set_language('nl');
        $userconsent->set_hasconsented(0);
        $userconsent->set_timeconsented(time()-10);
        $userconsent->save();

        $consentpolicies = userconsent::get_unansweredpolicies(2);
        $this->assertEquals(1, count($consentpolicies));

        // User again doesn't agree with mandatory option
        $userconsent = new userconsent();
        $userconsent->set_userid(2);
        $userconsent->set_consentoptionid($mandatoryoption->id);
        $userconsent->set_language('nl');
        $userconsent->set_hasconsented(0);
        $userconsent->set_timeconsented(time()-9);
        $userconsent->save();

        $consentpolicies = userconsent::get_unansweredpolicies(2);
        $this->assertEquals(1, count($consentpolicies));

        // User consents to mandatory option
        $userconsent->set_consentoptionid($mandatoryoption->id);
        $userconsent->set_hasconsented(1);
        $userconsent->set_timeconsented(time()-7);
        $userconsent->save();

        $consentpolicies = userconsent::get_unansweredpolicies(2);
        $this->assertEquals(1, count($consentpolicies)); // Hasn't answered optional

        // User doesn't agree with optional option
        $userconsent->set_consentoptionid($optionaloption->id);
        $userconsent->set_language('nl');
        $userconsent->set_hasconsented(0);
        $userconsent->set_timeconsented(time()-8);
        $userconsent->save();

        $consentpolicies = userconsent::get_unansweredpolicies(2);
        $this->assertEquals(0, count($consentpolicies));

        // Now user revokes his consent to mandatory option
        $userconsent->set_consentoptionid($mandatoryoption->id);
        $userconsent->set_hasconsented(0);
        $userconsent->set_timeconsented(time()-6);
        $userconsent->save();

        $consentpolicies = userconsent::get_unansweredpolicies(2);
        $this->assertEquals(1, count($consentpolicies));

        // Also check that full history is stored
        $rows = $DB->get_records('tool_sitepolicy_user_consent');
        $this->assertEquals(5, count($rows));
    }

    /**
     * Test has_user_consented
     */
    public function test_has_user_consented() {
        global $DB;


        /** @var \tool_sitepolicy\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $options = [
            'hasdraft' => false,
            'numpublished' => 1,
            'allarchived' => false,
            'authorid' => 2,
            'languages' => 'es,en,nl',
            'langprefix' => ',en,nl',
            'title' => 'Test policy',
            'statement' => 'Policy statement',
            'statementformat' => FORMAT_PLAIN,
            'numoptions' => 2,
            'consentstatement' => 'Consent statement',
            'providetext' => 'yes',
            'withheldtext' => 'no',
            'mandatory' => 'first'
        ];

        $sitepolicy = $generator->create_multiversion_policy($options);
        $version = policyversion::from_policy_latest($sitepolicy);

        $existingoptions = $DB->get_records('tool_sitepolicy_consent_options', ['policyversionid' => $version->get_id()]);
        $oneoption = reset($existingoptions);
        $this->assertEquals(2, count($existingoptions));

        // No consent given yet
        foreach ($existingoptions as $option) {
            $this->assertFalse(userconsent::has_user_consented($option->id, 2));
        }

        // User doesn't agree with this option
        $userconsent = new userconsent();
        $userconsent->set_userid(2);
        $userconsent->set_consentoptionid($oneoption->id);
        $userconsent->set_language('nl');
        $userconsent->set_hasconsented(0);
        $userconsent->set_timeconsented(time()-2);
        $userconsent->save();
        $this->assertFalse(userconsent::has_user_consented($oneoption->id, 2));

        // Then user agrees with this option in a different language
        $userconsent->set_language('es');
        $userconsent->set_hasconsented(1);
        $userconsent->set_timeconsented(time()-1);
        $userconsent->save();
        $this->assertTrue(userconsent::has_user_consented($oneoption->id, 2));

        // And now he revokes his consent again
        $userconsent->set_language('en');
        $userconsent->set_hasconsented(0);
        $userconsent->set_timeconsented(time());
        $userconsent->save();
        $this->assertFalse(userconsent::has_user_consented($oneoption->id, 2));
    }

    /**
     * Test user_has_answered
     */
    public function test_has_user_answered() {
        global $DB;


        /** @var \tool_sitepolicy\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $options = [
            'hasdraft' => false,
            'numpublished' => 1,
            'allarchived' => false,
            'authorid' => 2,
            'languages' => 'es,en,nl',
            'langprefix' => ',en,nl',
            'title' => 'Test policy',
            'statement' => 'Policy statement',
            'statementformat' => FORMAT_PLAIN,
            'numoptions' => 2,
            'consentstatement' => 'Consent statement',
            'providetext' => 'yes',
            'withheldtext' => 'no',
            'mandatory' => 'first'
        ];

        $sitepolicy = $generator->create_multiversion_policy($options);
        $version = policyversion::from_policy_latest($sitepolicy);

        $existingoptions = $DB->get_records('tool_sitepolicy_consent_options', ['policyversionid' => $version->get_id()]);
        $oneoption = reset($existingoptions);
        $this->assertEquals(2, count($existingoptions));

        // No consent answered yet
        foreach ($existingoptions as $option) {
            $this->assertFalse(userconsent::has_user_answered($option->id, 2));
        }

        // User doesn't agree with this option
        $userconsent = new userconsent();
        $userconsent->set_userid(2);
        $userconsent->set_consentoptionid($oneoption->id);
        $userconsent->set_language('nl');
        $userconsent->set_hasconsented(0);
        $userconsent->set_timeconsented(time()-2);
        $userconsent->save();
        $this->assertTrue(userconsent::has_user_answered($oneoption->id, 2));

        // Then user agrees with this option in a different language
        $userconsent->set_language('es');
        $userconsent->set_hasconsented(1);
        $userconsent->set_timeconsented(time()-1);
        $userconsent->save();
        $this->assertTrue(userconsent::has_user_answered($oneoption->id, 2));
    }

    /**
     * Data provider for test_user_consent_language.
     */
    public function data_user_consent_language() {
        return [
            [
                'syslang_is_primary',
                [
                    'authorid' => 2,
                    'languages' => 'en',
                    'title' => 'EN only Test policy',
                    'statement' => 'EN only Policy statement',
                    'numoptions' => 1,
                    'consentstatement' => 'EN only Consent statement',
                    'providetext' => 'yes',
                    'withheldtext' => 'no',
                    'mandatory' => 'all',
                ],
                ['en','en']
            ],
            [
                'syslang_is_avail',
                [
                    'authorid' => 2,
                    'languages' => 'fr,en',
                    'langprefix' => 'fr,en',
                    'title' => 'EN only Test policy',
                    'statement' => 'EN only Policy statement',
                    'numoptions' => 1,
                    'consentstatement' => 'EN only Consent statement',
                    'providetext' => 'yes',
                    'withheldtext' => 'no',
                    'mandatory' => 'all',
                ],
                ['en','en']
            ],
            [
                'user_preference_and_non_syslang_primary',
                [
                    'authorid' => 2,
                    'languages' => 'es',
                    'title' => 'SP only Test policy',
                    'statement' => 'SP only Policy statement',
                    'numoptions' => 1,
                    'consentstatement' => 'SP only Consent statement',
                    'providetext' => 'si',
                    'withheldtext' => 'no',
                    'mandatory' => 'all',
                ],
                ['es','es']
            ],
            [
                'user_preferences_avail',
                [
                    'authorid' => 2,
                    'languages' => 'nl,es',
                    'langprefix' => 'nl,es',
                    'title' => 'Test policy',
                    'statement' => 'Policy statement',
                    'numoptions' => 1,
                    'consentstatement' => 'Consent statement',
                    'providetext' => 'Yes',
                    'withheldtext' => 'No',
                    'mandatory' => 'First',
                ],
                ['es','nl']
            ],
            [
                'primary_for_all',
                [
                    'authorid' => 2,
                    'languages' => 'fr,he',
                    'langprefix' => 'fr,he',
                    'title' => 'Test policy',
                    'statement' => 'Policy statement',
                    'numoptions' => 1,
                    'consentstatement' => 'Consent statement',
                    'providetext' => 'Yes',
                    'withheldtext' => 'No',
                    'mandatory' => 'First',
                ],
                ['fr','fr']
            ],
        ];
    }

    /**
     * Test get_user_consent_language
     * @dataProvider data_user_consent_language
     */
    public function test_get_user_consent_language($name, $options, $expectedlang) {
        /** @var \tool_sitepolicy\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $stringmanager = get_string_manager();
        $method = new \ReflectionMethod($stringmanager, 'get_key_suffix');
        $method->setAccessible(true);

        $cachekey = 'list_'.$method->invoke($stringmanager);
        $property = new \ReflectionProperty($stringmanager, 'menucache');
        $property->setAccessible(true);
        $property->getValue($stringmanager)->set($cachekey, [
            'en' => 'English (en)',
            'es' => 'Español - Internacional ‎(es)‎',
            'fr' => 'Français ‎(fr)‎',
            'nl' => 'Nederlands ‎(nl)‎‎',
        ]);

        $user1 = $this->getDataGenerator()->create_user(['username' => 'user1', 'lang' => 'es']);
        $user2 = $this->getDataGenerator()->create_user(['username' => 'user2', 'lang' => 'nl']);

        $sitepolicy = $generator->create_published_policy($options);
        $versionid = policyversion::from_policy_latest($sitepolicy)->get_id();

        $lang1 = userconsent::get_user_consent_language($versionid, $user1->id);
        $this->assertEquals($expectedlang[0], $lang1, 'Failed while processing '.$name);
        $lang2 = userconsent::get_user_consent_language($versionid, $user2->id);
        $this->assertEquals($expectedlang[1], $lang2, 'Failed while processing '.$name);
    }

    /**
     * Test get_userconsenttable
     */
    public function test_get_userconsenttable() {

        /** @var \tool_sitepolicy\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $options = [
            'hasdraft' => true,
            'numpublished' => 3,
            'allarchived' => false,
            'authorid' => 2,
            'languages' => 'es,en,nl',
            'langprefix' => ',en,nl',
            'title' => 'Test policy',
            'statement' => 'Policy statement',
            'numoptions' => 2,
            'consentstatement' => 'Consent statement',
            'providetext' => 'yes',
            'withheldtext' => 'no',
            'mandatory' => 'first',
            'hasconsented' => true,
            'consentuser' => 3
        ];

        $generator->create_multiversion_policy($options);

        $consents = userconsent::get_userconsenttable(5);
        $this->assertEquals(0, count($consents));

        $consents = userconsent::get_userconsenttable(3);
        $this->assertEquals(2, count($consents));
    }

    /**
     * Test is_consent_needed
     */
    public function test_is_consent_needed_all() {
        global $DB, $USER;

        /** @var \tool_sitepolicy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $options = [
            'hasdraft' => false,
            'numpublished' => 1,
            'allarchived' => false,
            'authorid' => 2,
            'languages' => 'es,en,nl',
            'langprefix' => ',en,nl',
            'title' => 'Test policy',
            'statement' => 'Policy statement',
            'statementformat' => FORMAT_PLAIN,
            'numoptions' => 2,
            'consentstatement' => 'Consent statement',
            'providetext' => 'yes',
            'withheldtext' => 'no',
            'mandatory' => 'first'
        ];

        $sitepolicy = $generator->create_multiversion_policy($options);
        $version = policyversion::from_policy_latest($sitepolicy);

        $existingoptions = $DB->get_records('tool_sitepolicy_consent_options', ['policyversionid' => $version->get_id()]);
        $oneoption = reset($existingoptions);
        $this->assertEquals(2, count($existingoptions));

        // Normal user
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Site policies not enabled
        $this->assertFalse(userconsent::is_consent_needed($user->id));

        // Site policies enabled
        set_config('enablesitepolicies', 1);
        $this->assertTrue(userconsent::is_consent_needed($user->id));

        // Guest user
        $this->setGuestUser();
        $this->assertTrue(userconsent::is_consent_needed($USER->id));

        // Testing here only with Google web crawlers. More crawlers can be found in core_useragent_testcase::user_agents_providers
        $crawlers = [
            'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
            'Googlebot/2.1 (+http://www.googlebot.com/bot.html)',
            'Googlebot-Image/1.0',
            'Mozilla/5.0 (compatible; Yahoo! Slurp; http://help.yahoo.com/help/us/ysearch/slurp)',
        ];

        // Not yet open for google
        foreach ($crawlers as $useragent) {
            \core_useragent::instance(true, $useragent);
            $this->assertTrue(userconsent::is_consent_needed($USER->id));
        }

        // Now open to google
        set_config('opentogoogle', 1);
        foreach ($crawlers as $useragent) {
            \core_useragent::instance(true, $useragent);
            $this->assertFalse(userconsent::is_consent_needed($USER->id));
        }

        // Just verifying that normal guest still needs to consent
        \core_useragent::instance(true, null);
        $this->assertTrue(userconsent::is_consent_needed($USER->id));
    }

    /**
     * Test is_consent_needed when policy applies to authenticated users only.
     */
    public function test_is_consent_needed_authenticated_only() {
        global $DB, $USER;

        /** @var \tool_sitepolicy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $options = [
            'hasdraft' => false,
            'numpublished' => 1,
            'allarchived' => false,
            'authorid' => 2,
            'languages' => 'es,en,nl',
            'applies_to' => 'authenticated',
            'langprefix' => ',en,nl',
            'title' => 'Test policy',
            'statement' => 'Policy statement',
            'statementformat' => FORMAT_PLAIN,
            'numoptions' => 2,
            'consentstatement' => 'Consent statement',
            'providetext' => 'yes',
            'withheldtext' => 'no',
            'mandatory' => 'first'
        ];

        $sitepolicy = $generator->create_multiversion_policy($options);
        $version = policyversion::from_policy_latest($sitepolicy);

        $existingoptions = $DB->get_records('tool_sitepolicy_consent_options', ['policyversionid' => $version->get_id()]);
        $oneoption = reset($existingoptions);
        $this->assertEquals(2, count($existingoptions));

        // Normal user
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Site policies enabled
        set_config('enablesitepolicies', 1);
        $this->assertTrue(userconsent::is_consent_needed($user->id));

        // Guest user
        $this->setGuestUser();
        $this->assertFalse(userconsent::is_consent_needed($USER->id));

        // Testing here only with Google web crawlers. More crawlers can be found in core_useragent_testcase::user_agents_providers
        $crawlers = [
            'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
            'Googlebot/2.1 (+http://www.googlebot.com/bot.html)',
            'Googlebot-Image/1.0',
            'Mozilla/5.0 (compatible; Yahoo! Slurp; http://help.yahoo.com/help/us/ysearch/slurp)',
        ];

        // Not yet open for google
        foreach ($crawlers as $useragent) {
            \core_useragent::instance(true, $useragent);
            $this->assertFalse(userconsent::is_consent_needed($USER->id));
        }

        // Now open to google
        set_config('opentogoogle', 1);
        foreach ($crawlers as $useragent) {
            \core_useragent::instance(true, $useragent);
            $this->assertFalse(userconsent::is_consent_needed($USER->id));
        }

        // Just verifying that normal guest still does not need consent
        \core_useragent::instance(true, null);
        $this->assertFalse(userconsent::is_consent_needed($USER->id));
    }

    /**
     * Test is_consent_needed when policy applies to guest users only.
     */
    public function test_is_consent_needed_guest_only() {
        global $DB, $USER;

        /** @var \tool_sitepolicy_generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $options = [
            'hasdraft' => false,
            'numpublished' => 1,
            'allarchived' => false,
            'authorid' => 2,
            'languages' => 'es,en,nl',
            'applies_to' => 'guest',
            'langprefix' => ',en,nl',
            'title' => 'Test policy',
            'statement' => 'Policy statement',
            'statementformat' => FORMAT_PLAIN,
            'numoptions' => 2,
            'consentstatement' => 'Consent statement',
            'providetext' => 'yes',
            'withheldtext' => 'no',
            'mandatory' => 'first'
        ];

        $sitepolicy = $generator->create_multiversion_policy($options);
        $version = policyversion::from_policy_latest($sitepolicy);

        $existingoptions = $DB->get_records('tool_sitepolicy_consent_options', ['policyversionid' => $version->get_id()]);
        $oneoption = reset($existingoptions);
        $this->assertEquals(2, count($existingoptions));

        // Normal user
        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);

        // Site policies enabled
        set_config('enablesitepolicies', 1);
        $this->assertFalse(userconsent::is_consent_needed($user->id));

        // Guest user
        $this->setGuestUser();
        $this->assertTrue(userconsent::is_consent_needed($USER->id));

        // Testing here only with Google web crawlers. More crawlers can be found in core_useragent_testcase::user_agents_providers
        $crawlers = [
            'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
            'Googlebot/2.1 (+http://www.googlebot.com/bot.html)',
            'Googlebot-Image/1.0',
            'Mozilla/5.0 (compatible; Yahoo! Slurp; http://help.yahoo.com/help/us/ysearch/slurp)',
        ];

        // Not yet open for google
        foreach ($crawlers as $useragent) {
            \core_useragent::instance(true, $useragent);
            $this->assertTrue(userconsent::is_consent_needed($USER->id));
        }

        // Now open to google
        set_config('opentogoogle', 1);
        foreach ($crawlers as $useragent) {
            \core_useragent::instance(true, $useragent);
            $this->assertFalse(userconsent::is_consent_needed($USER->id));
        }

        // Just verifying that normal guest still needs consent
        \core_useragent::instance(true, null);
        $this->assertTrue(userconsent::is_consent_needed($USER->id));
    }

    /**
     * Test has_consented_previous_version
     */
    public function test_has_consented_previous_version() {
        global $DB;


        /** @var \tool_sitepolicy\testing\generator $generator */
        $generator = $this->getDataGenerator()->get_plugin_generator('tool_sitepolicy');

        $options = [
            'hasdraft' => true,
            'numpublished' => 1,
            'allarchived' => false,
            'authorid' => 2,
            'languages' => 'es,en,nl',
            'langprefix' => ',en,nl',
            'title' => 'Test policy',
            'statement' => 'Policy statement',
            'numoptions' => 1,
            'consentstatement' => 'Consent statement',
            'providetext' => 'yes',
            'withheldtext' => 'no',
            'mandatory' => 'first'
        ];

        $sitepolicy = $generator->create_multiversion_policy($options);
        $activeversion = policyversion::from_policy_latest($sitepolicy, policyversion::STATUS_PUBLISHED);
        $draftversion = policyversion::from_policy_latest($sitepolicy, policyversion::STATUS_DRAFT);
        $row = $DB->get_record('tool_sitepolicy_consent_options', ['policyversionid' => $activeversion->get_id()]);
        $consentoptionid = $row->id;

        // No consent given yet
        $this->assertFalse(userconsent::has_consented_previous_version($activeversion, 3));
        $this->assertFalse(userconsent::has_consented_previous_version($activeversion, 4));

        // User 3 give consent
        $userconsent = new userconsent();
        $userconsent->set_userid(3);
        $userconsent->set_consentoptionid($consentoptionid);
        $userconsent->set_language('nl');
        $userconsent->set_hasconsented(1);
        $userconsent->set_timeconsented(time()-10);
        $userconsent->save();

        // Now publish the draft version
        $draftversion->publish();
        $this->assertTrue(userconsent::has_consented_previous_version($draftversion, 3));
        $this->assertFalse(userconsent::has_consented_previous_version($draftversion, 4));
    }
}
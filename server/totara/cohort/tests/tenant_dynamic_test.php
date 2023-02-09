<?php
/*
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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_cohort
 */

defined('MOODLE_INTERNAL') || die();

class totara_cohort_tenant_dynamic_testcase extends advanced_testcase {
    private function create_cohort_rules(stdClass $rule, stdClass $ruleparam): void {
        $generator = \totara_cohort\testing\generator::instance();
        $generator->create_cohort_rule_params(
            $rule->rulesetid, $rule->type, $rule->name, $ruleparam->params, $ruleparam->listofvalues
        );
    }

    /**
     * @param int|null $contextid
     * @return stdClass
     */
    private function create_cohort_and_ruleset(int $contextid = null): stdClass {
        $cohort = $this->getDataGenerator()->create_cohort(['cohorttype' => 2, 'contextid' => $contextid]);
        $rulesetid = cohort_rule_create_ruleset($cohort->activecollectionid);
        $cohort->rulesetid = $rulesetid;
        return $cohort;
    }

    public function test_tenant_context_restricts_to_participants() {
        global $DB, $CFG;
        require_once("{$CFG->dirroot}/totara/cohort/lib.php");

        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $this->setAdminUser();

        $tenant1 = $tenantgenerator->create_tenant();
        $tenant2 = $tenantgenerator->create_tenant();
        $tenant3 = $tenantgenerator->create_tenant();

        $user0_1 = $this->getDataGenerator()->create_user(['tenantid' => null, 'email' => 'user01@pokus.com']);
        $user0_2 = $this->getDataGenerator()->create_user(['tenantparticipant' => $tenant1->idnumber, 'email' => 'user02@pokus.com']);
        $user1_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id, 'email' => 'user11@pokus.com']);
        $user1_2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id, 'email' => 'user12@pokus.com']);
        $user2_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id, 'email' => 'user21@pokus.com']);
        $user2_2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id, 'email' => 'user22@pokus.com']);
        $user3_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant3->id, 'email' => 'user31@pokus.com']);

        // System level means all users.
        $cohort1 = $this->create_cohort_and_ruleset();
        $rule = new stdClass();
        $rule->rulesetid = $cohort1->rulesetid;
        $rule->type = "user";
        $rule->name = 'email';
        $ruleparam = new stdClass();
        $ruleparam->params = ['equal' => COHORT_RULES_OP_IN_CONTAINS];
        $ruleparam->listofvalues = ['@pokus.com'];
        $this->create_cohort_rules($rule, $ruleparam);
        totara_cohort_update_dynamic_cohort_members($cohort1->id);
        $members = $DB->get_fieldset_sql('SELECT userid FROM "ttr_cohort_members" WHERE cohortid = ? ORDER BY userid ASC', [$cohort1->id]);
        $expected = [$user0_1->id, $user0_2->id, $user1_1->id, $user1_2->id, $user2_1->id, $user2_2->id, $user3_1->id];
        $this->assertSame($expected, $members);

        // Tenant levels restrits to participants.
        $tenantcategory1 = $DB->get_record('course_categories', ['id' => $tenant1->categoryid], '*', MUST_EXIST);
        $tenantcontext1 = context_coursecat::instance($tenantcategory1->id);
        $cohort2 = $this->create_cohort_and_ruleset($tenantcontext1->id);
        $rule = new stdClass();
        $rule->rulesetid = $cohort2->rulesetid;
        $rule->type = "user";
        $rule->name = 'email';
        $ruleparam = new stdClass();
        $ruleparam->params = ['equal' => COHORT_RULES_OP_IN_CONTAINS];
        $ruleparam->listofvalues = ['@pokus.com'];
        $this->create_cohort_rules($rule, $ruleparam);
        totara_cohort_update_dynamic_cohort_members($cohort2->id);
        $members = $DB->get_fieldset_sql('SELECT userid FROM "ttr_cohort_members" WHERE cohortid = ? ORDER BY userid ASC', [$cohort2->id]);
        $expected = [$user0_2->id, $user1_1->id, $user1_2->id];
        $this->assertSame($expected, $members);

        // Tenants disabled means ignore restrictions.
        $tenantgenerator->disable_tenants();
        totara_cohort_update_dynamic_cohort_members($cohort2->id);
        $members = $DB->get_fieldset_sql('SELECT userid FROM "ttr_cohort_members" WHERE cohortid = ? ORDER BY userid ASC', [$cohort2->id]);
        $expected = [$user0_1->id, $user0_2->id, $user1_1->id, $user1_2->id, $user2_1->id, $user2_2->id, $user3_1->id];
        $this->assertSame($expected, $members);
    }
}

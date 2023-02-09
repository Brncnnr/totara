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
 * @author Petr Skoda <petr.skoda@totaralearning.com>
 * @package totara_cohort
 */

defined('MOODLE_INTERNAL') || die();

class totara_cohort_audience_member_tenant_member_rule_testcase extends advanced_testcase {
    private function create_cohort_rules(stdClass $rule, stdClass $ruleparam): void {
        $generator = \totara_cohort\testing\generator::instance();
        $generator->create_cohort_rule_params(
            $rule->rulesetid, $rule->type, $rule->name, $ruleparam->params, $ruleparam->listofvalues
        );
    }

    /**
     * @return stdClass
     */
    private function create_cohort_and_ruleset(): stdClass {
        $cohort = $this->getDataGenerator()->create_cohort(['cohorttype' => 2]);
        $rulesetid = cohort_rule_create_ruleset($cohort->activecollectionid);
        $cohort->rulesetid = $rulesetid;
        return $cohort;
    }

    public function test_audiencen_rule_tenant_members() {
        global $DB, $CFG;
        require_once("{$CFG->dirroot}/totara/cohort/lib.php");

        /** @var \totara_tenant\testing\generator $tenantgenerator */
        $tenantgenerator = \totara_tenant\testing\generator::instance();
        $tenantgenerator->enable_tenants();
        $this->setAdminUser();

        $user0_1 = $this->getDataGenerator()->create_user(['tenantid' => null]);

        $tenant1 = $tenantgenerator->create_tenant();
        $user1_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);
        $user1_2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant1->id]);

        $tenant2 = $tenantgenerator->create_tenant();
        $user2_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);
        $user2_2 = $this->getDataGenerator()->create_user(['tenantid' => $tenant2->id]);

        $tenant3 = $tenantgenerator->create_tenant();
        $user3_1 = $this->getDataGenerator()->create_user(['tenantid' => $tenant3->id]);

        $cohort1 = $this->create_cohort_and_ruleset();
        $rule = new stdClass();
        $rule->rulesetid = $cohort1->rulesetid;
        $rule->type = "user";
        $rule->name = 'tenantmember';
        $ruleparam = new stdClass();
        $ruleparam->params = ['equal' => COHORT_RULES_OP_IN_EQUAL];
        $ruleparam->listofvalues = [$tenant1->id];
        $this->create_cohort_rules($rule, $ruleparam);
        totara_cohort_update_dynamic_cohort_members($cohort1->id);
        $members = $DB->get_fieldset_sql('SELECT userid FROM "ttr_cohort_members" WHERE cohortid = ? ORDER BY userid ASC', [$cohort1->id]);
        $expected = [$user1_1->id, $user1_2->id];
        $this->assertSame($expected, $members);

        $cohort2 = $this->create_cohort_and_ruleset();
        $rule = new stdClass();
        $rule->rulesetid = $cohort2->rulesetid;
        $rule->type = "user";
        $rule->name = 'tenantmember';
        $ruleparam = new stdClass();
        $ruleparam->params = ['equal' => COHORT_RULES_OP_IN_EQUAL];
        $ruleparam->listofvalues = [$tenant1->id, $tenant3->id];
        $this->create_cohort_rules($rule, $ruleparam);
        totara_cohort_update_dynamic_cohort_members($cohort2->id);
        $members = $DB->get_fieldset_sql('SELECT userid FROM "ttr_cohort_members" WHERE cohortid = ? ORDER BY userid ASC', [$cohort2->id]);
        $expected = [$user1_1->id, $user1_2->id, $user3_1->id];
        $this->assertSame($expected, $members);

        $cohort3 = $this->create_cohort_and_ruleset();
        $rule = new stdClass();
        $rule->rulesetid = $cohort3->rulesetid;
        $rule->type = "user";
        $rule->name = 'tenantmember';
        $ruleparam = new stdClass();
        $ruleparam->params = ['equal' => COHORT_RULES_OP_IN_NOTEQUAL];
        $ruleparam->listofvalues = [$tenant1->id, $tenant3->id];
        $this->create_cohort_rules($rule, $ruleparam);
        totara_cohort_update_dynamic_cohort_members($cohort3->id);
        $members = $DB->get_fieldset_sql('SELECT userid FROM "ttr_cohort_members" WHERE cohortid = ? ORDER BY userid ASC', [$cohort3->id]);
        $expected = [$user2_1->id, $user2_2->id];
        $this->assertSame($expected, $members);
    }
}

@totara @totara_reportbuilder @mod_facetoface
Feature: I am able to see all resources
  that is within the seminars rooms report

  Background:
    Given I am on a totara site
    And the following "courses" exist:
      | fullname  | shortname | category |
      | Course101 | 101       | 0        |
    And the following "global assets" exist in "mod_facetoface" plugin:
      | name  |
      | asset1 |
    And the following "custom assets" exist in "mod_facetoface" plugin:
      | name  |
      | asset2 |
      | asset3 |
    And the following "global facilitators" exist in "mod_facetoface" plugin:
      | name  |
      | facilitator1 |
    And the following "custom facilitators" exist in "mod_facetoface" plugin:
      | name  |
      | facilitator2 |
      | facilitator3 |
    And the following "global rooms" exist in "mod_facetoface" plugin:
      | name  |
      | room1 |
    And the following "custom rooms" exist in "mod_facetoface" plugin:
      | name  |
      | room2 |
      | room3 |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname           | shortname     | source                 |
      | asset report       | assetrp       | facetoface_asset       |
      | facilitator report | facilitatorrp | facetoface_facilitator |
      | room report        | roomrp        | facetoface_rooms       |

  Scenario: Checking whether the seminar report displays all resources
    Given I log in as "admin"
    And I click on "Reports" in the totara menu
    When I follow "asset report"
    Then the "assetrp" table should contain the following:
      | Name   | Sitewide |
      | asset1 | Yes      |
      | asset2 | No       |
      | asset3 | No       |
    And I press the "back" button in the browser
    When I follow "facilitator report"
    Then the "facilitatorrp" table should contain the following:
      | Name         | Sitewide |
      | facilitator1 | Yes      |
      | facilitator2 | No       |
      | facilitator3 | No       |
    And I press the "back" button in the browser
    When I follow "room report"
    Then the "roomrp" table should contain the following:
      | Name  | Sitewide |
      | room1 | Yes      |
      | room2 | No       |
      | room3 | No       |
    And I press the "back" button in the browser

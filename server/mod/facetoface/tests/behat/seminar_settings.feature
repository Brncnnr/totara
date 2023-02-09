@mod @mod_facetoface
Feature: Disabling approval options in global settings would
  not cause the empty radio button in seminar module's settings

  Background: I am on totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | user1    | user      | lastname | email@something.com |
    And the following "courses" exist:
      | fullname  | shortname | category |
      | Course101 | C101      | 0        |

  @javascript
  Scenario: Disabled approval options in global settings are not affect to seminars module's settings
    Given I log in as "admin"
    And the following "roles" exist:
      | name     | shortname        | contextlevel |
      | Training | train            | System       |
    And I navigate to "Seminars > Global settings" in site administration
    And I set the following fields to these values:
      | Training | 1 |
    And I click on "Save changes" "button"
    # This id may need to be updated whenever a new system role is added.
    And I set the following fields to these values:
      | id_s__facetoface_approvaloptions_approval_role_17 | 1 |
    And I click on "Save changes" "button"
    And I am on "Course101" course homepage
    And I turn editing mode on
    And I add a "Seminar" to section "1" and I fill the form with:
      | Name        | Seminar 1           |
      | Training    | 1                   |
    And I navigate to "Seminars > Global settings" in site administration
    # This id may need to be updated whenever a new system role is added.
    And I set the following fields to these values:
      | id_s__facetoface_approvaloptions_approval_role_17 | 0 |
    And I click on "Save changes" "button"
    And I am on "Course101" course homepage
    And I follow "Seminar 1"
    And I follow "Edit settings"
    And I expand all fieldsets
    Then I should see "Training"
    And I set the field "No Approval" to "1"
    And I click on "Save and display" "button"
    And I follow "Edit settings"
    And I expand all fieldsets
    And I should not see "Training"

@totara @mod_approval @mod_approval_noengage @javascript @vuejs
Feature: Interacting with the application dashboard.
  Background:
    Given I am on a totara site
    And I disable the "engage_resources" advanced feature
    And I disable the "container_workspace" advanced feature
    And the following "users" exist:
      | username    | firstname | lastname | email                 |
      | applicant   | Applie    | Kaant    | applicant@example.com |
      | boss        | Boss      | Boss     | boss@example.com      |
      | sitemanager | Mana      | Djer     | manager@example.com   |
      | approver    | App       | Roover   | approver@example.com  |
    And the following job assignments exist:
      | user      | manager | idnumber  |
      | applicant | boss    | jajaja2   |
    And the following "role assigns" exist:
      | user        | role    | contextlevel | reference |
      | sitemanager | manager | System       |           |
    And the following "cohorts" exist:
      | name          | idnumber |
      | Test audience | AUD001   |
    And the following "cohort members" exist:
      | user          | cohort |
      | applicant     | AUD001 |
      | sitemanager   | AUD001 |
    And the following "workflow types" exist in "mod_approval" plugin:
      | name                  |
      | simple request |
    And the following "forms" exist in "mod_approval" plugin:
      | title     |
      | Test form |
    And the following "form versions" exist in "mod_approval" plugin:
      | form      | version | json_schema |
      | Test form | 1       | test1       |
    And the following "workflows" exist in "mod_approval" plugin:
      | name          | description               | id_number | form      | workflow_type  | type   | identifier |
      | Test workflow | test workflow description | WKF001    | Test form | simple request | cohort | AUD001     |
    And the following "workflow stages" exist in "mod_approval" plugin:
      | workflow | name       | type            |
      | WKF001   | Test stage | FORM_SUBMISSION |
    And the following "form views" exist in "mod_approval" plugin:
      | workflow_stage | field_key | required |
      | Test stage     | food      | true     |
      | Test stage     | drink     | true     |
    And I publish the "WKF001" workflow
    # Setup some applications.
    Given the following "applications" exist in "mod_approval" plugin:
      | title               | user        | workflow | assignment | creator     |
      | Other application 1 | applicant   | WKF001   | AUD001     | sitemanager |
      | Other application 2 | applicant   | WKF001   | AUD001     | sitemanager |
      | Other application 3 | applicant   | WKF001   | AUD001     | sitemanager |
      | Other application 4 | applicant   | WKF001   | AUD001     | sitemanager |
      | My application 1    | sitemanager | WKF001   | AUD001     | sitemanager |
      | My application 2    | sitemanager | WKF001   | AUD001     | sitemanager |
      | My application 3    | sitemanager | WKF001   | AUD001     | sitemanager |
      | My application 4    | sitemanager | WKF001   | AUD001     | sitemanager |
    # Assign can delete draft capability.
    Then I log in as "admin"
    And I set the following system permissions of "Authenticated User" role:
      | mod/approval:delete_draft_application_any | Allow |
    And I run the scheduled task "\mod_approval\task\role_map_regenerate_all"
    And I log out

  Scenario Outline: Applications from others and Your applications row action: Edit
    When I log in as "sitemanager"
    And I visit the Applications Dashboard
    When I follow "<tab_name>"
    # <tab_name> Applications Tab
    Then I should see the tui datatable contains:
      | Application title            |
      | <application_title_prefix> 4 |
      | <application_title_prefix> 3 |
      | <application_title_prefix> 2 |
      | <application_title_prefix> 1 |

    # Test Dropdown buttons exist
    When I open the dropdown menu in the tui datatable row with "<application_title_prefix> 3" "Application title"
    Then I should see "Edit" option in the dropdown menu

    # Test Edit link
    When I click on "Edit" option in the dropdown menu
    Then I should see "<application_title_prefix> 3" in the ".tui-mod_approval-header" "css_element"
    And I follow "Back to applications"
    Examples:
      | tab_name                 | application_title_prefix |
      | Your applications        | My application           |
      | Applications from others | Other application        |

  Scenario Outline: Applications from others and Your applications row action: Print Preview
    When I log in as "sitemanager"
    And I visit the Applications Dashboard
    When I follow "<tab_name>"
    # <tab_name> Applications Tab
    Then I should see the tui datatable contains:
      | Application title            |
      | <application_title_prefix> 4 |
      | <application_title_prefix> 3 |
      | <application_title_prefix> 2 |
      | <application_title_prefix> 1 |

    # Test Dropdown buttons exist
    When I open the dropdown menu in the tui datatable row with "<application_title_prefix> 3" "Application title"
    And I should see "Print preview" option in the dropdown menu
    # Test print preview
    When I open the dropdown menu in the tui datatable row with "<application_title_prefix> 3" "Application title"
    And I click on "Print preview" option in the dropdown menu
    When I switch to "totara_approval_workflow_application_preview" window
    Then I should see "Test Form"
    And I should see "Food & drinks"
    And I close the current window
    Then I switch to the main window

    Examples:
      | tab_name                 | application_title_prefix |
      | Your applications        | My application           |
      | Applications from others | Other application        |

  Scenario Outline: Applications from others and Your applications row action: Clone
    When I log in as "sitemanager"
    And I visit the Applications Dashboard
    When I follow "<tab_name>"
    # <tab_name> Applications Tab
    Then I should see the tui datatable contains:
      | Application title            |
      | <application_title_prefix> 4 |
      | <application_title_prefix> 3 |
      | <application_title_prefix> 2 |
      | <application_title_prefix> 1 |

    # Test Dropdown buttons exist
    When I open the dropdown menu in the tui datatable row with "<application_title_prefix> 3" "Application title"
    And I should see "Clone" option in the dropdown menu

    # Test clone
    When I open the dropdown menu in the tui datatable row with "<application_title_prefix> 3" "Application title"
    And I click on "Clone" option in the dropdown menu
    Then I should see "Application cloned successfully" in the tui success notification toast
    When I follow "Back to applications"
    And I follow "<tab_name>"
    # Applications contains new cloned application
    Then I should see the tui datatable contains:
      | Application title            |
      | <application_title_prefix> 3 |
      | <application_title_prefix> 4 |
      | <application_title_prefix> 3 |
      | <application_title_prefix> 2 |
      | <application_title_prefix> 1 |

    Examples:
      | tab_name                 | application_title_prefix |
      | Your applications        | My application           |
      | Applications from others | Other application        |

  Scenario Outline: Applications from others and Your applications row action: Delete
    When I log in as "sitemanager"
    And I visit the Applications Dashboard
    When I follow "<tab_name>"
    # <tab_name> Applications Tab
    Then I should see the tui datatable contains:
      | Application title            |
      | <application_title_prefix> 4 |
      | <application_title_prefix> 3 |
      | <application_title_prefix> 2 |
      | <application_title_prefix> 1 |

    # Test Dropdown buttons exist
    When I open the dropdown menu in the tui datatable row with "<application_title_prefix> 3" "Application title"
    And I should see "Delete" option in the dropdown menu

    # Test delete
    When I open the dropdown menu in the tui datatable row with "<application_title_prefix> 1" "Application title"
    And I click on "Delete" option in the dropdown menu
    And I confirm the tui confirmation modal
    Then I should see "Application deleted successfully" in the tui success notification toast
    And I should see the tui datatable contains:
      | Application title            |
      | <application_title_prefix> 4 |
      | <application_title_prefix> 3 |
      | <application_title_prefix> 2 |
    And I should see "Showing 3 of 3 applications"

    Examples:
      | tab_name                 | application_title_prefix |
      | Your applications        | My application           |
      | Applications from others | Other application        |

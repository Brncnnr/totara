@report @report_securityoverview
Feature: security overview

  Background:
    Given I am on a totara site
    And I log in as "admin"

  @javascript
  Scenario: Search for a status of critical on the security overview report

    Given I navigate to "Security overview" node in "Site administration > Security"
    Then  ".statusok" "css_element" should exist
    And  ".statuscritical" "css_element" should not exist

  @javascript
  Scenario: Check status correct on security overview report
    Given I navigate to "Security overview" node in "Site administration > Security"
    And I should see "OK" in the "Insecure dataroot" "table_row"
    And I should see "OK" in the "No authentication" "table_row"
    And I should see "OK" in the "Open to Google" "table_row"
    And I should see "OK" in the "Password policy" "table_row"
    And I should see "OK" in the "Email change confirmation" "table_row"
    And I should see "OK" in the "Username enumeration" "table_row"
    And I should see "OK" in the "XSS trusted users" "table_row"
    And I should see "OK" in the "Administrators" "table_row"
    And I should see "OK" in the "Default role for all users" "table_row"
    And I should see "OK" in the "Guest role" "table_row"
    And I should see "OK" in the "Frontpage role" "table_row"
    And I should see "OK" in the "HTTP only cookies" "table_row"

  # NOTE auth_none was removed in Totara 12, there is no way to test it, but we want to keep the error in case somebody copied it from older branch.

  @javascript
  Scenario: Check that Critical status is displayed when required in the security overview for issue, Guest role

    # First, check the status is OK
    Given I navigate to "Security overview" node in "Site administration > Security"
    And I should see "OK" in the "Guest role" "table_row"

    # Now change to create a Critical status
    Given I navigate to "Define roles" node in "Site administration > Permissions"
    And I click on "Guest" "link" in the "Guest" "table_row"
    And I click on "Edit" "button"
    And I set the following fields to these values:
      | moodle/site:manageblocks  | 1 |
    And I click on "Save changes" "button"

    # Check the status is shown as Critical
    Given I navigate to "Security overview" node in "Site administration > Security"
    And I should see "Critical" in the "Guest role" "table_row"

  @javascript
  Scenario: Check that Critical status is displayed when required in the security overview for issue, Frontpage role

    # First, check the status is OK
    Given I navigate to "Security overview" node in "Site administration > Security"
    And I should see "OK" in the "Frontpage role" "table_row"

    # Now change to create a Critical status
    # For this, lets changes the front page role id to guest, and also change the guest capability so that it will create the critical flag.
    Given the following config values are set as admin:
      | defaultfrontpageroleid | 6 |
    And I navigate to "Define roles" node in "Site administration > Permissions"
    And I click on "Guest" "link" in the "Guest" "table_row"
    And I click on "Edit" "button"
    And I set the following fields to these values:
      | moodle/site:manageblocks  | 1 |
    And I click on "Save changes" "button"

    # Check the status is shown as Critical
    Given I navigate to "Security overview" node in "Site administration > Security"
    And I should see "Critical" in the "Frontpage role" "table_row"

  @javascript
  Scenario: Check that the Critical status is displayed when httponly is disabled

    # To start with the security setting is on
    Given I navigate to "Security overview" node in "Site administration > Security"
    And I should see "OK" in the "HTTP only cookies" "table_row"

    # Now change to create a Serious status
    # For this I need to disable the httponly setting.
    Given the following config values are set as admin:
      | cookiehttponly | 0 |

    # Check the status is Serious now
    Given I navigate to "Security overview" node in "Site administration > Security"
    And I should see "Serious" in the "HTTP only cookies" "table_row"

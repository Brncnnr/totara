@mod @mod_scorm @javascript @totara
Feature: Known trusted SCORM pakcage whitelisting

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email               |
      | mint     | Minimal   | Trainer  | mint@example.com    |
      | maxt     | Maximal   | Trainer  | maxt@example.com    |
      | pmanager | Package   | Manager  | manager@example.com |
    And the following "roles" exist:
      | name             | shortname   | contextlevel | archetype      |
      | Min Trainer      | mintrainer  | System       | editingteacher |
      | Package Manager  | pacman      | System       |                |
    And the following "permission overrides" exist:
      | capability                      | permission | role       | contextlevel | reference |
      | mod/scorm:addnewpackage         | Prohibit   | mintrainer | System       |           |
      | mod/scorm:managetrustedpackages | Allow      | pacman     | System       |           |
    And the following "categories" exist:
      | name  | category | idnumber |
      | Cat 1 | 0        | CAT1     |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | CAT1     |
      | Course 2 | C2        | CAT1     |
    And the following "course enrolments" exist:
      | user | course | role           |
      | mint | C1     | mintrainer     |
      | maxt | C1     | editingteacher |
      | maxt | C2     | editingteacher |
    And the following "role assigns" exist:
      | user     | role   | contextlevel | reference |
      | pmanager | pacman | System       |           |

  Scenario: Restricted Editing trainer may only add a known trusted package
    Given I log in as "maxt"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "SCORM package" to section "1"
    And I set the following fields to these values:
      | Name        | Trusted user SCORM test |
      | Description | Some description        |
    And I upload "mod/scorm/tests/packages/overview_test.zip" file to "Package file" filemanager
    And I click on "Save and display" "button"
    And I should see "Number of attempts allowed: Unlimited"
    And I log out

    When I log in as "mint"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "SCORM package" to section "2"
    And I set the following fields to these values:
      | Name        | Untrusted user reused SCORM package |
      | Description | Some other description              |
    And I upload "mod/scorm/tests/packages/overview_test.zip" file to "Package file" filemanager
    And I click on "Save and display" "button"
    Then I should see "Number of attempts allowed: Unlimited"

    When I am on "Course 1" course homepage
    And I add a "SCORM package" to section "3"
    And I set the following fields to these values:
      | Name        | Untrusted user new SCORM package |
      | Description | Some other description           |
    And I upload "mod/scorm/tests/packages/singlescobasic.zip" file to "Package file" filemanager
    And I click on "Save and display" "button"
    Then I should see "You are not allowed to add new unknown SCORM packages"
    And I should not see "Number of attempts allowed: Unlimited"
    And I press "Cancel"

  Scenario: SCORM package manager may remove contethash from list of known trusted packages
    Given I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I click on "Create" "button"
    And I set the field "search_input" to "SCORM"
    And I click on "Search" "button" in the ".tw-selectRegionPanel__content" "css_element"
    And I click on "Known trusted SCORM packages" "text"
    And I press "Create and edit"
    And I switch to "Access" tab
    And I set the field "Package Manager" to "1"
    And I press "Save changes"
    And I follow "View This Report"
    And I should see "Known trusted SCORM packages"
    And I should see "0 records shown"
    And I log out

    And I log in as "maxt"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "SCORM package" to section "1"
    And I set the following fields to these values:
      | Name        | Trusted user SCORM test |
      | Description | Some description        |
    And I upload "mod/scorm/tests/packages/overview_test.zip" file to "Package file" filemanager
    And I click on "Save and display" "button"
    And I should see "Number of attempts allowed: Unlimited"
    And I am on "Course 1" course homepage
    And I add a "SCORM package" to section "2"
    And I set the following fields to these values:
      | Name        | Accidental user SCORM test |
      | Description | Some description           |
    And I upload "mod/scorm/tests/packages/singlescobasic.zip" file to "Package file" filemanager
    And I click on "Save and display" "button"
    And I should see "Number of attempts allowed: Unlimited"
    And I log out

    And I log in as "pmanager"
    And I click on "Reports" in the totara menu
    And I click on "Known trusted SCORM packages" "text"
    And I should see "Known trusted SCORM packages"
    And I should see "2 records shown"
    And "8dc8de68d846fd616731d689a43094b0048ffe3c" row "File names" column of "report_known_trusted_scorm_packages" table should contain "overview_test.zip"
    And "8dc8de68d846fd616731d689a43094b0048ffe3c" row "Usage count" column of "report_known_trusted_scorm_packages" table should contain "1"
    And "8dc8de68d846fd616731d689a43094b0048ffe3c" row "Actions" column of "report_known_trusted_scorm_packages" table should contain "Delete"
    And "cf4d4d5b5e8da875842d21de1655b68eb5879455" row "File names" column of "report_known_trusted_scorm_packages" table should contain "singlescobasic.zip"
    And "cf4d4d5b5e8da875842d21de1655b68eb5879455" row "Usage count" column of "report_known_trusted_scorm_packages" table should contain "1"
    And "cf4d4d5b5e8da875842d21de1655b68eb5879455" row "Actions" column of "report_known_trusted_scorm_packages" table should contain "Delete"

    When I click on "Delete" "link" in the "8dc8de68d846fd616731d689a43094b0048ffe3c" "table_row"
    And I should see "Do you want to remove \"8dc8de68d846fd616731d689a43094b0048ffe3c\" from the list of known trusted package content hashes?"
    And I press "Delete package trust"
    Then I should see "Known trusted SCORM packages"
    And I should see "1 record shown"
    And "cf4d4d5b5e8da875842d21de1655b68eb5879455" row "File names" column of "report_known_trusted_scorm_packages" table should contain "singlescobasic.zip"
    And "cf4d4d5b5e8da875842d21de1655b68eb5879455" row "Usage count" column of "report_known_trusted_scorm_packages" table should contain "1"
    And "cf4d4d5b5e8da875842d21de1655b68eb5879455" row "Actions" column of "report_known_trusted_scorm_packages" table should contain "Delete"

  Scenario: Restricted Editing trainer may update SCORM activity with unknown package
    Given I log in as "maxt"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "SCORM package" to section "1"
    And I set the following fields to these values:
      | Name        | Trusted user SCORM test |
      | Description | Some description        |
    And I upload "mod/scorm/tests/packages/overview_test.zip" file to "Package file" filemanager
    And I click on "Save and display" "button"
    And I should see "Number of attempts allowed: Unlimited"
    And I log out

    And I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I click on "Create" "button"
    And I set the field "search_input" to "SCORM"
    And I click on "Search" "button" in the ".tw-selectRegionPanel__content" "css_element"
    And I click on "Known trusted SCORM packages" "text"
    And I press "Create and view"
    And I should see "Known trusted SCORM packages"
    And I should see "1 record shown"
    And I click on "Delete" "link" in the "8dc8de68d846fd616731d689a43094b0048ffe3c" "table_row"
    And I press "Delete package trust"
    And I should see "Known trusted SCORM packages"
    And I should see "0 records shown"
    And I log out

    When I log in as "mint"
    And I am on "Course 1" course homepage with editing mode on
    And I follow "Trusted user SCORM test"
    And I follow "Edit settings"
    And I press "Save and display"
    Then I should see "Number of attempts allowed: Unlimited"

    When I am on "Course 1" course homepage
    And I add a "SCORM package" to section "2"
    And I set the following fields to these values:
      | Name        | Untrusted user reused SCORM package |
      | Description | Some other description              |
    And I upload "mod/scorm/tests/packages/overview_test.zip" file to "Package file" filemanager
    And I click on "Save and display" "button"
    Then I should see "You are not allowed to add new unknown SCORM packages"
    And I should not see "Number of attempts allowed: Unlimited"
    And I press "Cancel"

  Scenario: SCORM package report shows all packages
    Given I log in as "maxt"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "SCORM package" to section "1"
    And I set the following fields to these values:
      | Name        | First SCORM      |
      | Description | Some description |
    And I upload "mod/scorm/tests/packages/overview_test.zip" file to "Package file" filemanager
    And I click on "Save and display" "button"
    And I should see "Number of attempts allowed: Unlimited"

    And I am on "Course 2" course homepage
    And I add a "SCORM package" to section "1"
    And I set the following fields to these values:
      | Name        | Second SCORM     |
      | Description | Some description |
    And I upload "mod/scorm/tests/packages/singlescobasic.zip" file to "Package file" filemanager
    And I click on "Save and display" "button"
    And I should see "Number of attempts allowed: Unlimited"
    And I log out

    And I log in as "mint"
    And I am on "Course 1" course homepage with editing mode on
    And I add a "SCORM package" to section "2"
    And I set the following fields to these values:
      | Name        | Untrusted user reused SCORM package |
      | Description | Some other description              |
    And I upload "mod/scorm/tests/packages/overview_test.zip" file to "Package file" filemanager
    And I click on "Save and display" "button"
    And I should see "Number of attempts allowed: Unlimited"
    And I log out

    And I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I click on "Create" "button"
    And I set the field "search_input" to "SCORM"
    And I click on "Search" "button" in the ".tw-selectRegionPanel__content" "css_element"
    And I click on "Known trusted SCORM packages" "text"
    And I press "Create and view"
    And I should see "Known trusted SCORM packages"
    And I should see "2 records shown"
    And I click on "Delete" "link" in the "8dc8de68d846fd616731d689a43094b0048ffe3c" "table_row"
    And I press "Delete package trust"
    And I should see "Known trusted SCORM packages"
    And I should see "1 record shown"
    And I log out

    When I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I click on "Create" "button"
    And I set the field "search_input" to "SCORM"
    And I click on "Search" "button" in the ".tw-selectRegionPanel__content" "css_element"
    And I click on "Local SCORM packages" "text"
    And I press "Create and view"
    Then I should see "3 records shown"
    And "First SCORM" row "Course Name" column of "report_local_scorm_packages" table should contain "Course 1"
    And "First SCORM" row "Package file" column of "report_local_scorm_packages" table should contain "overview_test.zip"
    And "First SCORM" row "Package content hash" column of "report_local_scorm_packages" table should contain "8dc8de68d846fd616731d689a43094b0048ffe3c"
    And "First SCORM" row "Trusted package" column of "report_local_scorm_packages" table should contain "No"
    And "Second SCORM" row "Course Name" column of "report_local_scorm_packages" table should contain "Course 2"
    And "Second SCORM" row "Package file" column of "report_local_scorm_packages" table should contain "singlescobasic.zip"
    And "Second SCORM" row "Package content hash" column of "report_local_scorm_packages" table should contain "cf4d4d5b5e8da875842d21de1655b68eb5879455"
    And "Second SCORM" row "Trusted package" column of "report_local_scorm_packages" table should contain "Yes"
    And "Untrusted user reused SCORM package" row "Course Name" column of "report_local_scorm_packages" table should contain "Course 1"
    And "Untrusted user reused SCORM package" row "Package file" column of "report_local_scorm_packages" table should contain "overview_test.zip"
    And "Untrusted user reused SCORM package" row "Package content hash" column of "report_local_scorm_packages" table should contain "8dc8de68d846fd616731d689a43094b0048ffe3c"
    And "Untrusted user reused SCORM package" row "Trusted package" column of "report_local_scorm_packages" table should contain "No"

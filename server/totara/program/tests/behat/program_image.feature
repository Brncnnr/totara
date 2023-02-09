@totara @totara_program @_file_upload @javascript
Feature: Program images should allow but ignore directories
  As a user with the permissions for managing a program
  I should be able to upload program images and directories

  Background:
    Given I am on a totara site
    And the following "programs" exist in "totara_program" plugin:
      | fullname    | shortname | idnumber |
      | Program One | prog1     | prog1    |

  Scenario: Saving a directory in the Program image field
    Given I log in as "admin"
    And I am on "Program One" program homepage
    And I press "Edit program details"
    Then I should see image with alt text "Image for the program"
    # Check that saving nothing but a directory doesn't break the display.
    When I switch to "Details" tab
    Then ".fp-file" "css_element" should not exist
    When I create "DirBreakage" folder in "Image" filemanager
    And I press "Save changes"
    Then I should see "Program details saved successfully"
    And I should see image with alt text "Image for the program"
    # Check that saving a directory and a file doesn't break the display.
    When I switch to "Details" tab
    Then ".fp-file" "css_element" should not exist
    When I create "DirBreakage" folder in "Image" filemanager
    And I upload "totara/program/tests/fixtures/leaves-blue.png" file to "Image" filemanager
    And I press "Save changes"
    Then I should see "Program details saved successfully"
    And I should see image with alt text "Image for the program"
    And "img[src*='leaves-blue.png']" "css_element" should exist
    When I switch to "Details" tab
    And ".fp-file" "css_element" should exist

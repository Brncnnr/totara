@totara @perform @mod_perform @javascript @vuejs
Feature: Make sure the correct navigation breadcrumbs and blocks are shown.

  Scenario: Admin management pages show correct navigation options
    Given the following "activities" exist in "mod_perform" plugin:
      | activity_name | create_track |
      | activity_one  | true         |
    And I log in as "admin"

    # Manage performance activities page
    When I navigate to the manage perform activities page
    Then I should see "Manage performance activities"
    And "#settingsnav" "css_element" should not exist

    # Edit activity page
    When I click on "activity_one" "link"
    Then I should see "activity_one"
    And I should not see "Performance activity administration" in the "#settingsnav" "css_element"
    And I should not see "Course administration" in the "#settingsnav" "css_element"
    And I should not see "Courses" in the ".breadcrumb-container" "css_element"

    # Subject instances embedded report
    When I click on "Back to all performance activities" "link"
    And I click on "Participation reporting" "link"
    Then I should see "There are no records that match your selected criteria"
    And I should not see "Performance activity administration" in the "#settingsnav" "css_element"
    And I should not see "Course administration" in the "#settingsnav" "css_element"
    And I should not see "Courses" in the ".breadcrumb-container" "css_element"

  Scenario: User facing pages show correct navigation option
    Given the following "users" exist:
      | username | firstname | lastname |
      | user     | User      | One      |
    And the following "subject instances" exist in "mod_perform" plugin:
      | activity_name | subject_username | subject_is_participating | other_participant_username |
      | activity_one  | user             | true                     | admin                      |
    And I log in as "admin"

    # Outstanding activities list page
    When I navigate to the outstanding perform activities list page
    Then I should see "Performance activities"
    And I should not see "Category:" in the "#settingsnav" "css_element"

    # Single activity page
    When I click on "As Manager" "link"
    And I click on "activity_one" "link"
    Then I should see "activity_one"
    And I should see perform activity relationship to user "Manager"
    And I should not see "Performance activity administration" in the "#settingsnav" "css_element"
    And I should not see "Course administration" in the "#settingsnav" "css_element"
    And I should not see "Courses" in the ".breadcrumb-container" "css_element"

@totara @totara_core @completion @javascript
Feature: Test that completion criteria is locked once a user is enrolled

  Background:
    Given I am on a totara site

    And the following "users" exist:
    | username | firstname | lastname | email          |
    | user1    | user      | one      | u1@example.com |

    And the following "courses" exist:
    | fullname | shortname | summary          | format | enablecompletion |
    | Course 1 | C1        | Course summary 1 | topics | 1                |

    And the following "activities" exist:
    | activity | name        | intro       | course | idnumber | completion |
    | book     | Test book 1 | Test book 1 | C1     | book1    | 1          |
    | book     | Test book 2 | Test book 2 | C1     | book1    | 1          |

    # Set the books as completion criteria for the course
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I navigate to "Course completion" node in "Course administration"
    And I click on "Condition: Activity completion" "link"
    And I click on "Test book 1" "checkbox"
    And I click on "Test book 2" "checkbox"
    And I press "Save changes"

    And the following "course enrolments" exist:
    | user  | course | role    |
    | user1 | C1     | student |

    And I log out

  Scenario: Complete the activity and confirm that the activity completion options are blocked
    # Log in as user and complete the activities
    When I log in as "user1"
    And I am on "Course 1" course homepage
    And I wait until the page is ready
    And I click on "Manual completion of Test book 1" "checkbox"
    And I click on "Manual completion of Test book 2" "checkbox"
    And I log out

    # Log in as admin and confirm that the activity completion condition is disabled
    When I log in as "admin"
    And I am on "Course 1" course homepage
    And I wait until the page is ready
    And I navigate to "Course completion" node in "Course administration"
    And I click on "Condition: Activity completion" "link"
    Then the "Test book 1" "checkbox" should be disabled
    And the "Test book 2" "checkbox" should be disabled
@totara @perform @totara_competency @javascript @vuejs
Feature: Competency profile detail page confirm linked course progress summary
  As a user
  I want to confirm my progress in a linked course

  Background:
    Given the following "users" exist:
      | username  | firstname | lastname |
      | user1     | user      | one      |

    And a competency scale called "scale" exists with the following values:
      | name             | idnumber     | proficient | default | sortorder |
      | Super Competent  | super        | 1          | 0       | 1         |
      | Barely Competent | barely       | 0          | 0       | 2         |
      | Incompetent      | incompetent  | 0          | 1       | 3         |

    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | course1   | 1                |

    And the following "competency" frameworks exist:
      | fullname             | idnumber | scale |
      | Competency Framework | comp_fw  | scale |

    And the following "competency" hierarchy exists:
      | framework | idnumber | fullname     | assignavailability |
      | comp_fw   | comp1    | Competency 1 | 1                  |

    And the following "linked courses" exist in "totara_competency" plugin:
      | competency | course  | mandatory |
      | comp1      | course1 | 0         |

    And the following "manual pathways" exist in "totara_competency" plugin:
      | competency | roles          |
      | comp1      | self,appraiser |

    And the following "achievement configuration" exist in "totara_competency" plugin:
      | competency | aggregation_type |
      | comp1      | first            |

    And the following "linkedcourses" exist in "totara_criteria" plugin:
      | idnumber      | competency | number_required |
      | linkedcourses | comp1      | 0               |

    And the following "criteria group pathways" exist in "totara_competency" plugin:
      | competency | scale_value | criteria      | sortorder |
      | comp1      | super       | linkedcourses | 1         |

    And the following "assignments" exist in "totara_competency" plugin:
      | competency | user_group_type | user_group | type |
      | comp1      | user            | user1      | self |

    And I run the scheduled task "totara_competency\task\expand_assignments_task"

  Scenario: I should see 'Not tracked' when completion is not enabled for course
    Given I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Edit settings" node in "Course administration"
    And I expand all fieldsets
    And I set the field "Enable completion tracking" to "No"
    And I press "Save and display"
    And I log out

    When I log in as "user1"
    And I navigate to the competency profile details page for the "Competency 1" competency
    Then I should see "1" rows in the tui datatable
    And I should see the tui datatable contains:
      | Courses  | Progress    |
      | Course 1 | Not tracked |

  Scenario: I should see 'No criteria' when there are no completion criteria specified for the course
    Given I log in as "user1"
    And I navigate to the competency profile details page for the "Competency 1" competency
    Then I should see "1" rows in the tui datatable
    And I should see the tui datatable contains:
      | Courses  | Progress    |
      | Course 1 | No criteria |

  Scenario: I should see 'Not yet started' when there are completion criteria specified for the course
    Given the following "activities" exist:
      | activity | name        | intro       | course  | idnumber | completion |
      | book     | Test book 1 | Test book 1 | course1 | book1    | 1          |
      | book     | Test book 2 | Test book 2 | course1 | book1    | 1          |
    And I log in as "admin"
    And I am on "Course 1" course homepage with editing mode on
    And I navigate to "Course completion" node in "Course administration"
    And I click on "Condition: Activity completion" "link"
    And I click on "Test book 1" "checkbox"
    And I click on "Test book 2" "checkbox"
    And I press "Save changes"
    And I log out

    When I log in as "user1"
    And I navigate to the competency profile details page for the "Competency 1" competency
    Then I should see "1" rows in the tui datatable
    And I should see the tui datatable contains:
      | Courses  | Progress        |
      | Course 1 | Not yet started |

  Scenario: I should see progress percentage when there are completion criteria and user completed them
    Given the following "activities" exist:
      | activity | name        | intro       | course  | idnumber | completion |
      | book     | Test book 1 | Test book 1 | course1 | book1    | 1          |
      | book     | Test book 2 | Test book 2 | course1 | book1    | 1          |
    And the following "course enrolments" exist:
      | user  | course  | role    |
      | user1 | course1 | student |
    And I log in as "admin"
    And I am on "Course 1" course homepage
    And I navigate to "Course completion" node in "Course administration"
    And I click on "Condition: Activity completion" "link"
    And I click on "Test book 1" "checkbox"
    And I click on "Test book 2" "checkbox"
    And I press "Save changes"
    And I log out

    When I log in as "user1"
    And I am on "Course 1" course homepage
    And I wait until the page is ready
    And I click on "Test book 1" "checkbox"
    And I click on "Test book 2" "checkbox"
    And I navigate to the competency profile details page for the "Competency 1" competency
    Then I should see "1" rows in the tui datatable
    And I should see the tui datatable contains:
      | Courses  | Progress |
      | Course 1 | 100%     |
@mod @mod_facetoface @javascript @core_backup @core_grades @_file_upload
Feature: Backup and restore seminar activity completion
  Background:
    Given the following config values are set as admin:
      | grade_decimalpoints | 2 |
    And the following "courses" exist:
      | fullname | shortname | category | enablecompletion |
      | course1  | course1   | 0        | 1                |
    And I log in as "admin"

  Scenario: Backup seminars, restore to new course and verify activity completion fields
    # NOTE: Do NOT set gradepass, completion and completionpass here; The data generator cannot handle them.
    Given the following "seminars" exist in "mod_facetoface" plugin:
      | name      | course  |
      | Seminar 1 | course1 |
      | Seminar 2 | course1 |
      | Seminar 3 | course1 |
      | Seminar 4 | course1 |
      | Seminar 5 | course1 |

    And I am on "course1" course homepage with editing mode on
    And I click on ".toggle-display" "css_element" in the "Seminar 1" activity
    And I click on "Edit settings" "link" in the "Seminar 1" activity
    And I set the following fields to these values:
      | Passing grade | 0.03 |
      | Completion tracking | Do not indicate activity completion |
      | Require grade | No |
      | completionstatusrequired[90]  | 0 |
      | completionstatusrequired[100] | 0 |
    And I click on "Save and return to course" "button"

    And I click on ".toggle-display" "css_element" in the "Seminar 2" activity
    And I click on "Edit settings" "link" in the "Seminar 2" activity
    And I set the following fields to these values:
      | Passing grade | 3.14 |
      | Completion tracking | Learners can manually mark the activity as completed |
      | Require grade | No |
      | completionstatusrequired[90]  | 0 |
      | completionstatusrequired[100] | 0 |
    And I click on "Save and return to course" "button"

    And I click on ".toggle-display" "css_element" in the "Seminar 3" activity
    And I click on "Edit settings" "link" in the "Seminar 3" activity
    And I set the following fields to these values:
      | Passing grade | 27.18 |
      | Completion tracking | Show activity as complete when conditions are met |
      | Require grade | Yes, any grade (0–100) |
      | completionstatusrequired[90]  | 0 |
      | completionstatusrequired[100] | 0 |
    And I click on "Save and return to course" "button"

    And I click on ".toggle-display" "css_element" in the "Seminar 4" activity
    And I click on "Edit settings" "link" in the "Seminar 4" activity
    And I set the following fields to these values:
      | Passing grade | 6.02 |
      | Completion tracking | Show activity as complete when conditions are met |
      | Require grade | Yes, passing grade |
      | completionstatusrequired[90]  | 0 |
      | completionstatusrequired[100] | 0 |
    And I click on "Save and return to course" "button"

    And I click on ".toggle-display" "css_element" in the "Seminar 5" activity
    And I click on "Edit settings" "link" in the "Seminar 5" activity
    And I set the following fields to these values:
      | Manual event grading | 1 |
      | Passing grade        | 99.99 |
      | Completion tracking  | Show activity as complete when conditions are met |
      | Require grade        | Yes, passing grade |
      | completionstatusrequired[90]  | 1 |
      | completionstatusrequired[100] | 1 |
    And I click on "Save and return to course" "button"

    # Backup
    And I navigate to "Backup" node in "Course administration"
    And I click on "Jump to final step" "button"
    And I click on "Continue" "button"

    # Restore
    And I click on "Restore" "button" in the "backup" "table"
    And I click on "Next" "button"
    And I click on "Miscellaneous" "text" in the "#region-main" "css_element"
    And I click on "Next" "button"
    And I click on "Next" "button"
    And I set the following fields to these values:
      | Course name | course2 |
      | Course short name | course2 |
    And I click on "Next" "button"
    And I click on "Perform restore" "button"
    And I click on "Continue" "button"

    And I click on ".toggle-display" "css_element" in the "Seminar 1" activity
    And I click on "Edit settings" "link" in the "Seminar 1" activity
    Then the following fields match these values:
      | Passing grade | 0.03 |
      | Completion tracking | Do not indicate activity completion |
      | Require grade | No |
      | completionstatusrequired[90]  | 0 |
      | completionstatusrequired[100] | 0 |

    And I am on "course2" course homepage
    And I click on ".toggle-display" "css_element" in the "Seminar 2" activity
    And I click on "Edit settings" "link" in the "Seminar 2" activity
    Then the following fields match these values:
      | Passing grade | 3.14 |
      | Completion tracking | Learners can manually mark the activity as completed |
      | Require grade | No |
      | completionstatusrequired[90]  | 0 |
      | completionstatusrequired[100] | 0 |

    And I am on "course2" course homepage
    And I click on ".toggle-display" "css_element" in the "Seminar 3" activity
    And I click on "Edit settings" "link" in the "Seminar 3" activity
    Then the following fields match these values:
      | Passing grade | 27.18 |
      | Completion tracking | Show activity as complete when conditions are met |
      | Require grade | Yes, any grade (0–100) |
      | completionstatusrequired[90]  | 0 |
      | completionstatusrequired[100] | 0 |

    And I am on "course2" course homepage
    And I click on ".toggle-display" "css_element" in the "Seminar 4" activity
    And I click on "Edit settings" "link" in the "Seminar 4" activity
    Then the following fields match these values:
      | Passing grade | 6.02 |
      | Completion tracking | Show activity as complete when conditions are met |
      | Require grade | Yes, passing grade |
      | completionstatusrequired[90]  | 0 |
      | completionstatusrequired[100] | 0 |

    And I am on "course2" course homepage
    And I click on ".toggle-display" "css_element" in the "Seminar 5" activity
    And I click on "Edit settings" "link" in the "Seminar 5" activity
    Then the following fields match these values:
      | Manual event grading | 1 |
      | Passing grade        | 99.99 |
      | Completion tracking  | Show activity as complete when conditions are met |
      | Require grade        | Yes, passing grade |
      | completionstatusrequired[90]  | 1 |
      | completionstatusrequired[100] | 1 |

  Scenario: Restore seminars from old mbz and verify activity completion fields
    Given I am on "course1" course homepage with editing mode on
    And I navigate to "Restore" node in "Course administration"
    And I press "Manage backup files"
    And I upload "mod/facetoface/tests/fixtures/passing-grade-test.mbz" file to "Files" filemanager
    And I press "Save changes"
    When I restore "passing-grade-test.mbz" backup into a new course using this options:
      | Schema | Course name | course2 |
    Then I should not see "Learners can manually mark this item complete: Seminar 1"
    And I should not see "The system marks this item complete according to conditions: Seminar 1"
    And I should see "Learners can manually mark this item complete: Seminar 2"
    And I should see "The system marks this item complete according to conditions: Seminar 3"
    And I should see "The system marks this item complete according to conditions: Seminar 4"
    And I should see "The system marks this item complete according to conditions: Seminar 5"

    And I click on ".toggle-display" "css_element" in the "Seminar 1 - None" activity
    And I click on "Edit settings" "link" in the "Seminar 1 - None" activity
    Then the following fields match these values:
      | Passing grade | 0.00 |
      | Completion tracking | Do not indicate activity completion |
      | Require grade | No |

    And I am on "course2" course homepage
    And I click on ".toggle-display" "css_element" in the "Seminar 2 - Manual" activity
    And I click on "Edit settings" "link" in the "Seminar 2 - Manual" activity
    Then the following fields match these values:
      | Passing grade | 0.00 |
      | Completion tracking | Learners can manually mark the activity as completed |
      | Require grade | No |

    And I am on "course2" course homepage
    And I click on ".toggle-display" "css_element" in the "Seminar 3 - Require grade" activity
    And I click on "Edit settings" "link" in the "Seminar 3 - Require grade" activity
    Then the following fields match these values:
      | Passing grade | 0.00 |
      | Completion tracking | Show activity as complete when conditions are met |
      | Require grade | Yes, any grade (0–100) |

    And I am on "course2" course homepage
    And I click on ".toggle-display" "css_element" in the "Seminar 4 - Require status" activity
    And I click on "Edit settings" "link" in the "Seminar 4 - Require status" activity
    Then the following fields match these values:
      | Passing grade | 0.00 |
      | Completion tracking | Show activity as complete when conditions are met |
      | Require grade | No |

    And I am on "course2" course homepage
    And I click on ".toggle-display" "css_element" in the "Seminar 5 - Require grade and status" activity
    And I click on "Edit settings" "link" in the "Seminar 5 - Require grade and status" activity
    Then the following fields match these values:
      | Passing grade | 0.00 |
      | Completion tracking | Show activity as complete when conditions are met |
      | Require grade | Yes, any grade (0–100) |

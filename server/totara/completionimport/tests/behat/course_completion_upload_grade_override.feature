@totara @totara_completion_upload @totara_courseprogressbar @javascript @_file_upload
Feature: Verify grade unit and override option when uploading course completion

  Background:
    Given the "mylearning" user profile block exists
    And I am on a totara site
    And the following "users" exist:
      | username | firstname  | lastname  | email                |
      | learner1 | Bob1       | Learner1  | learner1@example.com |
    And the following "courses" exist:
      | fullname | shortname | idnumber |
      | Course 1 | C1        | 1        |
    And the following "activities" exist:
      | activity | name | course | idnumber |
      | assign   | Ass1 | C1     | Ass1     |
      | assign   | Ass2 | C1     | Ass2     |
      | assign   | Ass3 | C1     | Ass3     |
    And the following "standard_report" exist in "totara_reportbuilder" plugin:
      | fullname    | shortname   | source                | accessmode |
      | CCIH report | ccih_report | course_completion_all | 0          |
    And I log in as "admin"
    And I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "report-name" to "Record of Learning: Courses"
    And I press "id_submitgroupstandard_addfilter"
    And I click on "Record of Learning: Courses" "link"
    And I switch to "Columns" tab
    And I add the "Grade" column to the report
    And I press "Save changes"

  Scenario Outline: Upload course completion with grade format and no override
    When I navigate to "Upload course records" node in "Site administration > Courses > Upload completion records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_completion_3a.csv" file to "CSV file to upload" filemanager
    And I set the following fields to these values:
      | Upload course Create evidence  | 1            |
      | Upload course CSV Grade format | <grade_unit> |
      | Upload course Override current course completions | Never |
    And I click on "Save" "button" in the ".totara_completionimport__uploadcourse_form" "css_element"
    Then I should see "Course completion file successfully imported."
    And I should see "2 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_course_completions_task"

    When I follow "Course import report"
    Then the "completionimport_course" table should contain the following:
      | Line number | Course Shortname | Completion date | Grade                |
      | 2           | C1               | 2011-11-11      | <grade_imported_1st> |
      | 3           | evidence_a       | 2012-12-12      | 90                   |

    When I navigate to "Upload course records" node in "Site administration > Courses > Upload completion records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_completion_3b.csv" file to "CSV file to upload" filemanager
    And I set the following fields to these values:
      | Upload course Create evidence  | 1            |
      | Upload course CSV Grade format | <grade_unit> |
      | Upload course Override current course completions | Never |
    And I click on "Save" "button" in the ".totara_completionimport__uploadcourse_form" "css_element"
    Then I should see "Course completion file successfully imported."
    And I should see "2 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_course_completions_task"

    When I follow "Course import report"
    Then the "completionimport_course" table should contain the following:
      | Line number | Course Shortname | Completion date | Grade                |
      | 2           | C1               | 2016-06-06      | <grade_imported_2nd> |
      | 3           | evidence_b       | 2018-08-08      | 40                   |

    When I navigate to "Upload course records" node in "Site administration > Courses > Upload completion records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_completion_3c.csv" file to "CSV file to upload" filemanager
    And I set the following fields to these values:
      | Upload course Create evidence  | 1            |
      | Upload course CSV Grade format | <grade_unit> |
      | Upload course Override current course completions | Never |
    And I click on "Save" "button" in the ".totara_completionimport__uploadcourse_form" "css_element"
    Then I should see "Course completion file successfully imported."
    And I should see "2 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_course_completions_task"

    When I follow "Course import report"
    Then the "completionimport_course" table should contain the following:
      | Line number | Course Shortname | Completion date | Grade                |
      | 2           | C1               | 2003-03-03      | <grade_imported_3rd> |
      | 3           | evidence_c       | 2004-04-04      | 60                   |

    When I navigate to "Upload course records" node in "Site administration > Courses > Upload completion records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_completion_3d.csv" file to "CSV file to upload" filemanager
    And I set the following fields to these values:
      | Upload course Create evidence  | 1            |
      | Upload course CSV Grade format | <grade_unit> |
      | Upload course Override current course completions | Never |
    And I click on "Save" "button" in the ".totara_completionimport__uploadcourse_form" "css_element"
    Then I should see "Course completion file successfully imported."
    And I should see "2 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_course_completions_task"

    When I follow "Course import report"
    Then the "completionimport_course" table should contain the following:
      | Line number | Course Shortname | Completion date | Grade |
      | 3           | evidence_d       | 2004-04-04      | 20    |

    When I navigate to "Manage users" node in "Site administration > Users"
    And I follow "Bob1 Learner1"
    And I click on "Record of Learning" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    Then the "plan_courses" table should contain the following:
      | Course Title | Previous Completions | Progress | Grade       |
      | Course 1     | 2                    | 100%     | <grade_rol> |

    And I click on "2" "link" in the "td.course_completion_history_course_completion_previous_completion" "css_element"
    Then the "plan_courses_completion_history" table should contain the following:
      | Date last completed | Course Title | Grade at time of completion |
      | 6 Jun 2016          | Course 1     | <grade_history_2nd>         |
      | 3 Mar 2003          | Course 1     | <grade_history_3rd>         |

    When I follow "Other Evidence"
    Then the "plan_evidence" table should contain the following:
      | Name                          |
      | Completed course : evidence_a |
      | Completed course : evidence_b |
      | Completed course : evidence_c |
      | Completed course : evidence_d |

    When I click on "Completed course : evidence_a" "link"
    Then I should see "Completed course : evidence_a"
    And I should see "evidence_a" in the "Course short name" evidence item field
    And I should see "notacourse" in the "Course ID number" evidence item field
    And I should see "12 December 2012" in the "Completion date" evidence item field
    And I should see "90" in the "Grade" evidence item field

    When I press the "back" button in the browser
    And I click on "Completed course : evidence_b" "link"
    Then I should see "Completed course : evidence_b"
    And I should see "evidence_b" in the "Course short name" evidence item field
    And I should see "notacourse" in the "Course ID number" evidence item field
    And I should see "8 August 2018" in the "Completion date" evidence item field
    And I should see "40" in the "Grade" evidence item field

    When I press the "back" button in the browser
    And I click on "Completed course : evidence_c" "link"
    Then I should see "Completed course : evidence_c"
    And I should see "evidence_c" in the "Course short name" evidence item field
    And I should see "notacourse" in the "Course ID number" evidence item field
    And I should see "4 April 2004" in the "Completion date" evidence item field
    And I should see "60" in the "Grade" evidence item field

    When I press the "back" button in the browser
    And I click on "Completed course : evidence_d" "link"
    Then I should see "Completed course : evidence_d"
    And I should see "evidence_d" in the "Course short name" evidence item field
    And I should see "notacourse" in the "Course ID number" evidence item field
    And I should see "4 April 2004" in the "Completion date" evidence item field
    And I should see "20" in the "Grade" evidence item field

    When I navigate to my "CCIH report" report
    Then the "ccih_report" table should contain the following:
      | Date completed | Grade at time of completion | Is current record |
      | 11 Nov 2011    | <grade_rol>                 | Yes               |
      | 6 Jun 2016     | <grade_history_2nd>         | No                |
      | 3 Mar 2003     | <grade_history_3rd>         | No                |

    Examples:
      | grade_unit | grade_imported_1st | grade_imported_2nd | grade_imported_3rd | grade_rol | grade_history_2nd | grade_history_3rd |
      | Percentage | 240                | 150                | 210                | 80.0%     | 50.0%             | 70.0%             |
      | Real       | 80                 | 50                 | 70                 | 26.7%     | 16.7%             | 23.3%             |

  Scenario Outline: Upload course completion with grade format and always override
    When I navigate to "Upload course records" node in "Site administration > Courses > Upload completion records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_completion_3a.csv" file to "CSV file to upload" filemanager
    And I set the following fields to these values:
      | Upload course Create evidence  | 1            |
      | Upload course CSV Grade format | <grade_unit> |
      | Upload course Override current course completions | Always |
    And I click on "Save" "button" in the ".totara_completionimport__uploadcourse_form" "css_element"
    Then I should see "Course completion file successfully imported."
    And I should see "2 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_course_completions_task"

    When I follow "Course import report"
    Then the "completionimport_course" table should contain the following:
      | Line number | Course Shortname | Completion date | Grade                |
      | 2           | C1               | 2011-11-11      | <grade_imported_1st> |
      | 3           | evidence_a       | 2012-12-12      | 90                   |

    When I navigate to "Upload course records" node in "Site administration > Courses > Upload completion records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_completion_3b.csv" file to "CSV file to upload" filemanager
    And I set the following fields to these values:
      | Upload course Create evidence  | 1            |
      | Upload course CSV Grade format | <grade_unit> |
      | Upload course Override current course completions | Always |
    And I click on "Save" "button" in the ".totara_completionimport__uploadcourse_form" "css_element"
    Then I should see "Course completion file successfully imported."
    And I should see "2 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_course_completions_task"

    When I follow "Course import report"
    Then the "completionimport_course" table should contain the following:
      | Line number | Course Shortname | Completion date | Grade                |
      | 2           | C1               | 2016-06-06      | <grade_imported_2nd> |
      | 3           | evidence_b       | 2018-08-08      | 40                   |

    When I navigate to "Upload course records" node in "Site administration > Courses > Upload completion records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_completion_3c.csv" file to "CSV file to upload" filemanager
    And I set the following fields to these values:
      | Upload course Create evidence  | 1            |
      | Upload course CSV Grade format | <grade_unit> |
      | Upload course Override current course completions | Always |
    And I click on "Save" "button" in the ".totara_completionimport__uploadcourse_form" "css_element"
    Then I should see "Course completion file successfully imported."
    And I should see "2 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_course_completions_task"

    When I follow "Course import report"
    Then the "completionimport_course" table should contain the following:
      | Line number | Course Shortname | Completion date | Grade                |
      | 2           | C1               | 2003-03-03      | <grade_imported_3rd> |
      | 3           | evidence_c       | 2004-04-04      | 60                   |

    When I navigate to "Upload course records" node in "Site administration > Courses > Upload completion records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_completion_3d.csv" file to "CSV file to upload" filemanager
    And I set the following fields to these values:
      | Upload course Create evidence  | 1            |
      | Upload course CSV Grade format | <grade_unit> |
      | Upload course Override current course completions | Always |
    And I click on "Save" "button" in the ".totara_completionimport__uploadcourse_form" "css_element"
    Then I should see "Course completion file successfully imported."
    And I should see "2 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_course_completions_task"

    When I follow "Course import report"
    Then the "completionimport_course" table should contain the following:
      | Line number | Course Shortname | Completion date | Grade                |
      | 2           | C1               | 2003-03-03      | <grade_imported_4th> |
      | 3           | evidence_d       | 2004-04-04      | 20                   |

    When I navigate to "Manage users" node in "Site administration > Users"
    And I follow "Bob1 Learner1"
    And I click on "Record of Learning" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    Then the "plan_courses" table should contain the following:
      | Course Title | Previous Completions | Progress | Grade       |
      | Course 1     |                      | 100%     | <grade_rol> |
    And the "plan_courses" table should not contain the following:
      | Course Title | Previous Completions | Previous Completions | Previous Completions | Previous Completions |
      | Course 1     | 1                    | 2                    | 3                    | 4                    |

    When I follow "Other Evidence"
    Then the "plan_evidence" table should contain the following:
      | Name                          |
      | Completed course : evidence_a |
      | Completed course : evidence_b |
      | Completed course : evidence_c |
      | Completed course : evidence_d |
    # Repetitive steps omitted

    When I navigate to my "CCIH report" report
    Then the "ccih_report" table should contain the following:
      | Date completed | Grade at time of completion | Is current record |
      | 3 Mar 2003     | <grade_rol>                 | Yes               |

    Examples:
      | grade_unit | grade_imported_1st | grade_imported_2nd | grade_imported_3rd | grade_imported_4th | grade_rol |
      | Percentage | 240                | 150                | 210                | 90                 | 30.0%     |
      | Real       | 80                 | 50                 | 70                 | 30                 | 10.0%     |

  Scenario Outline: Upload course completion with grade format and override if new
    When I navigate to "Upload course records" node in "Site administration > Courses > Upload completion records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_completion_3a.csv" file to "CSV file to upload" filemanager
    And I set the following fields to these values:
      | Upload course Create evidence  | 1            |
      | Upload course CSV Grade format | <grade_unit> |
      | Upload course Override current course completions | Never |
    And I click on "Save" "button" in the ".totara_completionimport__uploadcourse_form" "css_element"
    Then I should see "Course completion file successfully imported."
    And I should see "2 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_course_completions_task"

    When I follow "Course import report"
    Then the "completionimport_course" table should contain the following:
      | Line number | Course Shortname | Completion date | Grade                |
      | 2           | C1               | 2011-11-11      | <grade_imported_1st> |
      | 3           | evidence_a       | 2012-12-12      | 90                   |

    When I navigate to "Upload course records" node in "Site administration > Courses > Upload completion records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_completion_3b.csv" file to "CSV file to upload" filemanager
    And I set the following fields to these values:
      | Upload course Create evidence  | 1            |
      | Upload course CSV Grade format | <grade_unit> |
      | Upload course Override current course completions | If more recent |
    And I click on "Save" "button" in the ".totara_completionimport__uploadcourse_form" "css_element"
    Then I should see "Course completion file successfully imported."
    And I should see "2 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_course_completions_task"

    When I follow "Course import report"
    Then the "completionimport_course" table should contain the following:
      | Line number | Course Shortname | Completion date | Grade                |
      | 2           | C1               | 2016-06-06      | <grade_imported_2nd> |
      | 3           | evidence_b       | 2018-08-08      | 40                   |

    When I navigate to "Upload course records" node in "Site administration > Courses > Upload completion records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_completion_3c.csv" file to "CSV file to upload" filemanager
    And I set the following fields to these values:
      | Upload course Create evidence  | 1            |
      | Upload course CSV Grade format | <grade_unit> |
      | Upload course Override current course completions | If more recent |
    And I click on "Save" "button" in the ".totara_completionimport__uploadcourse_form" "css_element"
    Then I should see "Course completion file successfully imported."
    And I should see "2 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_course_completions_task"

    When I follow "Course import report"
    Then the "completionimport_course" table should contain the following:
      | Line number | Course Shortname | Completion date | Grade                |
      | 2           | C1               | 2003-03-03      | <grade_imported_3rd> |
      | 3           | evidence_c       | 2004-04-04      | 60                   |

    When I navigate to "Upload course records" node in "Site administration > Courses > Upload completion records"
    And I upload "totara/completionimport/tests/behat/fixtures/course_completion_3d.csv" file to "CSV file to upload" filemanager
    And I set the following fields to these values:
      | Upload course Create evidence  | 1            |
      | Upload course CSV Grade format | <grade_unit> |
      | Upload course Override current course completions | If more recent |
    And I click on "Save" "button" in the ".totara_completionimport__uploadcourse_form" "css_element"
    Then I should see "Course completion file successfully imported."
    And I should see "2 Records imported pending processing"
    And I run the adhoc scheduled tasks "totara_completionimport\task\import_course_completions_task"

    When I follow "Course import report"
    Then the "completionimport_course" table should contain the following:
      | Line number | Course Shortname | Completion date | Grade |
      | 3           | evidence_d       | 2004-04-04      | 20    |

    When I navigate to "Manage users" node in "Site administration > Users"
    And I follow "Bob1 Learner1"
    And I click on "Record of Learning" "link" in the ".block_totara_user_profile_category_mylearning" "css_element"
    Then the "plan_courses" table should contain the following:
      | Course Title | Previous Completions | Progress | Grade       |
      | Course 1     | 1                    | 100%     | <grade_rol> |

    And I click on "1" "link" in the "td.course_completion_history_course_completion_previous_completion" "css_element"
    Then the "plan_courses_completion_history" table should contain the following:
      | Date last completed | Course Title | Grade at time of completion |
      | 3 Mar 2003          | Course 1     | <grade_history_3rd>         |
    And the "plan_courses_completion_history" table should not contain the following:
      | Date last completed |
      | 6 Jun 2016          |

    When I follow "Other Evidence"
    Then the "plan_evidence" table should contain the following:
      | Name                          |
      | Completed course : evidence_a |
      | Completed course : evidence_b |
      | Completed course : evidence_c |
      | Completed course : evidence_d |
    # Repetitive steps omitted

    When I navigate to my "CCIH report" report
    Then the "ccih_report" table should contain the following:
      | Date completed | Grade at time of completion | Is current record |
      | 6 Jun 2016     | <grade_rol>                 | Yes               |
      | 3 Mar 2003     | <grade_history_3rd>         | No                |

    Examples:
      | grade_unit | grade_imported_1st | grade_imported_2nd | grade_imported_3rd | grade_rol | grade_history_3rd |
      | Percentage | 240                | 150                | 210                | 50.0%     | 70.0%             |
      | Real       | 80                 | 50                 | 70                 | 16.7%     | 23.3%             |

    # Maths:
    #   | 20 / 300 =  6.7% | 20% * 300 =  60 |
    #   | 30 / 300 = 10.0% | 30% * 300 =  90 |
    #   | 40 / 300 = 13.3% | 40% * 300 = 120 |
    #   | 50 / 300 = 16.7% | 50% * 300 = 150 |
    #   | 60 / 300 = 20.0% | 60% * 300 = 180 |
    #   | 70 / 300 = 23.3% | 70% * 300 = 210 |
    #   | 80 / 300 = 26.7% | 80% * 300 = 240 |
    #   | 90 / 300 = 30.0% | 90% * 300 = 270 |

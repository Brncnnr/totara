@totara @perform @totara_competency @competency_achievement @javascript
Feature: Copy competency achievement paths (part 3)

  Background:
    Given I am on a totara site

    And a competency scale called "ggb" exists with the following values:
      | name  | description          | idnumber | proficient | default | sortorder |
      | Great | Is great at doing it | great    | 1          | 0       | 1         |
      | Good  | Is ok at doing it    | good     | 0          | 0       | 2         |
      | Bad   | Has no idea          | bad      | 0          | 1       | 3         |

    And the following "competency" frameworks exist:
      | fullname            | idnumber | description                        | scale |
      | Bulk copy framework | bulkFW   | Framework for bulk pathway copying | ggb   |

    And the following "competency" hierarchy exists:
      | framework | fullname                | idnumber                | parent            |
      | bulkFW    | Source Competency       | source competency       |                   |
      | bulkFW    | Target Competency       | target competency       |                   |
      | bulkFW    | Source child competency | source child competency | source competency |
      | bulkFW    | Another Competency      | another competency      |                   |

    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | course1   | 1                |
      | Course 2 | course2   | 1                |
      | Course 3 | course3   | 1                |

    And the following "linked courses" exist in "totara_competency" plugin:
      | competency        | course  | mandatory |
      | source competency | course1 | 1         |

    And the following "linkedcourses" exist in "totara_criteria" plugin:
      | idnumber      | competency        | number_required |
      | linkedcourses | source competency | 1               |

    And the following "coursecompletion" exist in "totara_criteria" plugin:
      | idnumber         | courses         | number_required |
      | coursecompletion | course2,course3 | 2               |

    And the following "onactivate" exist in "totara_criteria" plugin:
      | idnumber   | competency        |
      | onactivate | source competency |

    And the following "childcompetency" exist in "totara_criteria" plugin:
      | idnumber        | competency        | number_required |
      | childcompetency | source competency | 1               |

    And the following "othercompetency" exist in "totara_criteria" plugin:
      | idnumber                | competency         | number_required | competencies       |
      | othercompetency         | source competency  | 1               | another competency |
      | othercompetency_another | another competency | 1               | source competency  |

    And the following "learning plan pathways" exist in "totara_competency" plugin:
      | competency              |
      | source competency       |
      | source child competency |

    And the following "perform rating pathways" exist in "totara_competency" plugin:
      | competency        |
      | source competency |

    And the following "manual pathways" exist in "totara_competency" plugin:
      | competency        | roles             |
      | source competency | manager,appraiser |

    And the following "criteria group pathways" exist in "totara_competency" plugin:
      | competency         | scale_value | criteria                        | aggregation |
      | source competency  | bad         | onactivate                      |             |
      | source competency  | great       | childcompetency,othercompetency | all         |
      | source competency  | great       | coursecompletion                | any         |
      | source competency  | good        | linkedcourses                   |             |
      | another competency | great       | othercompetency_another         |             |

  Scenario: Do bulk copy of pathways to target with no existing pathways
    When I log in as "admin"
    And I click on "Dashboard" "link"
    And I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Bulk copy framework" "link"
    And I click on "Target Competency" "link"
    Then I should see "No achievement paths added" in the ".tui-competencySummaryAchievementConfiguration" "css_element"

    When I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Bulk copy framework" "link"
    And I click on "Source Competency" "link"
    Then I should see "Learning plan" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(3)" "css_element"
    And I should see "Performance activity rating" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(4)" "css_element"

    And I should see "Manual rating" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(5)" "css_element"
    And I should see "Manager" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(5)" "css_element"
    And I should see "Appraiser" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(5)" "css_element"

    And I should see "Assignment activation" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"

    And I should see "Proficiency in child competencies" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"
    And I should not see "Proficiency not possible due to invalid criteria on one or more child competency" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"

    And I should see "Proficiency in other competencies" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"
    And I should see "Another Competency" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"

    And I should see "Courses" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"
    And I should see "Course 2" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"
    And I should see "Course 3" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"

    And I should see "Linked courses" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"
    And I should not see "No courses linked to the competency" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"

    When I click on "Copy to other competencies in the same framework" "button"
    And I wait until the page is ready
    And I set the field "Search" to "Target Competency"
    And I toggle the selection of row "1" of the tui select table
    And I click on "Apply" "button"
    And I wait until the page is ready
    And I click on "Continue" "button"
    Then I should see "The achievement paths were successfully scheduled to be applied to 1 competency. These changes might take a while to be reflected throughout the site."

    When I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Bulk copy framework" "link"
    And I run the adhoc scheduled tasks "totara_competency\task\copy_pathway_task"
    And I click on "Target Competency" "link"
    Then I should not see "No achievement paths added"
    And I should see "Learning plan" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(3)" "css_element"
    And I should see "Performance activity rating" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(4)" "css_element"

    And I should see "Manual rating" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(5)" "css_element"
    And I should see "Manager" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(5)" "css_element"
    And I should see "Appraiser" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(5)" "css_element"

    And I should see "Assignment activation" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"

    And I should see "Proficiency in child competencies" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"
    And I should see "No child competencies exist" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"

    And I should see "Proficiency in other competencies" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"
    And I should see "No competencies added" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"

    And I should see "Linked courses" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"
    And I should see "No courses linked to the competency" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"

    And I should see "Courses" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"
    And I should see "No courses added" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"

    And I should see "Linked courses" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"
    And I should see "No courses linked to the competency" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"


  Scenario: Do bulk copy of pathways to target with existing pathways
    When I log in as "admin"
    And I click on "Dashboard" "link"
    And I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Bulk copy framework" "link"
    And I click on "Another Competency" "link"
    Then I should see "Proficiency in other competencies" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(3)" "css_element"
    And I should see "Source Competency" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(3)" "css_element"

    When I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Bulk copy framework" "link"
    And I click on "Source Competency" "link"
    Then I should see "Learning plan" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(3)" "css_element"
    And I should see "Performance activity rating" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(4)" "css_element"

    And I should see "Manual rating" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(5)" "css_element"
    And I should see "Manager" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(5)" "css_element"
    And I should see "Appraiser" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(5)" "css_element"

    And I should see "Assignment activation" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"

    And I should see "Proficiency in child competencies" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"
    And I should not see "Proficiency not possible due to invalid criteria on one or more child competency" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"

    And I should see "Proficiency in other competencies" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"
    And I should see "Another Competency" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"

    And I should see "Courses" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"
    And I should see "Course 2" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"
    And I should see "Course 3" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"

    And I should see "Linked courses" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"
    And I should not see "No courses linked to the competency" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"

    When I click on "Copy to other competencies in the same framework" "button"
    And I wait until the page is ready
    And I set the field "Search" to "Another Competency"
    And I toggle the selection of row "1" of the tui select table
    And I click on "Apply" "button"
    And I wait until the page is ready
    And I click on "Replace all" "button"
    Then I should see "The achievement paths were successfully scheduled to be applied to 1 competency. It contains criteria-based paths that will need to be reviewed. These changes might take a while to be reflected throughout the site."

    When I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Bulk copy framework" "link"
    And I run the adhoc scheduled tasks "totara_competency\task\copy_pathway_task"
    And I click on "Another Competency" "link"
    Then I should not see "No achievement paths added"
    And I should see "Learning plan" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(3)" "css_element"
    And I should see "Performance activity rating" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(4)" "css_element"

    And I should see "Manual rating" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(5)" "css_element"
    And I should see "Manager" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(5)" "css_element"
    And I should see "Appraiser" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(5)" "css_element"

    And I should see "Assignment activation" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"

    And I should see "Proficiency in child competencies" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"
    And I should see "No child competencies exist" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"

    And I should see "Proficiency in other competencies" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"
    And I should see "No competencies added" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"

    And I should see "Linked courses" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"
    And I should see "No courses linked to the competency" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"

    And I should see "Courses" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"
    And I should see "No courses added" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"

    And I should see "Linked courses" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"
    And I should see "No courses linked to the competency" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"


  Scenario: Warning banner appears when target competency summary page is visited before copy pathway task runs
    When I log in as "admin"
    And I click on "Dashboard" "link"
    And I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Bulk copy framework" "link"
    And I click on "Target Competency" "link"
    Then I should not see "There is a task scheduled to update this competency"
    And I should see "No achievement paths added" in the ".tui-competencySummaryAchievementConfiguration" "css_element"

    When I click on "Dashboard" "link"
    And I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Bulk copy framework" "link"
    And I click on "Another Competency" "link"
    Then I should not see "There is a task scheduled to update this competency"
    And I should see "Proficiency in other competencies" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(3)" "css_element"
    And I should see "Source Competency" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(3)" "css_element"

    When I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Bulk copy framework" "link"
    And I click on "Source Competency" "link"
    And I click on "Copy to other competencies in the same framework" "button"
    And I wait until the page is ready
    And I click on "Select all rows" tui "checkbox"
    And I click on "Apply" "button"
    And I wait until the page is ready
    And I click on "Replace all" "button"
    Then I should see "The achievement paths were successfully scheduled to be applied to 2 competencies. There is 1 competency containing criteria-based paths that will need to be reviewed. These changes might take a while to be reflected throughout the site."

    When I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Bulk copy framework" "link"
    And I click on "Target Competency" "link"
    Then I should see "There is a task scheduled to update this competency"
    And I should see "No achievement paths added" in the ".tui-competencySummaryAchievementConfiguration" "css_element"

    When I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Bulk copy framework" "link"
    And I click on "Another Competency" "link"
    Then I should see "There is a task scheduled to update this competency"
    And I should see "Proficiency in other competencies" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(3)" "css_element"
    And I should see "Source Competency" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(3)" "css_element"

    When I run the adhoc scheduled tasks "totara_competency\task\copy_pathway_task"
    And I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Bulk copy framework" "link"
    And I click on "Target Competency" "link"
    Then I should not see "There is a task scheduled to update this competency"
    And I should see "Learning plan" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(3)" "css_element"
    And I should see "Performance activity rating" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(4)" "css_element"

    And I should see "Manual rating" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(5)" "css_element"
    And I should see "Manager" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(5)" "css_element"
    And I should see "Appraiser" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(5)" "css_element"

    And I should see "Assignment activation" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"

    And I should see "Proficiency in child competencies" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"
    And I should see "No child competencies exist" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"

    And I should see "Proficiency in other competencies" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"
    And I should see "No competencies added" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"

    And I should see "Linked courses" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"
    And I should see "No courses linked to the competency" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"

    And I should see "Courses" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"
    And I should see "No courses added" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"

    And I should see "Linked courses" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"
    And I should see "No courses linked to the competency" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"

    When I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Bulk copy framework" "link"
    And I click on "Another Competency" "link"
    Then I should not see "There is a task scheduled to update this competency"
    And I should see "Learning plan" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(3)" "css_element"
    And I should see "Performance activity rating" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(4)" "css_element"

    And I should see "Manual rating" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(5)" "css_element"
    And I should see "Manager" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(5)" "css_element"
    And I should see "Appraiser" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(5)" "css_element"

    And I should see "Assignment activation" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"

    And I should see "Proficiency in child competencies" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"
    And I should see "No child competencies exist" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"

    And I should see "Proficiency in other competencies" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"
    And I should see "No competencies added" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"

    And I should see "Linked courses" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"
    And I should see "No courses linked to the competency" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"

    And I should see "Courses" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"
    And I should see "No courses added" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"

    And I should see "Linked courses" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"
    And I should see "No courses linked to the competency" in the ".tui-competencySummaryAchievementConfiguration > .tui-card:nth-child(6)" "css_element"

  Scenario: I cannot modify target competency pathways until the pathway copying task has run.
    When I log in as "admin"
    And I click on "Dashboard" "link"
    And I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Bulk copy framework" "link"
    And I click on "Target Competency" "link"
    Then I should not see "There is a task scheduled to update this competency"
    And I should see "No achievement paths added" in the ".tui-competencySummaryAchievementConfiguration" "css_element"

    When I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Bulk copy framework" "link"
    And I click on "Source Competency" "link"
    When I click on "Copy to other competencies in the same framework" "button"
    And I wait until the page is ready
    And I set the field "Search" to "Target Competency"
    And I toggle the selection of row "1" of the tui select table
    And I click on "Apply" "button"
    And I wait until the page is ready
    And I click on "Continue" "button"
    Then I should see "The achievement paths were successfully scheduled to be applied to 1 competency. These changes might take a while to be reflected throughout the site."

    When I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Bulk copy framework" "link"
    And I click on "Target Competency" "link"
    Then I should see "There is a task scheduled to update this competency"
    And I should see "No achievement paths added" in the ".tui-competencySummaryAchievementConfiguration" "css_element"

    When I navigate to the competency achievement paths page for the "Target Competency" competency
    And I add a "learning_plan" pathway
    And I wait until the page is ready
    Then the "Apply changes" "button" should be disabled

    When I reload the page
    And I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Bulk copy framework" "link"
    And I wait until the page is ready
    And I run the adhoc scheduled tasks "totara_competency\task\copy_pathway_task"
    And I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Bulk copy framework" "link"
    And I click on "Target Competency" "link"
    And I wait until the page is ready
    Then I should not see "There is a task scheduled to update this competency"

    When I navigate to the competency achievement paths page for the "Target Competency" competency
    When I add a "manual" pathway
    And I wait until the page is ready
    And I click on "Apply changes" "button"
    Then I should see "Changes applied successfully"
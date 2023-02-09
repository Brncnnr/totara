@totara @perform @totara_competency @competency_achievement @javascript
Feature: Copy competency achievement paths (part 2)

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
      | framework | fullname                                                         | idnumber                                                   | parent                                                     |
      | bulkFW    | Source Only Learning Plan Pathway Competency                     | source lp only pathway competency                          |                                                            |
      | bulkFW    | Source Only Perform Rating Pathway Competency                    | source perf only pathway competency                        |                                                            |
      | bulkFW    | Source Only Manual Rating Pathway Competency                     | source manual only pathway competency                      |                                                            |
      | bulkFW    | Source Only Criteria Based (assignment) Pathway Competency       | source criteria (assignment) only pathway competency       |                                                            |
      | bulkFW    | Source Only Criteria Based (linked course) Pathway Competency    | source criteria (linked course) only pathway competency    |                                                            |
      | bulkFW    | Source Only Criteria Based (other course) Pathway Competency     | source criteria (other course) only pathway competency     |                                                            |
      | bulkFW    | Source Only Criteria Based (child competency) Pathway Competency | source criteria (child competency) only pathway competency |                                                            |
      | bulkFW    | Source Only Criteria Based (other competency) Pathway Competency | source criteria (other competency) only pathway competency |                                                            |
      | bulkFW    | Target Zero Pathway Competency                                   | target zero pathways competency                            |                                                            |
      | bulkFW    | Source child competency                                          | source child competency                                    | source criteria (child competency) only pathway competency |
      | bulkFW    | Another competency                                               | another competency                                         |                                                            |

    And the following "courses" exist:
      | fullname | shortname | enablecompletion |
      | Course 1 | course1   | 1                |
      | Course 2 | course2   | 1                |
      | Course 3 | course3   | 1                |

    And the following "linked courses" exist in "totara_competency" plugin:
      | competency                                              | course  | mandatory |
      | source criteria (linked course) only pathway competency | course1 | 1         |

    And the following "linkedcourses" exist in "totara_criteria" plugin:
      | idnumber             | competency                                              | number_required |
      | linkedcourses_single | source criteria (linked course) only pathway competency | 1               |

    And the following "coursecompletion" exist in "totara_criteria" plugin:
      | idnumber                | courses         | number_required |
      | coursecompletion_single | course2,course3 | 2               |

    And the following "onactivate" exist in "totara_criteria" plugin:
      | idnumber          | competency                                           |
      | onactivate_single | source criteria (assignment) only pathway competency |

    And the following "childcompetency" exist in "totara_criteria" plugin:
      | idnumber               | competency                                                 | number_required |
      | childcompetency_single | source criteria (child competency) only pathway competency | 1               |

    And the following "othercompetency" exist in "totara_criteria" plugin:
      | idnumber               | competency                                                 | number_required | competencies       |
      | othercompetency_single | source criteria (other competency) only pathway competency | 1               | another competency |

    And the following "learning plan pathways" exist in "totara_competency" plugin:
      | competency                        |
      | source lp only pathway competency |
      | source child competency           |
      | another competency                |

    And the following "perform rating pathways" exist in "totara_competency" plugin:
      | competency                          |
      | source perf only pathway competency |

    And the following "manual pathways" exist in "totara_competency" plugin:
      | competency                            | roles             |
      | source manual only pathway competency | manager,appraiser |

    And the following "criteria group pathways" exist in "totara_competency" plugin:
      | competency                                                 | scale_value | criteria                |
      | source criteria (assignment) only pathway competency       | good        | onactivate_single       |
      | source criteria (linked course) only pathway competency    | good        | linkedcourses_single    |
      | source criteria (other course) only pathway competency     | good        | coursecompletion_single |
      | source criteria (child competency) only pathway competency | good        | childcompetency_single  |
      | source criteria (other competency) only pathway competency | good        | othercompetency_single  |

    When I log in as "admin"
    And I click on "Dashboard" "link"
    And I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Bulk copy framework" "link"
    And I click on "Target Zero Pathway Competency" "link"
    Then I should see "No achievement paths added" in the ".tui-competencySummaryAchievementConfiguration" "css_element"

  Scenario: Do bulk copy from source with learning plan pathway only
    When I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Bulk copy framework" "link"
    And I click on "Source Only Learning Plan Pathway Competency" "link"
    Then I should see "Learning plan" in the ".tui-competencySummaryAchievementCriteria__criterion" "css_element"

    When I click on "Copy to other competencies in the same framework" "button"
    And I wait until the page is ready
    And I set the field "Search" to "Target Zero Pathway Competency"
    And I toggle the selection of row "1" of the tui select table
    And I click on "Apply" "button"
    And I wait until the page is ready
    And I click on "Continue" "button"
    Then I should see "The achievement paths were successfully scheduled to be applied to 1 competency. These changes might take a while to be reflected throughout the site."

    When I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Bulk copy framework" "link"
    And I run the adhoc scheduled tasks "totara_competency\task\copy_pathway_task"
    And I click on "Target Zero Pathway Competency" "link"
    Then I should see "Learning plan" in the ".tui-competencySummaryAchievementCriteria__criterion" "css_element"

  Scenario: Do bulk copy from source with perform rating pathway only
    When I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Bulk copy framework" "link"
    And I click on "Source Only Perform Rating Pathway Competency" "link"
    Then I should see "Performance activity rating" in the ".tui-competencySummaryAchievementCriteria__criterion" "css_element"

    When I click on "Copy to other competencies in the same framework" "button"
    And I wait until the page is ready
    And I set the field "Search" to "Target Zero Pathway Competency"
    And I toggle the selection of row "1" of the tui select table
    And I click on "Apply" "button"
    And I wait until the page is ready
    And I click on "Continue" "button"
    Then I should see "The achievement paths were successfully scheduled to be applied to 1 competency. These changes might take a while to be reflected throughout the site."

    When I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Bulk copy framework" "link"
    And I run the adhoc scheduled tasks "totara_competency\task\copy_pathway_task"
    And I click on "Target Zero Pathway Competency" "link"
    Then I should see "Performance activity rating" in the ".tui-competencySummaryAchievementCriteria__criterion" "css_element"

  Scenario: Do bulk copy from source with manual rating pathway only
    When I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Bulk copy framework" "link"
    And I click on "Source Only Manual Rating Pathway Competency" "link"
    Then I should see "Manual rating" in the ".tui-competencySummaryAchievementCriteria__criterion" "css_element"
    And I should see "Manager" in the ".tui-competencySummaryAchievementCriteria__criterion" "css_element"
    And I should see "Appraiser" in the ".tui-competencySummaryAchievementCriteria__criterion" "css_element"

    When I click on "Copy to other competencies in the same framework" "button"
    And I wait until the page is ready
    And I set the field "Search" to "Target Zero Pathway Competency"
    And I toggle the selection of row "1" of the tui select table
    And I click on "Apply" "button"
    And I wait until the page is ready
    And I click on "Continue" "button"
    Then I should see "The achievement paths were successfully scheduled to be applied to 1 competency. These changes might take a while to be reflected throughout the site."

    When I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Bulk copy framework" "link"
    And I run the adhoc scheduled tasks "totara_competency\task\copy_pathway_task"
    And I click on "Target Zero Pathway Competency" "link"
    Then I should see "Manual rating" in the ".tui-competencySummaryAchievementCriteria__criterion" "css_element"
    And I should see "Manager" in the ".tui-competencySummaryAchievementCriteria__criterion" "css_element"
    And I should see "Appraiser" in the ".tui-competencySummaryAchievementCriteria__criterion" "css_element"

  Scenario: Do bulk copy from source with criteria pathway (assignment) only
    When I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Bulk copy framework" "link"
    And I click on "Source Only Criteria Based (assignment) Pathway Competency" "link"
    Then I should see "Assignment activation" in the ".tui-competencySummaryAchievementCriteria__criterion" "css_element"

    When I click on "Copy to other competencies in the same framework" "button"
    And I wait until the page is ready
    And I set the field "Search" to "Target Zero Pathway Competency"
    And I toggle the selection of row "1" of the tui select table
    And I click on "Apply" "button"
    And I wait until the page is ready
    And I click on "Continue" "button"
    Then I should see "The achievement paths were successfully scheduled to be applied to 1 competency. These changes might take a while to be reflected throughout the site."

    When I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Bulk copy framework" "link"
    And I run the adhoc scheduled tasks "totara_competency\task\copy_pathway_task"
    And I click on "Target Zero Pathway Competency" "link"
    Then I should see "Assignment activation" in the ".tui-competencySummaryAchievementCriteria__criterion" "css_element"

  Scenario: Do bulk copy from source with criteria pathway (linked courses) only
    When I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Bulk copy framework" "link"
    And I click on "Source Only Criteria Based (linked course) Pathway Competency" "link"
    Then I should see "Linked courses" in the ".tui-competencySummaryAchievementCriteria__criterion" "css_element"
    And I should not see "No courses linked to the competency" in the ".tui-competencySummaryAchievementCriteria__criterion" "css_element"

    When I click on "Copy to other competencies in the same framework" "button"
    And I wait until the page is ready
    And I set the field "Search" to "Target Zero Pathway Competency"
    And I toggle the selection of row "1" of the tui select table
    And I click on "Apply" "button"
    And I wait until the page is ready
    And I click on "Continue" "button"
    Then I should see "The achievement paths were successfully scheduled to be applied to 1 competency. These changes might take a while to be reflected throughout the site."

    When I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Bulk copy framework" "link"
    And I run the adhoc scheduled tasks "totara_competency\task\copy_pathway_task"
    And I click on "Target Zero Pathway Competency" "link"
    Then I should see "Linked courses" in the ".tui-competencySummaryAchievementCriteria__criterion" "css_element"
    And I should see "No courses linked to the competency" in the ".tui-competencySummaryAchievementCriteria__criterion" "css_element"

  Scenario: Do bulk copy from source with criteria pathway (other courses) only
    When I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Bulk copy framework" "link"
    And I click on "Source Only Criteria Based (other course) Pathway Competency" "link"
    Then I should see "Courses" in the ".tui-competencySummaryAchievementCriteria__criterion" "css_element"
    Then I should see "Course 2" in the ".tui-competencySummaryAchievementCriteria__criterion" "css_element"
    Then I should see "Course 3" in the ".tui-competencySummaryAchievementCriteria__criterion" "css_element"

    When I click on "Copy to other competencies in the same framework" "button"
    And I wait until the page is ready
    And I set the field "Search" to "Target Zero Pathway Competency"
    And I toggle the selection of row "1" of the tui select table
    And I click on "Apply" "button"
    And I wait until the page is ready
    And I click on "Continue" "button"
    Then I should see "The achievement paths were successfully scheduled to be applied to 1 competency. These changes might take a while to be reflected throughout the site."

    When I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Bulk copy framework" "link"
    And I run the adhoc scheduled tasks "totara_competency\task\copy_pathway_task"
    And I click on "Target Zero Pathway Competency" "link"
    Then I should see "Courses" in the ".tui-competencySummaryAchievementCriteria__criterion" "css_element"
    Then I should see "No courses added" in the ".tui-competencySummaryAchievementCriteria__criterion" "css_element"

  Scenario: Do bulk copy from source with criteria pathway (child competencies) only
    When I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Bulk copy framework" "link"
    And I click on "Source Only Criteria Based (child competency) Pathway Competency" "link"
    Then I should see "Proficiency in child competencies" in the ".tui-competencySummaryAchievementCriteria__criterion" "css_element"
    Then I should not see "Proficiency not possible due to invalid criteria on one or more child competency" in the ".tui-competencySummaryAchievementCriteria__criterion" "css_element"

    When I click on "Copy to other competencies in the same framework" "button"
    And I wait until the page is ready
    And I set the field "Search" to "Target Zero Pathway Competency"
    And I toggle the selection of row "1" of the tui select table
    And I click on "Apply" "button"
    And I wait until the page is ready
    And I click on "Continue" "button"
    Then I should see "The achievement paths were successfully scheduled to be applied to 1 competency. These changes might take a while to be reflected throughout the site."

    When I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Bulk copy framework" "link"
    And I run the adhoc scheduled tasks "totara_competency\task\copy_pathway_task"
    And I click on "Target Zero Pathway Competency" "link"
    Then I should see "Proficiency in child competencies" in the ".tui-competencySummaryAchievementCriteria__criterion" "css_element"
    Then I should see "No child competencies exist" in the ".tui-competencySummaryAchievementCriteria__criterion" "css_element"

  Scenario: Do bulk copy from source with criteria pathway (other competencies) only
    When I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Bulk copy framework" "link"
    And I click on "Source Only Criteria Based (other competency) Pathway Competency" "link"
    Then I should see "Proficiency in other competencies" in the ".tui-competencySummaryAchievementCriteria__criterion" "css_element"
    And I should see "Another competency" in the ".tui-competencySummaryAchievementCriteria__criterion" "css_element"

    When I click on "Copy to other competencies in the same framework" "button"
    And I wait until the page is ready
    And I set the field "Search" to "Target Zero Pathway Competency"
    And I toggle the selection of row "1" of the tui select table
    And I click on "Apply" "button"
    And I wait until the page is ready
    And I click on "Continue" "button"
    Then I should see "The achievement paths were successfully scheduled to be applied to 1 competency. These changes might take a while to be reflected throughout the site."

    When I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Bulk copy framework" "link"
    And I run the adhoc scheduled tasks "totara_competency\task\copy_pathway_task"
    And I click on "Target Zero Pathway Competency" "link"
    Then I should see "Proficiency in other competencies" in the ".tui-competencySummaryAchievementCriteria__criterion" "css_element"
    And I should see "No competencies added" in the ".tui-competencySummaryAchievementCriteria__criterion" "css_element"

  Scenario: Do bulk copy from source to source child
    When I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Bulk copy framework" "link"
    And I click on "Source Only Criteria Based (child competency) Pathway Competency" "link"
    Then I should see "Proficiency in child competencies" in the ".tui-competencySummaryAchievementCriteria__criterion" "css_element"

    When I click on "Copy to other competencies in the same framework" "button"
    And I wait until the page is ready
    # Make sure the row is disabled
    Then I should see "Source Only Criteria Based (child competency) Pathway Competency" in the ".tui-dataTableRow--disabled" "css_element"
    When I click on "View child competencies" "button" in the ".tui-dataTableRow:nth-child(3)" "css_element"
    And I toggle the selection of row "1" of the tui select table
    And I click on "Apply" "button"
    And I wait until the page is ready
    And I click on "Replace all" "button"
    Then I should see "The achievement paths were successfully scheduled to be applied to 1 competency. These changes might take a while to be reflected throughout the site."

@totara @perform @totara_competency @competency_achievement @javascript
Feature: Copy competency achievement paths

  Background:
    Given I am on a totara site
    And a competency scale called "ggb" exists with the following values:
      | name  | description          | idnumber | proficient | default | sortorder |
      | Great | Is great at doing it | great    | 1          | 0       | 1         |
      | Good  | Is ok at doing it    | good     | 0          | 0       | 2         |
      | Bad   | Has no idea          | bad      | 0          | 1       | 3         |

    And the following "competency" frameworks exist:
      | fullname          | idnumber | description                       | scale |
      | Colours framework | colourFW | Framework for colour competencies | ggb   |

    And the following "competency" hierarchy exists:
      | framework | fullname                      | idnumber              | description                           | parent      |
      | colourFW  | Colourless competency         | colourless competency | Competency without a pathway          |             |
      | colourFW  | Parent                        | parent                | Parent competency                     |             |
      | colourFW  | no pathway competency         | no pathway comp       | Competency without a pathway          | parent      |
      | colourFW  | manual pathway competency     | manual pathway comp   | Competency with manual rating pathway | parent      |
      | colourFW  | The amber competency          | amber comp            | Competency without a pathway          |             |
      | colourFW  | The aqua competency           | aqua comp             | Competency without a pathway          |             |
      | colourFW  | The beige competency          | beige comp            | Competency without a pathway          |             |
      | colourFW  | The blue competency           | blue comp             | Competency without a pathway          |             |
      | colourFW  | The blueviolet competency     | blueviolet comp       | Competency without a pathway          |             |
      | colourFW  | The bronze competency         | bronze comp           | Competency without a pathway          |             |
      | colourFW  | The brown competency          | brown comp            | Competency without a pathway          |             |
      | colourFW  | The chocolate competency      | chocolate comp        | Competency without a pathway          |             |
      | colourFW  | The copper competency         | copper comp           | Competency without a pathway          |             |
      | colourFW  | The coral competency          | coral comp            | Competency without a pathway          |             |
      | colourFW  | The cyan competency           | cyan comp             | Competency without a pathway          |             |
      | colourFW  | The firebrick competency      | firebrick comp        | Competency without a pathway          |             |
      | colourFW  | The forestgreen competency    | forestgreen comp      | Competency without a pathway          |             |
      | colourFW  | The gold competency           | gold comp             | Competency without a pathway          |             |
      | colourFW  | The gray competency           | gray comp             | Competency without a pathway          |             |
      | colourFW  | The green competency          | green comp            | Competency without a pathway          |             |
      | colourFW  | The indigo competency         | indigo comp           | Competency without a pathway          |             |
      | colourFW  | The khaki competency          | khaki comp            | Competency without a pathway          |             |
      | colourFW  | The lavender competency       | lavender comp         | Competency without a pathway          |             |
      | colourFW  | The lime competency           | lime comp             | Competency without a pathway          |             |
      | colourFW  | The magenta competency        | magenta comp          | Competency without a pathway          |             |
      | colourFW  | The maroon competency         | maroon comp           | Competency without a pathway          |             |
      | colourFW  | The midnightblue competency   | midnightblue comp     | Competency without a pathway          |             |
      | colourFW  | The navy competency           | navy comp             | Competency without a pathway          |             |
      | colourFW  | The olive competency          | olive comp            | Competency without a pathway          |             |
      | colourFW  | The orange competency         | orange comp           | Competency without a pathway          |             |
      | colourFW  | The orchid competency         | orchid comp           | Competency without a pathway          |             |
      | colourFW  | The peach competency          | peach comp            | Competency without a pathway          |             |
      | colourFW  | The pink competency           | pink comp             | Competency without a pathway          |             |
      | colourFW  | The plum competency           | plum comp             | Competency without a pathway          |             |
      | colourFW  | The purple competency         | purple comp           | Competency without a pathway          |             |
      | colourFW  | The red competency            | red comp              | Competency without a pathway          |             |
      | colourFW  | The royalblue competency      | royalblue comp        | Competency without a pathway          |             |
      | colourFW  | The salmon competency         | salmon comp           | Competency without a pathway          |             |
      | colourFW  | The sandybrown competency     | sandybrown comp       | Competency without a pathway          |             |
      | colourFW  | The seagreen competency       | seagreen comp         | Competency without a pathway          |             |
      | colourFW  | The silver competency         | silver comp           | Competency without a pathway          |             |
      | colourFW  | The skyblue competency        | skyblue comp          | Competency without a pathway          |             |
      | colourFW  | The slateblue competency      | slateblue comp        | Competency without a pathway          |             |
      | colourFW  | The springgreen competency    | springgreen comp      | Competency without a pathway          |             |
      | colourFW  | The steelblue competency      | steelblue comp        | Competency without a pathway          |             |
      | colourFW  | The tan competency            | tan comp              | Competency without a pathway          |             |
      | colourFW  | The teal competency           | teal comp             | Competency without a pathway          |             |
      | colourFW  | The thistle competency        | thistle comp          | Competency without a pathway          |             |
      | colourFW  | The tomato competency         | tomato comp           | Competency without a pathway          |             |
      | colourFW  | The vanilla competency        | vanilla comp          | Competency without a pathway          |             |
      | colourFW  | The violet competency         | violet comp           | Competency without a pathway          |             |
      | colourFW  | The yellow competency         | yellow comp           | Competency without a pathway          |             |
      | colourFW  | The yellowgreen competency    | yellowgreen comp      | Competency without a pathway          |             |
      | colourFW  | The aqua 1 competency         | aqua comp 1           | Competency without a pathway          | aqua comp   |
      | colourFW  | The aqua 2 competency         | aqua comp 2           | Competency without a pathway          | aqua comp   |
      | colourFW  | The aqua 3 competency         | aqua comp 3           | Competency without a pathway          | aqua comp   |
      | colourFW  | The aqua 4 competency         | aqua comp 4           | Competency without a pathway          | aqua comp   |
      | colourFW  | The aqua 5 competency         | aqua comp 5           | Competency without a pathway          | aqua comp   |
      | colourFW  | The aqua 6 competency         | aqua comp 6           | Competency without a pathway          | aqua comp   |
      | colourFW  | The child aqua 1.1 competency | aqua comp 1.1         | Competency without a pathway          | aqua comp 1 |
      | colourFW  | The child aqua 1.2 competency | aqua comp 1.2         | Competency without a pathway          | aqua comp 1 |
      | colourFW  | The child aqua 1.3 competency | aqua comp 1.3         | Competency without a pathway          | aqua comp 1 |
      | colourFW  | The child aqua 1.4 competency | aqua comp 1.4         | Competency without a pathway          | aqua comp 1 |


    And the following "manual pathways" exist in "totara_competency" plugin:
      | competency          | roles          |
      | manual pathway comp | self,appraiser |
      | brown comp          | self,appraiser |
      | skyblue comp        | self,appraiser |

    Given the following "courses" exist:
      | fullname | shortname | enablecompletion | summary                               |
      | Course 1 | course1   | 1                | Course <strong>1</strong> Description |

    And the following "linked courses" exist in "totara_competency" plugin:
      | competency | course  | mandatory |
      | brown comp | course1 | 1         |

    And the following "linkedcourses" exist in "totara_criteria" plugin:
      | idnumber      | competency | number_required |
      | linkedcourses | brown comp | 1               |

    And the following "criteria group pathways" exist in "totara_competency" plugin:
      | competency | scale_value | criteria      | sortorder |
      | brown comp | great       | linkedcourses | 1         |

  Scenario: Achievement paths cannot be copied when the current competency doesnt have achievement paths
    When I log in as "admin"
    When I click on "Dashboard" "link"
    And I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Colours framework" "link"
    And I click on "no pathway competency" "link"
    Then I should see "No achievement paths added" in the ".tui-competencySummaryAchievementConfiguration" "css_element"
    When I click on "Copy to other competencies in the same framework" "button"
    Then I should see "Cannot copy achievement paths" in the tui modal
    And I should see "This competency has no achievement paths. To copy it, achievement paths must be added." in the tui modal

  Scenario: Manual achievement paths can be copied to other competencies
    When I log in as "admin"
    When I click on "Dashboard" "link"
    And I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Colours framework" "link"
    And I click on "manual pathway competency" "link"
    Then I should not see "No achievement paths added" in the ".tui-competencySummaryAchievementConfiguration" "css_element"
    When I click on "Copy to other competencies in the same framework" "button"
    And I wait until the page is ready
    Then I should see "Copy achievement paths from: ‘manual pathway competency’"
    And I toggle the selection of row "1" of the tui select table
    When I click on "Apply" "button"
    And I wait until the page is ready
    And I click on "Continue" "button"
    Then I should see "The achievement paths were successfully scheduled to be applied to 1 competency. These changes might take a while to be reflected throughout the site."

  Scenario: Competencies within the same framework can be filtered
    When I log in as "admin"
    When I click on "Dashboard" "link"
    And I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Colours framework" "link"
    And I click on "manual pathway competency" "link"
    Then I should not see "No achievement paths added" in the ".tui-competencySummaryAchievementConfiguration" "css_element"
    When I click on "Copy to other competencies in the same framework" "button"
    And I wait until the page is ready
    Then I should see "Copy achievement paths from: ‘manual pathway competency’"
    And I should see "51 competencies" in the ".tui-competencyCopyPathwaySelection__count" "css_element"
    And I should see "10" rows in the tui datatable
    And the field "Items per page" matches value "10"
    And I should see "5" in the ".tui-paging__selector" "css_element"
    And I should see "1" in the ".tui-paging__selector-number--current" "css_element"
    And I should see "The bronze competency"
    And I should see "The brown competency"
    And I should not see "The indigo competency"
    And I should not see "The olive competency"

    When I click on "Page 2" "button"
    Then I should see "10" rows in the tui datatable
    And I should see "2" in the ".tui-paging__selector-number--current" "css_element"
    And I should not see "The bronze competency"
    And I should not see "The brown competency"
    And I should see "The indigo competency"
    And I should see "The gold competency"
    And I should not see "The olive competency"

    When I set the field "Items per page" to "20"
    Then I should see "20" rows in the tui datatable
    And I should see "The bronze competency"
    And I should see "The indigo competency"
    And I should not see "The olive competency"
    And I should see "3" in the ".tui-paging__selector" "css_element"
    And I should not see "4" in the ".tui-paging__selector" "css_element"
    When I click on "Page 3" "button"
    Then I should see "11" rows in the tui datatable
    And I should see "The tomato competency"
    And I should see "The yellow competency"

    # Check the search filter works as expected
    When I set the field "Search" to "competency"
    Then I should see "62 competencies" in the ".tui-competencyCopyPathwaySelection__count" "css_element"
    And I should see "20" rows in the tui datatable
    And I should see "Search results" in the ".tui-competencyCopyPathwayCrumbtrail__list-current" "css_element"
    When I set the field "Search" to "b"
    Then I should see "13 competencies" in the ".tui-competencyCopyPathwaySelection__count" "css_element"
    And I should see "13" rows in the tui datatable
    And I should see "The bronze competency"
    And I should see "The brown competency"
    When I set the field "Search" to "123"
    Then I should see "No competencies found"

    # Check the Without achievement paths filter works as expected and achivement path data is correct displayed
    When I set the field "Search" to "b"
    # skyblue competency
    Then I should see "Achievement paths: Manual rating" on row "11" of the tui select table
    # brown competency
    And I should see "Achievement paths: Criteria-based paths, Manual rating" on row "6" of the tui select table
    And I should see "13 competencies" in the ".tui-competencyCopyPathwaySelection__count" "css_element"
    When I click on the "Without achievement paths" tui toggle button
    Then the "Without achievement paths" tui toggle switch should be "on"
    And I should see "11 competencies" in the ".tui-competencyCopyPathwaySelection__count" "css_element"
    And I should not see "The skyblue competency"
    And I should not see "The brown competency"
    When I click on the "Without achievement paths" tui toggle button
    Then the "Without achievement paths" tui toggle switch should be "off"
    And I should see "13 competencies" in the ".tui-competencyCopyPathwaySelection__count" "css_element"
    And I should see "The skyblue competency"
    And I should see "The brown competency"

    # Check the source competency is included in the list
    When I set the field "Search" to ""
    And I set the field "Items per page" to "50"
    Then I should see "50" rows in the tui datatable
    When I click on "View child competencies" "button" in the ".tui-dataTableRow:nth-child(2)" "css_element"
    Then I should see "manual pathway competency" in the ".tui-competencyCopyPathwaySelection__body" "css_element"


  Scenario: Competencies can be added and removed from the basket
    When I log in as "admin"
    When I click on "Dashboard" "link"
    And I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Colours framework" "link"
    And I click on "manual pathway competency" "link"
    Then I should not see "No achievement paths added" in the ".tui-competencySummaryAchievementConfiguration" "css_element"
    When I click on "Copy to other competencies in the same framework" "button"
    And I wait until the page is ready
    Then I should see "Copy achievement paths from: ‘manual pathway competency’"
    And I should see "51 competencies" in the ".tui-competencyCopyPathwaySelection__count" "css_element"
    And I should not see "Clear selection" in the ".tui-competencyCopyPathwayBasket" "css_element"
    And I should not see "View selected" in the ".tui-competencyCopyPathwayBasket" "css_element"

    # Bulk Select competencies in the list
    When I click on "Select all rows" tui "checkbox"
    Then I should see "10" in the ".tui-basket__selectedCount" "css_element"
    And I should see "Clear selection" in the ".tui-competencyCopyPathwayBasket" "css_element"
    And I should see "View selected" in the ".tui-competencyCopyPathwayBasket" "css_element"
    When I click on "Select all rows" tui "checkbox"
    Then I should see "0" in the ".tui-basket__selectedCount" "css_element"
    And I should not see "Clear selection" in the ".tui-competencyCopyPathwayBasket" "css_element"
    And I should not see "View selected" in the ".tui-competencyCopyPathwayBasket" "css_element"
    And I should see the tui select table contains:
      | Colourless competency     |
      | Parent                    |
      | The amber competency      |
      | The aqua competency       |
      | The beige competency      |
      | The blue competency       |
      | The blueviolet competency |
      | The bronze competency     |
      | The brown competency      |
      | The chocolate competency  |

    # View Select competencies in the review list
    When I toggle the selection of row "3" of the tui select table
    And I toggle the selection of row "6" of the tui select table
    And I toggle the selection of row "8" of the tui select table
    And I toggle the selection of row "10" of the tui select table
    Then I should see "4" in the ".tui-basket__selectedCount" "css_element"
    When I click on "View selected" "button"
    Then I should see "Selected competencies" in the ".tui-competencyCopyReviewingSelection__heading" "css_element"
    And I should see "Back to all competencies" in the ".tui-competencyCopyPathwayBasket" "css_element"
    And I should not see "View selected" in the ".tui-competencyCopyPathwayBasket" "css_element"
    And I should see "The amber competency" on row "1" of the tui select table
    And I should see "The blue competency" on row "2" of the tui select table
    And I should see "The bronze competency" on row "3" of the tui select table
    And I should see "The chocolate competency" on row "4" of the tui select table
    When I click on "Clear selection" "button"
    Then I should see "0" in the ".tui-basket__selectedCount" "css_element"
    And I should not see "Load more"

    # Navigating back to competency selection view
    When I click on "Back to all competencies" "button"
    Then I should see "Framework" in the ".tui-competencyCopyPathwayCrumbtrail__list-current" "css_element"
    And I should see "10" rows in the tui datatable

    # Load more competencies in the review list
    When I set the field "Items per page" to "100"
    Then I should see "51" rows in the tui datatable
    When I click on "Select all rows" tui "checkbox"
    Then I should see "51" in the ".tui-basket__selectedCount" "css_element"
    When I click on "View selected" "button"
    Then I should see "50" rows in the tui datatable
    And I should see "Load more"
    And I should not see "The yellowgreen competency"
    When I click on "Load more" "button"
    Then I should see "51" rows in the tui datatable
    And I should see "The yellowgreen competency"
    And I should not see "Load more"

    # Check unselected items aren't removed
    When I toggle the selection of row "51" of the tui select table
    Then I should see "50" in the ".tui-basket__selectedCount" "css_element"
    And I should see "The yellowgreen competency"

    # Check filters are reset when switching views
    When I click on "Back to all competencies" "button"
    And I set the field "Search" to "b"
    When I click on "View selected" "button"
    And I click on "Back to all competencies" "button"
    And the field "Search" matches value ""

  Scenario: Competency hierarchy can be navigated
    When I log in as "admin"
    Then I click on "Dashboard" "link"
    And I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Colours framework" "link"
    And I click on "manual pathway competency" "link"
    And I click on "Copy to other competencies in the same framework" "button"
    And I wait until the page is ready
    Then I should see "51 competencies" in the ".tui-competencyCopyPathwaySelection__count" "css_element"
    And I should see "The aqua competency" on row "4" of the tui select table

    When I click on "View child competencies" "button" in the ".tui-dataTableRow:nth-child(4)" "css_element"
    And I wait until the page is ready
    Then I should see "The aqua competency" in the ".tui-competencyCopyPathwayCrumbtrail__list-current" "css_element"
    And I should see "Framework" in the ".tui-competencyCopyPathwayCrumbtrail__list" "css_element"
    And I should see the tui select table contains:
      | The aqua 1 competency |
      | The aqua 2 competency |
      | The aqua 3 competency |
      | The aqua 4 competency |
      | The aqua 5 competency |
      | The aqua 6 competency |
    And I should see "The aqua 1 competency" on row "1" of the tui select table
    And I should see "The aqua competency" on row "1" of the tui select table

    When I click on "View child competencies" "button" in the ".tui-dataTableRow:nth-child(1)" "css_element"
    And I wait until the page is ready
    And I should see "The aqua 1 competency" in the ".tui-competencyCopyPathwayCrumbtrail__list-current" "css_element"
    And I should see "Framework" in the ".tui-competencyCopyPathwayCrumbtrail__list" "css_element"
    And I should see "The aqua competency" in the ".tui-competencyCopyPathwayCrumbtrail__list" "css_element"
    And I should see the tui select table contains:
      | The child aqua 1.1 competency |
      | The child aqua 1.2 competency |
      | The child aqua 1.3 competency |
      | The child aqua 1.4 competency |

    When I click on "The aqua competency" "button"
    Then I should see "The aqua competency" in the ".tui-competencyCopyPathwayCrumbtrail__list-current" "css_element"
    And I should see "Framework" in the ".tui-competencyCopyPathwayCrumbtrail__list" "css_element"
    And I should see the tui select table contains:
      | The aqua 1 competency |
      | The aqua 2 competency |
      | The aqua 3 competency |
      | The aqua 4 competency |
      | The aqua 5 competency |
      | The aqua 6 competency |

    When I click on "Framework" "button"
    And I wait until the page is ready
    Then I should see "Framework" in the ".tui-competencyCopyPathwayCrumbtrail__list-current" "css_element"
    And I should see the tui select table contains:
      | Colourless competency     |
      | Parent                    |
      | The amber competency      |
      | The aqua competency       |
      | The beige competency      |
      | The blue competency       |
      | The blueviolet competency |
      | The bronze competency     |
      | The brown competency      |
      | The chocolate competency  |

    When I click on "View child competencies" "button" in the ".tui-dataTableRow:nth-child(4)" "css_element"
    And I wait until the page is ready
    And I toggle the selection of row "1" of the tui select table
    And I toggle the selection of row "2" of the tui select table
    Then I click on "View child competencies" "button" in the ".tui-dataTableRow:nth-child(1)" "css_element"
    And I wait until the page is ready
    And I should see "The aqua 1 competency" in the ".tui-competencyCopyPathwayCrumbtrail__list-current" "css_element"
    And I toggle the selection of row "1" of the tui select table
    And I toggle the selection of row "2" of the tui select table
    And I toggle the selection of row "3" of the tui select table
    Then I set the field "Search" to "competency"
    And I should see "62 competencies" in the ".tui-competencyCopyPathwaySelection__count" "css_element"
    And I should see "Search results" in the ".tui-competencyCopyPathwayCrumbtrail__list-current" "css_element"

    When I click on "View selected" "button"
    Then I should see the tui select table contains:
      | The aqua 1 competency         |
      | The aqua 2 competency         |
      | The child aqua 1.1 competency |
      | The child aqua 1.2 competency |
      | The child aqua 1.3 competency |

  Scenario: Copy achievement paths shows modals
    When I log in as "admin"
    When I click on "Dashboard" "link"

    # None have achievement paths
    And I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Colours framework" "link"
    And I click on "manual pathway comp" "link"
    When I click on "Copy to other competencies in the same framework" "button"
    And I wait until the page is ready
    And I toggle the selection of row "1" of the tui select table
    When I click on "Apply" "button"
    Then I should see "The copied achievement paths will be applied to the 1 competencies selected. Do you want to continue?" in the tui modal
    And I click on "Continue" "button"

    # All have achievement paths
    When I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Colours framework" "link"
    And I click on "manual pathway comp" "link"
    When I click on "Copy to other competencies in the same framework" "button"
    And I wait until the page is ready
    And I toggle the selection of row "9" of the tui select table
    When I set the field "Items per page" to "100"
    And I wait until the page is ready
    And I toggle the selection of row "40" of the tui select table
    When I click on "Apply" "button"
    Then I should see "The copied achievement paths will be applied to the 2 competencies selected. All of them already have achievement paths. Replacing the existing achievement paths may result in changes to users’ proficiency. Do you want to continue and replace the existing achievement paths?" in the tui modal
    And I click on "Replace all" "button"

    # Some have achievement paths
    When I navigate to "Manage competencies" node in "Site administration > Competencies"
    And I click on "Colours framework" "link"
    And I click on "manual pathway comp" "link"
    When I click on "Copy to other competencies in the same framework" "button"
    And I wait until the page is ready
    And I toggle the selection of row "8" of the tui select table
    And I toggle the selection of row "9" of the tui select table
    When I click on "Apply" "button"
    Then I should see "The copied achievement paths will be applied to the 2 competencies selected. 1 of them already have achievement paths. Replacing the existing achievement paths may result in changes to users’ proficiency. Do you want to continue and replace the existing achievement paths?" in the tui modal
    And I click on "Replace all" "button"
@totara @totara_contentmarketplace @contentmarketplace_linkedin @javascript @vuejs
Feature: Use the catalogue import page to create courses based upon LinkedIn Learning course content.

  Background:
    Given I set up the "linkedin" content marketplace plugin
    And I log in as "admin"

  Scenario: Learning objects catalog: No items
    When I navigate to the catalog import page for the "linkedin" content marketplace
    Then the tui basket should be empty
    And I should see "0 items" in the ".tui-contentMarketplaceImportCountAndFilters" "css_element"
    And I should see "No items to display"
    And ".tui-dataTable" "css_element" should not be visible
    And ".tui-paging" "css_element" should not be visible

  Scenario: Learning objects catalog: Basket sorting
    Given the following "learning objects" exist in "contentmarketplace_linkedin" plugin:
      | urn | title    |
      | A   | Course C |
      | B   | Course B |
      | C   | Course A |
    When I navigate to the catalog import page for the "linkedin" content marketplace
    Then the field "Sort by" matches value "Latest"
    And I should see "3 items" in the ".tui-contentMarketplaceImportCountAndFilters" "css_element"
    And I should see "3" rows in the tui datatable
    And I should see the tui select table contains:
      | Course C |
      | Course B |
      | Course A |
    When I set the field "Sort by" to "Alphabetical"
    Then I should see the tui select table contains:
      | Course A |
      | Course B |
      | Course C |

  Scenario: Learning objects: Basket pagination
    Given the following "learning objects" exist in "contentmarketplace_linkedin" plugin:
      | urn | title     |
      | 01  | Course 01 |
      | 02  | Course 02 |
      | 03  | Course 03 |
      | 04  | Course 04 |
      | 05  | Course 05 |
      | 06  | Course 06 |
      | 07  | Course 07 |
      | 08  | Course 08 |
      | 09  | Course 09 |
      | 10  | Course 10 |
      | 11  | Course 11 |
      | 12  | Course 12 |
      | 13  | Course 13 |
      | 14  | Course 14 |
      | 15  | Course 15 |
      | 16  | Course 16 |
      | 17  | Course 17 |
      | 18  | Course 18 |
      | 19  | Course 19 |
      | 20  | Course 20 |
      | 21  | Course 21 |
      | 22  | Course 22 |
      | 23  | Course 23 |
      | 24  | Course 24 |
      | 25  | Course 25 |
      | 26  | Course 26 |
      | 27  | Course 27 |
      | 28  | Course 28 |
      | 29  | Course 29 |
      | 30  | Course 30 |
      | 31  | Course 31 |
      | 32  | Course 32 |
      | 33  | Course 33 |
      | 34  | Course 34 |
      | 35  | Course 35 |
      | 36  | Course 36 |
      | 37  | Course 37 |
      | 38  | Course 38 |
      | 39  | Course 39 |
      | 40  | Course 40 |
      | 41  | Course 41 |
      | 42  | Course 42 |
      | 43  | Course 43 |
      | 44  | Course 44 |
      | 45  | Course 45 |
      | 46  | Course 46 |
      | 47  | Course 47 |
      | 48  | Course 48 |
      | 49  | Course 49 |
      | 50  | Course 50 |
      | 51  | Course 51 |
    When I navigate to the catalog import page for the "linkedin" content marketplace
    And I set the field "Sort by" to "Alphabetical"
    Then the field "Items per page" matches value "20"
    And I should see "20" rows in the tui datatable
    And I should see "Course 01"
    And I should see "Course 10"
    And I should see "Course 11"
    And I should see "Course 20"
    And I should not see "Course 21"
    When I set the field "Items per page" to "10"
    Then I should see "10" rows in the tui datatable
    And I should see "Course 01"
    And I should see "Course 10"
    And I should not see "Course 11"
    And I should not see "Course 20"
    And I should not see "Course 21"
    When I click on "2" "button" in the ".tui-paging__selector" "css_element"
    Then I should not see "Course 01"
    And I should not see "Course 10"
    And I should see "Course 11"
    And I should see "Course 20"
    And I should not see "Course 21"
    When I set the field "Page" to "3"
    And I click on "Go to page" "button"
    Then I should not see "Course 01"
    And I should not see "Course 10"
    And I should not see "Course 11"
    And I should not see "Course 20"
    And I should see "Course 21"
    When I click on "Page 1" "button"
    And I set the field "Items per page" to "20"
    And I toggle the selection of all rows in the tui select table
    And I click on "Page 2" "button"
    And I toggle the selection of all rows in the tui select table
    And I click on "Page 3" "button"
    And I toggle the selection of all rows in the tui select table
    And I set the field "Select category" to "Miscellaneous"
    Then I should see "51" items in the tui basket
    When I click on "Next: Review" "button"
    Then I should see "50" rows in the tui datatable
    And I should see "Course 01"
    And I should see "Course 10"
    And I should see "Course 11"
    And I should see "Course 20"
    And I should not see "Course 51"
    When I click on "Load more" "button"
    Then I should see "51" rows in the tui datatable
    And I should see "Course 01"
    And I should see "Course 51"
    When I click on "Back to catalogue" "button"
    Then I should see "20" rows in the tui datatable
    And I should not see "Course 21"
    When I click on "Page 3" "button"
    Then I should see "11" rows in the tui datatable
    And I should see "Course 51"

  Scenario: Learning objects catalog: Card content
    Given the following "learning objects" exist in "contentmarketplace_linkedin" plugin:
      | urn | title    | level        | asset_type    | time_to_complete | time_to_complete_unit |
      | A   | Course A | BEGINNER     | COURSE        | 30               | MINUTE                |
      | B   | Course B | INTERMEDIATE | LEARNING_PATH | 4                | HOUR                  |
      | C   | Course C | ADVANCED     | VIDEO         | 5                | SECOND                |
    And the following "classifications" exist in "contentmarketplace_linkedin" plugin:
      | urn        | name                                       | type    |
      | category:1 | J2EE                                       | LIBRARY |
      | category:2 | Hibernate is way better than active record | SUBJECT |
    And the following "classification relationships" exist in "contentmarketplace_linkedin" plugin:
      | parent_urn | child_urn  |
      | category:1 | category:2 |
    And the following "learning object classifications" exist in "contentmarketplace_linkedin" plugin:
      | learning_object_urn | classification_urn |
      | A                   | category:2         |
      | B                   | category:2         |
      | C                   | category:2         |
    When I navigate to the catalog import page for the "linkedin" content marketplace
    Then I should see the tui select table contains:
      | Course A |
      | Course B |
      | Course C |

    And ".tui-linkedInImportLearningItem__logoContainer img" "css_element" should exist

    # Course A
    And I should see "Hibernate is way better than active record" on row "1" of the tui select table
    And I should see "Content difficulty level Beginner" on row "1" of the tui select table
    And I should see "Time to complete content 30m" on row "1" of the tui select table
    And I should see "Type of content Course" on row "1" of the tui select table

    # Course B
    And I should see "Hibernate is way better than active record" on row "2" of the tui select table
    And I should see "Content difficulty level Intermediate" on row "2" of the tui select table
    And I should see "Time to complete content 4h 0m" on row "2" of the tui select table

    # Course C
    And I should see "Hibernate is way better than active record" on row "3" of the tui select table
    And I should see "Content difficulty level Advanced" on row "3" of the tui select table
    And I should see "Time to complete content 5s" on row "3" of the tui select table
    And I should see "Type of content Video" on row "3" of the tui select table

  Scenario: Learning objects catalog: Applying static filters
    Given the following "learning objects" exist in "contentmarketplace_linkedin" plugin:
      | urn | title     | description | asset_type    | time_to_complete | time_to_complete_unit |
      | A   | Course A  | PHP         | COURSE        | 30               | MINUTE                |
      | B   | Course B  | Photoshop   | LEARNING_PATH | 4                | HOUR                  |
      | C   | Course C  | People      | VIDEO         | 5                | SECOND                |
    When I navigate to the catalog import page for the "linkedin" content marketplace
    Then I should see the tui select table contains:
      | Course A |
      | Course B |
      | Course C |
    When I click on "Select all rows" tui "checkbox"
    Then I should see "3" in the ".tui-basket__selectedCount" "css_element"
    When I click on "Next: Review" "button"
    Then I should see "3" rows in the tui datatable
    And I should see "3 items" in the ".tui-contentMarketplaceImportCountAndFilters" "css_element"
    When I click on "< 10 mins" tui "checkbox" in the "Time to Complete" tui "tree"
    Then I should see "1 items matching \"< 10 mins\"" in the ".tui-contentMarketplaceImportCountAndFilters" "css_element"
    And I should see the tui select table contains:
      | Course C |
    When I set the field "Search" to "   people    "
    And I should see "1 items matching \"people\" AND \"< 10 mins\"" in the ".tui-contentMarketplaceImportCountAndFilters" "css_element"
    When I reload the page
    Then I should see the tui select table contains:
      | Course C |
    And I should see "1 items matching \"people\" AND \"< 10 mins\"" in the ".tui-contentMarketplaceImportCountAndFilters" "css_element"
    When I click on "Clear all" "button" in the ".tui-filterSidePanel" "css_element"
    And I set the field "Search" to "photoshop"
    Then I should see the tui select table contains:
      | Course B |
    And I should see "1 items" in the ".tui-contentMarketplaceImportCountAndFilters" "css_element"

  Scenario: Learning objects catalog: Applying static filters resets filters
    Given the following "learning objects" exist in "contentmarketplace_linkedin" plugin:
      | urn | title     |
      | 01  | Course 01 |
      | 02  | Course 02 |
      | 03  | Course 03 |
      | 04  | Course 04 |
      | 05  | Course 05 |
      | 06  | Course 06 |
      | 07  | Course 07 |
      | 08  | Course 08 |
      | 09  | Course 09 |
      | 10  | Course 10 |
      | 11  | Course 11 |
      | 12  | Course 12 |
      | 13  | Course 13 |
      | 14  | Course 14 |
      | 15  | Course 15 |
      | 16  | Course 16 |
      | 17  | Course 17 |
      | 18  | Course 18 |
      | 19  | Course 19 |
      | 20  | Course 20 |
      | 21  | Course 21 |
      | 22  | Course 22 |
      | 23  | Course 23 |
      | 24  | Course 24 |
      | 25  | Course 25 |
    When I navigate to the catalog import page for the "linkedin" content marketplace
    Then the field "Items per page" matches value "20"
    And I should see "20" rows in the tui datatable
    When I click on "Page 2" "button"
    Then I should see "5" rows in the tui datatable
    And I should see "2" in the ".tui-paging__selector-number--current" "css_element"
    When I set the field "Search" to "Course"
    Then I should see "20" rows in the tui datatable
    And I should see "1" in the ".tui-paging__selector-number--current" "css_element"

  Scenario: Learning objects catalog: Create courses from basket selection with categories
    Given I am on a totara site
    And the following "categories" exist:
      | name       | category | idnumber |
      | Category A | 0        | A        |
      | Category B | 0        | B        |
      | Category C | 0        | C        |
    And the following "learning objects" exist in "contentmarketplace_linkedin" plugin:
      | urn | title    | level        | asset_type    | time_to_complete | time_to_complete_unit |
      | A   | Course A | BEGINNER     | COURSE        | 30               | MINUTE                |
      | B   | Course B | INTERMEDIATE | LEARNING_PATH | 4                | HOUR                  |
      | C   | Course C | ADVANCED     | VIDEO         | 5                | SECOND                |
    And the following "classifications" exist in "contentmarketplace_linkedin" plugin:
      | urn        | name                                       | type    |
      | category:1 | J2EE                                       | LIBRARY |
      | category:2 | Hibernate is way better than active record | SUBJECT |
    And the following "classification relationships" exist in "contentmarketplace_linkedin" plugin:
      | parent_urn | child_urn  |
      | category:1 | category:2 |
    And the following "learning object classifications" exist in "contentmarketplace_linkedin" plugin:
      | learning_object_urn | classification_urn |
      | A                   | category:2         |
      | B                   | category:2         |
      | C                   | category:2         |
    When I navigate to the catalog import page for the "linkedin" content marketplace
    And I toggle the selection of row "1" of the tui select table
    And I toggle the selection of row "2" of the tui select table
    And I toggle the selection of row "3" of the tui select table
    And I set the field "Select category" to "Category A"
    Then I should see "3" items in the tui basket
    When I click on "Next: Review" "button"
    Then I should see the tui select table contains:
      | Course A |
      | Course B |
      | Course C |
    # Course A
    And I should see "Hibernate is way better than active record" on row "1" of the tui select table
    And I should see "Content difficulty level Beginner" on row "1" of the tui select table
    And I should see "Time to complete content 30m" on row "1" of the tui select table
    And I should see "Type of content Course" on row "1" of the tui select table
    And I should see "Category: Category A" on row "1" of the tui select table
    # Course B
    And I should see "Hibernate is way better than active record" on row "2" of the tui select table
    And I should see "Content difficulty level Intermediate" on row "2" of the tui select table
    And I should see "Time to complete content 4h 0m" on row "2" of the tui select table
    And I should see "Category: Category A" on row "2" of the tui select table
    # Course C
    And I should see "Hibernate is way better than active record" on row "3" of the tui select table
    And I should see "Content difficulty level Advanced" on row "3" of the tui select table
    And I should see "Time to complete content 5s" on row "3" of the tui select table
    And I should see "Type of content Video" on row "3" of the tui select table
    And I should see "Category: Category A" on row "3" of the tui select table

    When I toggle the selection of row "3" of the tui select table
    And I click on "Back to catalogue" "button"
    And I set the field "Select category" to "Category C"
    And I click on "Next: Review" "button"
    Then I should see "2" items in the tui basket

    When I click on "Edit course category" "button" in the ".tui-dataTableRow:nth-child(1)" "css_element"
    And I set the field "Assign to category" to "Category B"
    And I click on "Update" "button"
    Then I should see "Category: Category B" on row "1" of the tui select table
    Then I should see "Category: Category C" on row "2" of the tui select table

    When I click on "Create course(s)" "button"
    Then I should see "The courses have been successfully created" in the ".alert-success" "css_element"
    And I should see "Find learning" in the ".tw-catalog__title" "css_element"
    And I should see "Latest" in the ".tw-catalogResultsSort .tw-selectTree__current_label" "css_element"
    And I should see "Course B" in the ".tw-catalog__results" "css_element"
    And I should see "Category C" in the ".tw-catalog__results" "css_element"
    And I should see "Course A" in the ".tw-catalog__results" "css_element"
    And I should see "Category B" in the ".tw-catalog__results" "css_element"
    And I should not see "Course C"
    And I should not see "Category A"

    When I click on "Explore content marketplace" "link"
    Then I should see "LinkedIn Learning catalogue"
    And I should see "Appears in 1 course" on row "1" of the tui select table
    And I should see "Appears in 1 course" on row "2" of the tui select table
    When I click on "1 course" "button"
    Then I should see "Course A" in the ".tui-linkedInImportLearningItem__bar-coursesList" "css_element"
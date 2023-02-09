@totara @totara_engage @engage @totara_reportedcontent @javascript
Feature: Admin can remove or approve comments that have been reported.

  Background:
    Given I am on a totara site
    And the following "users" exist:
      | username | firstname | lastname | email             |
      | user1    | User1     | One      | user1@example.com |
      | user2    | User2     | Two      | user2@example.com |
    And the following "topics" exist in "totara_topic" plugin:
      | name    |
      | Topic 1 |
    And the following "articles" exist in "engage_article" plugin:
      | name      | username | content      | format       | access | topics  |
      | Article 1 | user1    | Test Article | FORMAT_PLAIN | PUBLIC | Topic 1 |
    And the following "playlists" exist in "totara_playlist" plugin:
      | name       | username | summary       | access | topics  |
      | Playlist 1 | user1    | Test Playlist | PUBLIC | Topic 1 |
    And the following "surveys" exist in "engage_survey" plugin:
      | question | username | access | topics  |
      | Survey 1 | user1    | PUBLIC | Topic 1 |
    And the following "comments" exist in "totara_comment" plugin:
      | name       | username | component       | area    | content          |
      | Article 1  | user1    | engage_article  | comment | article comment  |
      | Playlist 1 | user1    | totara_playlist | comment | playlist comment |
    And the following "resource reviews" exist in "totara_reportedcontent" plugin:
      | component      | name      | username |
      | engage_article | Article 1 | user2    |
      | engage_survey  | Survey 1  | user2    |
    And the following "comment reviews" exist in "totara_reportedcontent" plugin:
      | component       | area    | name       | comment          | username |
      | totara_playlist | comment | Playlist 1 | playlist comment | user2    |
      | engage_article  | comment | Article 1  | article comment  | user2    |

  Scenario: As an admin, I can choose to allow reported comments to remain.
    Given I log in as "admin"
    And I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "report-name" to "Inappropriate content"
    And I press "id_submitgroupstandard_addfilter"
    And I follow "Inappropriate content"

    When I follow "View This Report"
    Then I should see "article comment"
    And I should see "playlist comment"

    # Approve both
    When I click on "#reportedcontent tr:nth-child(3) [data-action='approve']" "css_element"
    And I click on "#reportedcontent tr:nth-child(4) [data-action='approve']" "css_element"
    Then I should see "Allowed"

    # Filter on "Allowed" and make sure our results show up
    When I set the field "reportedcontent-status" to "2"
    And I press "id_submitgroupstandard_addfilter"
    Then I should see "article comment"
    And I should see "playlist comment"

    # Go look at the playlist & article to see if the comment exists still
    When I view article "Article 1"
    And I click on "Comments" "link"
    Then I should see "article comment"

    When I view playlist "Playlist 1"
    And I click on "Comments" "link"
    Then I should see "playlist comment"

  Scenario: As an admin, I can choose to remove reported comments.
    Given I log in as "admin"
    And I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "report-name" to "Inappropriate content"
    And I press "id_submitgroupstandard_addfilter"
    And I follow "Inappropriate content"

    When I follow "View This Report"
    Then I should see "article comment"
    And I should see "playlist comment"

    # Remove both the reports
    When I click on "#reportedcontent tr:nth-child(3) [data-action='remove']" "css_element"
    And I press "Confirm"

    # Modal goes funky with behat, so refresh the page for the second remove
    And I press "id_submitgroupstandard_addfilter"
    And I click on "#reportedcontent tr:nth-child(3) [data-action='remove']" "css_element"
    And I press "Confirm"
    Then I should see "Removed"

    # Filter on "Removed" and make sure our results show up
    When I set the field "reportedcontent-status" to "1"
    And I press "id_submitgroupstandard_addfilter"
    Then I should see "article comment"
    And I should see "playlist comment"

    # Go look at the playlist & article to see if the comment exists still
    When I view article "Article 1"
    And I click on "Comments" "link"
    Then I should not see "article comment"
    And I should see "This comment has been removed."

    When I view playlist "Playlist 1"
    And I click on "Comments" "link"
    Then I should not see "playlist comment"
    And I should see "This comment has been removed."

  Scenario: As an admin, I can remove reported resources & surveys.
    Given I log in as "admin"
    And I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "report-name" to "Inappropriate content"
    And I press "id_submitgroupstandard_addfilter"
    And I follow "Inappropriate content"
    And I follow "View This Report"

    # Remove both the survey & resource report
    When I click on "#reportedcontent tr:nth-child(1) [data-action='remove']" "css_element"
    And I press "Confirm"

    # Modal goes funky with behat, so refresh the page for the second remove
    And I press "id_submitgroupstandard_addfilter"
    And I click on "#reportedcontent tr:nth-child(1) [data-action='remove']" "css_element"
    And I press "Confirm"
    Then I should see "Removed"

    # Filter on "Removed" and make sure our results show up
    When I set the field "reportedcontent-status" to "1"
    And I press "id_submitgroupstandard_addfilter"
    Then I should see "Survey 1"
    And I should see "Article 1"

    # Now log in as user1 and check that they can't see it anymore
    When I log out
    And I log in as "user1"
    And I click on "Your Library" in the totara menu
    Then I should not see "Article 1"
    And I should not see "Survey 1"

  Scenario: The remove and approve buttons also work with manually created user reports.
    Given I log in as "admin"
    And I navigate to "Manage user reports" node in "Site administration > Reports"
    And I press "Create report"
    And I set the field "search_input" to "Inappropriate content"
    And I click on "button.tw-selectSearchText__btn" "css_element"
    And I click on "div[data-tw-grid-item-id=\"reportedcontent-source\"]" "css_element"
    And I wait for pending js
    And I press "Create and edit"
    And I follow "Columns"
    And I set the field "id_newcolumns" to "Date reviewed"
    And I press "Save changes"
    And I follow "View This Report"
    And I set the field "reportedcontent-status_op" to "1"
    And I press "id_submitgroupstandard_addfilter"
    Then "//table/tbody/tr[1]/td[contains(concat(' ',normalize-space(@class),' '),'reportedcontent_time_reviewed')]/div[text()]" "xpath_element" should not exist

    When I click on "table[data-source='rb_source_reportedcontent'] tr:nth-child(1) [data-action='remove']" "css_element"
    And I press "Confirm"
    And I wait for pending js
    Then I should see "Removed"
    Then "//table/tbody/tr[1]/td[contains(concat(' ',normalize-space(@class),' '),'reportedcontent_time_reviewed')]/div[text()]" "xpath_element" should exist

    # Modal goes funky with behat, so refresh the page for the second action
    When I set the field "reportedcontent-status_op" to "1"
    And I press "id_submitgroupstandard_addfilter"
    Then "//table/tbody/tr[1]/td[contains(concat(' ',normalize-space(@class),' '),'reportedcontent_time_reviewed')]/div[text()]" "xpath_element" should not exist

    When I click on "table[data-source='rb_source_reportedcontent'] tr:nth-child(1) [data-action='approve']" "css_element"
    And I wait for pending js
    Then I should see "Allowed"

  Scenario: The date reviewed column is automatically updated when I approve or remove a review.
    Given I log in as "admin"
    And I navigate to "Manage embedded reports" node in "Site administration > Reports"
    And I set the field "report-name" to "Inappropriate content"
    And I press "id_submitgroupstandard_addfilter"
    And I follow "Inappropriate content"
    And I follow "Columns"
    And I set the field "id_newcolumns" to "Date reviewed"
    And I press "Save changes"
    And I follow "View This Report"

    # Column should start out empty
    Then "//table/tbody/tr[1]/td[contains(concat(' ',normalize-space(@class),' '),'reportedcontent_time_reviewed')]/div[text()]" "xpath_element" should not exist

    # When an action is taken, the field should update and no longer be empty
    When I click on "table[data-source='rb_source_reportedcontent'] tr:nth-child(1) [data-action='approve']" "css_element"
    And I wait for pending js
    Then "//table/tbody/tr[1]/td[contains(concat(' ',normalize-space(@class),' '),'reportedcontent_time_reviewed')]/div[text()]" "xpath_element" should exist

    # Check that the remove button also updates the column
    When I reload the page
    Then "//table/tbody/tr[1]/td[contains(concat(' ',normalize-space(@class),' '),'reportedcontent_time_reviewed')]/div[text()]" "xpath_element" should not exist

    When I click on "table[data-source='rb_source_reportedcontent'] tr:nth-child(1) [data-action='remove']" "css_element"
    And I press "Confirm"
    And I wait for pending js
    Then "//table/tbody/tr[1]/td[contains(concat(' ',normalize-space(@class),' '),'reportedcontent_time_reviewed')]/div[text()]" "xpath_element" should exist

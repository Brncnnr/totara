@totara @totara_hierarchy @totara_customfield
Feature: Hierarchy index page sql splitter and joiner
  The index page needs to work with more than 60 custom fields defined.

  @javascript
  Scenario: Check that it doesn't break with 61 custom fields
    Given I am on a totara site
    And the following "organisation" frameworks exist:
      | fullname      | idnumber | description           |
      | orgframework1 | FW001    | Framework description |
    And the following hierarchy types exist:
      | hierarchy    | idnumber | fullname |
      | organisation | orgtype1 | orgtype1 |
    And the following hierarchy type custom fields exist:
      | hierarchy    | typeidnumber | type | fullname            | shortname   | value |
      | organisation | orgtype1     | text | textinput1fullname  | textinput1  |       |
      | organisation | orgtype1     | text | textinput2fullname  | textinput2  |       |
      | organisation | orgtype1     | text | textinput3fullname  | textinput3  |       |
      | organisation | orgtype1     | text | textinput4fullname  | textinput4  |       |
      | organisation | orgtype1     | text | textinput5fullname  | textinput5  |       |
      | organisation | orgtype1     | text | textinput6fullname  | textinput6  |       |
      | organisation | orgtype1     | text | textinput7fullname  | textinput7  |       |
      | organisation | orgtype1     | text | textinput8fullname  | textinput8  |       |
      | organisation | orgtype1     | text | textinput9fullname  | textinput9  |       |
      | organisation | orgtype1     | text | textinput10fullname | textinput10 |       |
      | organisation | orgtype1     | text | textinput11fullname | textinput11 |       |
      | organisation | orgtype1     | text | textinput12fullname | textinput12 |       |
      | organisation | orgtype1     | text | textinput13fullname | textinput13 |       |
      | organisation | orgtype1     | text | textinput14fullname | textinput14 |       |
      | organisation | orgtype1     | text | textinput15fullname | textinput15 |       |
      | organisation | orgtype1     | text | textinput16fullname | textinput16 |       |
      | organisation | orgtype1     | text | textinput17fullname | textinput17 |       |
      | organisation | orgtype1     | text | textinput18fullname | textinput18 |       |
      | organisation | orgtype1     | text | textinput19fullname | textinput19 |       |
      | organisation | orgtype1     | text | textinput20fullname | textinput20 |       |
      | organisation | orgtype1     | text | textinput21fullname | textinput21 |       |
      | organisation | orgtype1     | text | textinput22fullname | textinput22 |       |
      | organisation | orgtype1     | text | textinput23fullname | textinput23 |       |
      | organisation | orgtype1     | text | textinput24fullname | textinput24 |       |
      | organisation | orgtype1     | text | textinput25fullname | textinput25 |       |
      | organisation | orgtype1     | text | textinput26fullname | textinput26 |       |
      | organisation | orgtype1     | text | textinput27fullname | textinput27 |       |
      | organisation | orgtype1     | text | textinput28fullname | textinput28 |       |
      | organisation | orgtype1     | text | textinput29fullname | textinput29 |       |
      | organisation | orgtype1     | text | textinput30fullname | textinput30 |       |
      | organisation | orgtype1     | text | textinput31fullname | textinput31 |       |
      | organisation | orgtype1     | text | textinput32fullname | textinput32 |       |
      | organisation | orgtype1     | text | textinput33fullname | textinput33 |       |
      | organisation | orgtype1     | text | textinput34fullname | textinput34 |       |
      | organisation | orgtype1     | text | textinput35fullname | textinput35 |       |
      | organisation | orgtype1     | text | textinput36fullname | textinput36 |       |
      | organisation | orgtype1     | text | textinput37fullname | textinput37 |       |
      | organisation | orgtype1     | text | textinput38fullname | textinput38 |       |
      | organisation | orgtype1     | text | textinput39fullname | textinput39 |       |
      | organisation | orgtype1     | text | textinput40fullname | textinput40 |       |
      | organisation | orgtype1     | text | textinput41fullname | textinput41 |       |
      | organisation | orgtype1     | text | textinput42fullname | textinput42 |       |
      | organisation | orgtype1     | text | textinput43fullname | textinput43 |       |
      | organisation | orgtype1     | text | textinput44fullname | textinput44 |       |
      | organisation | orgtype1     | text | textinput45fullname | textinput45 |       |
      | organisation | orgtype1     | text | textinput46fullname | textinput46 |       |
      | organisation | orgtype1     | text | textinput47fullname | textinput47 |       |
      | organisation | orgtype1     | text | textinput48fullname | textinput48 |       |
      | organisation | orgtype1     | text | textinput49fullname | textinput49 |       |
      | organisation | orgtype1     | text | textinput50fullname | textinput50 |       |
      | organisation | orgtype1     | text | textinput51fullname | textinput51 |       |
      | organisation | orgtype1     | text | textinput52fullname | textinput52 |       |
      | organisation | orgtype1     | text | textinput53fullname | textinput53 |       |
      | organisation | orgtype1     | text | textinput54fullname | textinput54 |       |
      | organisation | orgtype1     | text | textinput55fullname | textinput55 |       |
      | organisation | orgtype1     | text | textinput56fullname | textinput56 |       |
      | organisation | orgtype1     | text | textinput57fullname | textinput57 |       |
      | organisation | orgtype1     | text | textinput58fullname | textinput58 |       |
      | organisation | orgtype1     | text | textinput59fullname | textinput59 |       |
      | organisation | orgtype1     | text | textinput60fullname | textinput60 |       |
      | organisation | orgtype1     | text | textinput61fullname | textinput61 |       |

    When I log in as "admin"
    And I navigate to "Manage organisations" node in "Site administration > Organisations"
    And I click on "orgframework1" "link"
    And I press "Add new organisation"
    And I set the following fields to these values:
      | fullname | org1     |
      | typeid   | orgtype1 |
    And I press "Save changes"
    And I click on "Edit" "link"
    And I set the following fields to these values:
      | textinput1fullname  | value1  |
      | textinput2fullname  | value2  |
      | textinput3fullname  | value3  |
      | textinput4fullname  | value4  |
      | textinput5fullname  | value5  |
      | textinput6fullname  | value6  |
      | textinput7fullname  | value7  |
      | textinput8fullname  | value8  |
      | textinput9fullname  | value9  |
      | textinput10fullname | value10 |
      | textinput11fullname | value11 |
      | textinput12fullname | value12 |
      | textinput13fullname | value13 |
      | textinput14fullname | value14 |
      | textinput15fullname | value15 |
      | textinput16fullname | value16 |
      | textinput17fullname | value17 |
      | textinput18fullname | value18 |
      | textinput19fullname | value19 |
      | textinput20fullname | value20 |
      | textinput21fullname | value21 |
      | textinput22fullname | value22 |
      | textinput23fullname | value23 |
      | textinput24fullname | value24 |
      | textinput25fullname | value25 |
      | textinput26fullname | value26 |
      | textinput27fullname | value27 |
      | textinput28fullname | value28 |
      | textinput29fullname | value29 |
      | textinput30fullname | value30 |
      | textinput31fullname | value31 |
      | textinput32fullname | value32 |
      | textinput33fullname | value33 |
      | textinput34fullname | value34 |
      | textinput35fullname | value35 |
      | textinput36fullname | value36 |
      | textinput37fullname | value37 |
      | textinput38fullname | value38 |
      | textinput39fullname | value39 |
      | textinput40fullname | value40 |
      | textinput41fullname | value41 |
      | textinput42fullname | value42 |
      | textinput43fullname | value43 |
      | textinput44fullname | value44 |
      | textinput45fullname | value45 |
      | textinput46fullname | value46 |
      | textinput47fullname | value47 |
      | textinput48fullname | value48 |
      | textinput49fullname | value49 |
      | textinput50fullname | value50 |
      | textinput51fullname | value51 |
      | textinput52fullname | value52 |
      | textinput53fullname | value53 |
      | textinput54fullname | value54 |
      | textinput55fullname | value55 |
      | textinput56fullname | value56 |
      | textinput57fullname | value57 |
      | textinput58fullname | value58 |
      | textinput59fullname | value59 |
      | textinput60fullname | value60 |
      | textinput61fullname | value61 |
    And I press "Save changes"

    Then I navigate to "Manage organisations" node in "Site administration > Organisations"
    And I click on "orgframework1" "link"
    And I should see "org1"
    And I should see "Type: orgtype1"
    And I should see "textinput1fullname: value1"
    And I should see "textinput2fullname: value2"
    And I should see "textinput3fullname: value3"
    And I should see "textinput4fullname: value4"
    And I should see "textinput5fullname: value5"
    And I should see "textinput6fullname: value6"
    And I should see "textinput7fullname: value7"
    And I should see "textinput8fullname: value8"
    And I should see "textinput9fullname: value9"
    And I should see "textinput10fullname: value10"
    And I should see "textinput11fullname: value11"
    And I should see "textinput12fullname: value12"
    And I should see "textinput13fullname: value13"
    And I should see "textinput14fullname: value14"
    And I should see "textinput15fullname: value15"
    And I should see "textinput16fullname: value16"
    And I should see "textinput17fullname: value17"
    And I should see "textinput18fullname: value18"
    And I should see "textinput19fullname: value19"
    And I should see "textinput20fullname: value20"
    And I should see "textinput21fullname: value21"
    And I should see "textinput22fullname: value22"
    And I should see "textinput23fullname: value23"
    And I should see "textinput24fullname: value24"
    And I should see "textinput25fullname: value25"
    And I should see "textinput26fullname: value26"
    And I should see "textinput27fullname: value27"
    And I should see "textinput28fullname: value28"
    And I should see "textinput29fullname: value29"
    And I should see "textinput30fullname: value30"
    And I should see "textinput31fullname: value31"
    And I should see "textinput32fullname: value32"
    And I should see "textinput33fullname: value33"
    And I should see "textinput34fullname: value34"
    And I should see "textinput35fullname: value35"
    And I should see "textinput36fullname: value36"
    And I should see "textinput37fullname: value37"
    And I should see "textinput38fullname: value38"
    And I should see "textinput39fullname: value39"
    And I should see "textinput40fullname: value40"
    And I should see "textinput41fullname: value41"
    And I should see "textinput42fullname: value42"
    And I should see "textinput43fullname: value43"
    And I should see "textinput44fullname: value44"
    And I should see "textinput45fullname: value45"
    And I should see "textinput46fullname: value46"
    And I should see "textinput47fullname: value47"
    And I should see "textinput48fullname: value48"
    And I should see "textinput49fullname: value49"
    And I should see "textinput50fullname: value50"
    And I should see "textinput51fullname: value51"
    And I should see "textinput52fullname: value52"
    And I should see "textinput53fullname: value53"
    And I should see "textinput54fullname: value54"
    And I should see "textinput55fullname: value55"
    And I should see "textinput56fullname: value56"
    And I should see "textinput57fullname: value57"
    And I should see "textinput58fullname: value58"
    And I should see "textinput59fullname: value59"
    And I should see "textinput60fullname: value60"
    And I should see "textinput61fullname: value61"

    Then I set the field "search" to "value1"
    And I press "Go"
    Then I should see "org1"
    And I should see "Type: orgtype1"
    And I should see "textinput1fullname: value1"
    # Others skipped, checked above
    And I should see "textinput17fullname: value17"
    # Others skipped, checked above
    And I should see "textinput61fullname: value61"

    Then I set the field "search" to "value17"
    And I press "Go"
    Then I should see "org1"
    And I should see "Type: orgtype1"
    And I should see "textinput1fullname: value1"
    # Others skipped, checked above
    And I should see "textinput17fullname: value17"
    # Others skipped, checked above
    And I should see "textinput61fullname: value61"

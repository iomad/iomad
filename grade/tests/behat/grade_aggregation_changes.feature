@core @core_grades
Feature: Changing the aggregation of an item affects its weight and extra credit definition
  In order to switch to another aggregation method
  As an teacher
  I need to be able to edit the grade category settings

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1        |
    And the following "grade categories" exist:
      | fullname      | course | aggregation |
      | Cat mean      | C1     | 0           |
      | Cat median    | C1     | 2           |
      | Cat min       | C1     | 4           |
      | Cat max       | C1     | 6           |
      | Cat mode      | C1     | 8           |
      | Cat weighted  | C1     | 10          |
      | Cat weighted2 | C1     | 10          |
      | Cat simple    | C1     | 11          |
      | Cat ec        | C1     | 12          |
      | Cat natural   | C1     | 13          |
    And the following "grade items" exist:
      | itemname  | course | category    | aggregationcoef | aggregationcoef2 | weightoverride |
      | Item a1   | C1     | ?           | 0               | 0                | 0              |
      | Item a2   | C1     | ?           | 0               | 0.40             | 1              |
      | Item a3   | C1     | ?           | 1               | 0.10             | 1              |
      | Item a4   | C1     | ?           | 1               | 0                | 0              |
      | Item b1   | C1     | Cat natural | 0               | 0                | 0              |
      | Item b2   | C1     | Cat natural | 0               | 0.40             | 1              |
      | Item b3   | C1     | Cat natural | 1               | 0.10             | 1              |
      | Item b4   | C1     | Cat natural | 1               | 0                | 0              |
    And I log in as "admin"
    And I set the following administration settings values:
      | grade_aggregations_visible | Mean of grades,Weighted mean of grades,Simple weighted mean of grades,Mean of grades (with extra credits),Median of grades,Lowest grade,Highest grade,Mode of grades,Natural |
    And I am on "Course 1" course homepage
    And I navigate to "View > Grader report" in the course gradebook
    And I turn editing mode on
    And I follow "Edit   Cat mean"
    And I set the following fields to these values:
      | Weight adjusted     | 1  |
      | Weight              | 20 |
      | Extra credit        | 0  |
    And I press "Save changes"
    And I follow "Edit   Cat median"
    And I set the following fields to these values:
      | Weight adjusted     | 1  |
      | Weight              | 5  |
      | Extra credit        | 0  |
    And I press "Save changes"
    And I follow "Edit   Cat min"
    And I set the following fields to these values:
      | Weight adjusted     | 0  |
      | Weight              | 0  |
      | Extra credit        | 1  |
    And I press "Save changes"
    And I follow "Edit   Item a1"
    And the field "Weight adjusted" matches value "0"
    And the field "Extra credit" matches value "0"
    And I press "Cancel"
    And I follow "Edit   Item a2"
    And the field "Weight adjusted" matches value "1"
    And the field "id_aggregationcoef2" matches value "40.0"
    And the field "Extra credit" matches value "0"
    And I press "Cancel"
    And I follow "Edit   Item a3"
    And the field "Weight adjusted" matches value "1"
    And the field "id_aggregationcoef2" matches value "10.0"
    And the field "Extra credit" matches value "1"
    And I press "Cancel"
    And I follow "Edit   Item a4"
    And the field "Weight adjusted" matches value "0"
    And the field "Extra credit" matches value "1"
    And I press "Cancel"
    And I follow "Edit   Item b1"
    And the field "Weight adjusted" matches value "0"
    And the field "Extra credit" matches value "0"
    And I press "Cancel"
    And I follow "Edit   Item b2"
    And the field "Weight adjusted" matches value "1"
    And the field "id_aggregationcoef2" matches value "40.0"
    And the field "Extra credit" matches value "0"
    And I press "Cancel"
    And I follow "Edit   Item b3"
    And the field "Weight adjusted" matches value "1"
    And the field "id_aggregationcoef2" matches value "10.0"
    And the field "Extra credit" matches value "1"
    And I press "Cancel"
    And I follow "Edit   Item b4"
    And the field "Weight adjusted" matches value "0"
    And the field "Extra credit" matches value "1"
    And I press "Cancel"

  Scenario: Switching a category from Natural aggregation to Mean of grades and back
    Given I follow "Edit   Course 1"
    And I set the field "Aggregation" to "Mean of grades"
    When I press "Save changes"
    And I follow "Edit   Item a1"
    Then I should not see "Weight adjusted"
    And I should not see "Weight"
    And I should not see "Extra credit"
    And I press "Cancel"
    And I follow "Edit   Item a2"
    And I should not see "Weight adjusted"
    And I should not see "Weight"
    And I should not see "Extra credit"
    And I press "Cancel"
    And I follow "Edit   Item a3"
    And I should not see "Weight adjusted"
    And I should not see "Weight"
    And I should not see "Extra credit"
    And I press "Cancel"
    And I follow "Edit   Item a4"
    And I should not see "Weight adjusted"
    And I should not see "Weight"
    And I should not see "Extra credit"
    And I press "Cancel"
    And I follow "Edit   Cat mean"
    And I expand all fieldsets
    And I should not see "Weight adjusted"
    And I should not see "Weight" in the "#id_headerparent" "css_element"
    And I should not see "Extra credit"
    And I press "Cancel"
    And I follow "Edit   Cat median"
    And I expand all fieldsets
    And I should not see "Weight adjusted"
    And I should not see "Weight" in the "#id_headerparent" "css_element"
    And I should not see "Extra credit"
    And I press "Cancel"
    And I follow "Edit   Cat min"
    And I expand all fieldsets
    And I should not see "Weight adjusted"
    And I should not see "Weight" in the "#id_headerparent" "css_element"
    And I should not see "Extra credit"
    And I press "Cancel"
    And I follow "Edit   Cat natural"
    And I set the field "Aggregation" to "Mean of grades"
    And I press "Save changes"
    And I follow "Edit   Item b1"
    And I should not see "Weight adjusted"
    And I should not see "Weight"
    And I should not see "Extra credit"
    And I press "Cancel"
    And I follow "Edit   Item b2"
    And I should not see "Weight adjusted"
    And I should not see "Weight"
    And I should not see "Extra credit"
    And I press "Cancel"
    And I follow "Edit   Item b3"
    And I should not see "Weight adjusted"
    And I should not see "Weight"
    And I should not see "Extra credit"
    And I press "Cancel"
    And I follow "Edit   Item b4"
    And I should not see "Weight adjusted"
    And I should not see "Weight"
    And I should not see "Extra credit"
    And I press "Cancel"
    # Switching back.
    And I follow "Edit   Course 1"
    And I set the field "Aggregation" to "Natural"
    And I press "Save changes"
    And I follow "Edit   Item a1"
    And the field "Weight adjusted" matches value "0"
    And the field "Extra credit" matches value "0"
    And I press "Cancel"
    And I follow "Edit   Item a2"
    And the field "Weight adjusted" matches value "0"
    And the field "Extra credit" matches value "0"
    And I press "Cancel"
    And I follow "Edit   Item a3"
    And the field "Weight adjusted" matches value "0"
    And the field "Extra credit" matches value "0"
    And I press "Cancel"
    And I follow "Edit   Item a4"
    And the field "Weight adjusted" matches value "0"
    And the field "Extra credit" matches value "0"
    And I press "Cancel"
    And I follow "Edit   Cat mean"
    And I expand all fieldsets
    And the field "Weight adjusted" matches value "0"
    And the field "Extra credit" matches value "0"
    And I press "Cancel"
    And I follow "Edit   Cat median"
    And I expand all fieldsets
    And the field "Weight adjusted" matches value "0"
    And the field "Extra credit" matches value "0"
    And I press "Cancel"
    And I follow "Edit   Cat min"
    And I expand all fieldsets
    And the field "Weight adjusted" matches value "0"
    And the field "Extra credit" matches value "0"
    And I press "Cancel"
    And I follow "Edit   Cat natural"
    And I set the field "Aggregation" to "Natural"
    And I press "Save changes"
    And I follow "Edit   Item b1"
    And the field "Weight adjusted" matches value "0"
    And the field "Extra credit" matches value "0"
    And I press "Cancel"
    And I follow "Edit   Item b2"
    And the field "Weight adjusted" matches value "0"
    And the field "Extra credit" matches value "0"
    And I press "Cancel"
    And I follow "Edit   Item b3"
    And the field "Weight adjusted" matches value "0"
    And the field "Extra credit" matches value "0"
    And I press "Cancel"
    And I follow "Edit   Item b4"
    And the field "Weight adjusted" matches value "0"
    And the field "Extra credit" matches value "0"
    And I press "Cancel"

  Scenario: Switching a category from Natural aggregation to Weighted mean of grades and back
    Given I follow "Edit   Course 1"
    And I set the field "Aggregation" to "Weighted mean of grades"
    When I press "Save changes"
    And I follow "Edit   Item a1"
    Then I should not see "Weight adjusted"
    And I should not see "Extra credit"
    And the field "Item weight" matches value "1"
    And I press "Cancel"
    And I follow "Edit   Item a2"
    And I should not see "Weight adjusted"
    And I should not see "Extra credit"
    And the field "Item weight" matches value "1"
    And I press "Cancel"
    And I follow "Edit   Item a3"
    And I should not see "Weight adjusted"
    And I should not see "Extra credit"
    And the field "Item weight" matches value "1"
    And I press "Cancel"
    And I follow "Edit   Item a4"
    And I should not see "Weight adjusted"
    And I should not see "Extra credit"
    And the field "Item weight" matches value "1"
    And I press "Cancel"
    And I follow "Edit   Cat mean"
    And I expand all fieldsets
    And I should not see "Weight adjusted"
    And I should not see "Extra credit"
    And the field "Item weight" matches value "1"
    And I press "Cancel"
    And I follow "Edit   Cat median"
    And I expand all fieldsets
    And I should not see "Weight adjusted"
    And I should not see "Extra credit"
    And the field "Item weight" matches value "1"
    And I press "Cancel"
    And I follow "Edit   Cat min"
    And I expand all fieldsets
    And I should not see "Weight adjusted"
    And I should not see "Extra credit"
    And the field "Item weight" matches value "1"
    And I press "Cancel"
    And I follow "Edit   Cat natural"
    And I set the field "Aggregation" to "Weighted mean of grades"
    And I press "Save changes"
    And I follow "Edit   Item b1"
    And I should not see "Weight adjusted"
    And I should not see "Extra credit"
    And the field "Item weight" matches value "1"
    And I press "Cancel"
    And I follow "Edit   Item b2"
    And I should not see "Weight adjusted"
    And I should not see "Extra credit"
    And the field "Item weight" matches value "1"
    And I press "Cancel"
    And I follow "Edit   Item b3"
    And I should not see "Weight adjusted"
    And I should not see "Extra credit"
    And the field "Item weight" matches value "1"
    And I press "Cancel"
    And I follow "Edit   Item b4"
    And I should not see "Weight adjusted"
    And I should not see "Extra credit"
    And the field "Item weight" matches value "1"
    And I press "Cancel"
    # Switching back.
    And I follow "Edit   Course 1"
    And I set the field "Aggregation" to "Natural"
    And I press "Save changes"
    And I follow "Edit   Item a1"
    And the field "Weight adjusted" matches value "0"
    And the field "Extra credit" matches value "0"
    And I press "Cancel"
    And I follow "Edit   Item a2"
    And the field "Weight adjusted" matches value "0"
    And the field "Extra credit" matches value "0"
    And I press "Cancel"
    And I follow "Edit   Item a3"
    And the field "Weight adjusted" matches value "0"
    And the field "Extra credit" matches value "0"
    And I press "Cancel"
    And I follow "Edit   Item a4"
    And the field "Weight adjusted" matches value "0"
    And the field "Extra credit" matches value "0"
    And I press "Cancel"
    And I follow "Edit   Cat mean"
    And I expand all fieldsets
    And the field "Weight adjusted" matches value "0"
    And the field "Extra credit" matches value "0"
    And I press "Cancel"
    And I follow "Edit   Cat median"
    And I expand all fieldsets
    And the field "Weight adjusted" matches value "0"
    And the field "Extra credit" matches value "0"
    And I press "Cancel"
    And I follow "Edit   Cat min"
    And I expand all fieldsets
    And the field "Weight adjusted" matches value "0"
    And the field "Extra credit" matches value "0"
    And I press "Cancel"
    And I follow "Edit   Cat natural"
    And I set the field "Aggregation" to "Natural"
    And I press "Save changes"
    And I follow "Edit   Item b1"
    And the field "Weight adjusted" matches value "0"
    And the field "Extra credit" matches value "0"
    And I press "Cancel"
    And I follow "Edit   Item b2"
    And the field "Weight adjusted" matches value "0"
    And the field "Extra credit" matches value "0"
    And I press "Cancel"
    And I follow "Edit   Item b3"
    And the field "Weight adjusted" matches value "0"
    And the field "Extra credit" matches value "0"
    And I press "Cancel"
    And I follow "Edit   Item b4"
    And the field "Weight adjusted" matches value "0"
    And the field "Extra credit" matches value "0"
    And I press "Cancel"

  @javascript
  Scenario: Switching grade items between categories
    # Move to same aggregation (Natural).
    Given I navigate to "Setup > Gradebook setup" in the course gradebook
    And I set the field "Select Item a1" to "1"
    And I set the field "Select Item a2" to "1"
    And I set the field "Select Item a3" to "1"
    And I set the field "Select Item a4" to "1"
    When I select "Cat natural" from the "Move selected items to" singleselect
    And I navigate to "View > Grader report" in the course gradebook
    And I follow "Edit   Item a1"
    Then the field "Weight adjusted" matches value "0"
    And the field "Extra credit" matches value "0"
    And I press "Cancel"
    And I follow "Edit   Item a2"
    And the field "Weight adjusted" matches value "1"
    And the field "id_aggregationcoef2" matches value "40.0"
    And the field "Extra credit" matches value "0"
    And I press "Cancel"
    And I follow "Edit   Item a3"
    And the field "Weight adjusted" matches value "1"
    And the field "id_aggregationcoef2" matches value "10.0"
    And the field "Extra credit" matches value "1"
    And I press "Cancel"
    And I follow "Edit   Item a4"
    And the field "Weight adjusted" matches value "0"
    And the field "Extra credit" matches value "1"
    And I press "Cancel"
    # Move to Mean of grades (with extra credit).
    And I navigate to "Setup > Gradebook setup" in the course gradebook
    And I set the field "Select Item a1" to "1"
    And I set the field "Select Item a2" to "1"
    And I set the field "Select Item a3" to "1"
    And I set the field "Select Item a4" to "1"
    And I select "Cat ec" from the "Move selected items to" singleselect
    And I navigate to "View > Grader report" in the course gradebook
    And I follow "Edit   Item a1"
    And the field "Extra credit" matches value "0"
    And I press "Cancel"
    And I follow "Edit   Item a2"
    And the field "Extra credit" matches value "0"
    And I press "Cancel"
    And I follow "Edit   Item a3"
    And the field "Extra credit" matches value "1"
    And I press "Cancel"
    And I follow "Edit   Item a4"
    And the field "Extra credit" matches value "1"
    And I press "Cancel"
    # Move to Simple weight mean of grades.
    And I navigate to "Setup > Gradebook setup" in the course gradebook
    And I set the field "Select Item a1" to "1"
    And I set the field "Select Item a2" to "1"
    And I set the field "Select Item a3" to "1"
    And I set the field "Select Item a4" to "1"
    And I select "Cat simple" from the "Move selected items to" singleselect
    And I navigate to "View > Grader report" in the course gradebook
    And I follow "Edit   Item a1"
    And the field "Extra credit" matches value "0"
    And I press "Cancel"
    And I follow "Edit   Item a2"
    And the field "Extra credit" matches value "0"
    And I press "Cancel"
    And I follow "Edit   Item a3"
    And the field "Extra credit" matches value "1"
    And I press "Cancel"
    And I follow "Edit   Item a4"
    And the field "Extra credit" matches value "1"
    And I press "Cancel"
    # Move to Weighted mean of grades.
    And I navigate to "Setup > Gradebook setup" in the course gradebook
    And I set the field "Select Item a1" to "1"
    And I set the field "Select Item a2" to "1"
    And I set the field "Select Item a3" to "1"
    And I set the field "Select Item a4" to "1"
    And I select "Cat weighted" from the "Move selected items to" singleselect
    And I navigate to "View > Grader report" in the course gradebook
    And I follow "Edit   Item a1"
    And the field "Item weight" matches value "1"
    And I set the field "Item weight" to "2"
    And I press "Save changes"
    And I follow "Edit   Item a2"
    And the field "Item weight" matches value "1"
    And I set the field "Item weight" to "5"
    And I press "Save changes"
    And I follow "Edit   Item a3"
    And the field "Item weight" matches value "1"
    And I set the field "Item weight" to "8"
    And I press "Save changes"
    And I follow "Edit   Item a4"
    And the field "Item weight" matches value "1"
    And I set the field "Item weight" to "11"
    And I press "Save changes"
    # Move to same (Weighted mean of grades).
    And I navigate to "Setup > Gradebook setup" in the course gradebook
    And I set the field "Select Item a1" to "1"
    And I set the field "Select Item a2" to "1"
    And I set the field "Select Item a3" to "1"
    And I set the field "Select Item a4" to "1"
    And I select "Cat weighted2" from the "Move selected items to" singleselect
    And I wait "2" seconds
    And I navigate to "View > Grader report" in the course gradebook
    And I follow "Edit   Item a1"
    And the field "Item weight" matches value "2"
    And I press "Save changes"
    And I follow "Edit   Item a2"
    And the field "Item weight" matches value "5"
    And I press "Save changes"
    And I follow "Edit   Item a3"
    And the field "Item weight" matches value "8"
    And I press "Save changes"
    And I follow "Edit   Item a4"
    And the field "Item weight" matches value "11"
    And I press "Save changes"
    # Move back to Natural.
    And I navigate to "Setup > Gradebook setup" in the course gradebook
    And I set the field "Select Item a1" to "1"
    And I set the field "Select Item a2" to "1"
    And I set the field "Select Item a3" to "1"
    And I set the field "Select Item a4" to "1"
    And I select "Course 1" from the "Move selected items to" singleselect
    And I navigate to "View > Grader report" in the course gradebook
    And I follow "Edit   Item a1"
    And the field "Weight adjusted" matches value "0"
    And the field "Extra credit" matches value "0"
    And I press "Cancel"
    And I follow "Edit   Item a2"
    And the field "Weight adjusted" matches value "0"
    And the field "Extra credit" matches value "0"
    And I press "Cancel"
    And I follow "Edit   Item a3"
    And the field "Weight adjusted" matches value "0"
    And the field "Extra credit" matches value "0"
    And I press "Cancel"
    And I follow "Edit   Item a4"
    And the field "Weight adjusted" matches value "0"
    And the field "Extra credit" matches value "0"
    And I press "Cancel"

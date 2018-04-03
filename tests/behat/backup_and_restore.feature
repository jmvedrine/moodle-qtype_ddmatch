@qtype @qtype_ddmatch
Feature: Test duplicating a quiz containing a Drag and drop matching question
  As a teacher
  In order re-use my courses containing Drag and drop matching questions
  I need to be able to backup and restore them

  Background:
    Given the following "users" exist:
      | username | firstname | lastname | email               |
      | teacher1 | T1        | Teacher1 | teacher1@moodle.com |
    And the following "courses" exist:
      | fullname | shortname | category |
      | Course 1 | C1        | 0        |
    And the following "question categories" exist:
      | contextlevel | reference | name           |
      | Course       | C1        | Test questions |
    And the following "questions" exist:
      | questioncategory | qtype   | name         | template |
      | Test questions   | ddmatch | ddmatch-001  | foursubq |
    And the following "activities" exist:
      | activity   | name      | course | idnumber |
      | quiz       | Test quiz | C1     | quiz1    |
    And quiz "Test quiz" contains the following questions:
      | ddmatch-001 | 1 |
    And I log in as "admin"
    And I am on "Course 1" course homepage

@javascript
  Scenario: Backup and restore a course containing a Drag and drop matching question
    When I backup "Course 1" course using this options:
      | Confirmation | Filename | test_backup.mbz |
    And I restore "test_backup.mbz" backup into a new course using this options:
      | Schema | Course name | Course 2 |
    And I navigate to "Question bank" node in "Course administration"
    And I click on "Edit" "link" in the "ddmatch-001" "table_row"
    Then the following fields match these values:
      | Question name                      | ddmatch-001                      |
      | Question text                      | Classify the animals.            |
      | General feedback                   | General feedback.                |
      | Default mark                       | 1                                |
      | Shuffle                            | 1                                |
      | Question 1                         | frog                             |
      | Question 2                         | cat                              |
      | Question 3                         | newt                             |
      | Question 4                         |                                  |
      | id_subanswers_0                    | amphibian                        |
      | id_subanswers_1                    | mammal                           |
      | id_subanswers_2                    | amphibian                        |
      | id_subanswers_3                    | insect                           |

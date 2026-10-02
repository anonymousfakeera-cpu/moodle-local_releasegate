@local_releasegate
Feature: Release gate pages enforce authentication and authorisation
  In order to protect scan results and audit data
  Unauthenticated users must be redirected to the login page
  And authenticated users with sufficient capability must reach the pages

  Background:
    Given the following "courses" exist:
      | fullname    | shortname |
      | Test Course | TC1       |
    And the following "users" exist:
      | username | firstname | lastname | email                |
      | teacher1 | Teacher   | One      | teacher1@example.com |
    And the following "course enrolments" exist:
      | user     | course | role           |
      | teacher1 | TC1    | editingteacher |

  @javascript
  Scenario: Unauthenticated user is redirected from the site overview
    When I visit "/local/releasegate/index.php"
    Then I should see "Log in"

  @javascript
  Scenario: Unauthenticated user is redirected from the access review
    When I visit "/local/releasegate/accessreview.php"
    Then I should see "Log in"

  @javascript
  Scenario: Unauthenticated user is redirected from the audit log
    When I visit "/local/releasegate/audit.php"
    Then I should see "Log in"

  @javascript
  Scenario: Site administrator can open the site overview
    Given I log in as "admin"
    When I visit "/local/releasegate/index.php"
    Then I should see "Release gate"

  @javascript
  Scenario: Site administrator can open the access review
    Given I log in as "admin"
    When I visit "/local/releasegate/accessreview.php"
    Then I should see "Release gate access review"

  @javascript
  Scenario: Site administrator can open the audit log
    Given I log in as "admin"
    When I visit "/local/releasegate/audit.php"
    Then I should see "Release gate audit log"

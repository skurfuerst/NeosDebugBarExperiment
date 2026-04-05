Feature: Doctrine query logging in the debug bar
  The debug bar should display Doctrine SQL queries executed during a request.

  Scenario: Doctrine collector panel appears in debug bar output
    When I request "/debugbar-test"
    Then the response status code should be 200
    And the response should contain "doctrine"

  Scenario: Real SQL query executed during request appears in debug bar
    When I request "/debugbar-test"
    Then the response status code should be 200
    And the response should contain "debugbar_test"

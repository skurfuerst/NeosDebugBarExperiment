Feature: Flow log messages appear in the debug bar
  The debug bar should display log messages written to Flow's system logger
  during the request, using a dedicated "flow_logs" collector panel.

  Scenario: Flow logs collector panel appears in debug bar output
    When I request "/debugbar-test"
    Then the response status code should be 200
    And the response should contain "flow_logs"

  Scenario: System logger message from the request appears in debug bar
    When I request "/debugbar-test"
    Then the response status code should be 200
    And the response should contain "debugbar_log_test_message"

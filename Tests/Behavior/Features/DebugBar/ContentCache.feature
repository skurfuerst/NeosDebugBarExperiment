Feature: Content cache collector in the debug bar
  The debug bar should display Neos content cache statistics.

  Scenario: Content cache collector panel appears in debug bar output
    When I request "/debugbar-test"
    Then the response status code should be 200
    And the response should contain "neos_content_cache"

  Scenario: Cache miss is recorded when an uncached segment is requested
    When I request "/debugbar-test"
    Then the response status code should be 200
    And the response should contain "nb_misses"

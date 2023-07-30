  Feature: The Behat tests are configured correctly

  Scenario: WP-CLI loads for your tests
    Given a WP install

    When I run `wp eval 'echo "Hello world.";'`
    Then STDOUT should be:
      """
      Hello world.
      """

  Scenario: WP-CLI recognises plugin commands
    Given a WP install

    When I run `wp plugin --help`
    Then STDOUT should contain:
      """
      Manages plugins, including installs, activations, and updates.
      """

  Scenario: WP-CLI recognises Co-Authors Plus commands when the plugin is loaded
    Given a WP installation with the Co-Authors Plus plugin

    When I run `wp co-authors-plus --help`
    Then STDOUT should contain:
      """
      Manage co-authors and guest authors.
      """

<?php

namespace Drupal\Tests\localgov_menu_link_group\Kernel;

use Drupal\KernelTests\KernelTestBase;

/**
 * Config import test.
 *
 * Configuration file import should:
 * - Create a new menu link for the defined group.
 * - Move child menu links under the above menu link.
 * - Make the group specific menu link a child of its defined parent menu link.
 *
 * @group localgov_menu_link_group
 */
class GroupConfigImportTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'localgov_menu_link_group'];

  /**
   * Test that child menu links have been rearranged under a group.
   */
  public function testMenuLinkAlteration() {

    $this->container->get('module_installer')->install(['group_config_test']);

    $group_menu_link_id = 'localgov_menu_link_group:localgov_menu_link_group_test';
    $has_group_menu_link = $this->container->get('plugin.manager.menu.link')->hasDefinition($group_menu_link_id);
    $this->assertTrue($has_group_menu_link);

    $child_menu_links = $this->container->get('plugin.manager.menu.link')->getChildIds($group_menu_link_id);
    $parent_menu_links = $this->container->get('plugin.manager.menu.link')->getParentIds($group_menu_link_id);

    $expected_child_menu_links = [
      'system.performance_settings' => 'system.performance_settings',
      'system.logging_settings'     => 'system.logging_settings',
    ];
    $this->assertEqual($child_menu_links, $expected_child_menu_links);

    $has_parent_menu_link = in_array('system.admin_config_development', $parent_menu_links);
    $this->assertTrue($has_parent_menu_link);
  }

}

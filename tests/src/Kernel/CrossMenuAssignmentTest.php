<?php

namespace Drupal\Tests\localgov_menu_link_group\Kernel;

use Drupal\Core\Form\FormState;
use Drupal\user\Entity\User;
use Drupal\KernelTests\KernelTestBase;
use Drupal\localgov_menu_link_group\Entity\LocalGovMenuLinkGroup;
use Drupal\localgov_menu_link_group\Form\LocalGovMenuLinkGroupForm;

/**
 * Tests for the Entity form.
 *
 * Tests for cases where the menu of the selected parent menu link is different
 * from the menu of the selected child menu links.
 *
 * @group localgov_menu_link_group
 */
class CrossMenuAssignmentTest extends KernelTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['system', 'user', 'localgov_menu_link_group'];

  /**
   * {@inheritdoc}
   */
  protected function setUp() {

    parent::setUp();

    $this->installConfig(self::$modules);
    $this->installSchema('system', ['sequences']);
    $this->installEntitySchema('user');
    $this->installEntitySchema('localgov_menu_link_group');
    $this->container->get('plugin.manager.menu.link')->rebuild();

    $admin_user = User::create([
      'name' => 'admin',
      'mail' => 'admin@example.net',
    ]);
    $admin_user->addRole('administrator');
    $admin_user->save();
    $this->container->get('current_user')->setAccount($admin_user);
  }

  /**
   * Test child menu link for new groups.
   *
   * The menu name of a child menu link should be the same as the menu name of
   * the parent menu link.  When they differ, the child menu link's menu name
   * should be changed to match that of the parent menu.
   *
   * - Create a new group entity through form submission.  Use different
   *   **menus** for parent and child links.
   * - Load the newly created group entity and check the child menu links.
   * - The menu name of the child links should match that of the parent links.
   * - Note that the menu name appears at the beginning of a menu link id.
   *   Example: 'foo:bar_baz'.  Here "foo" is the menu name.
   */
  public function testNewChildMenuLinkName() {

    $empty_group = LocalGovMenuLinkGroup::create();
    $create_form_obj = LocalGovMenuLinkGroupForm::create($this->container);
    $create_form_obj->setEntity($empty_group);
    $create_form_obj->setModuleHandler($this->container->get('module_handler'));
    $create_form_obj->setEntityTypeManager($this->container->get('entity_type.manager'));
    $create_form_state = new FormState();
    $create_form_state->setValue('id', $group_id = 'foo');
    $create_form_state->setValue('group_label', $group_label = 'Foo');
    $create_form_state->setValue('parent_menu_link', 'account:user.page');
    $create_form_state->setValue('child_menu_links', ['admin:system.admin_content']);
    $create_form_state->setValue('op', 'Save');
    $this->container->get('form_builder')->submitForm($create_form_obj, $create_form_state);

    $this->assertEmpty($create_form_state->getErrors());

    $new_group = LocalGovMenuLinkGroup::load(LocalGovMenuLinkGroupForm::ENTITY_ID_PREFIX . $group_id);
    $expected_group_label = $group_label;
    $this->assertEqual($expected_group_label, $new_group->label());

    $child_menu_links = $new_group->get('child_menu_links');
    $expected_child_menu_links = ['account:system.admin_content'];
    $this->assertEqual($child_menu_links, $expected_child_menu_links);
  }

  /**
   * Test child menu link for existing groups.
   *
   * - Load an existing Group entity whose parent and child menus are different.
   * - Inspect the edit form for this Group entity.
   * - The parent and child menus should match.
   */
  public function testExistingChildMenuLinkName() {

    $this->container->get('module_installer')->install(['group_config_test']);

    $existing_group = LocalGovMenuLinkGroup::load('localgov_menu_link_group_differing_menu');
    $this->assertNotEmpty($existing_group);

    $group_update_form = $this->container->get('entity.form_builder')->getForm($existing_group);

    $expected_default_child_menu_links = ['account:system.performance_settings', 'account:system.logging_settings'];
    $this->assertEqual($group_update_form['child_menu_links']['#default_value'], $expected_default_child_menu_links);
  }

}

<?php

declare(strict_types = 1);

namespace Drupal\localgov_menu_link_group\Plugin\Deriver;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\localgov_menu_link_group\Entity\LocalGovMenuLinkGroupInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Menu link generator.
 *
 * Generate one menu link for each localgov_menu_link_group config entity.
 */
class MenuGroups extends DeriverBase implements ContainerDeriverInterface {

  const MENU_LINK_GROUP_CONFIG_ENTITY = 'localgov_menu_link_group';

  /**
   * {@inheritdoc}
   *
   * Define menu links.  Each menu link represents a menu link group.
   */
  public function getDerivativeDefinitions($base_plugin_definition) {

    $active_menu_link_groups = $this->fetchActiveMenuLinkGroups();
    $group_count = count($active_menu_link_groups);

    $menu_links = array_map(
      [$this, 'prepareMenuLinkForGroup'],
      $active_menu_link_groups,
      array_fill(0, $group_count, $base_plugin_definition));

    $menu_links_with_keys = array_combine(array_keys($active_menu_link_groups), $menu_links);

    return $menu_links_with_keys;
  }

  /**
   * Define the menu link for **one** menu link group.
   *
   * The menu link's title is taken from the corresponding
   * localgov_menu_link_group entity.
   */
  public static function prepareMenuLinkForGroup(LocalGovMenuLinkGroupInterface $group, array $base_menu_link_definition): array {

    // Parent menu link format: Parent-menu-name:Parent-menu-link.
    list($menu_name) = explode(':', $group->get('parent_menu_link'));
    $parent_menu_link = substr_replace($group->get('parent_menu_link'), '', 0, strlen($menu_name) + 1);

    $menu_link_for_group = [
      'id'         => $group->id(),
      'title'      => $group->label(),
      'menu_name'  => $menu_name,
      'parent'     => $parent_menu_link,
      'weight'     => $group->get('weight'),
    ] + $base_menu_link_definition;

    return $menu_link_for_group;
  }

  /**
   * Load our localgov_menu_link_group entities.
   */
  protected function fetchActiveMenuLinkGroups(): array {

    $active_menu_link_groups = $this->entityTypeManager->getStorage(self::MENU_LINK_GROUP_CONFIG_ENTITY)->loadByProperties(['status' => 1]);

    return $active_menu_link_groups;
  }

  /**
   * Keep track of the dependencies.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager) {

    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Factory method.
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {

    return new static(
      $container->get('entity_type.manager'),
    );
  }

  /**
   * Entity type manager service.
   *
   * @var Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

}

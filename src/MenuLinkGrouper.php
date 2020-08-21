<?php

declare(strict_types = 1);

namespace Drupal\localgov_menu_link_group;

use Drupal\localgov_menu_link_group\Entity\LocalGovMenuLinkGroupInterface;

/**
 * Reassign parent menu link for a group's child menu links.
 *
 * For each menu link group, we create a menu link.  All the child menu links
 * of this group then appear as children of this group menu link.  Here we
 * replace the original parent menu link of each child menu link with the group
 * menu link.
 *
 * Example: Child menu link A belongs to Group G.  A's original parent menu link
 * is B.  After reassignment, G will become A's new parent menu link.
 */
class MenuLinkGrouper {

  /**
   * Keep a **reference** to the entire menu link tree.
   *
   * @param array $menu_links
   *   This is the menu_links tree passed to hook_menu_links_discovered_alter().
   */
  public function __construct(array &$menu_links) {

    $this->menuLinks = &$menu_links;
  }

  /**
   * Reassign the parent menu link for all child menu links of a group.
   */
  public function groupChildMenuLinks(LocalGovMenuLinkGroupInterface $group, string $group_id): void {

    $child_menu_links = $group->get('child_menu_links');
    array_walk($child_menu_links, [$this, 'setNewParentForChildMenuLink'], $group_id);
  }

  /**
   * Reassign the parent menu link for a child menu link of a group.
   */
  protected function setNewParentForChildMenuLink(string $child_menu_link_id, string $ignore, string $group_id): void {

    $is_unknown_child_menu_link = !array_key_exists($child_menu_link_id, $this->menuLinks);
    if ($is_unknown_child_menu_link) {
      return;
    }

    $group_menu_link_id = "localgov_menu_link_group:$group_id";
    $this->menuLinks[$child_menu_link_id]['parent'] = $group_menu_link_id;
  }

  /**
   * The entire menu tree of a Drupal instance.
   *
   * The menu_links tree passed to hook_menu_links_discovered_alter().
   *
   * @var array
   */
  protected $menuLinks;

}

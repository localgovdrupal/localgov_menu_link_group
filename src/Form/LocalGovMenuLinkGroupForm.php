<?php

declare(strict_types = 1);

namespace Drupal\localgov_menu_link_group\Form;

use Drupal\Core\Entity\EntityForm;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Menu\MenuParentFormSelectorInterface;
use Drupal\Core\Render\ElementInfoManagerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Form handler for the localGov_menu_link_group config entity add/edit forms.
 */
class LocalGovMenuLinkGroupForm extends EntityForm {

  const ENTITY_ID_PREFIX = 'localgov_menu_link_group_';

  /**
   * {@inheritdoc}
   */
  public function form(array $form, FormStateInterface $form_state) {

    $group = $this->entity;

    $form = parent::form($form, $form_state);

    $form['group_label'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Group name'),
      '#description' => $this->t('It will act as label of the menu link for this group.'),
      '#maxlength' => 255,
      '#default_value' => $group->label(),
      '#required' => TRUE,
    ];

    list($field_prefix, $field_suffix) = $group->isNew() ? ['<span dir="ltr">' . self::ENTITY_ID_PREFIX, '</span>&lrm;'] : ['', ''];
    $form['id'] = [
      '#type'  => 'machine_name',
      '#default_value' => $group->id(),
      '#field_prefix' => $field_prefix,
      '#field_suffix' => $field_suffix,
      '#machine_name' => [
        'exists' => [$this, 'exist'],
        'source' => ['group_label'],
      ],
      '#disabled' => !$group->isNew(),
    ];

    $form['status'] = [
      '#title' => $this->t('Enabled'),
      '#type'  => 'checkbox',
      '#default_value' => $group->status(),
    ];

    $form['weight'] = [
      '#title' => $this->t('Weight of its menu link'),
      '#type'  => 'number',
      '#default_value' => $group->get('weight'),
    ];

    $form['parent_menu_link'] = $this->menuLinkSelector->parentSelectElement('admin:');
    $form['parent_menu_link']['#title'] = $this->t('Parent menu link');
    $form['parent_menu_link']['#description'] = $this->t('The menu link for this group will appear as a **child** of this menu link.  Example: Add content.');
    $form['parent_menu_link']['#default_value'] = $group->get('parent_menu_link');
    $form['parent_menu_link']['#required'] = TRUE;

    $form['child_menu_links'] = $this->menuLinkSelector->parentSelectElement('admin:');
    $form['child_menu_links']['#title'] = $this->t('Child menu links');
    $form['child_menu_links']['#description'] = $this->t('These will appear as children of the menu link for this group.  Example: Article, Basic page.');
    $form['child_menu_links']['#multiple'] = TRUE;
    $form['child_menu_links']['#default_value'] = $group->get('child_menu_links');
    $form['child_menu_links']['#size'] = 20;

    $has_multiselect_form_element = $this->elementInfo->hasDefinition('multiselect');
    if ($has_multiselect_form_element) {
      $form['child_menu_links']['#type'] = 'multiselect';
    }

    return $form;
  }

  /**
   * {@inheritdoc}
   *
   * Prepend the fixed prefix for new group id values.
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {

    $group = $this->entity;

    if ($group->isNew()) {
      $group_id = $form_state->getValue('id');
      $group_id_w_prefix = self::ENTITY_ID_PREFIX . $group_id;
      $form_state->setValue('id', $group_id_w_prefix);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {

    $group = $this->entity;
    $status = $group->save();

    if ($status === SAVED_NEW) {
      $this->messenger()->addMessage($this->t('The %label LocalGov menu link group created.', [
        '%label' => $group->label(),
      ]));
    }
    else {
      $this->messenger()->addMessage($this->t('The %label LocalGov menu link group has been updated.', [
        '%label' => $group->label(),
      ]));
    }

    $form_state->setRedirect('entity.localgov_menu_link_group.collection');
  }

  /**
   * Helper function to check entity existence.
   *
   * Checks whether a localgov_menu_link_group configuration entity exists.
   */
  public function exist($id) {
    $entity = $this->entityTypeManager->getStorage('localgov_menu_link_group')->getQuery()
      ->condition('id', $id)
      ->execute();
    return (bool) $entity;
  }

  /**
   * Prepares a localgov_menu_link_group entity form object.
   */
  public function __construct(MenuParentFormSelectorInterface $menu_link_selector, ElementInfoManagerInterface $element_info) {

    $this->menuLinkSelector = $menu_link_selector;
    $this->elementInfo      = $element_info;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {

    return new static(
      $container->get('menu.parent_form_selector'),
      $container->get('element_info')
    );
  }

  /**
   * Menu link dropdown builder.
   *
   * @var \Drupal\Core\Menu\MenuParentFormSelectorInterface
   */
  protected $menuLinkSelector;

  /**
   * Render element info service.
   *
   * @var Drupal\Core\Render\ElementInfoManagerInterface
   */
  protected $elementInfo;

}

<?php

namespace Drupal\user_statistics\Form;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides a filter form for UserStatistics list.
 *
 * This form allows filtering UserStatistics by:
 * - User (autocomplete),
 * - Node (autocomplete),
 * - Action type (edit/view).
 */
class UserStatisticsFilterForm extends FormBase {

  /**
   * The form builder service.
   */
  protected FormBuilderInterface $formBuilder;

  /**
   * The current user service.
   *
   * Used to determine if the user is admin
   * or should see only their own records.
   */
  protected AccountProxyInterface $currentUser;

  /**
   * The entity type manager service.
   *
   * Used to load user and node entities via dependency injection
   * instead of calling static ::load() methods directly.
   */
  protected EntityTypeManagerInterface $entityTypeManager;

  /**
   * Constructs a new UserStatisticsFilterForm.
   */
  public function __construct(RequestStack $request_stack, FormBuilderInterface $form_builder, AccountProxyInterface $current_user, EntityTypeManagerInterface $entity_type_manager) {
    $this->requestStack = $request_stack;
    $this->formBuilder = $form_builder;
    $this->currentUser = $current_user;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('request_stack'),
      $container->get('form_builder'),
      $container->get('current_user'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'user_statistics_filter_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $request = $this->requestStack->getCurrentRequest();
    $uid_from_url = $request->query->get('uid');
    $nid_from_url = $request->query->get('node_id');
    $is_admin = $this->currentUser->hasPermission('administer users');

    $user_storage = $this->entityTypeManager->getStorage('user');
    $node_storage = $this->entityTypeManager->getStorage('node');

    $default_user = ($uid_from_url && is_numeric($uid_from_url))
      ? $user_storage->load($uid_from_url)
      : NULL;

    $default_node = ($nid_from_url && is_numeric($nid_from_url))
      ? $node_storage->load($nid_from_url)
      : NULL;

    // User autocomplete field.
    if ($is_admin) {
      $form['uid'] = [
        '#type' => 'entity_autocomplete',
        '#title' => $this->t('User'),
        '#target_type' => 'user',
        '#selection_settings' => [
          'include_anonymous' => FALSE,
        ],
        '#default_value' => $default_user,
        '#size' => 20,
        '#attributes' => ['placeholder' => $this->t('Name or username contains')],
      ];
    }

    // Action type select field.
    $form['action_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Action'),
      '#options' => [
        '' => $this->t('- Any -'),
        'edit' => $this->t('Edit'),
        'view' => $this->t('View'),
      ],
      '#default_value' => $request->query->get('action_type') ?: '',
    ];

    // Node autocomplete field (filtered by bundle 'news').
    $form['node_id'] = [
      '#type' => 'entity_autocomplete',
      '#title' => $this->t('Node'),
      '#target_type' => 'node',
      '#selection_settings' => [
        'target_bundles' => ['news'],
      ],
      '#default_value' => $default_node,
      '#size' => 30,
      '#placeholder' => $this->t('Title contains'),
    ];

    // Submit button.
    $form['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Filter'),
      '#button_type' => 'primary',
      '#attributes' => ['class' => ['button']],
    ];

    // Add CSS class for styling.
    $form['#attributes']['class'][] = 'user-statistics-filter-form';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $query = [];

    if ($uid = $form_state->getValue('uid')) {
      $query['uid'] = $uid;
    }
    if ($action = $form_state->getValue('action_type')) {
      $query['action_type'] = $action;
    }
    if ($nid = $form_state->getValue('node_id')) {
      $query['node_id'] = $nid;
    }

    // Redirect to the UserStatistics collection page with filter query.
    $form_state->setRedirect('entity.user_statistics.collection', [], ['query' => $query]);
  }

}

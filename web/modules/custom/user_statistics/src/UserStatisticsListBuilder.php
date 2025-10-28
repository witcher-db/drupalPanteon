<?php

namespace Drupal\user_statistics;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityListBuilder;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Link;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Drupal\user_statistics\Form\UserStatisticsFilterForm;
use Drupal\Core\Session\AccountProxyInterface;

/**
 * Defines the list builder for User Statistics entities.
 */
class UserStatisticsListBuilder extends EntityListBuilder {

  /**
   * The form builder service.
   */
  protected FormBuilderInterface $formBuilder;

  /**
   * The request stack service.
   */
  protected RequestStack $requestStack;

  /**
   * The current user service.
   *
   * Used to determine if the user is admin
   * or should see only their own records.
   */
  protected AccountProxyInterface $currentUser;

  /**
   * Constructs a new UserStatisticsListBuilder.
   */
  public function __construct(
    EntityTypeInterface $entity_type,
    $storage,
    RequestStack $request_stack,
    FormBuilderInterface $form_builder,
    AccountProxyInterface $current_user,
  ) {
    parent::__construct($entity_type, $storage);
    $this->requestStack = $request_stack;
    $this->formBuilder = $form_builder;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, $entity_type) {
    return new static(
      $entity_type,
      $container->get('entity_type.manager')->getStorage($entity_type->id()),
      $container->get('request_stack'),
      $container->get('form_builder'),
      $container->get('current_user')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function buildHeader(): array {
    $header['id'] = $this->t('ID');
    $header['user'] = $this->t('User');
    $header['node'] = $this->t('Node');
    $header['comment'] = $this->t('Comment');
    $header['action_type'] = $this->t('Action');
    $header['datetime'] = $this->t('Timestamp');

    if ($this->currentUser->hasPermission('administer users')) {
      $clear_all_url = Url::fromRoute('user_statistics.clear_all');
      $clear_all_link = Link::fromTextAndUrl($this->t('Clear `Em All'), $clear_all_url)->toRenderable();
      $clear_all_link['#attributes'] = [
        'class' => ['button', 'button--small', 'button--danger'],
      ];

      $header['operations'] = [
        'data' => $clear_all_link,
        'class' => [RESPONSIVE_PRIORITY_LOW],
      ];
    }

    return $header + parent::buildHeader();
  }

  /**
   * {@inheritdoc}
   */
  public function load(): array {
    $entity_query = $this->storage->getQuery();
    $entity_query->accessCheck(TRUE);

    $request = $this->requestStack->getCurrentRequest();

    if ($uid = $request->query->get('uid')) {
      $entity_query->condition('uid', $uid);
    }
    if ($nid = $request->query->get('node_id')) {
      $entity_query->condition('node_id', $nid);
    }
    if ($action_type = $request->query->get('action_type')) {
      $entity_query->condition('action_type', $action_type);
    }

    if (!$this->currentUser->hasPermission('administer users')) {
      $entity_query->condition('uid', $this->currentUser->id());
    }

    $entity_query->pager(50);

    $header = $this->buildHeader();
    $entity_query->tableSort($header);

    $ids = $entity_query->execute();
    return $this->storage->loadMultiple($ids);
  }

  /**
   * {@inheritdoc}
   */
  public function buildRow(EntityInterface $entity): array {
    $row['id'] = $entity->id();

    $user = $entity->get('uid')->entity;
    $row['user'] = $user ? $user->toLink() : $this->t('Anonymous');

    $node = $entity->get('node_id')->entity;
    $row['node'] = $node ? $node->toLink() : $this->t('Unknown');

    $row['comment'] = $entity->get('comment')->value ?? '';
    $row['action_type'] = $entity->get('action_type')->value ?? '';
    $row['datetime'] = date('Y-m-d H:i:s', $entity->get('datetime')->value) ?? '';

    return $row + parent::buildRow($entity);
  }

  /**
   * {@inheritdoc}
   */
  public function render(): array {
    $build = parent::render();
    $build['#title'] = $this->t('User edit/view statistics');

    $form = $this->formBuilder->getForm(UserStatisticsFilterForm::class);
    $form['#attributes']['style'] = 'display: ruby;';

    return [
      'filter_form' => $form,
      'list' => $build,
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getOperations(EntityInterface $entity) {
    $operations = parent::getOperations($entity);

    if (!$this->currentUser->hasPermission('administer users')) {
      unset($operations['delete']);
    }

    return $operations;
  }

}

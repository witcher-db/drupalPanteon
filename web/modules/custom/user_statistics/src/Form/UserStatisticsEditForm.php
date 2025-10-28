<?php

namespace Drupal\user_statistics\Form;

use Drupal\Core\Entity\ContentEntityForm;
use Drupal\Core\Form\FormStateInterface;

/**
 * Form for editing the 'comment' field of UserStatistics entity.
 */
class UserStatisticsEditForm extends ContentEntityForm {

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $form = parent::buildForm($form, $form_state);

    $comment_value = $entity->get('comment')->value ?? '';

    $form['comment'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Comment'),
      '#default_value' => $comment_value,
      '#required' => TRUE,
      '#rows' => 5,
      '#attributes' => ['placeholder' => $this->t('Enter comment')],
    ];

    $form['user_statistics_id'] = [
      '#type' => 'hidden',
      '#value' => $entity->id(),
    ];

    $form['actions']['submit']['#value'] = $this->t('Save');
    $form['actions']['submit']['#button_type'] = 'primary';

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $form, FormStateInterface $form_state) {
    $entity = $this->entity;

    $entity->set('comment', $form_state->getValue('comment') ?? '');

    $status = $entity->save();

    if ($status == SAVED_UPDATED) {
      $this->messenger()->addStatus($this->t('Comment updated.'));
    }

    $form_state->setRedirect('entity.user_statistics.collection');
  }

}

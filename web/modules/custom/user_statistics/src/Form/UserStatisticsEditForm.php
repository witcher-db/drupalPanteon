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
    // Let the parent ContentEntityForm build all fields automatically.
    $form = parent::buildForm($form, $form_state);

    // Optionally, modify form actions or add extra attributes.
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

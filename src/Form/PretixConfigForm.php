<?php

namespace Drupal\itk_pretix\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

/**
 * Class PretixConfigForm.
 */
class PretixConfigForm extends ConfigFormBase {

  /**
   * {@inheritdoc}
   */
  protected function getEditableConfigNames() {
    return [
      'itk_pretix.pretixconfig',
    ];
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'pretix_config_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $config = $this->config('itk_pretix.pretixconfig');

    $form['pretix_url'] = [
      '#type' => 'textfield',
      '#description' => $this->t('The full pretix url, e.g. https://pretix.eu/'),
      '#title' => $this->t('pretix url'),
      '#size' => 64,
      '#default_value' => $config->get('pretix_url') ?? 'https://pretix.eu/',
      '#required' => TRUE,
    ];

    $form['organizer_slug'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Organizer (short form)'),
      '#description' => $this->t('The part of the url immediately after the domain name, e.g. "organizer-short-name" in "https://pretix.eu/control/organizer/organizer-short-name/"'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('organizer_slug'),
      '#required' => TRUE,
    ];

    $form['api_token'] = [
      '#type' => 'textfield',
      '#title' => $this->t('API Token'),
      '#description' => $this->t('The pretix API token (see https://docs.pretix.eu/en/latest/api/tokenauth.html#obtaining-an-api-token)'),
      '#maxlength' => 64,
      '#size' => 64,
      '#default_value' => $config->get('api_token'),
      '#required' => TRUE,
    ];

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    parent::submitForm($form, $form_state);

    $this->config('itk_pretix.pretixconfig')
      ->set('api_token', $form_state->getValue('api_token'))
      ->save();
  }

}

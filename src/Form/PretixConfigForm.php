<?php

namespace Drupal\itk_pretix\Form;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\itk_pretix\Pretix\EventHelper;
use ItkDev\Pretix\Api\Client;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Class PretixConfigForm.
 */
class PretixConfigForm extends ConfigFormBase {
  /**
   * The event helper.
   *
   * @var \Drupal\itk_pretix\Pretix\EventHelper
   */
  private $eventHelper;

  /**
   * {@inheritDoc}
   */
  public function __construct(ConfigFactoryInterface $config_factory, EventHelper $eventHelper) {
    parent::__construct($config_factory);
    $this->eventHelper = $eventHelper;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('config.factory'),
      $container->get('itk_pretix.event_helper')
    );
  }

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
      '#type' => 'url',
      '#description' => $this->t('The full pretix url, e.g. https://pretix.eu/'),
      '#title' => $this->t('pretix url'),
      '#size' => 64,
      '#default_value' => $config->get('pretix_url') ?? 'https://pretix.eu/',
      '#required' => TRUE,
    ];

    $form['organizer_slug'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Organizer (short form)'),
      '#description' => $this->t('The part of the url immediately after "/control/organizer/", e.g. "organizer-short-name" in "https://pretix.eu/control/organizer/organizer-short-name/"'),
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

    $form['template_event_slugs'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Template events'),
      '#description' => $this->t('Template event short forms. One per line.'),
      '#default_value' => $config->get('template_event_slugs'),
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
      ->set('pretix_url', $form_state->getValue('pretix_url'))
      ->set('organizer_slug', $form_state->getValue('organizer_slug'))
      ->set('api_token', $form_state->getValue('api_token'))
      ->set('template_event_slugs', $form_state->getValue('template_event_slugs'))
      ->save();
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);

    try {
      $client = new Client([
        'url' => $form_state->getValue('pretix_url'),
        'organizer' => $form_state->getValue('organizer_slug'),
        'api_token' => $form_state->getValue('api_token'),
      ]);
    }
    catch (\Exception $exception) {
      $form_state->setErrorByName('pretix_url', $this->t('Cannot create pretix api client'));
      return;
    }

    try {
      $client->getOrganizers();
    }
    catch (\Exception $exception) {
      $form_state->setErrorByName('pretix_url', $this->t('Cannot connect to pretix api'));
      return;
    }

    $templateEventSlugs = array_unique(array_filter(array_map('trim', explode(PHP_EOL, $form_state->getValue('template_event_slugs')))));
    $templateEvents = [];
    foreach ($templateEventSlugs as $eventSlug) {
      try {
        $event = $client->getEvent($eventSlug);
        $templateEvents[$event->getSlug()] = $event;
      }
      catch (\Exception $exception) {
      }
    }

    $invalidTemplateEventSlugs = array_diff($templateEventSlugs, array_keys($templateEvents));
    if (!empty($invalidTemplateEventSlugs)) {
      $form_state->setErrorByName('template_event_slugs',
        $this->t('Invalid template event slugs: @event_slugs',
          ['@event_slugs' => implode(', ', $invalidTemplateEventSlugs)]));
      return;
    }

    foreach ($templateEvents as $event) {
      $error = $this->eventHelper->validateTemplateEvent($event, $client);
      if (is_array($error)) {
        // We only show the first error message.
        $message = reset($error);
        $form_state->setErrorByName('template_event_slugs',
          $this->t('Event @event_slug is not a valid template: @message', [
            '@event_slug' => $event->getSlug(),
            '@message' => $message,
          ])
        );
        return;
      }
    }
  }

}

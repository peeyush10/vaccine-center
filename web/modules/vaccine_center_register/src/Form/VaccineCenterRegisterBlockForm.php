<?php

namespace Drupal\vaccine_center_register\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\user\Entity\User;
use Drupal\node\Entity\Node;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Url;

/**
 * Provides a Vaccine center register form.
 */
class VaccineCenterRegisterBlockForm extends FormBase {

  /**
   * The route match.
   *
   * @var \Drupal\Core\Routing\RouteMatchInterface
   */
  protected $routeMatch;

  /**
   * @var AccountProxy
   */
  protected $currentUser;

  /**
   * Constructs a new VaccineCenterRegisterBlockForm.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The route match.
   */
  public function __construct(RouteMatchInterface $route_match, AccountProxyInterface $current_user) {

    $this->routeMatch = $route_match;
    $this->currentUser = $current_user;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_route_match'),
      $container->get('current_user'),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'vaccine_center_register_block_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['actions'] = [
      '#type' => 'actions',
      '#prefix' => '<div id="edit-output">',
      '#suffix' => '</div>',
    ];
    if ($this->currentUser->id()) {
        $user = User::load($this->currentUser->id());
        $vaccinated = $user->get('field_register_vaccine_status')->value;
        if (!$vaccinated) {
          $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Register'),
          ];
        }
        else {
          $date = new DrupalDateTime($user->get('field_register_vaccine_date')->value, 'UTC');
          $form['actions']['vaccine_status'] = [
            '#markup' => $this->t('You are registerd at <a href="@node_url">@node_title</a> on @date. <a href="@url">Click here</a> to check status', [
              '@node_title' => $user->get('field_register_vaccine_center')->entity->getTitle(),
              '@node_url' => $user->get('field_register_vaccine_center')->entity->toUrl()->toString(),
              '@date' => $date->format('F j, Y - H:i'),
              '@url' => $user->toUrl()->toString(),
            ])
          ];
        }
    }
    else {
      $url = Url::fromRoute('user.login');
      $url->setOption('query', \Drupal::destination()->getAsArray());
      $string = $this->t('<a href="@url">Click here</a> to login and register for vaccination.', ['@url' => $url->toString()]);
      $form['actions']['vaccine_status'] = [
        '#markup' => $string,
      ];
    }
    
    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $user = User::load($this->currentUser->id());
    $node = $this->routeMatch->getParameter('node');
    $node = Node::load($node->id());
    
    $user->set('field_register_vaccine_status', 1);
    $user->set('field_register_vaccine_center', ['target_id' => $node->id()]);
    $now = DrupalDateTime::createFromTimestamp(time());
    $now->setTimezone(new \DateTimeZone('UTC'));
    $user->set('field_register_vaccine_date', $now->format('Y-m-d\TH:i:s'));
    $user->save();

    $node->set('field_available_slots', $node->get('field_available_slots')->value - 1);
    $node->save();
    $this->messenger()->addStatus($this->t('Vaccine registration completed.'));
  }
}

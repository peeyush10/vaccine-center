<?php

namespace Drupal\vaccine_center_register\Plugin\Block;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Block\BlockBase;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides a vaccine_center_register_block block.
 *
 * @Block(
 *   id = "vaccine_center_register_block",
 *   admin_label = @Translation("Vaccine Center Register Block"),
 *   category = @Translation("Vaccine Center Register")
 * )
 */
class VaccineCenterRegisterBlock extends BlockBase {

  /**
   * {@inheritdoc}
   */
  protected function blockAccess(AccountInterface $account) {
    // @DCG Evaluate the access condition here.
    $condition = TRUE;
    return AccessResult::allowedIf($condition);
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $form = \Drupal::formBuilder()->getForm('Drupal\vaccine_center_register\Form\VaccineCenterRegisterBlockForm');
    return $form;
  }

}

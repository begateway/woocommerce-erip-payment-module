<?php
/** @var WC_Payment_Gateway_Begateway_Erip $gateway */
/** @var WC_Order $order */
/** @var int $order_id */
/** @var array $order_data */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
} // Exit if accessed directly
?>

<ul class="order_action submitbox">
	<?php if ( 'cancelled' == $order->get_status() ): ?>
		<li class="begateway-erip-admin-section-li-small">
      <?php echo __( 'Заказ отменен', 'wc-begateway-erip-payment' ); ?>
    </li>
  	<li style='font-size: xx-small'>&nbsp;</li>
	<?php endif; ?>

  <?php $uid = $order->get_meta( '_begateway_transaction_id', true ); ?>
  <?php if ( $uid ): ?>
  	<li class="begateway-erip-admin-section-li-header-small">
      <?php echo __( 'Номер операции', 'wc-begateway-erip-payment' ) ?>
    </li>
  	<li class="begateway-erip-admin-section-li-small">
      <?php echo $uid; ?>
    </li>
  	<li style='font-size: xx-small'>&nbsp;</li>
  <?php endif; ?>

  <?php $can_cancel = $gateway->can_cancel_bill( $order ); ?>
	<?php if ( $can_cancel ): ?>
		<li class="begateway-erip-full-width">
      <a class="button" data-action="begateway_cancel" id="begateway_cancel" data-confirm="<?php echo __( 'Вы отменяете выставленный счёт в ЕРИП', 'wc-begateway-erip-payment' ); ?>" data-nonce="<?php echo wp_create_nonce( 'begateway' ); ?>" data-order-id="<?php echo $order_id; ?>">
      <?php echo __( 'Отменить счёт в ЕРИП', 'wc-begateway-erip-payment' ); ?>
      </a>
    </li>
  	<li style='font-size: xx-small'>&nbsp;</li>
	<?php endif; ?>

  <?php $can_create_bill = $gateway->can_create_bill( $order ); ?>
	<?php if ( $can_create_bill ): ?>
		<li class="begateway-erip-full-width">
      <a class="button" data-action="begateway_create" id="begateway_create" data-confirm="<?php echo __( 'Вы создаете счёт в ЕРИП', 'wc-begateway-erip-payment' ); ?>" data-nonce="<?php echo wp_create_nonce( 'begateway' ); ?>" data-order-id="<?php echo $order_id; ?>">
      <?php echo __( 'Создать счёт в ЕРИП', 'wc-begateway-erip-payment' ); ?>
      </a>
    </li>
	<?php endif; ?>
</ul>

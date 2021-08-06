<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = array(
  //Включение платежного шлюза
  'enabled' => array(
    'title'		=> __( 'Оплата через ЕРИП', 'woocommerce-begateway-erip' ),
    'label'		=> __( 'Разрешить', 'woocommerce-begateway-erip' ),
    'type'		=> 'checkbox',
    'default'	=> 'no',
  ),
  //Id магазина
  'erip_id_magazin' => array(
    'title'		=> __( 'ID магазина', 'woocommerce-begateway-erip' ),
    'type'		=> 'text',
    'desc_tip'	=> __( 'Введите ID магазина в системе bePaid', 'woocommerce-begateway-erip' ),
    'default'	=> ''
  ),
  //Ключ магазина
  'erip_API_key' => array(
    'title'		=> __( 'Секретный ключ магазина', 'woocommerce-begateway-erip' ),
    'type'		=> 'text',
    'desc_tip'	=> __( 'Введите секретный ключ вашего магазина в системе bePaid', 'woocommerce-begateway-erip' ),
    'default'	=> '',
  ),
  //Код услуги  ЕРИП
  'erip_kod_uslugi' => array(
    'title'		=> __( 'Код услуги в системе ЕРИП', 'woocommerce-begateway-erip' ),
    'type'		=> 'text',
    'desc_tip'	=> __( 'Введите код услуги в системе ЕРИП, присвоенный bePaid', 'woocommerce-begateway-erip' ),
    'default'	=> '',
  ),
  //Информация для плательщика для печати на чеке
  'info_message_in_check' => array(
    'title'		=> __( 'Сообщение в чеке после оплаты', 'woocommerce-begateway-erip' ),
    'type'		=> 'text',
    'desc_tip'	=> __( 'Введите сообщение, которой будет выводиться в чеке после оплаты заказа', 'woocommerce-begateway-erip' ),
    'default'	=> __( 'Спасибо за оплату', 'woocommerce-begateway-erip' ),
    'description' => __( '{{fio}}, {{order_number}}, {{name_shop}}', 'woocommerce-begateway-erip' ),
  ),
  //Имя способа оплаты для покупателя на странице выбора
  'name_sposoba_oplati' => array(
    'title'		=> __( 'Название способа оплаты', 'woocommerce-begateway-erip' ),
    'type'		=> 'text',
    'desc_tip'	=> __( 'Введите название способа оплаты, который будет отображаться у пользователя в списке вариантов оплаты', 'woocommerce-begateway-erip' ),
    'default'	=> __( 'Оплата через ЕРИП', 'woocommerce-begateway-erip' ),
  ),
  //Описание способа оплаты для покупателя
  'description_sposoba_oplati' => array(
    'title'		=> __( 'Описание способа оплаты для покупателя', 'woocommerce-begateway-erip' ),
    'type'		=> 'textarea',
    'desc_tip'	=> __( 'Введите описание способа оплаты для пользователя', 'woocommerce-begateway-erip' ),
    'default'	=> __( 'ЕРИП позволяет произвести оплату в любом удобном для Вас месте, в удобное для Вас время, в удобном для Вас пункте банковского обслуживания – банкомате, инфокиоске, интернет-банке, кассе банков, с помощью мобильного банкинга и т.д. Вы можете осуществить платеж с использованием наличных денежных средств, электронных денег и банковских платежных карточек в пунктах банковского обслуживания банков, которые оказывают услуги по приему платежей, а также посредством инструментов дистанционного банковского обслуживания.', 'woocommerce-begateway-erip' ),
    'css'		=> 'max-width:80%; height: 200px;'
  ),
  //Текст-подтверждение заказа для ручного режима
  'description_configuration_manual_mode' => array(
    'title'		=> __( 'Текст-подтверждение заказа для ручного режима', 'woocommerce-begateway-erip' ),
    'type'		=> 'textarea',
    'desc_tip'	=> __( 'Введите текст-подтверждение заказа для ручного режима. Текст будет показан на странице завершения оплаты заказа.', 'woocommerce-begateway-erip' ),
    'default'	=> __( 'Наш менеджер свяжется с вами для уточнения заказа и сообщит номер заказа и инструкции как его оплатить в системе ЕРИП.', 'woocommerce-begateway-erip' ),
    'css'		=> 'max-width:80%; height: 200px;'
  ),
  //Текст-подтверждение заказа для автоматического режима
  'description_configuration_auto_mode' => array(
    'title'		=> __( 'Текст-подтверждение заказа для автоматического режима', 'woocommerce-begateway-erip' ),
    'type'		=> 'textarea',
    'desc_tip'	=> __( 'Введите текст-подтверждение заказа для автоматического режима. Текст будет показан на странице завершения оплаты заказа.', 'woocommerce-begateway-erip' ),
    'default'	=> __( 'Если вы осуществляете платеж в кассе банка, пожалуйста, сообщите кассиру о необходимости проведения платежа через ЕРИП.

Для проведения платежа необходимо найти магазин в дереве ЕРИП по коду услуги {{erip_service_code}} или воспользоваться инструкцией:

1.​ Выбрать пункт ЕРИП
2.​ Выбрать последовательно пункты: {{instruction_erip}}
3.​ Ввести номер заказа {{order_number}}
4.​ Проверить корректность информации
5.​ Совершить платеж.

Если вы пользуетесь мобильным приложением банка, то используйте его, чтобы отсканировать QR-код и осуществить платеж.

{{qr_code}}

Обратите внимание: если вы откажетесь от покупки, для возврата денег вам придется обратиться в магазин.
', 'woocommerce-begateway-erip' ),
    'css'		=> 'max-width:80%; height: 200px;',
    'description' => __( '{{fio}}, {{order_number}}, {{name_shop}}, {{instruction_erip}}', 'woocommerce-begateway-erip' ),
  ),
  //Текст E-mail сообщения с информацией как оплатить заказ в системе ЕРИП
  'description_email_erip_instruction' => array(
    'title'		=> __( 'Текст для e-mail сообщения с инструкцией об оплате', 'woocommerce-begateway-erip' ),
    'type'		=> 'textarea',
    'desc_tip'	=> __( 'Введите текст сообщения, которое ваш покупатель получит после создания заказа', 'woocommerce-begateway-erip' ),
    'default'	=> __( 'Здравствуйте, {{fio}}!

В этом письме содержится инструкция как оплатить заказ {{order_number}} в магазине {{name_shop}} через систему ЕРИП.

Если вы осуществляете платеж в кассе банка, пожалуйста, сообщите кассиру о необходимости проведения платежа через ЕРИП.

Для проведения платежа необходимо найти магазин в дереве ЕРИП по коду услуги {{erip_service_code}} или воспользоваться инструкцией:

1.​ Выбрать пункт ЕРИП
2.​ Выбрать последовательно пункты: {{instruction_erip}}
3.​ Ввести номер заказа {{order_number}}
4.​ Проверить корректность информации
5.​ Совершить платеж.

Если вы пользуетесь мобильным приложением банка, то используйте его, чтобы отсканировать QR-код и осуществить платеж.

{{qr_code}}

Обратите внимание: если вы откажетесь от покупки, для возврата денег вам придется обратиться в магазин.
', 'woocommerce-begateway-erip' ),
    'css'		=> 'max-width:80%; height: 200px;',
    'description' => __( '{{fio}}, {{order_number}}, {{name_shop}}, {{instruction_erip}}', 'woocommerce-begateway-erip' ),

  ),
  // сколько минут дать на оплату
  'payment_valid' => array(
    'title' => __( 'Оплата действительна (минут)', 'woocommerce-begateway-erip' ),
    'type' => 'text',
    'description' => __( 'Укажите количество минут, в течение которых заказ должен быть оплачен', 'woocommerce-begateway-erip' ),
    'default' => '60'
  ),
  //Создание счета в ЕРИП
  'type_sposoba_oplati' => array(
    'title'		=> __( 'Создание счета в ЕРИП', 'woocommerce-begateway-erip' ),
      'desc'    	=> __( 'Выберите тип создания счета для пользователя', 'woocommerce-begateway-erip' ),
      'css'     	=> 'min-width:150px;',
      'std'     	=> 'left',
      'default' 	=> 'left',
      'type'    	=> 'select',
      'options' 	=> array(
          'manual'	=> __( 'Ручное создание счета', 'woocommerce-begateway-erip' ),
          'auto'		=> __( 'Автоматическое создание счета', 'woocommerce-begateway-erip' ),
      ),
      'desc_tip' 	=>  true,
    ),
  'debug' => array(
    'title' => __( 'Журнал отладки', 'woocommerce-begateway-erip' ),
    'type' => 'checkbox',
    'label' => __( 'Включить журнал отладки', 'woocommerce-begateway-erip' ),
    'default' => 'no',
    'description' =>  __( 'Записывать события', 'woocommerce-begateway-erip' ),
    'desc_tip'    => true
  )
);

return apply_filters('wc_begateway_erip_settings', $settings);

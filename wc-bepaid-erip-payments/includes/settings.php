<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$settings = array(
  //Включение платежного шлюза
  'enabled' => array(
    'title'		=> __( 'Оплата через ЕРИП', 'wc-begateway-erip-payment' ),
    'label'		=> __( 'Разрешить', 'wc-begateway-erip-payment' ),
    'type'		=> 'checkbox',
    'default'	=> 'no',
  ),
  //Id магазина
  'erip_id_magazin' => array(
    'title'		=> __( 'ID магазина', 'wc-begateway-erip-payment' ),
    'type'		=> 'text',
    'desc_tip'	=> __( 'Введите ID магазина в системе bePaid', 'wc-begateway-erip-payment' ),
    'default'	=> ''
  ),
  //Ключ магазина
  'erip_API_key' => array(
    'title'		=> __( 'Секретный ключ магазина', 'wc-begateway-erip-payment' ),
    'type'		=> 'text',
    'desc_tip'	=> __( 'Введите секретный ключ вашего магазина в системе bePaid', 'wc-begateway-erip-payment' ),
    'default'	=> '',
  ),
  //Код услуги  ЕРИП
  'erip_kod_uslugi' => array(
    'title'		=> __( 'Код услуги в системе ЕРИП', 'wc-begateway-erip-payment' ),
    'type'		=> 'text',
    'desc_tip'	=> __( 'Введите код услуги в системе ЕРИП, присвоенный bePaid', 'wc-begateway-erip-payment' ),
    'default'	=> '',
  ),
  //Информация для плательщика для печати на чеке
  'info_message_in_check' => array(
    'title'		=> __( 'Сообщение в чеке после оплаты', 'wc-begateway-erip-payment' ),
    'type'		=> 'text',
    'desc_tip'	=> __( 'Введите сообщение, которой будет выводиться в чеке после оплаты заказа', 'wc-begateway-erip-payment' ),
    'default'	=> __( 'Спасибо за оплату', 'wc-begateway-erip-payment' ),
    'description' => __( 'Текст может содержать метки: {{fio}} - имя и фамилия покупателя, {{order_number}} - номер заказа, {{name_shop}} - имя вашего магазина.', 'wc-begateway-erip-payment' ),
  ),
  //Имя способа оплаты для покупателя на странице выбора
  'name_sposoba_oplati' => array(
    'title'		=> __( 'Название способа оплаты', 'wc-begateway-erip-payment' ),
    'type'		=> 'text',
    'desc_tip'	=> __( 'Введите название способа оплаты, который будет отображаться у пользователя в списке вариантов оплаты', 'wc-begateway-erip-payment' ),
    'default'	=> __( 'Оплата через ЕРИП', 'wc-begateway-erip-payment' ),
  ),
  //Описание способа оплаты для покупателя
  'description_sposoba_oplati' => array(
    'title'		=> __( 'Описание способа оплаты для покупателя', 'wc-begateway-erip-payment' ),
    'type'		=> 'textarea',
    'desc_tip'	=> __( 'Введите описание способа оплаты для пользователя', 'wc-begateway-erip-payment' ),
    'default'	=> __( 'ЕРИП позволяет произвести оплату в любом удобном для Вас месте, в удобное для Вас время, в удобном для Вас пункте банковского обслуживания – банкомате, инфокиоске, интернет-банке, кассе банков, с помощью мобильного банкинга и т.д. Вы можете осуществить платеж с использованием наличных денежных средств, электронных денег и банковских платежных карточек в пунктах банковского обслуживания банков, которые оказывают услуги по приему платежей, а также посредством инструментов дистанционного банковского обслуживания.', 'wc-begateway-erip-payment' ),
    'css'		=> 'max-width:80%; height: 200px;'
  ),
  //Текст-подтверждение заказа для ручного режима
  'description_configuration_manual_mode' => array(
    'title'		=> __( 'Текст-подтверждение заказа для ручного режима', 'wc-begateway-erip-payment' ),
    'type'		=> 'textarea',
    'desc_tip'	=> __( 'Введите текст-подтверждение заказа для ручного режима. Текст будет показан на странице завершения оплаты заказа.', 'wc-begateway-erip-payment' ),
    'default'	=> __( 'Наш менеджер свяжется с вами для уточнения заказа и сообщит номер заказа и инструкции как его оплатить в системе ЕРИП.', 'wc-begateway-erip-payment' ),
    'css'		=> 'max-width:80%; height: 200px;'
  ),
  //Текст-подтверждение заказа для автоматического режима
  'description_configuration_auto_mode' => array(
    'title'		=> __( 'Текст-подтверждение заказа для автоматического режима', 'wc-begateway-erip-payment' ),
    'type'		=> 'textarea',
    'desc_tip'	=> __( 'Введите текст-подтверждение заказа для автоматического режима. Текст будет показан на странице завершения оплаты заказа.', 'wc-begateway-erip-payment' ),
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
', 'wc-begateway-erip-payment' ),
    'css'		=> 'max-width:80%; height: 200px;',
    'description' => __( 'Текст может содержать метки: {{fio}} - имя и фамилия покупателя, {{order_number}} - номер заказа, {{name_shop}} - имя вашего магазина, {{instruction_erip}} - инструкция по оплате через ЕРИП.', 'wc-begateway-erip-payment' ),
  ),
  //Текст E-mail сообщения с информацией как оплатить заказ в системе ЕРИП
  'description_email_erip_instruction' => array(
    'title'		=> __( 'Текст для e-mail сообщения с инструкцией об оплате', 'wc-begateway-erip-payment' ),
    'type'		=> 'textarea',
    'desc_tip'	=> __( 'Введите текст сообщения, которое ваш покупатель получит после создания заказа', 'wc-begateway-erip-payment' ),
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
', 'wc-begateway-erip-payment' ),
    'css'		=> 'max-width:80%; height: 200px;',
    'description' => __( 'Текст может содержать метки: {{fio}} - имя и фамилия покупателя, {{order_number}} - номер заказа, {{name_shop}} - имя вашего магазина, {{instruction_erip}} - инструкция по оплате через ЕРИП, {{qr_code}} - QR-код для оплаты через мобильное приложение банка.', 'wc-begateway-erip-payment' ),
  ),
  // сколько минут дать на оплату
  'payment_valid' => array(
    'title' => __( 'Оплата действительна (минут)', 'wc-begateway-erip-payment' ),
    'type' => 'text',
    'description' => __( 'Укажите количество минут, в течение которых заказ должен быть оплачен', 'wc-begateway-erip-payment' ),
    'default' => '60'
  ),
  // Создание счёта в ЕРИП
  'type_sposoba_oplati' => array(
    'title'		=> __( 'Создание счёта в ЕРИП', 'wc-begateway-erip-payment' ),
      'desc'    	=> __( 'Выберите тип создания счёта для пользователя', 'wc-begateway-erip-payment' ),
      'css'     	=> 'min-width:150px;',
      'std'     	=> 'left',
      'default' 	=> 'left',
      'type'    	=> 'select',
      'options' 	=> array(
          'auto'		=> __( 'Автоматическое создание счёта', 'wc-begateway-erip-payment' ),
          'manual'	=> __( 'Ручное создание счёта', 'wc-begateway-erip-payment' )
      ),
      'desc_tip' 	=>  true,
    ),
  'debug' => array(
    'title' => __( 'Журнал отладки', 'wc-begateway-erip-payment' ),
    'type' => 'checkbox',
    'label' => __( 'Включить журнал отладки', 'wc-begateway-erip-payment' ),
    'default' => 'no',
    'description' =>  __( 'Записывать события', 'wc-begateway-erip-payment' ),
    'desc_tip'    => true
  )
);

return apply_filters('wc_begateway_erip_settings', $settings);

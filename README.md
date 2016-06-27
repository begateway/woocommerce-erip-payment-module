# Модуль оплаты для системы "Расчёт" (ЕРИП) через агрегатора bePaid.by

## Установка

  - Создайте резервную копию вашего магазина и базы данных
  - Загрузите архив модуля [woocommerce-erip-payments.zip](https://github.com/beGateway/woocommerce-erip-payment-module/raw/master/woocommerce-erip-payments.zip)
  - Зайдите в панель администратора Wordpress (www.yoursite.com/wp-admin/)
  - Выберите __Плагины -> Добавить новый__
  - Нажмите __Загрузить плагин__
  - Выберите архив модуля _woocommerce-erip-payments.zip_ и установите
  - Выберите __Плагины -> Установленные__ и найдите _WooCommerce ERIP Gateway Payments_ модуль и активируйте его.

![Добавить новый плагин](https://github.com/beGateway/woocommerce-erip-payment-module/raw/master/doc/01_7.jpg)

![Загрузить новый плагин](https://github.com/beGateway/woocommerce-erip-payment-module/raw/master/doc/01_6.jpg)

![Установить новый плагин](https://github.com/beGateway/woocommerce-erip-payment-module/raw/master/doc/01_5.jpg)

![Активировать новый плагин](https://github.com/beGateway/woocommerce-erip-payment-module/raw/master/doc/01_4.jpg)

## Настройка

Зайдите в **WooCommerce -> Настройки -> Платежи**

![Настройки плагина](https://github.com/beGateway/woocommerce-erip-payment-module/raw/master/doc/01_3.jpg)

Вверху страницы вы увидите ссылку __ЕРИП__. Нажмите на её, чтобы перейти к настройкам ЕРИП модуля.

![Перейти к настройкам ЕРИП плагина](https://github.com/beGateway/woocommerce-erip-payment-module/raw/master/doc/01_2.jpg)

Задайте настройки, которые вы получили от bePaid или используйте тестовые данные, которые вы можете найти ниже.

![Настройки ЕРИП плагина](https://github.com/beGateway/woocommerce-erip-payment-module/raw/master/doc/01_1.jpg)


  - Разрешите оплату через ЕРИП, поставив галочку **Разрешить**
  - Id магазина и его ключ, можно найти в личном кабинете bePaid
  - Задайте **Домен API** _api.bepaid.by_
  - Введите в **Код услуги в системе ЕРИП** код услуги, присвоенный bePaid и который был указан в анкете услуги при подаче документов в ЕРИП
  - Введите в **Название поставщика услуг** Ваше наименование компании или индивидуального предпринимателя, которые зарегистированы в ЕРИП
  - Выберите в **Создание счета в ЕРИП** режим работы с ЕРИП. Автоматический режим означает, что счёт в ЕРИП будет создан сразу же и клиенту будет показана инструкция как оплатить. В ручном режиме менеджер магазина должен сам изменить статус заказа в Ожидание оплаты и клиенту будет выслано письмо с инструкцией как оплатить
  - Остальные поля можете не изменять или внесите правки согласно вашим требованиям
  - нажмите **Сохранить изменения** и ваш магазин настроен принимать платежи через ЕРИП

## Работа с модулем

WooCommerce будет автоматически создавать требования в ЕРИП и выслать инструкцию об оплате покупателю в случае выбора в настройках модуля автоматического режима работы. Заказу будет присвоен статус _В ожидании оплаты_.

Если был выбран режим ручного создания счета в ЕРИП, то заказу присваивается статус _На удержании_. Менеджер должен перейти на страницу данных о заказе и в блоке **Операции с Заказом** выбрать опцию _Сгенерировать платежное поручение в системе ЕРИП_.

Нажать **Сохранить Заказ**. WooCommerce создаст требование в ЕРИП и переведет заказ в статус _В ожидании оплаты_.

![Действия с заказом](https://github.com/beGateway/woocommerce-erip-payment-module/raw/master/doc/create_erip_1.jpg)

![Создать счет в систему ЕРИП](https://github.com/beGateway/woocommerce-erip-payment-module/raw/master/doc/create_erip_3.jpg)

![Сохранить заказ](https://github.com/beGateway/woocommerce-erip-payment-module/raw/master/doc/create_erip_2.jpg)

После получения оплаты через ЕРИП, WooCommerce получит уведомление о платеже и переведет статус заказа _Обработка_.

Идентификатор платежа на стороне bePaid можно найти в данных заказа в блоке __Произвольные поля__.

![ЕРИП UID](https://github.com/beGateway/woocommerce-erip-payment-module/raw/master/doc/erip-uid_1.jpg)

## Примечания

Разработанно и протестировано с:

Wordress 4.2.x / 4.3.x / 4.4.x / 4.5.x
WooCommerce 2.3.x / 2.4.x / 2.5.x / 2.6.x
Требуется PHP 5.3+

## Тестовые данные

Вы можете использовать следующие данные, чтобы настроить ЕРИП платежи в тестовом режиме:

  - Домен API **api.bepaid.by**
  - Код услуги в системе ЕРИП **99999999**

Если у вас нет еще зарегистрированного магазина в системе bePaid, то вы можете использовать следующие данные:

  - ID магазина **363**
  - Ключ магазина **4f585d2709776e53d080f36872fd1b63b700733e7624dfcadd057296daa37df6**

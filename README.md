# woocommerce-payment-module
## Установка
- Создайте резервную копию вашего магазина и базы данных
- Загрузите модуль
- Зайдите в панель администратора Wordpress (www.yourshop.com/wp-admin/)
- Выберите Плагины -> Добавить новый
- Загрузите модуль через Добавить новый
- Выберите Плагины -> Установленные и найдите WooCommerce beGateway Payment Gateway модуль и активируйте его.

![WooCommerce beGateway Payment Gateway](https://github.com/beGateway/woocommerce-payment-module/raw/master/doc/activate-plugin-ru.png)

## Настройка
Зайдите в WooCommerce -> Настройки -> Оплата

![WooCommerce beGateway Payment Gateway](https://github.com/beGateway/woocommerce-payment-module/raw/master/doc/setup-plugin-1-ru.png)

Вверху страницы вы увидите ссылку beGateway. Нажмите на ее и откроется страницы настройки модуля.
Параметры понятные и говорят сами за себя.

![WooCommerce beGateway Payment Gateway](https://github.com/beGateway/woocommerce-payment-module/raw/master/doc/setup-plugin-2-ru.png)

- задайте Заголовок e.g. Credit or debit card
- задайте Заголовок для администратора e.g. beGateway
- задайте Описание e.g. VISA, MasterCard. You are free to put all payment cards supported by your acquiring payment agreement.
- задайте Тип транзакции: Авторизация или Платеж
- отметьте Включить администратору возможность списания/отмены авторизации/возврат если хотите посылать списания/возвраты/отмену авторизации из панели администратора WooCommerce
- отметьте Журнал отладки если хотите журналировать события модуля

В следующих полях:

- Id магазина
- Секретный ключ
- Домен платежного шлюза
- Домен страницы оплаты
введите значения, полученные от вашей платежной компании.

- нажмите Сохранить изменения
Модуль настроен и готов к работе.

## Примечания

Разработанно и протестированно с:

Wordress 4.2.x / 4.3.x
WooCommerce 2.3.x / 2.4.x
Требуется PHP 5.3+

## Тестовые данные

Вы можете использовать следующие данные, чтобы настроить способ оплаты в тестовом режиме:

- Идентификационный номер магазина **361**
- Секретный ключ магазина **b8647b68898b084b836474ed8d61ffe117c9a01168d867f24953b776ddcb134d**
- Домен платежного шлюза **demo-gateway.begateway.com**
- Домен платежной страницы **checkout.begateway.com**

Используйте следующий тестовый набор для тестового платежа:

- номер карты **4200000000000000**
- имя на карте **John Doe**
- месяц срока действия карты **01**, чтобы получить успешный платеж
- месяц срока действия карты **10**, чтобы получить неуспешный платеж
- CVC **123**

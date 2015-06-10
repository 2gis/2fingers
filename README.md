# 2fingers

## Кратко о сути
**2fingers** - это простой PHP-фреймворк для написания *параметризованных* функциональных тестов для вашего JSON-based REST API.

Инструмент позволяет тестировать API методом полноценного чёрного ящика и заточен под использование реальных случайных данных из тестовой БД.

Для запуска тестов используется PHPUnit, для выполнения запросов - HTTP-клиент Guzzle, для управления зависимостями - composer.

Рекомендуется к использованию под ОС семейства Linux.

**Обратите внимание**, в репозитории лишь базовые классы и примеры, фреймворк нужно адаптировать под архитектуру вашего API и структуру БД!

По любым вопросам касательно 2fingers пишите на leaxfm@gmail.com или p.asanov@flamp.ru.

## Деплоймент и запуск

### Требуемые компоненты

- для запуска 2fingers требуются установленные пакеты php5-cli (версия PHP не ниже 5.4), php5-pgsql, php5-curl.
- требуются установленные composer и phpunit

### Установка composer и phpunit

- скачиваем файл .phar с оф. сайта
- переносим фарку в папку с другими исполняемыми файлами:
`sudo mv phpunit.phar /usr/local/bin/phpunit`
- заходим в /usr/local/bin и превращаем файл в исполняемый:
`sudo chmod u+x phpunit`
- теперь можно из любой папки писать `phpunit <something>`
- аналогично для composer

### Копируем проект из репозитория
```
git clone git@github.com:2gis/2fingers.git
```
### Подтягиваем необходимые зависимости
```
composer install
```
### Создание конфига
Переименовываем шаблон конфига server.php.dist в server.php, затем прописываем в получившийся конфиг хост и базу данных.
```
cd config/
cp server.php.dist server.php
vim server.php
```
### Запуск тестов
Тесты на API лежат в папке tests. Запускаем их с помощью phpunit. 
```
phpunit tests/
```
Внутри тесты удобно разбивать по директориям в соответствии, например, с разделами документации (Auth, Users и т.д.). Соответственно, можно запускать тесты из конкретного раздела:
```
phpunit tests/Users/
```
Конкретный тест можно запустить, указав полный путь до него:
```
phpunit tests/AddSomethingTest.php
```
При запуске можно консольными параметрами указать хост, БД и параметр verbose, если не хочется менять их значения в конфиге server:
```
phpunit tests/AddSomethingTest.php --host=test.test --dbhost=test.test --dbname=test
phpunit tests/AddSomethingTest.php --verbose=false
```
Параметр verbose показывает, нужно ли отображать развёрнутую отладочную информацию (параметры запроса, JSON-ответ) для упавших тестов в консоль. По умолчанию он всегда true.

Также есть возможность запускать наборы тестов (test suites):
```
phpunit --testsuite regression
```
Формировать такие наборы можно в конфиге phpunit.xml.

###Примеры запуска

Так выглядит успешно прошедшая сборка:
```
p.asanov@uk-rnd-266:~/2fingers$ phpunit tests/2.0/Comments/
 
( ͡° ͜ʖ ͡°) starting 2fingers...
 
PHPUnit 4.3.5 by Sebastian Bergmann.
 
Configuration read from /home/p.asanov/2fingers/phpunit.xml
...............................................................  63 / 146 ( 43%)
............................................................... 126 / 146 ( 86%)
....................
 
Time: 43.17 seconds, Memory: 16.50Mb
 
OK (146 tests, 1135 assertions)
```
Каждая точка - это успешно прошедший тест-кейс. Всего в папке Comments 12 классов с тестами, их запуск даёт нам суммарно 146 тест-кейсов, которые суммарно дают нам 1135 проверок атрибутов в JSON.
Если тест упал, вместо точки будет красная буква F (проверка не прошла) или E ( в тесте произошла ошибка).
Кроме того, в консоль по умолчанию выводится отладочная информация:
```
....................F...
 
Time: 6.48 seconds, Memory: 8.00Mb
 
There was 1 failure:
1) AddReviewTest::testAddReview with data set #20 ('141265770608749', 'rDlJ4zSUks', 5, true, NULL, 0, 403)
===============================================================
POST https://jazz.precise.flamp.test/api/2.0/reviews
 
filial_id = 141265770608749
text = rDlJ4zSUks
rating = 5
is_recommended = true
photos = 
 
HEADERS:
Accept: application/json;q=1;depth=1;scopes={"review":{}}
Authorization: Bearer 271b3df12efbbace6ff88ea98f964aa52da32a4
---------------------------------------------------------------
{
    "code": 401,
    "status": "error",
    "error_code": 0,
    "message": "У вас нет прав доступа для выполнения запроса"
}
---------------------------------------------------------------
 
Failed asserting that 401 matches expected 403.
 
/home/p.asanov/2fingers/tests/2.0/BaseFlampApiTest.php:79
/home/p.asanov/2fingers/tests/BaseTest.php:62
/home/p.asanov/2fingers/tests/2.0/Reviews/AddReviewTest.php:89
                                         
FAILURES!                              
Tests: 24, Assertions: 122, Failures: 1.
```
### Настройки для PHPStorm
Если вы собираетесь разрабытавать тесты на 2fingers, настройте PHPStorm, чтобы дебаг и запуск тестов работали корректно из самой IDE:
- Настройки - PHP: убедитесь, что в графах PHP Language Level и Interpretator указана корректная версия PHP, а в настройках интепретатора указан корректный путь до PHP (напр. /usr/bin)
- Настройки - PHPUnit: убедитесь, что выбран пункт Use custom autoloader и указан корректный путь к PHPUnit (напр. /usr/local/bin/phpunit), в качестве Default configuration runner указан 2fingers/phpunit.xml, а в качестве Default bootstrap file - 2fingers/bootstrap.php.

## Как писать тесты в 2fingers

### Структура теста
Создаём файл вида **AddSomethingTest.php**, содержащий в себе PHP-класс **AddSomethingTest**. Названия файла и класса должны совпадать и оканчиваться на **Test**.

Кроме этого, класс должен обязательно расширять базовый класс **BaseTest**:
```php
class AddSomethingTest extends BaseTest
{
}
```
<p align="center">
<img src="https://hsto.org/files/8ac/516/8e9/8ac5168e93354774b488f348f506d489.png" alt="">
</p>

Параметризованный тест- это тест-шаблон, который принимает на вход N наборов данных, а на выходе выдает N реальных тестов.

Поэтому внутри класса **AddSomethingTest** должны быть 2 обязательных метода: 
- дата провайдер, т.е. источник данных (тест-кейсов, тестовых наборов) для параметризованного теста - **providerAddSomething**
- метод, представляющий себой непосредственно исполняемый параметризованный тест - **testAddSomething**

Имя теста должен обязательно начинаться со слова **test**, имя дата провайдера - со слова **provider**. В остальном желательно, чтобы названия соответствовали имени класса, для понятности.

Чтобы **testAddSomething** знал, откуда брать тестовые наборы, нужно указать для него дата провайдер с помощью аннотации **@dataProvider**:
```php
class AddSomethingTest extends BaseTest
{
    public function providerAddSomething() {}
 
    /**
     * @dataProvider providerAddSomething
     */
    public function testAddSomething() {}
}
```
### Пишем метод test

Метод **testAddSomething** и является, собственно, параметризованым тестом, прогоняемым на нескольких тестовых наборах.

Что же должен делать такой тест? Очевидно - установить параметры запроса к API, выполнить этот запрос и проверить ответ! 

Кроме того, т.к. тест параметризованный, на вход он должен принимать из дата провайдера параметры (тестовый набор) в виде списка аргументов.

<p align="center">
<img src="https://hsto.org/files/60a/b17/5b4/60ab175b4aa24acba22a73e2aba7452a.png" alt="">
</p>

Большая часть аргументов - это и есть параметры запроса, взятые напрямую из документации. Помимо них, тестовый набор может содержать роль, под которой выполняется запрос, ожидаемый код ответа и, возможно, какие-то дополнительные булевые флаги.

<p align="center">
<img src="https://hsto.org/files/7fb/5c0/d11/7fb5c0d11ca44e258a760c5674b04af1.png" alt="">
</p>

Теперь понятно, как написать метод test:
- задаем список аргументов для метода, соответствующий тестовому набору
- задаём HTTP-метод, метод API, параметры запроса (если они есть): просто присваиваем <br>*$this->имя_параметра* нужное значение. 
- выполняем запрос с помощью метода **send()**. При этом можно указать роль методом **asUser()**, либо напрямую аксесс токен методом **withAccessToken()**. При необходимости можно указать протокол методом **overProtocol()**. Все эти методы вызываются через *$this->*, и их можно вызывать цепочно, т.е. друг за другом через стрелочку. Разумется, **send()** в цепочке всегда должен быть последним.
- проверяем код ответа с помощью метода **waitFor()**
```php
public function testAddReview($filial_id, $text, $rating, $is_recommended,
                              $photos, $user_id, $expected_code)
{
    // Задаём http-метод, метод API, параметры запроса и scopes
    $this->http_method = 'POST';
    $this->method = "reviews";
    $this->params = [
        'filial_id' => $filial_id,
        'text' => $text,
        'rating' => $rating,
        'is_recommended' => $is_recommended,
        'photos' => $photos,
    ];
    $this->scopes = 'review';
 
    // Выполняем запрос и проверяем коды ответа
    $this->asUser($user_id)->send();
    $this->waitFor($expected_code);
}
```

### Пишем dataProvider

В методе providerAddSomething формируются тестовые наборы. Чтобы сформировать такой набор, нам в большинстве случаев нужно иметь какой-то исходный объект. Например, это может быть филиал, к которому мы добавляем отзыв.
Таким образом, дата провайдер состоит из двух частей:
- получение данных из подходящего источника для формирования наборов
- непосредственно формирование списка тестовых наборов. По сути это просто return массива массивов.
```php
public function providerAddReview()
{
    $filial_id = Config()->filials->pac;
    $user_id = Db()->user()->getRandomUser()->id;
 
    return [
        [$filial_id, Generate()->text(150), 5, true, null, $user_id, 201],
    ];
}
```
### Источники данных

- Конфиг как источник статичных, синтетических данных (фикстуры). В конфиге данные хранятся в виде дерева (массив массивов). Просто указываем через стрелочки путь до нужного узла дерева. Если нужно получить конкретный массив целиком, можно воспользоваться методом **asArray()**.
```php
$user_id = Config()->roles->guest;
```
- БД как источник реальных случайных данных. Объекты из БД получаем цепочным запросом в удобном описательном виде. Обычно сначала указываем таблицу/сущность, затем условия выборки и в самом конце дёргаем метод получения.
```php
$entity = Db()->entity('review')->forFilial($filial_id)
  ->isHidden(false)->getRandomEntity();
```
- Тестируемое API. Используем его методы для генерации данных, если данных в БД недостаточно, или если мы не можем их получить никаким другим путём.
```php
Api()->addUserToken();
```
- Генератор случайных данных.
```
Generate()->text(150)
```
### Проверки

Теперь, когда есть набор данных и тест, который на нем успешно проходит и проверяет код ответа, можно добавить дополнительные проверки атрибутов JSON.

*Проверку атрибутов ответа имеет смысл проводить только для позитивных кейсов. Поэтому перед проверкой нужно убедиться, что тест получил успешный код ответа (20x).*

Для проверок атрибутов во фреймворке реализован специальный рекурсивный метод **assert()**. Суть метода чрезвычайно проста: он сравнивает два объекта: ожидаемый (expected) и фактический (actual), и в случае их несовпадения выводит в консоль отладочную информацию (запрос / ответ) с указанием ошибочного атрибута.
```php
if ($expected_code === 201) {
 
    $actual = ...
 
    $expected = ...
 
    $this->assert($expected, $actual);
];
```
#### Actual
Фактический результат - это объект, полученный из JSON-ответа API. Обычно в качестве actual мы берём тот самый объект, который проверяем в тесте.

Получить JSON-ответ можно с помощью метода **getResponseBody()**, вытащить из него нужный объект - просто через стрелочку с указанием имени атрибута.
```php
$actual = $this->getResponseBody()->review;
```
#### Expected
Ожидаемый результат - это сформированный в тесте массив ключ-значение, который описывает actual-объект. Поддерживает любые уровни вложенности. Массивом expected можно описать и JSON-объект, и JSON-массив объектов.

Каждый ключ массива должен, разумеется, точно соответствовать названию аналогичного атрибута в actual-объекте.

Пример ожидаемого результата:
```php
$expected = [
    'filial_id' => $filial_id,
    'user_id' => $user_id,
    'text' => $text,
    'rating' => $rating,
    'is_recommended' => $is_recommended,
    'date_created' => CHECK_DATETIME_FORMAT,
    'date_edited' => null,
    'comments_count' => 0,
    'source' => CHECK_SOURCE,
    'project' => CHECK_NOT_NULL,
    'additional_data' => [
        'is_my' => true,
        'is_liked' => false,
    ],
    'url' => CHECK_STRING_NOT_EMPTY,
    'count_by_author_about_filial' => CHECK_NOT_NEGATIVE,
    'id' => CHECK_POSITIVE
];
```

Значение ключа может быть определено абсолютно точно (параметр, переданный из набора данных, либо 0, null и т.д.).

Если же мы не знаем точного ожидаемого значения, мы можем описать его в виде условия (положительное число, не NULL, непустая строка и т.д.). Делается это с помощью текстовых констант **CHECK_**.

| Константа  | Описание  | 
| ------------ |---------------| 
| CHECK_EXIST      | Проверяет, что атрибут с таким именем в принципе существует в ответе | 
| CHECK_NOT_NULL      | Проверяет, что значение атрибута отлично от NULL        |
| CHECK_POSITIVE | Проверяет, что значение атрибута - положительное число        |
| CHECK_NOT_NEGATIVE | Проверяет, что значение атрибута - неотрицательное число        |
| CHECK_STRING_NOT_EMPTY | Проверяет, что значение атрибута - непустая строка.        |
| CHECK_DATETIME_FORMAT | Проверяет, что datetime атрибут имеет корректный формат        |
| CHECK_SOURCE и т.п. | По сути, проверка на enum. В данном случае убеждаемся, что параметр source имеет корректное строковое значение        |

Для добавления своих проверок нужно добавить константы в bootstrap.php и метод **assert()** класса **BaseTest.php**

#### Другие проверки

Все проверки, которые не удалось осуществить с помощью метода **assert()**, можно делать с помощью стандартных методов PHPUnit **assertEquals()**, **assertNotNull()**, **assertContains()** и т.д. Полный список смотри [здесь](https://phpunit.de/manual/current/en/appendixes.assertions.html).

### Детализированная схема теста

<p align="center">
<img src="https://hsto.org/files/8aa/49a/2c9/8aa49a2c94a64fbe9b99ec64b3ad1a95.png" alt="">
</p>

### Работа с БД

#### Концепция
Идея работы с классом **MiscDbHelper** следующая:
- в цепочном вызове вначале обозначаем таблицу/сущность, которую хотим получить, например **table('users')**, **entity('user')** или **user()**
- затем перечисляем условия выборки, например **isHidden()**, **hasComments()**
- в конце дёргаем собственно метод получения сущности. Для сложных сущностей нужно создать специальные методы, например **getRandomUser()** и **getRandomEntity()**, для более простых кейсов можно использовать **getRow()** и **getRows()**.

#### Отладка

Чтобы посмотреть на SQL-запрос, просто подставляем единичку в функцию Db(), и текущий запрос к БД выведется в консоль:
```php
$entity = Db(1)->entity('review')->forFilial($filial_id)
    ->isHidden(false)->getRandomEntity();
```
Актуально только для тех запросов, которые по факту улетают в БД, т.е. при формировании подзапросов такой способ работать не будет.

#### Получение случайной уникальной сущности

Используем для этой цели метод **getRandomEntity()**. Метод подбирает случайную сущность по заданному условию и добавляет её в список "использованных" в текущем запуске сущностей - это необходимо для изоляции данных, чтобы не было конфликтов в тестах.

Для более сложных метасущностей можно создать свои аналогичные методы.

#### Простые запросы к одной таблице

Если нужно добавить в цепочный вызов одно простое условие, создаем новый public метод в **MiscDBHelper** и указываем это условие для блока WHERE:
```php
public function forProject($project_id)
{
    $this->where .= " AND {$this->current_table}.project_id = {$project_id}";
    return $this;
}
```
#### Подзапросы

Подзапросы делаем с помощью метода **getSubquery()**. Он должен быть в конце цепочки вызова, вместо, например, getRandomEnity(). Идея в том, что он вернет не саму сущность из базы, а SQL-подзапрос для ее получения. Потом берём этот подзапрос и вставляем в условие **idIn()** или **idNotIn()** в основном цепочном вызове.
```php
$subquery = Db()->fields('object_id')->table('comments')->getSubquery();

$review = Db()->entity('review')->idNotIn($subquery)->getRandomEntity();
```
В этом примере мы сначала создаём подзапрос: *получить из таблицы comments поле object_id для всех записей.*

Затем *выбираем такой случайный отзыв, id которого не входит в созданный выше подзапрос.*

В методы **idIn()**, **idNotIn()** можно передавать как подзапрос, так и просто id (число) или даже массив id-шников.
В некоторых случаях вместо IN удобней использовать конструкцию EXIST, она так же реализована через методы **exist()** и **notExist()**.

#### JOIN

MiscDbHelper поддерживает работу с JOIN'ами. При этом используются понятия текущей (current) и главной (main) таблицы. 

Указав в цепочке метод **joinSomething()**, присоединяющий таблицу table, мы делаем её текущей. Следующие условия в цепочке теперь будут накладываться уже на table.

При этом реализовывать методы вида **joinSomething()** можно с помощью методов **join()** и **smartJoin()**, подробнее см. MiscDbHelper.

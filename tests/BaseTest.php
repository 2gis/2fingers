<?php

/**
 * Класс расширяет стандартный PHPUnit-TestCase.
 * Он используется для вынесения общей для всех тестов логики (например, проверки кодов ответа),
 * а также для сокращения и упрощения тестов.
 *
 * Все тесты для API наследуются от этого класса
 *
 */
abstract class BaseTest extends PHPUnit_Framework_TestCase
{
    protected $method;
    protected $params = null;
    protected $protocol = null;
    protected $http_method;
    protected $access_token = null;

    /** @var  ApiRequest */
    protected $request;
    /** @var  Guzzle\Http\Message\Response */
    protected $response;

    protected $expected_code;
    protected $message;

    /** Параметр отпределяет, выводить ли отладочную информацию для упавших тестов в консоль */
    protected $verbose;

    /**
     * Вызываем конструктор базового класса PHPUnit_Framework_TestCase,
     * чтобы установить параметр verbose в значение из конфига
     *
     * В вызов конструктора приходится передавать системные параметры
     * @link https://github.com/sebastianbergmann/phpunit/issues/621
     */
    function __construct($name = NULL, array $data = array(), $dataName = '')
    {
        $this->verbose = ($GLOBALS['server']['verbose'] === 'false' ? false : true);
        parent::__construct($name, $data, $dataName);
    }

    /**
     * Переопределяем стандарнтый метод run для очистки экземпляра Guzzle после каждого TestCase
     * @param PHPUnit_Framework_TestResult $result
     * @return PHPUnit_Framework_TestResult
     */
    public function run(PHPUnit_Framework_TestResult $result = null)
    {
        $res = parent::run($result);
        $this->request = null;
        return $res;
    }

    /**
     * Отправка запроса и получение ответа
     */
    public function send()
    {
        $this->createRequest();
        $this->response = $this->request->send();
    }

    /**
     * Формирование запроса
     */
    protected function createRequest()
    {
        if(!$this->protocol)
            $this->protocol = 'https';

        $this->request = new ApiRequest($this->http_method, $this->protocol,
            $this->method, $this->params);

        $this->request->setAccessToken($this->access_token);
    }

    /**
     * Сравнение ожидаемого кода ответа с фактическим
     * @param $expected_code
     */
    public function waitFor($expected_code)
    {
        $this->expected_code = $expected_code;

        // В наследниках проверяем http-код
        $this->checkHttpCode();
        // Проверяем код в JSON-теле ответа
        $this->checkBodyCode();
    }

    /**
     * Сравнение ожидаемого http-кода ответа с фактическим
     */
    protected function checkHttpCode()
    {
        $this->assertEquals($this->expected_code, $this->response->getStatusCode(),
            // Если проверка не прошла, выводим в консоль отладочную информацию
            $this->showMessage());
    }

    /**
     * Проверка кода в теле ответа
     */
    protected function checkBodyCode()
    {
        $this->assertEquals($this->expected_code, $this->getResponseBodyCode(),
            // Если проверка не прошла, выводим в консоль отладочную информацию
            $this->showMessage());
    }

    /**
     * Извлечение кода из тела ответа API
     */
    protected function getResponseBodyCode()
    {
        return $this->getResponseBody()->code;
    }

    /**
     * Получение тела ответа
     * @return object
     * @throws Exception
     */
    public function getResponseBody()
    {
        if (!isset($this->response))
            throw new Exception('Response is null. Try to send request first!');
        return json_decode($this->response->getBody());
    }

    /**
     * Метод нужен для тестов, которые используют в исходных данных id юзера
     * @param $user_id
     * @return $this
     */
    public function asUser($user_id)
    {
        // Здесь может быть описана логика получения аксесс токена из БД по id пользователя

        return $this;
    }

    /**
     * Метод нужен для тестов, которые используют в данных аксесс токен в явном виде
     * @param $access_token
     * @return $this
     */
    public function withAccessToken($access_token)
    {
        $this->access_token = $access_token;
        return $this;
    }

    /**
     * @param $protocol
     * @return $this
     */
    public function overProtocol($protocol)
    {
        $this->protocol = $protocol;
        return $this;
    }

    /**
     * Сравнение фактического и ожидаемого результатов.
     * Метод рекурсивно обходит весь массив expected и сравнивает значения полей с объектом/массивом actual
     * Для проверки кейсов типа "поле должно быть не null" используются константы
     *
     * @param $expected
     * @param $actual
     * @throws Exception Если в actual отсутствует проверяемый ключ
     * @throws Exception Если expected или actual являются скалярными величинами
     */
    public function assert($expected, $actual)
    {
        // Проверяем, что и expected, и actual не являются скалярными величинами
        if(!is_array($expected))
            throw new Exception("Expected must be an array");
        if(!is_array($actual) && !is_object($actual))
            throw new Exception("Wrong actual (scalar). Must be an object or array");

        // Обходим весь массив expected
        foreach ($expected as $key => $expected_value)
        {
            // Ошибка, если в объекте actual отсутствует проверяемый ключ
            if(is_array($actual) && !array_key_exists($key, $actual) ||
                is_object($actual) && !property_exists($actual, $key))
                throw new Exception("\n" . $this->showMessage() .
                    "\e[31mNo such property/key '{$key}' in actual response\e[39m");

            // Если actual массив, берём значение по ключу key, если объект - берём значение поля key
            if(is_array($actual))
                $actual_value = $actual[$key];
            elseif(is_object($actual))
                $actual_value = $actual->$key;

            // Если оба значения являются НЕ скалярными, повторяем обход рекурсивно
            if (is_array($expected_value) && (is_array($actual_value) || is_object($actual_value)))
                $this->assert($expected_value, $actual_value);

            // Если оба (или хотя бы одно) значения являются скалярными, выполняем проверки по значению
            else
                switch ((string)$expected_value) {
                    // Убеждаемся, что поле существует
                    case CHECK_EXIST:
                        // Если поле отсутствует, выше выбросится исключение, поэтому ничего делать не нужно
                        break;

                    // Убеждаемся, что поле не null
                    case CHECK_NOT_NULL:
                        $this->assertNotNull($actual_value, $this->showMessage() .
                            "\e[31m{$key} is null\e[39m");
                        break;

                    // Убеждаемся, что числовое значение в поле > 0
                    case CHECK_POSITIVE:
                        $this->assertGreaterThan(0, $actual_value, $this->showMessage($key));
                        break;

                    // Убеждаемся, что числовое значение в поле >= 0
                    case CHECK_NOT_NEGATIVE:
                        $this->assertGreaterThanOrEqual(0, $actual_value, $this->showMessage($key));
                        break;

                    // Убеждаемся, что числовое значение в поле != 0
                    case CHECK_NOT_ZERO:
                        $this->assertNotEquals(0, $actual_value, $this->showMessage($key));
                        break;

                    // Убеждаемся, что строковое значение в поле не является пустой строкой
                    case CHECK_STRING_NOT_EMPTY:
                        $this->assertGreaterThan(0, strlen($actual_value), $this->showMessage($key));
                        break;

                    // Убеждаемся, что в массиве 0 элементов
                    case CHECK_ARRAY_EMPTY:
                        $this->assertCount(0, $actual_value, $this->showMessage() .
                            "\e[31m{$key} has more than 0 elements\e[39m");
                        break;

                    // Убеждаемся, что в массиве есть элементы
                    case CHECK_ARRAY_NOT_EMPTY:
                        $this->assertNotCount(0, $actual_value, $this->showMessage() .
                            "\e[31m{$key} has 0 elements\e[39m");
                        break;

                    // Убеждаемся, что дата имеет корректный формат
                    case CHECK_DATETIME_FORMAT:
                        $this->assertRegExp('#\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\+\d{2}:\d{2}#',
                            $actual_value, $this->showMessage() . "\e[31m{$key} has wrong format\e[39m");
                        break;
                    // Убеждаемся, что датa имеет корректный формат
                    case CHECK_DATE_FORMAT:
                        $this->assertRegExp('#\d{4}-\d{2}-\d{2}#',
                            $actual_value, $this->showMessage() . "\e[31m{$key} has wrong format\e[39m");
                        break;
                    // Если константа не задана, просто убеждаемся, что значение поля совпадает в expected и actual объектах
                    default:
                        $this->assertEquals($expected_value, $actual_value, $this->showMessage($key));
                }
        }
    }

    /**
     * Вывод в консоль параметров запроса и тела ответа с цветной разметкой.
     * Используется для отладки упавших тестов
     *
     * Some styles for ANSI character attributes
     * @link http://www.cyberciti.biz/open-source/command-line-hacks/remark-command-regex-markup-examples/
     *
     * @param null $field_name Имя некорректного поля в теле ответа
     * @return string
     */
    protected function showMessage($field_name = null)
    {
        // Выводим http-метод и URL запроса
        $this->message = "===============================================================\n";
        $this->message .= $this->request->logRequestHttpMethod() . " ";
        // URL зеленого цвета, шрифт жирный
        $this->message .= "\e[32m\e[1m" . $this->request->logRequestUrl() . "\e[22m\e[39m\n";

        if($this->verbose)
        {
            // В наследниках выводим заголовки и body-параметры, если необходимо
            $this->showHeadersAndPostFields();

            // Выводим тело ответа с форматированием и цветовой разметкой
            $this->message .= "---------------------------------------------------------------\n";
            $this->message .= $this->highlight($this->response->getBody()) . "\n";
            $this->message .= "---------------------------------------------------------------\n";
        }

        // Если нужно, выводим имя некорректного поля с красной подсветкой
        if($field_name)
            $this->message .= "\n\e[31mInvalid {$field_name}\e[39m";

        return $this->message;
    }

    /**
     * Регулярки для метода higlight
     */
    protected static $pattern = [
        '#("[^"]*?")(\s*\:[^,]*?)#' => "\e[1;35m$1\e[0m$2",
        '#(.*?\s*\:\s*)(".*")#' => "$1\e[0;32m$2\e[0m",
        '#(.*?\s*\:\s*)(\d+|null)#ism' => "$1\e[0;34m$2\e[0m",
    ];

    /**
     * Цветная разметка JSON
     */
    public function highlight($jsonText, $prettyPrint = true)
    {
        if ($prettyPrint) {
            $jsonText = json_encode(json_decode($jsonText), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        foreach (self::$pattern as $currentPattern => $currentTemplate) {
            $jsonText = preg_replace($currentPattern, $currentTemplate, $jsonText);
        }
        return $jsonText;
    }

    /**
     * Вывод в консоль заголовков и body-параметров запроса с цветной разметкой.
     * Используется для отладки упавших тестов на FlampAPI
     *
     * Some styles for ANSI character attributes
     * @link http://www.cyberciti.biz/open-source/command-line-hacks/remark-command-regex-markup-examples/
     * @return string
     */
    protected function showHeadersAndPostFields()
    {
        // Выводим body-поля, если они есть
        if ($this->request->logRequestPostFields()) {
            $this->message .= "\n";
            foreach($this->request->logRequestPostFields() as $key => $value)
            {
                // Если параметр представляет собой массив
                if(is_array($value))
                    $value = "array(" . implode(", ", $value) . ")";
                // Жирный шрифт для названия параметра, синий цвет для значения
                $this->message .= "\e[1m" . $key . "\e[22m = " . "\e[36m" . $value . "\e[39m\n";
            }
        }

        // Выводим заголовки запроса, если они есть
        if ($this->request->logRequestHeaders()) {
            $this->message .= "\nHEADERS:\n";
            foreach($this->request->logRequestHeaders() as $key => $value)
                // Жирный шрифт для названия заголовка
                $this->message .= "\e[1m" . $key . "\e[22m" . ": " . $value . "\n";
        }
    }
}

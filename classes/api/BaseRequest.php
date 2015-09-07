<?php

/**
 * От этого класса наследуются классы работы с разными API. Например, с тестируемым.
 *
 * Класс реализован с помощью HTTP-клиента Guzzle.
 * Используется версия 3.9, т.к. по ней есть подробная документация (2ой линк)
 *
 * @link http://guzzle.readthedocs.org/en/latest/
 * @link http://api.guzzlephp.org/
 *
 */
class BaseRequest
{
    /** @var  Guzzle\Http\Client */
    protected $client;
    /** @var  Guzzle\Http\Message\Request | Guzzle\Http\Message\EntityEnclosingRequest */
    protected $request;
    /** @var  Guzzle\Http\Url */
    protected $url;

    protected $host;
    protected $path;
    protected $version;

    protected $http_method;
    protected $protocol;
    protected $method;
    protected $params = null;

    /**
     * @param $http_method
     * @param $protocol
     * @param $method
     * @param null $params Если параметры запроса отсутствуют, их можно в явном виде не задавать
     */
    public function __construct($http_method, $protocol, $method, $params = null)
    {
        // Создаём Guzzle-клиент
        $this->client = new \Guzzle\Http\Client();

        $this->http_method = $http_method;
        $this->protocol = $protocol;
        $this->method = $method;

        // Если задан массив параметров, ищем в нём булевые значения и приводим к строкам
        if (isset($params))
            $this->params = $this->boolToString($params);

        // Инициализируем в наследниках составные части URL
        $this->init();
    }

    /**
     * Приведение булевых значений к строковому виду
     * @param $params
     * @return array
     */
    protected function boolToString($params)
    {
        foreach ($params as $key => $value) {
            if (is_bool($value)) {
                $params[$key] = json_encode($value);
            }
        }
        return $params;
    }

    /**
     * Логику инициализации остальных атрибутов определяем в наследниках
     */
    protected function init()
    {
    }

    /**
     * Формирование базового URL
     */
    protected function buildUrl()
    {
        // Создаём основу URL из протокола и хоста
        $this->url = new \Guzzle\Http\Url($this->protocol, $this->host);
        // Добавляем путь в наследниках
        $this->createUrlPath();
    }

    /**
     * Логику формирования пути в URL определяем в наследниках
     */
    protected function createUrlPath()
    {
    }

    /**
     * Формирование параметров запроса
     */
    protected function prepareParams()
    {
        // Избавляемся от параметров со значением null
        $params = [];
        if (isset($this->params))
            foreach($this->params as $key => $value)
                if (isset($value))
                    $params[$key] = $value;

        // Для запросов с query помещаем параметры в URL, а массив параметров сбрасываем
        if (strcasecmp($this->http_method, 'GET') === 0
            || strcasecmp($this->http_method, 'DELETE') === 0) {

            $this->url->setQuery($params);
            $params = null;

        } // Для запросов с body проверяем наличие картинки, и если она есть - подставляем к пути @, чтобы Guzzle загрузил её
        elseif (isset($params['image']))
            $params['image'] = '@' . $params['image'];

        $this->params = $params;
    }

    /**
     * Отправка запроса и получение ответа
     * @return \Guzzle\Http\Message\Response
     */
    public function send()
    {
        // Выставляем с помощью cURL таймаут в 7 секунд
        $this->client->setConfig([
            'curl.options' => [CURLOPT_TIMEOUT => 7]
        ]);

        // Отключаем проверку SSL сертификатов, чтобы работали https-запросы
        $this->client->setSslVerification(false);

        // Формируем базовый URL и параметры запроса
        $this->buildUrl();
        $this->prepareParams();

        // Формируем запрос в наследниках
        $this->createRequest();

        // Выполняем запрос
        try {
            $response = $this->request->send();

        } // В случае кода, отличного от 20X, Guzzle выбрасывает исключение. Ловим его, чтобы передать ответ в тест
        catch (\Guzzle\Http\Exception\BadResponseException $exception) {
            $response = $exception->getResponse();
        }

        return $response;
    }

    /**
     * Логику формирования запроса определяем в наследниках
     */
    protected function createRequest()
    {
    }

    /**
     * Логирование URL запроса
     * @return string
     */
    public function logRequestUrl()
    {
        return $this->request->getUrl();
    }

    /**
     * Логирование http-метода запроса
     * @return string
     */
    public function logRequestHttpMethod()
    {
        return $this->http_method;
    }

}

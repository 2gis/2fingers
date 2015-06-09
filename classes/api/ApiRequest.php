<?php

/**
 * Класс расширяет базовый применимо к тестируемому API,
 * добавляя обработку заголовков, в т.ч. аксесс токенов (для OAuth 2.0)
 *
 * Подробнее про аксесс токены читай документацию по OAuth 2.0
 *
 */
class ApiRequest extends BaseRequest
{
    protected $headers;
    protected $access_token = null;

    /**
     * @param null $access_token
     */
    public function setAccessToken($access_token = null)
    {
        $this->access_token = $access_token;
    }

    /**
     * Инициализация необходимых атрибутов (составные части URL)
     */
    public function init()
    {
        $this->host = $GLOBALS['server']['host'];
        $this->path = Config()->api->path;
        $this->version = Config()->api->version;
    }

    /**
     * Формирование пути в URL
     */
    protected function createUrlPath()
    {
        // Путь начинается с api/
        $this->url->setPath($this->path);

        // Добавляем к пути версию API
        $this->url->addPath($this->version);

        // Добавляем к пути метод API
        $this->url->addPath($this->method);
    }

    /**
     * Формирование заголовков
     */
    protected function prepareHeaders()
    {

        // Здесь мог бы быть ваш код работы с заголовками

        // Аксесс токен всегда передаём в соответствующем заголовке
        if($this->access_token)
            $this->headers['Authorization'] = "Bearer {$this->access_token}";
    }

    /**
     * Формирование заголовков и запроса
     */
    public function createRequest()
    {
        //Формируем заголовки запроса
        $this->prepareHeaders();

        // Создаём запрос
        $this->request = $this->client->createRequest(
            $this->http_method, $this->url, $this->headers, $this->params);
    }

    /**
     * Логирование заголовков запроса
     * @return array Если есть заголовок Authorization
     */
    public function logRequestHeaders()
    {
        if ($this->request->getHeader('authorization'))
            $headers['Authorization'] = $this->request->getHeader('authorization')->__toString();

        if(isset($headers))
            return $headers;
    }

    /**
     * Логирование body-параметров запроса.
     * @return array Если запрос типа POST, PUT, PATCH
     */
    public function logRequestPostFields()
    {
        if ($this->request instanceof \Guzzle\Http\Message\EntityEnclosingRequest)
            return $this->request->getPostFields()->getAll();
    }
}

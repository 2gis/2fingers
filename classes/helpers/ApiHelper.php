<?php

/**
 * Класс для работы с тестируемым API.
 * Используем, например, когда нам не хватает данных для тестирования,
 * и мы вынуждены создавать их, дёргая методы тестируемого API.
 *
 * $id = Api()->testMethod();
 *
 */
class ApiHelper
{
    protected $method;
    protected $http_method;
    protected $params = null;
    protected $access_token;
    /** @var  ApiRequest */
    protected $request;
    /** @var  Guzzle\Http\Message\Response */
    protected $response;

    public function __construct()
    {
    }

    /**
     * Формирование запроса
     */
    protected function send()
    {
        $this->request = new ApiRequest($this->http_method, 'https', $this->method, $this->params);
        $this->request->setAccessToken($this->access_token);

        $this->response = $this->request->send();

        if (substr($this->getResponseBody()->code, 0, 2) !== '20')
            throw new Exception('FlampAPIHelper returned wrong response code');
    }

    public function getResponseBody()
    {
        return json_decode($this->response->getBody());
    }

    /**
     * Так может выглядеть метод, дёргающий какие-либо методы тестируемого API
     */
    public function testMethod()
    {
        $this->http_method = '';
        $this->method = '';
        $this->params = [];

        $this->access_token = '';

        $this->send();
        return ;
    }

}

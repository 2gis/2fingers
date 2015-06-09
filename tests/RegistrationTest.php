<?php

/**
 * Документация по методу
 * @link
 *
 * @author
 */
class RegistrationTest extends BaseTest
{

    public function providerRegistration()
    {
        return [
            [Generate()->email(), '1234', '55.432423', '83.343432', 'https', 200],
            [Generate()->email(), '1234', '55.432423', '83.343432', 'http', 404],
        ];
    }

    /**
     * @dataProvider providerRegistration
     */
    public function testRegistration($email, $password, $lon, $lat,
                                     $protocol, $expected_code)
    {
        // Задаём http-метод, метод API, параметры запроса и scopes
        $this->http_method = 'POST';
        $this->method = "users";
        $this->params = [
            'email' => $email,
            'password' => $password,
            'lon' => $lon,
            'lat' => $lat
        ];

        // Выполняем запрос и проверяем коды ответа
        $this->overProtocol($protocol)->send();
        $this->waitFor($expected_code);

        // Проверяем поля в ответе
        if ($expected_code === 200) {

            // Фактический результат
            $actual = $this->getResponseBody();

            // Ожидаемый результат
            $expected = [
                'user' => CHECK_STRING_NOT_EMPTY,
                'access_token' => CHECK_STRING_NOT_EMPTY
            ];

            // Сравниваем фактический и ожидаемый результат
            $this->assert($expected, $actual);
        }

    }
}

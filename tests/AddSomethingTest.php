<?php

/**
 * Документация по методу
 * @link
 *
 * @author
 */
class AddSomethingTest extends BaseTest
{

    public function providerAddSomething()
    {
        // Cлучайный объект из БД
        $object = Db()->entity('object')->isSomething(true)->getRandomEntity();

        return array(
            // без дополнительных параметров
            [$object, Config()->roles->user, 201],
            // гостем
            [$object, Config()->roles->guest, 401],
        );
    }

    /**
     * @dataProvider providerAddSomething
     */
    public function testAddSomething($object, $role, $expected_code)
    {
        // Задаём http-метод, метод API, параметры запроса и scopes
        $this->http_method = 'POST';
        $this->method = "objects/{$object->id}/something";

        // Выполняем запрос и проверяем коды ответа
        $this->asUser($role)->send();
        $this->waitFor($expected_code);

        // Проверяем поля в ответе
        if ($expected_code === 201) {

            // Проверка на повторное действие
            $this->asUser($role)->send();
            $this->waitFor(201);

        }

    }

}
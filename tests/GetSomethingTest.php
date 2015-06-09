<?php
/**
 * Документация по методу
 * @link
 *
 * @author
 */
class GetSomethingTest extends BaseTest
{

    public function providerGetSomething()
    {
        $object = Db()->table('table')->getRow();

        return array(

            // с ролью гость
            [$object, Config()->roles->guest, 200],
            // с ролью user
            [$object, Config()->roles->user, 200],

        );
    }

    /**
     * @dataProvider providerGetSomething
     */
    public function testGetSomething($object, $role, $expected_code)
    {
        // Задаём http-метод, метод API, параметры запроса и scopes
        $this->http_method = 'GET';
        $this->method = "objects/{$object->id}";

        // Выполняем запрос и проверяем коды ответа
        $this->asUser($role)->send();
        $this->waitFor($expected_code);

        // Проверяем поля в ответе
        if ($expected_code === 200) {

            // Фактический результат
            $actual = $this->getResponseBody()->object;

            // Ожидаемый результат
            $expected = [
                'id' => $object->id,
                'name' => $object->name,
                'type' =>$object->type,
                'status' => 1,
                'is_hidden' => false,
                'project' => CHECK_EXIST,
                'parent_id' => CHECK_NOT_NEGATIVE,
                'date_created' => CHECK_DATETIME_FORMAT,
                'self_link' => CHECK_STRING_NOT_EMPTY
            ];

            // Сравниваем фактический и ожидаемый результат
            $this->assert($expected, $actual);

        }
    }
}
<?php

/**
 * Класс-генератор рандомных сущностей.
 * Используется в тестах как источник синтетических случайных данных
 */
class TestHelper
{
    /**
     * Генерация случайного тестового e-mail адреса
     * @param string $prefix
     * @return string
     */
    public function email($prefix = 'at_')
    {
        return $prefix . hash('crc32', time() . mt_rand()) . '@test.ru';
    }

    /**
     * Генерация тестового объекта "пользователь" со емейлом и паролем
     * @param null $email
     * @param string $password
     * @return object
     */
    public function user($email = null, $password = '1234')
    {
        $user = new stdClass();
        if (is_null($email)) {
            $user->email = $this->email();
        } else {
            $user->email = $email;
        }
        $user->password = $password;
        return $user;
    }

    /**
     * Генерация случайного текста
     * @param $length
     * @param string $charset
     * @return string
     */
    public function text($length, $charset = '1234567890QWERTYUIOPASDFGHJKLZXCVBNMqwertyuiopasdfghjklzxcvbnm_ ')
    {
        $str = '';
        $count = strlen($charset);

        while ($length--) {
            $str .= $charset[mt_rand(0, $count - 1)];
        }

        return $str;
    }

}

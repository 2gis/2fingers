<?php

/**
 * Класс для получения данных из конфига.
 * Используется в тестах как источник заранее известных, статических данных
 *
 */
class ConfigHelper
{
    public $options_array;

    /**
     * В конструкторе поле options_array инициализируется как весь конфиг
     */
    public function __construct()
    {
        // Подгрузка конфига через $GLOBALS осуществляется в bootstrap.php
        $this->options_array = $GLOBALS['config'];
    }

    /**
     * Магический метод дёргается при вызове через стрелочку, напр. Config()->roles...
     * @param $param
     * @throws Exception
     */
    public function __get($param) {

        // Если указанный ключ действительно есть в конфиге
        if (array_key_exists($param, $this->options_array)) {
            
            // Если по ключу получили набор записей в виде массива, нужно идти дальше вглубь по дереву
            if (is_array($this->options_array[$param])) {

                $this->options_array = $this->options_array[$param];
                // Возвращаем экземпляр текущего класса, после чего в цепочке выполнится следующий вызов через стрелку
                return $this;
            }
            // Если по ключу не массив - значит, мы добрались до конкретной записи, которую и возвращаем
            else 
                return $this->options_array[$param];
        }
        else
            throw new Exception("Parameter not found: {$param}");
    }

    /**
     * Метод возвращает свойство options_array,
     * чтобы можно было на любом шаге рекурсии получать набор записей конфига в виде массива
     * @return array
     */
    public function asArray()
    {
        return $this->options_array;
    }
}


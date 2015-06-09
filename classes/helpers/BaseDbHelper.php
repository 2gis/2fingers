<?php

/**
 * Класс для низкоуровневой работы с БД.
 * Использует непосредственно системный интерфейс PDO
 *
 * @link http://phpfaq.ru/pdo
 * @link http://habrahabr.ru/post/137664/
 * @link http://php.net/manual/ru/book.pdo.php
 */
class BaseDbHelper
{
    /** @var PDO */
    protected $db;
    protected $verbose;

    /**
     * Подключаемся к БД с помощью системного класса PDO
     * @param $db_name
     * @param $db_host
     * @param $db_user
     * @param $db_password
     * @param bool $verbose
     */
    public function __construct($db_name, $db_host, $db_user, $db_password, $verbose = false)
    {
        $this->db = new PDO("pgsql:dbname={$db_name};host={$db_host};",
            $db_user, $db_password);
        $this->verbose = $verbose;
    }

    /**
     * Получение из БД нескольких строк результата по переданному SQL-запросу
     * @param $sql
     * @return array При отсутствии результатов SQL-запроса вернётся пустой массив
     */
    public function fetchAll($sql)
    {
        return $this->query($sql)->fetchAll();
    }

    /**
     * Получение из БД строки результата по переданному SQL-запросу
     * @param $sql
     * @return object|false При отсутствии результатов SQL-запроса вернётся false
     */
    public function fetch($sql)
    {
        return $this->query($sql)->fetch();
    }

    /**
     * Непосредственно выполнение SQL-запроса и получение данных
     * @param $sql
     * @return PDOStatement http://php.net/manual/ru/class.pdostatement.php
     * @throws Exception При ошибке в SQL-запросе
     */
    protected function query($sql)
    {
        if ($this->verbose)
            echo($sql . "\n\n");

        $query = $this->db->query($sql, PDO::FETCH_OBJ);
        if (!$query)
            throw new Exception('Error in SQL query');

        return $query;
    }

    /**
     * Выполнение SQL-запроса. Используется для запросов DELETE
     * @param $sql
     * @throws Exception При ошибке в SQL-запросе
     */
    public function execute($sql)
    {
        if ($this->verbose)
            echo($sql . "\n\n");

        $success = $this->db->prepare($sql)->execute();
        if (!$success)
            throw new Exception('Error in SQL query');
    }

    /**
     * Чтобы отключиться от БД, когда она становится не нужна, достаточно присвоить PDO-объекту null
     */
    public function __destruct()
    {
        $this->db = null;
    }
    
}
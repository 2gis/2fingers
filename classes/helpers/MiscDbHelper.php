<?php

/**
 * Класс для высокоуровневой работы с БД.
 * Используется в тестах как источник реальных случайных данных.
 * Данные получаем с помощью цепочных вызовов, описывающих параметры желаемого объекта.
 *
 * Класс нужно кастомизировать под структуру вашей БД!
 * В частности, описать все методы-звенья цепочки для получения нужной единицы данных.
 * Здесь лишь приведены самые общие методы и возможные примеры использования.
 *
 * Примеры:
 *
 * получить случайное нескрытое фото с комментариями:
 * Db()->entity('photo')->isHidden(false)->hasComments(true)->getRandomEntity();
 *
 * получить отзыв с id=666:
 * Db()->entity('review')->withId(666)->getRandomEntity();
 *
 * получить сущность, присоединив какую-либо таблицу и отфильтровав в этой таблице строки по условию isSomething():
 * Db()->entity('review')->joinSomething()->isSomething(true)->getRandomEntity();
 *
 * получить случайный сложный метаобъект, хранящийся в разных таблицах, по заданному условию
 * Db()->meta()->isSomething(true)->getRandomMeta();
 *
 * проверить, существуют ли в таблице table строки по указанному условию:
 * Db()->table('table')->isSomething(true)->checkIfExist();
 *
 * получить из таблицы table все записи по указанному условию isSomething:
 * Db()->table('table')->isSomething(true)->getRows();
 *
 * получить из таблицы table поле id в 5 строках:
 * Db()->table('table')->fields('id')->getRows(5);
 *
 * сформировать подзапрос и затем получить данные, отфильтрованные по этому подзапросу:
 * $subquery = Db()->table('table1')->fields('id')->getSubquery();
 * Db()->table('table2')->idIn($subquery)->getRows();
 *
 * получить из таблицы table все записи, отсортированные по id:
 * Db()->table('table')->getSortedList();
 *
 * получить из таблицы table все записи, отсортированные по type в убывающем порядке:
 * Db()->table('table')->getSortedList('type DESC');
 */

class MiscDbHelper
{
    /** Количество записей для случайной выборки */
    const RANDOM_LIMIT = 1000;

    /** @var BaseDbHelper */
    protected $db;

    /** Список запрашиваемых полей для инструкции SELECT */
    protected $what;
    /** Условие для инструкции WHERE */
    protected $where;

    /** Имя таблицы, из которой осуществляется выборка, либо JOIN */
    protected $from;
    /** Текущая присоединяемая таблица. Используется, если есть JOIN */
    protected $current_table;
    /** Основная таблица. Используется, если есть JOIN */
    protected $main_table;
    /** Количество JOINов */
    protected $join_count;

    /** Статическое поле - двумерный массив: [таблица][id сущности]
     * Используется для запоминания сущностей, уже использованных в тестах */
    protected static $used_ids = array();

    /**
     * Подключение к БД
     * @param bool $verbose Определяет, что нужно вывести текущий SQL-запрос в консоль
     */
    public function __construct($verbose = false)
    {
        $this->db = new BaseDbHelper($GLOBALS['server']['dbname'], $GLOBALS['server']['dbhost'],
            Config()->db->user, Config()->db->password, $verbose);

        $this->newWhere();
        $this->what = '*';
        $this->join_count = 0;
    }

    /**
     * Инструкция SELECT.
     * @return string
     */
    protected function select()
    {
        $select = "SELECT {$this->what}";
        return $select;
    }

    /**
     * Инструкция FROM.
     * Осуществляет выборку из текущей таблицы
     * @param bool $with_alias Нужно ли оформить FROM с алиасом (для подзапросов)
     * @return string
     */
    protected function from($with_alias = false)
    {
        if ($with_alias)
            $from_clause = " FROM ({$this->from}) as foo";
        else
            $from_clause = " FROM {$this->from}";
        return $from_clause;
    }

    /**
     * Возвращает текущее условие WHERE
     * @return string
     */
    protected function where()
    {
        return $this->where;
    }

    /**
     * Инструкция WHERE по умолчанию
     */
    protected function newWhere()
    {
        // Всегда истинное выражение 1 = 1 позволяет добавлять произвольное количество операндов через AND
        $this->where = "\nWHERE 1 = 1";
    }

    /**
     * Инструкция DELETE FROM.
     * Удаляет из текущей таблицы
     * @return string
     */
    protected function deleteFrom()
    {
        $delete_from = "DELETE FROM {$this->from}";
        return $delete_from;
    }

    /**
     * В конце каждого SQL-запроса следует использовать разделитель - точку с запятой
     * @return string
     */
    protected function semicolon()
    {
        return ';';
    }

    /**
     * Инструкция ORDER BY.
     * Определяет порядок строк в выборке
     * @param string $order По умолчанию сортируем случайным образом
     * @return string
     */
    protected function order($order = 'RANDOM()')
    {
        return " ORDER BY {$order}";
    }

    /**
     * Инструкция LIMIT.
     * Определяет количество строк в выборке
     * @param int|string $limit По умолчанию выбираем одну строку
     * @return string
     */
    protected function limit($limit = 1)
    {
        return " LIMIT {$limit}";
    }

    /**
     * Ускоренный алгоритм получения случайной строки из выборки.
     * Выбираем первую 1000 записей, после чего выбираем оттуда одну случайную строку
     * @param string $query Основной запрос
     * @return string
     */
    protected function acceleratedRandom($query)
    {
        $this->what = '*';
        // добавляем limit 1000, чтобы ускорить выборку, и пустые строки, чтобы повысить читабельность вложенного запроса
        $this->from = "\n\n{$query}" . $this->limit(self::RANDOM_LIMIT) . "\n\n";

        $accelerated_query = $this->select() .
            $this->from(true) .
            $this->order() .
            $this->limit() .
            $this->semicolon();

        return $accelerated_query;
    }

    /**
     * Условие определяет тип сущности, запрашиваемой в тесте.
     * В соответствии с типом требуемой сущности происходит выборка из нужной таблицы
     * @param $entity
     * @return $this
     */
    public function entity($entity)
    {
        // Таблица - это сущность во множественном числе
        $this->table($entity . 's');
        return $this;
    }

    /**
     * Условие определяет таблицу(ы), из которой будет производиться выборка
     * @param $table
     * @return $this
     */
    public function table($table)
    {
        $this->from = $this->current_table = $this->main_table = $table;
        return $this;
    }

    /**
     * Проверка на наличие результатов выполнения SQL-запроса
     * @param $result
     * @throws Exception При отсутствии в БД результатов по запросу
     */
    protected function checkResult($result)
    {
        if (!$result)
            throw new Exception('No results from database');
    }

    /**
     * Отправка готового запроса в БД, проверка наличия результата и его возвращение
     *
     * @param string $query SQL-запрос
     * @param bool $all Показывает, что нужно получить несколько строк из БД
     * @return object
     */
    protected function returnResult($query, $all = false)
    {
        $result = $all ? $this->db->fetchAll($query) : $this->db->fetch($query);
        $this->checkResult($result);
        return $result;
    }

    protected function returnRandomResult($query)
    {
        $result = $this->returnResult($query);

        $this->addToUsedIds($result);
        return $result;
    }

    /**
     * @param $result
     */
    private function addToUsedIds($result)
    {
        $t = $this->main_table;

        // Здесь может быть логика, которая изолирует данные во избежание конфликтов в тестах.
        // Например, если для теста взяли объект фото, можно здесь изолировать (добавить в список использованных)
        // все комментарии к этому фото.

        // Записываем id главной сущности и её тип (таблицу) в список использованных
        self::$used_ids[$t][] = $result->id;
    }


    /**
     * Универсальная конструкция SELECT FROM WHERE
     * @return string
     */
    protected function selectFromWhere()
    {
        $query = $this->select() .
            $this->from() .
            $this->where();
        return $query;
    }

    /**
     * Получение случайной сущности из БД. Ставится в конец цепочки вызова.
     * Проверяет каждую сущность на уникальность и добавляет взятую в список уникальных.
     *
     * Вызов может выглядеть так:
     *
     * Db()->entity('review')->...->getRandomEnity();
     *
     * @return object
     * @throws Exception При отсутствии в БД результатов по запросу
     */
    public function getRandomEntity()
    {
        // Проверяем, что сущность уникальная
        $this->checkIfUnique();

        $query = $this->acceleratedRandom($this->selectFromWhere());

        return $this->returnRandomResult($query);
    }

    /**
     * Условие, определяющее уникальность сущности.
     * Т.е. берём сущность, которая ещё не использовалась в текущем запуске тестов
     * @return string
     */
    public function checkIfUnique()
    {
        $t = $this->main_table;

        // Исключаем из выборки использованные в тестах сущности
        if (isset(self::$used_ids[$t]))
            $this->idNotIn(self::$used_ids[$t], "{$t}.id");

        return $this;
    }

    /**
     * Условие создает сложную метасущность (например, "пользователь"),
     * хранящуюся в разных таблицах.
     *
     *
     * В тестах ее использование может выглядеть так:
     *
     * Db()->meta()...->getRandomMeta();
     *
     * @return $this
     */
    public function meta()
    {
        $this->table('first_table');

        $t1 = $this->main_table;
        $t2 = 'second_table';

        // Формируем условие FROM как JOIN нужных таблиц
        $this->from .= $this->join($t1, $t2, 'id', 'second_id');

        // Здесь можно наложить какие-нибудь условия на метасущность
        // Например, что у пользователя всегда непротухший токен

        return $this;
    }


    /**
     *
     * Проверка, существует ли в указанной таблице сущность с заданными параметрами
     * @return bool
     */
    public function checkIfExist()
    {
        $query = $this->selectFromWhere() .
            $this->semicolon();

        $check = $this->db->fetch($query);
        return $check ? true : false;
    }


    /**
     * Типичное условие для блока WHERE. Из таких и составляются цепочные вызовы.
     * Задаёт значение для булевого поля в таблице.
     *
     * Ниже есть аналогичный пример для поля id.
     *
     * @param $is_something
     * @return $this
     */
    public function isSomething($is_something)
    {
        $this->where .= " AND {$this->current_table}.is_something = " . ($is_something ? 'true' : 'false');
        return $this;
    }

    /**
     * Условие определяет id сущности
     * @param $id
     * @return $this
     */
    public function withId($id)
    {
        $this->where .= " AND {$this->current_table}.id = {$id}";
        return $this;
    }

    /**
     * Условие IS NULL для поля
     * @param $field_name
     * @return $this
     */
    protected function isNull($field_name)
    {
        $this->where .= " AND {$this->current_table}.{$field_name} IS NULL";
        return $this;
    }

    /**
     * Более сложное условие - когда нужно приджойнить ещё одну таблицу, а то и две.
     * После этого вызова все следующие условия в цепочке будут применятся к текущей, т.е. приджойненной, таблице.
     *
     * @return $this
     */
    public function joinSomething()
    {
        $t1 = $this->main_table;
        $t2 = 'some_table';

        // Формируем JOIN
        $this->from .= $this->join($t1, $t2, 'id', 'some_id');

        // Запоминаем присоединенную таблицу
        $this->current_table = $t2;

        return $this;
    }

    /**
     * Тоже сложное условие с джойном.
     * Используем, если по логическому признаку мы либо приджоиниваем таблицу и ищем совпавшие строки (true),
     * либо, наоборот, делаем LEFT JOIN и ищем строки в левой таблице без совпадений в правой (false).
     *
     * @param $bool_flag
     * @return $this
     */

    public function joinSomethingAndCheckMAtches($bool_flag)
    {
        $this->smartJoin($this->main_table, 'some_table', 'id', 'some_id', $bool_flag);
        return $this;
    }


    /**
     * Конструкция id IN. По умолчанию берётся id в текущей таблице
     * @param $values int|array|string Набор значений. Может быть числом, массивом чисел или строкой (подзапросом)
     * @param $custom_id_field_name null|string Можно задать поле, отличное от id
     * @return $this
     */
    public function idIn($values, $custom_id_field_name = null)
    {
        $this->where .= $this->prepareInOperator($values, $custom_id_field_name, false);
        return $this;
    }

    /**
     * Конструкция id NOT IN. По умолчанию берётся id в текущей таблице
     * @param int|array|string $values Набор значений. Может быть числом, массивом чисел или строкой (подзапросом)
     * @param null|string $custom_id_field_name Можно задать поле, отличное от id
     * @return $this
     */
    public function idNotIn($values, $custom_id_field_name = null)
    {
        $this->where .= $this->prepareInOperator($values, $custom_id_field_name, true);
        return $this;
    }

    /**
     * Внутренняя логика для методов idIn() и idNotIn()
     * @param int|array|string $values Набор значений
     * @param null|string $custom_id_field_name Название поля
     * @param bool $not Определяет, нужно ли добавить NOT к оператору IN
     * @return string
     */
    protected function prepareInOperator($values, $custom_id_field_name, $not)
    {
        // Если набор значений это подзапрос, формируем для него красивые отступы
        if (is_string($values))
            $values = "\n\n{$values}\n\n";
        // Если набор значений передан в виде массива, формируем из него строку
        if (is_array($values))
            $values = implode(',', $values);

        // Если кастомное название поле не задано, берём поле id из текущей таблицы
        if ($custom_id_field_name)
            $field = $custom_id_field_name;
        else
            $field = "{$this->current_table}.id";

        $condition = " AND {$field}" . ($not ? ' NOT' : '') . " IN ({$values})";
        return $condition;
    }

    /**
     * Конструкция EXISTS
     * @param string $subquery
     * @return $this
     */
    public function exists($subquery)
    {
        $this->where .= " AND EXISTS (\n\n{$subquery}\n\n)";
        return $this;
    }

    /**
     * Конструкция NOT EXISTS
     * @param $subquery
     * @return $this
     */
    public function notExists($subquery)
    {
        $this->where .= " AND NOT EXISTS (\n\n{$subquery}\n\n)";
        return $this;
    }

    /**
     * Формирование инструкции JOIN ... ON ...
     * @param $t1
     * @param $t2
     * @param $field1
     * @param $field2
     * @return $this
     */
    protected function join($t1, $t2, $field1, $field2)
    {
        // Для того чтобы поле id трактовалось в результатах однозначно как id главной таблицы
        if ($this->join_count === 0)
            $this->what .= ", {$this->main_table}.id AS id";

        $this->join_count++;

        return "\nJOIN {$t2} ON {$t1}.{$field1} = {$t2}.{$field2}";
    }

    /**
     * Присоединение таблицы с целью установить наличие либо отсутсвие совпадений
     * @param $t1
     * @param $t2
     * @param $field1
     * @param $field2
     * @param bool $condition В зависимости от этого условия получаем строки, для которых есть либо нет совпадений в присоединяемой таблице
     * @return $this
     */
    protected function smartJoin($t1, $t2, $field1, $field2, $condition)
    {
        // Запоминаем присоединенную таблицу
        $this->current_table = $t2;

        $join = $this->join($t1, $t2, $field1, $field2);

        if($condition)
            $this->from .= $join;
        // Если нужен пользователь без связанных данных в присоединямой таблице, делаем LEFT JOIN
        else {
            $this->from .= "\nLEFT" . $join;
            $this->isNull($field2);
        }
        return $this;
    }


    /**
     * Метод возвращает одну случайную строку из таблицы по заданному условию
     * @return array
     * @throws Exception
     */

    public function getRow()
    {
        $query = $this->acceleratedRandom($this->selectFromWhere());
        return $this->returnResult($query);
    }

    /**
     * Метод возвращает все строки таблицы по заданному условию
     * @param string $limit При необходимости можно указать количество строк
     * @return array
     * @throws Exception
     */

    public function getRows($limit = 'ALL')
    {
        $query = $this->selectFromWhere() .
            $this->limit($limit) .
            $this->semicolon();

        $result = $this->db->fetchAll($query);
        return $result;
    }

    /**
     * Метод возвращает остортированные строки таблицы по заданному условию
     * @param string $order При необходимости можно указать поле для сортировки
     * @return array
     * @throws Exception
     */

    public function getSortedList($order = 'id')
    {
        $query = $this->selectFromWhere() .
            $this->order($order) .
            $this->semicolon();

        return $this->returnResult($query, true);
    }

    /**
     * Метод позволяет, при необходимости, указать необходимые для выборки поля
     * @param string $what
     * @return $this
     */

    public function fields($what)
    {
        $this->what = $what;
        return $this;
    }

    /**
     * Метод используется для формирования подзапросов
     *
     * Например,
     * $subquery = Db()->table('some_table')->...->getSubquery();
     * $comment = Db()->table('comment')->withId($subquery)->getRandomEntity();
     *
     *
     */
    public function getSubquery()
    {
        return $this->selectFromWhere();
    }

}

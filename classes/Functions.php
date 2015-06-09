<?php
/**
 * В этом файле описаны функции для получение нужных данных в тестах.
 * Идея в том, чтобы можно было писать удобно и кратко, например, Config()->...
 * При этом такой вызов создаст объект нужного Хелпера и позволит получить нужные данные.
 *
 * Поддерживаются следующие источники данных: конфиг, БД, тестируемое API, случайный генератор.
 */

function Config()
{
    return new ConfigHelper();
}

function Db($verbose = false)
{
    return new MiscDbHelper($verbose);
}

function Api()
{
    return new ApiHelper();
}

function Generate()
{
    return new TestHelper();
}
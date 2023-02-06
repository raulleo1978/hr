<?php

namespace Manager;

class User
{
    const limit = 10;

    /**
     * Возвращает пользователей старше заданного возраста.
     * @param int $ageFrom
     * @return array
     */
    function getUsers(int $ageFrom): array
    {
        $ageFrom = (int)trim($ageFrom);

        return \Gateway\User::getUsers($ageFrom);
    }

    /**
     * Возвращает пользователей по списку имен.
     * @return array
     */
    public static function getByNames(): array 
    {
        // Для того чтобы избавиться от потенциального cross-site scripting или атаки XSS, добавил следующие коды.
        /*
            Непроверенный ввод: код использует $_GET['names'] для получения списка имен для извлечения пользователей, 
        но не проверяет ввод, чтобы убедиться, что он соответствует ожидаемому формату или размеру. 
        Это может привести к неожиданному поведению или сбоям.
        */
        if(empty($_GET['names']) || !is_array($_GET['names'])){
            throw new \Exception('Неверный ввод: ожидался массив имен.');
        }

        /*
            Удаляем любые начальные или конечные пробелы из каждого элемента в массиве $_GET['names']
        и возвращает измененный массив. Функция "trim()" применяется к каждому элементу в массиве $_GET['names']
        и удаляет все пробелы в начале и в конце каждого элемента. Полученный массив затем сохраняется
        в переменной $names.
            Удоляем все пустые елементы в массиве $names.
            Затем приводим количество элементов в соответсвие с лимитом const limit = 10.
        */
        $names = array_map('trim',$_GET['names']);
        $names = array_filter($names, function($n){
            return !empty($n);
        });
        $names = array_slice($names,0,self::limit);

        $users = [];
        foreach ($names as $name) {
            $users[] = \Gateway\User::user($name);
        }

        return $users;
    }

    /**
     * Добавляет пользователей в базу данных.
     * @param $users
     * @return array
     */
    public function users($users): array
    {
        /* 
        Для того чтобы избавиться от потенциальной атаки через SQL-injection перепишем код.
        Будем использовать функцию quote для экранирования и заключать в кавычки значение 
        для использования в SQL-запросе.
        */
        
        $ids = [];
        \Gateway\User::getInstance()->beginTransaction();
        foreach ($users as $user) {
            try {
                $name = \Getway\User::getInstance()->quote(user['name']);
                $lastName = \Gateway\User::getInstance()->quote($user['lastName']);
                $age = (int)\Gateway\User::getInstance()->quote($user['age']); 
                \Gateway\User::add($name, $lastName, $age);
                \Gateway\User::getInstance()->commit();
                $ids[] = \Gateway\User::getInstance()->lastInsertId();
            } catch (\Exception $e) {
                \Gateway\User::getInstance()->rollBack();
                throw $e;
            }
        }

        return $ids;
    }
}
<?php

namespace Gateway;

use PDO;

/* 
для того чтобы избежать хардкодинга конфигурационных данных подключения и лимитов, 
а также для более эфективного кода создадим class cinfug, куда пропишем подключение config.php
*/
class Config
{
    private static $instance;
    private static $config;

    public static function getInstance(): Config
    {
        if (is_null(self::$instance)) {
            self::$config = include 'config.php';
            self::$instance = new Config();
        }
        return self::$instance;
    }

    public static function getConfig(): array
    {
        return self::$config;
    }

    public static function getDbDns(): string
    {
        return self::$config['db_dns'];
    }

    public static function getDbUser(): string
    {
        return self::$config['db_user'];
    }

    public static function getDbPass(): string
    {
        return self::$config['db_pass'];
    }

    public static function getUsersLimit(): int
    {
        return self::$config['users_limit'];
    }
}

class User
{
    /**
     * @var PDO
     */
    public static $instance;
 
    /**
     * Реализация singleton
     * @return PDO
     */
    public static function getInstance(): PDO
    {
        if (is_null(self::$instance)) {
            try {
                $config = Config::getInstance();
                $dsn = $config->getDbDns();
                $user = $config->getDbUser();
                $password = $config->getDbPass();
                self::$instance = new PDO($dsn, $user, $password);
            }catch (PDOException $e){
                exit('Возникла ошибка при подключении к базе данных'.$e->getMessage());
            }
        }
        return self::$instance;
    }

    /**
     * Возвращает список пользователей старше заданного возраста.
     * @param int $ageFrom
     * @return array
     */
    /*
    Для того чтобы избежать SQL-injection перепишем код тут
    */

    public static function getUsers(int $ageFrom): array
    {
        $config = Config::getInstance();
        $limit = $config->getUsersLimit();
        $stmt = self::getInstance()->prepare("SELECT id, name, lastName, `from`, age, settings FROM Users WHERE age > ? LIMIT ?");
        $stmt->execute([$ageFrom, $limit]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $users = [];
        foreach ($rows as $row) {
            $settings = json_decode($row['settings']);
            $users[] = [
                'id' => $row['id'],
                'name' => $row['name'],
                'lastName' => $row['lastName'],
                'from' => $row['from'],
                'age' => $row['age'],
                'key' => $settings->key, //Используется ->key вместо ['key'] для доступа к свойству объекта $settings.
            ];
        }

        return $users;
    }

    /**
     * Возвращает пользователя по имени.
     * @param string $name
     * @return array
     */
    /*
    Также существует уязвимость для атаки SQL-injection, 
    поэтому важно использовать параметризованный запрос с bindParam для предотвращения проблемы
    */
    public static function user(string $name): array
    {
        $stmt = self::getInstance()->prepare("SELECT id, name, lastName, from, age, settings FROM Users WHERE name = :name");
        $stmt->bindParam(':name', $name);
        $stmt->execute();
        
        $user_by_name = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user_by_name) {
            return [];
        }

        return [
            'id' => $user_by_name['id'],
            'name' => $user_by_name['name'],
            'lastName' => $user_by_name['lastName'],
            'from' => $user_by_name['from'],
            'age' => $user_by_name['age'],
        ];
    }

    /**
     * Добавляет пользователя в базу данных.
     * @param string $name
     * @param string $lastName
     * @param int $age
     * @return string
     */
    /* 
    такаяже проблема как и в предыдущей функции user(string $name)
    */
    public static function add(string $name, string $lastName, int $age): string
    {
        $sth = self::getInstance()->prepare("INSERT INTO Users (name, lastName, age) VALUES (:name, :lastName, :age)");
        $sth->bindParam(':name', $name, PDO::PARAM_STR);
        $sth->bindParam(':lastName', $lastName, PDO::PARAM_STR);
        $sth->bindParam(':age', $age, PDO::PARAM_INT);
        $sth->execute();

        return self::getInstance()->lastInsertId();
    }
}
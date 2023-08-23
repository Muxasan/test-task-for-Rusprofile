<?php

require_once('bootstrap.php');

// Создаем экземпляр класса Database
$database = new Database(HOST, DBNAME, USER, PASS);
// Устанавливаем соединение с БД
$database->connect();

// Компании с наибольшей суммарной задолженностью
$result = $database->select("SELECT organizations.name, SUM(taxes.total_debt) AS total_debt
    FROM organizations
    JOIN taxes ON organizations.inn = taxes.organization_inn
    GROUP BY organizations.inn
    ORDER BY total_debt DESC
    LIMIT 5;");
if ($result) {
    echo "Компании с наибольшей суммарной задолженностью:<br>";
    foreach ($result as $row) {
        echo $row['name'].': '.$row['total_debt']." рублей<br>";
    }
}
echo '<br>';

// Общая задолженность всех компаний по каждому виду налога
$result = $database->select("SELECT taxes.name, SUM(taxes.debt) AS total_debt
    FROM taxes
    GROUP BY taxes.name;");
if ($result) {
    echo "Общая задолженность всех компаний по каждому виду налога:<br>";
    foreach ($result as $row) {
        echo $row['name'] .': '.$row['total_debt']. " рублей<br>";
    }
}

echo '<br>';
// Средняя задолженность по регионам (код региона — первые две цифры ИНН)
$result = $database->select("SELECT LEFT(organizations.inn, 2) AS region_code, AVG(taxes.total_debt) AS average_debt
    FROM organizations
    JOIN taxes ON organizations.inn = taxes.organization_inn
    GROUP BY region_code;");
if ($result) {
    echo "Cредняя задолженность по регионам:<br>";
    foreach ($result as $row) {
        echo $row['region_code'] .': '.round($row['average_debt'], 2). " рублей<br>";
    }
}

// Закрываем соединение с БД
$database->disconnect();

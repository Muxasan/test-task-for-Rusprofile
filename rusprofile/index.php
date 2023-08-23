<?php
require_once('bootstrap.php');
try {
    $siteParser = new SiteParser();
    
    if ($siteParser->parseDom(URL_TO_DATASET)) {
        $dom = $siteParser->getData();
    
        if (!empty($dom)) {
            $datasetUrl = $siteParser->getDatasetUrl($dom);
        }
        $pathToDataSetFile = $_SERVER['DOCUMENT_ROOT'].PATH.date("Ym").'.zip';
    
        $siteParser->saveFileFromUrl($datasetUrl, $pathToDataSetFile);
    
        $files = $siteParser->getFilesFromZipGenerator($pathToDataSetFile);
    } else {
        throw new Exception("Ошибка при парсинге данных");
    }
    
    
    // Создаем экземпляр класса Database
    $database = new Database(HOST, DBNAME, USER, PASS);
    // Устанавливаем соединение с БД
    $database->connect();

    foreach ($files as $file) {
        try {
            $xml = new XMLParser($file);
    
            foreach ($xml->getOrganization() as [$orgName, $inn]) {
                // Вставка данных в таблицу organizations
                $params = [
                    ':inn' => $inn,
                    ':name' => $orgName
                ];
                $database->insert("INSERT INTO organizations (name, inn) VALUES (:name, :inn)", $params);
                
                foreach ($xml->getTaxes() as [$taxName, $taxDebt, $penalty, $fine, $totalDebt]) {
                    // Вставка данных в таблицу taxes
                    $params = [
                        ':organization_inn' => $inn,
                        ':name' => $taxName,
                        ':debt' => $taxDebt,
                        ':penalty' => $penalty,
                        ':fine' => $fine,
                        ':total_debt' => $totalDebt
                    ];
                    $database->insert("INSERT INTO taxes (organization_inn, name, debt, penalty, fine, total_debt) 
                        VALUES (:organization_inn, :name, :debt, :penalty, :fine, :total_debt)", $params);
                }
            };
        } catch (PDOException $e) {
            throw new Exception("Ошибка при загрузке данных из xml в базу: " . $e->getMessage());
        }
    }


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
} catch (Exception $e) {
    echo $e;
}

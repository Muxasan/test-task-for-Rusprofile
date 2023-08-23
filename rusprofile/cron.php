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
} catch (Exception $e) {
    echo $e;
}

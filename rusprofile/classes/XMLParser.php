<?php

class XMLParser
{

    private $xml;
    private $currentDoc;

    public function __construct($xmlFile)
    {
        $this->xml = simplexml_load_file($xmlFile);
    }

    public function getOrganization(): Generator
    {
        foreach ($this->xml->Документ as $doc) {
            $orgName = (string)$doc->СведНП['НаимОрг'];
            $inn = (string)$doc->СведНП['ИННЮЛ'];
            $this->currentDoc = $doc;
            yield [$orgName, $inn];
        }
    }

    public function getTaxes(): Generator
    {
        foreach ($this->currentDoc->СведНедоим as $item) {
            $taxName = (string)$item['НаимНалог'];
            $taxDebt = round((float)$item['СумНедНалог'], 2);
            $penalty = round((float)$item['СумПени'], 2);
            $fine = round((float)$item['СумШтраф'], 2);
            $totalDebt = round((float)$item['ОбщСумНедоим'], 2);

            // Сохранение данных в базу данных
            yield [$taxName, $taxDebt, $penalty, $fine, $totalDebt];
        }
    }
}
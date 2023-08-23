<?php

class SiteParser
{
    private $data;

    public function __construct(DOMDocument $data = null)
    {
        $this->data = $data;
    }

    /**
     * Parse site by url and set dom data
     *
     * @param string $url
     * @return bool
     */
    public function parseDom(string $url): bool
    {
        $response = file_get_contents($url);

        if ($response !== false) {
            libxml_use_internal_errors(true);
            $dom = new DOMDocument;
            $dom->loadHTML($response);
            libxml_clear_errors();
            $this->data = $dom;
            return true;
        }

        return false;
    }

    /**
     * Get dataset Url
     *
     * @param DOMDocument $dom
     * @return string
     */
    public function getDatasetUrl(DOMDocument $dom): string
    {
        $xPath = new DOMXPath($dom);
        $dataUrl = $xPath->query('//*[@id="divSecondPageColumns"]//*[@class="border_table"]//tr[9]//a');
        if (!empty($dataUrl[0])) {
            return $dataUrl[0]->textContent;
        } else {
            return '';
        }
    }

    /**
     * Save file
     *
     * @param string $url
     * @param string $path
     * @return bool
     */
    public function saveFileFromUrl(string $url, string $path): bool
    {
        $newDataset = file_get_contents($url);
        if ($this->checkIsNewDataset($newDataset, $path)
            && file_put_contents($path, $newDataset)) {
            return true;
        } else {
            throw new Exception('Не удалось сохранить файл');
        }
    }

    /**
     * Compare dataset new file with old
     *
     * @param string|false $newDataset
     * @param string $path
     * @return bool
     */
    private function checkIsNewDataset($newDataset, string $path): bool
    {
        if (file_get_contents($path) !== $newDataset) {
            return true;
        } else {
            throw new Exception('Набор идентичен имеющимуся, обновление не требуется');
        }
    }

    /**
     * File from zip generator
     *
     * @param string $zipPath
     * @return Generator
     */
    public function getFilesFromZipGenerator(string $zipPath): Generator
    {
        $zip = new ZipArchive;
        if ($zip->open($zipPath) === true) {
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entryName = $zip->getNameIndex($i);
                $isDirectory = substr($entryName, -1) === '/';
                
                if (!$isDirectory) {
                    $zip->extractTo('./', array($entryName));
                    yield $entryName;
                }
            }
            $zip->close();
        } else {
            throw new Exception('Не удалось открыть архив.');
        }
    }

    /**
     * Get Dom data
     *
     * @return DOMDocument
     */
    public function getData(): DOMDocument
    {
        return $this->data;
    }
}

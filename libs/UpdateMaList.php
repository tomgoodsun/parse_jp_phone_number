<?php
use PhpOffice\PhpSpreadsheet\Settings;
use PhpOffice\PhpSpreadsheet\Reader\Xls as Reader;

class UpdateMaList
{
    private $def = array(
        'A' => 'number_area_code',
        'B' => 'ma',
        'C' => 'number',
        'D' => 'area_code',
        'E' => 'city_code',
        'F' => 'owner',
        'G' => 'status',
        'H' => 'remarks',
    );

    private $targetSheetName = '公開データ';

    private $srcUrls = array();
    private $tmpPath;

    private $results = array();

    /**
     * Constructor
     *
     * @param array $srcUrls
     * @param string $tmpPath = null
     */
    public function __construct(array $srcUrls, $tmpPath = null)
    {
        $this->srcUrls = $srcUrls;

        if (null === $tmpPath) {
            $tmpPath = __DIR__ . '/../tmp';
        }
        if (!file_exists($tmpPath)) {
            mkdir($tmpPath, 0777, true);
        }
        $this->tmpPath = $tmpPath;
    }

    /**
     * Get results
     *
     * @return array
     */
    public function getResults()
    {
        return $this->results;
    }

    /**
     * Get path to source file
     *
     * @param string $url
     * @return string
     */
    private function getSourceFilePath($url)
    {
        // TODO: 動的URLの場合はこの方法では対応できない。が、今は考慮しない。
        $name = basename($url);
        return $this->tmpPath . '/' . $name;
    }

    /**
     * Download file
     *
     * @param string $url
     * @param string $path
     * @return bool
     */
    private function downloadFile($url, $path)
    {
        if (file_exists($path)) {
            unlink($path);
        }
        if (preg_match('/^https?:\/\//', $url)) {
            passthru(sprintf('wget -O %s %s > /dev/null 2>&1', $path, $url), $ret);
            return true;
        }
        return false;
    }

    /**
     * Read file and build results
     *
     * @param string $file
     */
    private function read($file)
    {
        $reader = new Reader();
        $spreadsheet = $reader->load($file);
        $sheet = $spreadsheet->getSheetByName($this->targetSheetName);

        // TODO: 開始行の自動特定が出来るといいかな。
        $i = 2;
        do {
            $i++;
            $tmp = array();
            foreach ($this->def as $key => $title) {
                $value = $sheet->getCell($key . $i)->getValue();
                if ('number_area_code' == $title && strlen($value) == 0) {
                    return;
                }
                if ('number_area_code' != $title && strlen($value) == 0) {
                    $value = null;
                } else {
                    $value = $value;
                }
                $tmp[$title] = $value;
            }
            $tmp['number_length'] = strlen($tmp['number']);
            $tmp['area_code_length'] = strlen($tmp['area_code']);
            $tmp['city_code_length'] = strlen($tmp['city_code']);
    
            $this->results[] = $tmp;
        } while(strlen($tmp['number_area_code']) > 0);
    }

    /**
     * Retrieve data and create results
     */
    public function create()
    {
        foreach ($this->srcUrls as $url) {
            $path = $this->getSourceFilePath($url);
            if (!file_exists($path)) {
                $this->downloadFile($url, $path);
            }
            $this->read($path);
        }
    }

    /**
     * Create SQLs
     *
     * @return array
     */
    public function createSqls()
    {
        $sqls = array();
        $sqls[] = 'DELETE FROM `landline_ma`;' . PHP_EOL . PHP_EOL;
        $chunkedResults = array_chunk($this->results, 100);
        foreach ($chunkedResults as $results) {
            $selectList = array();
            foreach ($results as $result) {
                $cols = array();
                foreach ($result as $col => $val) {
                    if (null === $val) {
                        $val = 'NULL';
                    } else {
                        $val = '"' . $val . '"';
                    }
                    $cols[] = sprintf('%s AS %s', $val, $col);
                }
                $selectList[] = 'SELECT ' . implode(", ", $cols) . PHP_EOL;
            }
            $sqls[] = sprintf(
                'INSERT INTO `landline_ma`%s%s;',
                PHP_EOL . '    ',
                implode('    UNION ALL ', $selectList)
            ) . PHP_EOL . PHP_EOL;
        }
        return $sqls;
    }

    /**
     * Generate rebuilding SQL
     */
    public function generateSql()
    {
        foreach ($this->createSqls() as $sql) {
            echo $sql;
        }
    }

    /**
     * Create CSV lines
     *
     * @param string $delimiter = ','
     * @param string $surround = ''
     * @return array
     */
    public function createCsvLines($delimiter = ',', $surround = '')
    {
        $lines = array();
        $delimiter = $surround . $delimiter . $surround;
        foreach ($this->results as $result) {
            $lines[] = $surround . implode($delimiter, $result) . $surround . PHP_EOL;
        }
        return $lines;
    }

    /**
     * Generate CSV
     *
     * @param string $delimiter = ','
     * @param string $surround = ''
     */
    public function generateCsv($delimiter = ',', $surround = '')
    {
        foreach ($this->createCsvLines($delimiter, $surround) as $line) {
            echo $line;
        }
    }
}

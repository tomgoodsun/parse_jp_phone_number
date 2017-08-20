<?php
class TelephoneNumberParser
{
    private $delimiter = '-';

    protected $setting = array();

    private $database;

    public function __construct($setting_path)
    {
        $this->setting = yaml_parse_file($setting_path);
    }

    /**
     * @param array $parts
     * @return string
     */
    public function join(array $parts)
    {
        return implode($this->delimiter, $parts);
    }

    /**
     * @return \PDO
     */
    public function getDatabase()
    {
        if (null === $this->database) {
            $this->database = new \PDO('sqlite:' . __DIR__ . '/../data/ja_com_spec_master.sqlite3');
            $this->database->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->database->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        }
        return $this->database;
    }

    /**
     * @param string $number
     * @param array $format
     * @return array
     * @throws \Exception
     */
    public function getLandlineFormat($number, array $format)
    {
        $sql = 'SELECT `area_code_length`, `city_code_length` FROM `landline_ma` WHERE `number` = ?';
        $stmt = $this->getDatabase()->prepare($sql);
        $stmt->execute([$number]);
        $result = $stmt->fetchAll(); 
        if (count($result) == 0) {
            throw new \Exception(sprintf('Landline MA not found. {expected: %s]', $number));
        }
        $format['digits'][0] = intval($result[0]['area_code_length']);
        $format['digits'][1] = intval($result[0]['city_code_length']);
        $format['details'] = $result;
        return $format;
    }

    /**
     * @param string $phoneNo
     * @return array
     * @throws \Exception
     */
    public function detectFormat($phoneNo)
    {
        foreach ($this->setting['formats'] as $format) {
            try {
                if ('固定電話' == $format['category']) {
                    $format = $this->getLandlineFormat(substr($phoneNo, 0, 6), $format);
                }
                if (strlen($phoneNo) == $format['length'] && preg_match('/' . $format['regexp'] . '/', $phoneNo)) {
                    return $format;
                }
            } catch (\Exception $e) {
                throw $e;
            }
        }
        return array();
    }

    protected function getErrorInfo(array $result)
    {
        $result['is_error'] = true;
        $result['expected'] = $this->setting;
        return $result;
    }

    /**
     * @param string $phoneNo
     * @return array
     */
    public function parse($phoneNo)
    {
        $result = array(
            'is_error' => false,
            'original' => $phoneNo,
        );

        try {
            $format = $this->detectFormat($phoneNo);
            $result['format'] = $format;
            if (count($format) == 0) {
                return $this->getErrorInfo($result);
            }
        } catch (\Exception $e) {
            $result['exception'] = $e;
            return $this->getErrorInfo($result);
        }


        $parts = array();
        $start = 0;
        foreach ($format['digits'] as $digit) {
            $parts[] = substr($phoneNo, $start, $digit);
            $start += $digit;
        }

        $result['splitted'] = $parts;
        $result['joined'] = $this->join($parts);
        return $result;
    }
}

<?php
/*
 * Copyright (c) 2013 Benjamin Michotte <benjamin@produweb.be>
 *     ProduWeb SA <http://www.produweb.be>
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is furnished
 * to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */
abstract class CMS_Db_Orm_Adapter_Abstract
{
    /** @var Zend_Db_Adapter_Abstract */
    protected $dbAdapter;

    function __construct($dbAdapter)
    {
        $this->dbAdapter = $dbAdapter;
    }

    /**
     * @param array $array
     * @param string $column
     * @return string|null
     */
    protected function getData($array, $column)
    {
        return isset($array[$column]) && ! empty($array[$column]) ? $array[$column] : null;
    }

    /**
     * @param string $type
     * @throws Zend_Db_Exception
     * @return string
     */
    abstract protected function cleanType($type);

    /**
     * @param array $data
     * @throws Zend_Db_Exception
     * @return string
     */
    abstract public function createTable($data);

    /**
     * @param array $tableData
     * @param array $data
     * @throws Zend_Db_Exception
     * @return string
     */
    abstract public function alterTable($tableData, $data);
}
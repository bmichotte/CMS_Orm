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
class CMS_Db_Orm_Reflection_Docblock_Tag_Column extends CMS_Db_Orm_Reflection_Docblock_Tag_Abstract
{
    /** @var string */
    protected $columnName;

    /** @var string */
    protected $type;

    /** @var string */
    protected $length;

    /** @var string */
    protected $default;

    /** @var string */
    protected $unique;

    /** @var string */
    protected $nullable;

    /** @var string */
    protected $unsigned;

    /**
     * Constructor
     *
     * @param  string $tagDocblockLine
     * @throws Zend_Reflection_Exception
     * @return \CMS_Db_Orm_Reflection_Docblock_Tag_Column
     */
    public function __construct($tagDocblockLine)
    {
        if (!preg_match('#^@Column#', $tagDocblockLine))
        {
            require_once 'Zend/Reflection/Exception.php';
            throw new Zend_Reflection_Exception('Provided docblock line is does not contain a valid tag');
        }

        $this->_name = "Column";
        $this->fields = array("columnName" => "name", "type", "length", "default", "unique", "nullable", "unsigned");
        $this->parseFields($tagDocblockLine);
    }

    /**
     * @return string
     */
    public function getColumnName()
    {
        return $this->columnName;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @return string
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @return string
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @return string
     */
    public function getNullable()
    {
        return $this->nullable;
    }

    /**
     * @return string
     */
    public function getUnique()
    {
        return $this->unique;
    }

    /**
     * @return string
     */
    public function getUnsigned()
    {
        return $this->unsigned;
    }


}
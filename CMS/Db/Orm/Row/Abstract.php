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

/**
 * Class CMS_Db_Orm_Row_Abstract
 *
 * @method CMS_Db_Orm_Row_Abstract findBy*()
 */
class CMS_Db_Orm_Row_Abstract extends Zend_Db_Table_Row_Abstract
{
    public function __construct(array $config = array())
    {
        $name = explode("_", get_class($this));
        $table = strtolower(end($name));

        $reflectionClass = new Zend_Reflection_Class($this);
        $docs = $reflectionClass->getDocblock('CMS_Db_Orm_Reflection_Docblock');
        if ($docs->hasTag("Entity"))
        {
            /** @var $orm CMS_Db_Orm_Reflection_Docblock_Tag_Entity */
            $orm = $docs->getTag("Entity");
            if (!is_null($orm->getTable()))
            {
                $table = $orm->getTable();
            }
        }
        $primary = null;
        foreach ($reflectionClass->getProperties() as $property)
        {
            /** @var $property Zend_Reflection_Property */
            if ($property->getDeclaringClass()->getName() !== $reflectionClass->getName())
            {
                continue;
            }

            $docs = $property->getDocComment('CMS_Db_Orm_Reflection_Docblock');

            if (!$docs->hasTag("Column"))
            {
                continue;
            }

            if ($docs->hasTag("Id"))
            {
                /** @var $column CMS_Db_Orm_Reflection_Docblock_Tag_Column */
                $column = $docs->getTag("Column");
                if (!is_null($column->getColumnName()))
                {
                    $primary = $column->getColumnName();
                }
                else
                {
                    $primary = strtolower($property->getName());
                }
                break;
            }
        }

        $_table = new Zend_Db_Table(array(
            Zend_Db_Table::NAME      => $table,
            Zend_Db_Table::ROW_CLASS => get_class($this),
            Zend_Db_Table::PRIMARY   => array($primary)
        ));

        if (is_null($config))
        {
            $config = array();
        }
        $config["table"] = $_table;

        parent::__construct($config);
    }

    public function __call($method, array $args)
    {
        if (preg_match("/^set(?<property>\w+)/", $method, $matches))
        {
            $reflectionClass = new Zend_Reflection_Class($this);
            $property = $matches['property'];
            $property = strtolower(substr($property, 0, 1)) . substr($property, 1);

            if ($reflectionClass->hasProperty($property))
            {
                $this->{$property} = end($args);

                $reflectionProperty = $reflectionClass->getProperty($property);
                $docs = $reflectionProperty->getDocComment('CMS_Db_Orm_Reflection_Docblock');
                if ($docs->hasTag("Column"))
                {
                    $column = $docs->getTag("Column");
                    if (!is_null($column->getColumnName()))
                    {
                        $property = $column->getColumnName();
                    }
                }

                $this->_data[$property] = $this->{$property};
                $this->_modifiedFields[$property] = true;
                return $this;
            }
        }

        if (preg_match("/^get(?<property>\w+)/", $method, $matches))
        {
            $reflectionClass = new Zend_Reflection_Class($this);
            $property = $matches['property'];
            $property = strtolower(substr($property, 0, 1)) . substr($property, 1);

            if ($reflectionClass->hasProperty($property))
            {
                if (!is_null($this->{$property}))
                {
                    return $this->{$property};
                }
                $sqlProperty = $property;
                $reflectionProperty = $reflectionClass->getProperty($property);
                $docs = $reflectionProperty->getDocComment('CMS_Db_Orm_Reflection_Docblock');
                if ($docs->hasTag("Column"))
                {
                    $column = $docs->getTag("Column");
                    if (!is_null($column->getColumnName()))
                    {
                        $sqlProperty = $column->getColumnName();
                    }
                }

                $this->{$property} = $this->_data[$sqlProperty];
                return $this->{$property};
            }
        }

        if (preg_match('/^findBy(?<by>\w+?)$/', $method, $matches))
        {
            $property = $matches['by'];
            $property = strtolower(substr($property, 0, 1)) . substr($property, 1);

            $reflectionClass = new Zend_Reflection_Class($this);
            if ($reflectionClass->hasProperty($property))
            {
                $reflectionProperty = $reflectionClass->getProperty($property);
                $docs = $reflectionProperty->getDocComment('CMS_Db_Orm_Reflection_Docblock');
                if ($docs->hasTag("Column"))
                {
                    $column = $docs->getTag("Column");
                    if (!is_null($column->getColumnName()))
                    {
                        $property = $column->getColumnName();
                    }
                }

                $value = end($args);
                $select = $this->_getTable()->select()->where($this->_getTable()->getAdapter()->quoteIdentifier($property, true) . " = ?", $value);
                $row = $this->_getTable()->fetchRow($select);
                if (is_null($row))
                {
                    throw new Zend_Db_Exception("Can not find " . get_class($this) . " object by $property with value $value");
                }
                $this->_cleanData = $row->_cleanData;
                $this->_data = $row->_data;

                return $this;
            }
        }

        return parent::__call($method, $args);
    }

    public function save()
    {
        return parent::save();
    }
}
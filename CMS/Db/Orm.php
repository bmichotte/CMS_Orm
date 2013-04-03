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
class CMS_Db_Orm
{
    /** @var array */
    protected $paths;

    /** @var string */
    protected $cacheDir;

    /** @var array */
    protected $classes;

    /** @var Zend_Db_Adapter_Abstract */
    protected $dbAdapter;

    function __construct()
    {
        $this->paths = array();
    }

    /**
     * @param \Zend_Db_Adapter_Abstract $dbAdapter
     * @return CMS_Db_Orm
     */
    public function setDbAdapter($dbAdapter)
    {
        $this->dbAdapter = $dbAdapter;
        return $this;
    }

    /**
     * Set the cache dir
     * @param string $cacheDir
     * @return CMS_Db_Orm
     */
    public function setCacheDir($cacheDir)
    {
        $this->cacheDir = $cacheDir;
        return $this;
    }

    /**
     * Get the cache dir
     * @return string
     */
    public function getCacheDir()
    {
        return $this->cacheDir;
    }

    /**
     * @param string $path
     * @return CMS_Db_Orm
     */
    public function addPath($path)
    {
        $this->paths[] = $path;

        return $this;
    }

    public function run()
    {
        if (empty($this->paths))
        {
            return;
        }

        if (!file_exists($this->cacheDir))
        {
            if (!mkdir($this->cacheDir, 0755, true))
            {
                throw new Zend_Cache_Exception("Can not create cache dir at " . $this->cacheDir);
            }
        }

        $this->parseFiles();
    }

    protected function parseFiles()
    {
        $this->classes = array();
        foreach ($this->paths as $dir)
        {
            foreach (glob($dir . "/*.php") as $file)
            {
                require_once $file;
                $reflectionFile = new Zend_Reflection_File($file);
                foreach ($reflectionFile->getClasses() as $class)
                {
                    /** @var $class Zend_Reflection_Class */
                    if (!$class->isSubclassOf('CMS_Db_Orm_Row_Abstract'))
                    {
                        continue;
                    }

                    /** @var $docs CMS_Db_Orm_Reflection_Docblock */
                    $docs = $class->getDocblock('CMS_Db_Orm_Reflection_Docblock');
                    if ($docs->hasTag("Entity"))
                    {
                        /** @var $orm CMS_Db_Orm_Reflection_Docblock_Tag_Entity */
                        $orm = $docs->getTag("Entity");
                        $table = $orm->getTable();
                        if (is_null($table))
                        {
                            $name = explode("_", $class->getName());
                            $table = strtolower(end($name));
                        }

                        $tableData = array(
                            "table"  => $table,
                            "fields" => array()
                        );

                        foreach ($class->getProperties(ReflectionProperty::IS_PROTECTED) as $property)
                        {
                            /** @var $property Zend_Reflection_Property */
                            if ($property->getDeclaringClass()->getName() !== $class->getName())
                            {
                                continue;
                            }

                            $docs = $property->getDocComment('CMS_Db_Orm_Reflection_Docblock');

                            if (! $docs->hasTag("Column"))
                            {
                                continue;
                            }
                            $field = array();
                            if ($docs->hasTag("Id"))
                            {
                                $field['id'] = true;
                                $field['unsigned'] = true;
                            }

                            /** @var $column CMS_Db_Orm_Reflection_Docblock_Tag_Column */
                            $column = $docs->getTag("Column");
                            if (! is_null($column->getColumnName()))
                            {
                                $field['column'] = $column->getColumnName();
                            }
                            else
                            {
                                $field['column'] = strtolower($property->getName());
                            }

                            if (! is_null($column->getType()))
                            {
                                $field['type'] = $column->getType();
                            }
                            else if (! is_null($docs->getTag("var")))
                            {
                                /** @var $type CMS_Db_Orm_Reflection_Docblock_Tag */
                                $type = $docs->getTag("var");
                                $field['type'] = $type->getDescription();
                            }

                            if (! is_null($column->getLength()))
                            {
                                $field['length'] = $column->getLength();
                            }

                            if (! is_null($column->getDefault()))
                            {
                                $field['default'] = $column->getDefault();
                            }

                            if (! is_null($column->getNullable()))
                            {
                                $field['nullable'] = true;
                            }

                            if (! is_null($column->getUnique()))
                            {
                                $field['unique'] = true;
                            }

                            if (! is_null($column->getUnsigned()))
                            {
                                $field['unsigned'] = true;
                            }

                            $tableData['fields'][] = $field;
                        }


                        $this->classes[] = $tableData;
                    }
                }
            }
        }

        $this->checkTables();
    }

    protected function checkTables()
    {
        $adapterName = $this->getAdapter();
        /** @var $adapter CMS_Db_Orm_Adapter_Abstract */
        $adapter = new $adapterName($this->dbAdapter);
        $tables = $this->dbAdapter->listTables();

        $creates = array();
        $alters = array();
        foreach ($this->classes as $class)
        {
            if (in_array($class['table'], $tables))
            {
                $tableData = $this->dbAdapter->describeTable($class['table']);
                $alter = $adapter->alterTable($tableData, $class);
                if (! is_null($alter))
                {
                    $alters[] = $alter;
                }
            }
            else
            {
                $syntax = $adapter->createTable($class);
                $creates[] = $syntax;
            }
        }

        $this->dbAdapter->beginTransaction();
        try
        {
            foreach ($creates as $query)
            {
                Zend_Debug::dump($query, "create");
                $this->dbAdapter->query($query);
            }
            foreach ($alters as $query)
            {
                Zend_Debug::dump($query, "alter");
                $this->dbAdapter->query($query);
            }
            $this->dbAdapter->commit();
        }
        catch (Zend_Db_Exception $e)
        {
            $this->dbAdapter->rollBack();
            throw $e;
        }
    }

    protected function getAdapter()
    {
        $class = get_class($this->dbAdapter);
        $class = str_replace("Zend_Db_Adapter", "", $class);
        $class = "CMS_Db_Orm_Adapter" . $class;

        if (! class_exists($class))
        {
            throw new Zend_Db_Exception("Adapter $class is not (yet) implemented");
        }
        return $class;
    }
}
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
class CMS_Db_Orm_Adapter_Pdo_Mysql extends CMS_Db_Orm_Adapter_Abstract
{
    /**
     * @param array $data
     * @return string
     * @throws Zend_Db_Exception
     */
    public function createTable($data)
    {
        $table = $data['table'];

        $uniques = array();
        $pks = array();
        $fields = array();
        foreach ($data['fields'] as $field)
        {
            $fields[] = $this->buildField($field, $uniques, $pks);
        }

        $createSyntax = "CREATE TABLE " . $this->dbAdapter->quoteIdentifier($table, true) . " (" . PHP_EOL;
        $createSyntax .= join(", " . PHP_EOL, $fields) . PHP_EOL;

        if (!empty($uniques))
        {
            $uniq = "uc_{$table}_" . join("_", $uniques);
            foreach ($uniques as $idx => $unique)
            {
                $uniques[$idx] = $this->dbAdapter->quoteIdentifier($unique, true);
            }
            $createSyntax .= ",  CONSTRAINT $uniq UNIQUE(" . join(", ", $uniques) . ")";
        }
        if (!empty($pks))
        {
            $createSyntax .= ", PRIMARY KEY (" . join(", ", $pks) . ")" . PHP_EOL;
        }

        $createSyntax .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8;";

        return $createSyntax;
    }

    protected function buildField($field, &$uniques, &$pks, $ignoreErrors = false)
    {
        $column = $this->getData($field, 'column');
        $type = $this->getData($field, 'type');
        $length = $this->getData($field, 'length');

        if (!$ignoreErrors && (is_null($column) || is_null($type)))
        {
            throw new Zend_Db_Exception("Invalid column name and/or type");
        }

        $syntax = $this->dbAdapter->quoteIdentifier($column, true);
        $type = $this->cleanType($type);
        if ($type == "varchar" && is_null($length))
        {
            $length = 255;
        }

        if ($type == "int" && is_null($length))
        {
            $length = 10;
        }

        if ($type == "decimal" && is_null($length))
        {
            $length = "5,2";
        }

        if ($type == "tinyint")
        {
            $length = 1;
        }

        $columnType = $type;

        if (in_array($type, array("varchar", "int", "decimal", "tinyint")))
        {
            $columnType .= "($length)";
        }

        $syntax .= " " . $columnType;

        $nullable = $this->getData($field, "nullable");
        $unique = $this->getData($field, "unique");
        $default = $this->getDefault($type, $field);
        $unsigned = $this->getData($field, "unsigned");
        $id = $this->getData($field, "id");

        if (!is_null($unsigned))
        {
            $syntax .= " unsigned";
        }

        if (!is_null($id))
        {
            $pks[] = $this->dbAdapter->quoteIdentifier($column, true);

            $syntax .= " AUTO_INCREMENT";
        }

        if (is_null($nullable))
        {
            $syntax .= " NOT NULL";
        }
        if (!is_null($unique))
        {
            $uniques[] = $column;
        }

        if (!is_null($default))
        {
            $syntax .= " DEFAULT $default";
        }
        return $syntax;
    }

    /**
     * @param string $type
     * @throws Zend_Db_Exception
     * @return string
     */
    protected function cleanType($type)
    {
        switch (strtolower($type))
        {
            case 'int':
            case 'integer':
                return 'int';

            case 'string':
            case 'varchar':
                return 'varchar';

            case 'smallint':
                return "smallint";

            case 'bigint':
                return "bigint";

            case 'boolean':
            case 'bool':
                return "tinyint";

            case "decimal":
                return "decimal";

            case "date":
            case "datetime":
                return "datetime";

            case "time":
            case "timestamp":
                return "timestamp";

            case "text":
                return "text";

            case "object":
            case "array":
            case "json":
                return "clob";

            case "float":
                return "float";

            case "double":
                return "double";

            case "blob":
                return "blob";

            default:
                throw new Zend_Db_Exception("$type is not a valid column type");
        }
    }

    /**
     * @param array $tableData
     * @param array $data
     * @throws Zend_Db_Exception
     * @return string
     */
    public function alterTable($tableData, $data)
    {
        $table = $data['table'];

        $missings = array();
        $changed = array();
        $removed = array();
        $uniques = array();
        $pks = array();

        $subquery = "SELECT " . $this->dbAdapter->quoteIdentifier("constraint_name", true) . " FROM "
            . $this->dbAdapter->quoteIdentifier("information_schema.table_constraints", true)
            . " WHERE " . $this->dbAdapter->quoteIdentifier("table_schema", true) . " = schema() AND "
            . $this->dbAdapter->quoteIdentifier("table_name", true) . " = '$table'";

        $query = "SELECT * FROM " . $this->dbAdapter->quoteIdentifier("information_schema.key_column_usage", true)
            . " WHERE " . $this->dbAdapter->quoteIdentifier("table_schema", true) . " = schema() AND "
            . $this->dbAdapter->quoteIdentifier("table_name", true) . " = '$table' AND "
            . $this->dbAdapter->quoteIdentifier("constraint_name", true) . " IN (" . $subquery . ")";

        $constraints = $this->dbAdapter->query($query)->fetchAll();
        //Zend_Debug::dump($constraints);

        foreach ($tableData as $column => $columnInfos)
        {
            $found = false;
            foreach ($data['fields'] as $field)
            {
                $columnName = $this->getData($field, 'column');
                if ($columnName == $column)
                {
                    $found = true;

                    $alters = array();

                    $type = $this->getData($field, 'type');
                    $type = $this->cleanType($type);

                    if ($columnInfos['DATA_TYPE'] != $type)
                    {
                        $alters['type'] = $type;
                    }

                    $length = $this->getData($field, 'length');
                    if ($columnInfos['LENGTH'] != $length || $type == 'decimal')
                    {
                        if (($type == "varchar" && $columnInfos['LENGTH'] != 255)
                            || ($type == "int" && $columnInfos['LENGTH'] != 10))
                        {
                            $alters['length'] = $length;
                            $alters['type'] = $type;
                        }

                        if ($type == 'decimal')
                        {
                            if (is_null($length))
                            {
                                $length = "5,2";
                            }
                            $l = explode(",", $length);
                            if ($columnInfos['PRECISION'] != $l[0] || $columnInfos['SCALE'] != $l[1])
                            {
                                $alters['length'] = $length;
                                $alters['type'] = $type;
                            }
                        }
                    }

                    $nullable = $this->getData($field, "nullable");
                    if ($columnInfos['NULLABLE'] != $nullable)
                    {
                        $alters['nullable'] = $nullable;
                    }

                    $default = $this->getDefault($type, $field);
                    if ($columnInfos['DEFAULT'] != $default)
                    {
                        $alters['default'] = $default;
                    }

                    $unsigned = $this->getData($field, "unsigned");
                    if ($columnInfos['UNSIGNED'] != $unsigned)
                    {
                        $alters['unsigned'] = $unsigned;
                    }

                    $id = $this->getData($field, "id");
                    if ($columnInfos['IDENTITY'] != $id)
                    {
                        $alters['id'] = $id;
                        if (isset($alters['unsigned']))
                        {
                            unset($alters['unsigned']);
                        }
                    }
                    if (!is_null($id))
                    {
                        foreach ($constraints as $constraint)
                        {
                            if ($constraint->COLUMN_NAME == $column && $constraint->CONSTRAINT_NAME != 'PRIMARY')
                            {
                                $pks = $column;
                            }
                        }
                    }

                    // TODO FIXXME
                    $unique = $this->getData($field, "unique");
                    foreach ($constraints as $constraint)
                    {
                        if ($constraint->COLUMN_NAME == $columnName)
                        {
                            break;
                        }
                    }

                    if (!empty($alters))
                    {
                        $alters['column'] = $column;
                        $changed[] = $this->buildField($alters, $uniques, $pks, true);
                    }
                }
            }

            if (!$found)
            {
                $removed[] = $column;
            }
        }

        foreach ($data['fields'] as $field)
        {
            $columnName = $this->getData($field, 'column');
            if (isset($tableData[$columnName]))
            {
                continue;
            }

            $syntax = $this->buildField($field, $uniques, $pks);
            $missings[] = $syntax;
        }

        /*Zend_Debug::dump(array(
            "missings" => $missings,
            "changed"  => $changed,
            "removed"  => $removed,
            "uniques"  => $uniques,
            "pks"      => $pks
        ));*/

        if (empty($missings) && empty($changed) && empty($removed) && empty($uniques) && empty($pks))
        {
            return null;
        }

        $query = "ALTER TABLE " . $this->dbAdapter->quoteIdentifier($table, true);
        if (!empty($missings))
        {
            foreach ($missings as $missing)
            {
                $query .= " ADD " . $missing . ",";
            }
            $query = substr($query, 0, strlen($query) - 1);
        }
        if (!empty($changed))
        {
            if (!empty($missings))
            {
                $query .= ", ";
            }
            foreach ($changed as $change)
            {
                $query .= " MODIFY " . $change . ",";
            }
            $query = substr($query, 0, strlen($query) - 1);
        }
        if (!empty($removed))
        {
            if (!empty($missings) || !empty($changed))
            {
                $query .= ", ";
            }
            foreach ($removed as $remove)
            {
                $query .= " DROP COLUMN " . $this->dbAdapter->quoteIdentifier($remove) . ",";
            }
            $query = substr($query, 0, strlen($query) - 1);
        }

        return $query;
    }

    private function getDefault($type, $field)
    {
        $default = $this->getData($field, "default");
        if (is_null($default))
        {
            return null;
        }
        if (in_array($type, array("varchar", "text")))
        {
            return $this->dbAdapter->quote($default);
        }
        else if ($type == "tinyint")
        {
            return $default === "true";
        }
        return $default;
    }
}

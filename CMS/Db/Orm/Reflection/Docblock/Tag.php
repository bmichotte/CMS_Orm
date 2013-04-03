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
class CMS_Db_Orm_Reflection_Docblock_Tag extends Zend_Reflection_Docblock_Tag
{
    protected static $_tagClasses = array(
        'param'  => 'Zend_Reflection_Docblock_Tag_Param',
        'return' => 'Zend_Reflection_Docblock_Tag_Return',
        'Entity' => 'Cms_Db_Orm_Reflection_Docblock_Tag_Entity',
        'Id'     => 'Cms_Db_Orm_Reflection_Docblock_Tag_Id',
        'Column' => 'Cms_Db_Orm_Reflection_Docblock_Tag_Column'
    );

    /**
     * Factory: Create the appropriate annotation tag object
     *
     * @param  string $tagDocblockLine
     * @throws Zend_Reflection_Exception
     * @return Zend_Reflection_Docblock_Tag
     */
    public static function factory($tagDocblockLine)
    {
        $matches = array();

        if (!preg_match('#^@(\w+)#', $tagDocblockLine, $matches))
        {
            require_once 'Zend/Reflection/Exception.php';
            throw new Zend_Reflection_Exception('No valid tag name found within provided docblock line.');
        }

        $tagName = $matches[1];
        if (array_key_exists($tagName, self::$_tagClasses))
        {
            $tagClass = self::$_tagClasses[$tagName];
            if (!class_exists($tagClass))
            {
                Zend_Loader::loadClass($tagClass);
            }
            return new $tagClass($tagDocblockLine);
        }
        return new self($tagDocblockLine);
    }
}
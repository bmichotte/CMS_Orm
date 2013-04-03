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
class CMS_Db_Orm_Reflection_Docblock_Tag_Entity extends Zend_Reflection_Docblock_Tag
{
    /** @var string */
    private $table;

    /**
     * Constructor
     *
     * @param  string $tagDocblockLine
     * @throws Zend_Reflection_Exception
     * @return \CMS_Db_Orm_Reflection_Docblock_Tag_Entity
     */
    public function __construct($tagDocblockLine)
    {
        if (!preg_match('#^@Entity#', $tagDocblockLine))
        {
            require_once 'Zend/Reflection/Exception.php';
            throw new Zend_Reflection_Exception('Provided docblock line is does not contain a valid tag');
        }

        $this->_name = "Entity";

        $tags = array('table');
        foreach ($tags as $tag)
        {
            if (preg_match("#$tag=(?<$tag>\w+)(\s|,|\))?#i", $tagDocblockLine, $matches))
            {
                if (isset($matches[$tag]))
                {
                    $this->{$tag} = $matches[$tag];
                }
            }
        }
    }

    /**
     * @return string
     */
    public function getTable()
    {
        return $this->table;
    }
}
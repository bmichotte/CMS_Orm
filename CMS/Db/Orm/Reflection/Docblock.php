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
class CMS_Db_Orm_Reflection_Docblock extends Zend_Reflection_Docblock
{
    /**
     * Parse the docblock
     *
     * @return void
     */
    protected function _parse()
    {
        $docComment = $this->_docComment;

        // First remove doc block line starters
        $docComment = preg_replace('#[ \t]*(?:\/\*\*|\*\/|\*)?[ ]{0,1}(.*)?#', '$1', $docComment);
        $docComment = ltrim($docComment, "\r\n"); // @todo should be changed to remove first and last empty line

        $this->_cleanDocComment = $docComment;

        // Next parse out the tags and descriptions
        $parsedDocComment = $docComment;
        $lineNumber = $firstBlandLineEncountered = 0;
        while (($newlinePos = strpos($parsedDocComment, "\n")) !== false)
        {
            $lineNumber++;
            $line = substr($parsedDocComment, 0, $newlinePos);

            $matches = array();

            if ((strpos($line, '@') === 0) && (preg_match('#^(@\w+.*?)(\n)(?:@|\r?\n|$)#s', $parsedDocComment, $matches)))
            {
                $this->_tags[] = CMS_Db_Orm_Reflection_Docblock_Tag::factory($matches[1]);
                $parsedDocComment = str_replace($matches[1] . $matches[2], '', $parsedDocComment);
            }
            else
            {
                if ($lineNumber < 3 && !$firstBlandLineEncountered)
                {
                    $this->_shortDescription .= $line . "\n";
                }
                else
                {
                    $this->_longDescription .= $line . "\n";
                }

                if ($line == '')
                {
                    $firstBlandLineEncountered = true;
                }

                $parsedDocComment = substr($parsedDocComment, $newlinePos + 1);
            }

        }

        $this->_shortDescription = rtrim($this->_shortDescription);
        $this->_longDescription = rtrim($this->_longDescription);
    }
}
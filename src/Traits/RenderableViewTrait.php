<?php
/* ===========================================================================
 * Copyright 2018 Zindex Software
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *    http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 * ============================================================================ */

namespace Opis\Colibri\Traits;

use function Opis\Colibri\Functions\render;

trait RenderableViewTrait
{
    protected $renderedContent;

    /**
     * The __toString method allows a class to decide how it will react when it is converted to a string.
     *
     * @return string
     * @link http://php.net/manual/en/language.oop5.magic.php#language.oop5.magic.tostring
     */
    public function __toString()
    {
        if ($this->renderedContent === null) {
            try {
                $this->renderedContent = render($this);
            } catch (\Exception $e) {
                $this->renderedContent = $e->getMessage();
            } catch (\Error $e) {
                $this->renderedContent = $e->getMessage();
            }
        }

        return $this->renderedContent;
    }
}
<?php

/*
 * This file is part of the Imagine package.
 *
 * (c) Bulat Shakirzyanov <mallluhuct@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Imagine\Filter;

use Imagine\ImageInterface;

interface FilterInterface
{
    /**
     * Applies scheduled transformation to ImageInterface instance
     * Returns processed ImageInterface instance
     *
     * @param Imagine\ImageInterface $image
     *
     * @return Imagine\ImageInterface
     */
    function apply(ImageInterface $image);
}

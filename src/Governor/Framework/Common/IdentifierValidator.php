<?php

/*
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR
 * A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT
 * OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL,
 * SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT
 * LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY
 * THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
 * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE
 * OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * The software is based on the Axon Framework project which is
 * licensed under the Apache 2.0 license. For more information on the Axon Framework
 * see <http://www.axonframework.org/>.
 * 
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the MIT license. For more information, see
 * <http://www.governor-framework.org/>.
 */

namespace Governor\Framework\Common;

/**
 * Utility class performing aggregate identifier validation tasks.
 * 
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
class IdentifierValidator
{

    /**
     * Validates the identifier. Currently only scalar PHP types are supported as aggregate identifiers.
     * 
     * @param mixed $identifier
     * @throws \InvalidArgumentException
     */
    public static function validateIdentifier($identifier)
    {
        if (!is_scalar($identifier)) {
            throw new \InvalidArgumentException(sprintf("One of the events contains an unsuitable aggregate identifier " .
                    "for this EventStore implementation. See reference guide " .
                    "for more information. Invalid type is: %s",
                    self::getType($identifier)));
        }
    }

    private static function getType($var)
    {
        if (is_object($var)) {
            return get_class($var);
        }

        return gettype($var);
    }

}

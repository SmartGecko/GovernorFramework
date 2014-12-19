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

namespace Governor\Framework\UnitOfWork;

/**
 * Description of CurrentUnitOfWork
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
abstract class CurrentUnitOfWork
{

    private static $current = array();

    public static function isStarted()
    {
        return !empty(self::$current);
    }

    /**
     * 
     * @return \Governor\Framework\UnitOfWork\UnitOfWorkInterface
     * @throws \RuntimeException
     */
    public static function get()
    {
        if (self::isEmpty()) {
            throw new \RuntimeException("No UnitOfWork is currently started");
        }
                
        return end(self::$current);
    }

    private static function isEmpty()
    {
        $unitsOfWork = self::$current;
        return null === $unitsOfWork || empty($unitsOfWork);
    }

    public static function commit()
    {
        self::get()->commit();
    }

    /**
     * Binds the given <code>unitOfWork</code> to the current thread. If other UnitOfWork instances were bound, they
     * will be marked as inactive until the given UnitOfWork is cleared.
     *
     * @param UnitOfWorkInterface $unitOfWork The UnitOfWork to bind to the current thread.
     */
    public static function set(UnitOfWorkInterface $unitOfWork)
    {                
        self::$current[] = $unitOfWork;
    }

    public static function clear(UnitOfWorkInterface $unitOfWork)
    {        
        if (end(self::$current) === $unitOfWork) {
            $current = array_pop(self::$current);
        } else {
            throw new \RuntimeException("Could not clear this UnitOfWork. It is not the active one.");
        }
    }

}

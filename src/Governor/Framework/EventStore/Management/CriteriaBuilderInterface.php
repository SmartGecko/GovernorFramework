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

namespace Governor\Framework\EventStore\Management;

/**
 * Interface providing access to the criteria API of an Event Store.
 * <p/>
 * <em>Example:</em><br/>
 * <pre>
 *     $criteriaBuilder = $eventStore->newCriteriaBuilder();
 *     // Timestamps are stored as ISO 8601 Strings.
 *     $criteria = $criteriaBuilder->property("timeStamp")->greaterThan("2011-11-12");
 *     $eventStore->visitEvents($criteria, $visitor);
 * </pre>
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
interface CriteriaBuilderInterface
{

    /**
     * Returns a property instance that can be used to build criteria. The given <code>propertyName</code> must hold a
     * valid value for the Event Store that returns that value. Typically, it requires the "indexed" values to be used,
     * such as event identifier, aggregate identifier, timestamp, etc.
     *
     * @param string $propertyName The name of the property to evaluate
     * @return PropertyInterface a property instance that can be used to build expressions
     */
    public function property($propertyName);
}

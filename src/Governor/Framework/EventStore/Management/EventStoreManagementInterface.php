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

use Governor\Framework\EventStore\EventVisitorInterface;

/**
 * Interface describing operations useful for management purposes. These operations are typically used in migration
 * scripts when deploying new versions of applications.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
interface EventStoreManagementInterface
{

    /**
     * Loads all events available in the event store and calls
     * {@link \Governor\Framework\EventStore\EventVisitorInterface::doWithEvent}
     * for each event found. Events of a single aggregate are guaranteed to be ordered by their sequence number.
     * <p/>
     * Implementations are encouraged, though not required, to supply events in the absolute chronological order.
     * <p/>
     * Processing stops when the visitor throws an exception.
     *
     * @param EventVisitorInterface $visitor The visitor the receives each loaded event
     * @param CriteriaInterface $criteria The criteria describing the events to select.     
     */
    public function visitEvents(EventVisitorInterface $visitor,
            CriteriaInterface $criteria = null);

    /**
     * Returns a CriteriaBuilderInterface that allows the construction of criteria for this EventStore implementation
     *
     * @return CriteriaBuilderInterface a builder to create Criteria for this Event Store.          
     */
    public function newCriteriaBuilder();
}

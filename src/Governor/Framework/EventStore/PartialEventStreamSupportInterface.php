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

namespace Governor\Framework\EventStore;

use Governor\Framework\Domain\DomainEventStreamInterface;
/**
 * Interface PartialEventStreamSupportInterface
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a>
 */
interface PartialEventStreamSupportInterface
{

    /**
     * Returns a Stream containing events for the aggregate identified by the given {@code type} and {@code
     * identifier}, starting at the event with the given {@code firstSequenceNumber} (included) up to and including the
     * event with given {@code lastSequenceNumber}.
     * If no event with given {@code lastSequenceNumber} exists, the returned stream will simply read until the end of
     * the aggregate's events.
     * <p/>
     * The returned stream will not contain any snapshot events.
     *
     * @param string $type                The type identifier of the aggregate
     * @param string $identifier          The identifier of the aggregate
     * @param int $firstSequenceNumber The sequence number of the first event to find
     * @param int|null $lastSequenceNumber  The sequence number of the last event in the stream
     * @return DomainEventStreamInterface a Stream containing events for the given aggregate, starting at the given first sequence number
     */
    public function readEventsWithinScn($type, $identifier, $firstSequenceNumber,
            $lastSequenceNumber = null);
}

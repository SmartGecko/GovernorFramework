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

namespace Governor\Framework\EventSourcing;

/**
 * Interface describing instances that are capable of creating snapshot events for aggregates. Although snapshotting
 * is typically an asynchronous process, implementations may to choose to create snapshots in the calling thread.
 *
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
interface SnapshotterInterface 
{
     /**
     * Schedules snapshot taking for an aggregate with given <code>typeIdentifier</code> and
     * <code>aggregateIdentifier</code>. 
     *
     * @param string $typeIdentifier      the type of the aggregate to take the snapshot for
     * @param mixed $aggregateIdentifier The identifier of the aggregate to take the snapshot for
     */
    public function scheduleSnapshot($typeIdentifier, $aggregateIdentifier);
}

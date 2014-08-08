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

namespace Governor\Framework\Domain;

/**
 * Representation for a stream of events sorted by occurance.
 * 
 * @author    "David Kalosi" <david.kalosi@gmail.com>  
 * @license   <a href="http://www.opensource.org/licenses/mit-license.php">MIT License</a> 
 */
interface DomainEventStreamInterface
{

    /**
     * Returns <code>true</code> if the stream has more events, meaning that a call to <code>next()</code> will not
     * result in an exception. If a call to this method returns <code>false</code>, there is no guarantee about the
     * result of a consecutive call to <code>next()</code>
     *
     * @return boolean <code>true</code> if the stream contains more events.
     */
    public function hasNext();

    /**
     * Returns the next events in the stream, if available. Use <code>hasNext()</code> to obtain a guarantee about the
     * availability of any next event. Each call to <code>next()</code> will forward the pointer to the next event in
     * the stream.
     * <p/>
     * If the pointer has reached the end of the stream, the behavior of this method is undefined. It could either
     * return <code>null</code>, or throw an exception, depending on the actual implementation. Use {@link #hasNext()}
     * to confirm the existence of elements after the current pointer.
     *
     * @return DomainEventMessageInterface the next event in the stream.
     */
    public function next();

    /**
     * Returns the next events in the stream, if available, without moving the pointer forward. Hence, a call to {@link
     * #next()} will return the same event as a call to <code>peek()</code>. Use <code>hasNext()</code> to obtain a
     * guarantee about the availability of any next event.
     * <p/>
     * If the pointer has reached the end of the stream, the behavior of this method is undefined. It could either
     * return <code>null</code>, or throw an exception, depending on the actual implementation. Use {@link #hasNext()}
     * to confirm the existence of elements after the current pointer.
     *
     * @return DomainEventMessageInterface the next event in the stream.
     */
    public function peek();
}

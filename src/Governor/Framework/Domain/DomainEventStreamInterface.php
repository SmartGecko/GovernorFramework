<?php

namespace Governor\Framework\Domain;

/**
 * Representation for a stream of events sorted by occurance.
 */
interface DomainEventStreamInterface
{

    /**
     * Returns <code>true</code> if the stream has more events, meaning that a call to <code>next()</code> will not
     * result in an exception. If a call to this method returns <code>false</code>, there is no guarantee about the
     * result of a consecutive call to <code>next()</code>
     *
     * @return <code>true</code> if the stream contains more events.
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

<?php
/*
 * Copyright 2019 MongoDB, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *   http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace MongoDB\Model;

use IteratorIterator;
use MongoDB\BSON\Serializable;
use MongoDB\Driver\Cursor;
use MongoDB\Driver\Monitoring\CommandFailedEvent;
use MongoDB\Driver\Monitoring\CommandStartedEvent;
use MongoDB\Driver\Monitoring\CommandSubscriber;
use MongoDB\Driver\Monitoring\CommandSucceededEvent;
use MongoDB\Exception\InvalidArgumentException;
use MongoDB\Exception\ResumeTokenException;
use MongoDB\Exception\UnexpectedValueException;
use function count;
use function is_array;
use function is_integer;
use function is_object;
use function MongoDB\Driver\Monitoring\addSubscriber;
use function MongoDB\Driver\Monitoring\removeSubscriber;

/**
 * ChangeStreamIterator wraps a change stream's tailable cursor.
 *
 * This iterator tracks the size of each batch in order to determine when the
 * postBatchResumeToken is applicable. It also ensures that initial calls to
 * rewind() do not execute getMore commands.
 *
 * @internal
 */
class ChangeStreamIterator extends IteratorIterator implements CommandSubscriber
{
    /** @var integer */
    private $batchPosition = 0;

    /** @var integer */
    private $batchSize;

    /** @var boolean */
    private $isRewindNop;

    /** @var boolean */
    private $isValid = false;

    /** @var object|null */
    private $postBatchResumeToken;

    /** @var array|object|null */
    private $resumeToken;

    /**
     * @internal
     * @param Cursor            $cursor
     * @param integer           $firstBatchSize
     * @param array|object|null $initialResumeToken
     * @param object|null       $postBatchResumeToken
     */
    public function __construct(Cursor $cursor, $firstBatchSize, $initialResumeToken, $postBatchResumeToken)
    {
        if (! is_integer($firstBatchSize)) {
            throw InvalidArgumentException::invalidType('$firstBatchSize', $firstBatchSize, 'integer');
        }

        if (isset($initialResumeToken) && ! is_array($initialResumeToken) && ! is_object($initialResumeToken)) {
            throw InvalidArgumentException::invalidType('$initialResumeToken', $initialResumeToken, 'array or object');
        }

        if (isset($postBatchResumeToken) && ! is_object($postBatchResumeToken)) {
            throw InvalidArgumentException::invalidType('$postBatchResumeToken', $postBatchResumeToken, 'object');
        }

        parent::__construct($cursor);

        $this->batchSize = $firstBatchSize;
        $this->isRewindNop = ($firstBatchSize === 0);
        $this->postBatchResumeToken = $postBatchResumeToken;
        $this->resumeToken = $initialResumeToken;
    }

    /** @internal */
    final public function commandFailed(CommandFailedEvent $event)
    {
    }

    /** @internal */
    final public function commandStarted(CommandStartedEvent $event)
    {
        if ($event->getCommandName() !== 'getMore') {
            return;
        }

        $this->batchPosition = 0;
        $this->batchSize = null;
        $this->postBatchResumeToken = null;
    }

    /** @internal */
    final public function commandSucceeded(CommandSucceededEvent $event)
    {
        if ($event->getCommandName() !== 'getMore') {
            return;
        }

        $reply = $event->getReply();

        if (! isset($reply->cursor->nextBatch) || ! is_array($reply->cursor->nextBatch)) {
            throw new UnexpectedValueException('getMore command did not return a "cursor.nextBatch" array');
        }

        $this->batchSize = count($reply->cursor->nextBatch);

        if (isset($reply->cursor->postBatchResumeToken) && is_object($reply->cursor->postBatchResumeToken)) {
            $this->postBatchResumeToken = $reply->cursor->postBatchResumeToken;
        }
    }

    /**
     * @see https://php.net/iteratoriterator.current
     * @return mixed
     */
    public function current()
    {
        return $this->isValid ? parent::current() : null;
    }

    /**
     * Returns the resume token for the iterator's current position.
     *
     * Null may be returned if no change documents have been iterated and the
     * server did not include a postBatchResumeToken in its aggregate or getMore
     * command response.
     *
     * @return array|object|null
     */
    public function getResumeToken()
    {
        return $this->resumeToken;
    }

    /**
     * @see https://php.net/iteratoriterator.key
     * @return mixed
     */
    public function key()
    {
        return $this->isValid ? parent::key() : null;
    }

    /**
     * @see https://php.net/iteratoriterator.rewind
     * @return void
     */
    public function next()
    {
        /* Determine if advancing the iterator will execute a getMore command
         * (i.e. we are already positioned at the end of the current batch). If
         * so, rely on the APM callbacks to reset $batchPosition and update
         * $batchSize. Otherwise, we can forgo APM and manually increment
         * $batchPosition after calling next(). */
        $getMore = $this->isAtEndOfBatch();

        if ($getMore) {
            addSubscriber($this);
        }

        try {
            parent::next();
            $this->onIteration(! $getMore);
        } finally {
            if ($getMore) {
                removeSubscriber($this);
            }
        }
    }

    /**
     * @see https://php.net/iteratoriterator.rewind
     * @return void
     */
    public function rewind()
    {
        if ($this->isRewindNop) {
            return;
        }

        parent::rewind();
        $this->onIteration(false);
    }

    /**
     * @see https://php.net/iteratoriterator.valid
     * @return boolean
     */
    public function valid()
    {
        return $this->isValid;
    }

    /**
     * Extracts the resume token (i.e. "_id" field) from a change document.
     *
     * @param array|object $document Change document
     * @return array|object
     * @throws InvalidArgumentException
     * @throws ResumeTokenException if the resume token is not found or invalid
     */
    private function extractResumeToken($document)
    {
        if (! is_array($document) && ! is_object($document)) {
            throw InvalidArgumentException::invalidType('$document', $document, 'array or object');
        }

        if ($document instanceof Serializable) {
            return $this->extractResumeToken($document->bsonSerialize());
        }

        $resumeToken = is_array($document)
            ? (isset($document['_id']) ? $document['_id'] : null)
            : (isset($document->_id) ? $document->_id : null);

        if (! isset($resumeToken)) {
            $this->isValid = false;
            throw ResumeTokenException::notFound();
        }

        if (! is_array($resumeToken) && ! is_object($resumeToken)) {
            $this->isValid = false;
            throw ResumeTokenException::invalidType($resumeToken);
        }

        return $resumeToken;
    }

    /**
     * Return whether the iterator is positioned at the end of the batch.
     *
     * @return boolean
     */
    private function isAtEndOfBatch()
    {
        return $this->batchPosition + 1 >= $this->batchSize;
    }

    /**
     * Perform housekeeping after an iteration event.
     *
     * @see https://github.com/mongodb/specifications/blob/master/source/change-streams/change-streams.rst#updating-the-cached-resume-token
     * @param boolean $incrementBatchPosition
     */
    private function onIteration($incrementBatchPosition)
    {
        $this->isValid = parent::valid();

        /* Disable rewind()'s NOP behavior once we advance to a valid position.
         * This will allow the driver to throw a LogicException if rewind() is
         * called after the cursor has advanced past its first element. */
        if ($this->isRewindNop && $this->isValid) {
            $this->isRewindNop = false;
        }

        if ($incrementBatchPosition && $this->isValid) {
            $this->batchPosition++;
        }

        /* If the iterator is positioned at the end of the batch, apply the
         * postBatchResumeToken if it's available. This handles both the case
         * where the current batch is empty (since onIteration() will be called
         * after a successful getMore) and when the iterator has advanced to the
         * last document in its current batch. Otherwise, extract a resume token
         * from the current document if possible. */
        if ($this->isAtEndOfBatch() && $this->postBatchResumeToken !== null) {
            $this->resumeToken = $this->postBatchResumeToken;
        } elseif ($this->isValid) {
            $this->resumeToken = $this->extractResumeToken($this->current());
        }
    }
}

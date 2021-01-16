<?php
/*
 * Copyright 2017 MongoDB, Inc.
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

namespace MongoDB\Operation;

use MongoDB\BSON\TimestampInterface;
use MongoDB\ChangeStream;
use MongoDB\Driver\Cursor;
use MongoDB\Driver\Exception\RuntimeException;
use MongoDB\Driver\Manager;
use MongoDB\Driver\Monitoring\CommandFailedEvent;
use MongoDB\Driver\Monitoring\CommandStartedEvent;
use MongoDB\Driver\Monitoring\CommandSubscriber;
use MongoDB\Driver\Monitoring\CommandSucceededEvent;
use MongoDB\Driver\ReadPreference;
use MongoDB\Driver\Server;
use MongoDB\Exception\InvalidArgumentException;
use MongoDB\Exception\UnexpectedValueException;
use MongoDB\Exception\UnsupportedException;
use MongoDB\Model\ChangeStreamIterator;
use function array_intersect_key;
use function array_unshift;
use function count;
use function is_array;
use function is_object;
use function is_string;
use function MongoDB\Driver\Monitoring\addSubscriber;
use function MongoDB\Driver\Monitoring\removeSubscriber;
use function MongoDB\select_server;
use function MongoDB\server_supports_feature;

/**
 * Operation for creating a change stream with the aggregate command.
 *
 * Note: the implementation of CommandSubscriber is an internal implementation
 * detail and should not be considered part of the public API.
 *
 * @api
 * @see \MongoDB\Collection::watch()
 * @see https://docs.mongodb.com/manual/changeStreams/
 */
class Watch implements Executable, /* @internal */ CommandSubscriber
{
    const FULL_DOCUMENT_DEFAULT = 'default';
    const FULL_DOCUMENT_UPDATE_LOOKUP = 'updateLookup';

    /** @var integer */
    private static $wireVersionForStartAtOperationTime = 7;

    /** @var Aggregate */
    private $aggregate;

    /** @var array */
    private $aggregateOptions;

    /** @var array */
    private $changeStreamOptions;

    /** @var string|null */
    private $collectionName;

    /** @var string */
    private $databaseName;

    /** @var integer|null */
    private $firstBatchSize;

    /** @var boolean */
    private $hasResumed = false;

    /** @var Manager */
    private $manager;

    /** @var TimestampInterface */
    private $operationTime;

    /** @var array */
    private $pipeline;

    /** @var object|null */
    private $postBatchResumeToken;

    /**
     * Constructs an aggregate command for creating a change stream.
     *
     * Supported options:
     *
     *  * batchSize (integer): The number of documents to return per batch.
     *
     *  * collation (document): Specifies a collation.
     *
     *  * fullDocument (string): Determines whether the "fullDocument" field
     *    will be populated for update operations. By default, change streams
     *    only return the delta of fields during the update operation (via the
     *    "updateDescription" field). To additionally return the most current
     *    majority-committed version of the updated document, specify
     *    "updateLookup" for this option. Defaults to "default".
     *
     *    Insert and replace operations always include the "fullDocument" field
     *    and delete operations omit the field as the document no longer exists.
     *
     *  * maxAwaitTimeMS (integer): The maximum amount of time for the server to
     *    wait on new documents to satisfy a change stream query.
     *
     *  * readConcern (MongoDB\Driver\ReadConcern): Read concern.
     *
     *  * readPreference (MongoDB\Driver\ReadPreference): Read preference. This
     *    will be used to select a new server when resuming. Defaults to a
     *    "primary" read preference.
     *
     *  * resumeAfter (document): Specifies the logical starting point for the
     *    new change stream.
     *
     *    Using this option in conjunction with "startAfter" and/or
     *    "startAtOperationTime" will result in a server error. The options are
     *    mutually exclusive.
     *
     *  * session (MongoDB\Driver\Session): Client session.
     *
     *    Sessions are not supported for server versions < 3.6.
     *
     *  * startAfter (document): Specifies the logical starting point for the
     *    new change stream. Unlike "resumeAfter", this option can be used with
     *    a resume token from an "invalidate" event.
     *
     *    Using this option in conjunction with "resumeAfter" and/or
     *    "startAtOperationTime" will result in a server error. The options are
     *    mutually exclusive.
     *
     *  * startAtOperationTime (MongoDB\BSON\TimestampInterface): If specified,
     *    the change stream will only provide changes that occurred at or after
     *    the specified timestamp. Any command run against the server will
     *    return an operation time that can be used here. Alternatively, an
     *    operation time may be obtained from MongoDB\Driver\Server::getInfo().
     *
     *    Using this option in conjunction with "resumeAfter" and/or
     *    "startAfter" will result in a server error. The options are mutually
     *    exclusive.
     *
     *    This option is not supported for server versions < 4.0.
     *
     *  * typeMap (array): Type map for BSON deserialization. This will be
     *    applied to the returned Cursor (it is not sent to the server).
     *
     * Note: A database-level change stream may be created by specifying null
     * for the collection name. A cluster-level change stream may be created by
     * specifying null for both the database and collection name.
     *
     * @param Manager     $manager        Manager instance from the driver
     * @param string|null $databaseName   Database name
     * @param string|null $collectionName Collection name
     * @param array       $pipeline       List of pipeline operations
     * @param array       $options        Command options
     * @throws InvalidArgumentException for parameter/option parsing errors
     */
    public function __construct(Manager $manager, $databaseName, $collectionName, array $pipeline, array $options = [])
    {
        if (isset($collectionName) && ! isset($databaseName)) {
            throw new InvalidArgumentException('$collectionName should also be null if $databaseName is null');
        }

        $options += [
            'fullDocument' => self::FULL_DOCUMENT_DEFAULT,
            'readPreference' => new ReadPreference(ReadPreference::RP_PRIMARY),
        ];

        if (isset($options['fullDocument']) && ! is_string($options['fullDocument'])) {
            throw InvalidArgumentException::invalidType('"fullDocument" option', $options['fullDocument'], 'string');
        }

        if (isset($options['resumeAfter']) && ! is_array($options['resumeAfter']) && ! is_object($options['resumeAfter'])) {
            throw InvalidArgumentException::invalidType('"resumeAfter" option', $options['resumeAfter'], 'array or object');
        }

        if (isset($options['startAfter']) && ! is_array($options['startAfter']) && ! is_object($options['startAfter'])) {
            throw InvalidArgumentException::invalidType('"startAfter" option', $options['startAfter'], 'array or object');
        }

        if (isset($options['startAtOperationTime']) && ! $options['startAtOperationTime'] instanceof TimestampInterface) {
            throw InvalidArgumentException::invalidType('"startAtOperationTime" option', $options['startAtOperationTime'], TimestampInterface::class);
        }

        /* In the absence of an explicit session, create one to ensure that the
         * initial aggregation and any resume attempts can use the same session
         * ("implicit from the user's perspective" per PHPLIB-342). Since this
         * is filling in for an implicit session, we default "causalConsistency"
         * to false. */
        if (! isset($options['session'])) {
            try {
                $options['session'] = $manager->startSession(['causalConsistency' => false]);
            } catch (RuntimeException $e) {
                /* We can ignore the exception, as libmongoc likely cannot
                 * create its own session and there is no risk of a mismatch. */
            }
        }

        $this->aggregateOptions = array_intersect_key($options, ['batchSize' => 1, 'collation' => 1, 'maxAwaitTimeMS' => 1, 'readConcern' => 1, 'readPreference' => 1, 'session' => 1, 'typeMap' => 1]);
        $this->changeStreamOptions = array_intersect_key($options, ['fullDocument' => 1, 'resumeAfter' => 1, 'startAfter' => 1, 'startAtOperationTime' => 1]);

        // Null database name implies a cluster-wide change stream
        if ($databaseName === null) {
            $databaseName = 'admin';
            $this->changeStreamOptions['allChangesForCluster'] = true;
        }

        $this->manager = $manager;
        $this->databaseName = (string) $databaseName;
        $this->collectionName = isset($collectionName) ? (string) $collectionName : null;
        $this->pipeline = $pipeline;

        $this->aggregate = $this->createAggregate();
    }

    /** @internal */
    final public function commandFailed(CommandFailedEvent $event)
    {
    }

    /** @internal */
    final public function commandStarted(CommandStartedEvent $event)
    {
        if ($event->getCommandName() !== 'aggregate') {
            return;
        }

        $this->firstBatchSize = null;
        $this->postBatchResumeToken = null;
    }

    /** @internal */
    final public function commandSucceeded(CommandSucceededEvent $event)
    {
        if ($event->getCommandName() !== 'aggregate') {
            return;
        }

        $reply = $event->getReply();

        if (! isset($reply->cursor->firstBatch) || ! is_array($reply->cursor->firstBatch)) {
            throw new UnexpectedValueException('aggregate command did not return a "cursor.firstBatch" array');
        }

        $this->firstBatchSize = count($reply->cursor->firstBatch);

        if (isset($reply->cursor->postBatchResumeToken) && is_object($reply->cursor->postBatchResumeToken)) {
            $this->postBatchResumeToken = $reply->cursor->postBatchResumeToken;
        }

        if ($this->shouldCaptureOperationTime($event->getServer()) &&
            isset($reply->operationTime) && $reply->operationTime instanceof TimestampInterface) {
            $this->operationTime = $reply->operationTime;
        }
    }

    /**
     * Execute the operation.
     *
     * @see Executable::execute()
     * @param Server $server
     * @return ChangeStream
     * @throws UnsupportedException if collation or read concern is used and unsupported
     * @throws RuntimeException for other driver errors (e.g. connection errors)
     */
    public function execute(Server $server)
    {
        return new ChangeStream(
            $this->createChangeStreamIterator($server),
            function ($resumeToken, $hasAdvanced) {
                return $this->resume($resumeToken, $hasAdvanced);
            }
        );
    }

    /**
     * Create the aggregate command for a change stream.
     *
     * This method is also used to recreate the aggregate command when resuming.
     *
     * @return Aggregate
     */
    private function createAggregate()
    {
        $pipeline = $this->pipeline;
        array_unshift($pipeline, ['$changeStream' => (object) $this->changeStreamOptions]);

        return new Aggregate($this->databaseName, $this->collectionName, $pipeline, $this->aggregateOptions);
    }

    /**
     * Create a ChangeStreamIterator by executing the aggregate command.
     *
     * @param Server $server
     * @return ChangeStreamIterator
     */
    private function createChangeStreamIterator(Server $server)
    {
        return new ChangeStreamIterator(
            $this->executeAggregate($server),
            $this->firstBatchSize,
            $this->getInitialResumeToken(),
            $this->postBatchResumeToken
        );
    }

    /**
     * Execute the aggregate command.
     *
     * The command will be executed using APM so that we can capture data from
     * its response (e.g. firstBatch size, postBatchResumeToken).
     *
     * @param Server $server
     * @return Cursor
     */
    private function executeAggregate(Server $server)
    {
        addSubscriber($this);

        try {
            return $this->aggregate->execute($server);
        } finally {
            removeSubscriber($this);
        }
    }

    /**
     * Return the initial resume token for creating the ChangeStreamIterator.
     *
     * @see https://github.com/mongodb/specifications/blob/master/source/change-streams/change-streams.rst#updating-the-cached-resume-token
     * @return array|object|null
     */
    private function getInitialResumeToken()
    {
        if ($this->firstBatchSize === 0 && isset($this->postBatchResumeToken)) {
            return $this->postBatchResumeToken;
        }

        if (isset($this->changeStreamOptions['startAfter'])) {
            return $this->changeStreamOptions['startAfter'];
        }

        if (isset($this->changeStreamOptions['resumeAfter'])) {
            return $this->changeStreamOptions['resumeAfter'];
        }

        return null;
    }

    /**
     * Resumes a change stream.
     *
     * @see https://github.com/mongodb/specifications/blob/master/source/change-streams/change-streams.rst#resume-process
     * @param array|object|null $resumeToken
     * @param bool              $hasAdvanced
     * @return ChangeStreamIterator
     * @throws InvalidArgumentException
     */
    private function resume($resumeToken = null, $hasAdvanced = false)
    {
        if (isset($resumeToken) && ! is_array($resumeToken) && ! is_object($resumeToken)) {
            throw InvalidArgumentException::invalidType('$resumeToken', $resumeToken, 'array or object');
        }

        $this->hasResumed = true;

        /* Select a new server using the original read preference. While watch
         * is not usable within transactions, we still check if there is a
         * pinned session. This is to avoid an ambiguous error message about
         * running a command on the wrong server. */
        $server = select_server($this->manager, $this->aggregateOptions);

        $resumeOption = isset($this->changeStreamOptions['startAfter']) && ! $hasAdvanced ? 'startAfter' : 'resumeAfter';

        unset($this->changeStreamOptions['resumeAfter']);
        unset($this->changeStreamOptions['startAfter']);
        unset($this->changeStreamOptions['startAtOperationTime']);

        if ($resumeToken !== null) {
            $this->changeStreamOptions[$resumeOption] = $resumeToken;
        }

        if ($resumeToken === null && $this->operationTime !== null) {
            $this->changeStreamOptions['startAtOperationTime'] = $this->operationTime;
        }

        // Recreate the aggregate command and return a new ChangeStreamIterator
        $this->aggregate = $this->createAggregate();

        return $this->createChangeStreamIterator($server);
    }

    /**
     * Determine whether to capture operation time from an aggregate response.
     *
     * @see https://github.com/mongodb/specifications/blob/master/source/change-streams/change-streams.rst#startatoperationtime
     * @param Server $server
     * @return boolean
     */
    private function shouldCaptureOperationTime(Server $server)
    {
        if ($this->hasResumed) {
            return false;
        }

        if (isset($this->changeStreamOptions['resumeAfter']) ||
            isset($this->changeStreamOptions['startAfter']) ||
            isset($this->changeStreamOptions['startAtOperationTime'])) {
            return false;
        }

        if ($this->firstBatchSize > 0) {
            return false;
        }

        if ($this->postBatchResumeToken !== null) {
            return false;
        }

        if (! server_supports_feature($server, self::$wireVersionForStartAtOperationTime)) {
            return false;
        }

        return true;
    }
}

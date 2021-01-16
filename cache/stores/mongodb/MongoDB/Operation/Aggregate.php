<?php
/*
 * Copyright 2015-2017 MongoDB, Inc.
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

use ArrayIterator;
use MongoDB\Driver\Command;
use MongoDB\Driver\Exception\RuntimeException as DriverRuntimeException;
use MongoDB\Driver\ReadConcern;
use MongoDB\Driver\ReadPreference;
use MongoDB\Driver\Server;
use MongoDB\Driver\Session;
use MongoDB\Driver\WriteConcern;
use MongoDB\Exception\InvalidArgumentException;
use MongoDB\Exception\UnexpectedValueException;
use MongoDB\Exception\UnsupportedException;
use stdClass;
use Traversable;
use function current;
use function is_array;
use function is_bool;
use function is_integer;
use function is_object;
use function is_string;
use function MongoDB\create_field_path_type_map;
use function MongoDB\is_last_pipeline_operator_write;
use function MongoDB\server_supports_feature;
use function sprintf;

/**
 * Operation for the aggregate command.
 *
 * @api
 * @see \MongoDB\Collection::aggregate()
 * @see http://docs.mongodb.org/manual/reference/command/aggregate/
 */
class Aggregate implements Executable
{
    /** @var integer */
    private static $wireVersionForCollation = 5;

    /** @var integer */
    private static $wireVersionForDocumentLevelValidation = 4;

    /** @var integer */
    private static $wireVersionForReadConcern = 4;

    /** @var integer */
    private static $wireVersionForWriteConcern = 5;

    /** @var string */
    private $databaseName;

    /** @var string|null */
    private $collectionName;

    /** @var array */
    private $pipeline;

    /** @var array */
    private $options;

    /**
     * Constructs an aggregate command.
     *
     * Supported options:
     *
     *  * allowDiskUse (boolean): Enables writing to temporary files. When set
     *    to true, aggregation stages can write data to the _tmp sub-directory
     *    in the dbPath directory. The default is false.
     *
     *  * batchSize (integer): The number of documents to return per batch.
     *
     *  * bypassDocumentValidation (boolean): If true, allows the write to
     *    circumvent document level validation. This only applies when an $out
     *    or $merge stage is specified.
     *
     *    For servers < 3.2, this option is ignored as document level validation
     *    is not available.
     *
     *  * collation (document): Collation specification.
     *
     *    This is not supported for server versions < 3.4 and will result in an
     *    exception at execution time if used.
     *
     *  * comment (string): An arbitrary string to help trace the operation
     *    through the database profiler, currentOp, and logs.
     *
     *  * explain (boolean): Specifies whether or not to return the information
     *    on the processing of the pipeline.
     *
     *  * hint (string|document): The index to use. Specify either the index
     *    name as a string or the index key pattern as a document. If specified,
     *    then the query system will only consider plans using the hinted index.
     *
     *  * maxTimeMS (integer): The maximum amount of time to allow the query to
     *    run.
     *
     *  * readConcern (MongoDB\Driver\ReadConcern): Read concern.
     *
     *    This is not supported for server versions < 3.2 and will result in an
     *    exception at execution time if used.
     *
     *  * readPreference (MongoDB\Driver\ReadPreference): Read preference.
     *
     *    This option is ignored if an $out or $merge stage is specified.
     *
     *  * session (MongoDB\Driver\Session): Client session.
     *
     *    Sessions are not supported for server versions < 3.6.
     *
     *  * typeMap (array): Type map for BSON deserialization. This will be
     *    applied to the returned Cursor (it is not sent to the server).
     *
     *  * useCursor (boolean): Indicates whether the command will request that
     *    the server provide results using a cursor. The default is true.
     *
     *    This option allows users to turn off cursors if necessary to aid in
     *    mongod/mongos upgrades.
     *
     *  * writeConcern (MongoDB\Driver\WriteConcern): Write concern. This only
     *    applies when an $out or $merge stage is specified.
     *
     *    This is not supported for server versions < 3.4 and will result in an
     *    exception at execution time if used.
     *
     * Note: Collection-agnostic commands (e.g. $currentOp) may be executed by
     * specifying null for the collection name.
     *
     * @param string      $databaseName   Database name
     * @param string|null $collectionName Collection name
     * @param array       $pipeline       List of pipeline operations
     * @param array       $options        Command options
     * @throws InvalidArgumentException for parameter/option parsing errors
     */
    public function __construct($databaseName, $collectionName, array $pipeline, array $options = [])
    {
        $expectedIndex = 0;

        foreach ($pipeline as $i => $operation) {
            if ($i !== $expectedIndex) {
                throw new InvalidArgumentException(sprintf('$pipeline is not a list (unexpected index: "%s")', $i));
            }

            if (! is_array($operation) && ! is_object($operation)) {
                throw InvalidArgumentException::invalidType(sprintf('$pipeline[%d]', $i), $operation, 'array or object');
            }

            $expectedIndex += 1;
        }

        $options += [
            'allowDiskUse' => false,
            'useCursor' => true,
        ];

        if (! is_bool($options['allowDiskUse'])) {
            throw InvalidArgumentException::invalidType('"allowDiskUse" option', $options['allowDiskUse'], 'boolean');
        }

        if (isset($options['batchSize']) && ! is_integer($options['batchSize'])) {
            throw InvalidArgumentException::invalidType('"batchSize" option', $options['batchSize'], 'integer');
        }

        if (isset($options['bypassDocumentValidation']) && ! is_bool($options['bypassDocumentValidation'])) {
            throw InvalidArgumentException::invalidType('"bypassDocumentValidation" option', $options['bypassDocumentValidation'], 'boolean');
        }

        if (isset($options['collation']) && ! is_array($options['collation']) && ! is_object($options['collation'])) {
            throw InvalidArgumentException::invalidType('"collation" option', $options['collation'], 'array or object');
        }

        if (isset($options['comment']) && ! is_string($options['comment'])) {
            throw InvalidArgumentException::invalidType('"comment" option', $options['comment'], 'string');
        }

        if (isset($options['explain']) && ! is_bool($options['explain'])) {
            throw InvalidArgumentException::invalidType('"explain" option', $options['explain'], 'boolean');
        }

        if (isset($options['hint']) && ! is_string($options['hint']) && ! is_array($options['hint']) && ! is_object($options['hint'])) {
            throw InvalidArgumentException::invalidType('"hint" option', $options['hint'], 'string or array or object');
        }

        if (isset($options['maxAwaitTimeMS']) && ! is_integer($options['maxAwaitTimeMS'])) {
            throw InvalidArgumentException::invalidType('"maxAwaitTimeMS" option', $options['maxAwaitTimeMS'], 'integer');
        }

        if (isset($options['maxTimeMS']) && ! is_integer($options['maxTimeMS'])) {
            throw InvalidArgumentException::invalidType('"maxTimeMS" option', $options['maxTimeMS'], 'integer');
        }

        if (isset($options['readConcern']) && ! $options['readConcern'] instanceof ReadConcern) {
            throw InvalidArgumentException::invalidType('"readConcern" option', $options['readConcern'], ReadConcern::class);
        }

        if (isset($options['readPreference']) && ! $options['readPreference'] instanceof ReadPreference) {
            throw InvalidArgumentException::invalidType('"readPreference" option', $options['readPreference'], ReadPreference::class);
        }

        if (isset($options['session']) && ! $options['session'] instanceof Session) {
            throw InvalidArgumentException::invalidType('"session" option', $options['session'], Session::class);
        }

        if (isset($options['typeMap']) && ! is_array($options['typeMap'])) {
            throw InvalidArgumentException::invalidType('"typeMap" option', $options['typeMap'], 'array');
        }

        if (! is_bool($options['useCursor'])) {
            throw InvalidArgumentException::invalidType('"useCursor" option', $options['useCursor'], 'boolean');
        }

        if (isset($options['writeConcern']) && ! $options['writeConcern'] instanceof WriteConcern) {
            throw InvalidArgumentException::invalidType('"writeConcern" option', $options['writeConcern'], WriteConcern::class);
        }

        if (isset($options['batchSize']) && ! $options['useCursor']) {
            throw new InvalidArgumentException('"batchSize" option should not be used if "useCursor" is false');
        }

        if (isset($options['readConcern']) && $options['readConcern']->isDefault()) {
            unset($options['readConcern']);
        }

        if (isset($options['writeConcern']) && $options['writeConcern']->isDefault()) {
            unset($options['writeConcern']);
        }

        if (! empty($options['explain'])) {
            $options['useCursor'] = false;
        }

        $this->databaseName = (string) $databaseName;
        $this->collectionName = isset($collectionName) ? (string) $collectionName : null;
        $this->pipeline = $pipeline;
        $this->options = $options;
    }

    /**
     * Execute the operation.
     *
     * @see Executable::execute()
     * @param Server $server
     * @return Traversable
     * @throws UnexpectedValueException if the command response was malformed
     * @throws UnsupportedException if collation, read concern, or write concern is used and unsupported
     * @throws DriverRuntimeException for other driver errors (e.g. connection errors)
     */
    public function execute(Server $server)
    {
        if (isset($this->options['collation']) && ! server_supports_feature($server, self::$wireVersionForCollation)) {
            throw UnsupportedException::collationNotSupported();
        }

        if (isset($this->options['readConcern']) && ! server_supports_feature($server, self::$wireVersionForReadConcern)) {
            throw UnsupportedException::readConcernNotSupported();
        }

        if (isset($this->options['writeConcern']) && ! server_supports_feature($server, self::$wireVersionForWriteConcern)) {
            throw UnsupportedException::writeConcernNotSupported();
        }

        $inTransaction = isset($this->options['session']) && $this->options['session']->isInTransaction();
        if ($inTransaction) {
            if (isset($this->options['readConcern'])) {
                throw UnsupportedException::readConcernNotSupportedInTransaction();
            }
            if (isset($this->options['writeConcern'])) {
                throw UnsupportedException::writeConcernNotSupportedInTransaction();
            }
        }

        $hasExplain = ! empty($this->options['explain']);
        $hasWriteStage = is_last_pipeline_operator_write($this->pipeline);

        $command = $this->createCommand($server, $hasWriteStage);
        $options = $this->createOptions($hasWriteStage, $hasExplain);

        $cursor = $hasWriteStage && ! $hasExplain
            ? $server->executeReadWriteCommand($this->databaseName, $command, $options)
            : $server->executeReadCommand($this->databaseName, $command, $options);

        if ($this->options['useCursor'] || $hasExplain) {
            if (isset($this->options['typeMap'])) {
                $cursor->setTypeMap($this->options['typeMap']);
            }

            return $cursor;
        }

        if (isset($this->options['typeMap'])) {
            $cursor->setTypeMap(create_field_path_type_map($this->options['typeMap'], 'result.$'));
        }

        $result = current($cursor->toArray());

        if (! isset($result->result) || ! is_array($result->result)) {
            throw new UnexpectedValueException('aggregate command did not return a "result" array');
        }

        return new ArrayIterator($result->result);
    }

    /**
     * Create the aggregate command.
     *
     * @param Server  $server
     * @param boolean $hasWriteStage
     * @return Command
     */
    private function createCommand(Server $server, $hasWriteStage)
    {
        $cmd = [
            'aggregate' => isset($this->collectionName) ? $this->collectionName : 1,
            'pipeline' => $this->pipeline,
        ];
        $cmdOptions = [];

        $cmd['allowDiskUse'] = $this->options['allowDiskUse'];

        if (! empty($this->options['bypassDocumentValidation']) &&
            server_supports_feature($server, self::$wireVersionForDocumentLevelValidation)
        ) {
            $cmd['bypassDocumentValidation'] = $this->options['bypassDocumentValidation'];
        }

        foreach (['comment', 'explain', 'maxTimeMS'] as $option) {
            if (isset($this->options[$option])) {
                $cmd[$option] = $this->options[$option];
            }
        }

        if (isset($this->options['collation'])) {
            $cmd['collation'] = (object) $this->options['collation'];
        }

        if (isset($this->options['hint'])) {
            $cmd['hint'] = is_array($this->options['hint']) ? (object) $this->options['hint'] : $this->options['hint'];
        }

        if (isset($this->options['maxAwaitTimeMS'])) {
            $cmdOptions['maxAwaitTimeMS'] = $this->options['maxAwaitTimeMS'];
        }

        if ($this->options['useCursor']) {
            /* Ignore batchSize if pipeline includes an $out or $merge stage, as
             * no documents will be returned and sending a batchSize of zero
             * could prevent the pipeline from executing at all. */
            $cmd['cursor'] = isset($this->options["batchSize"]) && ! $hasWriteStage
                ? ['batchSize' => $this->options["batchSize"]]
                : new stdClass();
        }

        return new Command($cmd, $cmdOptions);
    }

    /**
     * Create options for executing the command.
     *
     * @see http://php.net/manual/en/mongodb-driver-server.executereadcommand.php
     * @see http://php.net/manual/en/mongodb-driver-server.executereadwritecommand.php
     * @param boolean $hasWriteStage
     * @param boolean $hasExplain
     * @return array
     */
    private function createOptions($hasWriteStage, $hasExplain)
    {
        $options = [];

        if (isset($this->options['readConcern'])) {
            $options['readConcern'] = $this->options['readConcern'];
        }

        if (! $hasWriteStage && isset($this->options['readPreference'])) {
            $options['readPreference'] = $this->options['readPreference'];
        }

        if (isset($this->options['session'])) {
            $options['session'] = $this->options['session'];
        }

        if ($hasWriteStage && ! $hasExplain && isset($this->options['writeConcern'])) {
            $options['writeConcern'] = $this->options['writeConcern'];
        }

        return $options;
    }
}

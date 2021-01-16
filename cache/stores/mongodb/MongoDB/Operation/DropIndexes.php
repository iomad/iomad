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

use MongoDB\Driver\Command;
use MongoDB\Driver\Exception\RuntimeException as DriverRuntimeException;
use MongoDB\Driver\Server;
use MongoDB\Driver\Session;
use MongoDB\Driver\WriteConcern;
use MongoDB\Exception\InvalidArgumentException;
use MongoDB\Exception\UnsupportedException;
use function current;
use function is_array;
use function is_integer;
use function MongoDB\server_supports_feature;

/**
 * Operation for the dropIndexes command.
 *
 * @api
 * @see \MongoDB\Collection::dropIndexes()
 * @see http://docs.mongodb.org/manual/reference/command/dropIndexes/
 */
class DropIndexes implements Executable
{
    /** @var integer */
    private static $wireVersionForWriteConcern = 5;

    /** @var string */
    private $databaseName;

    /** @var string */
    private $collectionName;

    /** @var string */
    private $indexName;

    /** @var array */
    private $options;

    /**
     * Constructs a dropIndexes command.
     *
     * Supported options:
     *
     *  * maxTimeMS (integer): The maximum amount of time to allow the query to
     *    run.
     *
     *  * session (MongoDB\Driver\Session): Client session.
     *
     *    Sessions are not supported for server versions < 3.6.
     *
     *  * typeMap (array): Type map for BSON deserialization. This will be used
     *    for the returned command result document.
     *
     *  * writeConcern (MongoDB\Driver\WriteConcern): Write concern.
     *
     *    This is not supported for server versions < 3.4 and will result in an
     *    exception at execution time if used.
     *
     * @param string $databaseName   Database name
     * @param string $collectionName Collection name
     * @param string $indexName      Index name (use "*" to drop all indexes)
     * @param array  $options        Command options
     * @throws InvalidArgumentException for parameter/option parsing errors
     */
    public function __construct($databaseName, $collectionName, $indexName, array $options = [])
    {
        $indexName = (string) $indexName;

        if ($indexName === '') {
            throw new InvalidArgumentException('$indexName cannot be empty');
        }

        if (isset($options['maxTimeMS']) && ! is_integer($options['maxTimeMS'])) {
            throw InvalidArgumentException::invalidType('"maxTimeMS" option', $options['maxTimeMS'], 'integer');
        }

        if (isset($options['session']) && ! $options['session'] instanceof Session) {
            throw InvalidArgumentException::invalidType('"session" option', $options['session'], Session::class);
        }

        if (isset($options['typeMap']) && ! is_array($options['typeMap'])) {
            throw InvalidArgumentException::invalidType('"typeMap" option', $options['typeMap'], 'array');
        }

        if (isset($options['writeConcern']) && ! $options['writeConcern'] instanceof WriteConcern) {
            throw InvalidArgumentException::invalidType('"writeConcern" option', $options['writeConcern'], WriteConcern::class);
        }

        if (isset($options['writeConcern']) && $options['writeConcern']->isDefault()) {
            unset($options['writeConcern']);
        }

        $this->databaseName = (string) $databaseName;
        $this->collectionName = (string) $collectionName;
        $this->indexName = $indexName;
        $this->options = $options;
    }

    /**
     * Execute the operation.
     *
     * @see Executable::execute()
     * @param Server $server
     * @return array|object Command result document
     * @throws UnsupportedException if writeConcern is used and unsupported
     * @throws DriverRuntimeException for other driver errors (e.g. connection errors)
     */
    public function execute(Server $server)
    {
        if (isset($this->options['writeConcern']) && ! server_supports_feature($server, self::$wireVersionForWriteConcern)) {
            throw UnsupportedException::writeConcernNotSupported();
        }

        $inTransaction = isset($this->options['session']) && $this->options['session']->isInTransaction();
        if ($inTransaction && isset($this->options['writeConcern'])) {
            throw UnsupportedException::writeConcernNotSupportedInTransaction();
        }

        $cursor = $server->executeWriteCommand($this->databaseName, $this->createCommand(), $this->createOptions());

        if (isset($this->options['typeMap'])) {
            $cursor->setTypeMap($this->options['typeMap']);
        }

        return current($cursor->toArray());
    }

    /**
     * Create the dropIndexes command.
     *
     * @return Command
     */
    private function createCommand()
    {
        $cmd = [
            'dropIndexes' => $this->collectionName,
            'index' => $this->indexName,
        ];

        if (isset($this->options['maxTimeMS'])) {
            $cmd['maxTimeMS'] = $this->options['maxTimeMS'];
        }

        return new Command($cmd);
    }

    /**
     * Create options for executing the command.
     *
     * @see http://php.net/manual/en/mongodb-driver-server.executewritecommand.php
     * @return array
     */
    private function createOptions()
    {
        $options = [];

        if (isset($this->options['session'])) {
            $options['session'] = $this->options['session'];
        }

        if (isset($this->options['writeConcern'])) {
            $options['writeConcern'] = $this->options['writeConcern'];
        }

        return $options;
    }
}

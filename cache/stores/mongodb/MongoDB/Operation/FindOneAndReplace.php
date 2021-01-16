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

use MongoDB\Driver\Exception\RuntimeException as DriverRuntimeException;
use MongoDB\Driver\Server;
use MongoDB\Exception\InvalidArgumentException;
use MongoDB\Exception\UnsupportedException;
use function is_array;
use function is_integer;
use function is_object;
use function MongoDB\is_first_key_operator;

/**
 * Operation for replacing a document with the findAndModify command.
 *
 * @api
 * @see \MongoDB\Collection::findOneAndReplace()
 * @see http://docs.mongodb.org/manual/reference/command/findAndModify/
 */
class FindOneAndReplace implements Executable, Explainable
{
    const RETURN_DOCUMENT_BEFORE = 1;
    const RETURN_DOCUMENT_AFTER = 2;

    /** @var FindAndModify */
    private $findAndModify;

    /**
     * Constructs a findAndModify command for replacing a document.
     *
     * Supported options:
     *
     *  * bypassDocumentValidation (boolean): If true, allows the write to
     *    circumvent document level validation.
     *
     *    For servers < 3.2, this option is ignored as document level validation
     *    is not available.
     *
     *  * collation (document): Collation specification.
     *
     *    This is not supported for server versions < 3.4 and will result in an
     *    exception at execution time if used.
     *
     *  * maxTimeMS (integer): The maximum amount of time to allow the query to
     *    run.
     *
     *  * projection (document): Limits the fields to return for the matching
     *    document.
     *
     *  * returnDocument (enum): Whether to return the document before or after
     *    the update is applied. Must be either
     *    FindOneAndReplace::RETURN_DOCUMENT_BEFORE or
     *    FindOneAndReplace::RETURN_DOCUMENT_AFTER. The default is
     *    FindOneAndReplace::RETURN_DOCUMENT_BEFORE.
     *
     *  * session (MongoDB\Driver\Session): Client session.
     *
     *    Sessions are not supported for server versions < 3.6.
     *
     *  * sort (document): Determines which document the operation modifies if
     *    the query selects multiple documents.
     *
     *  * typeMap (array): Type map for BSON deserialization.
     *
     *  * upsert (boolean): When true, a new document is created if no document
     *    matches the query. The default is false.
     *
     *  * writeConcern (MongoDB\Driver\WriteConcern): Write concern.
     *
     *    This is not supported for server versions < 3.2 and will result in an
     *    exception at execution time if used.
     *
     * @param string       $databaseName   Database name
     * @param string       $collectionName Collection name
     * @param array|object $filter         Query by which to filter documents
     * @param array|object $replacement    Replacement document
     * @param array        $options        Command options
     * @throws InvalidArgumentException for parameter/option parsing errors
     */
    public function __construct($databaseName, $collectionName, $filter, $replacement, array $options = [])
    {
        if (! is_array($filter) && ! is_object($filter)) {
            throw InvalidArgumentException::invalidType('$filter', $filter, 'array or object');
        }

        if (! is_array($replacement) && ! is_object($replacement)) {
            throw InvalidArgumentException::invalidType('$replacement', $replacement, 'array or object');
        }

        if (is_first_key_operator($replacement)) {
            throw new InvalidArgumentException('First key in $replacement argument is an update operator');
        }

        $options += [
            'returnDocument' => self::RETURN_DOCUMENT_BEFORE,
            'upsert' => false,
        ];

        if (isset($options['projection']) && ! is_array($options['projection']) && ! is_object($options['projection'])) {
            throw InvalidArgumentException::invalidType('"projection" option', $options['projection'], 'array or object');
        }

        if (! is_integer($options['returnDocument'])) {
            throw InvalidArgumentException::invalidType('"returnDocument" option', $options['returnDocument'], 'integer');
        }

        if ($options['returnDocument'] !== self::RETURN_DOCUMENT_AFTER &&
            $options['returnDocument'] !== self::RETURN_DOCUMENT_BEFORE) {
            throw new InvalidArgumentException('Invalid value for "returnDocument" option: ' . $options['returnDocument']);
        }

        if (isset($options['projection'])) {
            $options['fields'] = $options['projection'];
        }

        $options['new'] = $options['returnDocument'] === self::RETURN_DOCUMENT_AFTER;

        unset($options['projection'], $options['returnDocument']);

        $this->findAndModify = new FindAndModify(
            $databaseName,
            $collectionName,
            ['query' => $filter, 'update' => $replacement] + $options
        );
    }

    /**
     * Execute the operation.
     *
     * @see Executable::execute()
     * @param Server $server
     * @return array|object|null
     * @throws UnsupportedException if collation or write concern is used and unsupported
     * @throws DriverRuntimeException for other driver errors (e.g. connection errors)
     */
    public function execute(Server $server)
    {
        return $this->findAndModify->execute($server);
    }

    public function getCommandDocument(Server $server)
    {
        return $this->findAndModify->getCommandDocument($server);
    }
}

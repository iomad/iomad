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

namespace MongoDB\Exception;

class UnsupportedException extends RuntimeException
{
    /**
     * Thrown when array filters are not supported by a server.
     *
     * @return self
     */
    public static function arrayFiltersNotSupported()
    {
        return new static('Array filters are not supported by the server executing this operation');
    }

    /**
     * Thrown when collations are not supported by a server.
     *
     * @return self
     */
    public static function collationNotSupported()
    {
        return new static('Collations are not supported by the server executing this operation');
    }

    /**
     * Thrown when explain is not supported by a server.
     *
     * @return self
     */
    public static function explainNotSupported()
    {
        return new static('Explain is not supported by the server executing this operation');
    }

    /**
     * Thrown when a command's readConcern option is not supported by a server.
     *
     * @return self
     */
    public static function readConcernNotSupported()
    {
        return new static('Read concern is not supported by the server executing this command');
    }

    /**
     * Thrown when a readConcern is used with a read operation in a transaction.
     *
     * @return self
     */
    public static function readConcernNotSupportedInTransaction()
    {
        return new static('The "readConcern" option cannot be specified within a transaction. Instead, specify it when starting the transaction.');
    }

    /**
     * Thrown when a command's writeConcern option is not supported by a server.
     *
     * @return self
     */
    public static function writeConcernNotSupported()
    {
        return new static('Write concern is not supported by the server executing this command');
    }

    /**
     * Thrown when a writeConcern is used with a write operation in a transaction.
     *
     * @return self
     */
    public static function writeConcernNotSupportedInTransaction()
    {
        return new static('The "writeConcern" option cannot be specified within a transaction. Instead, specify it when starting the transaction.');
    }
}

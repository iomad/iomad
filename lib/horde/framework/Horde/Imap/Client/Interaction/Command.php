<?php
/**
 * Copyright 2012-2017 Horde LLC (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.horde.org/licenses/lgpl21.
 *
 * @category  Horde
 * @copyright 2012-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Imap_Client
 */

/**
 * An object representing an IMAP command (RFC 3501 [2.2.1]).
 *
 * @author    Michael Slusarz <slusarz@horde.org>
 * @category  Horde
 * @copyright 2012-2017 Horde LLC
 * @license   http://www.horde.org/licenses/lgpl21 LGPL 2.1
 * @package   Imap_Client
 * @since     2.10.0
 *
 * @property-read boolean $continuation  True if the command requires a server
 *                                       continuation response.
 */
class Horde_Imap_Client_Interaction_Command
extends Horde_Imap_Client_Data_Format_List
{
    /**
     * Debug string(s) to use instead of command text.
     *
     * Multiple entries refer to the various steps in a continuation command.
     *
     * @var array
     */
    public $debug = array();

    /**
     * Use LITERAL+ if available
     *
     * @var boolean
     */
    public $literalplus = true;

    /**
     * Are literal8's available?
     *
     * @var boolean
     */
    public $literal8 = false;

    /**
     * A callback to run on error.
     *
     * If callback returns true, the command will be treated as successful.
     *
     * @since 2.24.0
     *
     * @var callback
     */
    public $on_error = null;

    /**
     * A callback to run on success.
     *
     * @since 2.28.0
     *
     * @var callback
     */
    public $on_success = null;

    /**
     * Pipeline object associated with this command.
     *
     * @since 2.28.0
     *
     * @var Horde_Imap_Client_Interaction_Pipeline
     */
    public $pipeline;

    /**
     * Server response.
     *
     * @var Horde_Imap_Client_Interaction_Server
     */
    public $response;

    /**
     * The command tag.
     *
     * @var string
     */
    public $tag;

    /**
     * Command timer.
     *
     * @var Horde_Support_Timer
     */
    protected $_timer;

    /**
     * Constructor.
     *
     * @param string $cmd  The IMAP command.
     * @param string $tag  The tag to use. If not set, will be automatically
     *                     generated.
     */
    public function __construct($cmd, $tag = null)
    {
        $this->tag = is_null($tag)
            ? substr(new Horde_Support_Randomid(), 0, 10)
            : strval($tag);

        parent::__construct($this->tag);

        $this->add($cmd);
    }

    /**
     */
    public function __get($name)
    {
        switch ($name) {
        case 'continuation':
            return $this->_continuationCheck($this);
        }
    }

    /**
     * Get the command.
     *
     * @return string  The command.
     */
    public function getCommand()
    {
        return $this->_data[1];
    }

    /**
     * Start the command timer.
     */
    public function startTimer()
    {
        $this->_timer = new Horde_Support_Timer();
        $this->_timer->push();
    }

    /**
     * Return the timer data.
     *
     * @return mixed  Null if timer wasn't started, or a float containing
     *                elapsed command time.
     */
    public function getTimer()
    {
        return $this->_timer
            ? round($this->_timer->pop(), 4)
            : null;
    }

    /**
     * Recursive check for continuation functions.
     */
    protected function _continuationCheck($list)
    {
        foreach ($list as $val) {
            if (($val instanceof Horde_Imap_Client_Interaction_Command_Continuation) ||
                (($val instanceof Horde_Imap_Client_Data_Format_String) &&
                 $val->literal())) {
                return true;
            }

            if (($val instanceof Horde_Imap_Client_Data_Format_List) &&
                $this->_continuationCheck($val)) {
                return true;
            }
        }

        return false;
    }

}

<?php
/*
 *  $Id$
 *
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
 * This software consists of voluntary contributions made by many individuals
 * and is licensed under the LGPL. For more information please see
 * <http://phing.info>.
 */
 
require_once 'phing/BuildListener.php';
include_once 'phing/BuildEvent.php';
require_once 'Log.php';

/**
 * Writes log messages to PEAR Log.
 * 
 * By default it will log to file in current directory w/ name 'phing.log'.  You can customize
 * this behavior by setting properties:
 * - pear.log.type
 * - pear.log.name
 * - pear.log.ident (note that this class changes ident to project name)
 * - pear.log.conf (note that array values are currently unsupported in Phing property files)
 * 
 * <code>
 *  phing -f build.xml -logger phing.listener.PearLogger -Dpear.log.type=file -Dpear.log.name=/path/to/log.log
 * </code>
 * 
 * @author    Hans Lellelid <hans@xmpl.org>
 * @version   $Revision: 1.3 $ $Date$
 * @see       BuildEvent
 * @package   phing.listener
 */
class PearLogger implements BuildListener {

    /**
     *  Size of the left column in output. The default char width is 12.
     *  @var int
     */
    const LEFT_COLUMN_SIZE = 12;

    /**
     *  The message output level that should be used. The default is
     *  <code>PROJECT_MSG_VERBOSE</code>.
     *  @var int
     */
    protected $msgOutputLevel = PROJECT_MSG_DEBUG;

    /**
     *  Time that the build started
     *  @var int
     */
    protected $startTime;
    
    /**
     * Maps Phing PROJECT_MSG_* constants to PEAR_LOG_* constants.
     * @var array
     */
    protected static $levelMap = array( PROJECT_MSG_DEBUG => PEAR_LOG_DEBUG,
                                        PROJECT_MSG_INFO => PEAR_LOG_INFO,
                                        PROJECT_MSG_VERBOSE => PEAR_LOG_NOTICE,
                                        PROJECT_MSG_WARN => PEAR_LOG_WARNING,
                                        PROJECT_MSG_ERR => PEAR_LOG_ERR
                                       );
    /**
     * Whether logging has been configured.
     * @var boolean
     */
    protected $logConfigured = false;
              
    /**
     * Configure the logger.
     */
    protected function configureLogging() {
    
    	$logfile = Phing::getDefinedProperty('phing.listener.logfile');
    	
        $type = Phing::getDefinedProperty('pear.log.type');
        $name = Phing::getDefinedProperty('pear.log.name');
        $ident = Phing::getDefinedProperty('pear.log.ident');
        $conf = Phing::getDefinedProperty('pear.log.conf');
        
        if ($type === null) $type = 'file';
        
        if ($name === null) {
        	if ($logfile === null) {
        		$name = 'phing.log';
        	} else {
        		$name = $logfile;
        	}
        }
        if ($ident === null) $ident = 'phing';
        if ($conf === null) $conf = array();
        
        $this->logger = Log::singleton($type, $name, $ident, $conf, self::$levelMap[$this->msgOutputLevel]);
    }        
    
    /**
     * Get the configured PEAR logger to use.
     * This method just ensures that logging has been configured and returns the configured logger.
     * @return Log
     */
    protected function logger() {
        if (!$this->logConfigured) {
            $this->configureLogging();
        }
        return $this->logger;
    }
    
    /**
     *  Set the msgOutputLevel this logger is to respond to.
     *
     *  Only messages with a message level lower than or equal to the given
     *  level are output to the log.
     *
     *  <p> Constants for the message levels are in Project.php. The order of
     *  the levels, from least to most verbose, is:
     *
     *  <ul>
     *    <li>PROJECT_MSG_ERR</li>
     *    <li>PROJECT_MSG_WARN</li>
     *    <li>PROJECT_MSG_INFO</li>
     *    <li>PROJECT_MSG_VERBOSE</li>
     *    <li>PROJECT_MSG_DEBUG</li>
     *  </ul>
     *
     *  The default message level for DefaultLogger is PROJECT_MSG_ERR.
     *
     *  @param  integer  the logging level for the logger.
     *  @access public
     */
    function setMessageOutputLevel($level) {
        $this->msgOutputLevel = (int) $level;
    }

    /**
    *  Sets the start-time when the build started. Used for calculating
    *  the build-time.
    *
    *  @param  object  The BuildEvent
    *  @access public
    */

    function buildStarted(BuildEvent $event) {
        $this->startTime = Phing::currentTimeMillis();
        $this->logger()->setIdent($event->getProject()->getName());
        $this->logger()->info("Starting build with buildfile: ". $event->getProject()->getProperty("phing.file"));
    }

    /**
     *  Prints whether the build succeeded or failed, and any errors that
     *  occured during the build. Also outputs the total build-time.
     *
     *  @param  object  The BuildEvent
     *  @access public
     *  @see    BuildEvent::getException()
     */
    function buildFinished(BuildEvent $event) {
        $error = $event->getException();
        if ($error === null) {
            $msg = "Finished successful build.";
        } else {
            $msg = "Build failed. [reason: " . $error->getMessage() ."]";
        }
        $this->logger()->log($msg . " Total time: " . DefaultLogger::formatTime(Phing::currentTimeMillis() - $this->startTime));
    }

    /**
     *  Prints the current target name
     *
     *  @param  object  The BuildEvent
     *  @access public
     *  @see    BuildEvent::getTarget()
     */
    function targetStarted(BuildEvent $event) {}

    /**
     *  Fired when a target has finished. We don't need specific action on this
     *  event. So the methods are empty.
     *
     *  @param  object  The BuildEvent
     *  @access public
     *  @see    BuildEvent::getException()
     */
    function targetFinished(BuildEvent $event) {}

    /**
     *  Fired when a task is started. We don't need specific action on this
     *  event. So the methods are empty.
     *
     *  @param  object  The BuildEvent
     *  @access public
     *  @see    BuildEvent::getTask()
     */
    function taskStarted(BuildEvent $event) {}

    /**
     *  Fired when a task has finished. We don't need specific action on this
     *  event. So the methods are empty.
     *
     *  @param  object  The BuildEvent
     *  @access public
     *  @see    BuildEvent::getException()
     */
    function taskFinished(BuildEvent $event) {}

    /**
     *  Print a message to the stdout.
     *
     *  @param  object  The BuildEvent
     *  @access public
     *  @see    BuildEvent::getMessage()
     */
    function messageLogged(BuildEvent $event) {
        if ($event->getPriority() <= $this->msgOutputLevel) {            
            $msg = "";
            if ($event->getTask() !== null) {
                $name = $event->getTask();
                $name = $name->getTaskName();
                $msg = str_pad("[$name] ", self::LEFT_COLUMN_SIZE, " ", STR_PAD_LEFT);
            }
            $msg .= $event->getMessage();
            $this->logger()->log($msg, self::$levelMap[$event->getPriority()]);
        }
    }
}

<?php
/*
 *  $Id: SvnBaseTask.php,v 1.1 2006/01/25 15:28:12 mrook Exp $
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
 
include_once 'phing/Task.php';

/**
 *  Send a message by mail() 
 *
 *  <mail to="user@example.org" subject="build complete">The build process is a success...</mail> 
 * 
 *  @author   Francois Harvey at SecuriWeb (http://www.securiweb.net)
 *  @version  $Revision: 1.1 $
 *  @package  phing.tasks.ext
 */
abstract class SvnBaseTask extends Task
{
	private $workingCopy = "";
	
	private $repositoryUrl = "";
	
	private $svnPath = "/usr/bin/svn";
	
	private $svn = NULL;
	
	private $mode = "";
	
	private $svnArgs = array();

	/**
	 * Initialize Task.
 	 * This method includes any necessary SVN libraries and triggers
	 * appropriate error if they cannot be found.  This is not done in header
	 * because we may want this class to be loaded w/o triggering an error.
	 */
	function init() {
		include_once 'VersionControl/SVN.php';
		if (!class_exists('VersionControl_SVN')) {
			throw new Exception("SvnLastRevisionTask depends on PEAR VersionControl_SVN package being installed.");
		}
	}

	/**
	 * Sets the path to the workingcopy
	 */
	function setWorkingCopy($workingCopy)
	{
		$this->workingCopy = $workingCopy;
	}

	/**
	 * Returns the path to the workingcopy
	 */
	function getWorkingCopy()
	{
		return $this->workingCopy;
	}

	/**
	 * Sets the path/URI to the repository
	 */
	function setRepositoryUrl($repositoryUrl)
	{
		$this->repositoryUrl = $repositoryUrl;
	}

	/**
	 * Returns the path/URI to the repository
	 */
	function getRepositoryUrl()
	{
		return $this->repositoryUrl;
	}

	/**
	 * Sets the path to the SVN executable
	 */
	function setSvnPath($svnPath)
	{
		$this->svnPath = $svnPath;
	}

	/**
	 * Returns the path to the SVN executable
	 */
	function getSvnPath()
	{
		return $this->svnPath;
	}
	
	/**
	 * Creates a VersionControl_SVN class based on $mode
	 *
	 * @param mode The SVN mode to use (info, export, checkout, ...)
	 * @throws BuildException
	 */
	protected function setup($mode)
	{
		$this->mode = $mode;
		
		// Set up runtime options. Will be passed to all
		// subclasses.
		$options = array('fetchmode' => VERSIONCONTROL_SVN_FETCHMODE_ASSOC, 'svn_path' => $this->getSvnPath());
		
		// Pass array of subcommands we need to factory
		$this->svn = VersionControl_SVN::factory($mode, $options);

		if (!empty($this->repositoryUrl))
		{
			$this->svnArgs = array($this->repositoryUrl);
		}
		else
		if (!empty($this->workingCopy))
		{
			if (is_dir($this->workingCopy))
			{
				if (in_array(".svn", scandir($this->workingCopy)))
				{
					$this->svnArgs = array($this->workingCopy);
				}
				else
				{
					throw new BuildException("'".$this->workingCopy."' doesn't seem to be a working copy");
				}
			}
			else
			{
				throw new BuildException("'".$this->workingCopy."' is not a directory");
			}
		}
	}
	
	/**
	 * Executes the constructed VersionControl_SVN instance
	 *
	 * @param args Additional arguments to pass to SVN.
	 * @returns string Output generated by SVN.
	 */
	protected function run($args = NULL)
	{
		$svnstack = PEAR_ErrorStack::singleton('VersionControl_SVN');
		
		$tempArgs = $this->svnArgs;
		
		if (is_array($args))
		{
			$tempArgs = array_merge($tempArgs, $args);
		}
		
		if ($output = $this->svn->run($tempArgs))
		{
			return $output;
		}
		else
		{
			if (count($errs = $svnstack->getErrors()))
			{
				$err = current($errs);

				throw new BuildException("Failed to run the 'svn " . $this->mode . "' command: " . $err['message']);
			}
		}
	}
}
?>
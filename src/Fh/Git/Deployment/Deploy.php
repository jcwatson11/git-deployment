<?php
namespace Fh\Git\Deployment;

/**
 * Carries environment variables and provides
 * a controller for managing a deployment.
 *
 * Three different strategies are implemented and configured
 * in the config.php file, which can be set based on environment
 * variables set in the calling shell:
 *
 * - TagStrategy: Defines when and how to bump versions
 *   and tag a push that has been deployed into the work
 *   area.
 * - LocallyModifiedFileStrategy: Defines what to do with
 *   locally modified files in the work area when you push
 *   a new release to it.
 * - DeploymentStrategy: Defines the deployment and post-
 *   deployment itself.
 *
 * See main() for the controller flow.
 * See various strategy implementations to find out what
 * they do.
 */
class Deploy extends CommandRunner {

    /**
     * Configuration array that defines where and how
     * to deploy the given git push
     * @var array
     */
    public $config  = [];

    /**
     * String hash commit ID of the place the branch
     * was at before this push.
     * Empty when pushing a tag.
     * @var string
     */
    public $oldrev  = '';

    /**
     * String hash commit ID of the branch or tag being
     * pushed now.
     * @var string
     */
    public $newrev  = '';

    /**
     * git path to the reference being pushed.
     * For example: refs/tags/3.0.1, or refs/heads/3.0
     * @var string
     */
    public $ref     = '';

    /**
     * Basename of the ref path.
     * For example: 3.0.1 or 3.0
     * as opposed to the ref which is: refs/heads/3.0
     * @var string
     */
    public $baseref = '';

    /**
     * Constructor
     * @param array $config coniguration from config.php
     * @param string $oldrev hash from git
     * @param string $newrev hash from git
     * @param string $ref    Example: refs/tags/3.0.1
     */
    public function __construct($config, $oldrev, $newrev, $ref) {
        $this->config = $config;
        $this->oldrev = $oldrev;
        $this->newrev = $newrev;
        $this->ref = $ref;
        $this->baseref = basename($ref);
    }

    /**
     * Returns true if the target ref indicates a deletion.
     * @param  string  $ref hash from git
     * @return boolean
     */
    public function isDeleting($ref) {
        return ($ref == "0000000000000000000000000000000000000000");
    }

    /**
     * Fetches from origin in the target work area.
     * @return string output from commands
     */
    public function fetchOrigin() {
        $ret = '';
        $ret .= $this->git('fetch origin');
        $ret .= $this->git('fetch origin --tags');
        return $ret;
    }

    /**
     * Resolves the locally modified file strategy class
     * and returns an instance of it.
     * @return LocallyModifiedFileStrategyInterface
     */
    public function getLocallyModifiedFileStrategy() {
        $class = $this->config['locally_modified_file_strategy'];
        $instance = new $class($this);
        $this->out('Following strategy: ' . get_class($instance));
        return new $class($instance);
    }

    /**
     * Resolves the tag strategy class
     * and returns an instance of it.
     * @return TagStrategyInterface
     */
    public function getTagStrategy() {
        $class = $this->config['tag_strategy'];
        $instance = new $class($this);
        $this->out('Following strategy: ' . get_class($instance));
        return new $class($instance);
    }

    /**
     * Resolves the deployment strategy class
     * and returns an instance of it.
     * @return DeploymentStrategyInterface
     */
    public function getDeploymentStrategy() {
        $class = $this->config['deployment_strategy'];
        $instance = new $class($this);
        $this->out('Following strategy: ' . get_class($instance));
        return new $class($instance);
    }

    /**
     * Returns true if the ref refers to a tag.
     * False otherwise
     * @param  string  $ref Example: refs/tags/3.0.1
     * @return boolean      [description]
     */
    public function isTag($ref) {
        return preg_match('/^refs\/tags/',$ref) > 0;
    }

    /**
     * Returns true if the ref refers to a branch.
     * False otherwise
     * @param  string  $ref Example: refs/heads/3.0.1
     * @return boolean      [description]
     */
    public function isBranch($ref) {
        return preg_match('/^refs\/heads/',$ref) > 0;
    }

    /**
     * Returns true if the given remote exists in the target
     * work area by name. Returns false if it does not exist.
     * @param  string $remoteName like origin, beta, etc.
     * @return boolean
     */
    public function remoteExists($remoteName) {
        $response = trim($this->git("remote | egrep \"^$remoteName$\" | tail -1"));
        return ($response == $remoteName);
    }

    /**
     * Runs a git command with the given parameters.
     * Cleans up calls to git because of the need for the
     * $gitsetup variable.
     * @param  string $strParams git commands and parameters
     * @return void
     */
    public function git($strParams) {
        $gitsetup = $this->config['gitsetup'];
        return $this->command("git $gitsetup $strParams");
    }

    /**
     * The main processing entry point for the deployment process
     * @return void
     */
    public function main() {
        if($this->config['testing']) {
            $this->out("WARNING: You are in test mode. No deployment will actually take place.");
        }

        $this->out("INPUT oldrev: {$this->oldrev}, newrev: {$this->newrev}, ref: {$this->ref}");

        // If we're deleting something, don't deploy.
        if($this->isDeleting($this->newrev)) {
            $this->out("Deleting a ref does not trigger deployment.");
            exit;
        }

        // Make sure the remote exists
        $remote = $this->config['deployment_remote_name'];
        $this->out("Checking to make sure the $remote remote exists in the target work area.");
        if(!$this->remoteExists($remote)) {
            $this->out("The $remote remote is not set up in the target work area.");
            $this->out("CANNOT COMPLETE DEPLOYMENT");
            exit;
        } else {
            $this->out("It exists. So we're ok.");
        }

        $deployStrategy = $this->getDeploymentStrategy();
        // Now do pre-deployment processing, which
        // might include executing a pre-deployment script
        // that backs up a database.
        if(!$deployStrategy->preDeployment($this)) {
            $this->out("DeploymentStrategy::preDeployment() told me to abort. Aborting deployment.");
            exit;
        }
        

        // Deal with locally modified files.
        $this->out("Preparing to deal with locally modified files if there are any.");
        $LMFStrategy = $this->getLocallyModifiedFileStrategy();
        if(!$LMFStrategy->preDeploy($this)) {
            $this->out("LocallyModifieldFileStrategy::preDeploy() told me to abort. Aborting deployment.");
            exit;
        }

        // Bring the repository up to date with origin.
        $this->out("Bringing the work area up to date with origin.");
        echo $this->fetchOrigin();

        // Set up tag strategy
        $this->out("Preparing tag strategy.");
        $tagStrategy = $this->getTagStrategy();
        // Get the version number that we want to tag.
        $version = $tagStrategy->getVersion($this);

        // Now deploy the version to the work area
        $reftype = $this->isBranch($this->ref) ? "branch":"tag";
        $this->out("Deploying the $reftype you pushed ({$this->baseref}) to work area {$this->config['target']}.");
        if(!$deployStrategy->deploy($this,$version)) {
            $this->out("DeploymentStrategy::deploy() told me to abort. Aborting deployment.");
            exit;
        }

        // Handle any LM files that might have been stashed earlier.
        if(!$LMFStrategy->preTag($this)) {
            $this->out("LocallyModifiedFileStrategy::preTag() told me to abort. Aborting deployment.");
            exit;
        }

        // Now tag that deployed branch or tag, push to remotes, and check it out
        if(!$tagStrategy->tag($this,$version)) {
            $this->out("TagStrategy::tag() told me to abort. Aborting deployment.");
            exit;
        }

        // Now do post-deployment processing, which
        // might include executing a deployment script
        // and merging the deployed tag into another
        // branch.
        if(!$deployStrategy->postDeployment($this,$version)) {
            $this->out("DeploymentStrategy::postDeployment() told me to abort. Aborting deployment.");
            exit;
        }

        $this->out("DEPLOYMENT SUCCESSFUL!!");

    }

}

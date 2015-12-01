<?php

namespace Fh\Git\Deployment\Strategies;

use Fh\Git\Deployment\Deploy;
use Naneau\SemVer\Version;
use Fh\Git\Deployment\Strategies\Interfaces\DeploymentStrategyInterface;

class DefaultDeploymentStrategy implements DeploymentStrategyInterface {

    /**
     * Returns the full file path of the deployment
     * script configured.
     * @return string full path to deployment script
     */
    public function getPreDeploymentScriptPath(Deploy $deploy) {
        $deployScript = $deploy->config['pre_deployment_script'];
        if(!$deployScript) return '';
        $target = $deploy->config['target'];
        return $target.'/'.$deployScript;
    }

    /**
     * Performs any action that should be performed
     * before any deployment work begins. For example,
     * executing a pre-deployment script that backs
     * up your database.
     * @param  Deploy  $deploy
     * @return boolean true if successful, false otherwise
     */
    public function preDeployment(Deploy $deploy)
    {
        // Run deployment scripts
        $deploy->out("Running pre-deployment script in work area.");
        $target = $deploy->config['target'];
        $fullPath = $this->getPreDeploymentScriptPath($deploy);
        $deployScript = $deploy->config['pre_deployment_script'];
        if($fullPath && $this->fileExists($deploy, $fullPath)) {
            $deploy->out("Running pre-deployment script.");
            $deploy->out($deploy->command("cd $target && sudo " . $deployScript));
        } else {
            $deploy->out("No pre-deployment script found in work area. Skipping pre-deployment.");
        }
        return true;
    }

    /**
     * Performs the deployment itself. This usually means
     * simply checking out the pushed ref in the target
     * work area and nothing else.
     * @param  Deploy  $deploy  [description]
     * @param  Version $version [description]
     * @return boolean continue deployment or not
     */
    public function deploy(Deploy $deploy, Version $version) {
        $remote = $deploy->config['deployment_remote_name'];
        $deploy->out($deploy->git("fetch $remote"));
        if($deploy->isBranch($deploy->ref)) {
            $deploy->out("Deleting local copy of your branch just to avoid any potential merge conflicts.");
            $deploy->out($deploy->git("branch -D {$deploy->baseref}"));
            $ref = "$remote/{$deploy->baseref}";
        } else {
            $ref = $deploy->baseref;
        }
        $deploy->out("Checking out $ref");
        $deploy->out($deploy->git("checkout $ref"));
        if($deploy->isBranch($deploy->ref)) {
            $deploy->out($deploy->git("checkout -b {$deploy->baseref}"));
            $deploy->out("Pushing your branch to origin just in case you forgot to do that first.");
            $deploy->out($deploy->git("push origin {$deploy->baseref}"));
        }
        return true;
    }

    /**
     * Runs any post-processing logic that should be
     * run after the deployment is done.
     * For example, running a deployment script.
     * @param  Deploy  $deploy
     * @param  Version $version
     * @return boolean true if successful, false otherwise.
     */
    public function postDeployment(Deploy $deploy, Version $version) {
        // Run deployment scripts
        $deploy->out("Running post-deployment script in work area.");
        $target = $deploy->config['target'];
        $fullPath = $this->getPostDeploymentScriptPath($deploy);
        $deployScript = $deploy->config['post_deployment_script'];
        if($fullPath && $this->fileExists($deploy, $fullPath)) {
            $deploy->out("Running post-deployment script.");
            $deploy->out($deploy->command("cd $target && sudo " . $deployScript));
        } else {
            $deploy->out("No post-deployment script found in work area. Skipping post-deployment.");
        }
        return true;
    }

    /**
     * Returns the full file path of the deployment
     * script configured.
     * @return string full path to deployment script
     */
    public function getPostDeploymentScriptPath(Deploy $deploy) {
        $deployScript = $deploy->config['post_deployment_script'];
        if(!$deployScript) return '';
        $target = $deploy->config['target'];
        return $target.'/'.$deployScript;
    }

    /**
     * Returns true if the file exists and is a file.
     * False otherwise.
     * @param  Deploy $deploy
     * @param  string full path to file
     * @return boolean
     */
    public function fileExists(Deploy $deploy, $strPath) {
        $out = $deploy->command("[ -f $strPath ] && ls $strPath");
        return ($out) ? TRUE:FALSE;
    }
}

<?php

namespace Fh\Git\Deployment\Strategies;

use Fh\Git\Deployment\Deploy;
use Naneau\SemVer\Version;
use Fh\Git\Deployment\Strategies\Interfaces\DeploymentStrategyInterface;

class DefaultDeploymentStrategy implements DeploymentStrategyInterface {

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
        $deploy->out($deploy->git("fetch beta"));
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
        $fullPath = $this->getDeploymentScriptPath($deploy);
        $deployScript = $deploy->config['post_deployment_script'];
        if($fullPath && $this->fileExists($deploy, $fullPath)) {
            $deploy->out($deploy->command("cd $target"));
            $pwd = $deploy->command('pwd');
            $deploy->out("Current working directory is: $pwd");
            $deploy->out("Running deployment script.");
            $deploy->out($deploy->command($deployScript));
        }
        return true;
    }

    /**
     * Returns the full file path of the deployment
     * script configured.
     * @return string full path to deployment script
     */
    public function getDeploymentScriptPath(Deploy $deploy) {
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

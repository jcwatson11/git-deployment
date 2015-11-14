<?php

namespace Fh\Git\Deployment\Strategies;

use Fh\Git\Deployment\Deploy;
use Fh\Git\Deployment\Strategies\Interfaces\LocallyModifiedFileStrategyInterface;

/**
 * In alpha, all locally modified files can safely be deleted
 * because alpha is not considered a system of risk.
 */
class ResetLocallyModifiedFileStrategy implements LocallyModifiedFileStrategyInterface {

    /**
     * Performs any action that would be required
     * before deployment for locally modified files.
     * Note that this happens before the pushed
     * branch is deployed to the work area.
     * One common action would be to do a
     * git reset --hard
     * to rid the work area of locally modified files
     * before the deployment begins.
     * Another common strategy would be to stash
     * LM files now only to unstash and merge them
     * into the branch during the deployment.
     * This function should return true if the deployment
     * can continue, or false if the deployment should abort
     * because of locally modified files.
     * @param  Deploy $deploy
     * @return boolean true to continue with deployment, false otherwise
     */
    public function preDeploy(Deploy $deploy) {
        // Get a list of locally modified files from the work area
        $lmlist = $deploy->git("ls-files -m");

        // Place holder so you know how to get untracked files.
        // $target = $deploy->config['target'];
        // $untrlist = $deploy->command("git $gitsetup ls-files -o --exclude-standard $target");

        if($lmlist) {
            $deploy->out("Found locally modified files. Running git reset --hard");
            $result = $deploy->git("reset --hard");
            $deploy->out($result);
        }

        return true;
    }

    /**
     * Performs any action that would be required
     * to address locally modified files immediately
     * before tagging the pushed release.
     * A common task here would be to do a
     * git stash pop
     * to merge back any files you stashed during
     * preDeploy() above, and merge them into the
     * deployed branch.
     * @param  Deploy $deploy
     * @return boolean true to continue deployment, false otherwise
     */
    public function preTag(Deploy $deploy) {
        return true;
    }

}

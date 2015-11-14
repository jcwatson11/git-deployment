<?php

namespace Fh\Git\Deployment\Strategies\Interfaces;

use Fh\Git\Deployment\Deploy;

interface LocallyModifiedFileStrategyInterface {

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
    public function preDeploy(Deploy $deploy);

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
    public function preTag(Deploy $deploy);
}

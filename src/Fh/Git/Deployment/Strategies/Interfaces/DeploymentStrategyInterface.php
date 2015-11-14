<?php

namespace Fh\Git\Deployment\Strategies\Interfaces;

use Naneau\SemVer\Version;
use Fh\Git\Deployment\Deploy;

interface DeploymentStrategyInterface {

    /**
     * Performs the deployment into the work area.
     * Most of the time, this just means that
     * git checkout branchname is performed.
     *
     * Other actions like:
     *
     * - applying a release tag (TagStrategyInterface::tag())
     * - merging into another branch post release (DeploymentStrategyInterface::postDeployment())
     * - handling locally modified files that need
     *   to be popped and merged (LocallyModifiedFileStrategy::preTag())
     *
     * ... all happen in the strategies named in parentheses.
     * @param  Deploy $deploy
     * @param  Version $version
     * @return void
     */
    public function deploy(Deploy $deploy, Version $version);

    /**
     * Performs any action that should be performed
     * after deployment. For example, merging a
     * production release tag into a production branch
     * and pushing all branches and tags to origin.
     * @param  Deploy  $deploy
     * @param  Version $version
     * @return boolean true if successful, false otherwise
     */
    public function postDeployment(Deploy $deploy, Version $version);
}

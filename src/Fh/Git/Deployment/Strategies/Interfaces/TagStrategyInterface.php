<?php

namespace Fh\Git\Deployment\Strategies\Interfaces;

use Fh\Git\Deployment\Deploy;
use Naneau\SemVer\Version;

/**
 * Defines the strategy for tagging releases.
 */
interface TagStrategyInterface {

    /**
     * Performs the actual tagging of the release after
     * it has been deployed.
     * @param  Deploy $deploy
     * @param  Version $version
     * @return void tag name to proceed with
     */
    public function tag(Deploy $deploy, Version $version);

    /**
     * Returns an instance of the Version class
     * set with the proper attributes for the tag strategy.
     * If the version number is not to be bumped, then
     * it should evaluate to the same as the ref that was pushed.
     * @param  Deploy $deploy
     * @return Version
     */
    public function getVersion(Deploy $deploy);
}

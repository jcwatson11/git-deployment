<?php

namespace Fh\Git\Deployment\Strategies;

use Fh\Git\Deployment\Deploy;
use Fh\Git\Deployment\Strategies\Interfaces\TagStrategyInterface;
use Naneau\SemVer\Parser;
use Naneau\SemVer\Version;

/**
 * This strategy implements an automatic tagging strategy
 * that covers most FH projects. The optional 'v' prefix
 * is supported (like v3.0 as opposed to just 3.0).
 */
class AutoIncrementingTagStrategy implements TagStrategyInterface {

    /**
     * Returns a bumped version number for the given branch
     * according to your bumping strategy. This method
     * should check for the latest tag in the sequence
     * and return a version that is incremented from there.
     * @param  Deploy  $deploy
     * @return Version
     */
    public function getVersion(Deploy $deploy) {
        if($deploy->isTag($deploy->ref)) {
            $deploy->out("You have pushed a tag. Only branches can be auto-tagged. Pushing a tag will simply deploy that tag to the work area.\nNo new tag will be created.");
            try {
                $version = Parser::parse($deploy->baseref);
            } catch (\Exception $e) {
                $deploy->out("Could not parse version number: " . $e->getMessage() . " in file " . $e->getFile() . " on line " . $e->getLine());
                exit;
            }
            return $version;
        }
        if(preg_match('/^[0-9]+\.[0-9]+$/',$deploy->baseref) > 0) {
            $latestTag = $this->getLatestTag($deploy);
            $version = Parser::parse($latestTag);
            $version = $version->next();
            $deploy->out("Next tag to be used: $version");
            return $version;
        } else {
            $deploy->out("WARNING: Branch name does not match expected auto-tag format.");
            $deploy->out("CANNOT CONTINUE AUTO-DEPLOYMENT!");
            exit;
        }
    }

    /**
     * Uses the baseref property of $deploy to check the work area
     * to see what the latest tag is.
     * @param  Deploy $deploy
     * @return string latest tag name fitting the current release spec
     */
    public function getLatestTag(Deploy $deploy) {
        $remote = $deploy->config['deployment_remote_name'];
        $preRelease = ($remote != 'production') ? "-{$remote}\.[0-9]+":'';
        $latestTag = $deploy->git("tag | grep -E -i \"^{$deploy->baseref}\.[0-9]\.[0-9]+$preRelease$\" | sort -V | tail -1");
        if(!$latestTag) {
            $preReleaseSuffix = ($remote != 'production') ? "-$remote.0":'';
            $latestTag = $deploy->baseref . ".0" . $preReleaseSuffix;
            $deploy->out("Previous tag not found. Creating new tag $latestTag");
        } else {
            $deploy->out("Previous latest tag was $latestTag");
        }
        return $latestTag;
    }

    /**
     * Tags the currently deployed release inside
     * the work area and pushes it to all remotes.
     * @param  Deploy $deploy
     * @return string tag name to proceed with
     */
    public function tag(Deploy $deploy, Version $version) {
        if(!$deploy->isTag($deploy->ref)) {
            $deploy->out("Tagging work area with tag: $version");
            $deploy->out($deploy->git("tag $version"));
            $deploy->out($deploy->git("push origin $version"));
        } else {
            $deploy->out("No new tag will be created because you are pushing a tag.");
        }
        return true;
    }

}

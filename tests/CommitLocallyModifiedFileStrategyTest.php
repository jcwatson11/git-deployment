<?php

use Mockery as m;
use Fh\Git\Deployment\Deploy;

class CommitLocallyModifiedFileStrategyTest extends PHPUnit_Framework_TestCase {

    public $config = [];

    public function setUp() {
        $this->config = require "config.php";
    }

    private function newDeploy() {
        $this->config['testing'] = true;
        $this->config['locally_modified_file_strategy'] = 'Fh\Git\Deployment\Strategies\CommitLocallyModifiedFileStrategy';
        return new Deploy($this->config,"hash1","hash2","refs/heads/3.0");
    }

    public function test_it_stashes_files_on_pre_deploy_when_pushing_a_branch() {
        $d = $this->newDeploy();
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. ls-files -m')
          ->andReturn("composer.json\ncomposer.lock");
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. add .')
          ->andReturn("\n");
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. stash')
          ->andReturn("\n");

        ob_start();
        $s = $d->getLocallyModifiedFileStrategy();
        $response = $s->preDeploy($d);
        $out = ob_get_contents();
        ob_end_clean();

        $expected = "Following strategy: Fh\Git\Deployment\Strategies\CommitLocallyModifiedFileStrategy\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. ls-files -m\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. add .\n";
        $expected .= "\n";
        $expected .= "\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. stash\n";
        $expected .= "\n";
        $expected .= "\n";
        $this->assertEquals($expected, $out);
        $this->assertEquals(TRUE, $response);
    }

    public function test_it_fails_when_pushing_a_tag_to_a_work_area_with_locally_modified_files() {
        $this->config['testing'] = true;
        $this->config['locally_modified_file_strategy'] = 'Fh\Git\Deployment\Strategies\CommitLocallyModifiedFileStrategy';
        $d = new Deploy($this->config,"hash1","hash2","refs/tags/3.0.0");
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. ls-files -m')
          ->andReturn("composer.json\ncomposer.lock");
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. add .')
          ->andReturn("\n");
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. stash')
          ->andReturn("\n");

        ob_start();
        $s = $d->getLocallyModifiedFileStrategy();
        $response = $s->preDeploy($d);
        $out = ob_get_contents();
        ob_end_clean();

        $expected = "Following strategy: Fh\Git\Deployment\Strategies\CommitLocallyModifiedFileStrategy\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. ls-files -m\n";
        $expected .= "Found locally modified files in work area. But I'm unsure which branch to commit them to because you're pushing a tag.\n";
        $this->assertEquals($expected, $out);
        $this->assertEquals(FALSE, $response);

        // Even though it's unlikely to ever be encountered,
        // we should make sure that the preTag() method also
        // returns false for the same scenario.
        ob_start();
        $response = $s->preTag($d);
        $out = ob_get_contents();
        ob_end_clean();
        $expected = "You should never see this message because pushing a tag to a work are with locally modified files should halt the deployment from preDeploy().\n";
        $this->assertEquals($expected, $out);
        $this->assertEquals(FALSE, $response);
    }

    public function test_it_stash_pops_and_commits_locally_modified_files_during_preTag() {
        $d = $this->newDeploy();
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. stash pop')
          ->andReturn("\n");
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. add .')
          ->andReturn("\n");
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. commit -m"Committing locally modified files from beta."')
          ->andReturn("\n");

        ob_start();
        $s = $d->getLocallyModifiedFileStrategy();
        $response = $s->preTag($d);
        $out = ob_get_contents();
        ob_end_clean();

        $expected = "Following strategy: Fh\Git\Deployment\Strategies\CommitLocallyModifiedFileStrategy\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. stash pop\n";
        $expected .= "\n";
        $expected .= "\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. add .\n";
        $expected .= "\n";
        $expected .= "\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. commit -m\"Committing locally modified files from beta.\"\n";
        $expected .= "\n";
        $expected .= "\n";
        $this->assertEquals($expected, $out);
        $this->assertEquals(TRUE, $response);
    }

}

<?php

use Mockery as m;
use Fh\Git\Deployment\Deploy;

class DefaultDeploymentStrategyTest extends PHPUnit_Framework_TestCase {

    public $config = [];

    public function setUp() {
        $this->config = require "config.php";
    }

    private function newDeploy() {
        $this->config['testing'] = true;
        return new Deploy($this->config,"hash1","hash2","refs/heads/3.0");
    }

    public function test_it_deploys_a_branch_properly() {
        $d = $this->newDeploy();

        $d->expectCommand("git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. tag | grep -E -i \"^3.0\.[0-9]+-beta\.[0-9]+\$\" | sort -V | tail -1")
          ->andReturn("3.0.0-beta.42");
        $d->expectCommand("git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. branch -D 3.0")
          ->andReturn("\n");
        $d->expectCommand("git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. fetch beta")
          ->andReturn("\n");
        $d->expectCommand("git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. checkout beta/3.0")
          ->andReturn("\n");
        $d->expectCommand("git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. checkout -b 3.0")
          ->andReturn("\n");
        $d->expectCommand("git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. push origin 3.0")
          ->andReturn("\n");

        ob_start();
        $s = $d->getTagStrategy();
        $version = $s->getVersion($d);
        $ds = $d->getDeploymentStrategy();
        $result = $ds->deploy($d, $version);
        $out = ob_get_contents();
        ob_end_clean();

        $expected = "Following strategy: Fh\Git\Deployment\Strategies\AutoIncrementingTagStrategy\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. tag | grep -E -i \"^3.0\.[0-9]+-beta\.[0-9]+\$\" | sort -V | tail -1\n";
        $expected .= "Previous latest tag was 3.0.0-beta.42\n";
        $expected .= "Next tag to be used: 3.0.0-beta.43\n";
        $expected .= "Following strategy: Fh\Git\Deployment\Strategies\DefaultDeploymentStrategy\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. fetch beta\n";
        $expected .= "\n";
        $expected .= "\n";
        $expected .= "Deleting local copy of your branch just to avoid any potential merge conflicts.\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. branch -D 3.0\n";
        $expected .= "\n";
        $expected .= "\n";
        $expected .= "Checking out beta/3.0\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. checkout beta/3.0\n";
        $expected .= "\n";
        $expected .= "\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. checkout -b 3.0\n";
        $expected .= "\n";
        $expected .= "\n";
        $expected .= "Pushing your branch to origin just in case you forgot to do that first.\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. push origin 3.0\n";
        $expected .= "\n";
        $expected .= "\n";
        $this->assertEquals($expected,$out);
    }

    public function test_it_deploys_a_tag_properly() {
        $this->config['testing'] = true;
        $d = new Deploy($this->config,"hash1","hash2","refs/tags/3.0.25");

        $d->expectCommand("git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. checkout 3.0.25")
          ->andReturn("\n");
        $d->expectCommand("git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. fetch beta")
          ->andReturn("\n");

        ob_start();
        $s = $d->getTagStrategy();
        $version = $s->getVersion($d);
        $ds = $d->getDeploymentStrategy();
        $result = $ds->deploy($d, $version);
        $out = ob_get_contents();
        ob_end_clean();

        $expected = "Following strategy: Fh\Git\Deployment\Strategies\AutoIncrementingTagStrategy\n";
        $expected .= "You have pushed a tag. Only branches can be auto-tagged. Pushing a tag will simply deploy that tag to the work area.\n";
        $expected .= "No new tag will be created.\n";
        $expected .= "Following strategy: Fh\Git\Deployment\Strategies\DefaultDeploymentStrategy\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. fetch beta\n";
        $expected .= "\n";
        $expected .= "\n";
        $expected .= "Checking out 3.0.25\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. checkout 3.0.25\n";
        $expected .= "\n";
        $expected .= "\n";
        $this->assertEquals($expected,$out);
    }

    public function test_it_returns_the_pre_deployment_script_path_and_knows_when_it_exists() {
        $d = $this->newDeploy();

        $d->expectCommand('[ -f /var/www/test.fh.org/./pre-deploy.sh ] && ls /var/www/test.fh.org/./pre-deploy.sh')
          ->andReturn('pre-deploy.sh');
        $d->expectCommand('[ -f /var/www/test.fh.org/./pre-deploy.sh ] && ls /var/www/test.fh.org/./pre-deploy.sh')
          ->andReturn('');

        ob_start();
        $ds = $d->getDeploymentStrategy();
        $path = $ds->getPreDeploymentScriptPath($d);
        $result = $ds->fileExists($d,$path);
        $second_result = $ds->fileExists($d,$path);
        $out = ob_get_contents();
        ob_end_clean();

        $this->assertEquals("/var/www/test.fh.org/./pre-deploy.sh",$path);
        $this->assertEquals(TRUE,$result);
        $this->assertEquals(FALSE,$second_result);

        $expected = "Following strategy: Fh\Git\Deployment\Strategies\DefaultDeploymentStrategy\n";
        $expected .= "command: [ -f /var/www/test.fh.org/./pre-deploy.sh ] && ls /var/www/test.fh.org/./pre-deploy.sh\n";
        $expected .= "command: [ -f /var/www/test.fh.org/./pre-deploy.sh ] && ls /var/www/test.fh.org/./pre-deploy.sh\n";
        $this->assertEquals($expected,$out);
    }
    public function test_it_returns_the_post_deployment_script_path_and_knows_when_it_exists() {
        $d = $this->newDeploy();

        $d->expectCommand('[ -f /var/www/test.fh.org/./deploy.sh ] && ls /var/www/test.fh.org/./deploy.sh')
          ->andReturn('deploy.sh');
        $d->expectCommand('[ -f /var/www/test.fh.org/./deploy.sh ] && ls /var/www/test.fh.org/./deploy.sh')
          ->andReturn('');

        ob_start();
        $ds = $d->getDeploymentStrategy();
        $path = $ds->getPostDeploymentScriptPath($d);
        $result = $ds->fileExists($d,$path);
        $second_result = $ds->fileExists($d,$path);
        $out = ob_get_contents();
        ob_end_clean();

        $this->assertEquals("/var/www/test.fh.org/./deploy.sh",$path);
        $this->assertEquals(TRUE,$result);
        $this->assertEquals(FALSE,$second_result);

        $expected = "Following strategy: Fh\Git\Deployment\Strategies\DefaultDeploymentStrategy\n";
        $expected .= "command: [ -f /var/www/test.fh.org/./deploy.sh ] && ls /var/www/test.fh.org/./deploy.sh\n";
        $expected .= "command: [ -f /var/www/test.fh.org/./deploy.sh ] && ls /var/www/test.fh.org/./deploy.sh\n";
        $this->assertEquals($expected,$out);
    }
}

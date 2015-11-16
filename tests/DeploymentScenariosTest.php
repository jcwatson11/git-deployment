<?php

use Mockery as m;
use Fh\Git\Deployment\Deploy;

class DeploymentScenariosTest extends PHPUnit_Framework_TestCase {

    public $config = [];

    public function setUp() {
        $this->config = require "config.php";
    }

    private function newDeploy() {
        $this->config['testing'] = true;
        return new Deploy($this->config,"hash1","hash2","refs/tags/3.0.0");
    }

    public function test_it_can_deploy_a_tag() {
        $d = $this->newDeploy();
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. fetch origin')
          ->andReturn("\n");
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. fetch beta')
          ->andReturn("\n");
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. fetch origin --tags')
          ->andReturn("\n");
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. ls-files -m')
          ->andReturn("files");
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. reset --hard')
          ->andReturn("");
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. checkout 3.0.0')
          ->andReturn("\n");
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. tag 3.0.0')
          ->andReturn("\n");
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. remote | egrep "^beta$" | tail -1')
          ->andReturn('beta');
        $d->expectCommand('[ -f /var/www/test.fh.org/./deploy.sh ] && ls /var/www/test.fh.org/./deploy.sh')
          ->andReturn('deploy.sh');
        $d->expectCommand('cd /var/www/test.fh.org')
          ->andReturn("\n");
        $d->expectCommand('pwd')
          ->andReturn("/var/www/test.fh.org");
        $d->expectCommand('./deploy.sh')
          ->andReturn("\n");
        ob_start();
        echo $d->main();
        $out = ob_get_contents();
        ob_end_clean();

        $expected = "WARNING: You are in test mode. No deployment will actually take place.\n";
        $expected .= "INPUT oldrev: hash1, newrev: hash2, ref: refs/tags/3.0.0\n";
        $expected .= "Checking to make sure the beta remote exists in the target work area.\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. remote | egrep \"^beta$\" | tail -1\n";
        $expected .= "It exists. So we're ok.\n";
        $expected .= "Preparing to deal with locally modified files if there are any.\n";
        $expected .= "Following strategy: Fh\Git\Deployment\Strategies\ResetLocallyModifiedFileStrategy\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. ls-files -m\n";
        $expected .= "Found locally modified files. Running git reset --hard\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. reset --hard\n";
        $expected .= "\n"; // Empty output from git command
        $expected .= "Bringing the work area up to date with origin.\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. fetch origin\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. fetch origin --tags\n";
        $expected .= "\n"; // Empty output from git command
        $expected .= "\n"; // Empty output from git command
        $expected .= "Preparing tag strategy.\n";
        $expected .= "Following strategy: Fh\Git\Deployment\Strategies\AutoIncrementingTagStrategy\n";
        $expected .= "You have pushed a tag. Only branches can be auto-tagged. Pushing a tag will simply deploy that tag to the work area.\n";
        $expected .= "No new tag will be created.\n";
        $expected .= "Deploying the tag you pushed (3.0.0) to work area /var/www/test.fh.org.\n";
        $expected .= "Following strategy: Fh\Git\Deployment\Strategies\DefaultDeploymentStrategy\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. fetch beta\n";
        $expected .= "\n"; // Empty output from git command
        $expected .= "\n"; // Empty output from git command
        $expected .= "Checking out 3.0.0\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. checkout 3.0.0\n";
        $expected .= "\n"; // Empty output from git command
        $expected .= "\n"; // Empty output from git command
        $expected .= "No new tag will be created because you are pushing a tag.\n";
        $expected .= "Running post-deployment script in work area.\n";
        $expected .= "command: [ -f /var/www/test.fh.org/./deploy.sh ] && ls /var/www/test.fh.org/./deploy.sh\n";
        $expected .= "command: cd /var/www/test.fh.org\n";
        $expected .= "\n";
        $expected .= "\n";
        $expected .= "command: pwd\n";
        $expected .= "Current working directory is: /var/www/test.fh.org\n";
        $expected .= "Running deployment script.\n";
        $expected .= "command: ./deploy.sh\n";
        $expected .= "\n";
        $expected .= "\n";
        $expected .= "DEPLOYMENT SUCCESSFUL!!\n";
        $this->assertEquals($expected,$out);
    }

    public function test_it_can_deploy_a_branch() {
        $this->config['testing'] = true;
        $d = new Deploy($this->config,"hash1","hash2","refs/heads/3.0");
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. fetch origin')
          ->andReturn("\n");
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. fetch beta')
          ->andReturn("\n");
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. fetch origin --tags')
          ->andReturn("\n");
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. ls-files -m')
          ->andReturn("files");
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. reset --hard')
          ->andReturn("");
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. checkout 3.0.0')
          ->andReturn("\n");
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. tag 3.0.0')
          ->andReturn("\n");
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. remote | egrep "^beta$" | tail -1')
          ->andReturn('beta');
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. tag | grep -E -i "^3.0\.[0-9]\.[0-9]+-beta\.[0-9]+$" | sort -V | tail -1')
          ->andReturn('3.0.0-beta.2');
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. branch -D 3.0')
          ->andReturn("\n");
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. checkout beta/3.0')
          ->andReturn("\n");
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. checkout -b 3.0')
          ->andReturn("\n");
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. push origin 3.0')
          ->andReturn("\n");
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. tag 3.0.0-beta.3')
          ->andReturn("\n");
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. push origin 3.0.0-beta.3')
          ->andReturn("\n");
        $d->expectCommand('[ -f /var/www/test.fh.org/./deploy.sh ] && ls /var/www/test.fh.org/./deploy.sh')
          ->andReturn('deploy.sh');
        $d->expectCommand('cd /var/www/test.fh.org')
          ->andReturn("\n");
        $d->expectCommand('pwd')
          ->andReturn("/var/www/test.fh.org");
        $d->expectCommand('./deploy.sh')
          ->andReturn("\n");
        ob_start();
        echo $d->main();
        $out = ob_get_contents();
        ob_end_clean();

        $expected = "WARNING: You are in test mode. No deployment will actually take place.\n";
        $expected .= "INPUT oldrev: hash1, newrev: hash2, ref: refs/heads/3.0\n";
        $expected .= "Checking to make sure the beta remote exists in the target work area.\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. remote | egrep \"^beta$\" | tail -1\n";
        $expected .= "It exists. So we're ok.\n";
        $expected .= "Preparing to deal with locally modified files if there are any.\n";
        $expected .= "Following strategy: Fh\Git\Deployment\Strategies\ResetLocallyModifiedFileStrategy\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. ls-files -m\n";
        $expected .= "Found locally modified files. Running git reset --hard\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. reset --hard\n";
        $expected .= "\n"; // Empty output from git command
        $expected .= "Bringing the work area up to date with origin.\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. fetch origin\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. fetch origin --tags\n";
        $expected .= "\n"; // Empty output from git command
        $expected .= "\n"; // Empty output from git command
        $expected .= "Preparing tag strategy.\n";
        $expected .= "Following strategy: Fh\Git\Deployment\Strategies\AutoIncrementingTagStrategy\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. tag | grep -E -i \"^3.0\.[0-9]\.[0-9]+-beta\.[0-9]+\$\" | sort -V | tail -1\n";
        $expected .= "Previous latest tag was 3.0.0-beta.2\n";
        $expected .= "Next tag to be used: 3.0.0-beta.3\n";
        $expected .= "Deploying the branch you pushed (3.0) to work area /var/www/test.fh.org.\n";
        $expected .= "Following strategy: Fh\Git\Deployment\Strategies\DefaultDeploymentStrategy\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. fetch beta\n";
        $expected .= "\n"; // Empty output from git command
        $expected .= "\n"; // Empty output from git command
        $expected .= "Deleting local copy of your branch just to avoid any potential merge conflicts.\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. branch -D 3.0\n";
        $expected .= "\n"; // Empty output from git command
        $expected .= "\n"; // Empty output from git command
        $expected .= "Checking out beta/3.0\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. checkout beta/3.0\n";
        $expected .= "\n"; // Empty output from git command
        $expected .= "\n"; // Empty output from git command
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. checkout -b 3.0\n";
        $expected .= "\n"; // Empty output from git command
        $expected .= "\n"; // Empty output from git command
        $expected .= "Pushing your branch to origin just in case you forgot to do that first.\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. push origin 3.0\n";
        $expected .= "\n";
        $expected .= "\n";
        $expected .= "Tagging work area with tag: 3.0.0-beta.3\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. tag 3.0.0-beta.3\n";
        $expected .= "\n";
        $expected .= "\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. push origin 3.0.0-beta.3\n";
        $expected .= "\n"; // Empty output from git command
        $expected .= "\n"; // Empty output from git command
        $expected .= "Running post-deployment script in work area.\n";
        $expected .= "command: [ -f /var/www/test.fh.org/./deploy.sh ] && ls /var/www/test.fh.org/./deploy.sh\n";
        $expected .= "command: cd /var/www/test.fh.org\n";
        $expected .= "\n";
        $expected .= "\n";
        $expected .= "command: pwd\n";
        $expected .= "Current working directory is: /var/www/test.fh.org\n";
        $expected .= "Running deployment script.\n";
        $expected .= "command: ./deploy.sh\n";
        $expected .= "\n";
        $expected .= "\n";
        $expected .= "DEPLOYMENT SUCCESSFUL!!\n";
        $this->assertEquals($expected,$out);
    }
}

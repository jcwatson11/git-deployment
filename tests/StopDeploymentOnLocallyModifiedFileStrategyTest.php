<?php

use Mockery as m;
use Fh\Git\Deployment\Deploy;

class StopDeploymentOnLocallyModifiedFileStrategyTest extends PHPUnit_Framework_TestCase {

    public $config = [];

    public function setUp() {
        $this->config = require "config.php";
    }

    private function newDeploy() {
        $this->config['testing'] = true;
        $this->config['locally_modified_file_strategy'] = 'Fh\Git\Deployment\Strategies\StopDeploymentOnLocallyModifiedFileStrategy';
        return new Deploy($this->config,"hash1","hash2","refs/heads/3.0");
    }

    public function test_it_stops_the_deployment_on_pre_deploy_when_pushing_a_branch() {
        $d = $this->newDeploy();
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. ls-files -m')
          ->andReturn("composer.json\ncomposer.lock");

        ob_start();
        $s = $d->getLocallyModifiedFileStrategy();
        $response = $s->preDeploy($d);
        $out = ob_get_contents();
        ob_end_clean();

        $expected = "Following strategy: Fh\Git\Deployment\Strategies\StopDeploymentOnLocallyModifiedFileStrategy\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. ls-files -m\n";
        $expected .= "Found locally modified files in target work area. Cannot continue with deployment.\n";
        $this->assertEquals($expected, $out);
        $this->assertEquals(FALSE, $response);
    }

    public function test_it_fails_when_pushing_a_tag_to_a_work_area_with_locally_modified_files() {
        $this->config['testing'] = true;
        $this->config['locally_modified_file_strategy'] = 'Fh\Git\Deployment\Strategies\StopDeploymentOnLocallyModifiedFileStrategy';
        $d = new Deploy($this->config,"hash1","hash2","refs/tags/3.0.0");
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. ls-files -m')
          ->andReturn("composer.json\ncomposer.lock");

        ob_start();
        $s = $d->getLocallyModifiedFileStrategy();
        $response = $s->preDeploy($d);
        $out = ob_get_contents();
        ob_end_clean();

        $expected = "Following strategy: Fh\Git\Deployment\Strategies\StopDeploymentOnLocallyModifiedFileStrategy\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. ls-files -m\n";
        $expected .= "Found locally modified files in target work area. Cannot continue with deployment.\n";
        $this->assertEquals($expected, $out);
        $this->assertEquals(FALSE, $response);

    }

}

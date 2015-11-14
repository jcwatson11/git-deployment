<?php

use Mockery as m;
use Fh\Git\Deployment\Deploy;

class ResetLocallyModifiedFileStrategyTest extends PHPUnit_Framework_TestCase {

    public $config = [];

    public function setUp() {
        $this->config = require "config.php";
    }

    private function newDeploy() {
        $this->config['testing'] = true;
        $this->config['locally_modified_file_strategy'] = 'Fh\Git\Deployment\Strategies\ResetLocallyModifiedFileStrategy';
        return new Deploy($this->config,"hash1","hash2","refs/heads/3.0");
    }

    public function test_it_resets_hard_when_locally_modified_files_are_found_during_preDeploy() {
        $d = $this->newDeploy();
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree/var/www/test.fh.org/. ls-files -m')
          ->andReturn("composer.json\ncomposer.lock");
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree/var/www/test.fh.org/. reset --hard')
          ->andReturn("\n");

        ob_start();
        $s = $d->getLocallyModifiedFileStrategy();
        $response = $s->preDeploy($d);
        $out = ob_get_contents();
        ob_end_clean();

        $expected = "Following strategy: Fh\Git\Deployment\Strategies\ResetLocallyModifiedFileStrategy\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree/var/www/test.fh.org/. ls-files -m\n";
        $expected .= "Found locally modified files. Running git reset --hard\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree/var/www/test.fh.org/. reset --hard\n";
        $expected .= "\n";
        $expected .= "\n";
        $this->assertEquals($expected, $out);
        $this->assertEquals(TRUE, $response);

        // The preTag() method should always return true
        // for this strategy because there's nothing left to
        // follow up on because we reset the work area
        // during preDeploy()
        $this->assertEquals(TRUE,$s->preTag($d));
    }

}

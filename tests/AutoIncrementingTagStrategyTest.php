<?php

use Mockery as m;
use Fh\Git\Deployment\Deploy;

class AutoIncrementingTagStrategyTest extends PHPUnit_Framework_TestCase {

    public $config = [];

    public function setUp() {
        $this->config = require "config.php";
    }

    private function newDeploy() {
        $this->config['testing'] = true;
        return new Deploy($this->config,"hash1","hash2","refs/heads/3.0");
    }

    public function test_it_can_bump_a_pre_release_version() {
        $d = $this->newDeploy();

        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. tag | grep -E -i "^3.0\.[0-9]\.[0-9]+-beta\.[0-9]+$" | sort -V | tail -1')
          ->andReturn('3.0.0-beta.1');

        ob_start();
        $s = $d->getTagStrategy();
        $version = $s->getVersion($d);
        $out = ob_get_contents();
        ob_end_clean();

        $expected = "Following strategy: Fh\Git\Deployment\Strategies\AutoIncrementingTagStrategy\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. tag | grep -E -i \"^3.0\.[0-9]\.[0-9]+-beta\.[0-9]+\$\" | sort -V | tail -1\n";
        $expected .= "Previous latest tag was 3.0.0-beta.1\n";
        $expected .= "Next tag to be used: 3.0.0-beta.2\n";
        $this->assertEquals('3.0.0-beta.2',$version.'');
        $this->assertEquals($expected,$out);
    }

    public function test_it_can_bump_a_pre_release_version_when_no_latest_tag_is_found() {
        $d = $this->newDeploy();

        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. tag | grep -E -i "^3.0\.[0-9]\.[0-9]+-beta\.[0-9]+$" | sort -V | tail -1')
          ->andReturn('');

        ob_start();
        $s = $d->getTagStrategy();
        $version = $s->getVersion($d);
        $out = ob_get_contents();
        ob_end_clean();

        $expected = "Following strategy: Fh\Git\Deployment\Strategies\AutoIncrementingTagStrategy\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. tag | grep -E -i \"^3.0\.[0-9]\.[0-9]+-beta\.[0-9]+\$\" | sort -V | tail -1\n";
        $expected .= "Previous tag not found. Creating new tag 3.0.0-beta.0\n";
        $expected .= "Next tag to be used: 3.0.0-beta.1\n";
        $this->assertEquals('3.0.0-beta.1',$version.'');
        $this->assertEquals($expected,$out);
    }

    public function test_it_can_bump_a_production_version() {
        $d = $this->newDeploy();
        $d->config['deployment_remote_name'] = 'production';

        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. tag | grep -E -i "^3.0\.[0-9]\.[0-9]+$" | sort -V | tail -1')
          ->andReturn('3.0.0');

        ob_start();
        $s = $d->getTagStrategy();
        $version = $s->getVersion($d);
        $out = ob_get_contents();
        ob_end_clean();

        $expected = "Following strategy: Fh\Git\Deployment\Strategies\AutoIncrementingTagStrategy\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. tag | grep -E -i \"^3.0\.[0-9]\.[0-9]+\$\" | sort -V | tail -1\n";
        $expected .= "Previous latest tag was 3.0.0\n";
        $expected .= "Next tag to be used: 3.0.1\n";
        $this->assertEquals('3.0.1',$version.'');
        $this->assertEquals($expected,$out);
    }

    public function test_it_can_bump_a_production_version_when_no_latest_tag_is_found() {
        $d = $this->newDeploy();
        $d->config['deployment_remote_name'] = 'production';

        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. tag | grep -E -i "^3.0\.[0-9]\.[0-9]+$" | sort -V | tail -1')
          ->andReturn('');

        ob_start();
        $s = $d->getTagStrategy();
        $version = $s->getVersion($d);
        $out = ob_get_contents();
        ob_end_clean();

        $expected = "Following strategy: Fh\Git\Deployment\Strategies\AutoIncrementingTagStrategy\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. tag | grep -E -i \"^3.0\.[0-9]\.[0-9]+\$\" | sort -V | tail -1\n";
        $expected .= "Previous tag not found. Creating new tag 3.0.0\n";
        $expected .= "Next tag to be used: 3.0.1\n";
        $this->assertEquals('3.0.1',$version.'');
        $this->assertEquals($expected,$out);
    }

    public function test_it_can_tag_a_deployed_release() {
        $d = $this->newDeploy();
        $d->config['deployment_remote_name'] = 'production';

        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. tag | grep -E -i "^3.0\.[0-9]\.[0-9]+$" | sort -V | tail -1')
          ->andReturn('3.0.0');
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. tag 3.0.1')
          ->andReturn("\n");
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. push origin 3.0.1')
          ->andReturn("\n");
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. checkout 3.0.1')
          ->andReturn("\n");

        ob_start();
        $s = $d->getTagStrategy();
        $version = $s->getVersion($d);
        $s->tag($d,$version);
        $out = ob_get_contents();
        ob_end_clean();

        $expected = "Following strategy: Fh\Git\Deployment\Strategies\AutoIncrementingTagStrategy\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. tag | grep -E -i \"^3.0\.[0-9]\.[0-9]+\$\" | sort -V | tail -1\n";
        $expected .= "Previous latest tag was 3.0.0\n";
        $expected .= "Next tag to be used: 3.0.1\n";
        $expected .= "Tagging work area with tag: 3.0.1\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. tag 3.0.1\n";
        $expected .= "\n";
        $expected .= "\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. push origin 3.0.1\n";
        $expected .= "\n";
        $expected .= "\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree /var/www/test.fh.org/. checkout 3.0.1\n";
        $expected .= "\n";
        $expected .= "\n";
        $this->assertEquals('3.0.1',$version.'');
        $this->assertEquals($expected,$out);
    }

}

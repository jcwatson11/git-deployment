<?php

use Mockery as m;
use Fh\Git\Deployment\Deploy;

class DeployTest extends PHPUnit_Framework_TestCase {

    public $config = [];

    public function setUp() {
        $this->config = require "config.php";
    }

    private function newDeploy() {
        $this->config['testing'] = true;
        return new Deploy($this->config,"hash1","hash2","refs/tags/3.0");
    }

    public function test_it_echoes_a_message() {
        $d = $this->newDeploy();
        ob_start();
        $stringToOutput = "Testing messages.";
        $d->out($stringToOutput);
        $echoedString = ob_get_contents();
        ob_end_clean();
        $this->assertEquals($stringToOutput."\n",$echoedString,"Output is not working as expected.");
    }

    public function test_it_intializes_its_variables_properly() {
        $d = $this->newDeploy();

        $this->assertEquals($d->config,$this->config);
        $this->assertEquals($d->oldrev,'hash1');
        $this->assertEquals($d->newrev,'hash2');
        $this->assertEquals($d->ref,'refs/tags/3.0');
        $this->assertEquals($d->baseref,'3.0');
    }

    public function test_it_knows_when_the_process_is_deleting_something() {
        $deleteHash = "0000000000000000000000000000000000000000";
        $d = $this->newDeploy();
        $this->assertEquals($d->isDeleting($deleteHash),TRUE);
    }

    public function test_it_runs_a_command() {
        $d = $this->newDeploy();
        $d->expectCommand("echo test")
          ->andReturn("test\n");
        ob_start();
        echo $d->command("echo test");
        $out = ob_get_contents();
        ob_end_clean();
        $this->assertEquals("command: echo test\ntest\n",$out);
    }

    public function test_it_can_run_several_commands() {
        $d = $this->newDeploy();
        $d->expectCommand("echo test")
          ->andReturn("test\n");
        $d->expectCommand("ls")
          ->andReturn("composer.json\n");
        ob_start();
        echo $d->run(["echo test","ls"]);
        $out = ob_get_contents();
        ob_end_clean();
        $expected = "command: echo test\ncommand: ls\ntest\ncomposer.json\n";
        $this->assertEquals($expected,$out);
    }

    public function test_it_can_fetch_origin() {
        $d = $this->newDeploy();
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree/var/www/test.fh.org/. fetch origin')
          ->andReturn("\n");
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree/var/www/test.fh.org/. fetch origin --tags')
          ->andReturn("\n");
        ob_start();
        echo $d->fetchOrigin();
        $out = ob_get_contents();
        ob_end_clean();
        $expected = "command: git --git-dir /var/www/test.fh.org/.git --work-tree/var/www/test.fh.org/. fetch origin\n";
        $expected .= "command: git --git-dir /var/www/test.fh.org/.git --work-tree/var/www/test.fh.org/. fetch origin --tags\n";
        $expected .= "\n\n";
        $this->assertEquals($expected,$out);
    }

    public function test_it_can_tell_when_a_tag_is_a_branch() {
        $d = $this->newDeploy();
        $this->assertEquals($d->isBranch('refs/tags/v3.0.1'),FALSE);
        $this->assertEquals($d->isBranch('refs/heads/v3.0'),TRUE);
    }

    public function test_it_can_tell_when_a_ref_is_a_tag() {
        $d = $this->newDeploy();
        $this->assertEquals($d->isTag('refs/tags/v3.0.1'),TRUE);
        $this->assertEquals($d->isTag('refs/heads/v3.0'),FALSE);
    }

    public function test_it_can_tell_when_a_remote_exists() {
        $d = $this->newDeploy();
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree/var/www/test.fh.org/. remote | egrep "^beta$" | tail -1')
          ->andReturn('beta');
        $d->expectCommand('git --git-dir /var/www/test.fh.org/.git --work-tree/var/www/test.fh.org/. remote | egrep "^stage$" | tail -1')
          ->andReturn('');

        $expected = true;
        ob_start();
        $actual = $d->remoteExists('beta');
        $out = ob_get_contents();
        ob_end_clean();
        $this->assertEquals($expected, $actual);
        $expected = "command: git --git-dir /var/www/test.fh.org/.git --work-tree/var/www/test.fh.org/. remote | egrep \"^beta$\" | tail -1\n";
        $this->assertEquals($expected, $out);

        $expected = false;
        ob_start();
        $actual = $d->remoteExists('stage');
        $out = ob_get_contents();
        ob_end_clean();
        $this->assertEquals($expected, $actual);
        $expected = "command: git --git-dir /var/www/test.fh.org/.git --work-tree/var/www/test.fh.org/. remote | egrep \"^stage$\" | tail -1\n";
        $this->assertEquals($expected, $out);
    }

    public function test_it_removes_processed_command_expectations_after_running_them() {
        $d = $this->newDeploy();

        $d->expectCommand('foo')
          ->andReturn('a');
        $d->expectCommand('foo')
          ->andReturn('b');

        $out = $d->processCommandExpectation('foo');
        $this->assertEquals('a',$out);
        $out = $d->processCommandExpectation('foo');
        $this->assertEquals('b',$out);
    }

}

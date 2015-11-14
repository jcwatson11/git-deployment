<?php

namespace Fh\Git\Deployment;

/**
 * Simple unit testable command runner for PHP
 */
class CommandRunner {

    /**
     * Array of command expectations for use when testing.
     * @var array
     */
    public $commandExpectations = [];

    /**
     * Echo a message to the console.
     * @param  string $msg message
     * @return void
     */
    public function out($msg = '') {
        echo "$msg\n";
    }

    /**
     * Run an array of commands and return the output.
     * @param  array  $commands array of string commands to run
     * @return void
     */
    public function run($commands = []) {
        $ret = '';
        foreach($commands AS $command) {
            $ret .= $this->command($command);
        }
        return $ret;
    }

    /**
     * Run a single command and return its output as
     * a single string
     * @param  string $command
     * @return string output from command
     */
    public function command($command) {
        $this->out("command: $command");
        if($this->config['testing'] == true) {
            return $this->processCommandExpectation($command);
        } else {
            return `$command`;
        }
    }

    /**
     * For use with PHPUnit
     * Sets an expectation for a certain command so
     * you can mock return values.
     * @param  string $command
     * @return $this
     */
    public function expectCommand($command) {
        $this->commandExpectations[] = [
            'command' => $command
        ];
        return $this;
    }

    /**
     * Sets up the return value for the immediately previously
     * defined expected command
     * @param  string $value to return when the command is called
     * @return $this
     */
    public function andReturn($value) {
        $last = count($this->commandExpectations) - 1;
        $this->commandExpectations[$last]['return'] = $value;
        return $this;
    }

    /**
     * Processes through the list of expected commands
     * and returns the expected value if found.
     * Otherwise, an exception is thrown
     * Commands are encountered in order, and duplicate
     * commands with different output are supported.
     * @param  string $command
     * @return string return value
     */
    public function processCommandExpectation($command) {
        foreach($this->commandExpectations AS $index => $expectation) {
            if($expectation['command'] == $command) {
                $ret = $expectation['return'];
                unset($this->commandExpectations[$index]);
                return $ret;
            }
        }
        throw new \Exception("Unexpected command encountered:\n\n$command\n\nSet up an expectation for this to continue with testing.");
    }

}
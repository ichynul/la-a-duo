<?php

namespace Ichynul\LaADuo\Console;

use Symfony\Component\Console\Output\Output;

class StringOutput extends Output
{
    public $lines = [];

    public $line = '';

    public function clear()
    {
        $this->lines = [];
        $this->line = '';
    }

    protected function doWrite($message, $newline)
    {
        $this->line .= $message;

        if ($newline) {
            $this->lines[] = $this->line;
            $this->line = '';
        }
    }

    public function getContent()
    {
        if ($this->line) {
            $this->lines[] = $this->line;
            $this->line = '';
        }
        return trim(explode(PHP_EOL, $this->lines));
    }

    public function getLines()
    {
        return $this->lines;
    }
}

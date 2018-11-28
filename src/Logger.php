<?php
/**
 * Copyright © Vaimo Group. All rights reserved.
 * See LICENSE_VAIMO.txt for license details.
 */
namespace Vaimo\ComposerPatches;

class Logger
{
    const TYPE_NONE = 'none';
    
    /**
     * @var \Composer\IO\IOInterface $io
     */
    private $io;
    
    /**
     * @var array
     */
    private $indentationStack = array();
    
    /**
     * @var int 
     */
    private $muteDepth = 0;
    
    /**
     * @param \Composer\IO\IOInterface $io
     */
    public function __construct(
        \Composer\IO\IOInterface $io
    ) {
        $this->io = $io;
    }

    /**
     * @return \Composer\IO\IOInterface|\Composer\IO\ConsoleIO
     */
    public function getOutputInstance()
    {
        return $this->io;
    }

    public function isMuted()
    {
        return (bool)$this->muteDepth;
    }
    
    public function mute()
    {
        return $this->muteDepth++;
    }

    public function unMute($muteDepth = null)
    {
        $this->muteDepth = $muteDepth 
            ? $muteDepth 
            : max(--$this->muteDepth, 0);
    }
    
    public function writeRaw($message, array $args = array())
    {
        if ($this->muteDepth) {
            return;
        }
        
        $prefix = $this->getIndentationString();

        $lines = array_map(function ($line) use ($prefix) {
            return $prefix . $line;
        }, explode(PHP_EOL, $message));
        
        $this->io->write(
            vsprintf(implode(PHP_EOL, $lines), $args)
        );
    }
    
    public function write($type, $message, array $args = array())
    {
        $this->writeRaw($this->createTag($type, $message), $args);
    }

    public function writeVerbose($type, $message, array $args = array())
    {
        if (!$this->io->isVerbose()) {
            return;
        }
        
        $this->write($type, $message, $args);
    }
    
    public function writeException(\Exception $exception)
    {
        if (!$this->io->isVerbose()) {
            return;
        }
        
        $this->write('error', trim($exception->getMessage(), PHP_EOL . ' '));
        $this->write('', trim($exception->getTraceAsString(), PHP_EOL . ' '));
    }

    public function writeNewLine()
    {
        if ($this->muteDepth) {
            return;
        }
        
        $this->io->write('');
    }
    
    private function createTag($type, $contents)
    {
        if (!$type || $type == \Vaimo\ComposerPatches\Logger::TYPE_NONE) {
            return $contents;
        }
        
        return '<' . $type . '>' . $contents . '</' . $type . '>';
    }
    
    private function getIndentationString()
    {
        return str_pad('', count($this->indentationStack) * 2, ' ') . end($this->indentationStack);
    }

    public function writeIndentation()
    {
        if ($this->muteDepth) {
            return;
        }
        
        $this->io->write(
            $this->getIndentationString(), 
            false
        );
    }
    
    public function reset($index = 0)
    {
        $this->indentationStack = array_slice($this->indentationStack, 0, $index);
    }

    public function push($prefix = '')
    {
        $index = count($this->indentationStack);
        
        $this->indentationStack[] = $prefix ? ($prefix . ' ') : '';
        
        return $index;
    }

    public function pop()
    {
        array_pop($this->indentationStack);
    }
}

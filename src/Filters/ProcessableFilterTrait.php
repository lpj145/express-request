<?php
declare(strict_types=1);

namespace ExpressRequest\Filters;

trait ProcessableFilterTrait
{
    private $processable = false;

    protected function setCantProcess()
    {
        $this->processable = false;
    }

    protected function setProcessable()
    {
        $this->processable = true;
    }

    public function isProcessable(): bool
    {
        return $this->processable;
    }
}

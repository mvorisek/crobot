<?php

declare(strict_types=1);

namespace Mvorisek\Crobot\Github;

class GithubIsFileException extends \Exception
{
    private GithubFile $foundFile;

    public function __construct(GithubFile $foundFile, string $message)
    {
        parent::__construct($message);

        $this->foundFile = $foundFile;
    }

    public function getFoundFile(): GithubFile
    {
        return $this->foundFile;
    }
}

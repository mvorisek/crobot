<?php

declare(strict_types=1);

namespace Mvorisek\Crobot\Github;

class GithubFile
{
    protected GithubApi $api;
    protected string $repo;
    protected string $sha;
    protected string $mode;

    public function __construct(GithubApi $api, string $repo, string $sha, string $mode)
    {
        assert(in_array($mode, ['100644', '100755', '120000'], true));

        $this->api = $api;
        $this->repo = $repo;
        $this->sha = $sha;
        $this->mode = $mode;
    }

    public function getSha(): string
    {
        return $this->sha;
    }

    public function isExecutable(): bool
    {
        return $this->mode === '100755';
    }

    public function isSymlink(): bool
    {
        return $this->mode === '120000';
    }

    public function downloadContent(): string
    {
        assert(!$this->isSymlink());

        $response = $this->api->sendRequest('get', $this->api->makeRepoApiUrl($this->repo) . '/git/blobs/' . $this->sha);
        assert($response[0] === 200);

        assert($response[1]['encoding'] === 'base64');
        $content = base64_decode($response[1]['content'], true);
        assert($response[1]['size'] === strlen($content));

        return $content;
    }

    public function findSymlinkTargetPath(): string
    {
        assert($this->isSymlink());

        $modeBackup = $this->mode;
        try {
            $this->mode = '100644';

            return $this->downloadContent();
        } finally {
            $this->mode = $modeBackup;
        }
    }
}

<?php

declare(strict_types=1);

namespace Mvorisek\Crobot\Github;

class GithubTree
{
    protected GithubApi $api;
    protected string $repo;
    protected string $sha;

    public function __construct(GithubApi $api, string $repo, string $sha)
    {
        $this->api = $api;
        $this->repo = $repo;
        $this->sha = $sha;
    }

    public function getSha(): string
    {
        return $this->sha;
    }

    protected function assertValidPath(string $path): void
    {
        assert(preg_match('~(?:^|/)(?:|\.|\.\.)(?:$|/)~', $path) === 0);
    }

    private function isInDirectory(string $filterPath, string $filePath): bool
    {
        return $filterPath === '' || str_starts_with($filePath, $filterPath . '/');
    }

    /**
     * @return array<string, GithubFile|static>
     */
    protected function _listFiles(string $path = '', bool $recursive = true, bool $includeTrees = false): array
    {
        if ($path !== '') {
            $subtree = $this->findSubtree($path);

            $res = [];
            foreach ($subtree->_listFiles('', $recursive, $includeTrees) as $k => $v) {
                $res[$path . '/' . $k] = $v;
            }

            return $res;
        }

        $response = $this->api->sendRequest('get', $this->api->makeRepoApiUrl($this->repo) . '/git/trees/' . $this->sha . ($recursive ? '?recursive=1' : ''));
        assert($response[0] === 200);
        assert($response[1]['truncated'] === false);
        assert($response[1]['tree'] !== []);

        $res = [];
        foreach ($response[1]['tree'] as $item) {
            $this->assertValidPath($item['path']);

            if ($item['type'] === 'blob') {
                if ($this->isInDirectory($path, $item['path'])) {
                    $res[$item['path']] = new GithubFile($this->api, $this->repo, $item['sha'], $item['mode']);
                }
            } else {
                assert($item['type'] === 'tree');
                assert($item['mode'] === '040000');

                if ($includeTrees) {
                    $subtree = clone $this;
                    $subtree->sha = $item['sha'];

                    $res[$item['path']] = $subtree;
                }
            }
        }

        return $res;
    }

    /**
     * @return array<string, GithubFile>
     */
    public function listFiles(string $path = '', bool $recursive = true): array
    {
        return $this->_listFiles($path, $recursive);
    }

    /**
     * @param non-empty-string $path
     *
     * @return static
     */
    public function findSubtree(string $path): self
    {
        $this->assertValidPath($path);

        $lastSlashPos = strrpos($path, '/');
        $parentPath = $lastSlashPos === false
            ? ''
            : substr($path, 0, $lastSlashPos);

        try {
            $parentRes = $this->_listFiles($parentPath, false, true);
        } catch (GithubIsFileException $e) {
            throw new \Exception('Part of the path is a file');
        }

        if (!isset($parentRes[$path])) {
            throw new GithubPathNotFoundException('Path does not exist');
        }

        $res = $parentRes[$path];

        if ($res instanceof GithubFile) {
            throw new GithubIsFileException($res, 'Path is a file');
        }

        return $res;
    }
}

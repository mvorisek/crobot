<?php

declare(strict_types=1);

namespace Mvorisek\Crobot\Tests\Github;

use Atk4\Core\Phpunit\TestCase;
use Mvorisek\Crobot\Github\GithubApi;
use Mvorisek\Crobot\Github\GithubTree;
use Mvorisek\Crobot\Github\GithubTreeStaged;

class GithubTreeStagedTest extends TestCase
{
    /**
     * @param list<string> $old
     * @param list<string> $new
     *
     * @return list<string>
     */
    protected function makeDiff(array $old, array $new): array
    {
        $oldKeysIndex = array_flip($old);
        $newKeysIndex = array_flip($new);

        $res = [];
        foreach ($old as $k) {
            if (!isset($newKeysIndex[$k])) {
                $res[] = '- ' . $k;
            }
        }
        foreach ($new as $k) {
            if (!isset($oldKeysIndex[$k])) {
                $res[] = '+ ' . $k;
            }
        }

        return $res;
    }

    public function testUploadBasic(): void
    {
        $githubApi = new GithubApi();
        $tree = new GithubTree($githubApi, 'mvorisek/crobot', '95f25881c55a6419605c0df2b86672d44bdda0ed');

        $origFileNames = array_keys($tree->listFiles());

        $staged = new GithubTreeStaged($tree);
        self::assertSame($tree, $staged->upload());

        $staged->addFile('foo', 'Foo');
        $staged->addFile('bar/foo', 'Foo 2');
        $staged->addFile('bin', '', true);
        $staged->deleteFile('README.md');
        $newTree = $staged->upload();
        self::assertNotSame($tree, $newTree);

        $files = $newTree->listFiles();
        self::assertSame([
            '- README.md',
            '+ bar/foo',
            '+ bin',
            '+ foo',
        ], $this->makeDiff($origFileNames, array_keys($files)));

        self::assertFalse($files['foo']->isExecutable());
        self::assertFalse($files['foo']->isSymlink());
        self::assertTrue($files['bin']->isExecutable());
        self::assertFalse($files['bin']->isSymlink());
    }

    public function testUploadBinary(): void
    {
        $githubApi = new GithubApi();
        $tree = new GithubTree($githubApi, 'mvorisek/crobot', '95f25881c55a6419605c0df2b86672d44bdda0ed');

        $origFileNames = array_keys($tree->listFiles());

        $content = "\x00\xff" . str_repeat("xy\t\n", 100) . "\xfe";

        $staged = new GithubTreeStaged($tree);
        $staged->addFile('foo', $content);
        $newTree = $staged->upload();

        self::assertSame([
            '+ foo',
        ], $this->makeDiff($origFileNames, array_keys($newTree->listFiles())));

        $content2 = $newTree->listFiles()['foo']->downloadContent();
        self::assertSame(
            bin2hex(substr($content, 0, 10)) . '...' . bin2hex(substr($content, -10)),
            bin2hex(substr($content2, 0, 10)) . '...' . bin2hex(substr($content2, -10))
        );
        self::assertTrue($content2 === $content);
    }
}

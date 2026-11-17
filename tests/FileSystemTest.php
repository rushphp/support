<?php declare(strict_types=1);

use Rush\Support\FileSystem;
use Tests\Fixtures\FileSystemTestable;

beforeEach(function (): void {
    FileSystemTestable::reset();
});

test('copy dir replicates files and skips excluded top-level entries', function (): void {
    $root = sys_get_temp_dir() .'/rush-support-'. bin2hex(random_bytes(6));
    $source = $root .'/source';
    $destination = $root .'/destination';

    mkdir($source .'/keep', 0700, true);
    mkdir($source .'/storage', 0700, true);
    file_put_contents($source .'/root.txt', 'root');
    file_put_contents($source .'/keep/file.txt', 'keep');
    file_put_contents($source .'/storage/skip.txt', 'skip');

    FileSystem::copyDir($source, $destination, ['storage']);

    expect(file_get_contents($destination .'/root.txt'))->toBe('root')
        ->and(file_get_contents($destination .'/keep/file.txt'))->toBe('keep')
        ->and(is_file($destination .'/storage/skip.txt'))->toBeFalse();

    FileSystem::removeDir($root);

    expect(is_dir($root))->toBeFalse();
});


test('write creates parent directories and the file', function (): void {
    $root = sys_get_temp_dir() .'/rush-support-'. bin2hex(random_bytes(6));
    $file = $root .'/nested/file.txt';

    FileSystem::write($file, 'content', 0600);

    expect(is_file($file))->toBeTrue()
        ->and(file_get_contents($file))->toBe('content');

    FileSystem::removeDir($root);
});


test('temporary archive has the expected extension and location', function (): void {
    $archive = FileSystem::temporaryArchive('code-test-');

    expect($archive)->toStartWith(sys_get_temp_dir() .'/code-test-')
        ->and($archive)->toEndWith('.tar.gz')
        ->and(is_file($archive))->toBeFalse();
});


test('delete removes a directory recursively', function (): void {
    $root = sys_get_temp_dir() .'/rush-support-'. bin2hex(random_bytes(6));

    FileSystem::ensureDirectory($root .'/a/b');
    file_put_contents($root .'/a/b/file.txt', 'x');

    FileSystem::delete($root);

    expect(file_exists($root))->toBeFalse();
});


test('ensureDirectory is a no-op when the directory exists', function (): void {
    FileSystemTestable::$isDir = true;

    FileSystemTestable::ensureDirectory('/virtual');

    expect(FileSystemTestable::$mkdirCalls)->toBe(0);
});


test('ensureDirectory rejects a path that exists but is not a directory', function (): void {
    FileSystemTestable::$isDir = false;
    FileSystemTestable::$fileExists = true;

    expect(fn () => FileSystemTestable::ensureDirectory('/virtual'))
        ->toThrow(RuntimeException::class, 'not a directory');
});


test('ensureDirectory reports a mkdir failure', function (): void {
    FileSystemTestable::$isDir = false;
    FileSystemTestable::$fileExists = false;

    expect(fn () => FileSystemTestable::ensureDirectory('/virtual'))
        ->toThrow(RuntimeException::class, 'could not create directory');

    expect(FileSystemTestable::$mkdirCalls)->toBe(1);
});

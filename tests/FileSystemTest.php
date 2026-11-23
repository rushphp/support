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
    FileSystemTestable::$mkdirThrows = true;

    expect(fn () => FileSystemTestable::ensureDirectory('/virtual'))
        ->toThrow(RuntimeException::class, 'could not create directory');

    expect(FileSystemTestable::$mkdirCalls)->toBe(1);
});


test('ensureDirectory reports a false mkdir result', function (): void {
    FileSystemTestable::$isDir = false;
    FileSystemTestable::$fileExists = false;
    FileSystemTestable::$mkdirResult = false;

    expect(fn () => FileSystemTestable::ensureDirectory('/virtual'))
        ->toThrow(RuntimeException::class, 'could not create directory');

    expect(FileSystemTestable::$mkdirCalls)->toBe(1);
});


test('write reports a failed write', function (): void {
    FileSystemTestable::$isDir = true;
    FileSystemTestable::$filePutContentsResult = false;

    expect(fn () => FileSystemTestable::write('/virtual/file.txt', 'x'))
        ->toThrow(RuntimeException::class, 'could not write file');
});


test('copy dir excludes nested paths and accepts a trailing slash', function (): void {
    $root = sys_get_temp_dir() .'/rush-support-'. bin2hex(random_bytes(6));
    $source = $root .'/source';
    $destination = $root .'/destination';

    mkdir($source .'/keep/nested', 0700, true);
    mkdir($source .'/keep/other', 0700, true);
    file_put_contents($source .'/keep/nested/skip.txt', 'skip');
    file_put_contents($source .'/keep/other/keep.txt', 'keep');

    FileSystem::copyDir($source .'/', $destination, ['keep/nested']);

    expect(is_file($destination .'/keep/nested/skip.txt'))->toBeFalse()
        ->and(file_get_contents($destination .'/keep/other/keep.txt'))->toBe('keep');

    FileSystem::removeDir($root);
});


test('remove dir removes internal symlinks', function (): void {
    $root = sys_get_temp_dir() .'/rush-support-'. bin2hex(random_bytes(6));

    mkdir($root .'/target', 0700, true);
    symlink($root .'/target', $root .'/link');

    FileSystem::removeDir($root);

    expect(file_exists($root))->toBeFalse()
        ->and(is_link($root .'/link'))->toBeFalse();
});


test('write reports temporary, chmod and rename failures', function (): void {
    FileSystemTestable::$isDir = true;
    FileSystemTestable::$tempnamResult = false;

    expect(fn () => FileSystemTestable::write('/virtual/file.txt', 'x'))
        ->toThrow(RuntimeException::class, 'could not create temporary file');

    FileSystemTestable::reset();
    FileSystemTestable::$isDir = true;
    FileSystemTestable::$chmodResult = false;

    expect(fn () => FileSystemTestable::write('/virtual/file.txt', 'x'))
        ->toThrow(RuntimeException::class, 'could not chmod');

    FileSystemTestable::reset();
    FileSystemTestable::$isDir = true;
    FileSystemTestable::$renameResult = false;

    expect(fn () => FileSystemTestable::write('/virtual/file.txt', 'x'))
        ->toThrow(RuntimeException::class, 'could not move temporary file');
});


test('copyFile reports a missing source and a failed copy', function (): void {
    FileSystemTestable::$isFile = false;

    expect(fn () => FileSystemTestable::copyFile('/virtual/missing', '/virtual/out'))
        ->toThrow(RuntimeException::class, 'missing file');

    FileSystemTestable::reset();
    FileSystemTestable::$isFile = true;
    FileSystemTestable::$isDir = true;
    FileSystemTestable::$copyResult = false;

    expect(fn () => FileSystemTestable::copyFile('/virtual/in', '/virtual/out'))
        ->toThrow(RuntimeException::class, 'could not copy');
});


test('removeDir reports unlink and rmdir failures', function (): void {
    $root = sys_get_temp_dir() .'/rush-support-'. bin2hex(random_bytes(6));
    mkdir($root, 0700, true);
    file_put_contents($root .'/file.txt', 'x');

    FileSystemTestable::$isDir = true;
    FileSystemTestable::$unlinkResult = false;

    expect(fn () => FileSystemTestable::removeDir($root))
        ->toThrow(RuntimeException::class, 'could not remove file');

    FileSystem::removeDir($root);

    mkdir($root .'/sub', 0700, true);
    FileSystemTestable::reset();
    FileSystemTestable::$isDir = true;
    FileSystemTestable::$rmdirResult = false;

    expect(fn () => FileSystemTestable::removeDir($root))
        ->toThrow(RuntimeException::class, 'could not remove directory');

    FileSystem::removeDir($root);
});


test('tryDelete reports failure and treats a missing path as handled', function (): void {
    FileSystemTestable::$isFile = true;
    FileSystemTestable::$fileExists = true;
    FileSystemTestable::$unlinkResult = false;

    expect(FileSystemTestable::tryDelete('/virtual/file'))->toBeFalse();

    FileSystemTestable::reset();

    expect(FileSystemTestable::tryDelete('/virtual/missing'))->toBeTrue();
});


test('temporaryDirectory creates a fresh directory', function (): void {
    $directory = FileSystem::temporaryDirectory('rush-test-');

    expect(is_dir($directory))->toBeTrue()
        ->and(basename($directory))->toStartWith('rush-test-');

    FileSystem::removeDir($directory);
});


test('chmod applies the given mode', function (): void {
    $root = sys_get_temp_dir() .'/rush-support-'. bin2hex(random_bytes(6));
    $file = $root .'/file.txt';

    FileSystem::write($file, 'x');
    FileSystem::chmod($file, 0600);

    expect(substr(sprintf('%o', fileperms($file)), -4))->toBe('0600');

    FileSystem::removeDir($root);
});


test('copyFile copies file contents', function (): void {
    $root = sys_get_temp_dir() .'/rush-support-'. bin2hex(random_bytes(6));
    FileSystem::write($root .'/source.txt', 'content');

    FileSystem::copyFile($root .'/source.txt', $root .'/nested/target.txt');

    expect(file_get_contents($root .'/nested/target.txt'))->toBe('content');

    FileSystem::removeDir($root);
});


test('delete removes a file and a symlink', function (): void {
    $root = sys_get_temp_dir() .'/rush-support-'. bin2hex(random_bytes(6));
    mkdir($root, 0700, true);
    file_put_contents($root .'/file.txt', 'x');
    symlink($root .'/file.txt', $root .'/link');

    FileSystem::delete($root .'/file.txt');
    FileSystem::delete($root .'/link');

    expect(file_exists($root .'/file.txt'))->toBeFalse()
        ->and(is_link($root .'/link'))->toBeFalse();

    FileSystem::removeDir($root);
});

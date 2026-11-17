<?php declare(strict_types=1);

namespace Tests\Fixtures;

use Rush\Support\FileSystem;
use RuntimeException;


final class FileSystemTestable extends FileSystem
{
    public static bool $isDir = false;

    public static bool $fileExists = false;

    public static int $mkdirCalls = 0;


    public static function reset(): void
    {
        self::$isDir = false;
        self::$fileExists = false;
        self::$mkdirCalls = 0;
    }


    protected static function doIsDir(string $path): bool
    {
        return self::$isDir;
    }


    protected static function doFileExists(string $path): bool
    {
        return self::$fileExists;
    }


    protected static function doMkdir(string $path, int $mode): bool
    {
        self::$mkdirCalls++;

        throw new RuntimeException('mkdir failed');
    }
}

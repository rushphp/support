<?php declare(strict_types=1);

namespace Tests\Fixtures;

use Rush\Support\FileSystem;
use RuntimeException;


final class FileSystemTestable extends FileSystem
{
    public static bool $isDir = false;

    public static bool $fileExists = false;

    public static int $mkdirCalls = 0;

    public static bool $mkdirResult = true;

    public static bool $mkdirThrows = false;

    public static int|false $filePutContentsResult = 1;


    public static function reset(): void
    {
        self::$isDir = false;
        self::$fileExists = false;
        self::$mkdirCalls = 0;
        self::$mkdirResult = true;
        self::$mkdirThrows = false;
        self::$filePutContentsResult = 1;
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

        if (self::$mkdirThrows) {
            throw new RuntimeException('mkdir failed');
        }

        return self::$mkdirResult;
    }


    protected static function doTempnam(string $directory, string $prefix): string|false
    {
        return sys_get_temp_dir() .'/'. $prefix . bin2hex(random_bytes(6));
    }


    protected static function doFilePutContents(string $file, string $contents): int|false
    {
        return self::$filePutContentsResult;
    }


    protected static function doChmod(string $path, int $mode): bool
    {
        return true;
    }


    protected static function doRename(string $from, string $to): bool
    {
        return true;
    }
}

<?php declare(strict_types=1);

namespace Tests\Fixtures;

use Rush\Support\FileSystem;
use RuntimeException;


final class FileSystemTestable extends FileSystem
{
    public static bool $isDir = false;

    public static bool $isFile = false;

    public static bool $isLink = false;

    public static bool $fileExists = false;

    public static int $mkdirCalls = 0;

    public static bool $mkdirResult = true;

    public static bool $mkdirThrows = false;

    public static int|false $filePutContentsResult = 1;

    public static bool $tempnamResult = true;

    public static bool $chmodResult = true;

    public static bool $renameResult = true;

    public static bool $copyResult = true;

    public static bool $unlinkResult = true;

    public static bool $rmdirResult = true;


    public static function reset(): void
    {
        self::$isDir = false;
        self::$isFile = false;
        self::$isLink = false;
        self::$fileExists = false;
        self::$mkdirCalls = 0;
        self::$mkdirResult = true;
        self::$mkdirThrows = false;
        self::$filePutContentsResult = 1;
        self::$tempnamResult = true;
        self::$chmodResult = true;
        self::$renameResult = true;
        self::$copyResult = true;
        self::$unlinkResult = true;
        self::$rmdirResult = true;
    }


    protected static function doIsDir(string $path): bool
    {
        return self::$isDir;
    }


    protected static function doIsFile(string $path): bool
    {
        return self::$isFile;
    }


    protected static function doIsLink(string $path): bool
    {
        return self::$isLink;
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
        return self::$tempnamResult
            ? sys_get_temp_dir() .'/'. $prefix . bin2hex(random_bytes(6))
            : false;
    }


    protected static function doFilePutContents(string $file, string $contents): int|false
    {
        return self::$filePutContentsResult;
    }


    protected static function doChmod(string $path, int $mode): bool
    {
        return self::$chmodResult;
    }


    protected static function doRename(string $from, string $to): bool
    {
        return self::$renameResult;
    }


    protected static function doCopy(string $from, string $to): bool
    {
        return self::$copyResult;
    }


    protected static function doUnlink(string $path): bool
    {
        return self::$unlinkResult;
    }


    protected static function doRmdir(string $path): bool
    {
        return self::$rmdirResult;
    }
}

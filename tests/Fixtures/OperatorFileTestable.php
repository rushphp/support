<?php declare(strict_types=1);

namespace Tests\Fixtures;

use Rush\Support\OperatorFile;


final class OperatorFileTestable extends OperatorFile
{
    public static bool $file = false;

    public static bool $readable = true;

    public static string|false $contents = false;


    public static function reset(): void
    {
        self::$file = false;
        self::$readable = true;
        self::$contents = false;
    }


    protected static function doIsFile(string $path): bool
    {
        return self::$file;
    }


    protected static function doIsReadable(string $path): bool
    {
        return self::$readable;
    }


    protected static function doFileGetContents(string $file): string|false
    {
        return self::$contents;
    }
}

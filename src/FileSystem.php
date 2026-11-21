<?php declare(strict_types=1);

namespace Rush\Support;

use ErrorException;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use SplFileInfo;
use Throwable;


class FileSystem
{
    public static function ensureDirectory(string $path, int $mode = 0700): void
    {
        if (static::doIsDir($path)) {
            return;
        }

        if (static::doFileExists($path)) {
            throw new RuntimeException("not a directory: {$path}");
        }

        try {
            $created = static::guard(static fn () => static::doMkdir($path, $mode));

            if ($created !== true) {
                throw new RuntimeException("could not create directory: {$path}");
            }
        } catch (Throwable $error) {
            if (static::doIsDir($path)) {
                return;
            }

            throw new RuntimeException("could not create directory: {$path}", 0, $error);
        }
    }


    public static function write(string $path, string $contents, int $mode = 0644): void
    {
        static::ensureDirectory(dirname($path));

        $temporary = static::guard(static fn () => static::doTempnam(dirname($path), '.rush-'));

        if ( ! is_string($temporary)) {
            throw new RuntimeException("could not create temporary file for {$path}");
        }

        try {
            $written = static::guard(static fn () => static::doFilePutContents($temporary, $contents));

            if ($written === false) {
                throw new RuntimeException("could not write file: {$path}");
            }

            static::chmod($temporary, $mode);

            if ( ! static::guard(static fn () => static::doRename($temporary, $path))) {
                throw new RuntimeException("could not move temporary file to {$path}");
            }
        } catch (Throwable $error) {
            static::tryDelete($temporary);
            throw $error;
        }
    }


    public static function copyFile(string $source, string $destination): void
    {
        if ( ! static::doIsFile($source)) {
            throw new RuntimeException("missing file: {$source}");
        }

        static::ensureDirectory(dirname($destination));

        if ( ! static::guard(static fn () => static::doCopy($source, $destination))) {
            throw new RuntimeException("could not copy {$source} to {$destination}");
        }
    }


    /**
     * @param list<string> $exclude
     */
    public static function copyDir(string $source, string $destination, array $exclude = []): void
    {
        $source = rtrim($source, '/') ?: '/';
        $destination = rtrim($destination, '/');

        if ( ! static::doIsDir($source)) {
            throw new RuntimeException("missing directory: {$source}");
        }

        static::ensureDirectory($destination);

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST,
        );

        foreach ($iterator as $item) {
            if ( ! $item instanceof SplFileInfo || $item->isLink()) {
                continue;
            }

            $relative = str_replace('\\', '/', substr($item->getPathname(), strlen($source) + 1));

            if ($relative === '') {
                continue;
            }

            if (self::isExcluded($relative, $exclude)) {
                continue;
            }

            $path = $destination .'/'. $relative;

            if ($item->isDir()) {
                static::ensureDirectory($path);
                continue;
            }

            if ($item->isFile()) {
                static::copyFile($item->getPathname(), $path);
            }
        }
    }


    /** @param list<string> $exclude */
    private static function isExcluded(string $relative, array $exclude): bool
    {
        $top = explode('/', $relative)[0];

        foreach ($exclude as $item) {
            $item = trim($item, '/');

            if ($item === '') {
                continue;
            }

            if ($item === $relative || str_starts_with($relative, $item .'/')) {
                return true;
            }

            if ( ! str_contains($item, '/') && $item === $top) {
                return true;
            }
        }

        return false;
    }


    public static function removeDir(string $directory): void
    {
        if ( ! static::doIsDir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $item) {
            if ( ! $item instanceof SplFileInfo) {
                continue;
            }

            if ($item->isLink()) {
                if ( ! static::guard(static fn () => static::doUnlink($item->getPathname()))) {
                    throw new RuntimeException("could not remove link {$item->getPathname()}");
                }

                continue;
            }

            if ($item->isDir()) {
                if ( ! static::guard(static fn () => static::doRmdir($item->getPathname()))) {
                    throw new RuntimeException("could not remove directory {$item->getPathname()}");
                }

                continue;
            }

            if ( ! static::guard(static fn () => static::doUnlink($item->getPathname()))) {
                throw new RuntimeException("could not remove file {$item->getPathname()}");
            }
        }

        if ( ! static::guard(static fn () => static::doRmdir($directory))) {
            throw new RuntimeException("could not remove directory {$directory}");
        }
    }


    public static function delete(string $path): void
    {
        if ( ! static::doFileExists($path) && ! static::doIsLink($path)) {
            return;
        }

        if (static::doIsDir($path) && ! static::doIsLink($path)) {
            static::removeDir($path);
            return;
        }

        if ( ! static::guard(static fn () => static::doUnlink($path))) {
            throw new RuntimeException("could not delete {$path}");
        }
    }


    public static function tryDelete(string $path): bool
    {
        try {
            static::delete($path);

            return true;
        } catch (Throwable) {
            return false;
        }
    }


    public static function chmod(string $path, int $mode): void
    {
        if ( ! static::guard(static fn () => static::doChmod($path, $mode))) {
            throw new RuntimeException("could not chmod {$path}");
        }
    }


    public static function temporaryDirectory(string $prefix = 'rush-'): string
    {
        $directory = static::doSysGetTempDir() .'/'. $prefix . bin2hex(random_bytes(6));

        static::ensureDirectory($directory);

        return $directory;
    }


    public static function temporaryArchive(string $prefix = 'rush-'): string
    {
        return static::doSysGetTempDir() .'/'. $prefix . bin2hex(random_bytes(6)) .'.tar.gz';
    }


    /** @phpstan-impure */
    protected static function doIsDir(string $path): bool
    {
        return is_dir($path);
    }


    protected static function doIsFile(string $path): bool
    {
        return is_file($path);
    }


    protected static function doIsLink(string $path): bool
    {
        return is_link($path);
    }


    protected static function doFileExists(string $path): bool
    {
        return file_exists($path);
    }


    protected static function doMkdir(string $path, int $mode): bool
    {
        return mkdir($path, $mode, true);
    }


    protected static function doTempnam(string $directory, string $prefix): string|false
    {
        return tempnam($directory, $prefix);
    }


    protected static function doFilePutContents(string $file, string $contents): int|false
    {
        return file_put_contents($file, $contents);
    }


    protected static function doChmod(string $path, int $mode): bool
    {
        return chmod($path, $mode);
    }


    protected static function doRename(string $from, string $to): bool
    {
        return rename($from, $to);
    }


    protected static function doCopy(string $from, string $to): bool
    {
        return copy($from, $to);
    }


    protected static function doUnlink(string $path): bool
    {
        return unlink($path);
    }


    protected static function doRmdir(string $path): bool
    {
        return rmdir($path);
    }


    protected static function doSysGetTempDir(): string
    {
        return sys_get_temp_dir();
    }


    protected static function guard(callable $operation): mixed
    {
        set_error_handler(static function (
            int $severity,
            string $message,
            string $file,
            int $line,
        ): bool {
            throw new ErrorException($message, 0, $severity, $file, $line);
        });

        try {
            return $operation();
        } finally {
            restore_error_handler();
        }
    }
}

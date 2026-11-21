<?php declare(strict_types=1);

namespace Rush\Support;

use JsonException;
use RuntimeException;


class OperatorFile
{
    public static function path(string $projectRoot): string
    {
        return rtrim($projectRoot, '/') .'/.rush/operator.json';
    }


    public static function exists(string $projectRoot): bool
    {
        return static::doIsFile(static::path($projectRoot));
    }


    public static function read(string $projectRoot): ?Operator
    {
        $file = static::path($projectRoot);

        if ( ! static::doIsFile($file) || ! static::doIsReadable($file)) {
            return null;
        }

        $raw = static::doFileGetContents($file);

        if ($raw === false) {
            return null;
        }

        try {
            $data = json_decode($raw, true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if ( ! is_array($data)) {
            return null;
        }

        $name = $data['name'] ?? null;
        $email = $data['email'] ?? null;

        if ( ! is_string($name) || trim($name) === '' || ! is_string($email) || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }

        return new Operator(trim($name), trim($email));
    }


    public static function load(string $projectRoot): Operator
    {
        $operator = static::read($projectRoot);

        if ($operator === null) {
            throw new RuntimeException(
                'operator not configured; run ./rush operator --name="..." --email="..."',
            );
        }

        return $operator;
    }


    public static function save(string $projectRoot, Operator $operator): void
    {
        try {
            $json = json_encode(
                $operator->toArray(),
                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR,
            ) ."\n";
        } catch (JsonException $exception) {
            throw new RuntimeException('could not encode operator: '. $exception->getMessage(), 0, $exception);
        }

        static::doWrite(static::path($projectRoot), $json, 0600);
    }


    protected static function doIsFile(string $path): bool
    {
        return is_file($path);
    }


    protected static function doIsReadable(string $path): bool
    {
        return is_readable($path);
    }


    protected static function doFileGetContents(string $file): string|false
    {
        return file_get_contents($file);
    }


    protected static function doWrite(string $path, string $contents, int $mode): void
    {
        FileSystem::write($path, $contents, $mode);
    }
}

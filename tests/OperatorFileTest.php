<?php declare(strict_types=1);

use Rush\Support\FileSystem;
use Rush\Support\Operator;
use Rush\Support\OperatorFile;
use Tests\Fixtures\OperatorFileTestable;

beforeEach(function (): void {
    OperatorFileTestable::reset();
});

test('saves, reads and loads the operator identity', function (): void {
    $root = sys_get_temp_dir() .'/rush-support-'. bin2hex(random_bytes(6));
    mkdir($root, 0700, true);

    OperatorFile::save($root, new Operator('Developer', 'developer@example.com'));

    expect(OperatorFile::path($root))->toBe($root .'/.rush/operator.json')
        ->and(OperatorFile::exists($root))->toBeTrue()
        ->and(OperatorFile::load($root)->toArray())->toBe([
            'name' => 'Developer',
            'email' => 'developer@example.com',
        ]);

    FileSystem::removeDir($root);
});


test('load throws when the operator is missing', function (): void {
    $root = sys_get_temp_dir() .'/rush-support-'. bin2hex(random_bytes(6));
    mkdir($root, 0700, true);

    expect(fn () => OperatorFile::load($root))
        ->toThrow(RuntimeException::class);

    FileSystem::removeDir($root);
});


test('read returns null for an invalid email', function (): void {
    $root = sys_get_temp_dir() .'/rush-support-'. bin2hex(random_bytes(6));
    mkdir($root .'/.rush', 0700, true);
    file_put_contents($root .'/.rush/operator.json', json_encode([
        'name' => 'Developer',
        'email' => 'not-an-email',
    ]));

    expect(OperatorFile::read($root))->toBeNull()
        ->and(OperatorFile::exists($root))->toBeTrue();

    FileSystem::removeDir($root);
});


test('read returns null when the file is missing', function (): void {
    OperatorFileTestable::$file = false;

    expect(OperatorFileTestable::read('/virtual'))->toBeNull();
});


test('read returns null when the file is not readable', function (): void {
    OperatorFileTestable::$file = true;
    OperatorFileTestable::$readable = false;

    expect(OperatorFileTestable::read('/virtual'))->toBeNull();
});


test('read returns null for invalid json', function (): void {
    OperatorFileTestable::$file = true;
    OperatorFileTestable::$contents = 'not-json';

    expect(OperatorFileTestable::read('/virtual'))->toBeNull();
});


test('read returns null for an invalid payload', function (): void {
    OperatorFileTestable::$file = true;
    OperatorFileTestable::$contents = json_encode([
        'name' => 'Developer',
        'email' => 'not-an-email',
    ]);

    expect(OperatorFileTestable::read('/virtual'))->toBeNull();
});


test('read returns an operator for a valid payload', function (): void {
    OperatorFileTestable::$file = true;
    OperatorFileTestable::$contents = json_encode([
        'name' => 'Developer',
        'email' => 'developer@example.com',
    ]);

    expect(OperatorFileTestable::read('/virtual')?->toArray())->toBe([
        'name' => 'Developer',
        'email' => 'developer@example.com',
    ]);
});


test('load throws when the testable file is missing', function (): void {
    OperatorFileTestable::$file = false;

    expect(fn () => OperatorFileTestable::load('/virtual'))
        ->toThrow(RuntimeException::class, 'operator not configured');
});


test('exists reflects the file probe', function (): void {
    OperatorFileTestable::$file = true;

    expect(OperatorFileTestable::exists('/virtual'))->toBeTrue();

    OperatorFileTestable::$file = false;

    expect(OperatorFileTestable::exists('/virtual'))->toBeFalse();
});


test('save writes a private file and overwrites it', function (): void {
    $root = sys_get_temp_dir() .'/rush-support-'. bin2hex(random_bytes(6));
    mkdir($root, 0700, true);

    OperatorFile::save($root, new Operator('First', 'first@example.com'));
    OperatorFile::save($root, new Operator('Second', 'second@example.com'));

    $file = OperatorFile::path($root);

    expect(file_get_contents($file))->toContain('Second')
        ->and(substr(sprintf('%o', fileperms($file)), -4))->toBe('0600')
        ->and(OperatorFile::load($root)->name)->toBe('Second');

    FileSystem::removeDir($root);
});

<?php declare(strict_types=1);

use Rush\Support\FileSystem;
use Rush\Support\Operator;
use Rush\Support\OperatorFile;

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

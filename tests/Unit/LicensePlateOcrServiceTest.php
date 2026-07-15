<?php

use App\Services\LicensePlateOcrService;

uses(Tests\TestCase::class);

test('license plate ocr parsing preserves spaces and dashes', function () {
    $service = new LicensePlateOcrService();
    $reflection = new ReflectionClass(LicensePlateOcrService::class);
    $method = $reflection->getMethod('extractBestPlateCandidate');
    $method->setAccessible(true);

    // Test with XML format
    expect($method->invoke($service, '<plate>1234 AB 01</plate>'))->toBe('1234 AB 01');
    expect($method->invoke($service, '<plate>AB-123-CD</plate>'))->toBe('AB-123-CD');
    expect($method->invoke($service, '<plate>NOT_FOUND</plate>'))->toBeNull();
    expect($method->invoke($service, '<plate>AS-852-XZ'))->toBe('AS-852-XZ');
    expect($method->invoke($service, '<plate>A'))->toBeNull();

    // Test with fallback formats (without xml)
    expect($method->invoke($service, 'AS-852-XZ'))->toBe('AS-852-XZ');
    expect($method->invoke($service, '1234 AB 01'))->toBe('1234 AB 01');
    expect($method->invoke($service, 'NOT_FOUND'))->toBeNull();
    expect($method->invoke($service, 'SOME JUNK WITH AS-852-XZ'))->toBe('AS-852-XZ');
});

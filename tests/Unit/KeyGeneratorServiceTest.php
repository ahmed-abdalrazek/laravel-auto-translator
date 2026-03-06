<?php

namespace Aar\AutoTranslator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Aar\AutoTranslator\Services\KeyGeneratorService;

class KeyGeneratorServiceTest extends TestCase
{
    private KeyGeneratorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new KeyGeneratorService([
            'source_locale'    => 'en',
            'locales'          => ['en', 'ar', 'fr'],
            'auto_format_keys' => true,
        ]);
    }

    /** @test */
    public function it_formats_snake_case_keys_to_human_readable(): void
    {
        $this->assertSame('Order Summary', $this->service->formatKey('order_summary'));
        $this->assertSame('Login', $this->service->formatKey('auth.login'));
        $this->assertSame('Welcome Back', $this->service->formatKey('welcome_back'));
        $this->assertSame('User Profile', $this->service->formatKey('user_profile'));
    }

    /** @test */
    public function it_formats_kebab_case_keys(): void
    {
        $this->assertSame('Submit Form', $this->service->formatKey('submit-form'));
    }

    /** @test */
    public function it_parses_dot_notation_keys_into_group_and_item(): void
    {
        [$group, $item] = $this->service->parseKey('auth.login');
        $this->assertSame('auth', $group);
        $this->assertSame('login', $item);
    }

    /** @test */
    public function it_parses_plain_string_keys_as_json_group(): void
    {
        [$group, $item] = $this->service->parseKey('Hello World');
        $this->assertSame('*', $group);
        $this->assertSame('Hello World', $item);
    }

    /** @test */
    public function it_parses_nested_dot_notation_preserving_group(): void
    {
        [$group, $item] = $this->service->parseKey('validation.required');
        $this->assertSame('validation', $group);
        $this->assertSame('required', $item);
    }

    /** @test */
    public function it_handles_deeply_nested_keys(): void
    {
        [$group, $item] = $this->service->parseKey('messages.user.welcome');
        $this->assertSame('messages', $group);
        $this->assertSame('user.welcome', $item);
    }
}

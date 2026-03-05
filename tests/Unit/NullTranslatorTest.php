<?php

namespace Rz\LaravelAutoTranslator\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Rz\LaravelAutoTranslator\Translators\NullTranslator;

class NullTranslatorTest extends TestCase
{
    private NullTranslator $translator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->translator = new NullTranslator();
    }

    /** @test */
    public function it_returns_original_text_unchanged(): void
    {
        $this->assertSame('Hello World', $this->translator->translate('Hello World', 'ar', 'en'));
    }

    /** @test */
    public function it_returns_batch_unchanged(): void
    {
        $texts = ['Hello', 'World', 'Test'];
        $this->assertSame($texts, $this->translator->translateBatch($texts, 'fr', 'en'));
    }

    /** @test */
    public function it_has_correct_provider_name(): void
    {
        $this->assertSame('null', $this->translator->getProviderName());
    }

    /** @test */
    public function it_handles_empty_batch(): void
    {
        $this->assertSame([], $this->translator->translateBatch([], 'fr', 'en'));
    }
}

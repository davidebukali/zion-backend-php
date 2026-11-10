<?php

namespace Tests\Feature;

use Tests\TestCase;

class LegalPagesTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'session.driver' => 'array',
            'cache.default' => 'array',
        ]);
    }

    public function test_community_guidelines_page_is_accessible(): void
    {
        $response = $this->get(route('community-guidelines'));

        $response->assertStatus(200);
        $response->assertSee('Our Community Guidelines');
        $response->assertSee('An Open Door, A Peaceful Home');
        $response->assertSee('What We Welcome');
        $response->assertSee('What We Do Not Allow');
        $response->assertSee('Navigating Disputable Matters');
        $response->assertSee('Resolving Conflict');
    }

    public function test_footer_contains_community_guidelines_link(): void
    {
        $response = $this->get(route('home'));

        $response->assertStatus(200);
        $response->assertSee(route('community-guidelines'));
        $response->assertSee('Community Guidelines');
    }

    public function test_footer_does_not_contain_logo_or_tagline(): void
    {
        $footerHtml = view('components.landing.footer')->render();

        $this->assertStringNotContainsString('images/landing/logo.webp', $footerHtml);
        $this->assertStringNotContainsString('A social home built for what matters most.', $footerHtml);
        $this->assertStringContainsString('Community Guidelines', $footerHtml);
    }

    public function test_terms_page_links_to_community_guidelines(): void
    {
        $response = $this->get(route('terms'));

        $response->assertStatus(200);
        $response->assertSee(route('community-guidelines'));
    }
}

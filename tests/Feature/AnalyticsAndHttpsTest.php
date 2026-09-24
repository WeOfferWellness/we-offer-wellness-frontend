<?php

namespace Tests\Feature;

use Tests\TestCase;

class AnalyticsAndHttpsTest extends TestCase
{
    public function test_frontend_document_roots_share_the_base_layout(): void
    {
        $base = file_get_contents(resource_path('views/layouts/base.blade.php'));
        $this->assertStringContainsString("partials.analytics.ga4-head", $base);
        $this->assertStringContainsString("partials.analytics-bridge", $base);

        foreach ([
            resource_path('views/layouts/app.blade.php'),
            resource_path('views/app.blade.php'),
            resource_path('views/layouts/account.blade.php'),
            resource_path('views/redirecting.blade.php'),
        ] as $path) {
            $contents = file_get_contents($path);
            $this->assertStringContainsString("@extends('layouts.base')", $contents, $path);
            $this->assertStringNotContainsString("partials.analytics.ga4-head", $contents, $path);
        }
    }

    public function test_ga4_bootstrap_has_one_config_and_restores_consent_before_loader(): void
    {
        $contents = file_get_contents(resource_path('views/partials/analytics/ga4-head.blade.php'));
        $defaultPosition = strpos($contents, "gtag('consent', 'default'");
        $storedConsentPosition = strpos($contents, "localStorage.getItem('wow_cookie_preferences')");
        $loaderPosition = strpos($contents, 'googletagmanager.com/gtag/js');
        $configPosition = strpos($contents, "gtag('config'");

        $this->assertSame(1, substr_count($contents, "gtag('config'"));
        $this->assertIsInt($defaultPosition);
        $this->assertIsInt($storedConsentPosition);
        $this->assertIsInt($loaderPosition);
        $this->assertIsInt($configPosition);
        $this->assertLessThan($loaderPosition, $defaultPosition);
        $this->assertLessThan($loaderPosition, $storedConsentPosition);
        $this->assertLessThan($configPosition, $loaderPosition);
        $this->assertStringContainsString('send_page_view: true', $contents);
    }

    public function test_http_www_redirects_to_https_www(): void
    {
        $response = $this->withServerVariables([
            'HTTP_HOST' => 'www.weofferwellness.co.uk',
            'HTTPS' => 'off',
            'SERVER_PORT' => 80,
        ])->get('/schedule-discovery?date=2026-09-24');

        $response->assertStatus(301);
        $response->assertRedirect('https://www.weofferwellness.co.uk/schedule-discovery?date=2026-09-24');
    }

    public function test_https_apex_redirects_to_https_www(): void
    {
        $response = $this->withServerVariables([
            'HTTP_HOST' => 'weofferwellness.co.uk',
            'HTTPS' => 'on',
            'SERVER_PORT' => 443,
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ])->get('/');

        $response->assertStatus(301);
        $response->assertRedirect('https://www.weofferwellness.co.uk/');
    }
}

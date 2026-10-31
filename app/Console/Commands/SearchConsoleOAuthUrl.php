<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SearchConsoleOAuthUrl extends Command
{
    protected $signature = 'search-console:oauth-url
        {--client-id= : Google OAuth client ID}
        {--redirect-uri= : OAuth redirect URI}
        {--scope= : OAuth scope to request}';

    protected $description = 'Print a Google OAuth consent URL for Search Console access.';

    public function handle(): int
    {
        [$clientId, $redirectUri] = $this->loadClientDetails();
        $scope = trim((string) ($this->option('scope') ?: implode(' ', [
            'https://www.googleapis.com/auth/webmasters',
            'https://www.googleapis.com/auth/analytics.readonly',
        ])));

        if ($clientId === '') {
            $this->error('Missing Google OAuth client ID. Set GOOGLE_SEARCH_CONSOLE_CLIENT_ID or pass --client-id=');
            return self::FAILURE;
        }

        if ($redirectUri === '') {
            $this->error('Missing redirect URI. Pass --redirect-uri=.');
            return self::FAILURE;
        }

        $url = 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query([
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => $scope,
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
        ], '', '&', PHP_QUERY_RFC3986);

        $this->line($url);
        $this->newLine();
        $this->info('Open the URL, sign in with the Google account that has Search Console access, then copy the `code` parameter from the redirect URL.');

        return self::SUCCESS;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function loadClientDetails(): array
    {
        $clientId = trim((string) ($this->option('client-id') ?: config('services.search_console.client_id', '')));
        $redirectUri = trim((string) ($this->option('redirect-uri') ?: rtrim((string) config('app.url', ''), '/') . '/search-console/oauth/callback'));

        $credentialsFile = trim((string) config('services.search_console.client_credentials_file', ''));
        if ($clientId === '' && $credentialsFile !== '' && File::isFile($credentialsFile)) {
            $data = json_decode((string) File::get($credentialsFile), true);

            if (is_array($data)) {
                $clientId = $clientId !== '' ? $clientId : trim((string) ($data['web']['client_id'] ?? $data['installed']['client_id'] ?? ''));
            }
        }

        return [$clientId, $redirectUri];
    }
}

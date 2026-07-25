<?php
namespace App\Services;

use App\Core\Settings;

/**
 * Minimal GitHub REST client using a Personal Access Token (stored encrypted).
 * The token is sent only in the Authorization header — never in URLs or logs.
 */
class GitHubClient
{
    private string $repo;
    private string $token;

    public function __construct(?string $repo = null, ?string $token = null)
    {
        $this->repo  = $repo  ?? (string)Settings::get('github_repo', '');
        $this->token = $token ?? (string)Settings::getSecret('github_token', '');
    }

    public function isConfigured(): bool
    {
        return $this->repo !== '' && strpos($this->repo, '/') !== false;
    }

    /** GET a GitHub API endpoint and decode JSON. */
    public function get(string $path): array
    {
        [$body, $code] = $this->request('GET', 'https://api.github.com' . $path);
        $data = json_decode($body, true);
        if ($code < 200 || $code >= 300) {
            throw new \RuntimeException('GitHub API error (' . $code . '): ' . ($data['message'] ?? 'unknown'));
        }
        return is_array($data) ? $data : [];
    }

    /** Latest commit on a branch. */
    public function latestCommit(string $branch): array
    {
        return $this->get("/repos/{$this->repo}/commits/" . rawurlencode($branch));
    }

    /** Compare two commits and list changed files. */
    public function compare(string $base, string $head): array
    {
        if ($base === '') { return ['files' => []]; }
        return $this->get("/repos/{$this->repo}/compare/{$base}...{$head}");
    }

    /** Download the branch zipball to a local file. Returns bytes written. */
    public function downloadZip(string $branch, string $dest): int
    {
        $url = "https://api.github.com/repos/{$this->repo}/zipball/" . rawurlencode($branch);
        $fp = fopen($dest, 'w');
        if (!$fp) { throw new \RuntimeException('Cannot open destination for download.'); }
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_FILE           => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER     => $this->headers(),
            CURLOPT_TIMEOUT        => 300,
        ]);
        $ok = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);
        if (!$ok || $code < 200 || $code >= 300) {
            @unlink($dest);
            throw new \RuntimeException('Zip download failed (HTTP ' . $code . ').');
        }
        return (int)filesize($dest);
    }

    public function testConnection(): array
    {
        try {
            $data = $this->get("/repos/{$this->repo}");
            return ['ok' => true, 'full_name' => $data['full_name'] ?? $this->repo, 'private' => $data['private'] ?? null];
        } catch (\Throwable $e) {
            return ['ok' => false, 'error' => $e->getMessage()];
        }
    }

    private function headers(): array
    {
        $h = [
            'User-Agent: DwarkaRental-Updater',
            'Accept: application/vnd.github+json',
            'X-GitHub-Api-Version: 2022-11-28',
        ];
        if ($this->token !== '') {
            $h[] = 'Authorization: Bearer ' . $this->token;
        }
        return $h;
    }

    private function request(string $method, string $url): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTPHEADER     => $this->headers(),
            CURLOPT_TIMEOUT        => 30,
        ]);
        $body = curl_exec($ch);
        $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $err  = curl_error($ch);
        curl_close($ch);
        if ($body === false) {
            throw new \RuntimeException('GitHub request failed: ' . $err);
        }
        return [(string)$body, $code];
    }
}

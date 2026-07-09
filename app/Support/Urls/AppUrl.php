<?php

namespace App\Support\Urls;

class AppUrl
{
    public static function publicRoot(): string
    {
        return rtrim((string) config('app.public_url', config('app.url')), '/');
    }

    public static function adminRoot(): string
    {
        return rtrim((string) config('app.admin_url', config('app.url')), '/');
    }

    public static function publicHost(): ?string
    {
        return self::host(self::publicRoot());
    }

    public static function adminHost(): ?string
    {
        return self::host(self::adminRoot());
    }

    public static function hostsAreSplit(): bool
    {
        $publicHost = self::publicHost();
        $adminHost = self::adminHost();

        return $publicHost !== null
            && $adminHost !== null
            && $publicHost !== $adminHost;
    }

    public static function publicPath(string $path): string
    {
        if (self::isAbsoluteUrl($path)) {
            return $path;
        }

        return self::publicRoot().'/'.ltrim($path, '/');
    }

    public static function publicPathWhenSplit(string $path): string
    {
        if (! self::hostsAreSplit()) {
            return '/'.ltrim($path, '/');
        }

        return self::publicPath($path);
    }

    public static function adminPath(string $path): string
    {
        if (self::isAbsoluteUrl($path)) {
            return $path;
        }

        return self::adminRoot().'/'.ltrim($path, '/');
    }

    public static function adminPathWhenSplit(string $path): string
    {
        if (! self::hostsAreSplit()) {
            return '/'.ltrim($path, '/');
        }

        return self::adminPath($path);
    }

    private static function host(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) && $host !== '' ? $host : null;
    }

    private static function isAbsoluteUrl(string $url): bool
    {
        return str_starts_with($url, 'http://') || str_starts_with($url, 'https://');
    }
}

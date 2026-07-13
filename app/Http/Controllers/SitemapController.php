<?php

namespace App\Http\Controllers;

use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        $now = now()->toAtomString();
        $urls = [
            ['loc' => route('student.landing'), 'changefreq' => 'weekly', 'priority' => '1.0'],
            ['loc' => route('about-system'), 'changefreq' => 'monthly', 'priority' => '0.8'],
            ['loc' => route('login'), 'changefreq' => 'monthly', 'priority' => '0.6'],
            ['loc' => route('student.account.login.form'), 'changefreq' => 'monthly', 'priority' => '0.7'],
            ['loc' => route('student.password.forgot'), 'changefreq' => 'yearly', 'priority' => '0.3'],
            ['loc' => route('password.forgot'), 'changefreq' => 'yearly', 'priority' => '0.3'],
        ];

        $body = '<?xml version="1.0" encoding="UTF-8"?>'."\n";
        $body .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">'."\n";
        foreach ($urls as $entry) {
            $body .= "  <url>\n";
            $body .= '    <loc>'.e($entry['loc'])."</loc>\n";
            $body .= "    <lastmod>{$now}</lastmod>\n";
            $body .= '    <changefreq>'.$entry['changefreq']."</changefreq>\n";
            $body .= '    <priority>'.$entry['priority']."</priority>\n";
            $body .= "  </url>\n";
        }
        $body .= '</urlset>';

        return response($body, 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}

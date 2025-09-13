<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Quiz;
use App\Models\LegalPage;
use App\Models\Plan;

class SitemapController extends Controller
{
    public function index()
    {
        try {
            $sitemap = '<?xml version="1.0" encoding="UTF-8"?>';
            $sitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
            
            // Homepage (exists)
            $sitemap .= $this->addUrl(url('/'), now(), '1.0', 'daily');
            
            // Exam showcase (exists)
            $sitemap .= $this->addUrl(url('/exams'), now(), '0.8', 'weekly');
            
            // Pricing page (exists)
            $sitemap .= $this->addUrl(url('/pricing'), now(), '0.8', 'weekly');
            
            // About page (exists)
            $sitemap .= $this->addUrl(url('/about'), now(), '0.7', 'monthly');
            
            // Contact page (exists)
            $sitemap .= $this->addUrl(url('/contact'), now(), '0.6', 'monthly');
        
            // Public quizzes (if table exists)
            try {
                $quizzes = Quiz::where('is_public', true)
                    ->where('status', 'published')
                    ->orderBy('created_at', 'desc')
                    ->get();
                    
                foreach ($quizzes as $quiz) {
                    $sitemap .= $this->addUrl(
                        url('/exam/' . $quiz->id . '/preview'),
                        $quiz->updated_at,
                        '0.8',
                        'weekly'
                    );
                }
            } catch (\Exception $e) {
                // Quiz table doesn't exist yet, skip
            }
        
            // Legal pages (if table exists)
            try {
                $legalPages = LegalPage::where('status', 1)->get();
                foreach ($legalPages as $page) {
                    $sitemap .= $this->addUrl(
                        url('/legal/' . $page->slug),
                        $page->updated_at,
                        '0.5',
                        'monthly'
                    );
                }
            } catch (\Exception $e) {
                // LegalPage table doesn't exist yet, skip
            }
            
            // Built-in legal pages (these exist)
            $sitemap .= $this->addUrl(url('/terms'), now(), '0.5', 'monthly');
            $sitemap .= $this->addUrl(url('/privacy'), now(), '0.5', 'monthly');
            $sitemap .= $this->addUrl(url('/cookie'), now(), '0.5', 'monthly');
            $sitemap .= $this->addUrl(url('/refund'), now(), '0.5', 'monthly');
        
            $sitemap .= '</urlset>';
            
            return response($sitemap, 200)
                ->header('Content-Type', 'application/xml');
                
        } catch (\Exception $e) {
            // Return basic sitemap if there's any error
            $basicSitemap = '<?xml version="1.0" encoding="UTF-8"?>';
            $basicSitemap .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
            $basicSitemap .= $this->addUrl(url('/'), now(), '1.0', 'daily');
            $basicSitemap .= $this->addUrl(url('/exams'), now(), '0.8', 'weekly');
            $basicSitemap .= $this->addUrl(url('/pricing'), now(), '0.8', 'weekly');
            $basicSitemap .= $this->addUrl(url('/about'), now(), '0.7', 'monthly');
            $basicSitemap .= $this->addUrl(url('/contact'), now(), '0.6', 'monthly');
            $basicSitemap .= $this->addUrl(url('/terms'), now(), '0.5', 'monthly');
            $basicSitemap .= $this->addUrl(url('/privacy'), now(), '0.5', 'monthly');
            $basicSitemap .= $this->addUrl(url('/cookie'), now(), '0.5', 'monthly');
            $basicSitemap .= $this->addUrl(url('/refund'), now(), '0.5', 'monthly');
            $basicSitemap .= '</urlset>';
            
            return response($basicSitemap, 200)
                ->header('Content-Type', 'application/xml');
        }
    }
    
    private function addUrl($loc, $lastmod, $priority, $changefreq)
    {
        return '<url>' .
            '<loc>' . htmlspecialchars($loc) . '</loc>' .
            '<lastmod>' . $lastmod->format('Y-m-d\TH:i:s\Z') . '</lastmod>' .
            '<priority>' . $priority . '</priority>' .
            '<changefreq>' . $changefreq . '</changefreq>' .
            '</url>';
    }
}

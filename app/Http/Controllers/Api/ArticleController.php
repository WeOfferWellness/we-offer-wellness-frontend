<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Http;

class ArticleController extends Controller
{
    public function index(Request $request)
    {
      $limit = (int) $request->integer('limit', 6);

      // Normalise article images to atease host regardless of source
      $atease = rtrim((string) env('ATEASE_BASE_URL', env('ALT_STORAGE_BASE', 'https://atease.weofferwellness.co.uk')), '/');
      $normalizeImg = function ($u) use ($atease) {
          if (!$u) return $u;
          $s = (string) $u;
          if (preg_match('#^https?://#i', $s)) {
              $parts = parse_url($s) ?: [];
              $path = ($parts['path'] ?? '/') ?: '/';
              $query = isset($parts['query']) && $parts['query'] !== '' ? ('?'.$parts['query']) : '';
              return $atease . $path . $query;
          }
          $p = '/'.ltrim($s, '/');
          return $atease . $p;
      };

      // 1) Prefer Mindful Times API if configured/available
      $timesBase = rtrim((string) env('TIMES_BASE_URL', ''), '/');
      if ($timesBase !== '') {
        try {
          $res = Http::timeout(4)->acceptJson()->get($timesBase.'/api/articles', [ 'limit' => $limit ]);
          if ($res->successful()) {
            $json = $res->json();
            $rows = is_array($json['data'] ?? null) ? $json['data'] : $json;
            if (is_array($rows)) {
              // Force image host
              $out = [];
              foreach ($rows as $row) {
                  if (is_array($row) && isset($row['img'])) {
                      $row['img'] = $normalizeImg($row['img']);
                  }
                  $out[] = $row;
              }
              return response()->json($out);
            }
          }
        } catch (\Throwable $e) {
          // fall through to local
        }
      }

      // 2) Fallback to local DB if remote not available
      $base = Article::query()->with(['featuredMedia', 'backendFeaturedMedia', 'category']);
      $queryPublished = (clone $base)
        ->where(function($q){
          $q->where('status', 'published')
            ->orWhere('status', 'active')
            ->orWhere('status', 1)
            ->orWhere('status', 'Published')
            ->orWhere('status', 'PUBLISHED');
        })
        ->latest('id');

      $articles = $queryPublished->limit($limit)->get();
      if ($articles->isEmpty()) {
        $articles = (clone $base)->latest('id')->limit($limit)->get();
      }

      $norm = $normalizeImg; // import into closure
      $items = $articles->map(function(Article $a) use ($norm){
        $img = null;
        $resolvedMedia = null;
        if ($a->relationLoaded('backendFeaturedMedia') && $a->backendFeaturedMedia) {
          $resolvedMedia = $a->backendFeaturedMedia;
        } elseif ($a->relationLoaded('featuredMedia') && $a->featuredMedia) {
          $resolvedMedia = $a->featuredMedia;
        }

        if ($resolvedMedia) {
          $media = $resolvedMedia;
          $path = $media->url ?? $media->path ?? $media->media_url ?? null;
          if ($path) {
            if (str_starts_with($path, 'http://') || str_starts_with($path, 'https://')) {
              $img = $path;
            } else {
              $backend = rtrim((string) env('BACKEND_ASSET_URL', env('BACKEND_URL', '')), '/');
              $clean = ltrim($path, '/');
              $img = $backend ? ($backend . '/storage/' . $clean) : asset('storage/'.$clean);
            }
          }
        }
        // Fallback: first from media()
        if (!$img && method_exists($a, 'backendMedia') && $a->backendMedia()->exists()) {
          $m = $a->backendMedia()->first();
          $p = $m->url ?? $m->path ?? $m->media_url ?? null;
          if ($p) {
            if (str_starts_with($p, 'http://') || str_starts_with($p, 'https://')) {
              $img = $p;
            } else {
              $backend = rtrim((string) env('BACKEND_ASSET_URL', env('BACKEND_URL', '')), '/');
              $clean = ltrim($p, '/');
              $img = $backend ? ($backend . '/storage/' . $clean) : asset('storage/'.$clean);
            }
          }
        }
        if (!$img && method_exists($a, 'media') && $a->media()->exists()) {
          $m = $a->media()->first();
          $p = $m->url ?? $m->path ?? $m->media_url ?? null;
          if ($p) {
            if (str_starts_with($p, 'http://') || str_starts_with($p, 'https://')) {
              $img = $p;
            } else {
              $backend = rtrim((string) env('BACKEND_ASSET_URL', env('BACKEND_URL', '')), '/');
              $clean = ltrim($p, '/');
              $img = $backend ? ($backend . '/storage/' . $clean) : asset('storage/'.$clean);
            }
          }
        }

        // Normalise to atease host
        if ($img) { $img = $norm($img); }

        // Build external Times URL: category/year/month/slug-id
        $catName = optional($a->category)->name ?: 'journal';
        $cat = Str::slug($catName);
        $year = optional($a->created_at)->format('Y') ?? date('Y');
        $month = optional($a->created_at)->format('m') ?? date('m');
        $slug = Str::slug((string) ($a->title ?: 'article'));
        $timesBase = rtrim(env('TIMES_BASE_URL', 'https://times.weofferwellness.co.uk'), '/');
        $href = $timesBase . "/{$cat}/{$year}/{$month}/{$slug}-{$a->id}";

        return [
          'id' => $a->id,
          'title' => $a->title,
          'excerpt' => str($a->content ?? '')->stripTags()->limit(140)->value(),
          'tag' => optional($a->category)->name ?? 'MindfulTimes',
          'img' => $img,
          'href' => $href,
          'created_at' => optional($a->created_at)->toIso8601String(),
        ];
      });

      return response()->json($items);
    }
}

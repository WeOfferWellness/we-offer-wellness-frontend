<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Schedule::command('store:run-abandoned-cart')->hourly()->withoutOverlapping();
Schedule::command('store:reconcile-paid-checkouts')
    ->everyMinute()
    ->withoutOverlapping();
use App\Models\LegacyPageVisit;
use App\Models\Product;
use App\Models\ProductStatus;
use App\Models\V3Subscriber;
use App\Services\WhatCategoryCacheService;
use App\Support\BotPathMatcher;
use App\Support\ExternalReviews\VendorReviewFetcher;
use App\Support\ExternalReviews\VendorReviewPublisher;
use Illuminate\Support\Str;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Register product status maintenance command
Artisan::command('products:set-drafts-live {--dry-run}', function() {
    $draft = ProductStatus::where('status', 'draft')->first();
    $live = ProductStatus::where('status', 'live')->first();
    if (!$live) {
        $this->error('Live status not found');
        return 1;
    }
    if (!$draft) {
        $this->info('No draft status found; nothing to update.');
        return 0;
    }
    $count = (int) Product::where('product_status_id', $draft->id)->count();
    $this->info("Found {$count} draft products.");
    if ($this->option('dry-run')) {
        $this->info('Dry run: no changes made.');
        return 0;
    }
    $updated = Product::where('product_status_id', $draft->id)->update(['product_status_id' => $live->id]);
    $this->info("Updated {$updated} products to live.");
    return 0;
})->purpose('Update any products with status=draft to status=live');

// Debug Variant Inspector simulation across products
Artisan::command('wow:test-variant-inspector {--limit=} {--product=}', function(){
    $limit = $this->option('limit') ? (int)$this->option('limit') : null;
    $only = $this->option('product') ? (int)$this->option('product') : null;
    $q = Product::query()->with(['options.values','variants'])->orderBy('id','asc');
    if ($only) $q->where('id', $only);
    if ($limit) $q->limit($limit);

    $total=0; $fails=0; $failed=[];
    $this->info('Running Debug Variant Inspector simulation...');
    foreach ($q->cursor() as $p) {
        $total++;
        [$optionsArr, $variants] = (function($product){
            $valueLookup = [];
            $optionsArr = $product->options->map(function($o) use (&$valueLookup){
                $vals = $o->values->pluck('value', 'id')->all();
                foreach ($vals as $id => $label) { $valueLookup[$id] = $label; }
                return [ 'name' => $o->name ?? $o->meta_name, 'meta_name' => $o->meta_name, 'values' => array_values($vals) ];
            })->values()->all();
            $variantsArr = $product->variants->map(function($v) use ($valueLookup){
                if ($v->product_id == 6948) {
                    error_log('DBG RAW 6948 id='.$v->id.' options='.json_encode($v->options));
                }
                $optList = [];
                if (is_array($v->options) && count($v->options)) {
                    $ordered = []; $leftover = [];
                    foreach ($v->options as $k=>$val){ if (preg_match('/^option\d+$/',(string)$k)) $ordered[$k]=$val; else $leftover[$k]=$val; }
                    if (!empty($ordered)) { ksort($ordered); foreach ($ordered as $val) $optList[]=$val; foreach ($leftover as $val) $optList[]=$val; }
                    else { $optList = array_values($v->options); }
                } elseif (is_string($v->options) && strlen($v->options)) {
                    $decoded = json_decode($v->options, true);
                    if (is_array($decoded) && !empty($decoded)) {
                        $ordered = []; $leftover = [];
                        foreach ($decoded as $k => $val) { if (preg_match('/^option\d+$/',(string)$k)) $ordered[$k] = $val; else $leftover[$k] = $val; }
                        if (!empty($ordered)) { ksort($ordered); foreach ($ordered as $val) $optList[] = $val; foreach ($leftover as $val) $optList[] = $val; }
                        else { $optList = array_values($decoded); }
                    }
                } elseif (!empty($v->option_ids)) {
                    $ids = json_decode($v->option_ids, true) ?: [];
                    foreach ($ids as $oid) { if (isset($valueLookup[$oid])) $optList[] = $valueLookup[$oid]; }
                }
                return [ 'id'=>$v->id, 'options'=>$optList, 'price'=>(float)$v->price, 'compare'=>null, 'available'=>($v->inventory_quantity ?? 0) > 0 || is_null($v->inventory_quantity) ];
            })->values()->all();
            // Fallback: if all variant options empty but sessions exist, map sessions by price order → variants
            try {
                $allEmpty = true; foreach ($variantsArr as $vv) { if (!empty($vv['options'])) { $allEmpty = false; break; } }
                if ($allEmpty) {
                    $peopleIdx = $sessionsIdx = $locIdx = null;
                    foreach ($optionsArr as $i => $opt) {
                        $nm = strtolower((string)($opt['meta_name'] ?? $opt['name'] ?? ''));
                        if ($peopleIdx === null && str_contains($nm, 'person')) $peopleIdx = $i;
                        if ($sessionsIdx === null && str_contains($nm, 'session')) $sessionsIdx = $i;
                        if ($locIdx === null && str_contains($nm, 'location')) $locIdx = $i;
                    }
                    if ($sessionsIdx !== null) {
                        $sessionVals = $optionsArr[$sessionsIdx]['values'] ?? [];
                        $score = function($label){ $s=strtolower((string)$label); if (preg_match('/(\d+(?:\.\d+)?)\s*(hour|hr|hours|hrs)/',$s,$m)) return (float)$m[1]*60; if (preg_match('/(\d+(?:\.\d+)?)\s*(min|mins|minute|minutes)/',$s,$m)) return (float)$m[1]; if (preg_match('/\d+/',$s,$m)) return (float)$m[0]; return 0.0; };
                        $pairs = []; foreach ($sessionVals as $lbl){ $pairs[] = ['label'=>$lbl,'n'=>$score($lbl)]; }
                        usort($pairs, fn($a,$b)=> $a['n'] <=> $b['n']);
                        $sortedSession = array_map(fn($p)=>$p['label'], $pairs);
                        $sortedVariants = $variantsArr; usort($sortedVariants, fn($a,$b)=> (float)$a['price'] <=> (float)$b['price']);
                        $peopleLabel = ($peopleIdx!==null && isset($optionsArr[$peopleIdx]['values'][0])) ? (string)$optionsArr[$peopleIdx]['values'][0] : '';
                        $locLabel = ($locIdx!==null && isset($optionsArr[$locIdx]['values'][0])) ? (string)$optionsArr[$locIdx]['values'][0] : '';
                        $optCount = count($optionsArr);
                        $mapped = [];
                        foreach ($sortedVariants as $i => $sv) {
                            $sessionLabel = (string)($sortedSession[$i] ?? ($sessionVals[$i] ?? ''));
                            $optList = array_fill(0, $optCount, '');
                            if ($peopleIdx !== null) $optList[$peopleIdx] = $peopleLabel;
                            if ($sessionsIdx !== null) $optList[$sessionsIdx] = $sessionLabel;
                            if ($locIdx !== null) $optList[$locIdx] = $locLabel;
                            $sv['options'] = $optList;
                            $mapped[$sv['id']] = $sv;
                        }
                        foreach ($variantsArr as $k => $vv) { if (isset($mapped[$vv['id']])) $variantsArr[$k] = $mapped[$vv['id']]; }
                    }
                }
            } catch (\Throwable $e) { /* ignore */ }
            return [$optionsArr, $variantsArr];
        })($p);

        $issues = (function($p, $optionsArr, $variants){
            $issues=[]; if (empty($variants)) return ['no variants'];
            $pi=$si=$li=null; foreach ($optionsArr as $i=>$opt){ $nm=strtolower((string)($opt['name']??$opt['meta_name']??'')); if($pi===null && str_contains($nm,'person'))$pi=$i; if($si===null && str_contains($nm,'session'))$si=$i; if($li===null && str_contains($nm,'location'))$li=$i; }
            $peopleVals = ($pi!==null)?($optionsArr[$pi]['values']??[]):[]; if(empty($peopleVals)) $peopleVals=[null];
            $sessionVals = ($si!==null)?($optionsArr[$si]['values']??[]):[]; if(empty($sessionVals)) $sessionVals=[null];
            $locVals = ($li!==null)?($optionsArr[$li]['values']??[]):[]; if(empty($locVals)) $locVals=[null];
            $norm = fn($s)=> strtolower(preg_replace('~[^a-z0-9]+~','', (string)$s));
            $num = function($s){ if (preg_match('~\d+~',(string)$s,$m)) return (int)$m[0]; return null; };
            $sessionsOrdered = array_map(fn($v)=> is_array($v)?(string)($v['value']??''):(string)$v, $sessionVals);
            $kvFor = function($v) use ($optionsArr,$si,$sessionsOrdered,$norm,$num){
                $kv=[]; $toks=array_map('strval', $v['options']??[]);
                foreach ($toks as $t){ $lc=strtolower($t); if(str_contains($lc,'online'))$kv['format']='Online'; if(str_contains($lc,'in-person')||str_contains($lc,'in person')||str_contains($lc,'inperson'))$kv['format']='In-person'; $n=$num($t); if($n!==null && !isset($kv['people']))$kv['people']=$n; }
                if (!isset($kv['location'])){ if (in_array('online', array_map($norm,$toks), true)) $kv['location']='Online'; }
                if (!isset($kv['format'])) $kv['format'] = (($kv['location']??'')==='Online')?'Online':'In-person';
                if ($si!==null){ $sVals=array_map(fn($x)=>is_array($x)?(string)($x['value']??''):(string)$x, ($optionsArr[$si]['values']??[])); $exact=null; foreach($sVals as $lbl){ foreach($toks as $t){ if((string)$t===(string)$lbl){ $exact=$lbl; break 2; } } } if($exact) $kv['sessions']=$exact; else { $tokNums=array_values(array_filter(array_map($num,$toks),fn($x)=>$x!==null)); foreach($sVals as $lbl){ if($num($lbl)!==null && in_array($num($lbl),$tokNums,true)){ $kv['sessions']=$lbl; break; } } } }
                return $kv; };
            $matchesSession = fn($v,$sLbl)=> (!$sLbl) ? true : (function() use($v,$sLbl,$kvFor,$num){ $kv=$kvFor($v); $opts=$v['options']??[]; $wn=$num($sLbl); if(isset($kv['sessions'])) return ($wn!==null)?($num($kv['sessions'])===$wn):((string)$kv['sessions']===(string)$sLbl); return ($wn!==null)?(bool)array_filter($opts,fn($t)=>$num($t)===$wn):in_array((string)$sLbl,array_map('strval',$opts),true);} )();
            $matchesPeople = fn($v,$n)=> ($n===null) ? true : (function() use($v,$n,$kvFor,$num){ $kv=$kvFor($v); $opts=$v['options']??[]; if(isset($kv['people'])) return ((int)$kv['people'] === (int)$n); return (bool)array_filter($opts,fn($t)=>$num($t)===(int)$n);} )();
            $matchesLocation = fn($v,$loc)=> (!$loc) ? true : (function() use($v,$loc,$kvFor,$norm){ $w=$norm($loc); $kv=$kvFor($v); $have=isset($kv['location'])?$norm($kv['location']):''; if($have) return $have===$w || str_contains($have,$w) || str_contains($w,$have); $opts=$v['options']??[]; foreach($opts as $t){ $tn=$norm($t); if(!$tn || preg_match('/^[0-9]+$/',$tn)) continue; if($tn===$w || str_contains($tn,$w) || str_contains($w,$tn)) return true; } return false;} )();
            $rankBySessions = function($v) use($kvFor,$sessionsOrdered,$num){ $kv=$kvFor($v); $lbl=(string)($kv['sessions']??''); $idx=array_search($lbl,$sessionsOrdered,true); if($idx!==false) return (int)$idx; $kn=$num($lbl); if($kn!==null){ foreach($sessionsOrdered as $i=>$sv){ if($num($sv)===$kn) return $i; } } return 999; };
            $seenDiffByLoc=[];
            foreach ($locVals as $loc){ foreach ($sessionVals as $s){ foreach($peopleVals as $pval){
                if ($p->id == 6948 && $s === (string)($sessionVals[0] ?? '')){
                    // debug location matching per variant
                    foreach ($variants as $vv){
                        $ok = $matchesLocation($vv, $loc);
                        echo "DBG MATCH 6948 loc='".($loc??'')."' v=".$vv['id']." ";
                        echo $ok?"YES":"NO"; echo " tokens=[".implode('|', array_map('strval', $vv['options']??[]))."]\n";
                    }
                }
                $pool = $loc ? array_values(array_filter($variants, fn($v)=>$matchesLocation($v,$loc))) : $variants;
                if (!empty($pool)){
                    if ($s){ $hit=array_values(array_filter($pool, fn($v)=>$matchesSession($v,$s))); if(!empty($hit)) $pool=$hit; }
                    $wantN = $pval!==null ? $num($pval) : null; if ($wantN!==null){ $hit=array_values(array_filter($pool, fn($v)=>$matchesPeople($v,$wantN))); if(!empty($hit)) $pool=$hit; }
                }
                $chosen=null; if (count($pool)===1) $chosen=$pool[0]; elseif(!empty($pool)){ usort($pool, fn($a,$b)=> $rankBySessions($a) <=> $rankBySessions($b)); $chosen=$pool[0]; }
                if ($p->id == 6948) { echo "DBG 6948 -> loc='".($loc??'')."' sess='".($s??'')."' pool=".count($pool)." ids=[".implode(',', array_map(fn($v)=>$v['id'],$pool))."] chose=".($chosen['id']??'null')."\n"; }
                if (!$chosen){ $issues[] = "No variant for loc=['".($loc??'')."'], sessions=['".($s??'')."'], people=['".($pval??'')."']"; continue; }
                if ($loc && $s){ $key=(string)$s; $seenDiffByLoc[$key] = $seenDiffByLoc[$key] ?? []; $seenDiffByLoc[$key][$loc] = (string)$chosen['id']; }
            } } }
            foreach ($seenDiffByLoc as $sLbl=>$map){ if(count($map)>1){ $ids=array_values($map); if(count(array_unique($ids))===1){ $issues[] = "Location changes did not change variant for sessions='$sLbl'"; } } }
            return array_values(array_unique($issues));
        })($p, $optionsArr, $variants);

        if (!empty($issues)) {
            $fails++;
            $url = (function($p){ $slug = strtolower(trim(preg_replace('~[^a-z0-9]+~','-', (string)$p->title))); $seg = (function($p){ $t=strtolower((string)$p->product_type); $tags=strtolower((string)$p->tags_list); if (str_contains($t,'workshop')) return 'workshops'; if (str_contains($t,'event')) return 'events'; if (str_contains($t,'class')) return 'classes'; if (str_contains($t,'retreat')) return 'retreats'; if (str_contains($t,'gift')||str_contains($tags,'gift')) return 'gifts'; return 'therapies'; })($p); return url('/'.$seg.'/'.$p->id.'-'.$slug); })($p);
            $this->line("- FAIL: {$p->id} {$p->title} -> {$url}");
            foreach ($issues as $msg) { $this->line("    • $msg"); }
            $failed[] = $url;
        }
    }
    $this->newLine();
    $this->info("Scanned $total products. Failing: $fails");
    if ($fails) { $this->newLine(); $this->info('Problem URLs:'); foreach ($failed as $u){ $this->line('- '.$u); } }
})->purpose('Simulate Debug Variant Inspector matching and report issues');

Artisan::command('bot-traffic:purge {--dry-run}', function () {
    $dryRun = (bool) $this->option('dry-run');

    $purge = function (string $modelClass, callable $shouldDelete) use ($dryRun) {
        $removed = 0;
        $modelClass::query()->orderBy('id')
            ->chunkById(500, function ($models) use (&$removed, $modelClass, $shouldDelete, $dryRun) {
                $ids = [];
                foreach ($models as $row) {
                    if ($shouldDelete($row)) {
                        $ids[] = $row->id;
                    }
                }

                if (!$dryRun && !empty($ids)) {
                    $modelClass::whereIn('id', $ids)->delete();
                }

                $removed += count($ids);
            });

        return $removed;
    };

    $legacyRemoved = $purge(LegacyPageVisit::class, function ($visit) {
        return BotPathMatcher::matchesPath($visit->path)
            || BotPathMatcher::matchesUrl($visit->full_url ?? null)
            || BotPathMatcher::matchesPath($visit->slug);
    });

    $subscribersRemoved = $purge(V3Subscriber::class, function ($subscriber) {
        return BotPathMatcher::matchesPath($subscriber->landing_path)
            || BotPathMatcher::matchesUrl($subscriber->referrer ?? null);
    });

    if ($dryRun) {
        $this->info("Dry run complete. {$legacyRemoved} legacy hits and {$subscribersRemoved} subscriber records would be removed.");
    } else {
        $this->info("Removed {$legacyRemoved} legacy hits and {$subscribersRemoved} subscriber records originating from blocked paths.");
    }
})->purpose('Remove legacy page hits and V3 subscriber records created by blocked bot paths');

Artisan::command('reviews:recover {--vendor-id=} {--limit=0} {--with-ddg} {--skip-places} {--dry-run}', function (VendorReviewFetcher $fetcher) {
    $options = [
        'vendor_id' => $this->option('vendor-id'),
        'limit' => (int) ($this->option('limit') ?? 0),
        'with_ddg' => (bool) $this->option('with-ddg'),
        'skip_places' => (bool) $this->option('skip-places'),
        'dry_run' => (bool) $this->option('dry-run'),
    ];

    return $fetcher->run($this, $options);
})->purpose('Recover external vendor reviews using Google Places, Gemini, and JSON-LD scraping');

Artisan::command('reviews:publish {--vendor-id=} {--limit=0} {--dry-run} {--refresh}', function (VendorReviewPublisher $publisher) {
    $options = [
        'vendor_id' => $this->option('vendor-id'),
        'limit' => (int) ($this->option('limit') ?? 0),
        'dry_run' => (bool) $this->option('dry-run'),
        'refresh' => (bool) $this->option('refresh'),
    ];

    return $publisher->run($this, $options);
})->purpose('Publish vendor review rows into the primary reviews table for vendors');

Artisan::command('categories:export-what {--path=}', function (WhatCategoryCacheService $service) {
    $path = $service->export($this->option('path') ?: null);
    $payload = $service->load();
    $count = (int) ($payload['count'] ?? 0);
    $this->info("Exported {$count} categories to {$path}");
    return 0;
})->purpose('Export the nightly what-category lookup cache');

Schedule::command('locations:export-catalog')
    ->dailyAt('00:00')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('categories:export-what')
    ->dailyAt('00:00')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('sitemaps:generate')
    ->dailyAt('00:00')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('sitemaps:validate --skip-http')
    ->dailyAt('00:10')
    ->withoutOverlapping()
    ->runInBackground();

Schedule::command('search-console:submit-sitemap')
    ->dailyAt('00:20')
    ->withoutOverlapping()
    ->runInBackground();

<?php
namespace Config;
use App\Filters\AuthFilter;use App\Filters\RoleFilter;use CodeIgniter\Config\Filters as BaseFilters;use CodeIgniter\Filters\Cors;use CodeIgniter\Filters\CSRF;use CodeIgniter\Filters\ForceHTTPS;use CodeIgniter\Filters\Honeypot;use CodeIgniter\Filters\InvalidChars;use CodeIgniter\Filters\PageCache;use CodeIgniter\Filters\PerformanceMetrics;use CodeIgniter\Filters\SecureHeaders;
class Filters extends BaseFilters {
 public array $aliases=['csrf'=>CSRF::class,'honeypot'=>Honeypot::class,'invalidchars'=>InvalidChars::class,'secureheaders'=>SecureHeaders::class,'cors'=>Cors::class,'forcehttps'=>ForceHTTPS::class,'pagecache'=>PageCache::class,'performance'=>PerformanceMetrics::class,'auth'=>AuthFilter::class,'role'=>RoleFilter::class];
 public array $required=['before'=>['forcehttps','pagecache'],'after'=>['pagecache','performance']];
 public array $globals=['before'=>['csrf'],'after'=>['secureheaders']]; public array $methods=[]; public array $filters=[];
}

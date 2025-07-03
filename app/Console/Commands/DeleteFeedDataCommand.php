<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Feed;
use App\Models\UnitConversion;
use App\Models\FeedUsageDetail;

class DeleteFeedDataCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'feed:delete-all {--company= : Company ID to delete Feed data for} {--force : Force delete without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Delete all Feed data and related unit conversions, optionally for a specific company.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $companyId = $this->option('company');
        $scopeMsg = $companyId ? "for company_id=$companyId" : 'for ALL companies';
        $this->warn("⚠️  WARNING: This will delete Feed data and related unit conversions $scopeMsg!");

        // Ambil Feed yang akan dihapus
        $feedsQuery = $companyId ? Feed::where('company_id', $companyId) : Feed::query();
        $feeds = $feedsQuery->get();
        $feedIds = $feeds->pluck('id')->toArray();

        // Validasi: cek apakah Feed masih digunakan di FeedUsageDetail
        $usedFeedIds = FeedUsageDetail::whereIn('feed_id', $feedIds)->pluck('feed_id')->unique()->toArray();
        if (count($usedFeedIds) > 0 && !$this->option('force')) {
            $usedFeeds = $feeds->whereIn('id', $usedFeedIds);
            $this->error('❌ Tidak dapat menghapus. Ada Feed yang masih digunakan di FeedUsage:');
            $this->table(['ID', 'Code', 'Name'], $usedFeeds->map(fn($f) => [$f->id, $f->code, $f->name])->toArray());
            $this->warn('Gunakan --force untuk menghapus paksa (DANGER: data FeedUsageDetail akan orphan).');
            return 1;
        }
        if (count($usedFeedIds) > 0 && $this->option('force')) {
            $usedFeeds = $feeds->whereIn('id', $usedFeedIds);
            $this->warn('⚠️  Ada Feed yang masih digunakan di FeedUsage. Data FeedUsageDetail akan orphan!');
            $this->table(['ID', 'Code', 'Name'], $usedFeeds->map(fn($f) => [$f->id, $f->code, $f->name])->toArray());
        }

        if (!$this->option('force')) {
            if (!$this->confirm("Are you sure you want to proceed $scopeMsg?")) {
                $this->info('Operation cancelled.');
                return 0;
            }
            if (!$this->confirm('This action cannot be undone. Continue?')) {
                $this->info('Operation cancelled.');
                return 0;
            }
        }

        if ($companyId) {
            $feedCount = $feeds->count();
            $conversionCount = UnitConversion::where('type', 'Feed')->whereIn('item_id', $feedIds)->count();
            // Soft delete Feed
            $deletedFeeds = Feed::where('company_id', $companyId)->delete();
            // Soft delete UnitConversion
            $deletedConversions = UnitConversion::where('type', 'Feed')->whereIn('item_id', $feedIds)->delete();
            $this->info("Deleted $feedCount Feed records for company_id=$companyId.");
            $this->info("Deleted $conversionCount UnitConversion records (type=Feed) for company_id=$companyId.");
        } else {
            $feedCount = $feeds->count();
            $conversionCount = UnitConversion::where('type', 'Feed')->count();
            // Soft delete Feed
            $deletedFeeds = Feed::query()->delete();
            // Soft delete UnitConversion
            $deletedConversions = UnitConversion::where('type', 'Feed')->delete();
            $this->info("Deleted $feedCount Feed records (ALL companies).");
            $this->info("Deleted $conversionCount UnitConversion records (type=Feed, ALL companies).");
        }
        $this->info('✅ Feed data and related unit conversions deleted.');
        return 0;
    }
}

<?php
// app/Jobs/ProcessProductImageJob.php
namespace App\Jobs;

use App\Models\ProductImage;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Laravel\Facades\Image;

class ProcessProductImageJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        private string $tempPath,
        private int $productId,
        private bool $isMain = false
    ) {}

    public function handle(): void
    {
        $image    = Image::read(Storage::path($this->tempPath));
        $filename = 'products/' . uniqid() . '.webp';

        // resize + convert to webp
        $image->scale(width: 800)
              ->toWebp(quality: 85)
              ->save(Storage::path('public/' . $filename));

        // احذف الـ temp
        Storage::delete($this->tempPath);

        ProductImage::create([
            'product_id' => $this->productId,
            'path'       => $filename,
            'is_main'    => $this->isMain,
        ]);
    }
}
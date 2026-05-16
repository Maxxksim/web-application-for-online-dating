<?php

namespace App\Jobs;

use App\Models\Profile;
use App\Services\PhotoService;
use App\Services\ProfileService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class ProcessValidatePhoto implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly Profile $profile)
    {

    }

    public function handle(PhotoService $photoService, ProfileService $profileService): void
    {
        $photos = $this->profile->photos()->where('is_approved', false)->get();
        $validatedPhotos = $photoService->validateUserPhotos($photos->toArray());

        foreach ($validatedPhotos as $namePhoto => $validatedPhoto) {
            if ($validatedPhoto['result']) {
                $this->profile->photos()
                    ->where('path', 'profile_photos/' . $namePhoto)
                    ->update(['is_approved' => true]);
            } else {
                $this->profile->photos()
                    ->where('path', 'profile_photos/' . $namePhoto)
                    ->delete();

                Storage::disk('public')->delete('profile_photos/' . $namePhoto);
            }
        }
    }

    public function failed(\Throwable $exception): void
    {
        logger()->error($exception);
        foreach ($this->profile->photos()->where('is_approved', false)->get() as $photo) {
            Storage::disk('public')->delete($photo->path);
            $photo->delete();
        }
    }
}

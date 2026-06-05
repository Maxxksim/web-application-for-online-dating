<?php

namespace App\Jobs;

use App\Events\ProfilePhotoValidated;
use App\Models\Profile;
use App\Services\PhotoService;
use App\Services\ProfileService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
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
        $lock = Cache::lock('photo_validation_' . $this->profile->id, 60);

        if (!$lock->get()) {
            $this->release(10);
            return;
        }

        try {
            $photos = $this->profile->photos()->where('is_approved', false)->get();

            if ($photos->isEmpty()) {
                return;
            }

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

            $profileService->enableIfReady($this->profile);

            broadcast(new ProfilePhotoValidated($this->profile->id, [
                'photos' => $validatedPhotos,
                'approved_count' => collect($validatedPhotos)->where('result', true)->count(),
                'rejected_count' => collect($validatedPhotos)->where('result', false)->count(),
            ]));
        } finally {
            $lock->release();
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

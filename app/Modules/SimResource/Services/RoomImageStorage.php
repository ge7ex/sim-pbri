<?php

namespace App\Modules\SimResource\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final class RoomImageStorage
{
    public function validPath(?string $path, int $collegeId): bool
    {
        return $path !== null && preg_match('~^'.preg_quote((string) $collegeId, '~').'/[a-f0-9-]{36}\\.jpg$~D', $path) === 1;
    }

    public function store(UploadedFile $file, int $collegeId): string
    {
        if (! extension_loaded('gd')) {
            throw ValidationException::withMessages(['image' => 'ระบบยังไม่พร้อมประมวลผลภาพ กรุณาแจ้งผู้ดูแลระบบให้เปิด PHP GD']);
        }
        // Decode only bounded raster images, then create a fresh JPEG without original metadata or trailing payloads.
        $size = @getimagesize($file->getRealPath());
        if (! $size || ! in_array($size[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)
            || $size[0] < 1 || $size[1] < 1 || $size[0] > 3000 || $size[1] > 3000 || $file->getSize() > 5 * 1024 * 1024) {
            throw ValidationException::withMessages(['image' => 'กรุณาใช้ภาพ JPEG, PNG หรือ WebP ไม่เกิน 5 MB และ 3000 × 3000 พิกเซล']);
        }
        $source = @imagecreatefromstring(file_get_contents($file->getRealPath()));
        if ($source === false) {
            throw ValidationException::withMessages(['image' => 'ไม่สามารถอ่านภาพนี้ได้ กรุณาเลือกไฟล์ภาพใหม่']);
        }
        $scale = min(1, 1600 / max($size[0], $size[1]));
        $width = max(1, (int) round($size[0] * $scale));
        $height = max(1, (int) round($size[1] * $scale));
        $output = imagecreatetruecolor($width, $height);
        $stream = fopen('php://temp', 'w+b');
        try {
            imagefill($output, 0, 0, imagecolorallocate($output, 255, 255, 255));
            imagecopyresampled($output, $source, 0, 0, 0, 0, $width, $height, $size[0], $size[1]);
            if (! imagejpeg($output, $stream, 85)) {
                throw ValidationException::withMessages(['image' => 'ประมวลผลภาพไม่สำเร็จ กรุณาลองอีกครั้ง']);
            }
            rewind($stream);
            $path = $collegeId.'/'.Str::uuid().'.jpg';
            try {
                Storage::disk('room-images')->put($path, $stream);
            } catch (\Throwable $exception) {
                $this->delete($path, $collegeId);
                Log::warning('Room image storage failed', ['college_id' => $collegeId, 'exception_type' => $exception::class]);
                throw ValidationException::withMessages(['image' => 'บันทึกรูปไม่สำเร็จ กรุณาลองอีกครั้งหรือแจ้งผู้ดูแลระบบ']);
            }

            return $path;
        } finally {
            imagedestroy($source);
            imagedestroy($output);
            fclose($stream);
        }
    }

    public function delete(?string $path, int $collegeId): void
    {
        if (! $this->validPath($path, $collegeId)) {
            return;
        }
        try {
            if (! Storage::disk('room-images')->delete($path)) {
                Log::warning('Room image cleanup failed', ['college_id' => $collegeId]);
            }
        } catch (\Throwable $exception) {
            // A cleanup failure must not report a committed update as failed; paths stay private.
            Log::warning('Room image cleanup failed', ['college_id' => $collegeId, 'exception_type' => $exception::class]);
        }
    }
}

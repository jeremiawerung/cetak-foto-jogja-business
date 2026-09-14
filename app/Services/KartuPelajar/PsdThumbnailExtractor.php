<?php

namespace App\Services\KartuPelajar;

/**
 * Ekstrak thumbnail JPEG yang SUDAH tertanam di dalam file .psd (disimpan Photoshop
 * sendiri untuk preview cepat, Image Resource Block ID 1036) — tanpa perlu Imagick/
 * ImageMagick atau me-render ulang PSD-nya. Server ini tidak punya ekstensi Imagick,
 * jadi ini jalan satu-satunya untuk menampilkan preview PSD tanpa software tambahan.
 *
 * Referensi format: Adobe Photoshop File Format Spec, bagian "Image Resources".
 */
class PsdThumbnailExtractor
{
    private const SIGNATURE = '8BPS';

    private const RESOURCE_SIGNATURE = '8BIM';

    private const THUMBNAIL_RESOURCE_ID = 1036;

    /**
     * @return string|null isi JPEG mentah (byte string), atau null kalau file bukan PSD valid / tidak ada thumbnail.
     */
    public function extract(string $psdPath): ?string
    {
        $handle = fopen($psdPath, 'rb');

        if ($handle === false) {
            return null;
        }

        try {
            if (fread($handle, 4) !== self::SIGNATURE) {
                return null;
            }

            // Lewati sisa header tetap (version 2 + reserved 6 + channels 2 + height 4 + width 4 + depth 2 + colormode 2 = 22 byte).
            fseek($handle, 26);

            $colorModeLength = $this->readUInt32($handle);

            if ($colorModeLength === null) {
                return null;
            }

            fseek($handle, $colorModeLength, SEEK_CUR);

            $resourcesLength = $this->readUInt32($handle);

            if ($resourcesLength === null) {
                return null;
            }

            $resourcesEnd = ftell($handle) + $resourcesLength;

            while (ftell($handle) < $resourcesEnd) {
                if (fread($handle, 4) !== self::RESOURCE_SIGNATURE) {
                    return null;
                }

                $resourceId = $this->readUInt16($handle);
                $nameLength = $this->readUInt8($handle);

                if ($resourceId === null || $nameLength === null) {
                    return null;
                }

                // Pascal string nama: 1 byte panjang (sudah dibaca) + N byte data, TOTAL (1+N) di-pad ke genap.
                $bytesToSkipForName = ($nameLength + 1) % 2 === 0 ? $nameLength : $nameLength + 1;
                fseek($handle, $bytesToSkipForName, SEEK_CUR);

                $dataLength = $this->readUInt32($handle);

                if ($dataLength === null) {
                    return null;
                }

                $paddedDataLength = $dataLength % 2 === 0 ? $dataLength : $dataLength + 1;

                if ($resourceId === self::THUMBNAIL_RESOURCE_ID) {
                    // Sub-header thumbnail: format(4)+width(4)+height(4)+widthbytes(4)+totalsize(4)+sizecompressed(4)+bitspixel(2)+planes(2) = 28 byte.
                    fseek($handle, 28, SEEK_CUR);
                    $jpegSize = $dataLength - 28;

                    if ($jpegSize <= 0) {
                        return null;
                    }

                    $jpegData = fread($handle, $jpegSize);

                    return $jpegData !== false ? $jpegData : null;
                }

                fseek($handle, $paddedDataLength, SEEK_CUR);
            }

            return null;
        } finally {
            fclose($handle);
        }
    }

    private function readUInt32($handle): ?int
    {
        $bytes = fread($handle, 4);

        return $bytes !== false && strlen($bytes) === 4 ? unpack('N', $bytes)[1] : null;
    }

    private function readUInt16($handle): ?int
    {
        $bytes = fread($handle, 2);

        return $bytes !== false && strlen($bytes) === 2 ? unpack('n', $bytes)[1] : null;
    }

    private function readUInt8($handle): ?int
    {
        $bytes = fread($handle, 1);

        return $bytes !== false && strlen($bytes) === 1 ? unpack('C', $bytes)[1] : null;
    }
}

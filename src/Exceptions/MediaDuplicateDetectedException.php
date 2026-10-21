<?php

namespace MajidShafik\FilamentMediaLibrary\Exceptions;

use MajidShafik\FilamentMediaLibrary\Models\Media;
use Exception;

class MediaDuplicateDetectedException extends Exception
{
    protected Media $duplicateMedia;
    protected string $hash;

    public function __construct(Media $duplicateMedia, string $hash, string $message = 'تم العثور على صورة مطابقة تماماً في مكتبة الوسائط.')
    {
        parent::__construct($message);
        $this->duplicateMedia = $duplicateMedia;
        $this->hash = $hash;
    }

    public function getDuplicateMedia(): Media
    {
        return $this->duplicateMedia;
    }

    public function getHash(): string
    {
        return $this->hash;
    }
}
